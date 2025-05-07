<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\HeatTreatment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WOImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        // Skip the header row if needed
        $rows = $collection->skip(2);

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                try {
                    // Ensure the row has at least 28 columns
                    if (count($row) >= 28) {
                        // Trim all values and check for emptiness
                        $row = $row->map(function ($value) {
                            return trim($value);
                        });

                        // Skip rows where mandatory fields are empty (e.g., 'no_wo' atau 'no_so')
                        if (empty($row[0]) || (!isset($row[1]) && $row[1] !== '0')) {
                            Log::warning("Skipping row with empty mandatory fields: " . json_encode($row));
                            continue;
                        }

                        $existingRecord = null;

                        // Check if no_so is not 0, then apply the filter
                        if ($row[1] !== '0') {
                            $existingRecord = HeatTreatment::where('no_wo', $row[0])
                                ->where('no_so', $row[1])
                                ->first();
                        }

                        // Directly assign the date values as strings
                        $recordData = [
                            'tgl_wo' => $row[2] ?: null,
                            'area' => $row[3] ?: null,
                            'kode' => $row[4] ?: null,
                            'cust' => $row[5] ?: null,
                            'proses' => $row[6] ?: null,
                            'pcs' => $row[7] ?: null,
                            'kg' => $row[8] ?: null,
                            'batch_heating' => $row[9] ?: null,
                            'mesin_heating' => $row[10] ?: null,
                            'tgl_heating' => $row[11] ?: null,
                            'batch_temper1' => $row[12] ?: null,
                            'mesin_temper1' => $row[13] ?: null,
                            'tgl_temper1' => $row[14] ?: null,
                            'batch_temper2' => $row[15] ?: null,
                            'mesin_temper2' => $row[16] ?: null,
                            'tgl_temper2' => $row[17] ?: null,
                            'batch_temper3' => $row[18] ?: null,
                            'mesin_temper3' => $row[19] ?: null,
                            'tgl_temper3' => $row[20] ?: null,
                            'status_wo' => $row[21] ?: null,
                            'no_do' => $row[22] ?: null,
                            'status_do' => $row[23] ?: null,
                            'tgl_st' => $row[24] ?: null,
                            'supir' => $row[25] ?: null,
                            'penerima' => $row[26] ?: null,
                            'tgl_terima' => $row[27] ?: null,
                        ];

                        if ($existingRecord) {
                            // Log update attempt
                            Log::info("Updating record: no_wo={$row[0]}, no_so={$row[1]}");
                            // Update the existing record
                            $existingRecord->update($recordData);
                        } else {
                            // Log creation attempt
                            Log::info("Creating new record: no_wo={$row[0]}, no_so={$row[1]}");
                            // Create a new record
                            HeatTreatment::create(array_merge([
                                'no_wo' => $row[0],
                                'no_so' => $row[1]
                            ], $recordData));
                        }
                    } else {
                        // Log row data issue
                        Log::warning("Row data does not have enough elements: " . json_encode($row));
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing row: " . json_encode($row) . " - " . $e->getMessage());
                }
            }
        });
    }
}
