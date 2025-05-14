<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomDboReq;
use Illuminate\Auth\Events\Validated;
use App\Models\Customer;
use App\Models\User;

class CustomRequestController extends Controller
{
    public function showCstmReq()
    {
        $materials = CustomDboReq::with(['customers', 'finance', 'marketing'])->get();
        $customers = Customer::all();
        $users = User::all();

        return view('custom_req.showCstmReq', compact('materials', 'customers', 'users'));
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

        $sales = auth()->user()->id;

        CustomDboReq::create([
            'sales' => $sales,
            'customer' => $request->customer,
            'ket_drawing' => $request->ket_drawing,
            'progress' => $request->progress,
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

    public function updateMarketing(Request $request, $id)
    {
        $material = CustomDboReq::findOrFail($id);

        $material->ref_so = $request->ref_so;
        $material->save();
        
        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    public function updateCstmReq(Request $request, $id)
    {
        $material = CustomDboReq::findOrFail($id);
        $userId = auth()->user()->id;



        if (in_array($userId, [1])) {
            $material->customer = $request->customer;
            $material->ket_drawing = $request->ket_drawing;
            $material->remark = $request->remark;
            $material->nama_project = $request->nama_project;
            $material->tgl_update = $request->tgl_update;
            $material->harga_awal = $request->harga_awal;
            $material->harga_akhir = $request->harga_akhir;
            $material->progress = $request->progress;
        }
        // Logika update berdasarkan user ID
        if (in_array($userId, [ 2, 5])) {
            $material->customer = $request->customer;
            $material->ket_drawing = $request->ket_drawing;
            $material->remark = $request->remark;
        }

        if (in_array($userId, [3, 4, 5])) {
            $material->nama_project = $request->nama_project;
            $material->tgl_update = $request->tgl_update;
            $material->harga_awal = $request->harga_awal;
            $material->harga_akhir = $request->harga_akhir;
            $material->progress = $request->progress;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time().'_'.$file->getClientOriginalName();
                $file->storeAs('attachments', $filename, 'public');
                $material->attachment = $filename;
            }
        }

        

        $material->save();

        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }



}
