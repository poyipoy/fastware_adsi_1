<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomDboReq;
use Illuminate\Auth\Events\Validated;
use App\Models\Customer;

class CustomRequestController extends Controller
{
    public function showCstmReq()
    {
        $materials = CustomDboReq::with(['customers'])->get();
        $customers = Customer::all();

        return view('custom_req.showCstmReq', compact('materials', 'customers'));
    }

    public function showApprovalMarketing()
    {
        $materials = CustomDboReq::with(['customers'])
            ->where('status', 1)
            ->get();
        $customers = Customer::all();

        return view('custom_req.showApprovalMarketing', compact('materials', 'customers'));
    }

    public function approveMarketing($id)
    {
        $materials = CustomDboReq::findOrFail($id);

        $materials->status = 4;
        $materials->marketing_id = auth()->user()->id;
        $materials->approved_marketing = now();
        $materials->save();

        return redirect()->back()->with('success', 'Material berhasil disetujui.');
        
    }

    public function showApprovalFinance()
    {
        $materials = CustomDboReq::with(['customers'])
            ->where('status', 4)
            ->get();
        
        $customers = Customer::all();

        return view('custom_req.showApprovalFinance', compact('materials', 'customers'));
    }

    public function approveFinance($id)
    {
        $materials = CustomDboReq::findOrFail($id);

        $materials->status = 9;
        $materials->finance_id = auth()->user()->id;
        $materials->approved_finance = now();
        $materials->save();
        
    }

    public function createCstmReq(Request $request)
    {
        $request->validate([
            'sales' => 'required',
            'customer' => 'required',
        ]);
        
        // $request->validate([
        //     'sales' => 'required',
        //     'pt' => 'required',
        //     'ket_drawing' => 'required',
        //     'nama_project' => 'required',
        //     'progress' => 'required',
        //     'tgl_update' => 'required',
        //     'ref_so' => 'required',
        //     'remark' => 'requie|string|max:255',
        // ]);

        // // Debugging: Tampilkan semua data yang dikirim dari form
        // dd($request->all());

        CustomDboReq::create([
            'sales' => $request->sales,
            'customer' => $request->customer,
            'ket_drawing' => $request->ket_drawing,
            'nama_project' => $request->nama_project,
            'progress' => $request->progress,
            'tgl_update' => $request->tgl_update,
            'ref_so' => $request->so,
            'remark' => $request->remark,
            'tgl_permintaan' => now(),
            'status' => 1,
        ]);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }

    public function deleteCstmReq($id)
    {
        $material = CustomDboReq::findOrFail($id);
        $material->delete();

        return redirect()->back()->with('success', 'Material berhasil dihapus.');
    }

    public function updateCstmReq(Request $request, $id)
{
    // Validasi data yang dikirimkan hanya required
    $validated = $request->validate([
        'sales' => 'required',
        'customer' => 'required',  // Cek apakah customer ada, tanpa mengecek validitas ID di database
        'ket_drawing' => 'required',
        'nama_project' => 'required',
        'progress' => 'required',
        'tgl_update' => 'required',
        'so' => 'required', // Hanya memastikan SO ada
        'remark' => 'required', // Jika remark dibutuhkan
    ]);

    // Cari material berdasarkan ID
    $material = CustomDboReq::findOrFail($id);

    // Update data material
    $material->update([
        'sales' => $request->sales,
        'customer' => $request->customer,
        'ket_drawing' => $request->ket_drawing,
        'nama_project' => $request->nama_project,
        'progress' => $request->progress,
        'tgl_update' => $request->tgl_update,
        'ref_so' => $request->so,
        'remark' => $request->remark,
        'status' => 1, // Status default setelah update
    ]);

    // Redirect dengan pesan sukses
    return redirect()->back()->with('success', 'Material berhasil diperbarui.');
}


}
