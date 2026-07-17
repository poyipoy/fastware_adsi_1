<?php

namespace App\Imports;

use App\Models\User;
use App\Models\WorkingExperience;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class WorkingExperienceImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /** Baris yang berhasil diimport */
    public int $successCount = 0;

    /** Baris yang gagal validasi, berupa ['row' => N, 'email' => ..., 'errors' => [...]] */
    public array $failures = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2: header row = 1, data mulai baris 2
            $data = $row->toArray();

            // Normalisasi key: lowercase + trim
            $data = array_change_key_case(array_map('trim', array_map('strval', $data)), CASE_LOWER);

            // Ambil nama karyawan dari kolom 'nama_karyawan'
            $name = $data['nama_karyawan'] ?? null;

            // Lewati baris instruksi dari template Excel
            if ($name === 'Instruksi:' || stripos($name, 'Instruksi') !== false) {
                continue;
            }

            if (!$name) {
                $this->failures[] = [
                    'row'    => $rowNumber,
                    'name'   => '(kosong)',
                    'errors' => ['nama_karyawan' => 'Kolom nama_karyawan kosong.'],
                ];
                continue;
            }

            // Cari user berdasarkan nama (exact match)
            $users = User::where('name', $name)->get();

            if ($users->count() === 0) {
                $this->failures[] = [
                    'row'    => $rowNumber,
                    'name'   => $name,
                    'errors' => ['nama_karyawan' => 'Karyawan dengan nama "' . $name . '" tidak ditemukan (pastikan penulisan sama persis dengan sistem).'],
                ];
                continue;
            }

            if ($users->count() > 1) {
                $this->failures[] = [
                    'row'    => $rowNumber,
                    'name'   => $name,
                    'errors' => ['nama_karyawan' => 'Ditemukan lebih dari 1 karyawan dengan nama "' . $name . '". Hubungi administrator.'],
                ];
                continue;
            }

            $user = $users->first();

            // Normalisasi year_end: kosong → null
            $yearEnd = isset($data['tahun_selesai']) && $data['tahun_selesai'] !== '' && $data['tahun_selesai'] !== '0'
                ? (int) $data['tahun_selesai']
                : null;

            $yearStart = isset($data['tahun_mulai']) && $data['tahun_mulai'] !== '' ? (int) $data['tahun_mulai'] : null;

            // Validasi
            $rules = [
                'year_start'   => 'required|integer|digits:4|min:1900|max:' . (date('Y') + 5),
                'year_end'     => 'nullable|integer|digits:4|min:1900|max:' . (date('Y') + 5) . '|gte:year_start',
                'job_position' => 'required|string|max:255',
                'section'      => 'nullable|string|max:255',
                'departemen'   => 'nullable|string|max:255',
                'keterangan'   => 'nullable|string|max:1000',
            ];

            $messages = [
                'year_start.required' => 'Kolom tahun_mulai wajib diisi.',
                'year_start.integer'  => 'Kolom tahun_mulai harus berupa angka.',
                'year_start.digits'   => 'Kolom tahun_mulai harus 4 digit (misal: 2020).',
                'year_start.min'      => 'Kolom tahun_mulai minimal tahun 1900.',
                'year_start.max'      => 'Kolom tahun_mulai maksimal tahun ' . (date('Y') + 5) . '.',
                'year_end.integer'    => 'Kolom tahun_selesai harus berupa angka.',
                'year_end.digits'     => 'Kolom tahun_selesai harus 4 digit.',
                'year_end.min'        => 'Kolom tahun_selesai minimal tahun 1900.',
                'year_end.max'        => 'Kolom tahun_selesai maksimal tahun ' . (date('Y') + 5) . '.',
                'year_end.gte'        => 'Kolom tahun_selesai tidak boleh lebih kecil dari tahun_mulai.',
                'job_position.required'=> 'Kolom jabatan wajib diisi.',
                'job_position.max'     => 'Kolom jabatan maksimal 255 karakter.',
            ];

            $payload = [
                'year_start'   => $yearStart,
                'year_end'     => $yearEnd,
                'job_position' => $data['jabatan'] ?? null,
                'section'      => $data['section'] ?? null,
                'departemen'   => $data['departemen'] ?? null,
                'keterangan'   => $data['keterangan'] ?? null,
            ];

            $validator = Validator::make($payload, $rules, $messages);

            if ($validator->fails()) {
                $this->failures[] = [
                    'row'    => $rowNumber,
                    'name'   => $name,
                    'errors' => $validator->errors()->toArray(),
                ];
                continue;
            }

            WorkingExperience::create(array_merge($payload, ['user_id' => $user->id]));
            $this->successCount++;
        }
    }
}
