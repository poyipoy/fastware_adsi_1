<?php

namespace App\Http\Requests;

use App\Models\TcPeopleDevelopment;
use App\Services\HR\HRRoleAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $id = $this->route('id');
        if (! $id) {
            return true;
        }

        $training = TcPeopleDevelopment::query()
            ->select(['id', 'is_sharing_knowledge'])
            ->find($id);

        if (! $training || ! $training->is_sharing_knowledge) {
            return true;
        }

        return app(HRRoleAccessService::class)->hasFullAccess($this->user());
    }

    public function rules(): array
    {
        return [
            'relevansi' => ['required', Rule::in(['Ya', 'Tidak'])],
            'alasan_relevansi' => ['nullable', 'string', 'max:255'],
            'rekomendasi' => ['required', Rule::in(['Lanjutkan', 'Dihentikan'])],
            'alasan_rekomendasi' => ['nullable', 'string', 'max:255'],
            'kelengkapan_materi' => ['required', Rule::in(['Lengkap', 'Cukup Lengkap', 'Tidak Lengkap'])],
            'lokasi' => ['required', Rule::in(['Dekat', 'Sedang', 'Jauh'])],
            'metode_pengajaran' => ['required', Rule::in(['Mudah Dimengerti', 'Cukup Dimengerti', 'Sulit Dimengerti'])],
            'fasilitas' => ['required', Rule::in(['Lengkap', 'Cukup Lengkap', 'Tidak Lengkap'])],
            'lainnya_1' => ['nullable', 'string', 'max:255'],
            'metode_evaluasi' => ['required', Rule::in(['Sharing Knowledge', 'Penerapan', 'Interview'])],
            'minat' => ['required', Rule::in(['Tinggi', 'Sedang', 'Rendah'])],
            'daya_serap' => ['required', Rule::in(['Menguasai Materi', 'Paham Materi Penting', 'Tidak Paham'])],
            'penerapan' => ['required', Rule::in(['Cepat', 'Cukup', 'Lambat'])],
            'lainnya_2' => ['nullable', 'string', 'max:255'],
            'efektif' => ['required', Rule::in(['Efektif', 'Tidak Efektif'])],
            'catatan_tambahan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'relevansi' => 'relevansi materi',
            'rekomendasi' => 'rekomendasi',
            'kelengkapan_materi' => 'kelengkapan materi',
            'lokasi' => 'lokasi penyelenggaraan',
            'metode_pengajaran' => 'metode pengajaran',
            'fasilitas' => 'fasilitas',
            'metode_evaluasi' => 'metode evaluasi',
            'minat' => 'minat peserta',
            'daya_serap' => 'daya serap peserta',
            'penerapan' => 'penerapan dalam tugas',
            'efektif' => 'efektivitas',
        ];
    }
}
