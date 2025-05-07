<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\DetailInquiry;
use App\Models\DetailInquiryImport;
use App\Models\InquirySales;
use App\Models\TypeMaterial;
use App\Models\TrxDboProgPurchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDF;
use App\Exports\InquirySalesExport;
use App\Exports\DraftInquiryExport;
use App\Exports\InquiryImportInventoryExport;
use App\Exports\InquiryImportPurchaseExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class InquirySalesController extends Controller
{
    public function createInquirySales()
    {
        $statuses = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $inquiries = InquirySales::with('customer')
            ->whereIn('status', $statuses)
            ->where('is_active', 1)
            ->where('loc_imp', 'Local')
            ->orderByRaw('FIELD(status, 0, 1, 2, 3, 4, 5, 6, 7,8,9)')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('kode_inquiry');
        $customers = Customer::all();
        return view('inquiry.create', compact('inquiries', 'customers'));
    }

    public function createInquirySalesImport1(Request $request, $id)
    {
        $statuses = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        // Pastikan ID inquiry valid
        $inquiry = InquirySales::findOrFail($id);
        // Update status inquiry yang dipilih saja
        if ($inquiry->status == 1) {
            $inquiry->update(['status' => 2]);
        }
        // Ambil data inquiry setelah update
        $inquiry = InquirySales::with('customer')
            ->where('id', $id)
            ->whereIn('status', $statuses)
            ->where('is_active', 1)
            ->orderByRaw('FIELD(status, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9)')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('kode_inquiry');
        $customers = Customer::all();
        return redirect()->route('createinquiryImport');
    }

    public function createInquirySales1(Request $request, $id)
    {
        $statuses = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        // Pastikan ID inquiry valid
        $inquiry = InquirySales::findOrFail($id);
        // Update status inquiry yang dipilih saja
        if ($inquiry->status == 1) {
            $inquiry->update(['status' => 2]);
        }
        // Ambil data inquiry setelah update
        $inquiry = InquirySales::with('customer')
            ->where('id', $id)
            ->whereIn('status', $statuses)
            ->where('is_active', 1)
            ->orderByRaw('FIELD(status, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9)')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('kode_inquiry');
        $customers = Customer::all();
        return redirect()->route('createinquiry');
    }

    public function storeInquirySales(Request $request)
    {
        $request->validate([
            'jenis_inquiry' => 'required',
            'id_customer' => 'required',
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
        $inquiry->loc_imp = 'Local';
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
        // Ketika membuat Inquiry
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'Inquiry created.'
        ]);
        return redirect()->route('createinquiry')->with('success', 'Inquiry successfully saved.');
    }

    public function storeInquiryImport(Request $request)
    {
        $request->validate([
            'jenis_inquiry' => 'required',
            // 'id_customer' => 'required',
            'region' => 'required',
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
        // $inquiry->id_customer = $request->id_customer;
        $inquiry->loc_imp = 'Import';
        $inquiry->region = $request->region;
        // $inquiry->supplier = $request->supplier;
        // $inquiry->to_approve = 'Waiting';
        // $inquiry->to_validate = 'Waiting';
        $inquiry->status = 1;
        $inquiry->is_active = 1;
        $inquiry->create_by = Auth::user()->name;
        $inquiry->save();
        // Ketika membuat Inquiry
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => Auth::id(),
            'description' => 'Inquiry untuk Region [' . $inquiry->region . '] ditambahkan oleh ' . Auth::user()->name,
        ]);
        return redirect()->route('createinquiryImport')->with('success', 'Inquiry successfully saved.');
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

    public function formulirInquiryImport($id)
    {
        $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);
        $materials = DetailInquiryImport::where('id_inquiry', $inquiry->id)->with('type_materials')->get();
        $typeMaterials = TypeMaterial::all();
        $customers = Customer::all();

        return view('inquiry.formulirInquiryimport', compact('inquiry', 'materials', 'typeMaterials', 'customers'));
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

        // Ambil entri detail yang ada untuk inquiry
        $existingMaterials = DetailInquiry::where('id_inquiry', $id_inquiry)->get();

        // Iterasi dan simpan atau update material
        foreach ($request->materials as $material) {

            // Cek apakah material sudah ada
            $existingMaterial = $existingMaterials->where('id_type', $material['id_type'])->first();

            if ($existingMaterial) {
                // Jika sudah ada, update entri
                $existingMaterial->update([
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
                    'so' => $material['so'],
                    'note' => $material['note']
                ]);
            } else {
                // Jika belum ada, simpan sebagai entri baru
                DetailInquiry::create([
                    'id_inquiry' => $id_inquiry,
                    'id_type' => $material['id_type'],
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
                    'so' => $material['so'],
                    'note' => $material['note']
                ]);
            }
        }

        // Update status inquiry
        $inquiry = InquirySales::find($id_inquiry);
        if ($inquiry) {
            $inquiry->status = 1;
            $inquiry->save();
            Log::info('Inquiry status updated to 3', ['id' => $inquiry->id]);
        } else {
            Log::warning('Inquiry not found', ['id_inquiry' => $id_inquiry]);
            return response()->json(['message' => 'Inquiry not found'], 404);
        }

        // Ketika Inquiry Submitted
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'Inquiry Submitted'
        ]);

        return response()->json(['message' => 'Detail Inquiry saved successfully']);
    }

    public function previewSSImport(Request $request)
    {
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
            'materials.*.qty' => 'required|string',
            'materials.*.m1' => 'required|string',
            'materials.*.m2' => 'nullable|string',
            'materials.*.m3' => 'nullable|string',
            'materials.*.ship' => 'required|string',
            'materials.*.so' => 'required|string',
            'materials.*.note' => 'required|string',
            'materials.*.customer' => 'required|string',         // JSON string: ["1","2"]
            'materials.*.name_customer' => 'required|string',    // JSON string: ["PT A","PT B"]
            'materials.*.klasifikasi' => 'required|string',
        ]);

        $user = Auth::user();
        $user_id = $user->id;
        $user_name = $user->name;
        $id_inquiry = $request->id_inquiry;

        foreach ($request->materials as $material) {
            $customerIds = json_decode($material['customer'], true);

            if (!is_array($customerIds)) {
                return response()->json(['message' => 'Invalid customer format'], 422);
            }

            $validCustomerIds = Customer::whereIn('id', $customerIds)->pluck('id')->toArray();
            if (count($validCustomerIds) !== count($customerIds)) {
                return response()->json(['message' => 'Some customer IDs are invalid'], 404);
            }

            $newDetail = new DetailInquiryImport();
            $newDetail->id_inquiry = $id_inquiry;
            $newDetail->id_type = $material['id_type'];
            $newDetail->jenis = $material['jenis'];
            $newDetail->thickness = $material['thickness'];
            $newDetail->weight = $material['weight'];
            $newDetail->inner_diameter = $material['inner_diameter'];
            $newDetail->outer_diameter = $material['outer_diameter'];
            $newDetail->length = $material['length'];
            $newDetail->qty = $material['qty'];
            $newDetail->m1 = $material['m1'];
            $newDetail->m2 = $material['m2'];
            $newDetail->m3 = $material['m3'];
            $newDetail->ship = $material['ship'];
            $newDetail->so = $material['so'];
            $newDetail->note = $material['note'];
            $newDetail->create_by = $user_id;
            $newDetail->customer = $material['customer'];
            $newDetail->klasifikasi = $material['klasifikasi'];
            $newDetail->save();

            // Ambil nama tipe material
            $typeMaterial = TypeMaterial::find($material['id_type']);
            $typeName = $typeMaterial ? $typeMaterial->type_name : 'Unknown Type';

            // Buat log per item (jika kamu ingin log per material, bukan terakhir saja)
            TrxDboProgPurchase::create([
                'inquiry_id' => $id_inquiry,
                'user_id' => $user_id,
                'description' => 'Menambahkan material tipe "' . $typeName . '" oleh ' . $user_name,
            ]);
        }

        return response()->json(['message' => 'Detail Inquiry saved successfully']);
    }


    public function showFormSS(Request $request, $id)
    {
        $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);

        // Fetch all detail inquiries based on id_inquiry from the main inquiry
        $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();

        $typeMaterials = TypeMaterial::all(); // Ambil semua data TypeMaterial, sesuaikan dengan kebutuhan


        // Ambil semua nama file yang ter-upload
        $uploadedFiles = DetailInquiry::where('id_inquiry', $inquiry->id)
            ->pluck('file')
            ->flatMap(function ($file) {
                return json_decode($file) ?? []; // Kembalikan array kosong jika null
            })
            ->toArray();

        // $progressUpdates = TrxDboProgPurchase::where('inquiry_id', $id)->with('user')->get();
        $progressUpdates = TrxDboProgPurchase::where('inquiry_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc') // Urutkan berdasarkan created_at menurun
            ->get();

        // Cek apakah berasal dari halaman approval
        $isFromApproval = request()->query('source') === 'approval';
        return view('inquiry.showFormSS', compact('inquiry', 'materials', 'typeMaterials', 'progressUpdates', 'uploadedFiles', 'isFromApproval'));
    }

    public function showFormSSimport(Request $request, $id)
    {
        $inquiry = InquirySales::with('detailInquiryImport.type_materials')->findOrFail($id);

        // Ambil klasifikasi dari tombol
        $klasifikasi = request()->query('klasifikasi');

        // Fetch all detail inquiries based on id_inquiry from the main inquiry
        if (in_array($inquiry->status, [8, 9, 6]) && $klasifikasi) {
            $materials = DetailInquiryImport::withTrashed()
                ->where('id_inquiry', $inquiry->id)
                ->where('klasifikasi', $klasifikasi)
                ->with('type_materials')
                ->get();
        } else {
            $materials = DetailInquiryImport::withTrashed() // ← tambahkan ini
                ->where('id_inquiry', $inquiry->id)
                ->with('type_materials')
                ->get();
        }


        $typeMaterials = TypeMaterial::all();

        // Ambil semua nama file yang ter-upload
        $uploadedFiles = DetailInquiryImport::where('id_inquiry', $inquiry->id)
            ->pluck('file')
            ->flatMap(function ($file) {
                return json_decode($file) ?? [];
            })
            ->toArray();

        // Progress updates
        $progressUpdates = TrxDboProgPurchase::where('inquiry_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Customer & Users
        $customers = Customer::all();
        $users = User::all();

        // Cek apakah berasal dari halaman approval
        $isFromApproval = request()->query('source') === 'approval';

        return view('inquiry.showFormSSimport', compact('inquiry', 'materials', 'typeMaterials', 'progressUpdates', 'uploadedFiles', 'isFromApproval', 'customers', 'users'));
    }

    public function showFormSSimportinventory(Request $request, $id)
    {
        $inquiry = InquirySales::with('detailInquiryImport.type_materials')->findOrFail($id);

        // Ambil klasifikasi dari tombol
        $klasifikasi = request()->query('klasifikasi');

        // Fetch all detail inquiries based on id_inquiry from the main inquiry
        if (in_array($inquiry->status, [8, 9, 6]) && $klasifikasi) {
            $materials = DetailInquiryImport::where('id_inquiry', $inquiry->id)
                ->where('klasifikasi', $klasifikasi)
                ->with('type_materials')
                ->get();
        } else {
            $materials = DetailInquiryImport::where('id_inquiry', $inquiry->id)
                ->with('type_materials')
                ->get();
        }


        $typeMaterials = TypeMaterial::all();

        // Ambil semua nama file yang ter-upload
        $uploadedFiles = DetailInquiryImport::where('id_inquiry', $inquiry->id)
            ->pluck('file')
            ->flatMap(function ($file) {
                return json_decode($file) ?? [];
            })
            ->toArray();

        // Progress updates
        $progressUpdates = TrxDboProgPurchase::where('inquiry_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Customer & Users
        $customers = Customer::all();
        $users = User::all();

        // Cek apakah berasal dari halaman approval
        $isFromApproval = request()->query('source') === 'approval';

        return view('inquiry.showFormSSimport', compact('inquiry', 'materials', 'typeMaterials', 'progressUpdates', 'uploadedFiles', 'isFromApproval', 'customers', 'users'));
    }

    public function approveKaSie($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 4 (Approve Ka.Sie)
        $inquiry->status = 4; // Menandakan status "Approve Ka.Sie"
        // Simpan ID pengguna yang melakukan approve
        $inquiry->kasie_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->approved_kasie_at = now();
        $inquiry->save();

        // Ketika menyetujui oleh Ka.Sie
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'Approved by Ka. Sie.'
        ]);

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

    public function showApprovalKaDeptImport()
    {
        // Ambil semua inquiry dengan status Open (2) dan yang belum disetujui
        $inquiries = InquirySales::with('customer')
            ->where('status', 4) // Hanya ambil yang berstatus Open
            ->where('is_active', 1) // Hanya yang aktif
            ->where('loc_imp', 'Import') // Pastikan loc_imp benar-benar 'Import'
            ->latest()
            ->get();

        return view('inquiry.approvalKaDeptImport', compact('inquiries'));
    }

    public function approveKaDept($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 3 (Approve Ka.Dept)
        $inquiry->status = 3; // Menandakan status "Approve Ka.Dept"
        // Simpan ID pengguna yang melakukan approve
        $inquiry->kadept_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->approved_kadept_at = now();
        $inquiry->save();

        // Ketika menyetujui oleh Ka.Dept
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'Approved by Ka. Dept.'
        ]);

        return redirect()->route('showApprovalKaDept')->with('success', 'Inquiry approved successfully by Ka.Dept.');
    }


    public function approveKaDeptImport($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::whereIn('loc_imp', ['Import'])->findOrFail($id);

        // Ubah status inquiry menjadi 3 (Approve Ka.Dept)
        $inquiry->status = 3; // Menandakan status "Approve Ka.Dept"
        // Simpan ID pengguna yang melakukan approve
        $inquiry->kadept_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->kasie_id = Auth::user()->id;
        $inquiry->approved_kadept_at = now();
        $inquiry->approved_kasie_at = now();
        $inquiry->save();

        // Ketika menyetujui oleh Ka.Dept
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'Approved by Ka. Dept.'
        ]);

        // Format bulan dan tahun dibuat
        $createdMonth = Carbon::parse($inquiry->created_at)->format('F');
        $createdYear = Carbon::parse($inquiry->created_at)->format('Y');

        // Ketika menyetujui oleh Inventory
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'inquiry Region [ ' . $inquiry->region . ' ] Bulan ' . $createdMonth . ' ' . $createdYear . ' di submit oleh ' . auth::user()->name
        ]);

        return redirect()->route('showApprovalInventoryImport')->with('success', 'Inquiry approved successfully by Ka.Dept.');
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
        $inquiries = InquirySales::with(['customer', 'details'])
            ->where('status', 3) // Hanya ambil yang berstatus Approve Ka.Dept
            ->where('is_active', 1) // Hanya yang aktif
            ->get();

        return view('inquiry.approvalInventory', compact('inquiries'));
    }

    public function showApprovalInventoryImport()
    {
        // Ambil semua inquiry dengan status Open (2) dan yang belum disetujui
        $inquiries = InquirySales::with(['customer', 'details'])
            ->where('status', 3) // Hanya ambil yang berstatus Approve Ka.Dept
            ->where('is_active', 1) // Hanya yang aktif
            ->where('loc_imp', 'Import') // Pastikan loc_imp benar-benar 'Import'
            ->latest()
            ->get();

        return view('inquiry.approvalInventoryImport', compact('inquiries'));
    }

    public function approveInventory($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi 8 (Approve Inventory)
        $inquiry->status = 8; // Menandakan status "Approve Inventory"
        // Simpan ID pengguna yang melakukan approve
        $inquiry->inventory_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->approved_inventory_at = now();
        $inquiry->save();

        // Ketika menyetujui oleh Inventory
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'Approved by Inventory. ' . Auth::user()->name
        ]);

        return redirect()->route('showApprovalInventory')->with('success', 'Inquiry approved successfully by Inventory.');
    }

    public function approveInventoryImport($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::whereIn('loc_imp', ['Import'])->findOrFail($id);

        // Ubah status inquiry menjadi 8 (Approve Inventory)
        $inquiry->status = 8; // Menandakan status "Approve Inventory"
        // Simpan ID pengguna yang melakukan approve
        $inquiry->inventory_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->approved_inventory_at = now();
        $inquiry->save();

        // Format bulan dan tahun dibuat
        $createdMonth = Carbon::parse($inquiry->created_at)->format('F');
        $createdYear = Carbon::parse($inquiry->created_at)->format('Y');

        // Ketika menyetujui oleh Inventory
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'inquiry Region [ ' . $inquiry->region . ' ] Bulan ' . $createdMonth . ' ' . $createdYear . ' di konfirmasi inventory oleh ' . auth::user()->name
        ]);

        return redirect()->route('showApprovalInventoryImport')->with('success', 'Inquiry approved successfully by Inventory.');
    }

    public function rejectInventoryImport($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::whereIn('loc_imp', ['Import'])->findOrFail($id);

        // Ubah status inquiry menjadi 7 (Rejected)
        $inquiry->status = 7; // Menandakan status "Rejected"
        $inquiry->save();

        return redirect()->route('showApprovalInventoryImport')->with('success', 'Inquiry rejected successfully by Inventory');
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
            ->orderBy('created_at', 'desc')
            ->get();

        $draftInquiries = InquirySales::with('customer')
            ->whereIn('status', [1, 2, 3, 4]) // Draft dan Open
            ->where('is_active', 1)
            ->get();

        return view('inquiry.overviewPurchase', compact('inquiries', 'draftInquiries'));
    }

    public function overviewPurchaseImport()
    {
        // Ambil semua inquiry dengan status relevan dan loc_imp harus 'Import'
        $inquiries = InquirySales::whereIn('status', [5, 6, 8, 9]) // Mengambil status On Progress, Finished, etc.
            ->where('loc_imp', 'Import') // Pastikan loc_imp benar-benar 'Import'
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $draftInquiries = InquirySales::whereIn('status', [1, 2, 3, 4]) // Draft dan Open
            ->where('loc_imp', 'Import') // Pastikan loc_imp benar-benar 'Import'
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('inquiry.overviewPurchaseImport', compact('inquiries', 'draftInquiries'));
    }


    public function confirmPurchase($id)
    {
        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        // Ubah status inquiry menjadi "Confirm Purchasing" (status 9)
        $inquiry->status = 9; // Menandakan status "Confirm Purchasing"
        $inquiry->purchasing_id = Auth::user()->id; // Ambil ID pengguna yang login
        $inquiry->confirmed_purchasing_at = now();
        $inquiry->save();

        // Ketika Confirm by Procurement
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'Confirm Inquiry by Procurement.'
        ]);

        // Mengembalikan response sukses
        return response()->json(['success' => 'Inquiry confirmed for purchasing successfully.']);
    }

    public function confirmPurchaseimport(Request $request)
{
    $ids = $request->input('ids');
    $klasifikasi = $request->input('klasifikasi');

    if (!is_array($ids) || empty($ids)) {
        return response()->json(['error' => 'No inquiry IDs provided.'], 400);
    }

    $user = Auth::user();

    foreach ($ids as $id) {
        // Cek apakah ada detail inquiry dengan klasifikasi yang dimaksud
        $hasValidDetail = DetailInquiryImport::where('id_inquiry', $id)
            ->where('klasifikasi', $klasifikasi)
            ->exists();

        if (!$hasValidDetail) {
            continue; // Skip jika tidak ada klasifikasi yang sesuai
        }

        // Ambil inquiry-nya
        $inquiry = InquirySales::find($id);

        if (!$inquiry || $inquiry->status != 8) {
            continue; // Skip jika tidak ditemukan atau status bukan 8
        }

        // Update inquiry
        $inquiry->status = 9;
        $inquiry->purchasing_id = $user->id;
        $inquiry->confirmed_purchasing_at = now();
        $inquiry->save();

        // Format tanggal
        $createdMonth = Carbon::parse($inquiry->created_at)->format('F');
        $createdYear = Carbon::parse($inquiry->created_at)->format('Y');

        // Simpan progress ke tracking
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => $user->id,
            'description' => 'Inquiry Region [ ' . $inquiry->region . ' ] Bulan ' . $createdMonth . ' ' . $createdYear . ' dikonfirmasi purchase oleh ' . $user->name
        ]);
    }

    return response()->json(['success' => 'Selected inquiries have been successfully confirmed for purchasing.']);
}


    public function exportexceloverviewimportpurchase()
    {
        return Excel::download(new DraftInquiryExport, 'inquiry-sales.xlsx');
    }

    public function importexceloverviewimportpurchase(Request $request)
    {
        // 1️⃣ Validasi apakah file dikirim
        $request->validate([
            'file' => 'required|mimes:xlsx,xls', // Hapus batasan ukuran file
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            foreach ($rows as $index => $row) {
                if ($index < 2) {
                    // Skip the first two rows
                    continue;
                }

                // Normalize numeric values to remove unnecessary decimals
                foreach ($row as $key => $value) {
                    if (is_numeric($value)) {
                        $row[$key] = (string) intval($value);
                    }
                }

                // Check if record exists
                $existingRecord = InquirySales::where('id', $row[0])->where('kode_inquiry', $row[2])->first();

                $data = [
                    'id' => $row[0],
                    'id_customer' => $row[1],
                    'kode_inquiry' => $row[2],
                    'type_order' => $row[3],
                    'jenis_inquiry' => $row[4],
                    'loc_imp' => $row[5],
                    'est_date' => $row[6],
                    'supplier' => $row[7],
                    'create_by' => $row[8],
                    'progress' => $row[9],
                    'refnopo' => $row[10],
                    'status' => $row[11],
                    'updated_at' => now(),
                    'modified_by' => now(),
                    'region' => $row[14],
                ];

                if ($existingRecord) {
                    // Update the existing record
                    $existingRecord->update($data);
                } else {
                    // Create a new record
                    InquirySales::create($data);
                }
            }

            return response()->json(['success' => true, 'message' => 'Inquiry Import berhasil']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }



    public function updateInquiry(Request $request)
    {
        // Validasi input
        $request->validate([
            'inquiry_id' => 'required|integer|exists:inquiry_sales,id',
            'supplier' => 'required|string',
            'progress' => 'nullable|string',
            'refnopo' => 'nullable|string',
            'est_date' => 'nullable|date',
        ]);

        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($request->inquiry_id);

        // Update data inquiry
        $inquiry->supplier = $request->supplier;
        $inquiry->progress = $request->progress;
        $inquiry->refnopo = $request->refnopo;
        $inquiry->est_date = $request->est_date;
        $inquiry->status = 5;
        $inquiry->save();

        // Simpan terakhir update ke tabel trx_dbo_progpurchase
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(), // Atau ID pengguna yang sesuai
            'description' => $request->progress,
        ]);

        return response()->json(['message' => 'Inquiry updated successfully.']);
    }

    public function updateInquiryImport(Request $request)
{
    $request->validate([
        'ids' => 'required|array',
        'ids.*' => 'integer|exists:inquiry_sales,id',
        'description' => 'required|string',
        'klasifikasi' => 'required|string'
    ]);

    $userId = auth()->id();
    $description = $request->description;
    $klasifikasi = $request->klasifikasi;

    $updatedCount = 0;

    foreach ($request->ids as $id) {
        // Validasi klasifikasi melalui tabel detail
        $hasValidDetail = DetailInquiryImport::where('id_inquiry', $id)
            ->where('klasifikasi', $klasifikasi)
            ->exists();

        if ($hasValidDetail) {
            TrxDboProgPurchase::create([
                'inquiry_id' => $id,
                'user_id' => $userId,
                'description' => $description,
            ]);
            $updatedCount++;
        }
    }

    return response()->json([
        'message' => "Description updated for $updatedCount inquiries with klasifikasi '$klasifikasi'."
    ]);
}



public function updateProgressImport(Request $request, $id)
{
    try {
        $request->validate([
            'progress' => 'required|in:ok,pending,cancelled',
        ]);

        $inquiry = DetailInquiryImport::findOrFail($id);
        $inquiry->progress = $request->progress;
        $inquiry->save();

        return response()->json([
            'success' => true,
            'message' => 'Progress updated successfully',
        ], 200);
    } catch (\Exception $e) {
        \Log::error('Error updating progress: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to update progress',
        ], 500);
    }
}


    public function updateInquiryDetails(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'materials.*.id_type' => 'required|integer',
            'materials.*.jenis' => 'required|string',
            'materials.*.thickness' => 'nullable|numeric',
            'materials.*.weight' => 'nullable|numeric',
            'materials.*.inner_diameter' => 'nullable|numeric',
            'materials.*.outer_diameter' => 'nullable|numeric',
            'materials.*.length' => 'nullable|numeric',
            'materials.*.qty' => 'required|integer',
            'materials.*.m1' => 'nullable|numeric',
            'materials.*.m2' => 'nullable|numeric',
            'materials.*.m3' => 'nullable|numeric',
            'materials.*.ship' => 'required|string',
            'materials.*.so' => 'nullable|string',
            'materials.*.note' => 'nullable|string',
        ]);

        // Temukan inquiry berdasarkan ID
        $inquiry = InquirySales::findOrFail($id);

        $updatedMaterials = [];

        // Update data materials
        foreach ($request->materials as $materialData) {
            $material = DetailInquiry::find($materialData['id']); // Gunakan ID langsung
        
            if ($material) {
                $material->id_type = $materialData['id_type']; // Bisa berubah sekarang
                $jenis = $materialData['jenis'];
        
                // Update sesuai jenis
                $material->jenis = $jenis;
                $material->thickness = ($jenis === 'Flat') ? $materialData['thickness'] : null;
                $material->weight = ($jenis === 'Flat') ? $materialData['weight'] : null;
                $material->inner_diameter = ($jenis === 'Honed Tube') ? $materialData['inner_diameter'] : null;
                $material->outer_diameter = ($jenis === 'Round' || $jenis === 'Honed Tube') ? $materialData['outer_diameter'] : null;
                $material->length = $materialData['length'];
                $material->qty = $materialData['qty'];
                $material->m1 = $materialData['m1'];
                $material->m2 = $materialData['m2'];
                $material->m3 = $materialData['m3'];
                $material->ship = $materialData['ship'];
                $material->so = $materialData['so'];
                $material->note = $materialData['note'];
                $material->save();
        
                $updatedMaterials[] = $material;
            }
        }
        

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui!',
            'updatedMaterials' => $updatedMaterials // Kirim data terbaru ke frontend
        ]);
    }

    public function updateInquiryDetailsImport(Request $request, $id)
    {
        // Validasi data
        $validatedData = $request->validate([
            'id' => 'required|exists:inquiry_details,id',
            'id_type' => 'required|exists:type_materials,id',
            'jenis' => 'required|in:Flat,Round,Honed Tube',
            'thickness' => 'required_if:jenis,Flat',
            'weight' => 'required_if:jenis,Flat',
            'inner_diameter' => 'required_if:jenis,Round,Honed Tube',
            'outer_diameter' => 'required_if:jenis,Round,Honed Tube',
            'length' => 'required',
            'qty' => 'required',
            'm1' => 'required',
            'm2' => 'required',
            'm3' => 'required',
            'ship' => 'required|in:Deltamas,DS8',
            'so' => 'required',
            'note' => 'nullable',
            'customer' => 'required|array',
            'customer.*' => 'exists:customers,id',
        ]);

        // Temukan detail inquiry berdasarkan ID
        $inquiryDetail = DetailInquiryImport::findOrFail($validatedData['id']);
        $originalData = $inquiryDetail->getOriginal();

        // Encode array customer sebagai JSON
        $encodedCustomer = json_encode($validatedData['customer']);

        // Perbarui data detail inquiry
        $inquiryDetail->update([
            'id_type' => $validatedData['id_type'],
            'jenis' => $validatedData['jenis'],
            'thickness' => $validatedData['thickness'],
            'weight' => $validatedData['weight'],
            'inner_diameter' => $validatedData['inner_diameter'],
            'outer_diameter' => $validatedData['outer_diameter'],
            'length' => $validatedData['length'],
            'qty' => $validatedData['qty'],
            'm1' => $validatedData['m1'],
            'm2' => $validatedData['m2'],
            'm3' => $validatedData['m3'],
            'ship' => $validatedData['ship'],
            'so' => $validatedData['so'],
            'note' => $validatedData['note'],
            'customer' => $encodedCustomer,
        ]);

        // Cek perubahan
        $changes = [];
        foreach ($validatedData as $key => $value) {
            if ($key === 'customer') {
                $oldCustomer = json_decode($originalData['customer'] ?? '[]');
                $newCustomer = $validatedData['customer'];
                if (json_encode($oldCustomer) !== json_encode($newCustomer)) {
                    $changes[] = "customer: [" . implode(',', $oldCustomer) . "] → [" . implode(',', $newCustomer) . "]";
                }
            } elseif (array_key_exists($key, $originalData) && $originalData[$key] != $value) {
                $changes[] = "$key: " . ($originalData[$key] ?? '-') . " → " . $value;
            }
        }

        // Buat log jika ada perubahan
        if (!empty($changes)) {
            TrxDboProgPurchase::create([
                'inquiry_id' => $inquiryDetail->id_inquiry,
                'user_id' => Auth::id(),
                'description' => 'Detail Inquiry diupdate oleh ' . Auth::user()->name . ' | Perubahan: ' . implode(', ', $changes),
            ]);
        }

        return response()->json(['success' => true]);
    }


    public function editimport($id)
    {
        // Mengambil data DetailInquiryImport berdasarkan ID yang diberikan
        $materials = DetailInquiryImport::where('id', $id)->get();

        // Pastikan ada data materials sebelum mengambil inquiry
        if ($materials->isEmpty()) {
            abort(404, 'Data tidak ditemukan');
        }

        // Mengambil ID Inquiry dari DetailInquiryImport
        $id_inquiry = $materials->first()->id_inquiry;

        // Mengambil data InquirySales berdasarkan id_inquiry
        $inquiry = InquirySales::findOrFail($id_inquiry);

        // Ambil semua data TypeMaterial dan Customer
        $typeMaterials = TypeMaterial::all();
        $customers = Customer::all();

        return view('inquiry.updateinquiryimport', compact('inquiry', 'typeMaterials', 'customers', 'materials'));
    }

    public function updateImport(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'materials.*.id_type' => 'required|integer',
            'materials.*.jenis' => 'required|string',
            'materials.*.thickness' => 'nullable|numeric',
            'materials.*.weight' => 'nullable|numeric',
            'materials.*.inner_diameter' => 'nullable|numeric',
            'materials.*.outer_diameter' => 'nullable|numeric',
            'materials.*.length' => 'nullable|numeric',
            'materials.*.qty' => 'required|integer',
            'materials.*.m1' => 'nullable|numeric',
            'materials.*.m2' => 'nullable|numeric',
            'materials.*.m3' => 'nullable|numeric',
            'materials.*.ship' => 'required|string',
            'materials.*.so' => 'nullable|string',
            'materials.*.note' => 'nullable|string',
            'materials.*.customer' => 'required|string',
        ]);

        $logs = [];
        $user = Auth::user();
        $userId = $user->id;
        $userName = $user->name;

        foreach ($request->materials as $materialData) {
            $material = DetailInquiryImport::find($materialData['id']);


            $typematerial = TypeMaterial::find($materialData['id_type']);
            if ($typematerial) {
                $materialData['id_type'] = $typematerial->id;
            }


            if ($material) {
                $oldData = $material->toArray();
                $jenis = $materialData['jenis'];

                // Ambil nama tipe dari relasi type_materials
                $typeName = TypeMaterial::find($materialData['id_type'])->type_name ?? 'Unknown';

                // Update nilai
                $material->id_type = $materialData['id_type'];
                $material->jenis = $jenis;
                $material->thickness = ($jenis === 'Flat') ? $materialData['thickness'] : null;
                $material->weight = ($jenis === 'Flat') ? $materialData['weight'] : null;
                $material->inner_diameter = ($jenis === 'Honed Tube') ? $materialData['inner_diameter'] : null;
                $material->outer_diameter = (in_array($jenis, ['Round', 'Honed Tube'])) ? $materialData['outer_diameter'] : null;
                $material->length = $materialData['length'];
                $material->qty = $materialData['qty'];
                $material->m1 = $materialData['m1'];
                $material->m2 = $materialData['m2'];
                $material->m3 = $materialData['m3'];
                $material->ship = $materialData['ship'];
                $material->so = $materialData['so'];
                $material->note = $materialData['note'];
                $material->customer = $materialData['customer'];

                // Catat perubahan
                $ignoredFields = ['created_at', 'updated_at'];
                $changes = [];

                foreach ($material->getAttributes() as $key => $newValue) {
                    if (in_array($key, $ignoredFields)) continue;

                    $oldValue = $oldData[$key] ?? null;
                    if ($oldValue != $newValue) {
                        $changes[] = "'$key' \"$oldValue\" => \"$newValue\"";
                    }
                }

                if (!empty($changes)) {
                    $logs[] = [
                        'inquiry_id' => $id,
                        'description' => "Perubahan data $typeName: " . implode('; ', $changes) . " | Diubah: $userName",
                        'user_id' => $userId,
                    ];
                }

                $material->save();
            }
        }


        // Simpan log perubahan
        if (!empty($logs)) {
            DB::table('trx_dbo_progpurchase')->insert($logs);
        }

        return redirect()->route('showFormSSimport', ['id' => $id])
            ->with('success', 'Data berhasil diperbarui dan perubahan dicatat.');
    }

    function sanitizeToAscii($string)
    {
        $string = str_replace('→', '->', $string); // opsional
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    }


    public function importInquiryInventory(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $now = Carbon::now();
            $userId = Auth::id();

            // Ambil reference
            $typeIdList = DB::table('type_materials')->pluck('id', 'type_name')->toArray();
            $partnerList = DB::table('customers')->pluck('id', 'name_customer')->toArray();

            $inquiryUpdates = [];
            $detailUpdates = [];
            $logs = [];

            // Ambil data customer dari DB sekali di awal (di luar foreach rows)
            $customerRaw = DB::table('customers')->select('id', 'name_customer')->get();
            $customerMap = [];

            foreach ($customerRaw as $c) {
                $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $c->name_customer)));
                $customerMap[$normalized] = (string) $c->id;
            }

            foreach ($rows as $index => $row) {
                if ($index < 1 || empty(array_filter($row)) || count($row) < 28) {
                    continue;
                }

                $inquiryId = $row[26] ?? null;
                $detailId = $row[27] ?? null;

                $oldInquiry = DB::table('inquiry_sales')->where('id', $inquiryId)->first();
                $oldDetail = DB::table('detail_inquiry_import')->where('id', $detailId)->first();

                // // Proses customer array
                // $customerRaw = $row[3] ?? null;
                // $customerNames = array_map('trim', explode('; ', $customerRaw));
                // $customerIds = [];

                // foreach ($customerNames as $name) {
                //     if (isset($partnerList[$name])) {
                //         $customerIds[] = $partnerList[$name];
                //     }
                // }

                $customerRaw = $row[3] ?? '';
                $customerNames = array_map('trim', explode(';', $customerRaw));
                $customerIds = [];

                foreach ($customerNames as $name) {
                    $normalizedName = strtolower(trim(preg_replace('/\s+/', ' ', $name)));

                    if (isset($customerMap[$normalizedName])) {
                        $customerIds[] = $customerMap[$normalizedName];
                    } else {
                        Log::warning("Customer tidak ditemukan: [$normalizedName]");
                    }
                }

                $newInquiryData = [
                    'id' => $inquiryId,
                    'region' => $row[2] ?? null,
                    'kode_inquiry' => $row[4] ?? null,
                    'type_order' => $row[5] ?? null,
                    'jenis_inquiry' => $row[6] ?? null,
                    'loc_imp' => $row[7] ?? null,
                    'est_date' => !empty($row[8]) ? Carbon::parse($row[8])->format('Y-m-d') : null,
                    'create_by' => $row[9] ?? null,
                ];

                $newDetailData = [
                    'id_inquiry' => $inquiryId,
                    'id' => $detailId,
                    'id_type' => $typeIdList[$row[11] ?? ''] ?? null,
                    'jenis' => $row[12] ?? null,
                    'thickness' => $row[13] ?? null,
                    'inner_diameter' => $row[14] ?? null,
                    'outer_diameter' => $row[15] ?? null,
                    'weight' => $row[16] ?? null,
                    'length' => $row[17] ?? null,
                    'qty' => $row[18] ?? null,
                    'm1' => $row[19] ?? null,
                    'm2' => $row[20] ?? null,
                    'm3' => $row[21] ?? null,
                    'so' => $row[22] ?? null,
                    'ship' => $row[23] ?? null,
                    'note' => $row[24] ?? null,
                    'progress' => $row[28] ?? null,
                    'customer' => json_encode($customerIds),
                    'create_by' => $userId,
                    'est_date' => $row[8] ?? null,
                ];

                $changeDescription = '';

                if ($oldInquiry) {
                    foreach ($newInquiryData as $key => $value) {
                        if ($oldInquiry->$key != $value) {
                            $changeDescription .= "{$key}: '{$oldInquiry->$key}' → '{$value}'; ";
                        }
                    }
                }

                if ($oldDetail) {
                    foreach ($newDetailData as $key => $value) {
                        if ($oldDetail->$key != $value) {
                            $changeDescription .= "{$key}: '{$oldDetail->$key}' → '{$value}'; ";
                        }
                    }
                }

                if ($changeDescription) {
                    $description = "Updated via Excel Import by " . Auth::user()->name . ". Changes: " . $changeDescription;
                    $logs[] = [
                        'inquiry_id' => $inquiryId,
                        'description' => $this->sanitizeToAscii($description),
                        'user_id' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $inquiryUpdates[] = $newInquiryData;
                $detailUpdates[] = $newDetailData;
            }

            if (!empty($inquiryUpdates)) {
                DB::table('inquiry_sales')->upsert($inquiryUpdates, ['id'], array_keys($inquiryUpdates[0]));
            }

            if (!empty($detailUpdates)) {
                DB::table('detail_inquiry_import')->upsert($detailUpdates, ['id'], array_keys($detailUpdates[0]));
            }

            if (!empty($logs)) {
                DB::table('trx_dbo_progpurchase')->insert($logs);
            }

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil',
                'redirect' => route('showApprovalInventoryImport')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function importInquirypurchase(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(false); // Pastikan format tetap dipertahankan
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $now = Carbon::now();
            $userId = Auth::id();
            $userName = Auth::user()->name;

            // Ambil type_materials untuk reference
            $typeMaterials = DB::table('type_materials')->pluck('id', 'type_name');
            $partner = DB::table('users')->pluck('id', 'name');

            // Array untuk batch insert/update
            $inquiryUpdates = [];
            $detailUpdates = [];
            $logs = [];

            $customerRaw = DB::table('customers')->select('id', 'name_customer')->get();
            $customerMap = [];

            foreach ($customerRaw as $c) {
                $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $c->name_customer)));
                $customerMap[$normalized] = (string) $c->id;
            }

            foreach ($rows as $index => $row) {
                // *Lewati 2 baris pertama (judul) & baris kosong*
                if ($index < 1 || empty(array_filter($row))) {
                    continue;
                }

                // *Pastikan jumlah kolom cukup sebelum akses indeks*
                if (count($row) < 30) {
                    continue;
                }

                $partnerId = isset($row[25]) ? ($partner[$row[25]] ?? null) : null;
                // *Ambil Type ID dengan cek validitas data*
                $typeId = isset($row[11]) ? ($typeMaterials[$row[11]] ?? null) : null;

                // *Pastikan ID inquiry tidak kosong*
                if (empty($row[26])) {
                    continue;
                }

                $customerRaw = $row[4] ?? '';
                $customerNames = array_map('trim', explode(';', $customerRaw));
                $customerIds = [];

                foreach ($customerNames as $name) {
                    $normalizedName = strtolower(trim(preg_replace('/\s+/', ' ', $name)));

                    if (isset($customerMap[$normalizedName])) {
                        $customerIds[] = $customerMap[$normalizedName];
                    } else {
                        Log::warning("Customer tidak ditemukan: [$normalizedName]");
                    }
                }

                $inquiryUpdates[] = [
                    'id' => $row[26] ?? null,
                    'region' => $row[2] ?? null,
                    'kode_inquiry' => $row[5] ?? null,
                    'type_order' => $row[6] ?? null,
                    'jenis_inquiry' => $row[7] ?? null,
                    'loc_imp' => $row[8] ?? null,
                    'est_date' => !empty($row[9]) ? Carbon::parse($row[9])->format('Y-m-d') : null,
                    'create_by' => $row[10] ?? null,
                    'updated_at' => $now,
                ];

                $detailUpdates[] = [
                    'id' => $row[27] ?? null,
                    'id_inquiry' => $row[26] ?? null,
                    'id_type' => $typeId,
                    'jenis' => $row[12] ?? null,
                    'thickness' => $row[13] ?? null,
                    'inner_diameter' => $row[14] ?? null,
                    'outer_diameter' => $row[15] ?? null,
                    'weight' => $row[16] ?? null,
                    'length' => $row[17] ?? null,
                    'qty' => $row[18] ?? null,
                    'm1' => $row[19] ?? null,
                    'm2' => $row[20] ?? null,
                    'm3' => $row[21] ?? null,
                    'so' => $row[22] ?? null,
                    'ship' => $row[23] ?? null,
                    'note' => $row[24] ?? null,
                    'customer' => json_encode($customerIds),
                    'create_by' => $partnerId,
                    'updated_at' => $now,
                    'nopo' => $row[29] ?? null,
                    'supplier' => $row[30] ?? null,
                    'progress' => $row[28] ?? null,
                ];

                $logs[] = [
                    'inquiry_id' => $row[26] ?? null,
                    'description' => 'Updated purchase oleh ' . $userName . ' via Excel Import',
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // *Batch insert/update hanya jika ada data*
            if (!empty($inquiryUpdates)) {
                DB::table('inquiry_sales')->upsert($inquiryUpdates, ['id'], array_keys($inquiryUpdates[0]));
            }

            if (!empty($detailUpdates)) {
                DB::table('detail_inquiry_import')->upsert($detailUpdates, ['id_inquiry'], array_keys($detailUpdates[0]));
            }

            if (!empty($logs)) {
                DB::table('trx_dbo_progpurchase')->insert($logs);
            }

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function exportinquirypurchaseimport(Request $request)
    {

        $ids = explode(',', $request->query('ids'));
        $klasifikasi = $request->query('klasifikasi');

        if (empty($ids)) {
            abort(400, 'ID inquiry tidak ditemukan');
        }

        $currentDateExport = Carbon::now()->format('d-m-y');

        return Excel::download(
            new InquiryImportPurchaseExport($ids, $klasifikasi),
            'IMP_Purch_Export_' . $currentDateExport . '.xlsx'
        );
    }


    public function deleteInquiryDetailImport($id)
    {
        try {
            $userName = Auth::user()->name;

            // Ambil data material + relasi type
            $material = DetailInquiryImport::find($id);
            if (!$material) {
                return Response::json(['success' => false, 'message' => 'Material not found'], 404);
            }

            // Ambil type material
            $typeMaterial = TypeMaterial::find($material->id_type);
            $typeName = $typeMaterial ? $typeMaterial->type_name : 'Unknown Type';

            // Ambil inquiry
            $inquiry = InquirySales::findOrFail($material->id_inquiry);
            $region = $inquiry->region ?? 'Unknown Region';
            $monthYear = Carbon::parse($inquiry->created_at)->translatedFormat('F Y');

            // Hapus material
            $material->delete();

            // Catat ke log progress purchase
            TrxDboProgPurchase::create([
                'inquiry_id' => $inquiry->id,
                'user_id' => auth()->id(),
                'description' => "Material dengan tipe '{$typeName}' untuk region '{$region}' bulan {$monthYear} dihapus oleh {$userName}.",
            ]);

            return Response::json(['success' => true, 'message' => 'Material deleted successfully']);
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'message' => 'Failed to delete material'], 500);
        }
    }

    public function deleteInquiryDetailImportpermanen($id)
    {
        try {
            $userName = Auth::user()->name;

            $material = DetailInquiryImport::withTrashed()->find($id);
            if (!$material) {
                return Response::json(['success' => false, 'message' => 'Material not found'], 404);
            }

            if ($material->create_by != Auth::id()) {
                return Response::json(['success' => false, 'message' => 'Unauthorized action'], 403);
            }

            $typeMaterial = TypeMaterial::find($material->id_type);
            $typeName = $typeMaterial ? $typeMaterial->type_name : 'Unknown Type';

            $inquiry = InquirySales::findOrFail($material->id_inquiry);
            $region = $inquiry->region ?? 'Unknown Region';
            $monthYear = Carbon::parse($inquiry->created_at)->translatedFormat('F Y');

            // Force delete (hapus dari DB permanen)
            $material->forceDelete();

            TrxDboProgPurchase::create([
                'inquiry_id' => $inquiry->id,
                'user_id' => auth()->id(),
                'description' => "Material dengan tipe '{$typeName}' untuk region '{$region}' bulan {$monthYear} dihapus secara permanen oleh {$userName}.",
            ]);

            return Response::json(['success' => true, 'message' => 'Material permanently deleted']);
        } catch (\Exception $e) {
            \Log::error('Delete Permanen Error: ' . $e->getMessage());
            return Response::json(['success' => false, 'message' => 'Failed to permanently delete material'], 500);
        }
    }


    public function deleteInquiryDetail($id)
    {
        try {
            $material = DetailInquiry::find($id); // Ganti dengan model yang sesuai
            if (!$material) {
                return Response::json(['success' => false, 'message' => 'Material not found'], 404);
            }

            $material->delete();
            return Response::json(['success' => true, 'message' => 'Material deleted successfully']);
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'message' => 'Failed to delete material'], 500);
        }
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

        // Ketika Finished by Procurement
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => auth()->id(),
            'description' => 'Finished Inquiry by Procurement.'
        ]);

        return response()->json(['success' => 'Inquiry marked as finished.']);
    }

    public function finishInquiryimport(Request $request)
    {
        $ids = $request->input('ids');
        $klasifikasi = $request->input('klasifikasi');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['error' => 'No inquiry IDs provided.'], 400);
        }

        $userId = auth()->id();
        $userName = auth()->user()->name;

        foreach ($ids as $id) {
            $hasValidDetail = DetailInquiryImport::where('id_inquiry', $id)
                ->where('klasifikasi', $klasifikasi)
                ->exists();

            if (!$hasValidDetail) {
                continue; // Skip if no valid detail found
            }

            $inquiry = InquirySales::find($id);

            if(!$inquiry || $inquiry->status != 9) {
                continue; // Skip if inquiry not found
            }

            
            $inquiry->status = 6; // Finished
            $inquiry->save();

                // Format bulan dan tahun dibuat
            $createdMonth = Carbon::parse($inquiry->created_at)->format('F');
            $createdYear = Carbon::parse($inquiry->created_at)->format('Y');

                // Insert progress ke trx
            TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => $userId,
            'description' => 'Inquiry Region [ ' . $inquiry->region . ' ] bulan ' . $createdMonth . ' ' . $createdYear . ' diselesaikan oleh ' . $userName
            ]);
            
        }

        return response()->json(['success' => 'Inquiries marked as finished.']);
    }

    public function exportInquiries(Request $request)
    {
        // Ambil ID dari query string dan pastikan formatnya benar
        $idString = $request->query('id');
        $ids = !empty($idString) ? explode(',', $idString) : [];

        // Log raw dan processed IDs untuk debugging
        \Log::info('Raw ID string received: ' . $idString);
        \Log::info('IDs received for export:', $ids);

        // Pastikan ID tidak kosong
        if (empty($ids)) {
            return response()->json(['message' => 'No IDs provided'], 400);
        }

        $currentDateExp = now()->format('d-m-Y');

        // Ekspor data
        return Excel::download(new InquiryImportInventoryExport($ids), 'IMP_INV_Export_' . $currentDateExp . '.xlsx');
    }


    public function exportInquiry()
    {
        return Excel::download(new InquirySalesExport, 'inquiry_sales.xlsx');
    }

    public function showProgressHistory($id)
    {
        $inquiry = InquirySales::findOrFail($id);
        $progressUpdates = TrxDboProgPurchase::where('inquiry_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['inquiry' => $inquiry, 'progressUpdates' => $progressUpdates]);
    }

    public function generatePDF($id)
    {
        // Ambil data inquiry berdasarkan ID
        $inquiry = InquirySales::with(['details.type_materials', 'kasie', 'kadept', 'inventory', 'purchasing'])->findOrFail($id);
        $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();

        // Ambil nama pengguna yang melakukan submit
        $submittedBy = $inquiry->create_by;

        $latestInquiry = null;

        // Ambil nama dan status approval dengan logika if-else
        $signatures = [
            'submitted' => $submittedBy,
            'approved_kasie' => $inquiry->kasie ? $inquiry->kasie->name : 'Waiting Approval',
            'approved_kasie_date' => $inquiry->kasie ? ($inquiry->kasie->approval_date ?: null) : null,
            'approved_kadept' => $inquiry->kadept ? $inquiry->kadept->name : 'Waiting Approval',
            'approved_kadept_date' => $inquiry->kadept ? ($inquiry->kadept->approval_date ?: null) : null,
            'approved_inventory' => $inquiry->inventory ? $inquiry->inventory->name : 'Waiting Approval',
            'approved_inventory_date' => $inquiry->inventory ? ($inquiry->inventory->approval_date ?: null) : null,
            'confirmed_purchasing' => $inquiry->purchasing ? $inquiry->purchasing->name : 'Waiting Approval',
            'confirmed_purchasing_date' => $inquiry->purchasing ? ($inquiry->purchasing->approval_date ?: null) : null,
        ];

        // Konversi ke PDF dengan orientasi landscape
        $pdf = PDF::loadView('pdf.inquiry', compact('inquiry', 'materials', 'signatures', 'latestInquiry'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('ADSI_FormInquiry.pdf');
    }

    public function generatePDFimport($id)
    {
        // Ambil data inquiry berdasarkan ID
        $inquiry = InquirySales::with(['details.type_materials', 'kasie', 'kadept', 'inventory', 'purchasing'])->findOrFail($id);
        $materials = DetailInquiryImport::where('id_inquiry', $inquiry->id)->with('type_materials')->get();
        $customers = Customer::all();
        $users = User::all();

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
        $pdf = PDF::loadView('pdf.inquiry', compact('inquiry', 'materials', 'signatures', 'customers', 'users'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('ADSI_FormInquiry.pdf');
    }

    public function generatePDFimportMulti($month, $klasifikasi)
    {
        try {
            $carbon = Carbon::createFromFormat('Y-m', $month);

            $inquiries = InquirySales::with(['kasie', 'kadept', 'inventory', 'purchasing'])
                ->whereYear('created_at', $carbon->year)
                ->whereMonth('created_at', $carbon->month)
                ->whereNotNull('inventory_id')
                ->get();

            if ($inquiries->isEmpty()) {
                return back()->with('error', 'Tidak ada data inquiry untuk bulan tersebut.');
            }

            $latestInquiry = $inquiries->sortByDesc('created_at')->first();

            $signaturesList = [];
            if ($latestInquiry) {
                $signaturesList[$latestInquiry->id] = [
                    'approved_inventory' => $latestInquiry->inventory->name ?? 'Waiting Approval',
                    'confirmed_purchasing' => $latestInquiry->purchasing->name ?? 'Waiting Approval',
                ];
            }

            $inquiryIds = $inquiries->pluck('id');

            $materials = DetailInquiryImport::whereIn('id_inquiry', $inquiryIds)
                ->where('klasifikasi', $klasifikasi)
                ->with('type_materials', 'inquirySales1')
                ->get();

            if ($materials->isEmpty()) {
                return back()->with('error', 'Tidak ada detail inquiry dengan klasifikasi tersebut.');
            }

            $customers = Customer::all();
            $users = User::all();

            $pdf = PDF::loadView('pdf.inquiry', [
                'inquiries' => $inquiries,
                'materials' => $materials,
                'signaturesList' => $signaturesList,
                'customers' => $customers,
                'latestInquiry' => $latestInquiry,
                'users' => $users
            ])->setPaper('a4', 'landscape');

            return $pdf->download("Inquiry_Import_{$month}_{$klasifikasi}.pdf");
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'id_inquiry' => 'required|exists:inquiry_sales,id', // Pastikan ID inquiry valid
            'attachments.*' => 'file|mimes:pdf,png,jpg,jpeg|max:10048', // Validasi file
        ]);

        // Simpan file yang di-upload
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // Ambil nama asli file
                $filename = $file->getClientOriginalName();
                // Pindahkan file ke folder public/assets/inquiry
                $file->move(public_path('assets/inquiry'), $filename);

                // Cek apakah detail_inquiry dengan id_inquiry sudah ada
                $detail = DetailInquiry::where('id_inquiry', $request->id_inquiry)->first();

                if ($detail) {
                    // Jika sudah ada, tambahkan nama file ke kolom `file`
                    $currentFiles = $detail->file ? json_decode($detail->file) : []; // Mengambil file yang sudah ada
                    $currentFiles[] = $filename; // Tambahkan file baru

                    $detail->file = json_encode($currentFiles); // Simpan kembali ke kolom file
                    $detail->save();
                } else {
                    // Jika tidak ada, buat baris baru
                    DetailInquiry::create([
                        'id_inquiry' => $request->id_inquiry,
                        'file' => json_encode([$filename]), // Simpan sebagai array
                    ]);
                }
            }
        }

        // Ambil semua file yang terkait dengan id_inquiry
        $allFiles = DetailInquiry::where('id_inquiry', $request->id_inquiry)
            ->pluck('file')
            ->flatMap(function ($file) {
                return json_decode($file);
            })
            ->toArray();

        return response()->json(['message' => 'Files uploaded successfully', 'uploadedFiles' => $allFiles]);
    }

    public function show($id)
    {
        // Ambil inquiry dan materials
        $inquiry = InquirySales::findOrFail($id);
        $materials = DetailInquiry::where('id_inquiry', $inquiry->id)->with('type_materials')->get();

        // Ambil semua file yang di-upload terkait dengan id_inquiry
        $uploadedFiles = DetailInquiry::where('id_inquiry', $id)
            ->pluck('file')
            ->flatMap(function ($file) {
                return json_decode($file); // Mengonversi JSON ke array
            })->toArray();

        return view('showFormSS', compact('inquiry', 'materials', 'uploadedFiles'));
    }

    public function overviewInquiry()
    {

        // Ambil semua inquiry dengan status relevan
        $draftInquiries = InquirySales::with('customer')
            ->whereIn('status', [1, 2, 3, 4, 5, 6, 8, 9]) // Draft for Finish Process
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('inquiry.overviewInquiry', compact('draftInquiries'));
    }

    public function showApprovalPurchaseImport()
    {
        // Ambil semua inquiry dengan status Approve Ka.Dept (8, 9, 6) dan yang aktif serta import
        $inquiries = InquirySales::with(['customer', 'detailinquiryimport'])
            ->whereIn('status', [8, 9, 6])
            ->where('is_active', 1)
            ->where('loc_imp', 'Import')
            ->latest()
            ->get();

        // Group berdasarkan bulan dari created_at
        $groupedByMonth = $inquiries->groupBy(function ($inquiry) {
            return $inquiry->created_at->format('Y-m'); // Format sebagai "2025-04"
        });

        // Ambil satu inquiry terbaru per bulan untuk Daido
        $Daido = $groupedByMonth->map(function ($group) {
            // Urutkan dari yang paling baru
            $sortedGroup = $group->sortByDesc('created_at');

            return $sortedGroup->first(function ($inquiry) {
                return $inquiry->detailinquiryimport->contains(function ($detail) {
                    return $detail->klasifikasi === 'Daido';
                });
            });
        })->filter(); // Buang null values jika tidak ada inquiry Daido di bulan tersebut

        // Ambil satu inquiry per bulan untuk NonDaido
        $NonDaido = $groupedByMonth->map(function ($group) {
            return $group->first(function ($inquiry) {
                return $inquiry->detailinquiryimport->contains(function ($detail) {
                    return $detail->klasifikasi === 'NonDaido';
                });
            });
        })->filter(); // Buang null values jika tidak ada inquiry NonDaido di bulan tersebut

        return view('inquiry.overviewPurchaseImport', [
            'inquiries' => $inquiries,
            'Daido' => $Daido,
            'NonDaido' => $NonDaido,
        ]);
    }

    public function showFormSSimportpurchase($month, $klasifikasi)
    {
        // Parsing format bulan (pastikan formatnya valid: YYYY-MM atau sejenis)
        try {
            $carbonMonth = Carbon::parse($month);
        } catch (\Exception $e) {
            abort(400, 'Format bulan tidak valid');
        }

        $inquiries = InquirySales::with([
            'customer',
            'detailinquiryimport' => function ($query) use ($klasifikasi) {
                $query->where('klasifikasi', $klasifikasi);
            }
        ])
            ->whereYear('created_at', $carbonMonth->year)
            ->whereMonth('created_at', $carbonMonth->month)
            ->where('is_active', 1)
            ->where('loc_imp', 'Import')
            ->whereIn('status', ['8', '9', '6'])
            ->whereHas('detailinquiryimport', function ($query) use ($klasifikasi) {
                $query->where('klasifikasi', $klasifikasi);
            })
            ->get();

        // Fetch all detail inquiries based on id_inquiry from the main inquiry
        if ($klasifikasi) {
            $materials = DetailInquiryImport::whereIn('id_inquiry', $inquiries->pluck('id'))
                ->where('klasifikasi', $klasifikasi)
                ->with('type_materials')
                ->get();
        } else {
            $materials = DetailInquiryImport::whereIn('id_inquiry', $inquiries->pluck('id'))
                ->with('type_materials')
                ->get();
        }


        $inquiry = $inquiries->sortByDesc('created_at')->first();

        $customers = Customer::all();
        $users = User::all();

        // Ambil semua nama file yang ter-upload
        $uploadedFiles = DetailInquiryImport::whereIn('id_inquiry', $inquiries->pluck('id'))
            ->pluck('file')
            ->flatMap(function ($file) {
                return json_decode($file) ?? [];
            })
            ->toArray();

        // Progress updates
        $progressUpdates = TrxDboProgPurchase::whereIn('inquiry_id', $inquiries->pluck('id'))
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();


        $isFromApproval = request()->query('source') === 'approval';

        return view('inquiry.showformSSimportpurchase', compact('inquiries', 'isFromApproval', 'uploadedFiles', 'progressUpdates', 'customers', 'users', 'inquiry', 'materials', 'month', 'klasifikasi'));
    }


    public function createInquirySalesImport()
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

        return view('inquiry.createImport', compact('inquiries', 'customers'));
    }
}
