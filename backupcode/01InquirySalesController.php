<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DetailInquiry;
use App\Models\InquirySales;
use App\Models\TypeMaterial;
use App\Models\TrxDboProgPurchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDF;

class InquirySalesController extends Controller
{
    public function createInquirySales()
    {
        $statuses = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $inquiries = InquirySales::with('customer')
            ->whereIn('status', $statuses)
            ->where('is_active', 1)
            ->orderByRaw('FIELD(status, 0, 1, 2, 3, 4, 5, 6, 7,8,9)')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('kode_inquiry');

        $customers = Customer::all();

        return view('inquiry.create', compact('inquiries', 'customers'));
    }

    public function storeInquirySales(Request $request)
    {
        $request->validate([
            'jenis_inquiry' => 'required',
            'id_customer' => 'required',
            'loc_imp' => 'required',
            // 'supplier' => 'required',

        ]);

        // Generate inquiry code
        $jenisInquiry = $request->jenis_inquiry;
        $currentMonth = Carbon::now()->format('m');
        $currentYear = Carbon::now()->format('Y');

        // Ambil nomor urut
        $lastKodeInquiry = InquirySales::where('jenis_inquiry', $jenisInquiry)
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->orderBy('kode_inquiry', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastKodeInquiry) {
            $lastKodeParts = explode('/', $lastKodeInquiry->kode_inquiry);
            $nextNumber = intval(end($lastKodeParts)) + 1;
        }

        $kodeInquiry = sprintf('%s/%02d/%04d/%03d', $jenisInquiry, $currentMonth, $currentYear, $nextNumber);

        // Simpan data inquiry baru
        $inquiry = new InquirySales();
        $inquiry->kode_inquiry = $kodeInquiry;
        $inquiry->jenis_inquiry = $jenisInquiry;
        $inquiry->id_customer = $request->id_customer;
        $inquiry->loc_imp = $request->loc_imp;
        // $inquiry->supplier = $request->supplier;
        // $inquiry->to_approve = 'Waiting';
        // $inquiry->to_validate = 'Waiting';
        $inquiry->status = 1;
        $inquiry->is_active = 1;
        $inquiry->create_by = Auth::user()->name;
        $inquiry->save();

        // Simpan progres awal sebagai "No updates yet"
        $progress = new TrxDboProgPurchase();
        $progress->inquiry_id = $inquiry->id;
        $progress->description = '---- No updates yet ----'; // Set default
        $progress->save();

        return redirect()->route('createinquiry')->with('success', 'Inquiry successfully saved.');
    }


    public function editInquiry($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::with('customer')->find($id); // Memuat customer bersamaan

        // Cek apakah inquiry ditemukan
        if (!$inquiry) {
            return response()->json(['error' => 'Inquiry not found'], 404);
        }

        // Ambil semua customers untuk populasi dropdown di form
        $customers = Customer::all();

        return response()->json([
            'id' => $inquiry->id,
            'kode_inquiry' => $inquiry->kode_inquiry,
            'jenis_inquiry' => $inquiry->jenis_inquiry,
            'id_customer' => $inquiry->id_customer,
            'customer_name' => $inquiry->customer->name_customer, // Pastikan relasi sudah ada
            'loc_imp' => $inquiry->loc_imp, // Pastikan relasi sudah ada
            // 'supplier' => $inquiry->supplier, // Ambil supplier dengan benar
            'customers' => $customers,
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'jenis_inquiry' => 'required',
            'id_customer' => 'required',
            'loc_imp' => 'required',
            // 'supplier' => 'required',
        ]);

        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Update field yang diperlukan
        $inquiry->jenis_inquiry = $request->jenis_inquiry; // Update jenis inquiry
        $inquiry->id_customer = $request->id_customer; // Update customer ID
        $inquiry->loc_imp = $request->loc_imp; // Update customer ID
        // $inquiry->supplier = $request->supplier; // Update supplier
        $inquiry->create_by = Auth::user()->name; // Update siapa yang membuat inquiry jika ikutan

        $inquiry->save(); // Simpan perubahan

        return redirect()->route('createinquiry')->with('success', 'Inquiry updated successfully');
    }


    public function delete($id)
    {
        // Temukan data berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);
        // Ubah is_active menjadi 0
        $inquiry->is_active = 0; // Jadi tidak aktif
        $inquiry->save();

        return response()->json(['success' => 'Inquiry deleted successfully']);
    }

    public function formulirInquiry($id)
    {
        $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);
        $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();
        $typeMaterials = TypeMaterial::all();

        return view('inquiry.formulirInquiry', compact('inquiry', 'materials', 'typeMaterials'));
    }

    public function previewSS(Request $request)
    {
        // Validasi input
        $request->validate([
            'id_inquiry' => 'required|integer',
            'materials' => 'required|array',
            'materials.*.id_type' => 'required|integer',
            'materials.*.jenis' => 'required|string',
            'materials.*.thickness' => 'nullable|string',
            'materials.*.weight' => 'nullable|string',
            'materials.*.inner_diameter' => 'nullable|string',
            'materials.*.outer_diameter' => 'nullable|string',
            'materials.*.length' => 'nullable|string',
            'materials.*.qty' => 'nullable|string',
            'materials.*.m1' => 'nullable|string',
            'materials.*.m2' => 'nullable|string',
            'materials.*.m3' => 'nullable|string',
            'materials.*.ship' => 'nullable|string',
            'materials.*.so' => 'required|string',
            'materials.*.note' => 'nullable|string',
            'materials.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10048',
        ]);

        // Ambil id_inquiry dari request
        $id_inquiry = $request->id_inquiry;
        Log::info('ID Inquiry:', ['id_inquiry' => $id_inquiry]);

        // Iterasi dan simpan atau update material
        foreach ($request->materials as $material) {

            DetailInquiry::updateOrCreate(
                [
                    'id_inquiry' => $id_inquiry,
                    'id_type' => $material['id_type'],
                ],
                [
                    'jenis' => $material['jenis'],
                    'thickness' => $material['thickness'],
                    'weight' => $material['weight'],
                    'inner_diameter' => $material['inner_diameter'],
                    'outer_diameter' => $material['outer_diameter'],
                    'length' => $material['length'],
                    'qty' => $material['qty'],
                    'm1' => $material['m1'],
                    'm2' => $material['m2'],
                    'm3' => $material['m3'],
                    'ship' => $material['ship'],
                    'so' => $material['so'], // Menyimpan so
                    'note' => $material['note']
                ]
            );
        }

        // Update status inquiry
        $inquiry = InquirySales::find($id_inquiry);
        if ($inquiry) {
            $inquiry->status = 2;
            $inquiry->save();
            Log::info('Inquiry status updated to 3', ['id' => $inquiry->id]);
        } else {
            Log::warning('Inquiry not found', ['id_inquiry' => $id_inquiry]);
            return response()->json(['message' => 'Inquiry not found'], 404);
        }

        return response()->json(['message' => 'Detail Inquiry saved successfully']);
    }


    public function uploadFile(Request $request)
    {
        $request->validate([
            'id_inquiry' => 'required|integer|exists:detail_inquiry,id',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // Validasi untuk file
        ]);

        // Ambil inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($request->id_inquiry);

        // Simpan file
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName(); // Buat nama file unik
        $filePath = public_path('assets/inquiry'); // Directory untuk file upload

        // Pastikan direktori ada
        if (!file_exists($filePath)) {
            mkdir($filePath, 0755, true); // Membuat direktori jika tidak ada
        }

        // Pindahkan file
        $file->move($filePath, $filename);

        // Simpan nama file ke detail inquiry atau tabel yang sesuai
        // Misalnya, Anda bisa update detail inquiry
        $material = DetailInquiry::where('id_inquiry', $inquiry->id)->first();
        if ($material) {
            $material->file = $filename; // Simpan nama file
            $material->save();
        }

        return response()->json(['message' => 'File uploaded successfully.']);
    }


    public function showFormSS($id)
    {
        $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);

        // Fetch all detail inquiries based on id_inquiry from the main inquiry
        $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();

        $typeMaterials = TypeMaterial::all(); // Ambil semua data TypeMaterial, sesuaikan dengan kebutuhan

        $progressUpdates = TrxDboProgPurchase::where('inquiry_id', $id)->with('user')->get();
        // Cek apakah berasal dari halaman approval
        $isFromApproval = request()->query('source') === 'approval';
        return view('inquiry.showFormSS', compact('inquiry', 'materials', 'typeMaterials', 'progressUpdates', 'isFromApproval'));
    }

    public function approveKaSie($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 4 (Approve Ka.Sie)
        $inquiry->status = 4; // Menandakan status "Approve Ka.Sie"
        // Simpan ID pengguna yang melakukan approve
        $inquiry->kasie_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->save();

        return redirect()->route('formulirInquiry', ['id' => $id])->with('success', 'Inquiry approved by Ka.Sie successfully.');
    }

    public function showApprovalKaSie()
    {
        // Ambil semua inquiry dengan status Open (2) dan yang belum disetujui
        $inquiries = InquirySales::with('customer')
            ->where('status', 2) // Hanya ambil yang berstatus Open
            ->where('is_active', 1) // Hanya yang aktif
            ->get();

        return view('inquiry.approvalKaSie', compact('inquiries'));
    }

    public function rejectKaSie($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 5 (atau status yang relevan untuk rejected)
        $inquiry->status = 7; // Misalnya status ditandai sebagai rejected
        $inquiry->save();

        return response()->json(['success' => 'Inquiry rejected successfully.']);
    }

    public function showApprovalKaDept()
    {
        // Ambil semua inquiry dengan status Open (2) dan yang belum disetujui
        $inquiries = InquirySales::with('customer')
            ->where('status', 4) // Hanya ambil yang berstatus Open
            ->where('is_active', 1) // Hanya yang aktif
            ->get();

        return view('inquiry.approvalKaDept', compact('inquiries'));
    }

    public function approveKaDept($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 3 (Approve Ka.Dept)
        $inquiry->status = 3; // Menandakan status "Approve Ka.Dept"
        // Simpan ID pengguna yang melakukan approve
        $inquiry->kadept_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->save();

        return redirect()->route('showApprovalKaDept')->with('success', 'Inquiry approved successfully by Ka.Dept.');
    }

    public function rejectKaDept($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 7 (Rejected)
        $inquiry->status = 7; // Menandakan status "Rejected"
        $inquiry->save();

        return redirect()->route('showApprovalKaDept')->with('success', 'Inquiry rejected successfully by Ka.Dept.');
    }

    public function showApprovalInventory()
    {
        // Ambil semua inquiry dengan status Open (2) dan yang belum disetujui
        $inquiries = InquirySales::with('customer')
            ->where('status', 3) // Hanya ambil yang berstatus Approve Ka.Dept
            ->where('is_active', 1) // Hanya yang aktif
            ->get();

        return view('inquiry.approvalInventory', compact('inquiries'));
    }

    public function approveInventory($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 8 (Approve Inventory)
        $inquiry->status = 8; // Menandakan status "Approve Inventory"
        // Simpan ID pengguna yang melakukan approve
        $inquiry->inventory_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->save();

        return redirect()->route('showApprovalInventory')->with('success', 'Inquiry approved successfully by Inventory.');
    }

    public function rejectInventory($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 7 (Rejected)
        $inquiry->status = 7; // Menandakan status "Rejected"
        $inquiry->save();

        return redirect()->route('showApprovalInventory')->with('success', 'Inquiry rejected successfully by Inventory');
    }

    public function overviewPurchase()
    {
        // Ambil semua inquiry dengan status relevan
        $inquiries = InquirySales::with('customer')
            ->whereIn('status', [5, 6, 8, 9]) // Mengambil status On Progress, Finished, etc.
            ->where('is_active', 1)
            ->get();

        $draftInquiries = InquirySales::with('customer')
            ->whereIn('status', [1, 2, 3, 4]) // Draft dan Open
            ->where('is_active', 1)
            ->get();

        return view('inquiry.overviewPurchase', compact('inquiries', 'draftInquiries'));
    }

    public function confirmPurchase($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi "Confirm Purchasing" (status 9)
        $inquiry->status = 9; // Menandakan status "Confirm Purchasing"
        $inquiry->save();

        // Mengembalikan response sukses
        return response()->json(['success' => 'Inquiry confirmed for purchasing successfully.']);
    }

    public function updateInquiry(Request $request)
    {
        // Validasi input
        $request->validate([
            'inquiry_id' => 'required|integer|exists:inquiry_sales,id',
            'supplier' => 'required|string',
            'progress' => 'nullable|string',
            'est_date' => 'nullable|date',
        ]);

        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($request->inquiry_id);

        // Update data inquiry
        $inquiry->supplier = $request->supplier;
        $inquiry->progress = $request->progress; // Jika Anda ingin menyimpan last_update
        $inquiry->est_date = $request->est_date;
        $inquiry->save();

        return response()->json(['message' => 'Inquiry updated successfully.']);
    }

    // public function confirmPurchase($id)
    // {
    //     // Temukan inquiry berdasarkan ID
    //     $inquiry = InquirySales::findOrFail($id);

    //     // Pastikan status adalah "Approved Inventory" (status 8)
    //     if ($inquiry->status !== 8) {
    //         return response()->json(['error' => 'The inquiry is not approved by Inventory yet.'], 400);
    //     }

    //     // Ubah status inquiry menjadi "Confirm Purchasing" (status 9)
    //     $inquiry->status = 9; // Confirm Purchasing
    //     $inquiry->save();

    //     return response()->json(['success' => 'Inquiry confirmed for purchasing successfully.']);
    // }

    // public function storeProgressPurchase(Request $request)
    // {
    //     // Validasi input
    //     $request->validate([
    //         'inquiry_id' => 'required|integer|exists:inquiry_sales,id',
    //         'progress_description' => 'required|string',
    //         'supplier' => 'required|string',
    //         'est_date' => 'nullable|date',
    //     ]);

    //     // Simpan data ke tabel trx_dbo_progpurchase
    //     $progressUpdate = new TrxDboProgPurchase();
    //     $progressUpdate->inquiry_id = $request->inquiry_id;
    //     $progressUpdate->user_id = Auth::id();
    //     $progressUpdate->description = $request->progress_description;
    //     $progressUpdate->save();

    //     // Update status inquiry menjadi "On Progress" (nilai 5)
    //     $inquiry = InquirySales::findOrFail($request->inquiry_id);
    //     $inquiry->supplier = $request->supplier;
    //     $inquiry->est_date = $request->est_date;
    //     $inquiry->status = 5; // On Progress
    //     $inquiry->purchasing_id = Auth::user()->id; // ID pengguna yang login
    //     $inquiry->save();

    //     return response()->json([
    //         'message' => 'Progress update saved successfully.',
    //         'inquiry' => $inquiry,
    //         'progress' => $progressUpdate
    //     ]);
    // }

    public function finishInquiry($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi "Finished" (status 6)
        $inquiry->status = 6; // Finished
        $inquiry->save();

        return response()->json(['success' => 'Inquiry marked as finished.']);
    }

    public function showProgressHistory($id)
    {
        $inquiry = InquirySales::findOrFail($id);
        $progressUpdates = TrxDboProgPurchase::where('inquiry_id', $id)->with('user')->get();

        return response()->json(['progressUpdates' => $progressUpdates]);
    }

    public function generatePDF($id)
    {
        // Ambil data inquiry berdasarkan ID
        $inquiry = InquirySales::with(['details.type_materials', 'kasie', 'kadept', 'inventory', 'purchasing'])->findOrFail($id);
        $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();

        // Ambil nama pengguna yang melakukan submit
        $submittedBy = $inquiry->create_by;

        // Ambil nama dari relasi
        $signatures = [
            'submitted' => $submittedBy,
            'approved_kasie' => $inquiry->kasie ? $inquiry->kasie->name : 'Waiting Approval',
            'approved_kadept' => $inquiry->kadept ? $inquiry->kadept->name : 'Waiting Approval',
            'approved_inventory' => $inquiry->inventory ? $inquiry->inventory->name : 'Waiting Approval',
            'confirmed_purchasing' => $inquiry->purchasing ? $inquiry->purchasing->name : 'Waiting Approval',
        ];

        // Konversi ke PDF dengan orientasi landscape
        $pdf = PDF::loadView('pdf.inquiry', compact('inquiry', 'materials', 'signatures'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('ADSI_FormInquiry.pdf');
    }
}
