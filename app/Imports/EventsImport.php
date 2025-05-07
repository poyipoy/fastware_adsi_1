<?php

namespace App\Imports;

use App\Models\Event;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Validation\Rule;

class EventsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Define validation rules
        $rules = [
            'nama_mesin' => [
                'required',
                Rule::unique('events')->where(function ($query) use ($row) {
                    return $query->where('type', $row['type'])
                        ->where('nomor_baru', $row['nomor_baru']);
                }),
            ],
            'type' => 'required',
            'nomor_baru' => 'required',
        ];

        // Run validation
        $validator = Validator::make($row, $rules);

        // If validation fails, show a SweetAlert notification and return null
        if ($validator->fails()) {
            Alert::error('Upload failed', 'Validation error: ' . $validator->errors()->first());
            return null;
        }

        // Get the start time as the current timestamp
        $startTime = now();

        // Add one minute to the start time to get the end time
        $endTime = Carbon::parse($startTime)->addMinute();

        // If validation passes, return new event
        return new Event([
            'nama_mesin' => $row['nama_mesin'],
            'type' => $row['type'],
            'nomor_baru' => $row['nomor_baru'],
            'start' => $startTime,
            'end' => $endTime,
            'issue' => $row['issue'],
            'pic' => $row['pic'],
            'status' => $row['status'],
        ]);
    }
}
