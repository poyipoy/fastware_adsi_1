<?php

namespace App\Http\Controllers;

use App\Exports\CrpDetailExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Customer;
use App\Models\MstDboCrp;
use App\Models\TrsDboCrp;
use App\Models\DetailDboCrp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Exports\MstDboCrpActualExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;

class CrpController extends Controller
{

    public function index()
    {
        $userName = Auth::user()->name;

        $mstDboCrps = MstDboCrp::where('partner_user', Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $mstIds = $mstDboCrps->pluck('id')->toArray();

        $trsDboCrps = TrsDboCrp::with('partners')
            ->whereIn('mst_id', $mstIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $users = User::all(); // Tambahkan baris ini

        return view('crp.crp', compact('mstDboCrps', 'trsDboCrps', 'userName', 'users'));
    }


    public function create()
    {
        // Tampilkan form untuk menambah data
        return view('crp.create'); // Buat view ini
    }

    // public function store(Request $request)
    // {
    //     // Validasi data yang diterima
    //     $validatedData = $request->validate([
    //         'plan_values' => 'required|array',
    //         'actual_values' => 'required|array',
    //         'plan_ytd' => 'required|numeric',
    //         'actual_ytd' => 'required|numeric',
    //     ]);

    //     // Simpan data summary
    //     foreach ($validatedData['summary'] as $summary) {
    //         $mstCrp = MstDboCrp::create([
    //             'nm_category' => $summary['nm_category'],
    //             'month_1' => $summary['plan_values'][0],
    //             'month_2' => $summary['plan_values'][1],
    //             'month_3' => $summary['plan_values'][2],
    //             'month_4' => $summary['plan_values'][3],
    //             'month_5' => $summary['plan_values'][4],
    //             'month_6' => $summary['plan_values'][5],
    //             'month_7' => $summary['plan_values'][6],
    //             'month_8' => $summary['plan_values'][7],
    //             'month_9' => $summary['plan_values'][8],
    //             'month_10' => $summary['plan_values'][9],
    //             'month_11' => $summary['plan_values'][10],
    //             'month_12' => $summary['plan_values'][11],
    //             'grand_tot' => $summary['ytd'], // Total YTD yang dihitung
    //             'plan_actual' => 'Plan', // Sesuaikan jika perlu
    //         ]);

    //         // Simpan data detail yang berkaitan dengan summary
    //         foreach ($validatedData['details'] as $detail) {
    //             TrsDboCrp::create([
    //                 'mst_id' => $mstCrp->id, // Menghubungkan ke summary yang baru dibuat
    //                 'nm_category' => $detail['category'],
    //                 'detail_activity' => $detail['detail_activity'],
    //                 'no_po' => $detail['no_PO'],
    //                 'date' => $detail['date'],
    //                 'qty' => $detail['qty'],
    //                 // Tambahkan data harga jika diperlukan
    //                 'price_before' => $detail['price_before'] ?? null, // tambahkan jika ada
    //                 'price_after' => $detail['price_after'] ?? null, // tambahkan jika ada
    //                 'price_sell' => $detail['price_sell'] ?? null, // tambahkan jika ada
    //                 'total_cost_before' => $detail['total_cost_before'] ?? null, // tambahkan jika ada
    //                 'total_cost_after' => $detail['total_cost_after'] ?? null, // tambahkan jika ada
    //                 'total_cost_crp' => $detail['total_cost_crp'] ?? null, // tambahkan jika ada
    //             ]);
    //         }
    //         return response()->json(['success' => true]);
    //     }
    // }



    public function store(Request $request)
{
    // Validasi input
    $validated = $request->validate([
        'summaryData' => 'required|array',
    ]);
    
    // Mengambil summaryData dari request
    $summaryData = $request->input('summaryData');

    // Loop untuk setiap kategori dalam summaryData
    foreach ($summaryData as $nm_category => $entry) {
        // Pastikan bahwa data plan_values dan actual_values ada
        if (!isset($entry['plan_values']) || !isset($entry['actual_values']) || !is_array($entry['plan_values']) || !is_array($entry['actual_values'])) {
            continue; // Lewati jika tidak valid
        }

        // Data yang akan disimpan untuk plan
        $commonPlanData = [
            'month_1'     => $entry['plan_values'][0] ?? 0,
            'month_2'     => $entry['plan_values'][1] ?? 0,
            'month_3'     => $entry['plan_values'][2] ?? 0,
            'month_4'     => $entry['plan_values'][3] ?? 0,
            'month_5'     => $entry['plan_values'][4] ?? 0,
            'month_6'     => $entry['plan_values'][5] ?? 0,
            'month_7'     => $entry['plan_values'][6] ?? 0,
            'month_8'     => $entry['plan_values'][7] ?? 0,
            'month_9'     => $entry['plan_values'][8] ?? 0,
            'month_10'    => $entry['plan_values'][9] ?? 0,
            'month_11'    => $entry['plan_values'][10] ?? 0,
            'month_12'    => $entry['plan_values'][11] ?? 0,
            'grand_tot'   => $entry['plan_ytd'] ?? 0,
            'partner_user'=> Auth::id(),
        ];

        // Update or Create untuk Plan
        MstDboCrp::updateOrCreate(
            [
                'nm_category'  => $nm_category,
                'plan_actual'  => 'Plan',
                'partner_user' => Auth::id(),
            ],
            $commonPlanData
        );

        // Data yang akan disimpan untuk Actual
        $commonActualData = [
            'month_1'     => $entry['actual_values'][0] ?? 0,
            'month_2'     => $entry['actual_values'][1] ?? 0,
            'month_3'     => $entry['actual_values'][2] ?? 0,
            'month_4'     => $entry['actual_values'][3] ?? 0,
            'month_5'     => $entry['actual_values'][4] ?? 0,
            'month_6'     => $entry['actual_values'][5] ?? 0,
            'month_7'     => $entry['actual_values'][6] ?? 0,
            'month_8'     => $entry['actual_values'][7] ?? 0,
            'month_9'     => $entry['actual_values'][8] ?? 0,
            'month_10'    => $entry['actual_values'][9] ?? 0,
            'month_11'    => $entry['actual_values'][10] ?? 0,
            'month_12'    => $entry['actual_values'][11] ?? 0,
            'grand_tot'   => $entry['actual_ytd'] ?? 0,
            'partner_user'=> Auth::id(),
        ];

        // Update or Create untuk Actual
        MstDboCrp::updateOrCreate(
            [
                'nm_category'  => $nm_category,
                'plan_actual'  => 'Actual',
                'partner_user' => Auth::id(),
            ],
            $commonActualData
        );
    }

    return response()->json([
        'success' => true,
        'message' => 'Data berhasil disimpan.'
    ]);
}

    public function exportMstActual()
    {
        $date = date('Y-m-d');
        $filename = "MstDboCrp_Actual_{$date}.xlsx";
        return Excel::download(new MstDboCrpActualExport, $filename);
    }

    public function exportdetailcrp()
    {
        $date = date('Y-m-d');
        $filename = "DetailCRP_{$date}.xlsx";
        return Excel::download(new CrpDetailExport, $filename);
    }


    public function saveDetail(Request $request)
    {

        try {
            $rows = $request->input('rows');
            $userId = Auth::id();

            if (empty($rows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data provided to save.'
                ], 400);
            }

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                if (empty($row['actual_category'])) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Category is required for row " . ($index + 1)
                    ], 400);
                }

                $mstDboCrp = null;
                $oldTotalCostCrp = 0;
                $oldMstId = null;

                // Handle existing TrsDboCrp updates
                if (!empty($row['id'])) {
                    $existingTrs = TrsDboCrp::find($row['id']);
                    if (!$existingTrs) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "TrsDboCrp record not found for row " . ($index + 1)
                        ], 404);
                    }

                    $oldTotalCostCrp = $existingTrs->total_cost_crp;
                    $oldMstId = $existingTrs->mst_id;

                    // Check if category changed
                    if ($existingTrs->nm_category != $row['actual_category']) {
                        // Decrement old Mst
                        $oldMst = MstDboCrp::find($oldMstId);
                        if ($oldMst) {
                            $oldDate = $existingTrs->date ? Carbon::parse($existingTrs->date) : null;
                            if ($oldDate) {
                                $oldMonth = $oldDate->format('n');
                                $oldMonthColumn = 'month_' . $oldMonth;
                                $oldMst->decrement($oldMonthColumn, $oldTotalCostCrp);
                                $oldMst->decrement('grand_tot', $oldTotalCostCrp);
                            }
                        }

                        // Find new Mst based on new category
                        $mstDboCrp = MstDboCrp::where('partner_user', $userId)
                            ->where('nm_category', $row['actual_category'])
                            ->where('plan_actual', 'Actual')
                            ->first();
                    } else {
                        // Use existing Mst
                        $mstDboCrp = MstDboCrp::find($oldMstId);
                    }
                } else {
                    // New entry: find Mst by category and user
                    $mstDboCrp = MstDboCrp::where('partner_user', $userId)
                        ->where('nm_category', $row['actual_category'])
                        ->where('plan_actual', 'Actual')
                        ->first();
                }

                if (!$mstDboCrp) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Category not found for row " . ($index + 1)
                    ], 400);
                }

                // Validate date and month
                $date = $row['date'] ?? null;
                $month = $date ? Carbon::parse($date)->format('n') : null;
                if (!$month || $month < 1 || $month > 12) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid date/month for row " . ($index + 1)
                    ], 400);
                }

                $monthColumn = 'month_' . $month;
                $newTotalCostCrp = $row['total_cost_crp'] ?? 0;
                $difference = $newTotalCostCrp - $oldTotalCostCrp;

                // Update MstDboCrp
                if (!empty($row['id']) && $mstDboCrp->id == $oldMstId) {
                    // Adjust existing Mst by difference
                    $mstDboCrp->increment($monthColumn, $difference);
                    $mstDboCrp->increment('grand_tot', $difference);
                } else {
                    // New entry or changed category: increment new Mst
                    $mstDboCrp->increment($monthColumn, $newTotalCostCrp);
                    $mstDboCrp->increment('grand_tot', $newTotalCostCrp);
                }

                // Update or create TrsDboCrp
                $trsData = [
                    'mst_id' => $mstDboCrp->id,
                    'nm_category' => $row['actual_category'],
                    'detail_activity' => $row['detail_activity'] ?? null,
                    'no_po' => $row['no_po'] ?? null,
                    'date' => $date,
                    'qty' => $row['qty'] ?? 0,
                    'price_before' => $row['price_before'] ?? 0,
                    'price_after' => $row['price_after'] ?? 0,
                    'price_sell' => $row['selisih'] ?? 0,
                    'total_cost_before' => $row['total_cost_before'] ?? 0,
                    'total_cost_after' => $row['total_cost_after'] ?? 0,
                    'total_cost_crp' => $newTotalCostCrp,
                    'partner' => $userId,
                ];

                if (!empty($row['id'])) {
                    $existingTrs->update($trsData);
                } else {
                    TrsDboCrp::create($trsData);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data saved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }



    public function edit($id)
    {
        $mstDboCrp = MstDboCrp::findOrFail($id);
        return view('crp.edit', compact('mstDboCrp')); // Buat view ini
    }

    public function update(Request $request, $id)
    {
        // Validasi request
        $request->validate([
            'category' => 'required|string|max:255',
            'plan_actual' => 'required|string|max:10',
            // Tambahkan validasi untuk bulan-bulan lainnya
        ]);

        // Update data di database
        $mstDboCrp = MstDboCrp::findOrFail($id);
        $mstDboCrp->update($request->all());

        return redirect()->route('crp')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'string',
        ]);

        $userId = Auth::user()->id;
        $categories = $request->categories;

        // Hapus record Plan dan Actual untuk kategori yang diberikan
        $deleted = MstDboCrp::where('partner_user', $userId)
            ->whereIn('nm_category', $categories)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Tidak ada data yang dihapus.'
            ], 400);
        }
    }

    public function delete(Request $request)
    {
        $ids = $request->input('ids'); // array berisi id plan dan actual
        if (!$ids || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'ID tidak valid.']);
        }
    
        // Soft delete
        MstDboCrp::whereIn('id', $ids)->update(['deleted_at' => now()]);
    
        return response()->json(['success' => true]);
    }

    public function deleteDetail(Request $request)
    {
        $ids = $request->input('ids'); // array berisi id detail
        if (!$ids || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'ID tidak valid.']);
        }

        DB::beginTransaction();
        try {
            $details = TrsDboCrp::whereIn('id', $ids)->get();

            foreach ($details as $detail) {
                if (!$detail->date || !$detail->total_cost_crp || !$detail->mst_id) {
                    continue; // skip if any key data is missing
                }

                $month = Carbon::parse($detail->date)->format('n');
                $monthColumn = 'month_' . $month;

                $mst = MstDboCrp::find($detail->mst_id);
                if ($mst && Schema::hasColumn('mst_dbo_crp', $monthColumn)) {
                    $mst->decrement($monthColumn, $detail->total_cost_crp);
                    $mst->decrement('grand_tot', $detail->total_cost_crp);
                }
            }

            // Soft delete
            TrsDboCrp::whereIn('id', $ids)->update(['deleted_at' => now()]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus dan YTD diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus: ' . $e->getMessage()
            ], 500);
        }
    }

     public function showDetailModal($crpId)
    {
        // Ambil semua detail berdasarkan crpId
        $details = DetailDboCrp::where('crp_id', $crpId)->get();

        // Pastikan data dikirim dalam format JSON
        return response()->json($details);
    }

    // Fungsi untuk menyimpan data CRP ke database
    public function saveCrpDetails(Request $request)
    {
        $crpId = $request->input('crp_id');

        for ($month = 1; $month <= 12; $month++) {
            $detail = DetailDboCrp::where('crp_id', $crpId)->where('month', $month)->first();

            if (!$detail) {
                DetailDboCrp::create([
                    'crp_id' => $crpId,
                    'month' => $month,
                    'pi' => $request->input("pi_$month"),
                    'ca' => $request->input("ca_$month"),
                    'due_date' => $request->input("due_date_$month"),
                    'remark' => $request->input("remark_$month"),
                    'check_crp' => $request->has("check_crp_$month")
                ]);
            } else {
                $detail->update([
                    'pi' => $request->input("pi_$month"),
                    'ca' => $request->input("ca_$month"),
                    'due_date' => $request->input("due_date_$month"),
                    'remark' => $request->input("remark_$month"),
                    'check_crp' => $request->has("check_crp_$month")
                ]);
            }
        }

        return redirect()->back()->with('message', 'Data berhasil disimpan');
    }

    public function importCrpDetail(Request $request)
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

            $detailUpdates = [];
            $logs = [];

            foreach ($rows as $index => $row) {
                if ($index < 1 || empty(array_filter($row)) || count($row) < 13) {
                    continue;
                }

                $CrpId = $row[13] ?? null;
                $detailId = $row[12] ?? null;

                $oldCrp = DB::table('mst_dbo_crp')->where('id', $CrpId)->first();
                $oldDetail = DB::table('trs_dbo_crp')->where('id', $detailId)->first();

                $newDetailData = [
                    'mst_id' => $CrpId,
                    'id' => $detailId,
                    'nm_category' => $row[1] ?? null,
                    'detail_activity' => $row[2] ?? null,
                    'no_po' => $row[3] ?? null,
                    'date' => $row[4] ?? null,
                    'qty' => $row[5] ?? null,
                    'price_before' => $row[6] ?? null,
                    'price_after' => $row[7] ?? null,
                    'price_sell' => $row[8] ?? null,
                    'total_cost_before' => $row[9] ?? null,
                    'total_cost_after' => $row[10] ?? null,
                    'total_cost_crp' => $row[11] ?? null
                ];

                $detailUpdates[] = $newDetailData;
            }

            if (!empty($detailUpdates)) {
                DB::table('trs_dbo_crp')->upsert($detailUpdates, ['id'], array_keys($detailUpdates[0]));
            }

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil',
                'redirect' => route('crp')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function dashboardcrp()
    {
        $userName = Auth::user()->name;

        // Mengambil data dari model MstDboCrp
        $mstDboCrps = MstDboCrp::where('partner_user', Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Mengambil data dari model TrsDboCrp berdasarkan mst_id
        $mstIds = $mstDboCrps->pluck('id')->toArray();
        $trsDboCrps = TrsDboCrp::whereIn('mst_id', $mstIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('crp.dashboardcrp', compact('mstDboCrps', 'trsDboCrps', 'userName'));
    }

    

}
