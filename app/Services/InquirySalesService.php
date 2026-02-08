<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Enums\DetailInquiryStatus;
use App\Models\Customer;
use App\Models\InquirySales;
use App\Models\DetailInquiry;
use App\Models\DetailInquiryImport;
use App\Models\TypeMaterial;
use App\Models\TrxDboProgPurchase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InquirySalesService
{
    /**
     * Generate inquiry code
     * 
     * @param string $jenisInquiry
     * @return string
     */
    public function generateInquiryCode(string $jenisInquiry): string
    {
        $currentMonth = Carbon::now()->format('m');
        $currentYear = Carbon::now()->format('Y');
        
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
        
        return sprintf('%s/%02d/%04d/%03d', $jenisInquiry, $currentMonth, $currentYear, $nextNumber);
    }

    /**
     * Store local inquiry
     * 
     * @param array $data
     * @return InquirySales
     */
    public function storeLocalInquiry(array $data): InquirySales
    {
        $kodeInquiry = $this->generateInquiryCode($data['jenis_inquiry']);
        
        $inquiry = InquirySales::create([
            'kode_inquiry' => $kodeInquiry,
            'jenis_inquiry' => $data['jenis_inquiry'],
            'id_customer' => $data['id_customer'],
            'loc_imp' => 'Local',
            'status' => InquiryStatus::DRAFT_1->value,
            'is_active' => 1,
            'create_by' => Auth::user()->name,
        ]);

        // Create initial progress
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'description' => '---- No updates yet ----',
        ]);

        // Create creation progress
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => Auth::id(),
            'description' => 'Inquiry created.',
        ]);

        return $inquiry;
    }

    /**
     * Store import inquiry
     * 
     * @param array $data
     * @return InquirySales
     */
    public function storeImportInquiry(array $data): InquirySales
    {
        $kodeInquiry = $this->generateInquiryCode($data['jenis_inquiry']);
        
        $inquiry = InquirySales::create([
            'kode_inquiry' => $kodeInquiry,
            'jenis_inquiry' => $data['jenis_inquiry'],
            'loc_imp' => 'Import',
            'region' => $data['region'],
            'status' => InquiryStatus::DRAFT_1->value,
            'is_active' => 1,
            'create_by' => Auth::user()->name,
        ]);

        // Create creation progress
        TrxDboProgPurchase::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => Auth::id(),
            'description' => 'Inquiry untuk Region [' . $inquiry->region . '] ditambahkan oleh ' . Auth::user()->name,
        ]);

        return $inquiry;
    }

    /**
     * Get inquiries for create page (Local)
     * 
     * @param int $limit
     * @return Collection
     */
    public function getLocalInquiriesForCreate(int $limit = 10): Collection
    {
        $statuses = InquiryStatus::activeStatuses();
        
        $baseQuery = InquirySales::with([
            'customer:id,name_customer',
            'details:id,id_inquiry,ship',
            'latestPurchaseProgress' => static function ($query) {
                $table = $query->getModel()->getTable();
                $query->select(
                    "{$table}.id",
                    "{$table}.description",
                    "{$table}.created_at"
                );
            },
        ])
            ->select('inquiry_sales.*')
            ->whereIn('status', $statuses)
            ->where('is_active', 1)
            ->where('loc_imp', 'Local')
            ->whereIn('id', function ($query) use ($statuses) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('inquiry_sales')
                    ->whereIn('status', $statuses)
                    ->where('is_active', 1)
                    ->where('loc_imp', 'Local')
                    ->groupBy('kode_inquiry');
            });

        return (clone $baseQuery)
            ->orderByRaw('FIELD(status, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9)')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->unique('kode_inquiry')
            ->values();
    }

    /**
     * Get base query for DataTable (Local)
     * 
     * @return Builder
     */
    public function getLocalInquiriesBaseQuery(): Builder
    {
        $statuses = InquiryStatus::activeStatuses();
        
        return InquirySales::with([
            'customer:id,name_customer',
            'details:id,id_inquiry,ship',
            'latestPurchaseProgress' => static function ($query) {
                $table = $query->getModel()->getTable();
                $query->select(
                    "{$table}.id",
                    "{$table}.description",
                    "{$table}.created_at"
                );
            },
        ])
            ->select('inquiry_sales.*')
            ->whereIn('status', $statuses)
            ->where('is_active', 1)
            ->where('loc_imp', 'Local')
            ->whereIn('id', function ($query) use ($statuses) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('inquiry_sales')
                    ->whereIn('status', $statuses)
                    ->where('is_active', 1)
                    ->where('loc_imp', 'Local')
                    ->groupBy('kode_inquiry');
            });
    }

    /**
     * Format inquiry data for DataTable
     * 
     * @param InquirySales $inquiry
     * @return array
     */
    public function formatInquiryForDataTable(InquirySales $inquiry): array
    {
        $status = InquiryStatus::from($inquiry->status);
        $statusMeta = $status->getMeta();
        
        $latestProgress = $inquiry->latestPurchaseProgress;
        
        $shipLines = $inquiry->details
            ->pluck('ship')
            ->filter()
            ->unique()
            ->values();
        
        $shipHtml = $shipLines->isEmpty()
            ? '--- No Shipping Options ---'
            : $shipLines->map(fn ($ship) => e($ship))->implode('<br>');
        
        $estimatedDate = '-';
        if (!empty($inquiry->est_date)) {
            try {
                $estimatedDate = Carbon::parse($inquiry->est_date)->format('d-m-Y');
            } catch (\Throwable $e) {
                $estimatedDate = (string) $inquiry->est_date;
            }
        }
        
        return [
            'id' => $inquiry->id,
            'create_by' => $inquiry->create_by,
            'kode_inquiry' => $inquiry->kode_inquiry,
            'loc_imp' => $inquiry->loc_imp,
            'supplier' => $inquiry->supplier ?? '-',
            'customer_name' => optional($inquiry->customer)->name_customer ?? 'N/A',
            'status_label' => $statusMeta['label'],
            'status_class' => $statusMeta['class'],
            'ship_to' => $shipHtml,
            'last_update' => $latestProgress ? $latestProgress->description : 'No updates yet',
            'est_date' => $estimatedDate,
        ];
    }

    /**
     * Get inquiry for formulir
     * 
     * @param int $id
     * @return array
     */
    public function getInquiryForFormulir(int $id): array
    {
        $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);
        $materials = DetailInquiry::where('id_inquiry', $inquiry->id)
            ->with('type_materials')
            ->get();
        $typeMaterials = TypeMaterial::all();

        return [
            'inquiry' => $inquiry,
            'materials' => $materials,
            'typeMaterials' => $typeMaterials,
        ];
    }

    /**
     * Get inquiry for formulir import
     * 
     * @param int $id
     * @return array
     */
    public function getInquiryForFormulirImport(int $id): array
    {
        $inquiry = InquirySales::with('details.type_materials')->findOrFail($id);
        $materials = DetailInquiryImport::where('id_inquiry', $inquiry->id)
            ->with('type_materials')
            ->get();
        $typeMaterials = TypeMaterial::all();
        $customers = Customer::all();

        return [
            'inquiry' => $inquiry,
            'materials' => $materials,
            'typeMaterials' => $typeMaterials,
            'customers' => $customers,
        ];
    }

    /**
     * Get all customers
     * 
     * @return Collection
     */
    public function getAllCustomers(): Collection
    {
        return Customer::orderBy('name_customer')->get();
    }

    /**
     * Get status metadata
     * 
     * @param int $status
     * @return array{label: string, class: string}
     */
    public function getStatusMeta(int $status): array
    {
        try {
            $statusEnum = InquiryStatus::from($status);
            return $statusEnum->getMeta();
        } catch (\ValueError $e) {
            return ['label' => 'Unknown', 'class' => 'btn-light'];
        }
    }

    /**
     * Get detail status metadata
     * 
     * @param int $status
     * @return array{label: string, class: string}
     */
    public function getDetailStatusMeta(int $status): array
    {
        try {
            $statusEnum = DetailInquiryStatus::from($status);
            return $statusEnum->getMeta();
        } catch (\ValueError $e) {
            return ['label' => 'Pending', 'class' => 'badge bg-secondary'];
        }
    }

    /**
     * Resolve visible users based on role
     * 
     * @return array
     */
    public function resolveVisibleUsers(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $roleId = (int) ($user->role_id ?? 0);
        $rolesWithFullAccess = [1, 14, 15];
        $namesWithFullAccess = [
            'ADMINSTRATOR',
            'JESSICA PAUNE',
            'YULMAI RIDO WINANDA',
            'ILHAM CHOLID',
            'SONY STIAWAN',
            'SARAH EGA BUDI ASTUTI',
            'HERY HERMAWAN',
            'HEXAPA DARMADI',
            'DIMAS ADITYA PRIANDANA',
            'JUN JOHAMIN PD',
            'WULYO EKO PRASETYO',
            'YAN WELEM MANGINSELA',
            'SENDY PRABOWO',
            'ANDIK TOTOK SISWOYO',
            'DANIA ISNAWATI',
            'FISKA CHRISMAS YUDHA',
            'DWI KUNTORO',
            'YUNASIS PALGUNADI',
        ];

        $normalizedName = strtoupper(trim((string) ($user->name ?? '')));

        if (
            in_array($roleId, $rolesWithFullAccess, true)
            || ($normalizedName !== '' && in_array($normalizedName, $namesWithFullAccess, true))
        ) {
            return [];
        }

        return [$user->name];
    }
}

