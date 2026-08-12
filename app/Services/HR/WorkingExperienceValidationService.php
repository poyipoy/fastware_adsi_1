<?php

namespace App\Services\HR;

use App\Models\MstJobPosition;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WorkingExperienceValidationService
{
    public function prepare(array $input, ?User $knownUser = null): array
    {
        $validator = Validator::make($input, [
            'npk' => ['nullable'],
            'nama_karyawan' => [$knownUser ? 'nullable' : 'required_without:user_id', 'string', 'max:255'],
            'user_id' => [$knownUser ? 'nullable' : 'sometimes', 'integer', 'exists:users,id'],
            'year_start' => ['required', 'integer', 'digits:4', 'min:1900', 'max:'.(date('Y') + 5)],
            'year_end' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:'.(date('Y') + 5), 'gte:year_start'],
            'job_position' => ['required', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'departemen' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = $knownUser ?? $this->resolveEmployee($input);
        $position = $this->resolveMasterTuple(
            $input['job_position'] ?? null,
            $input['section'] ?? null,
            $input['departemen'] ?? null,
        );

        return [
            'user_id' => $user->id,
            'year_start' => (int) $input['year_start'],
            'year_end' => $this->nullableYear($input['year_end'] ?? null),
            'job_position' => $position->position_name,
            'section' => $position->section?->name,
            'departemen' => $position->department?->name,
            'keterangan' => $this->nullableText($input['keterangan'] ?? null),
        ];
    }

    public function resolveEmployee(array $input): User
    {
        if (! empty($input['user_id'])) {
            return User::findOrFail((int) $input['user_id']);
        }

        $name = $this->normalize($input['nama_karyawan'] ?? null);
        if ($name === null) {
            $this->fail('nama_karyawan', 'Nama karyawan wajib diisi sebagai verifikasi identitas.');
        }

        $npk = trim((string) ($input['npk'] ?? ''));
        if ($npk !== '' && $npk !== '0') {
            $matches = User::query()->where('npk', $npk)->get();
            if ($matches->isEmpty()) {
                $this->fail('npk', "NPK {$npk} tidak ditemukan.");
            }

            $nameMatches = $matches->filter(fn (User $user) => $this->normalize($user->name) === $name);
            if ($nameMatches->count() !== 1) {
                $message = $matches->count() > 1
                    ? "NPK {$npk} digunakan oleh lebih dari satu karyawan. Pastikan NPK dan nama sesuai dengan data karyawan."
                    : "Nama karyawan tidak sesuai dengan NPK {$npk}. Nama yang terdaftar: {$matches->first()->name}.";
                $this->fail('nama_karyawan', $message);
            }

            return $nameMatches->first();
        }

        $matches = User::query()->get(['id', 'npk', 'name'])
            ->filter(fn (User $user) => $this->normalize($user->name) === $name)
            ->values();

        if ($matches->isEmpty()) {
            $this->fail('nama_karyawan', 'Nama karyawan tidak ditemukan. Isi NPK untuk pencarian yang lebih presisi.');
        }

        if ($matches->count() !== 1) {
            $this->fail('nama_karyawan', 'Ada lebih dari satu karyawan dengan nama tersebut. Isi NPK agar karyawan dapat dikenali dengan tepat.');
        }

        return $matches->first();
    }

    public function resolveMasterTuple(mixed $job, mixed $section, mixed $department): MstJobPosition
    {
        $jobKey = $this->normalize($job);
        $sectionKey = $this->normalize($section);
        $departmentKey = $this->normalize($department);

        $allMatches = MstJobPosition::query()
            ->with(['section:id,name,department_id', 'department:id,name'])
            ->get()
            ->filter(fn (MstJobPosition $position) => $this->normalize($position->position_name) === $jobKey)
            ->values();

        if ($allMatches->isEmpty()) {
            $this->fail('job_position', 'Jabatan tidak ditemukan. Periksa kembali nama jabatan sesuai daftar yang tersedia.');
        }

        $activeMatches = $allMatches->where('is_active', true)->values();
        if ($activeMatches->isEmpty()) {
            $this->fail('job_position', "Jabatan {$allMatches->first()->position_name} sudah tidak aktif. Pilih jabatan yang masih aktif.");
        }

        $exact = $activeMatches->filter(function (MstJobPosition $position) use ($sectionKey, $departmentKey) {
            return $this->normalize($position->section?->name) === $sectionKey
                && $this->normalize($position->department?->name) === $departmentKey;
        })->values();

        if ($exact->count() === 1) {
            return $exact->first();
        }

        $suggestedValues = $activeMatches->map(fn (MstJobPosition $position) => sprintf(
            'Jabatan: %s, Section: %s, Departemen: %s',
            $position->position_name,
            $position->section?->name ?? '(kosong)',
            $position->department?->name ?? '(kosong)',
        ))->unique()->implode('; ');

        $this->fail(
            'job_position',
            'Jabatan, section, atau departemen belum sesuai. Gunakan data berikut: '.$suggestedValues.'.',
        );
    }

    public function normalize(mixed $value): ?string
    {
        $value = preg_replace('/[\p{Z}\s]+/u', ' ', trim((string) ($value ?? '')));

        return $value === '' ? null : mb_strtolower($value, 'UTF-8');
    }

    private function nullableText(mixed $value): ?string
    {
        $value = preg_replace('/[\p{Z}\s]+/u', ' ', trim((string) ($value ?? '')));

        return $value === '' ? null : $value;
    }

    private function nullableYear(mixed $value): ?int
    {
        return $value === null || $value === '' || (string) $value === '0' ? null : (int) $value;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
