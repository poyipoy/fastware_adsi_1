<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ImportAdministration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use ZipArchive;
use Illuminate\Support\Facades\Log;

class ImportAdministrationController extends Controller
{
    public function showcreate()
    {
        $admin = ImportAdministration::all();

        return view('import_adm.create', compact('admin'));
    }


    public function store(Request $request)
    {
        $user = auth()->id();
    
        // Validasi input
        $request->validate([
            'supplier' => 'required',
            'no_inv' => 'required',
        ]);
    
        // Mengambil tahun sekarang
        $year = now()->year;
    
        // Menentukan nomor dokumen yang akan datang berdasarkan tahun ini
        $lastDocument = ImportAdministration::whereYear('created_at', $year)
                                            ->orderBy('created_at', 'desc')
                                            ->first();
    
        $nextNumber = $lastDocument ? intval(substr($lastDocument->no_document, -4)) + 1 : 1;
    
        // Membuat nomor dokumen dengan format DOC/tahun/(nomor)
        $document = 'DOC/' . $year . '/' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    
        // Menyimpan data dokumen baru
        ImportAdministration::create([
            'no_document' => $document,
            'supplier' => $request->supplier,
            'no_inv' => $request->no_inv,
            'purchase_id' => $user,
            'status' => 1,
        ]);
    
        // Redirect kembali ke halaman sebelumnya dengan pesan sukses
        return redirect()->back()->with('success', 'Data berhasil disimpan.');
    }
    

    public function showformadm($id)
    {
        // Ambil admin berdasarkan ID
        $admin = ImportAdministration::findOrFail($id);

        // Hitung jumlah total data admin untuk maxAdminId
        $maxAdminId = ImportAdministration::max('id');

        // Memuat relasi purchase dan admin
        $admin->load('purchase', 'admin');

        return view('import_adm.showform', compact('admin', 'maxAdminId'));
    }

    
    
    public function updateAdmin(Request $request, $adminId)
    {
        $admin = ImportAdministration::findOrFail($adminId);

        // Validate input
        $validated = $request->validate([
            'supplier' => 'required|string|max:255',
            'no_inv' => 'required|string|max:255',
        ]);

        // Update the admin record with new data
        $admin->supplier = $request->input('supplier');
        $admin->no_inv = $request->input('no_inv');

        // Save changes
        $admin->save();

        return redirect()->route('dokumenadministration', $adminId)->with('success', 'Supplier and Invoice updated successfully.');
    }



    public function uploadFiles(Request $request, $adminId)
{
    $admin = ImportAdministration::findOrFail($adminId);
    $status = $request->input('status');
    $today = now()->format('Ymd');
    $noDocumentPrefix = 1;  // Start the document number from 1

    // Define file inputs, database columns, prefixes, and storage folders per status
    $fields = [
        1 => [
            'pl_file' => ['column' => 'pl', 'prefix' => 'PL_', 'folder' => 'pl'],
            'inv_file' => ['column' => 'inv', 'prefix' => 'INV_', 'folder' => 'inv'],
        ],
        2 => [
            'no_vo_file' => ['column' => 'novo_file', 'prefix' => 'VO_', 'folder' => 'no_vo'],
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
            'pib_final_file' => ['column' => 'pib_final', 'prefix' => 'PIB_FINAL_', 'folder' => 'pib_final'],
        ],
        6 => [
            'e_bill_file' => ['column' => 'e_bill', 'prefix' => 'E_BILL_', 'folder' => 'e_bill'],
        ],
    ];

    if (!isset($fields[$status])) {
        return redirect()->back()->with('error', 'Invalid status for upload.');
    }

    // Handle file and text input for each status
    foreach ($fields[$status] as $inputName => $field) {
        if ($request->hasFile($inputName)) {
            // Get existing files or initialize empty array
            $existingFiles = json_decode($admin->{$field['column']}, true) ?? [];
            $newFiles = [];

            // Loop through the uploaded files and store them
            foreach ($request->file($inputName) as $fileIndex => $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();

                // Generate the initial file name
                $newName = $field['prefix'] . $noDocumentPrefix . '_' . Str::slug($originalName, '_') . '_' . $today . '.' . $extension;

                // Check if the file already exists, and if so, append a number to the filename
                $counter = 1;
                $originalNewName = $newName;
                while (in_array($newName, $existingFiles)) {
                    // If the file exists, add a number to the filename
                    $newName = pathinfo($originalNewName, PATHINFO_FILENAME) . "({$counter})" . '.' . $extension;
                    $counter++;
                }

                // Store the new file
                $file->move(public_path('assets/adm_import/' . $field['folder']), $newName);

                // Add the new file name to the list
                $newFiles[] = $newName;

                // Increase the document number for the next file
                $noDocumentPrefix++; // Increment the number for the next file
            }

            // Merge old files and new files into a combined list (to prevent overwriting)
            $allFiles = array_merge($existingFiles, $newFiles);
            $admin->{$field['column']} = json_encode($allFiles); // Replace the old data with the new file
        }
    }

    // Handle text input (for 'no_aju_text' and 'no_vo_text')
    if ($status == 5 && $request->has('no_aju_text')) {
        // Save the no_aju text as varchar
        $admin->no_aju = $request->input('no_aju_text');
    }

    if ($status == 2 && $request->has('no_vo_text')) {
        // Save the no_vo text as varchar
        $admin->no_vo = $request->input('no_vo_text');
        $admin->vr = $request->input('no_vr_text');
    }

    // If status is 2, save the auth id of the logged-in user
    if ($status == 2) {
        $admin->admin_id = auth()->id(); // Save the logged-in user's ID in the admin record
    }

    // Add timestamp to track when the files were uploaded based on status
    if (in_array($status, [1, 2, 3])) {
        // For status 1, 2, and 3, update `purchase_updated_at`
        $admin->purchase_updated_at = now();
    } elseif (in_array($status, [4, 5, 6])) {
        // For status 4, 5, and 6, update `admin_updated_at`
        $admin->admin_updated_at = now();
    }

    // Save the changes to the admin record
    $admin->save();

    return redirect()->back()->with('success', 'Files uploaded successfully.');
}

    

public function deleteFile(Request $request)
{
    try {
        $fileName = $request->input('fileName');
        $statusCode = $request->input('statusCode');

        // Cek apakah data fileName dan statusCode ada
        if (!$fileName || !$statusCode) {
            return response()->json(['success' => false, 'message' => 'File atau status tidak ditemukan.']);
        }

        // Tentukan folder dan kolom berdasarkan prefix nama file
        $folder = $this->getFolderByFileName($fileName); // Menentukan folder berdasarkan nama file
        $column = $this->getColumnByFileName($fileName); // Menentukan kolom berdasarkan nama file

        if (!$folder || !$column) {
            return response()->json(['success' => false, 'message' => 'Folder atau kolom tidak ditemukan untuk file ini.']);
        }

        // Tentukan path file berdasarkan folder yang ditemukan
        $filePath = public_path("assets/adm_import/{$folder}/{$fileName}");

        // Cek apakah file ada di server
        if (file_exists($filePath)) {
            // Hapus file dari server
            if (unlink($filePath)) {
                Log::info("File {$fileName} berhasil dihapus dari server.");
            } else {
                Log::error("Gagal menghapus file {$fileName} dari server.");
                return response()->json(['success' => false, 'message' => 'Gagal menghapus file dari server.']);
            }

            // Ambil data file dari database
            $admin = ImportAdministration::findOrFail($request->user()->id); // Dapatkan data admin berdasarkan user_id
            $files = json_decode($admin->{$column}, true) ?? [];  // Pastikan $files selalu berupa array

            // Menghapus file dari array jika ditemukan
            $files = array_filter($files, function ($file) use ($fileName) {
                return $file !== $fileName;  // Menghapus file dari array
            });

            // Reindex array setelah penghapusan file
            $files = array_values($files);

            // Debugging: Log sebelum dan sesudah perubahan
            Log::info('Files sebelum dihapus:', ['files' => $admin->{$column}]);
            Log::info('Files setelah dihapus:', ['files' => $files]);

            // Pastikan array tidak kosong dan simpan kembali file yang telah dihapus di database
            if (empty($files)) {
                // Jika array kosong, simpan nilai null atau kosong ke kolom database
                $admin->{$column} = null;
            } else {
                // Jika masih ada file lainnya, simpan array yang telah diupdate
                $admin->{$column} = json_encode($files);
            }

            $admin->save();

            return response()->json(['success' => true, 'message' => 'File berhasil dihapus.']);
        }

        return response()->json(['success' => false, 'message' => 'File tidak ditemukan di server.']);
    } catch (\Exception $e) {
        Log::error('Error deleting file: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghapus file.']);
    }
}

private function getFolderByFileName($fileName)
{
    // Tentukan folder berdasarkan prefix nama file
    $fields = [
        1 => [
            'pl_file' => ['folder' => 'pl', 'prefix' => 'PL_'],
            'inv_file' => ['folder' => 'inv', 'prefix' => 'INV_'],
        ],
        2 => [
            'no_vo_file' => ['folder' => 'no_vo', 'prefix' => 'VO_'],
            'ls_file' => ['folder' => 'ls', 'prefix' => 'LS_'],
        ],
        3 => [
            'bl_file' => ['folder' => 'bl', 'prefix' => 'BL_'],
            'inv_final_file' => ['folder' => 'inv_final', 'prefix' => 'INV_FINAL_'],
            'pl_final_file' => ['folder' => 'pl_final', 'prefix' => 'PL_FINAL_'],
            'form_e_file' => ['folder' => 'form_e', 'prefix' => 'FORM_E_'],
        ],
        4 => [
            'asuransi_file' => ['folder' => 'asuransi', 'prefix' => 'ASURANSI_'],
        ],
        5 => [
            'pib_final_file' => ['folder' => 'pib_final', 'prefix' => 'PIB_FINAL_'],
        ],
        6 => [
            'e_bill_file' => ['folder' => 'e_bill', 'prefix' => 'E_BILL_'],
        ],
    ];

    foreach ($fields as $statusCode => $files) {
        foreach ($files as $file => $attributes) {
            if (strpos($fileName, $attributes['prefix']) === 0) {
                return $attributes['folder']; // Kembalikan folder jika prefix ditemukan
            }
        }
    }

    return ''; // If no match found
}

private function getColumnByFileName($fileName)
{
    // Tentukan kolom berdasarkan prefix nama file
    $fields = [
        1 => [
            'pl_file' => ['column' => 'pl', 'prefix' => 'PL_'],
            'inv_file' => ['column' => 'inv', 'prefix' => 'INV_'],
        ],
        2 => [
            'no_vo_file' => ['column' => 'novo_file', 'prefix' => 'VO_'],
            'ls_file' => ['column' => 'ls', 'prefix' => 'LS_'],
        ],
        3 => [
            'bl_file' => ['column' => 'bl', 'prefix' => 'BL_'],
            'inv_final_file' => ['column' => 'inv_final', 'prefix' => 'INV_FINAL_'],
            'pl_final_file' => ['column' => 'pl_final', 'prefix' => 'PL_FINAL_'],
            'form_e_file' => ['column' => 'form_e', 'prefix' => 'FORM_E_'],
        ],
        4 => [
            'asuransi_file' => ['column' => 'asuransi', 'prefix' => 'ASURANSI_'],
        ],
        5 => [
            'pib_final_file' => ['column' => 'pib_final', 'prefix' => 'PIB_FINAL_'],
        ],
        6 => [
            'e_bill_file' => ['column' => 'e_bill', 'prefix' => 'E_BILL_'],
        ],
    ];

    foreach ($fields as $status => $files) {
        foreach ($files as $file => $attributes) {
            if (strpos($fileName, $attributes['prefix']) === 0) {
                return $attributes['column']; // Return the column based on file prefix
            }
        }
    }

    return ''; // If no match found
}





    public function downloadFiles($adminId)
{
    try {
        Log::info("Admin ID: $adminId");

        $admin = ImportAdministration::findOrFail($adminId);

        // Inisialisasi array untuk menyimpan file berdasarkan status
        $filesByStatus = [
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => [],
            6 => [],
            7 => [],
        ];

        $downloadConfig = [
            1 => ['pl' => 'pl', 'inv' => 'inv'],
            2 => ['novo_file' => 'no_vo', 'ls' => 'ls'],
            3 => ['bl' => 'bl', 'inv_final' => 'inv_final', 'pl_final' => 'pl_final', 'form_e' => 'form_e'],
            4 => ['asuransi' => 'asuransi'],
            5 => ['pib_final' => 'pib_final'],
            6 => ['e_bill' => 'e_bill'],
            7 => ['e_bill' => 'e_bill'],
        ];

        foreach ($downloadConfig as $status => $columns) {
            foreach ($columns as $column => $folder) {
                $files = json_decode($admin->{$column}, true) ?? [];
                
                foreach ($files as $filename) {
                    $filePath = public_path("assets/adm_import/{$folder}/{$filename}");
                    $fileUrl = url("assets/adm_import/{$folder}/{$filename}");

                    if (file_exists($filePath)) {
                        $filesByStatus[$status][] = [
                            'name' => $filename,
                            'url' => $fileUrl
                        ];
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'filesByStatus' => $filesByStatus // Mengirimkan data file yang dibagi berdasarkan status
        ]);

    } catch (\Exception $e) {
        Log::error('Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 404);
    }
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
