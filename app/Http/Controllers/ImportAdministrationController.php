<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ImportAdministration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use ZipArchive;

class ImportAdministrationController extends Controller
{
    public function showcreate()
    {
        $admin = ImportAdministration::all();

        return view('import_adm.create', compact('admin'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier' => 'required',
            'no_inv' => 'required',
        ]);

        ImportAdministration::create([
            'supplier' => $request->supplier,
            'no_inv' => $request->no_inv,
            'status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan.'
        ]);
    }

    public function showformadm($id)
    {
        $admin = ImportAdministration::FindorFail($id);

        return view('import_adm.showform', compact('admin'));
    }

    public function uploadFiles(Request $request, $adminId)
    {
        $admin = ImportAdministration::findOrFail($adminId);
        $status = $request->input('status');
        $today = now()->format('Ymd');
        $noDocument = $admin->no_document;

        // Define file inputs, database columns, prefixes, and storage folders per status
        $fields = [
            1 => [
                'pl_file' => ['column' => 'pl', 'prefix' => 'PL_', 'folder' => 'pl'],
                'inv_file' => ['column' => 'inv', 'prefix' => 'INV_', 'folder' => 'inv'],
            ],
            2 => [
                'no_vo_file' => ['column' => 'no_vo', 'prefix' => 'NO_VO_', 'folder' => 'no_vo'],
                'ls_file' => ['column' => 'ls', 'prefix' => 'LS_', 'folder' => 'ls'],
            ],
            3 => [
                'bl_file' => ['column' => 'bl', 'prefix' => 'BL_', 'folder' => 'bl'],
                'inv_final_file' => ['column' => 'inv_final', 'prefix' => 'INV_FINAL_', 'folder' => 'inv_final'],
                'pl_final_file' => ['column' => 'pl_final', 'prefix' => 'PL_FINAL_', 'folder' => 'pl_final'],
                'form_e_file' => ['column' => 'form_e', 'prefix' => 'FORM_E_', 'folder' => 'form_e'],
            ],
            4 => [
                'asuransi_file' => ['column' => 'asuransi', 'prefix' => 'ASURANSI_', 'folder' => 'asuransi'],
            ],
            5 => [
                'no_aju_file' => ['column' => 'no_aju', 'prefix' => 'NO_AJU_', 'folder' => 'no_aju'],
                'pib_final_file' => ['column' => 'pib_final', 'prefix' => 'PIB_FINAL_', 'folder' => 'pib_final'],
            ],
            6 => [
                'e_bill_file' => ['column' => 'e_bill', 'prefix' => 'E_BILL_', 'folder' => 'e_bill'],
            ],
        ];

        if (!isset($fields[$status])) {
            return redirect()->back()->with('error', 'Invalid status for upload.');
        }

        foreach ($fields[$status] as $inputName => $field) {
            if ($request->hasFile($inputName)) {
                // Get existing files or initialize empty array
                $existingFiles = json_decode($admin->{$field['column']}, true) ?? [];
                $newFiles = [];

                // Loop through the uploaded files and store them
                foreach ($request->file($inputName) as $fileIndex => $file) {
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $newName = $field['prefix'] . $noDocument . '_' . Str::slug($originalName, '_') . '_' . $today . '.' . $extension;

                    // Delete the old file if it exists
                    if (in_array($newName, $existingFiles)) {
                        $oldFilePath = public_path('assets/adm_import/' . $field['folder'] . '/' . $newName);
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);  // Delete the old file
                        }
                    }

                    // Store the new file
                    $file->move(public_path('assets/adm_import/' . $field['folder']), $newName);

                    // Add the new file name to the list
                    $newFiles[] = $newName;
                }

                // Update the column in the database with new files
                $admin->{$field['column']} = json_encode(array_merge($existingFiles, $newFiles));
            }
        }

        // Save the changes to the admin record
        $admin->save();

        return redirect()->back()->with('success', 'Files uploaded successfully.');
    }




    public function downloadFiles($status, $adminId)
    {
        // Ambil data admin berdasarkan ID
        $admin = ImportAdministration::findOrFail($adminId);
    
        // Tentukan kolom yang akan diunduh berdasarkan status
        $downloadColumns = [
            1 => ['pl', 'inv'],
            2 => ['no_vo', 'ls'],
            3 => ['bl', 'inv_final', 'pl_final', 'form_e'],
            4 => ['asuransi'],
            5 => ['no_aju', 'pib_final'],
            6 => ['e_bill'],
            7 => ['e_bill'], // Menganggap Finish menggunakan file E-Bill
        ];
    
        // Cek apakah status valid
        if (!isset($downloadColumns[$status])) {
            return response()->json(['message' => 'Invalid status.'], 404);
        }
    
        // Array untuk menyimpan file paths
        $filePaths = [];
        // Array untuk menyimpan file yang tidak ditemukan
        $missingFiles = [];
    
        // Proses setiap kolom yang sesuai dengan status
        foreach ($downloadColumns[$status] as $column) {
            $files = json_decode($admin->{$column}, true) ?? [];
            foreach ($files as $file) {
                // Tentukan path file berdasarkan struktur folder
                $filePath = public_path('assets/adm_import/' . $column . '/' . $file);  // Menggunakan public_path
    
                // Cek apakah file ada di folder publik
                if (!file_exists($filePath)) {
                    // Jika file tidak ada, tambahkan ke array missingFiles
                    $missingFiles[] = $file;
                } else {
                    // Jika file ada, masukkan ke array filePaths untuk diunduh
                    $filePaths[] = $filePath;
                }
            }
        }
    
        // Jika ada file yang hilang, kembalikan pesan dengan daftar file yang hilang
        if (!empty($missingFiles)) {
            return redirect()->back()->with('error', 'Some files were not found: ' . implode(', ', $missingFiles));
        }
    
        // Jika tidak ada file yang ditemukan
        if (empty($filePaths)) {
            return redirect()->back()->with('error', 'No files found for this status.');
        }
    
        // Mengunduh setiap file satu per satu dan menyimpannya di array downloadUrls
        foreach ($filePaths as $filePath) {
            // Tentukan nama file download
            $fileName = 'adm_imp_' . pathinfo($filePath, PATHINFO_FILENAME) . '_' . now()->format('Ymd_His') . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
    
            // Periksa apakah file ada, jika ada, kirimkan untuk diunduh
            if (file_exists($filePath)) {
                // Mengunduh file
                return response()->download($filePath, $fileName);
            }
        }
    
        // Kembalikan pesan error jika file tidak ditemukan
        return redirect()->back()->with('error', 'No files found for download.');
    }
    



    public function approve($adminId)
    {
        $admin = ImportAdministration::findOrFail($adminId);
        if ($admin->status < 7) {
            $admin->status += 1;
            $admin->save();
        }
        return redirect()->back()->with('success', 'Status approved.');
    }

    public function reject($adminId)
    {
        $admin = ImportAdministration::findOrFail($adminId);
        if ($admin->status == 1) {
            $admin->delete();
            return redirect()->route('import.index')->with('success', 'Record deleted.'); // Adjust route as needed
        } else {
            $admin->status -= 1;
            $admin->save();
            return redirect()->back()->with('success', 'Status rejected.');
        }
    }

}
