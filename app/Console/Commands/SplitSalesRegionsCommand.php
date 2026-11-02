<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\HR\SalesRegionSplitService;
use Illuminate\Console\Command;
use Throwable;

class SplitSalesRegionsCommand extends Command
{
    protected $signature = 'hr:split-sales-regions
        {--apply : Apply the reviewed cutover; default only previews}
        {--actor= : Existing authorized HR actor user ID}
        {--reviewed-hash= : Exact hash returned by the reviewed preview}';

    protected $description = 'Preview or apply the guarded one-time Sales Region 1-4 department split';

    public function handle(SalesRegionSplitService $service): int
    {
        try {
            if ($this->option('apply')) {
                $actorId = (string) $this->option('actor');
                $hash = (string) $this->option('reviewed-hash');
                if (! ctype_digit($actorId) || ! preg_match('/\A[a-f0-9]{64}\z/', $hash)) {
                    $this->error('--apply memerlukan --actor dan --reviewed-hash dari preview yang telah direview.');

                    return self::FAILURE;
                }
                $actor = User::find($actorId);
                if (! $actor) {
                    $this->error('Actor tidak ditemukan.');

                    return self::FAILURE;
                }
                $result = $service->apply($actor, $hash);
            } else {
                $result = $service->preview();
            }
            $this->line('Status: '.$result['status']);
            $this->line('Preview hash: '.$result['hash']);
            foreach ($result['summary'] ?? [] as $label => $value) {
                $this->line($label.': '.$value);
            }
            if (! empty($result['regions'])) {
                $this->table(['Department tujuan', 'Section ID', 'Posisi ID', 'Sumber pejabat', 'Jumlah pejabat'],
                    array_map(fn ($region) => [$region['destination'], $region['section_id'], implode(', ', $region['position_ids']),
                        $region['incumbent_source'], $region['incumbent_count']], $result['regions']));
                $this->table(['Approval ID', 'Posisi', 'Level', 'Kepala asal', 'Kepala tujuan'],
                    array_map(fn ($route) => [$route['id'], $route['position'], $route['level'], $route['from'], $route['to']], $result['approval_changes']));
            }
            if (isset($result['snapshot'])) {
                $this->line('Snapshot private: '.$result['snapshot']);
            }
            foreach ($result['blockers'] ?? [] as $blocker) {
                $this->error($blocker);
            }

            return $result['status'] === 'blocked' ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            // Do not print SQL, bindings, assignment snapshots, or employee identities.
            if ($exception instanceof \Illuminate\Database\QueryException) {
                $this->error('Operasi database gagal; tidak ada cutover yang dikomit. Periksa schema dan snapshot private.');
            } else {
                $this->error($exception->getMessage());
            }

            return self::FAILURE;
        }
    }
}
