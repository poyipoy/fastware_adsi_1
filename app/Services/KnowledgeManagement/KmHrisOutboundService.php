<?php

namespace App\Services\KnowledgeManagement;

use App\Models\KmCompletionEvent;
use App\Models\KmHrisOutboundEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class KmHrisOutboundService
{
    /** @return array<string, bool> */
    public function gateStatus(): array
    {
        return collect(config('knowledge_management.hris.gates', []))
            ->map(static fn ($value): bool => (bool) $value)->all();
    }

    public function isReady(): bool
    {
        return (bool) config('knowledge_management.hris.enabled', false)
            && collect($this->gateStatus())->every(static fn (bool $passed): bool => $passed)
            && filled(config('knowledge_management.hris.endpoint'))
            && filled(config('knowledge_management.hris.secret'));
    }

    public function stage(int $limit = 500): int
    {
        if (! Schema::hasTable('km_hris_outbound_events')) {
            return 0;
        }
        $created = 0;
        KmCompletionEvent::query()
            ->with(['user', 'document'])
            ->whereDoesntHave('hrisOutboundEvent')
            ->orderBy('id')->limit(max(1, min($limit, 5000)))->get()
            ->each(function (KmCompletionEvent $event) use (&$created): void {
                $employeeId = trim((string) $event->user?->npk);
                if ($employeeId === '') {
                    return;
                }
                $row = KmHrisOutboundEvent::query()->firstOrCreate([
                    'event_key' => 'km.completion.v1:'.$event->getKey(),
                ], [
                    'completion_event_id' => $event->getKey(),
                    'employee_hris_id' => $employeeId,
                    'payload' => [
                        'schema_version' => '1.0',
                        'employee_id' => $employeeId,
                        'document_id' => (int) $event->document_id,
                        'document_version_id' => (int) $event->document_version_id,
                        'document_title' => (string) $event->document?->judul,
                        'completion_type' => $event->completion_type,
                        'completed_at' => $event->completed_at?->toIso8601String(),
                    ],
                    'status' => 'pending',
                ]);
                if ($row->wasRecentlyCreated) {
                    $created++;
                }
            });

        return $created;
    }

    /** @return array{sent: int, failed: int} */
    public function send(int $limit = 50): array
    {
        if (! $this->isReady()) {
            throw new RuntimeException('Gate HRIS belum lengkap atau integrasi belum dikonfigurasi.');
        }
        $result = ['sent' => 0, 'failed' => 0];
        for ($index = 0; $index < max(1, min($limit, 500)); $index++) {
            $event = $this->claim();
            if ($event === null) {
                break;
            }
            try {
                $body = json_encode($event->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                $timestamp = now()->toIso8601String();
                $signature = hash_hmac('sha256', $timestamp.'.'.$body, (string) config('knowledge_management.hris.secret'));
                $response = Http::timeout((int) config('knowledge_management.hris.timeout_seconds', 15))
                    ->withHeaders([
                        'X-KM-Timestamp' => $timestamp,
                        'X-KM-Signature' => $signature,
                        'Idempotency-Key' => $event->event_key,
                        'Accept' => 'application/json',
                    ])->withBody($body, 'application/json')
                    ->post((string) config('knowledge_management.hris.endpoint'));
                if (! $response->successful()) {
                    throw new RuntimeException('HRIS merespons HTTP '.$response->status().'.');
                }
                $event->forceFill([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'next_attempt_at' => null,
                    'last_error' => null,
                    'response_checksum_sha256' => hash('sha256', $response->body()),
                ])->save();
                $result['sent']++;
            } catch (Throwable $exception) {
                report($exception);
                $maximum = max(1, (int) config('knowledge_management.hris.maximum_attempts', 5));
                $attempts = (int) $event->attempts;
                $event->forceFill([
                    'status' => $attempts >= $maximum ? 'failed' : 'retry_pending',
                    'next_attempt_at' => $attempts >= $maximum ? null : now()->addMinutes([5, 30, 120, 360][$attempts - 1] ?? 720),
                    'last_error' => mb_substr($exception->getMessage(), 0, 4000),
                ])->save();
                $result['failed']++;
            }
        }

        return $result;
    }

    private function claim(): ?KmHrisOutboundEvent
    {
        return DB::transaction(function (): ?KmHrisOutboundEvent {
            $maximum = max(1, (int) config('knowledge_management.hris.maximum_attempts', 5));
            $staleBefore = now()->subMinutes(30);
            KmHrisOutboundEvent::query()
                ->where('status', 'processing')
                ->where('updated_at', '<=', $staleBefore)
                ->where('attempts', '>=', $maximum)
                ->update([
                    'status' => 'failed',
                    'next_attempt_at' => null,
                    'last_error' => 'Sinkronisasi HRIS terhenti setelah percobaan terakhir.',
                    'updated_at' => now(),
                ]);
            KmHrisOutboundEvent::query()
                ->where('status', 'processing')
                ->where('updated_at', '<=', $staleBefore)
                ->where('attempts', '<', $maximum)
                ->update([
                    'status' => 'retry_pending',
                    'next_attempt_at' => now(),
                    'last_error' => 'Sinkronisasi sebelumnya terhenti dan dijadwalkan ulang.',
                    'updated_at' => now(),
                ]);

            $event = KmHrisOutboundEvent::query()
                ->whereIn('status', ['pending', 'retry_pending'])
                ->where(static fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
                ->orderBy('id')->lockForUpdate()->first();
            if ($event === null) {
                return null;
            }
            $event->forceFill(['status' => 'processing', 'attempts' => (int) $event->attempts + 1])->save();
            return $event;
        }, 3);
    }
}
