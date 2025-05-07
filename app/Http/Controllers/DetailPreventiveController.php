<?php

namespace App\Http\Controllers;

use App\Models\DetailPreventive;
use App\Models\Preventive;
use App\Models\Mesin;
use Egulias\EmailValidator\Result\Reason\DetailedReason;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DetailPreventiveController extends Controller
{
    public function updateIssue(Request $request, Mesin $mesin)
    {
        // Ambil semua nilai issue dan perbaikan dari request
        $issues = $request->input('issue');
        $checkedIssues = $request->input('checked') ?? [];

        // Loop melalui setiap issue dari request
        foreach ($issues as $key => $issue) {
            // Cek apakah issue saat ini sudah diceklis atau tidak
            $isChecked = in_array($key, $checkedIssues);

            // Cari jika ada detail preventive sebelumnya dengan issue yang sama
            $existingDetailPreventive = DetailPreventive::where('id_mesin', $mesin->id)
                ->where('issue', $issue)
                ->first();

            if ($existingDetailPreventive) {
                // Jika sudah ada, update status checklist
                $existingDetailPreventive->update([
                    'issue_checked' => $isChecked ? '1' : '0'
                ]);
            } else {
                // Jika belum ada, buat detail preventive baru
                DetailPreventive::create([
                    'id_mesin' => $mesin->id,
                    'issue' => $issue,
                    'issue_checked' => $isChecked ? '1' : '0'
                ]);
            }
        }
        // Redirect atau response sesuai kebutuhan Anda
        return redirect()->route('maintenance.dashpreventive');
    }


    public function updatePerbaikan(Request $request, Mesin $mesin, DetailPreventive $detailPreventive)
    {
        $perbaikans = $request->input('perbaikan');
        $checkedPerbaikans = $request->input('checked') ?? [];

        // Hapus semua detail preventive yang terkait dengan mesin yang diberikan
        $mesin->detailPreventives()->delete();

        foreach ($perbaikans as $key => $perbaikan) {
            // Buat detail preventive baru
            $detailPreventive->create([
                'id_mesin' => $mesin->id,
                'perbaikan' => $perbaikan,
                'perbaikan_checked' => (in_array($key, $checkedPerbaikans) ? '1' : '0')
            ]);
        }
        return redirect()->route('maintenance.dashpreventive');
    }

    public function destroy(DetailPreventive $detailPreventive)
    {
        //
    }
}
