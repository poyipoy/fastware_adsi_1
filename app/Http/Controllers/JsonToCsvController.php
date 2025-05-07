<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class JsonToCsvController extends Controller
{
    public function showUploadForm()
    {
        return view('upload'); // Pastikan 'upload' adalah nama view yang benar
    }

    // public function convert(Request $request)
    // {
    //     $request->validate([
    //         'json_text' => 'required|string',
    //     ]);

    //     // Mengambil isi dari textarea
    //     $jsonData = $request->input('json_text');
    //     $jsonArray = json_decode($jsonData, true);

    //     // Memastikan data JSON valid
    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         return response()->json(['error' => 'Invalid JSON'], 400);
    //     }

    //     // Memastikan Payload dan Data ada
    //     if (isset($jsonArray['Payload']['Data']) && is_array($jsonArray['Payload']['Data'])) {
    //         $records = $jsonArray['Payload']['Data'];
    //     } else {
    //         return response()->json(['error' => 'No data available'], 400);
    //     }

    //     // Membuat file CSV
    //     $csvFileName = 'output.csv';
    //     $csvFile = fopen('php://output', 'w');

    //     // Menulis header CSV berdasarkan kunci dari elemen pertama
    //     if (!empty($records)) {
    //         fputcsv($csvFile, array_keys($records[0]));
    //     }

    //     // Menulis setiap record ke dalam CSV
    //     foreach ($records as $record) {
    //         fputcsv($csvFile, $record);
    //     }

    //     fclose($csvFile);

    //     // Menghasilkan file CSV untuk diunduh
    //     return response()->stream(
    //         function () use ($csvFileName) {
    //             $handle = fopen('php://output', 'r');
    //             fpassthru($handle);
    //             fclose($handle);
    //         },
    //         200,
    //         [
    //             'Content-Type' => 'text/csv',
    //             'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
    //         ]
    //     );
    // }

    public function convert(Request $request)
    {
        $request->validate([
            'json_text' => 'required|string',
        ]);

        // Mengambil isi dari textarea
        $jsonData = $request->input('json_text');

        if (empty($jsonData)) {
            return response()->json(['error' => 'No data received'], 400);
        }

        $jsonArray = json_decode($jsonData, true);

        // Memastikan data JSON valid
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON: ' . json_last_error_msg()], 400);
        }

        // Memastikan Payload dan Data ada
        if (!isset($jsonArray['Payload']['Data']) || !is_array($jsonArray['Payload']['Data'])) {
            return response()->json(['error' => 'No data available'], 400);
        }

        $records = $jsonArray['Payload']['Data'];

        // Menghasilkan file CSV untuk diunduh
        return response()->stream(
            function () use ($records) {
                $handle = fopen('php://output', 'w');

                // Mendefinisikan kolom yang ingin diambil
                $desiredColumns = ['BuyerTaxpayerName', 'TaxInvoiceNumber', 'VAT', 'Reference', 'TaxInvoiceStatus', 'TaxInvoiceDate'];

                // Menulis header CSV berdasarkan kolom yang diinginkan
                fputcsv($handle, $desiredColumns);

                // Menulis setiap record ke dalam CSV sesuai kolom yang diinginkan
                foreach ($records as $record) {
                    // Mengambil hanya kolom yang diinginkan
                    $filteredRecord = [];
                    foreach ($desiredColumns as $column) {
                        // Tetapkan nilai kolom atau null jika tidak ada
                        $filteredRecord[] = $record[$column] ?? null;
                    }
                    fputcsv($handle, $filteredRecord);
                }

                fclose($handle);
            },
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="output.csv"',
            ]
        );
    }
}
