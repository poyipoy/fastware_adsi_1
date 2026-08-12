<?php

namespace App\Services\HR;

use App\Enums\TrainingStatus;
use App\Models\TcPeopleDevelopment;
use App\Models\User;
use Illuminate\Support\Collection;

class TrainingEvaluationService
{
    public function __construct(
        private readonly HRRoleAccessService $roleAccess,
        private readonly TrainingParticipantService $participantService,
    ) {
    }

    public function participants(TcPeopleDevelopment $training): Collection
    {
        $training->loadMissing([
            'participants:id,npk,name',
            'user:id,npk,name',
        ]);

        return $this->participantService
            ->readableParticipants($training)
            ->unique('id')
            ->values();
    }

    public function isCompleted(TcPeopleDevelopment $training): bool
    {
        if ($training->is_sharing_knowledge) {
            return filled($training->dievaluasi) && filled($training->tgl_pengajuan);
        }

        return filled($training->diketahui);
    }

    public function canEdit(User $actor, TcPeopleDevelopment $training): bool
    {
        if (! $training->is_sharing_knowledge) {
            return ! in_array((int) $actor->role_id, [14, 15], true);
        }

        return $this->roleAccess->hasFullAccess($actor)
            && $training->status_2 === TrainingStatus::DONE
            && $this->participants($training)->isNotEmpty();
    }

    public function assertCanEdit(User $actor, TcPeopleDevelopment $training): void
    {
        if (! $training->is_sharing_knowledge) {
            return;
        }

        abort_unless(
            $this->roleAccess->hasFullAccess($actor),
            403,
            'Hanya HR yang dapat mengisi evaluasi Sharing Knowledge.',
        );

        abort_unless(
            $training->status_2 === TrainingStatus::DONE,
            422,
            'Evaluasi Sharing Knowledge hanya dapat diisi setelah status kegiatan Done.',
        );

        abort_unless(
            $this->participants($training)->isNotEmpty(),
            422,
            'Evaluasi Sharing Knowledge memerlukan minimal satu participant.',
        );
    }

    public function canViewResult(User $actor, TcPeopleDevelopment $training): bool
    {
        if (! $training->is_sharing_knowledge) {
            return true;
        }

        if ($this->roleAccess->hasFullAccess($actor)) {
            return true;
        }

        return $this->isCompleted($training)
            && $this->participants($training)->contains(
                fn (User $participant): bool => (int) $participant->id === (int) $actor->id,
            );
    }

    public function assertCanViewResult(User $actor, TcPeopleDevelopment $training): void
    {
        abort_unless(
            $this->canViewResult($actor, $training),
            403,
            'Anda tidak memiliki akses ke hasil evaluasi ini.',
        );
    }

    public function payload(TcPeopleDevelopment $training): array
    {
        $training->loadMissing([
            'participants:id,npk,name',
            'user:id,npk,name',
            'section:id,name',
        ]);

        $participants = $this->participants($training);
        $primaryUser = $training->is_sharing_knowledge
            ? $participants->first()
            : $training->user;

        return [
            'id' => (int) $training->id,
            'activity_type' => $training->is_sharing_knowledge ? 'Sharing Knowledge' : 'Training',
            'is_sharing_knowledge' => (bool) $training->is_sharing_knowledge,
            'evaluation_completed' => $this->isCompleted($training),
            'status_2' => $training->status_2,
            'section' => $training->section?->name ?? '-',
            'user' => $primaryUser ? $this->identity($primaryUser) : null,
            'participants' => $participants
                ->map(fn (User $participant): array => $this->identity($participant))
                ->all(),
            'participant_count' => $participants->count(),
            'program_training_plan' => $training->program_training_plan ?: $training->program_training,
            'program_training' => $training->program_training,
            'kategori_competency' => $training->kategori_competency,
            'due_date_plan' => $training->due_date_plan,
            'lembaga' => $training->lembaga_plan ?: $training->lembaga,
            'relevansi' => $training->relevansi,
            'alasan_relevansi' => $training->alasan_relevansi,
            'rekomendasi' => $training->rekomendasi,
            'alasan_rekomendasi' => $training->alasan_rekomendasi,
            'kelengkapan_materi' => $training->kelengkapan_materi,
            'lokasi' => $training->lokasi,
            'metode_pengajaran' => $training->metode_pengajaran,
            'fasilitas' => $training->fasilitas,
            'lainnya_1' => $training->lainnya_1,
            'metode_evaluasi' => $training->is_sharing_knowledge
                ? 'Sharing Knowledge'
                : $training->metode_evaluasi,
            'minat' => $training->minat,
            'daya_serap' => $training->daya_serap,
            'penerapan' => $training->penerapan,
            'lainnya_2' => $training->lainnya_2,
            'efektif' => $training->efektif,
            'catatan_tambahan' => $training->catatan_tambahan,
            'diketahui' => $training->is_sharing_knowledge ? null : $training->diketahui,
            'dievaluasi' => $training->dievaluasi,
            'tgl_pengajuan' => $training->tgl_pengajuan,
        ];
    }

    private function identity(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'npk' => EmployeeIdentityFormatter::npk($user->npk),
            'name' => $user->name ?: '-',
            'label' => EmployeeIdentityFormatter::label($user, ' — '),
        ];
    }
}
