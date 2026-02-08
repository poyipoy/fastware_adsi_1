<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LogbookVisits;
use App\Models\TrsLogbookVisits;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesVisitExport;
use Carbon\Carbon;

class SalesVisitController extends Controller
{
    /**
     * Tampilkan dashboard kunjungan sales
     */
    public function index()
    {
        // Periksa otorisasi
        if (!$this->isAuthorizedUser()) {
            abort(403, 'Unauthorized access');
        }

        return view('dashboard.dashboardSalesVisit');
    }

        private function isSalesRole($user): bool
    {
        return isset($user->role_id)
            && in_array((int)$user->role_id, [2, 3, 4, 44], true);
    }

    /**
     * Tampilkan halaman Laporan CRM di menu Sales
     */
    public function crmReport(Request $request)
    {
        if (!$this->isAuthorizedUser()) {
            abort(403, 'Unauthorized access');
        }

        // Sediakan daftar perusahaan untuk dropdown filter
        $companies = $this->getCompanyList();
        return view('sales.crm_report', compact('companies'));
    }

    /**
     * Ambil data dashboard via AJAX
     */
    public function getDashboardData(Request $request)
    {
        // Periksa otorisasi
        if (!$this->isAuthorizedUser()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = Auth::user();
        $isAdmin = $this->isAdmin($user);

        // Ambil filter tanggal (default: 7 hari terakhir)
        $endDate = $request->input('endDate', Carbon::now()->format('Y-m-d'));
        $startDate = $request->input('startDate', Carbon::now()->subDays(7)->format('Y-m-d'));
        
        $salesFilter = trim($request->input('salesFilter'));
        $regionFilter = trim($request->input('regionFilter'));
        $companyFilter = trim($request->input('companyFilter'));

        // Dapatkan tim sales berdasarkan peran user
        $salesUserIds = $this->getSalesUserIds($user, $isAdmin);

        // Query visits (difilter berdasarkan tanggal)
        $visitsQuery = LogbookVisits::with('user')
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds),fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($startDate, fn($q) => $q->whereDate('visit_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('visit_date', '<=', $endDate))
            ->when($salesFilter, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', $salesFilter)))
            ->when($regionFilter, function($q) use ($regionFilter) {
                $regionMappings = $this->getRegionMappings();
                $salesInRegion = $regionMappings[$regionFilter] ?? [];
                return $q->whereHas('user', fn($u) => $u->whereIn('name', $salesInRegion));
            })
            ->when($companyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$companyFilter}%"));

        $visits = $visitsQuery->get();

        // Query plans (difilter berdasarkan tanggal)
        $plansQuery = TrsLogbookVisits::with('user')
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds), fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($startDate, fn($q) => $q->whereDate('plan_visit', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('plan_visit', '<=', $endDate))
            ->when($salesFilter, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', $salesFilter)))
            ->when($regionFilter, function($q) use ($regionFilter) {
                $regionMappings = $this->getRegionMappings();
                $salesInRegion = $regionMappings[$regionFilter] ?? [];
                return $q->whereHas('user', fn($u) => $u->whereIn('name', $salesInRegion));
            })
            ->when($companyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$companyFilter}%"));

        $plans = $plansQuery->get();

        // Hitung ringkasan menggunakan data yang STRICT
        $strictComparison = $this->compareVisitsAndPlans($visits, $plans);
        $summary = $this->calculateSummary($strictComparison, $visits);

        // Ambil data chart (gunakan koleksi agar tidak melakukan query ulang)
        $chartData = $this->getChartData($user, $isAdmin, $startDate, $endDate, $salesFilter, $regionFilter, $companyFilter, $visits, $plans);

        // Ambil daftar perusahaan
        $companies = $this->getCompanyList();

        return response()->json([
            'summary' => $summary,
            'comparisonData' => [],
            'chartData' => $chartData,
            'companies' => $companies,
        ]);
    }

    /**
     * Ambil data DataTables (server-side)
     *
     * Pengatur ringan: parsing input lalu mendelegasikan logika yang berat ke helper kecil
     * untuk mengurangi kompleksitas tipe lokal dan meningkatkan autocomplete di IDE.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDetailData(Request $request): JsonResponse
    {
        // Parse request menjadi array params bertipe agar memudahkan analisis statis
        $params = $this->parseDataTableRequest($request);

        $user = Auth::user();
        $isAdmin = $this->isAdmin($user);
        $salesUserIds = $this->getSalesUserIds($user, $isAdmin);

        // If user filtered by Visit Date only (no Plan filter), prefer ordering by visit_date
        $hasPlanFilter = !empty($params['planStartDate']) || !empty($params['planEndDate']);
        $hasVisitFilter = !empty($params['visitStartDate']) || !empty($params['visitEndDate']);
        // Pemetaan kolom: plan_date => 1, visit_date => 5 (lihat mapping pada sortRows)
        if ($hasVisitFilter && !$hasPlanFilter) {
                // Jika order saat ini adalah plan_date default (1) atau tidak ditentukan, ganti ke visit_date (5)
            if (is_null($params['orderColumnIndex']) || $params['orderColumnIndex'] === 1) {
                $params['orderColumnIndex'] = 5;
            }
        }

        // Delegasikan ke helper kecil yang mengembalikan hitungan dan baris bertipe
        $result = $this->getComparisonRows($params, $user, $isAdmin, $salesUserIds);

        // Urutkan dan paginasi menggunakan helper (rutin kecil bertipe)
        $rows = $result['rows'];
        $this->sortRows($rows, $params['orderColumnIndex'], $params['orderDir']);
        $paged = $this->paginateRows($rows, $params['start'], $params['length']);

        return response()->json([
            'draw' => $params['draw'],
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => array_values($paged)
        ]);
    }

    /**
     * Bangun baris perbandingan dan hitungan berdasarkan params dan filter yang sudah diparsing.
     *
     * @param array $params Parsed request dari parseDataTableRequest
     * @param mixed $user Objekt user yang terautentikasi
     * @param bool $isAdmin
     * @param array $salesUserIds
     * @return array{recordsTotal:int, recordsFiltered:int, rows:array}
     */
    private function getComparisonRows(array $params, $user, bool $isAdmin, array $salesUserIds): array
    {
        // Start/end legacy (tidak dipakai langsung untuk plan/visit bila filter spesifik diberikan)
        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        $planStartDate = $params['planStartDate'] ?? $startDate;
        $planEndDate = $params['planEndDate'] ?? $endDate;
        $visitStartDate = $params['visitStartDate'] ?? $startDate;
        $visitEndDate = $params['visitEndDate'] ?? $endDate;
        $salesFilter = trim($params['salesFilter']);
        $regionFilter = $params['regionFilter'];
        $companyFilter = $params['companyFilter'];
        $planCompanyFilter = $params['planCompanyFilter'] ?? $companyFilter;
        $visitCompanyFilter = $params['visitCompanyFilter'] ?? $companyFilter;
        $searchValue = $params['searchValue'];

        // Build base queries via helpers to keep analysis-friendly and concise
        $visitsBase = $this->buildVisitsBaseQuery($isAdmin, $salesUserIds, $visitStartDate, $visitEndDate, $salesFilter, $regionFilter, $visitCompanyFilter);
        $plansBase = $this->buildPlansBaseQuery($isAdmin, $salesUserIds, $planStartDate, $planEndDate, $salesFilter, $regionFilter, $planCompanyFilter);

        // Tentukan pelanggan (customer) yang terlibat
        $involvedCustomers = $this->getInvolvedCustomersFromBases($visitsBase, $plansBase);

        if (empty($involvedCustomers)) {
            return ['recordsTotal' => 0, 'recordsFiltered' => 0, 'rows' => []];
        }

        // Ambil seluruh riwayat dan bandingkan
        [$allVisits, $allPlans] = $this->fetchAllHistory($involvedCustomers, $isAdmin, $salesUserIds, $salesFilter, $regionFilter, $planStartDate, $planEndDate, $visitStartDate, $visitEndDate, $planCompanyFilter, $visitCompanyFilter);
        $fullComparison = $this->compareVisitsAndPlans($allVisits, $allPlans);
        $recordsTotal = count($fullComparison);

        // Terapkan filter keberadaan plan/visit yang sama seperti di getExpandedData bila tidak ada kata cari
        $hasPlanFilter = !empty($planStartDate) || !empty($planEndDate);
        $hasVisitFilter = !empty($visitStartDate) || !empty($visitEndDate);

        if (!empty($searchValue)) {
            [$rows, $recordsFiltered] = $this->filterBySearch($searchValue, $involvedCustomers, $isAdmin, $planStartDate, $planEndDate, $visitStartDate, $visitEndDate, $salesUserIds, $salesFilter, $regionFilter, $planCompanyFilter, $visitCompanyFilter);

            // Terapkan filter keberadaan (aturan yang sama seperti di bawah) pada hasil pencarian juga
            $rows = array_values(array_filter($rows, function($row) use ($planStartDate, $planEndDate, $visitStartDate, $visitEndDate, $hasPlanFilter, $hasVisitFilter) {
                $planDate = substr($row['plan_date'], 0, 10);
                $visitDate = substr($row['visit_date'], 0, 10);

                $planInRange = ($row['plan_date'] !== '-') &&
                    (!$planStartDate || $planDate >= $planStartDate) &&
                    (!$planEndDate || $planDate <= $planEndDate);

                $visitInRange = ($row['visit_date'] !== '-') &&
                    (!$visitStartDate || $visitDate >= $visitStartDate) &&
                    (!$visitEndDate || $visitDate <= $visitEndDate);

                if ($hasPlanFilter && !$hasVisitFilter) {
                    return $planInRange;
                }
                if ($hasVisitFilter && !$hasPlanFilter) {
                    // Hanya sertakan baris yang hanya visit saat pengguna hanya memfilter berdasarkan tanggal kunjungan
                    return $visitInRange && ($row['plan_date'] === '-');
                }
                if ($hasPlanFilter && $hasVisitFilter) {
                    return $planInRange || $visitInRange;
                }
                return true;
            }));

            $recordsFiltered = count($rows);
        } else {
            // Filter fullComparison sesuai keberadaan filter plan/visit
            $rows = array_values(array_filter($fullComparison, function($row) use ($planStartDate, $planEndDate, $visitStartDate, $visitEndDate, $hasPlanFilter, $hasVisitFilter) {
                $planDate = substr($row['plan_date'], 0, 10);
                $visitDate = substr($row['visit_date'], 0, 10);

                $planInRange = ($row['plan_date'] !== '-') &&
                    (!$planStartDate || $planDate >= $planStartDate) &&
                    (!$planEndDate || $planDate <= $planEndDate);

                $visitInRange = ($row['visit_date'] !== '-') &&
                    (!$visitStartDate || $visitDate >= $visitStartDate) &&
                    (!$visitEndDate || $visitDate <= $visitEndDate);

                if ($hasPlanFilter && !$hasVisitFilter) {
                    return $planInRange;
                }
                if ($hasVisitFilter && !$hasPlanFilter) {
                    // Hanya sertakan baris yang hanya visit saat pengguna hanya memfilter berdasarkan tanggal kunjungan
                    return $visitInRange && ($row['plan_date'] === '-');
                }
                if ($hasPlanFilter && $hasVisitFilter) {
                    return $planInRange || $visitInRange;
                }
                return true;
            }));

            $recordsFiltered = count($rows);
        }

        return ['recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'rows' => $rows];
    }

    /**
     * Helper: parse parameter request DataTables menjadi array bertipe
     *
     * @param Request $request
     * @return array{
     *     startDate: ?string,
     *     endDate: ?string,
     *     salesFilter: string,
     *     regionFilter: string,
     *     companyFilter: string,
     *     searchValue: string,
     *     orderColumnIndex: int|null,
     *     orderDir: string,
     *     start: int,
     *     length: int,
     *     draw: int
     * }
     */
    private function parseDataTableRequest(Request $request): array
    {
        /** @var string|null $startDate (legacy - applies to both if specific not provided) */
        $startDate = $request->input('startDate');
        /** @var string|null $endDate (legacy - applies to both if specific not provided) */
        $endDate = $request->input('endDate');

        // Baru: filter terpisah untuk Plan Visit dan Visit Date
        $planStartDate = $request->input('planStartDate', $startDate);
        $planEndDate = $request->input('planEndDate', $endDate);
        $visitStartDate = $request->input('visitStartDate', $startDate);
        $visitEndDate = $request->input('visitEndDate', $endDate);
        $salesFilter = trim((string)$request->input('salesFilter'));
        $regionFilter = trim((string)$request->input('regionFilter'));
        $companyFilter = trim((string)$request->input('companyFilter'));

        // Separate company filters for Plan and Visit (fallback to legacy companyFilter)
        $planCompanyFilter = $request->input('planCompanyFilter', $companyFilter);
        $visitCompanyFilter = $request->input('visitCompanyFilter', $companyFilter);

        $searchValue = trim((string)$request->input('search.value'));
        $orderColumnIndex = $request->input('order.0.column');
        $orderColumnIndex = is_null($orderColumnIndex) ? null : (int)$orderColumnIndex;
        $orderDir = (string)$request->input('order.0.dir', 'asc');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length === -1) {
            $length = PHP_INT_MAX;
        }
        $draw = (int) $request->input('draw', 0);


        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'planStartDate' => $planStartDate,
            'planEndDate' => $planEndDate,
            'visitStartDate' => $visitStartDate,
            'visitEndDate' => $visitEndDate,
            'planCompanyFilter' => $planCompanyFilter,
            'visitCompanyFilter' => $visitCompanyFilter,
            'salesFilter' => $salesFilter,
            'regionFilter' => $regionFilter,
            'companyFilter' => $companyFilter,
            'searchValue' => $searchValue,
            'orderColumnIndex' => $orderColumnIndex,
            'orderDir' => $orderDir,
            'start' => $start,
            'length' => $length,
            'draw' => $draw,
        ];
    }

    /**
     * Bangun query dasar untuk kunjungan agar `getComparisonRows` tetap ringkas.
     *
     * @param bool $isAdmin
     * @param array $salesUserIds
     * @param string|null $visitStartDate
     * @param string|null $visitEndDate
     * @param string $salesFilter
     * @param string $regionFilter
     * @param string $visitCompanyFilter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildVisitsBaseQuery(bool $isAdmin, array $salesUserIds, $visitStartDate = null, $visitEndDate = null, string $salesFilter = '', string $regionFilter = '', $visitCompanyFilter = null)
    {
        $query = LogbookVisits::query()
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds), fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($visitStartDate, fn($q) => $q->whereDate('visit_date', '>=', $visitStartDate))
            ->when($visitEndDate, fn($q) => $q->whereDate('visit_date', '<=', $visitEndDate))
            ->when($salesFilter, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', $salesFilter)))
            ->when($regionFilter, function($q) use ($regionFilter) {
                $regionMappings = $this->getRegionMappings();
                $salesInRegion = $regionMappings[$regionFilter] ?? [];
                return $q->whereHas('user', fn($u) => $u->whereIn('name', $salesInRegion));
            })
            ->when($visitCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$visitCompanyFilter}%"));

        return $query;
    }

    /**
     * Bangun query dasar untuk rencana kunjungan agar `getComparisonRows` tetap ringkas.
     *
     * @param bool $isAdmin
     * @param array $salesUserIds
     * @param string|null $planStartDate
     * @param string|null $planEndDate
     * @param string $salesFilter
     * @param string $regionFilter
     * @param string $planCompanyFilter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildPlansBaseQuery(bool $isAdmin, array $salesUserIds, $planStartDate = null, $planEndDate = null, string $salesFilter = '', string $regionFilter = '', $planCompanyFilter = null)
    {
        $query = TrsLogbookVisits::query()
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds), fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($planStartDate, fn($q) => $q->whereDate('plan_visit', '>=', $planStartDate))
            ->when($planEndDate, fn($q) => $q->whereDate('plan_visit', '<=', $planEndDate))
            ->when($salesFilter, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', $salesFilter)))
            ->when($regionFilter, function($q) use ($regionFilter) {
                $regionMappings = $this->getRegionMappings();
                $salesInRegion = $regionMappings[$regionFilter] ?? [];
                return $q->whereHas('user', fn($u) => $u->whereIn('name', $salesInRegion));
            })
            ->when($planCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$planCompanyFilter}%"));

        return $query;
    }

    /**
     * Ambil nama pelanggan yang terlibat dari query dasar.
     *
     * @param \Illuminate\Database\Eloquent\Builder $visitsBase
     * @param \Illuminate\Database\Eloquent\Builder $plansBase
     * @return array<string>
     */
    private function getInvolvedCustomersFromBases($visitsBase, $plansBase): array
    {
        $visitCustomers = $visitsBase->pluck('customer_name')->unique()->filter()->values()->toArray();
        $planCustomers = $plansBase->pluck('customer_name')->unique()->filter()->values()->toArray();
        return array_values(array_unique(array_merge($visitCustomers, $planCustomers)));
    }

    /**
     * Helper: ambil seluruh riwayat (kunjungan & rencana kunjungan) untuk sekumpulan pelanggan
     *
     * @param iterable|array $involvedCustomers
     * @param bool $isAdmin
     * @param array $salesUserIds
     * @param string $salesFilter
     * @param string $regionFilter
     * @return array [\Illuminate\Database\Eloquent\Collection, \Illuminate\Database\Eloquent\Collection]
     */
    private function fetchAllHistory(iterable $involvedCustomers, bool $isAdmin, array $salesUserIds, string $salesFilter = '', string $regionFilter = '', $planStartDate = null, $planEndDate = null, $visitStartDate = null, $visitEndDate = null, $planCompanyFilter = null, $visitCompanyFilter = null): array
    {
        // Normalisasi iterable/Collection menjadi array nilai (nama customer)
        if ($involvedCustomers instanceof \Illuminate\Support\Collection) {
            $involvedCustomers = $involvedCustomers->values()->toArray();
        } elseif (!is_array($involvedCustomers)) {
            $involvedCustomers = is_iterable($involvedCustomers)
                ? (is_object($involvedCustomers) && method_exists($involvedCustomers, 'toArray')
                    ? $involvedCustomers->toArray()
                    : iterator_to_array($involvedCustomers))
                : (array) $involvedCustomers;
        }

        $allVisits = LogbookVisits::with('user')
            ->whereIn('customer_name', $involvedCustomers)
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds),fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($visitStartDate, fn($q) => $q->whereDate('visit_date', '>=', $visitStartDate))
            ->when($visitEndDate, fn($q) => $q->whereDate('visit_date', '<=', $visitEndDate))
            ->when($visitCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$visitCompanyFilter}%"))
            ->when($salesFilter, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', $salesFilter)))
            ->when($regionFilter, function($q) use ($regionFilter) {
                $regionMappings = $this->getRegionMappings();
                $salesInRegion = $regionMappings[$regionFilter] ?? [];
                return $q->whereHas('user', fn($u) => $u->whereIn('name', $salesInRegion));
            })
            ->get();

        $allPlans = TrsLogbookVisits::with('user')
            ->whereIn('customer_name', $involvedCustomers)
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds),fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($planStartDate, fn($q) => $q->whereDate('plan_visit', '>=', $planStartDate))
            ->when($planEndDate, fn($q) => $q->whereDate('plan_visit', '<=', $planEndDate))
            ->when($planCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$planCompanyFilter}%"))
            ->when($salesFilter, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', $salesFilter)))
            ->when($regionFilter, function($q) use ($regionFilter) {
                $regionMappings = $this->getRegionMappings();
                $salesInRegion = $regionMappings[$regionFilter] ?? [];
                return $q->whereHas('user', fn($u) => $u->whereIn('name', $salesInRegion));
            })
            ->get();

        return [$allVisits, $allPlans];
    }

    /**
     * Helper: terapkan filter pencarian dan kembalikan baris yang cocok beserta jumlahnya
     *
     * @param string $searchValue
     * @param array $involvedCustomers
     * @param bool $isAdmin
     * @param string|null $startDate
     * @param string|null $endDate
     * @param array $salesUserIds
     * @param string $salesFilter
     * @param string $regionFilter
     * @return array [array $rows, int $recordsFiltered]
     */
    private function filterBySearch(string $searchValue, array $involvedCustomers, bool $isAdmin, $planStartDate = null, $planEndDate = null, $visitStartDate = null, $visitEndDate = null, array $salesUserIds = [], string $salesFilter = '', string $regionFilter = '', $planCompanyFilter = null, $visitCompanyFilter = null): array
    {
        $searchLike = "%{$searchValue}%";

        $visitMatchCustomers = LogbookVisits::query()
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds),fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($visitStartDate, fn($q) => $q->whereDate('visit_date', '>=', $visitStartDate))
            ->when($visitEndDate, fn($q) => $q->whereDate('visit_date', '<=', $visitEndDate))
            ->when($visitCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$visitCompanyFilter}%"))
            ->where(function($q) use ($searchLike) {
                $q->where('customer_name', 'LIKE', $searchLike)
                  ->orWhere('pic_cust', 'LIKE', $searchLike)
                  ->orWhere('remark', 'LIKE', $searchLike)
                  ->orWhere('visit_result', 'LIKE', $searchLike);
            })
            ->pluck('customer_name')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        $planMatchCustomers = TrsLogbookVisits::query()
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds),fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($planStartDate, fn($q) => $q->whereDate('plan_visit', '>=', $planStartDate))
            ->when($planEndDate, fn($q) => $q->whereDate('plan_visit', '<=', $planEndDate))
            ->when($planCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$planCompanyFilter}%"))
            ->where(function($q) use ($searchLike) {
                $q->where('customer_name', 'LIKE', $searchLike)
                  ->orWhere('keterangan', 'LIKE', $searchLike);
            })
            ->pluck('customer_name')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        $userMatchCustomerFromVisits = LogbookVisits::query()
            ->when($visitStartDate, fn($q) => $q->whereDate('visit_date', '>=', $visitStartDate))
            ->when($visitEndDate, fn($q) => $q->whereDate('visit_date', '<=', $visitEndDate))
            ->whereHas('user', fn($u) => $u->where('name', 'LIKE', $searchLike))
            ->pluck('customer_name')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        $userMatchCustomerFromPlans = TrsLogbookVisits::query()
            ->when($planStartDate, fn($q) => $q->whereDate('plan_visit', '>=', $planStartDate))
            ->when($planEndDate, fn($q) => $q->whereDate('plan_visit', '<=', $planEndDate))
            ->whereHas('user', fn($u) => $u->where('name', 'LIKE', $searchLike))
            ->pluck('customer_name')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        $matchedCustomers = array_values(array_unique(array_merge($visitMatchCustomers, $planMatchCustomers, $userMatchCustomerFromVisits, $userMatchCustomerFromPlans)));
        $matchedCustomers = array_values(array_intersect($involvedCustomers, $matchedCustomers));

        if (empty($matchedCustomers)) {
            return [[], 0];
        }

        $allVisits = LogbookVisits::with('user')
            ->when($visitStartDate, fn($q) => $q->whereDate('visit_date', '>=', $visitStartDate))
            ->when($visitEndDate, fn($q) => $q->whereDate('visit_date', '<=', $visitEndDate))
            ->when($visitCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$visitCompanyFilter}%"))
            ->whereIn('customer_name', $matchedCustomers)
            ->get();

        $allPlans = TrsLogbookVisits::with('user')
            ->when($planStartDate, fn($q) => $q->whereDate('plan_visit', '>=', $planStartDate))
            ->when($planEndDate, fn($q) => $q->whereDate('plan_visit', '<=', $planEndDate))
            ->when($planCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$planCompanyFilter}%"))
            ->whereIn('customer_name', $matchedCustomers)
            ->get();

        $filteredComparison = $this->compareVisitsAndPlans($allVisits, $allPlans);
            // Terapkan filter berdasarkan keberadaan plan/visit ke hasil pencarian juga
        $hasPlanFilter = !empty($planStartDate) || !empty($planEndDate);
        $hasVisitFilter = !empty($visitStartDate) || !empty($visitEndDate);

        $filtered = array_values(array_filter($filteredComparison, function($row) use ($planStartDate, $planEndDate, $visitStartDate, $visitEndDate, $hasPlanFilter, $hasVisitFilter) {
            $planDate = substr($row['plan_date'], 0, 10);
            $visitDate = substr($row['visit_date'], 0, 10);

            $planInRange = ($row['plan_date'] !== '-') &&
                (!$planStartDate || $planDate >= $planStartDate) &&
                (!$planEndDate || $planDate <= $planEndDate);

            $visitInRange = ($row['visit_date'] !== '-') &&
                (!$visitStartDate || $visitDate >= $visitStartDate) &&
                (!$visitEndDate || $visitDate <= $visitEndDate);

            if ($hasPlanFilter && !$hasVisitFilter) {
                return $planInRange && ($row['visit_date'] !== '-');
            }
            if ($hasVisitFilter && !$hasPlanFilter) {
                return $visitInRange && ($row['plan_date'] === '-');
            }
            if ($hasPlanFilter && $hasVisitFilter) {
                return ($planInRange && $row['visit_date'] !== '-') || ($visitInRange && $row['plan_date'] === '-');
            }
            return true;
        }));

        $recordsFiltered = count($filtered);

        return [$filtered, $recordsFiltered];
    }

    /**
     * Helper: urutkan baris secara in-place berdasarkan kolom/order DataTables
     *
     * @param array $rows
     * @param int|null $orderColumnIndex
     * @param string $orderDir
     * @return void
     */
    private function sortRows(array &$rows, $orderColumnIndex, string $orderDir): void
    {
        $columnMap = [
            0 => 'sales_name',
            1 => 'plan_date',
            2 => 'keterangan',
            3 => 'company',
            4 => 'pic_cust',
            5 => 'visit_date',
            6 => 'remark',
            7 => 'visit_result'
        ];

        if (!isset($columnMap[$orderColumnIndex])) {
            return;
        }

        $sortKey = $columnMap[$orderColumnIndex];
        usort($rows, function ($a, $b) use ($sortKey, $orderDir) {
            $valA = $a[$sortKey] ?? '';
            $valB = $b[$sortKey] ?? '';
            if ($valA == $valB) return 0;
            return ($orderDir === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
        });
    }

    /**
     * Helper: paginasi baris
     *
     * @param array $rows
     * @param int $start
     * @param int $length
     * @return array
     */
    private function paginateRows(array $rows, int $start, int $length): array
    {
        return array_slice($rows, $start, $length);
    }

    /**
     * Get Expanded Data for Table/Export
     */
    private function getExpandedData($planStartDate = null, $planEndDate = null, $visitStartDate = null, $visitEndDate = null, $salesFilter = '', $regionFilter = '', $planCompanyFilter = '', $visitCompanyFilter = '')
    {
        $user = Auth::user();
        $isAdmin = $this->isAdmin($user);
        $salesUserIds = $this->getSalesUserIds($user, $isAdmin);
        // 1. Initial Strict Filter (to find involved customers)
        $visitsQuery = LogbookVisits::with('user')
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds),fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($visitStartDate, fn($q) => $q->whereDate('visit_date', '>=', $visitStartDate))
            ->when($visitEndDate, fn($q) => $q->whereDate('visit_date', '<=', $visitEndDate))
            ->when($salesFilter, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', $salesFilter)))
            ->when($regionFilter, function($q) use ($regionFilter) {
                $regionMappings = $this->getRegionMappings();
                $salesInRegion = $regionMappings[$regionFilter] ?? [];
                return $q->whereHas('user', fn($u) => $u->whereIn('name', $salesInRegion));
            })
            ->when($visitCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$visitCompanyFilter}%"));

        $visits = $visitsQuery->get();

        $plansQuery = TrsLogbookVisits::with('user')
            ->when(!$isAdmin && !$this->isSalesRole(Auth::user()) && !empty($salesUserIds),fn($q) => $q->whereIn('id_user', $salesUserIds))
            ->when($planStartDate, fn($q) => $q->whereDate('plan_visit', '>=', $planStartDate))
            ->when($planEndDate, fn($q) => $q->whereDate('plan_visit', '<=', $planEndDate))
            ->when($salesFilter, fn($q) => $q->whereHas('user', fn($u) => $u->where('name', $salesFilter)))
            ->when($regionFilter, function($q) use ($regionFilter) {
                $regionMappings = $this->getRegionMappings();
                $salesInRegion = $regionMappings[$regionFilter] ?? [];
                return $q->whereHas('user', fn($u) => $u->whereIn('name', $salesInRegion));
            })
            ->when($planCompanyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$planCompanyFilter}%"));

        $plans = $plansQuery->get();

        // Identifikasi pelanggan yang terlibat
        $visitCustomers = $visits->pluck('customer_name')->unique();
        $planCustomers = $plans->pluck('customer_name')->unique();
        $involvedCustomers = $visitCustomers->merge($planCustomers)->unique()->filter()->values();


        // 2. Fetch All History (apply same separate filters)
        if ($involvedCustomers->isNotEmpty()) {
            [$allVisits, $allPlans] = $this->fetchAllHistory($involvedCustomers, $isAdmin, $salesUserIds, $salesFilter, $regionFilter, $planStartDate, $planEndDate, $visitStartDate, $visitEndDate, $planCompanyFilter, $visitCompanyFilter);
        } else {
            $allVisits = $visits;
            $allPlans = $plans;
        }

        // Compare
        $fullComparisonData = $this->compareVisitsAndPlans($allVisits, $allPlans);

        // Filter final result
        // Tentukan apakah filter spesifik telah diberikan
        $hasPlanFilter = !empty($planStartDate) || !empty($planEndDate);
        $hasVisitFilter = !empty($visitStartDate) || !empty($visitEndDate);

        $comparisonData = array_filter($fullComparisonData, function($row) use ($planStartDate, $planEndDate, $visitStartDate, $visitEndDate, $hasPlanFilter, $hasVisitFilter) {
            // Normalize dates to Y-m-d for comparison
            $planDate = substr($row['plan_date'], 0, 10);
            $visitDate = substr($row['visit_date'], 0, 10);
            $planInRange = ($row['plan_date'] !== '-') && 
                (!$planStartDate || $planDate >= $planStartDate) && 
                (!$planEndDate || $planDate <= $planEndDate);

            $visitInRange = ($row['visit_date'] !== '-') && 
                (!$visitStartDate || $visitDate >= $visitStartDate) && 
                (!$visitEndDate || $visitDate <= $visitEndDate);

            // If user set only plan filter -> include plans in range (include plan-only rows)
            if ($hasPlanFilter && !$hasVisitFilter) {
                return $planInRange;
            }

            // If user set only visit filter -> include only visit-only rows (no plan)
            if ($hasVisitFilter && !$hasPlanFilter) {
                return $visitInRange && ($row['plan_date'] === '-');
            }

            // If both provided -> include matched pairs where plan in range OR visit-only where visit in range
            if ($hasPlanFilter && $hasVisitFilter) {
                return ($planInRange && $row['visit_date'] !== '-') || ($visitInRange && $row['plan_date'] === '-');
            }

            // No specific filters -> include everything
            return true;
        });

        return array_values($comparisonData);
    }

    /**
     * Ekspor data dashboard ke Excel
     */
    public function exportExcel(Request $request)
    {
        // Periksa otorisasi
        if (!$this->isAuthorizedUser()) {
            abort(403, 'Unauthorized access');
        }

        $user = Auth::user();
        
        // Ambil filter (konsisten dengan getDetailData)
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $planStartDate = $request->input('planStartDate', $startDate);
        $planEndDate = $request->input('planEndDate', $endDate);
        $visitStartDate = $request->input('visitStartDate', $startDate);
        $visitEndDate = $request->input('visitEndDate', $endDate);
        $salesFilter = trim($request->input('salesFilter'));
        $regionFilter = trim($request->input('regionFilter'));
        $companyFilter = trim($request->input('companyFilter'));
        $planCompanyFilter = $request->input('planCompanyFilter', $companyFilter);
        $visitCompanyFilter = $request->input('visitCompanyFilter', $companyFilter);

        // Gunakan helper untuk mengambil data (teruskan rentang plan/visit terpisah)
        $comparisonData = $this->getExpandedData($planStartDate, $planEndDate, $visitStartDate, $visitEndDate, $salesFilter, $regionFilter, $planCompanyFilter, $visitCompanyFilter);

        // Izinkan UI menunjukkan jenis ekspor yang diminta (plan atau visit)
        $exportType = $request->input('exportType', '');
        $timestamp = Carbon::now()->format('Y-m-d_His');
        switch (strtolower($exportType)) {
            case 'plan':
                $fileName = "Sales_Plan_vs_Visit_Report_{$timestamp}.xlsx";
                break;
            case 'visit':
                $fileName = "Sales_asVisit_Report_{$timestamp}.xlsx";
                break;
            default:
                $fileName = "Sales_Visit_Report_{$timestamp}.xlsx";
                break;
        }

        // Tentukan filter perusahaan yang ditampilkan dalam metadata ekspor (prioritaskan plan jika diberikan)
        $companyForExport = $planCompanyFilter !== '' ? $planCompanyFilter : ($visitCompanyFilter !== '' ? $visitCompanyFilter : '');

        return Excel::download(new SalesVisitExport($comparisonData, $startDate, $endDate, $salesFilter, $regionFilter, $companyForExport, $user->name), $fileName);
    }

    /**
     * Dapatkan ID user sales berdasarkan peran
     */
    private function getSalesUserIds($user, $isAdmin)
    {
        if ($isAdmin) {
            return []; // Admin can see all
        }

        $deptHeadMappings = $this->getDepartmentHeadMappings();
        $userName = strtoupper($user->name);

        if (array_key_exists($userName, $deptHeadMappings)) {
            $salesTeam = $deptHeadMappings[$userName] ?? [];
            $ids = User::whereIn('name', $salesTeam)->pluck('id')->toArray();

            // Jika ID pengguna sales yang dihitung tidak memiliki kunjungan/rencana di database,
            // fallback ke tanpa pembatasan agar user tetap melihat data (menangani masalah integritas data).
            $hasVisits = LogbookVisits::whereIn('id_user', $ids)->exists();
            $hasPlans = TrsLogbookVisits::whereIn('id_user', $ids)->exists();
            if (!$hasVisits && !$hasPlans) {
                return []; // no restriction
            }

            return $ids;
        }

        // Prefer akses berbasis peran: jika pengguna saat ini memiliki peran sales, izinkan
        // melihat data milik semua pengguna yang memiliki role sales (2,3,7,4,44).
        $salesRoleIds = [2, 3, 4, 44];
        if (isset($user->role_id) && in_array((int)$user->role_id, $salesRoleIds, true)) {
            $ids = User::whereIn('role_id', $salesRoleIds)->pluck('id')->toArray();
            // If no users found for those roles, fall back to no restriction to avoid empty results
            return !empty($ids) ? $ids : [];
        }

        // Jika field `section` pengguna berisi 'sales', pertahankan perilaku lama (tanpa pembatasan)
        if (isset($user->section) && is_string($user->section) && stripos($user->section, 'sales') !== false) {
            return []; // empty means "no restriction" in callers
        }

        // Fallback biasa: pengguna hanya melihat baris miliknya sendiri
        return [$user->id];
    }

    /**
     * Bandingkan kunjungan dan rencana untuk menghasilkan data perbandingan
     * Logika:
     * - Proses plans terlebih dahulu sebagai baseline, lalu cari satu visit yang cocok untuk setiap plan
     * - Cocokkan berdasarkan `customer_name` (case-insensitive)
     * - Setiap plan hanya dapat dicocokkan sekali
     * - Plan yang tidak tercocokkan ditampilkan sebagai baris hanya-plan
     */
    private function compareVisitsAndPlans($visits, $plans)
    {
        // PLAN adalah baseline. Kita iterasi plans terlebih dahulu dan mencoba menempelkan satu visit
        // yang cocok untuk setiap plan (cocok berdasarkan customer_name, case-insensitive, dan trim).
        // Setiap visit yang tidak cocok akan ditambahkan sebagai baris hanya-visit.
        $comparisonData = [];
        $matchedVisitIds = [];

        // Urutkan koleksi untuk keluaran yang stabil
        $sortedPlans = $plans->sortBy('plan_visit')->values();
        $sortedVisits = $visits->sortBy('visit_date')->values();

        // Helper untuk mendekode file dari sebuah visit
        $decodeFiles = function($visit) {
            if (empty($visit->file)) {
                return '-';
            }
            $fileData = json_decode($visit->file, true);
            if (is_array($fileData) && count($fileData) > 0) {
                return $fileData;
            }
            if (is_string($visit->file) && !empty($visit->file)) {
                return [$visit->file];
            }
            return '-';
        };
        // Iterasi plans (sebagai baseline)
        foreach ($sortedPlans as $plan) {
            $planCustomer = strtolower(trim($plan->customer_name ?? ''));
            $matchingVisit = null;

            foreach ($sortedVisits as $visit) {
                if (in_array($visit->id, $matchedVisitIds)) {
                    continue;
                }
                $visitCustomer = strtolower(trim($visit->customer_name ?? ''));
                if ($visitCustomer === $planCustomer) {
                    $matchingVisit = $visit;
                    $matchedVisitIds[] = $visit->id;
                    break; // one visit per plan
                }
            }

            $files = $matchingVisit ? $decodeFiles($matchingVisit) : '-';
            $displayCustomer = $matchingVisit && !empty($matchingVisit->new_customer_name)
                ? $matchingVisit->new_customer_name
                : ($plan->customer_name ?? '-');

            $comparisonData[] = [
                'sales_name' => $matchingVisit->user->name ?? $plan->user->name ?? '-',
                'customer_name' => $plan->customer_name ?? '-',
                'new_customer_name' => $matchingVisit->new_customer_name ?? '-',
                'plan_date' => $plan->plan_visit ?? '-',
                'keterangan' => $plan->keterangan ?? '-',
                'company' => $displayCustomer ?? '-',
                'pic_cust' => $matchingVisit->pic_cust ?? '-',
                'visit_date' => $matchingVisit->visit_date ?? '-',
                'remark' => $matchingVisit->remark ?? '-',
                'visit_result' => $matchingVisit->visit_result ?? '-',
                'files' => $files,
            ];
        }

        // Kunjungan yang tidak dicocokkan ke plan menjadi baris hanya-visit
        foreach ($sortedVisits as $visit) {
            if (in_array($visit->id, $matchedVisitIds)) {
                continue;
            }

            $files = $decodeFiles($visit);
            $displayCustomer = !empty($visit->new_customer_name) ? $visit->new_customer_name : $visit->customer_name;

            $comparisonData[] = [
                'sales_name' => $visit->user->name ?? '-',
                'customer_name' => $visit->customer_name ?? '-',
                'new_customer_name' => $visit->new_customer_name ?? '-',
                'plan_date' => '-',
                'keterangan' => '-',
                'company' => $displayCustomer ?? '-',
                'pic_cust' => $visit->pic_cust ?? '-',
                'visit_date' => $visit->visit_date ?? '-',
                'remark' => $visit->remark ?? '-',
                'visit_result' => $visit->visit_result ?? '-',
                'files' => $files,
            ];
        }

        return $comparisonData;
    }

    /**
     * Hitung statistik ringkasan beserta rincian pelanggan
     */
    private function calculateSummary($comparisonData, $visits)
    {
        $totalPlans = 0;
        $totalVisits = 0;
        $sesuaiPlan = 0;
        $tidakSesuaiPlan = 0;

        // Kumpulkan nama pelanggan unik (case-insensitive)
        $uniqueCustomers = [];

        foreach ($comparisonData as $data) {
            $hasPlan = $data['plan_date'] !== '-';
            $hasVisit = $data['visit_date'] !== '-';
            
            if ($hasPlan) {
                $totalPlans++;
            }
            if ($hasVisit) {
                $totalVisits++;
            }
            // Sesuai Plan = has both plan and visit with same date
            if ($hasPlan && $hasVisit && $data['plan_date'] === $data['visit_date']) {
                $sesuaiPlan++;
            }
            // Tidak Sesuai Plan = has both plan and visit but different dates
            if ($hasPlan && $hasVisit && $data['plan_date'] !== $data['visit_date']) {
                $tidakSesuaiPlan++;
            }
            // Collect unique customer names
            if (!empty($data['company'])) {
                $uniqueCustomers[strtolower(trim($data['company']))] = true;
            }
        }

        // Hitung customer lama dan customer baru dari kunjungan
        $customerLama = 0;
        $customerBaru = 0;
        $processedCustomers = [];

        foreach ($visits as $visit) {
            $customerKey = strtolower(trim($visit->customer_name ?? ''));
            if (empty($customerKey) || isset($processedCustomers[$customerKey])) {
                continue;
            }
            $processedCustomers[$customerKey] = true;

            if (empty($visit->new_customer_name)) {
                $customerLama++;
            } else {
                $customerBaru++;
            }
        }

        return [
            'totalPlans' => $totalPlans,
            'totalVisits' => $totalVisits,
            'sesuaiPlan' => $sesuaiPlan,
            'tidakSesuaiPlan' => $tidakSesuaiPlan,
            'totalCustomer' => count($uniqueCustomers),
            'customerLama' => $customerLama,
            'customerBaru' => $customerBaru,
        ];
    }

    /**
     * Ambil data chart untuk visualisasi dengan penyaringan berbasis peran
     */
    private function getChartData($user, $isAdmin, $startDate, $endDate, $salesFilter, $regionFilter, $companyFilter, $visits = null, $plans = null)
    {
        // Tentukan nama-nama sales yang diizinkan berdasarkan peran
        $allowedSalesNames = [];
        
        if ($isAdmin) {
            // Admin dapat melihat semua sales dari pemetaan departemen dan juga dirinya sendiri
            $deptMappings = $this->getDepartmentHeadMappings();
            foreach ($deptMappings as $salesList) {
                $allowedSalesNames = array_merge($allowedSalesNames, $salesList);
            }
            // Tambahkan nama Admin secara eksplisit (agar muncul di filter/chart bila ada datanya)
            $adminNames = $this->getAdminNames();
            $allowedSalesNames = array_merge($allowedSalesNames, $adminNames);
            
            $allowedSalesNames = array_unique($allowedSalesNames);
        } else {
            // DeptHead hanya dapat melihat bawahan mereka
            $deptMappings = $this->getDepartmentHeadMappings();
            $userName = strtoupper($user->name); // Samakan huruf besar/kecil dengan kunci pemetaan
            $allowedSalesNames = $deptMappings[$userName] ?? [$user->name];
            
            // Pastikan array valid jika pemetaan tidak ada
            if (empty($allowedSalesNames)) {
                $allowedSalesNames = [$user->name];
            }
        }

        // Terapkan filter sales jika disetel
        if ($salesFilter) {
            // Perbandingan ketat
            $allowedSalesNames = array_filter($allowedSalesNames, fn($name) => strcasecmp($name, $salesFilter) === 0);
        }

        // Terapkan filter region jika disetel
        if ($regionFilter) {
            $regionMappings = $this->getRegionMappings();
            $salesInRegion = $regionMappings[$regionFilter] ?? [];
            $allowedSalesNames = array_intersect($allowedSalesNames, $salesInRegion);
        }

        // Ambil user berdasarkan nama (cocok tepat) namun batasi pada role sales
        $salesUsers = User::whereIn('name', $allowedSalesNames)
            ->whereIn('role_id', $this->getSalesRoleIds())
            ->get();

        $labels = [];
        $visitCounts = [];
        $planCounts = [];
        // ... rest of logic ...

        foreach ($salesUsers as $salesUser) {
            $labels[] = $salesUser->name;

            if ($visits !== null && $plans !== null) {
                // Gunakan koleksi yang disediakan (konsisten & cepat)
                $visitCount = $visits->where('id_user', $salesUser->id)->count();
                $planCount = $plans->where('id_user', $salesUser->id)->count();
            } else {
                // Hitung kunjungan untuk sales ini
                $visitCount = LogbookVisits::where('id_user', $salesUser->id)
                    ->when($startDate, fn($q) => $q->whereDate('visit_date', '>=', $startDate))
                    ->when($endDate, fn($q) => $q->whereDate('visit_date', '<=', $endDate))
                    ->when($companyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$companyFilter}%"))
                    ->count();

                // Hitung rencana untuk sales ini
                $planCount = TrsLogbookVisits::where('id_user', $salesUser->id)
                    ->when($startDate, fn($q) => $q->whereDate('plan_visit', '>=', $startDate))
                    ->when($endDate, fn($q) => $q->whereDate('plan_visit', '<=', $endDate))
                    ->when($companyFilter, fn($q) => $q->where('customer_name', 'LIKE', "%{$companyFilter}%"))
                    ->count();
            }

            $visitCounts[] = $visitCount;
            $planCounts[] = $planCount;
        }

        return [
            'labels' => $labels,
            'visits' => $visitCounts,
            'plans' => $planCounts,
        ];
    }

    /**
     * Get unique company list from both logbook_visits and trs_logbook_visits (case-insensitive)
     */
    private function getCompanyList()
    {
        // Ambil nama pelanggan dari logbook_visits
        $visitCustomers = LogbookVisits::whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->pluck('customer_name')
            ->toArray();

        // Ambil nama pelanggan dari trs_logbook_visits
        $planCustomers = TrsLogbookVisits::whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->pluck('customer_name')
            ->toArray();

        // Gabungkan dan hilangkan duplikat tanpa memperhatikan huruf
        $allCustomers = array_merge($visitCustomers, $planCustomers);
        $uniqueCustomers = [];
        $seenLowercase = [];

        foreach ($allCustomers as $customer) {
            $lowerCustomer = strtolower(trim($customer));
            if (!isset($seenLowercase[$lowerCustomer])) {
                $seenLowercase[$lowerCustomer] = true;
                $uniqueCustomers[] = trim($customer);
            }
        }

        // Urutkan secara alfabetis
        sort($uniqueCustomers, SORT_STRING | SORT_FLAG_CASE);

        return $uniqueCustomers;
    }

    /**
     * Check if current user is authorized to access dashboard
     */
    private function isAuthorizedUser()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Izinkan pengguna yang secara eksplisit terdaftar sebagai admin atau kepala departemen (perilaku lama)
        $allowedUsers = array_merge(
            $this->getAdminNames(),
            $this->getDeptHeadNames()
        );

        if (in_array($user->name, $allowedUsers)) {
            return true;
        }

        // Allow users by role_id who belong to Sales roles
        $salesRoleIds = [2, 3, 4, 44];
        if (isset($user->role_id) && in_array((int)$user->role_id, $salesRoleIds, true)) {
            return true;
        }

        // Allow users whose `section` field equals 'Sales' (case-insensitive)
        if (isset($user->section) && is_string($user->section) && strcasecmp(trim($user->section), 'Sales') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is admin
     */
    private function isAdmin($user)
    {
        if (!$user) {
            return false;
        }
        return in_array($user->name, $this->getAdminNames());
    }

    /**
     * Get department head mappings
     */
    private function getDepartmentHeadMappings(): array
    {
        return [
            'ANDIK TOTOK SISWOYO' => [
                'ANDIK TOTOK SISWOYO',
                'DANIA ISNAWATI',
                'FISKA CHRISMAS YUDHA',
                'TOTOK SISWOYO',
                'DWI KUNTORO',
                'YUNASIS PALGUNADI',
                'Hexapa Darmadi',
            ],
            'ILHAM CHOLID' => [
                'ILHAM CHOLID',
                'HERY HERMAWAN',
                'RIFQI RAHMAT DZATNIKA',
                'SARAH EGA BUDI ASTUTI',
                'Dimas Aditya Priandana',
                'SONY STIAWAN',
            ],
            'JUN JOHAMIN PD' => [
                'JUN JOHAMIN PD',
                'YAN WELEM MANGINSELA',
                'WULYO EKO PRASETYO',
                'SENDY PRABOWO',
            ],
            'YULMAI RIDO WINANDA' => [
                'YULMAI RIDO WINANDA',
                'YAN WELEM MANGINSELA',
                'WULYO EKO PRASETYO',
                'SENDY PRABOWO',
                'HERY HERMAWAN',
                'RIFQI RAHMAT DZATNIKA',
                'SARAH EGA BUDI ASTUTI',
                'Dimas Aditya Priandana',
                'SONY STIAWAN',
            ],
        ];
    }

    /**
     * Get region mappings
     */
    private function getRegionMappings(): array
    {
        return [
            'Region 1' => [
                'YAN WELEM MANGINSELA',
                'WULYO EKO PRASETYO',
                'SENDY PRABOWO',
                'Hexapa Darmadi',
            ],
            'Region 2' => [
                'HERY HERMAWAN',
                'RIFQI RAHMAT DZATNIKA',
                'SARAH EGA BUDI ASTUTI',
                'Dimas Aditya Priandana',
                'SONY STIAWAN',
                'Hexapa Darmadi',
            ],
            'Region 3' => [
                'DANIA ISNAWATI',
                'FISKA CHRISMAS YUDHA',
                'TOTOK SISWOYO',
                'Hexapa Darmadi',
            ],
            'Region 4' => [
                'DWI KUNTORO',
                'YUNASIS PALGUNADI',
                'Hexapa Darmadi',
            ],
        ];
    }

    /**
     * Get department head names
     */
    private function getDeptHeadNames(): array
    {
        return [
            'ANDIK TOTOK SISWOYO',
            'ILHAM CHOLID',
            'JUN JOHAMIN PD',
            'YULMAI RIDO WINANDA',
            'SARAH EGA BUDI ASTUTI',
        ];
    }

    /**
     * Get admin names
     */
    private function getAdminNames(): array
    {
        return [
            'ADMINSTRATOR',
        ];
    }
}
