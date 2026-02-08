<?php

namespace App\Http\Controllers\magang;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\TcJobPosition;
use App\Models\User;

class StrukturOrganisasiController extends Controller
{
    public function index()
    {
        // Ambil data mapping departemen dari dashboard TCPD
        $departmentDefinitions = $this->departmentDefinitions();

        // Hitung jumlah karyawan per departemen
        $employeeCounts = [];
        foreach ($departmentDefinitions as $department => $jobPositions) {
            $userIds = TcJobPosition::whereIn('job_position', $jobPositions)
                ->whereNotNull('id_user')
                ->distinct()
                ->pluck('id_user')
                ->toArray();

            $employeeCounts[$department] = count($userIds);
        }

        return view('magang.jumlahkaryawan', compact('employeeCounts'));
    }

    protected function departmentDefinitions(): array
    {
        return [
            'Finance & Accounting, HRGA' => [
                'HR & GA Section Head',
                'HR & Legal Staff',
                'HRGA & CSR Staff',
                'Finance & Accounting Sec. Head',
                'Finance Admin',
                'Finance & Treasury Sec. Head',
                'Invoicing Staff',
                'AR Staff',
                'Accounting Staff & Kasir',
            ],
            'PDCA, Procurement, Inventory & IT' => [
                'PDCA, Inventory, Procurement & IT Sec. Head',
                'PDCA & Procurement Non Material Staff',
                'Procurement Material Staff',
                'Procurement Administration',
                'Inventory Staff',
                'IT Staff',
            ],
            'Sales Region 1 & 2' => [
                'Sales Admin',
                'SOH Region 1',
                'Sales Engineer Reg 1',
                'SOH Region 2',
                'Sales Engineer Reg 2',
            ],
            'Sales Region 3 & 4' => [
                'SOH Region 3',
                'Sales Engineer Reg 3',
                'SOH Region 4',
                'Sales Engineer Reg 4',
            ],
            'Delivery Warehouse & PPC' => [
                'Logistic Admin',
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder',
                'Logistic Foreman',
                'PPIC Staff',
            ],
            'Production Heat Treatment' => [
                'Produksi HT Sec. Head',
                'Foreman CT',
                'Foreman QC',
                'Leader HT',
                'Operator CT',
                'Admin HT & PPC',
                'Operator MTN',
                'Operator HT',
                'Leader Cutting',
            ],
            'Production CT, MC & MC Custom' => [
                'Machining Custom Sec. Head',
                'Leader MC',
                'Operator Bubut',
                'Operator Mc. Custom',
                'MC Custom Staff',
                'Operator Machining',
                'Foreman Machining Custom',
                'Foreman MC',
            ],
        ];
    }
}
