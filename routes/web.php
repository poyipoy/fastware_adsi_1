<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BopmController;
use App\Http\Controllers\ClaimSubmissionController;
use App\Http\Controllers\CrpController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntertainController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FormFPPController;
use App\Http\Controllers\HandlingController;
use App\Http\Controllers\HeatTreatmentController;
use App\Http\Controllers\ImportAdministrationController;
use App\Http\Controllers\InquirySalesController;
use App\Http\Controllers\ItemCodeController;
use App\Http\Controllers\JsonToCsvController;
use App\Http\Controllers\KmAnalyticsController;
use App\Http\Controllers\KmPengajuanController;
use App\Http\Controllers\LayoutEditorController;
use App\Http\Controllers\LayoutMenuController;
use App\Http\Controllers\MadingController;
use App\Http\Controllers\magang\ConvertController;
use App\Http\Controllers\magang\StrukturOrganisasiController;
use App\Http\Controllers\MesinController;
use App\Http\Controllers\OutstandingMaterialController;
use App\Http\Controllers\PdController;
use App\Http\Controllers\PengajuanSubcontController;
use App\Http\Controllers\PenilaianTCController;
use App\Http\Controllers\PoPengajuanController;
use App\Http\Controllers\PreventiveController;
use App\Http\Controllers\SafetyController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\SumbangSaranController;
use App\Http\Controllers\SupplierFormController;
use App\Http\Controllers\TcController;
use App\Http\Controllers\TcJobController;
use App\Http\Controllers\TrainingExportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// Proses login
Route::resource('mesins', MesinController::class);
Route::resource('users', UserController::class);
Route::resource('customers', CustomerController::class);
Route::resource('formperbaikans', FormFPPController::class);
Route::resource('receivedfpps', FormFPPController::class);
Route::resource('approvedfpps', FormFPPController::class);
Route::resource('tindaklanjuts', FormFPPController::class);
Route::resource('preventives', PreventiveController::class);
Route::resource('spareparts', SparepartController::class);

Route::get('/supplier/form/success', [SupplierFormController::class, 'showSuccessPage'])->name('supplierform.public.success');
Route::get('/supplier/form/{token}', [SupplierFormController::class, 'showPublicForm'])->name('supplierform.public.show');
Route::post('/supplier/form/create', [SupplierFormController::class, 'storePublicForm'])->name('supplierform.public.store');
Route::get('/download/template-rek', [SupplierFormController::class, 'downloadTemplateRek'])->name('download.template.rek');

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');

// Public debug route (no auth) - temporary
Route::get('debug-visits-public', function () {
    $approvals = \App\Models\MstPositionApproval::where('approver_position_id', 65)
        ->orWhere('position_id', 65)
        ->get();

    $allApprovals = \App\Models\MstPositionApproval::all();

    return response()->json([
        'approvals_for_dept_head_65' => $approvals,
        'all_approvals_count' => $allApprovals->count(),
        'all_approvals' => $allApprovals,
    ]);
});
Route::post('/login', [AuthController::class, 'login'])->name('login_post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('full-calender', [EventController::class, 'blokMaintanence'])->name('blokMaintanence');
    Route::get('/admin/layout-menu', [LayoutMenuController::class, 'edit'])->name('layout-menu.edit');
    Route::post('/admin/layout-menu', [LayoutMenuController::class, 'update'])->name('layout-menu.update');
    Route::get('/admin/layout-menu/json', [LayoutMenuController::class, 'index'])->name('layout-menu.index');
    Route::get('/admin/layout-editor', [LayoutEditorController::class, 'edit'])->name('layout-editor.edit');
    Route::post('/admin/layout-editor', [LayoutEditorController::class, 'update'])->name('layout-editor.update');
    Route::get('full-calenderDept', [EventController::class, 'blokDeptMaintenance'])->name('blokDeptMaintenance');

    Route::post('full-calender-AJAX', [EventController::class, 'ajax']);
    Route::get('generate-pdf/{mesin}', 'App\Http\Controllers\PDFController@generatePDF')->name('pdf.mesin');
    Route::get('dashboardMaintenance', [EventController::class, 'dashboardMaintenance'])->name('dashboardMaintenance');

    // Change Pass
    Route::get('/showDataDiri', 'App\Http\Controllers\AuthController@showDataDiri')->name('showDataDiri');
    Route::post('/ubahPassword', 'App\Http\Controllers\AuthController@ubahPassword')->name('ubahPassword');
    Route::post('/ubahDataDiri', 'App\Http\Controllers\AuthController@ubahDataDiri')->name('ubahDataDiri');

    //printpdf
    Route::get('/edit-evaluasi/{id}', 'App\Http\Controllers\AuthController@showEvaluasiPDF')->name('export-pdf');

    // Admin
    Route::get('dashboardusers', [UserController::class, 'index'])->name('dashboardusers');
    Route::get('dashboardcustomers', [CustomerController::class, 'index'])->name('dashboardcustomers');

    // Preventive
    Route::get('dashpreventive', [PreventiveController::class, 'maintenanceDashPreventive'])
        ->name('maintenance.dashpreventive');
    Route::get('deptmtcepreventive', [PreventiveController::class, 'deptmtceDashPreventive'])
        ->name('deptmtce.dashpreventive');
    Route::get('deptmtce/editpreventive/{mesin}', [PreventiveController::class, 'EditDeptMTCEPreventive'])
        ->name('deptmtce.editpreventive');

    // Production
    Route::get('dashboardproduction', [FormFPPController::class, 'DashboardProduction'])->name('fpps.index');
    Route::post('store', [FormFPPController::class, 'store'])->name('formperbaikans.store');
    Route::get('historyfpp', [FormFPPController::class, 'HistoryFPP'])->name('fpps.history');
    Route::get('lihatform/{formperbaikan}', [FormFPPController::class, 'LihatFPP'])
        ->name('fpps.show');
    Route::get('closedform/{formperbaikan}', [FormFPPController::class, 'ClosedFormProduction'])
        ->name('fpps.closed');

    // Maintenance
    Route::get('dashboardmaintenance', [FormFPPController::class, 'DashboardMaintenance'])
        ->name('maintenance.index');
    Route::get('dashboardmaintenancega', [FormFPPController::class, 'DashboardMaintenanceGA'])
        ->name('ga.dashboardga');
    Route::get('lihatmaintenance/{formperbaikan}', [FormFPPController::class, 'LihatMaintenance'])
        ->name('maintenance.lihat');
    Route::get('editmaintenance/{formperbaikan}', [FormFPPController::class, 'EditMaintenance'])
        ->name('maintenance.edit');
    Route::get('preventives/edit-issue/{preventive}', [PreventiveController::class, 'editIssue'])
        ->name('preventives.editpreventive');
    Route::get('preventives/lihat-issue/{preventive}', [PreventiveController::class, 'lihatIssue'])
        ->name('preventives.lihatpreventive');
    Route::put('preventives/update-issue/{preventive}', [PreventiveController::class, 'updateIssue'])
        ->name('preventives.updateIssue');

    Route::get('dashboardmesins', [MesinController::class, 'index'])->name('dashboardmesins');
    Route::get('dashboardgamesin', [MesinController::class, 'dashboardGAMesin'])->name('dashboardgamesin');
    Route::get('/mesins/showMesinGA/{mesin}', [MesinController::class, 'showMesinGA'])->name('mesins.showMesinGA');

    // Dept Maintenance
    Route::get('dashboarddeptmtce', [FormFPPController::class, 'DashboardDeptMTCE'])
        ->name('deptmtce.index');
    Route::get('dashboardapprovedga', [FormFPPController::class, 'DashboardFPPGA'])
        ->name('ga.approvedfpp');
    Route::get('lihatdeptmtce/{formperbaikan}', [FormFPPController::class, 'LihatDeptMTCE'])
        ->name('deptmtce.show');
    Route::get('editdeptmtcepreventive/{mesin}', [PreventiveController::class, 'EditDeptMTCEPreventive'])
        ->name('deptmtce.lihatpreventive');
    Route::get('dashboardPreventive', [PreventiveController::class, 'dashboardPreventive'])->name('dashboardPreventive');
    Route::get('dashboardPreventiveMaintenance', [PreventiveController::class, 'dashboardPreventiveMaintenance'])->name('dashboardPreventiveMaintenance');
    Route::get('dashboardPreventiveMaintenanceGA', [PreventiveController::class, 'dashboardPreventiveMaintenanceGA'])->name('dashboardPreventiveMaintenanceGA');
    Route::get('formpreventif', [PreventiveController::class, 'create'])->name('preventives.create');
    Route::get('editpreventive', [PreventiveController::class, 'edit'])->name('preventives.edit');
    Route::post('sparepart-import', [SparepartController::class, 'import'])->name('spareparts.import');
    Route::get('/spareparts/export/{nomor_mesin}', [SparepartController::class, 'export'])->name('spareparts.export');

    Route::put('/update-preventive', [PreventiveController::class, 'update'])->name('updatePreventive');

    // Sales
    Route::get('dashboardfppsales', [FormFPPController::class, 'DashboardFPPSales'])
        ->name('sales.index');
    Route::get('historysales', [FormFPPController::class, 'HistorySales'])
        ->name('sales.history');
    Route::get('lihatfppsales/{formperbaikan}', [FormFPPController::class, 'LihatFPPSales'])
        ->name('sales.lihat');

    // CRM Report (Sales submenu)
    Route::get('sales/crm-report', [\App\Http\Controllers\SalesVisitController::class, 'crmReport'])->name('sales.crm_report');
    Route::post('sales/crm-report/data', [\App\Http\Controllers\SalesVisitController::class, 'getDetailData'])->name('sales.crm.data');
    Route::get('sales/crm-report/export', [\App\Http\Controllers\SalesVisitController::class, 'exportExcel'])->name('sales.crm.export');

    // CRP
    Route::get('/crp', [CrpController::class, 'index'])->name('crp');
    Route::get('/crp/create', [CrpController::class, 'create'])->name('crp.create');
    Route::post('/crp/store', [CrpController::class, 'store'])->name('crp.store');
    Route::get('/crp/edit/{id}', [CrpController::class, 'edit'])->name('crp.edit');
    Route::put('/crp/update/{id}', [CrpController::class, 'update'])->name('crp.update');
    Route::post('/crp/delete', [CrpController::class, 'delete'])->name('crp.deletePermanen');
    Route::post('/crp/delete-detail', [CrpController::class, 'deleteDetail'])->name('crp.deletePermanenDetail');
    Route::post('/crp/save-detail', [CrpController::class, 'savedetail'])->name('crp.savedetail');
    Route::get('/export-mst-actual', [CrpController::class, 'exportMstActual'])->name('export.mst.actual');
    Route::get('/crp/showDetailModal/{crpId}', [CrpController::class, 'showDetailModal'])->name('crp.showDetailModal');
    Route::post('/crp/saveDetails', [CrpController::class, 'saveCrpDetails'])->name('crp.saveDetails');

    // Download File
    Route::get('download-excel/{tindaklanjut}', [FormFPPController::class, 'downloadAttachment'])->name('download.attachment');
    // DashboardforALL
    // Debug route to inspect current authenticated user
    Route::get('debug-auth', function () {
        return response()->json(['user' => auth()->user()]);
    })->name('debug.auth');

    // Debug: sample visits with related user
    Route::get('debug-visits', function () {
        $rows = App\Models\LogbookVisits::with('user')->limit(10)->get()->map(function ($v) {
            return [
                'id' => $v->id,
                'id_user' => $v->id_user,
                'user_name' => $v->user ? $v->user->name : null,
                'customer' => $v->customer_name,
                'visit_date' => (string) $v->visit_date,
            ];
        });

        return response()->json(['data' => $rows]);
    })->name('debug.visits');

    Route::get('/dashboardHandling', 'App\Http\Controllers\DsController@dashboardHandling')->name('dashboardHandling');
    Route::get('/dashboardMaintenance', 'App\Http\Controllers\DsController@dashboardMaintenance')->name('dashboardMaintenance');
    Route::get('/dshandling', 'App\Http\Controllers\DsController@dshandling')->name('dshandling');
    Route::get('/getChartData', 'App\Http\Controllers\HandlingController@getChartData')->name('getChartData');
    Route::get('/get-data-by-year', 'App\Http\Controllers\HandlingController@getDataByYear')->name('getDataByYear');
    Route::get('/api/filter-pie-chart-tipe', 'App\Http\Controllers\HandlingController@FilterPieChartTipe')->name('FilterPieChartTipe');
    Route::get('/api/filter-tipe-all', 'App\Http\Controllers\HandlingController@FilterTipeAll');
    Route::get('/api/FilterPieChartProses', 'App\Http\Controllers\HandlingController@FilterPieChartProses')->name('FilterPieChartProses');
    Route::get('/api/filterPieChartNG', [HandlingController::class, 'filterPieChartNG'])->name('filterPieChartNG');
    Route::get('/api/getChartStatusHandling', 'App\Http\Controllers\HandlingController@getChartStatusHandling')->name('getChartStatusHandling');
    Route::get('/export-handlings', 'App\Http\Controllers\HandlingController@export')->name('export.handlings');
    Route::get('/export-detail-crp', [CrpController::class, 'exportdetailcrp'])->name('export.detailcrp');
    Route::post('/import-detail-crp', [CrpController::class, 'importCrpDetail'])->name('import.detailcrp');

    // Grafik Repair Maintenance
    // Route::get('/getRepairMaintenance', 'App\Http\Controllers\MaintenanceController@getRepairMaintenance')->name('getRepairMaintenance');
    Route::get('/getMaintenanceData', 'App\Http\Controllers\MaintenanceController@getMaintenanceData')->name('getMaintenanceData');
    Route::get('/getMaintenanceDataAlat', 'App\Http\Controllers\MaintenanceController@getMaintenanceDataAlat')->name('getMaintenanceDataAlat');
    Route::get('/getRepairAlatBantu', 'App\Http\Controllers\MaintenanceController@getRepairAlatBantu')->name('getRepairAlatBantu');
    // Route::get('/getPeriodeWaktuPengerjaan', 'App\Http\Controllers\MaintenanceController@getPeriodeWaktuPengerjaan')->name('getPeriodeWaktuPengerjaan');
    Route::get('/getPeriodeWaktuAlat', 'App\Http\Controllers\MaintenanceController@getPeriodeWaktuAlat')->name('getPeriodeWaktuAlat');
    Route::get('/getPeriodeMesin', 'App\Http\Controllers\MaintenanceController@getPeriodeMesin')->name('getPeriodeMesin');
    Route::get('/getPeriodeAlat', 'App\Http\Controllers\MaintenanceController@getPeriodeAlat')->name('getPeriodeAlat');

    Route::get('handling', [HandlingController::class, 'index'])->name('index');
    Route::get('create', [HandlingController::class, 'create'])->name('create');
    Route::post('store', [HandlingController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [HandlingController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [HandlingController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [HandlingController::class, 'delete'])->name('delete');
    Route::patch('/changeStatus/{id}', [HandlingController::class, 'changeStatus'])->name('changeStatus');
    Route::get('/showHistory/{id}', [HandlingController::class, 'showHistory'])->name('showHistory');

    // deptMan
    Route::get('/deptMan', 'App\Http\Controllers\DeptManController@submission')->name('submission');
    Route::get('/showConfirm/{id}', 'App\Http\Controllers\DeptManController@showConfirm')->name('showConfirm');
    Route::put('/updateConfirm/{id}', 'App\Http\Controllers\DeptManController@updateConfirm')->name('updateConfirm');
    Route::get('/showFollowUp/{id}', 'App\Http\Controllers\DeptManController@showFollowUp')->name('showFollowUp');
    Route::get('/showHistoryProgres/{id}', 'App\Http\Controllers\DeptManController@showHistoryProgres')->name('showHistoryProgres');
    Route::put('/updateFollowUp/{id}', 'App\Http\Controllers\DeptManController@updateFollowUp')->name('updateFollowUp');
    Route::get('scheduleVisit', 'App\Http\Controllers\DeptManController@scheduleVisit')->name('scheduleVisit');
    Route::get('showHistoryCLaimComplain', 'App\Http\Controllers\DeptManController@showHistoryCLaimComplain')->name('showHistoryCLaimComplain');
    Route::get('/showCloseProgres/{id}', 'App\Http\Controllers\DeptManController@showCloseProgres')->name('showCloseProgres');
    // Route untuk update notes berdasarkan due_date
    Route::post('/update-notes', 'App\Http\Controllers\DeptManController@updateNotes')->name('schedule.updateNotes');

    // SS
    Route::get('/showSS', 'App\Http\Controllers\SumbangSaranController@showSS')->name('showSS');
    Route::get('/showSS/data', 'App\Http\Controllers\SumbangSaranController@showSSData')->name('showSS.data');
    Route::get('/dashboardSS', 'App\Http\Controllers\SumbangSaranController@dashboardSS')->name('dashboardSS');
    Route::get('/forumSS', 'App\Http\Controllers\SumbangSaranController@forumSS')->name('forumSS');

    Route::get('/chartSection', 'App\Http\Controllers\SumbangSaranController@chartSection')->name('chartSection');
    Route::post('/chartEmployee', 'App\Http\Controllers\SumbangSaranController@chartEmployee')->name('chartEmployee');
    Route::post('/chartUser', 'App\Http\Controllers\SumbangSaranController@chartUser')->name('chartUser');
    Route::post('/chartMountEmployee', 'App\Http\Controllers\SumbangSaranController@chartMountEmployee')->name('chartMountEmployee');

    Route::post('/export-konfirmasi-hrga', 'App\Http\Controllers\SumbangSaranController@exportKonfirmasiHRGA')->name('export-konfirmasi-hrga');
    Route::post('/update-status-to-bayar', 'App\Http\Controllers\SumbangSaranController@updateStatusToBayar')->name('updateStatusToBayar');

    Route::get('/showKonfirmasiForeman', 'App\Http\Controllers\SumbangSaranController@showKonfirmasiForeman')->name('showKonfirmasiForeman');
    Route::get('/showKonfirmasiDeptHead', 'App\Http\Controllers\SumbangSaranController@showKonfirmasiDeptHead')->name('showKonfirmasiDeptHead');
    Route::get('/showKonfirmasiKomite', 'App\Http\Controllers\SumbangSaranController@showKonfirmasiKomite')->name('showKonfirmasiKomite');
    Route::get('/showKonfirmasiHRGA', 'App\Http\Controllers\SumbangSaranController@showKonfirmasiHRGA')->name('showKonfirmasiHRGA');

    Route::post('/simpanSS', 'App\Http\Controllers\SumbangSaranController@simpanSS')->name('simpanSS');
    Route::post('/simpanPenilaian', 'App\Http\Controllers\SumbangSaranController@simpanPenilaian')->name('simpanPenilaian');
    Route::post('/submitnilai', 'App\Http\Controllers\SumbangSaranController@submitNilai')->name('submitnilai');
    Route::post('/submitTambahNilai', 'App\Http\Controllers\SumbangSaranController@submitTambahNilai')->name('submitTambahNilai');
    Route::post('/sumbangsaran/like/{id}', 'App\Http\Controllers\SumbangSaranController@like')->name('sumbangsaran.like');
    Route::post('/sumbangsaran/unlike/{id}', 'App\Http\Controllers\SumbangSaranController@unlike')->name('sumbangsaran.unlike');

    Route::get('/editSS/{id}', [SumbangSaranController::class, 'editSS'])->name('editSS');
    Route::post('/updateSS/{id}', [SumbangSaranController::class, 'updateSS']);

    Route::get('/getPenilaians/{id}', [SumbangSaranController::class, 'getPenilaians'])->name('getPenilaians');
    Route::get('/getNilai/{id}', [SumbangSaranController::class, 'getNilai'])->name('getNilai');
    Route::get('/getTambahNilai/{id}', [SumbangSaranController::class, 'getTambahNilai'])->name('getTambahNilai');
    Route::get('/file/download/{filename}', [SumbangSaranController::class, 'downloadFile'])->name('file.download');

    Route::delete('/delete-ss/{id}', [SumbangSaranController::class, 'deleteSS'])->name('deleteSS');
    Route::post('/kirim-ss/{id}', [SumbangSaranController::class, 'kirimSS'])->name('kirimSS');
    Route::post('/kirim-ss2/{id}', [SumbangSaranController::class, 'kirimSS2'])->name('kirimSS2');
    Route::get('/sumbangsaran/{id}', 'App\Http\Controllers\SumbangSaranController@getSumbangSaran')->name('sumbangsaran.show');
    Route::get('/secHead/{id}', 'App\Http\Controllers\SumbangSaranController@showSecHead')->name('sechead.show');

    // Safety Patrol
    Route::get('listpatrol', [SafetyController::class, 'listSafetyPatrol'])->name('listpatrol');
    Route::get('listpatrolpic', [SafetyController::class, 'listSafetyPatrolPIC'])->name('listpatrolpic');
    Route::get('reportpatrol', [SafetyController::class, 'reportPatrol'])->name('reportpatrol');
    Route::get('buatsafetypatrol', [SafetyController::class, 'buatFormSafety'])->name('patrols.buatFormSafety');
    Route::post('simpanPatrol', [SafetyController::class, 'simpanPatrol'])->name('patrols.simpanPatrol');
    Route::get('detailPatrol/{patrol}', [SafetyController::class, 'detailPatrol'])->name('patrols.detailPatrol');
    Route::get('/get-pic-area', [SafetyController::class, 'getPICArea']);
    Route::get('/get-area-patrol', [SafetyController::class, 'getAreaPatrol']);
    Route::get('/get-kategori-patrol', [SafetyController::class, 'getKategoriPatrol']);
    Route::get('/get-safety-patrol', [SafetyController::class, 'getSafetyPatrol']);
    Route::get('/get-lingkungan-patrol', [SafetyController::class, 'getLingkunganPatrol']);
    Route::post('export-patrol-data', [SafetyController::class, 'exportData'])->name('export-patrol-data');

    // WO Heat Treatment
    Route::get('dashboardImportWO', [HeatTreatmentController::class, 'dashboardImportWO'])
        ->name('dashboardImportWO');
    Route::get('dashboardTracingWO', [HeatTreatmentController::class, 'dashboardTracingWO'])
        ->name('dashboardTracingWO');
    Route::get('landingWO', [HeatTreatmentController::class, 'landingWO'])
        ->name('landingWO');
    Route::get('/filter-wo', [HeatTreatmentController::class, 'filterWO'])->name('filter-wo');
    Route::post('importWO', [HeatTreatmentController::class, 'WOHeat'])->name('importWO');
    Route::get('/searchWO', [HeatTreatmentController::class, 'searchWO'])->name('searchWO');
    Route::get('downtimeExport', [FormFPPController::class, 'downtimeExport']);
    Route::get('/getBatchData', [HeatTreatmentController::class, 'getBatchData'])->name('getBatchData');

    // Inquiry Sales Local
    Route::get('createinquiry', [InquirySalesController::class, 'createInquirySales'])->name('createinquiry');
    Route::get('createinquiry1{id}', [InquirySalesController::class, 'createInquirySales1'])->name('createinquiry1');
    Route::get('formulirInquiry/{id}', [InquirySalesController::class, 'formulirInquiry'])->name('formulirInquiry');
    // Route::get('tindakLanjutInquiry/{id}', [InquirySalesController::class, 'tindakLanjutInquiry'])->name('tindakLanjutInquiry');
    Route::get('showFormSS/{id}', [InquirySalesController::class, 'showFormSS'])->name('showFormSS');
    Route::post('/inquiry/approve/{id}', [InquirySalesController::class, 'approveKaSie'])->name('approveKaSie');
    Route::get('/inquiry/approval', [InquirySalesController::class, 'showApprovalKaSie'])->name('showApprovalKaSie');
    Route::post('/inquiry/reject/{id}', [InquirySalesController::class, 'rejectKaSie'])->name('rejectKaSie');
    Route::get('/inquiry/approval-ka-dept', [InquirySalesController::class, 'showApprovalKaDept'])->name('showApprovalKaDept');
    Route::post('/inquiry/approve-ka-dept/{id}', [InquirySalesController::class, 'approveKaDept'])->name('approveKaDept');
    Route::post('/inquiry/reject-ka-dept/{id}', [InquirySalesController::class, 'rejectKaDept'])->name('rejectKaDept');
    Route::get('/inquiry/overview-purchase', [InquirySalesController::class, 'overviewPurchase'])->name('overviewPurchase');
    Route::get('/inquiry/overview-purchase-2', [InquirySalesController::class, 'overviewPurchase2'])->name('overviewPurchase2');
    Route::get('/inquiry/overview-purchase/data', [InquirySalesController::class, 'overviewPurchaseData'])->name('overviewPurchase.data');
    Route::post('/inquiry/overview-purchase/export', [InquirySalesController::class, 'exportOverviewPurchase'])->name('overviewPurchase.export');
    Route::get('/inquiry/overview-purchase/export/date-range', [InquirySalesController::class, 'exportOverviewPurchaseByDate'])->name('overviewPurchase.exportByDate');
    Route::post('/inquiry/confirm-purchase/{id}', [InquirySalesController::class, 'confirmPurchase'])->name('confirmPurchase');
    Route::post('/inquiry/detail-status', [InquirySalesController::class, 'updateDetailStatuses'])->name('inquiry.detail-status');
    Route::post('/inquiry/detail-po', [InquirySalesController::class, 'updateDetailPo'])->name('inquiry.detail-po');
    Route::post('/inquiry/update', [InquirySalesController::class, 'updateInquiry'])->name('updateInquiry');
    Route::post('/inquiry/upload-file', [InquirySalesController::class, 'uploadFile'])->name('uploadFile');
    Route::post('/save-inquiry', [InquirySalesController::class, 'saveInquiry'])->name('saveInquiry');
    Route::get('/inquiry/overview-inquiry', [InquirySalesController::class, 'overviewInquiry'])->name('overviewInquiry');
    // Route::post('/inquiry/confirm-purchase/{id}', [InquirySalesController::class, 'confirmPurchase'])->name('confirmPurchase');
    // Route::post('/inquiry/progress', [InquirySalesController::class, 'storeProgressPurchase'])->name('storeProgressPurchase');
    Route::post('/inquiry/finish/{id}', [InquirySalesController::class, 'finishInquiry'])->name('finishInquiry');
    Route::post('/delete-file', [InquirySalesController::class, 'deleteFile'])->name('deleteFile');
    // Route::get('/inquiry/progress-history/{id}', [InquirySalesController::class, 'showProgressHistory'])->name('progressHistory');
    Route::get('/inquiry/progress-history/{id}', [InquirySalesController::class, 'showProgressHistory'])->name('progressHistory');
    Route::post('/inquiry/update-supplier/{id}', [InquirySalesController::class, 'updateSupplier'])->name('updateSupplier');
    Route::get('/inquiry/approval-inventory', [InquirySalesController::class, 'showApprovalInventory'])->name('showApprovalInventory');
    Route::post('/inquiry/approve-inventory/{id}', [InquirySalesController::class, 'approveInventory'])->name('approveInventory');
    Route::post('/inquiry/reject-inventory/{id}', [InquirySalesController::class, 'rejectInventory'])->name('rejectInventory');
    Route::get('/showFormSS/pdf/{id}', [InquirySalesController::class, 'generatePDF'])->name('showFormSS.pdf');
    // Route::get('historyFormSS/{id}', [InquirySalesController::class, 'historyFormSS'])->name('historyFormSS');
    Route::post('/inquiry/updateOverviewPurchase', [InquirySalesController::class, 'updateOverviewPurchase'])->name('updateOverviewPurchase');

    // Inquiry Order Import
    Route::get('createinquiryImport', [InquirySalesController::class, 'createInquirySalesImport'])->name('createinquiryImport');
    Route::get('createinquiryImport1/{id}', [InquirySalesController::class, 'createInquirySalesImport1'])->name('createinquiryImport1');
    Route::post('storeinquiryImport', [InquirySalesController::class, 'storeInquiryImport'])->name('storeinquiryImport');
    Route::get('showFormSSimport/{id}', [InquirySalesController::class, 'showFormSSimport'])->name('showFormSSimport');
    Route::get('showFormSSimportinventory/{id}', [InquirySalesController::class, 'showFormSSimportinventory'])->name('showFormSSimportinventory');
    Route::get('formulirInquiryimport/{id}', [InquirySalesController::class, 'formulirInquiryImport'])->name('formulirInquiryimport');
    Route::post('/inquiry/updateImport', [InquirySalesController::class, 'updateInquiryImport'])->name('updateInquiryImport');
    Route::post('/inquiry/previewSSImport', [InquirySalesController::class, 'previewSSImport'])->name('inquiry.previewSSImport');
    Route::delete('/deleteInquiryDetailImport/{id}', [InquirySalesController::class, 'deleteInquiryDetailImport'])->name('deleteInquiryDetailImport');
    Route::delete('/deleteInquiryDetailImportpermanen/{id}', [InquirySalesController::class, 'deleteInquiryDetailImportpermanen'])->name('deleteInquiryDetailImportpermanen');
    Route::delete('/deleteInquiryDetail/{id}', [InquirySalesController::class, 'deleteInquiryDetail'])->name('deleteInquiryDetail');
    Route::put('updateInquiryDetailsImport/{id}', [InquirySalesController::class, 'updateInquiryDetailsImport'])->name('updateInquiryDetailsImport');
    Route::get('/editimport/{id}', [InquirySalesController::class, 'editimport'])->name('editimport');
    Route::put('/updateimport/{id}', [InquirySalesController::class, 'updateImport'])->name('inquiry.update');
    Route::get('/inquiry/overview-purchase-import', [InquirySalesController::class, 'showApprovalPurchaseImport'])->name('overviewPurchaseImport');
    Route::post('/inquiry/export-purchasing-import', [InquirySalesController::class, 'exportOverviewPurchasecustom'])->name('exportpurchaseimportcustom');
    Route::get('/export-excel', [InquirySalesController::class, 'exportexceloverviewimportpurchase'])->name('exportExcelimportpurchase');
    Route::post('/import-excel-purchase', [InquirySalesController::class, 'importexceloverviewimportpurchase'])->name('importExcelimportpurchase');
    Route::get('/inquiry/overview-inquiry-import', [InquirySalesController::class, 'overviewInquiryImport'])->name('overviewInquiryImport');
    Route::get('/export-inquiries', [InquirySalesController::class, 'exportInquiries'])->name('exportInquiries');
    Route::post('/import/inquiry', [InquirySalesController::class, 'importinquiryinventory'])->name('import.inquiry');
    Route::post('/import/inquirypurchase', [InquirySalesController::class, 'importinquirypurchase'])->name('import.purchaseimport');
    Route::get('inquiry/export', [InquirySalesController::class, 'exportinquirypurchaseimport'])->name('exportInquiryimportpurchase');
    Route::post('/confirm-purchase-import', [InquirySalesController::class, 'confirmpurchaseimport'])->name('confirmPurchaseimport');
    Route::post('/inquiry/approveimport/{id}', [InquirySalesController::class, 'approveKaSieImport'])->name('approveKaSieImport');
    Route::get('/inquiry/approvalimport', [InquirySalesController::class, 'showApprovalKaSieImport'])->name('showApprovalKaSieImport');
    Route::post('/inquiry/rejectimport/{id}', [InquirySalesController::class, 'rejectKaSieImport'])->name('rejectKaSieImport');
    Route::get('/inquiry/approval-ka-deptimport', [InquirySalesController::class, 'showApprovalKaDeptImport'])->name('showApprovalKaDeptImport');
    Route::post('/inquiry/approve-ka-deptimport/{id}', [InquirySalesController::class, 'approveKaDeptImport'])->name('approveKaDeptImport');
    Route::post('/inquiry/reject-ka-deptimport/{id}', [InquirySalesController::class, 'rejectKaDeptImport'])->name('rejectKaDeptImport');
    Route::get('/inquiry/approval-inventoryimport', [InquirySalesController::class, 'showApprovalInventoryImport'])->name('showApprovalInventoryImport');
    Route::post('/inquiry/approve-inventoryimport/{id}', [InquirySalesController::class, 'approveInventoryImport'])->name('approveInventoryImport');
    Route::post('/inquiry/reject-inventoryimport/{id}', [InquirySalesController::class, 'rejectInventoryImport'])->name('rejectInventoryImport');
    Route::post('/inquiry/update-progress-import/{id}', [InquirySalesController::class, 'updateProgressImport'])->name('updateProgressImport');
    Route::post('/inquiry/finish-import', [InquirySalesController::class, 'finishInquiryimport'])->name('finishInquiryimport');

    Route::get('/inquiry/form-purchase-import/{month}/{klasifikasi}', [InquirySalesController::class, 'generatePDFimportMulti'])->name('generatePDFimport.multi');
    Route::get('/showFormSSimport/pdf/{id}', [InquirySalesController::class, 'generatePDFimport'])->name('showFormSSimport.pdf');
    Route::get('/inquiry/form-import/{month}/{klasifikasi}', [InquirySalesController::class, 'showFormSSimportpurchase'])->name('showFormSSimportpurchase');

    Route::post('/inquiry/update-details/{id}', [InquirySalesController::class, 'updateInquiryDetails'])->name('updateInquiryDetails');
    Route::get('konfirmInquiry', [InquirySalesController::class, 'konfirmInquiry'])->name('konfirmInquiry');
    Route::get('validasiInquiry', [InquirySalesController::class, 'validasiInquiry'])->name('validasiInquiry');
    Route::get('reportInquiry', [InquirySalesController::class, 'reportInquiry'])->name('reportInquiry');
    Route::post('storeinquiry', [InquirySalesController::class, 'storeInquirySales'])->name('storeinquiry');

    Route::post('/inquiry/previewSS', [InquirySalesController::class, 'previewSS'])->name('inquiry.previewSS');
    Route::post('/inquiry/tindakLanjutInquiry', [InquirySalesController::class, 'saveTindakLanjut'])->name('inquiry.tindakLanjutInquiry');
    Route::put('/inquiry/{id}', [InquirySalesController::class, 'update'])->name('updateinquiry');
    Route::get('/editInquiry/{id}', [InquirySalesController::class, 'editInquiry'])->name('editInquiry');
    Route::delete('/deleteinquiry/{id}', [InquirySalesController::class, 'delete'])->name('deleteinquiry');

    Route::get('/export-inquiry', [InquirySalesController::class, 'exportInquiry'])->name('export.inquiry');

    //import administration
    route::get('/import-administration', [ImportAdministrationController::class, 'showcreate'])->name('createadministration');
    route::post('/import-administration/store', [ImportAdministrationController::class, 'store'])->name('storeadministration');
    route::get('/import-administration/Showformadm/{id}', [ImportAdministrationController::class, 'showformadm'])->name('dokumenadministration');
    Route::post('/admin/{adminId}/upload', [ImportAdministrationController::class, 'uploadFiles'])->name('uploadFiles');
    Route::get('/admin/{adminId}/download', [ImportAdministrationController::class, 'downloadFiles'])->name('downloadFiles');
    Route::post('/admin/{adminId}/approve', [ImportAdministrationController::class, 'approve'])->name('approve');
    Route::post('/admin/{adminId}/reject', [ImportAdministrationController::class, 'reject'])->name('reject');
    Route::put('/admin/{adminId}/update', [ImportAdministrationController::class, 'updateAdmin'])->name('updateAdmin');
    Route::post('/admin/delete-file', [ImportAdministrationController::class, 'deleteFile'])->name('deleteFile');

    // km
    Route::get('/km', [KmPengajuanController::class, 'pengajuanKM'])->name('pengajuanKM');
    Route::get('/dsKnowlege', [KmPengajuanController::class, 'dsKnowlege'])->name('dsKnowlege');
    Route::get('/persetujuanKM', [KmPengajuanController::class, 'persetujuanKM'])->name('persetujuanKM');
    Route::get('/km/documents/{kmPengajuan}/preview', [KmPengajuanController::class, 'preview'])
        ->name('km.documents.preview');
    Route::get('/km/documents/{kmPengajuan}/download', [KmPengajuanController::class, 'download'])
        ->name('km.documents.download');
    Route::get('/km/documents/{kmPengajuan}/versions', [\App\Http\Controllers\KnowledgeManagement\KmDocumentVersionController::class, 'index'])
        ->name('km.document-versions.index');
    Route::post('/km/documents/{kmPengajuan}/versions/major', [\App\Http\Controllers\KnowledgeManagement\KmDocumentVersionController::class, 'storeMajor'])
        ->name('km.document-versions.major.store');
    Route::post('/km/documents/{kmPengajuan}/versions/minor', [\App\Http\Controllers\KnowledgeManagement\KmDocumentVersionController::class, 'storeMinor'])
        ->name('km.document-versions.minor.store');
    Route::get('/km/documents/{kmPengajuan}/versions/{version}/preview', [\App\Http\Controllers\KnowledgeManagement\KmDocumentVersionController::class, 'preview'])
        ->whereNumber('version')->name('km.document-versions.preview');
    Route::get(
        '/km/documents/{kmPengajuan}/versions/{version}/thumbnail',
        \App\Http\Controllers\KnowledgeManagement\KmDocumentVersionThumbnailController::class,
    )->whereNumber('version')->name('km.document-versions.thumbnail');
    Route::post('/km/document-versions/{version}/recover-original', [\App\Http\Controllers\KnowledgeManagement\KmDocumentVersionController::class, 'recover'])
        ->whereNumber('version')->name('km.document-versions.recover');
    Route::post('/km/approvals/bulk', [KmPengajuanController::class, 'bulkApprove'])
        ->name('km.approvals.bulk');
    Route::get('/km/admin/access', [\App\Http\Controllers\KnowledgeManagement\KmAccessRuleController::class, 'index'])
        ->name('km.access-rules.index');
    Route::post('/km/admin/access', [\App\Http\Controllers\KnowledgeManagement\KmAccessRuleController::class, 'store'])
        ->name('km.access-rules.store');
    Route::delete('/km/admin/access/{accessRule}', [\App\Http\Controllers\KnowledgeManagement\KmAccessRuleController::class, 'destroy'])
        ->name('km.access-rules.destroy');
    Route::get('/km/admin/compliance', [\App\Http\Controllers\KnowledgeManagement\KmComplianceController::class, 'index'])
        ->name('km.compliance.index');
    Route::post('/km/admin/compliance/assignments', [\App\Http\Controllers\KnowledgeManagement\KmComplianceController::class, 'store'])
        ->name('km.compliance.assignments.store');
    Route::post('/km/admin/compliance/versions/{version}/users/{user}/override', [\App\Http\Controllers\KnowledgeManagement\KmComplianceController::class, 'override'])
        ->name('km.compliance.override');
    Route::post('/km/admin/compliance/recipients/{assignmentUser}/exempt', [\App\Http\Controllers\KnowledgeManagement\KmComplianceController::class, 'exempt'])
        ->name('km.compliance.exempt');
    Route::get('/km/admin/compliance/export/{format}', [\App\Http\Controllers\KnowledgeManagement\KmComplianceExportController::class, 'details'])
        ->whereIn('format', ['xlsx', 'csv'])->name('km.compliance.export.details');
    Route::get('/km/admin/compliance/export-pdf', [\App\Http\Controllers\KnowledgeManagement\KmComplianceExportController::class, 'pdf'])
        ->name('km.compliance.export.pdf');
    Route::get('/km/profile/recognition-certificate', [\App\Http\Controllers\KnowledgeManagement\KmRecognitionController::class, 'certificate'])
        ->name('km.recognition.certificate');
    Route::get('/km/analytics/popular', [KmAnalyticsController::class, 'popular'])
        ->name('km.analytics.popular');
    Route::get('/km/analytics/popular/export/xlsx', [KmAnalyticsController::class, 'exportPopularXlsx'])
        ->name('km.analytics.popular.export.xlsx');
    Route::get('/km/analytics/popular/export/pdf', [KmAnalyticsController::class, 'exportPopularPdf'])
        ->name('km.analytics.popular.export.pdf');
    Route::post('/kmTransaksi/markAsRead', [KmPengajuanController::class, 'markAsRead'])->name('kmTransaksi.markAsRead');
    Route::post('/kmTransaksi/saveTransaction', [KmPengajuanController::class, 'saveTransaction'])->name('kmTransaksi.saveTransaction');

    // fungsi
    Route::post('/km', [KmPengajuanController::class, 'storeKM'])->name('storeKM');
    Route::put('/knowledge-management/update', [KmPengajuanController::class, 'update'])->name('updateKM');
    Route::get('/km/{id}/edit', [KmPengajuanController::class, 'edit'])->name('editKM');

    Route::get('/km/{id}/showPersetujuan', [KmPengajuanController::class, 'showPersetujuan'])->name('showPersetujuan');
    Route::put('/knowledge-management/approveKM', [KmPengajuanController::class, 'approveKM'])->name('approveKM');

    Route::patch('/km/{id}/update-status', [KmPengajuanController::class, 'updateStatus'])->name('updateStatusKM');
    Route::post('/kirimKM/{id}', [KmPengajuanController::class, 'kirimKM'])->name('kirimKM');
    Route::post('/like', [KmPengajuanController::class, 'like'])->name('kmSuka.like');
    Route::post('/unlike', [KmPengajuanController::class, 'unlike'])->name('kmSuka.unlike');
    Route::post('/insights/add', [KmPengajuanController::class, 'addInsight'])
        ->middleware('throttle:km-comments')
        ->name('insights.add');

    // ===== KM Jangka Menengah — Fitur Baru =====

    // Bookmark: simpan/hapus dokumen ke "Baca Nanti"
    Route::post(
        '/km/documents/{kmPengajuan}/bookmarks',
        [\App\Http\Controllers\KnowledgeManagement\KmBookmarkController::class, 'store']
    )->name('km.bookmarks.store');
    Route::delete(
        '/km/documents/{kmPengajuan}/bookmarks',
        [\App\Http\Controllers\KnowledgeManagement\KmBookmarkController::class, 'destroy']
    )->name('km.bookmarks.destroy');

    // Autosave metadata draft (judul, keterangan, tags, co-authors, reading_minutes)
    Route::patch(
        '/km/documents/{kmPengajuan}/autosave',
        \App\Http\Controllers\KnowledgeManagement\KmDocumentAutosaveController::class
    )->name('km.documents.autosave');

    Route::get(
        '/km/co-authors/options',
        \App\Http\Controllers\KnowledgeManagement\KmCoAuthorOptionsController::class
    )->name('km.co-authors.options');

    // Thumbnail — stream PNG privat atau SVG default
    Route::get(
        '/km/documents/{kmPengajuan}/thumbnail',
        \App\Http\Controllers\KnowledgeManagement\KmDocumentThumbnailController::class
    )->name('km.documents.thumbnail');

    Route::get(
        '/km/notifications',
        [\App\Http\Controllers\KnowledgeManagement\KmNotificationController::class, 'index']
    )->name('km.notifications.index');
    Route::post(
        '/km/notifications/{notification}/read',
        [\App\Http\Controllers\KnowledgeManagement\KmNotificationController::class, 'markRead']
    )->whereNumber('notification')->name('km.notifications.read');
    Route::post(
        '/km/notifications/read-all',
        [\App\Http\Controllers\KnowledgeManagement\KmNotificationController::class, 'markAllRead']
    )->name('km.notifications.read-all');

    Route::patch(
        '/km/documents/{kmPengajuan}/progress',
        [KmPengajuanController::class, 'updateProgress']
    )->name('km.reading.progress');

    Route::get(
        '/km/documents/{kmPengajuan}/insights/mention-options',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'mentionOptions']
    )->name('km.insights.mention-options');
    Route::get(
        '/km/documents/{kmPengajuan}/insights',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'index']
    )->name('km.insights.index');
    Route::post(
        '/km/documents/{kmPengajuan}/insights',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'store']
    )->middleware('throttle:km-comments')->name('km.insights.store');
    Route::patch(
        '/km/insights/{insight}',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'update']
    )->middleware('throttle:km-comments')->name('km.insights.update');
    Route::delete(
        '/km/insights/{insight}',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'destroy']
    )->middleware('throttle:km-comments')->name('km.insights.destroy');
    Route::put(
        '/km/insights/{insight}/reaction',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'react']
    )->middleware('throttle:km-reactions')->name('km.insights.reaction.store');
    Route::delete(
        '/km/insights/{insight}/reaction',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'unreact']
    )->middleware('throttle:km-reactions')->name('km.insights.reaction.destroy');
    Route::post(
        '/km/insights/{insight}/feature',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'feature']
    )->name('km.insights.feature');
    Route::delete(
        '/km/insights/{insight}/feature',
        [\App\Http\Controllers\KnowledgeManagement\KmInsightController::class, 'unfeature']
    )->name('km.insights.unfeature');

    // // tc
    // Route::get('/job', [TcJobController::class, 'jobShow'])->name('jobShow');
    Route::get('/tcShow', [TcController::class, 'tcShow'])->name('tcShow');
    Route::get('/tcCreate', [TcController::class, 'createTC'])->name('tcCreate');
    Route::post('/mst_tc/store', [TcController::class, 'storeTC'])->name('mst_tc.store');

    Route::get('mst_tc/{id}/edit', [TcController::class, 'edit'])->name('mst_tc.edit');
    Route::get('mst_tc/{id}/edit-all', [TcController::class, 'editAll'])->name('mst_tc.edit_all');
    Route::put('mst_tc/{id}/update-all', [TcController::class, 'updateAll'])->name('mst_tc.update_all');
    Route::get('mst_sk/{id}/editSoftSKills', [TcController::class, 'editSoftSKills'])->name('mst_sk.editSoftSKills');
    Route::get('mst_ad/{id}/editAdditionals', [TcController::class, 'editAdditional'])->name('mst_ad.editAdditionals');
    // Route::get('/job-position/{id}/edit', [TcJobController::class, 'getJobPositionData'])->name('getJobPosition');

    // Route::delete('/job-positions/delete-row', [TcJobController::class, 'deleteRow'])->name('jobPositions.deleteRow');

    Route::put('mst_tc/{id}', [TcController::class, 'update'])->name('mst_tc.update');
    Route::put('mst_sk/{id}/updateSoftSkills', [TcController::class, 'updateSoftSkills'])->name('mst_sk.updateSoftSkills');
    Route::put('mst_ad/{id}/updateAdditionals', [TcController::class, 'updateAdditionals'])->name('mst_ad.updateAdditionals');
    Route::get('/employees-by-job-position', [TcController::class, 'fetchEmployeesByJobPosition'])->name('employees.by.job.position');

    Route::get('/summary/index', [TcController::class, 'summaryData'])->name('job.positions.index');
    Route::post('/sumarry/details', [TcController::class, 'fetchDetails'])->name('job.positions.details');
    Route::get('/job/positions/details2/{job_position}', [TcController::class, 'fetchDetails2'])->name('job.positions.details2');

    // Route::get('/users/{userId}/role', [TcJobController::class, 'getUserRole'])->name('users.role');
    // Route::post('/job-positions', [TcJobController::class, 'store'])->name('jobPositions.store');
    // Route::put('/job-positions/{id}', [TcJobController::class, 'updateJob'])->name('jobPositions.update');
    // Route::delete('/job-positions/{id}', [TcJobController::class, 'deleteRow'])->name('jobPositions.destroy');
    Route::delete('/delete-tc-row/{id}', [TcController::class, 'deleteTcRow'])->name('tc.deleteRow');
    Route::delete('/delete-sk-row/{id}', [TcController::class, 'deleteSkRow'])->name('sk.deleteRow');
    Route::delete('/delete-ad-row/{id}', [TcController::class, 'deleteAdRow'])->name('ad.deleteRow');

    //Route untuk menampilkan halaman penilaian (index)
    Route::get('/penilaian', [PenilaianTCController::class, 'indexTrs'])->name('penilaian.index');
    Route::get('/penilaian-dept', [PenilaianTCController::class, 'indexTrs2'])->name('penilaian.index2');
    Route::get('/penilaian-hr', [PenilaianTCController::class, 'indexTrs3'])->name('penilaian.index3');
    Route::get('/penilaian-divhead', [PenilaianTCController::class, 'indexTrs4'])->name('penilaian.index4');
    Route::get('/dashboard-competency', [PenilaianTCController::class, 'dsCompetency'])->name('dsCompetency');
    Route::get('/dashboard-detail-competency', [PenilaianTCController::class, 'dsDetailCompetency'])->name('dsDetailCompetency');

    Route::get('/dashboard-people-development', [PdController::class, 'indexPD'])->name('indexPD');
    Route::get('/dashboard-people-development-hrga', [PdController::class, 'indexPD2'])->name('indexPD2');
    Route::get('/dashboard-histori-development', [PdController::class, 'historiDevelop'])->name('historiDept');

    Route::get('/buat-penilaian', [PenilaianTCController::class, 'createPenilaian'])->name('create.penilaian');
    Route::get('/buat-training', [PdController::class, 'createPD'])->name('createPD');

    Route::get('/get-job-position-data', [PenilaianTCController::class, 'getJobPositionData'])->name('getJobPositionData');
    Route::get('/get-job-position-data-edit', [PenilaianTCController::class, 'getJobPositionDataEdit'])->name('getJobPositionDataEdit');
    Route::get('/get-nilai-data-edit', [PenilaianTCController::class, 'getNilaiDataEdit'])->name('getNilaiDataEdit');
    Route::get('/get-job-point-kategori', [PenilaianTCController::class, 'getJobPointKategori'])->name('getJobPointKategori');

    Route::get('/view-pd/{modified_at}/{tahun_aktual}', [PdController::class, 'viewPD'])->name('viewPD');
    Route::get('/view-pd-HRGA/{tahun_aktual}', [PdController::class, 'viewPD2'])->name('viewPD2');
    Route::get('/export-pd-HRGA/{tahun_aktual}', [PdController::class, 'exportPD2'])->name('exportPD2');

    Route::post('/save-penilaian', [PenilaianTCController::class, 'savePenilaian'])->name('savePenilaian');
    Route::post('/save-pd-pengajuan', [PdController::class, 'savePdPengajuan'])->name('savePdPengajuan');
    Route::post('/save-pd-pengajuan-Dept', [PdController::class, 'savePdPengajuanDept'])->name('savePdPengajuanDept');

    Route::put('/updated-pd-hrga', [PdController::class, 'updatePdPlan'])->name('updatePdPlan');

    Route::post('/update-data', [PdController::class, 'updateData'])->name('updateData');

    Route::put('/updated-pd-hrga2', [PdController::class, 'updatePdPlan2'])->name('updatePdPlan2');
    Route::post('/update-status/{id_job_position}', [PenilaianTCController::class, 'kirimSC'])->name('update.status');
    Route::post('/update-status-dept/{id_job_position}', [PenilaianTCController::class, 'kirimDept'])->name('update.status2');
    Route::post('/update-status-div/{id_job_position}', [PenilaianTCController::class, 'kirimDiv'])->name('update.status3');

    Route::get('/editPdPengajuan/{modified_at}/{tahun_aktual}', [PdController::class, 'editPdPengajuan'])->name('editPdPengajuan');
    Route::get('/editPdPengajuan-HRGA/{tahun_aktual}', [PdController::class, 'editPdPengajuanHRGA'])->name('editPdPengajuanHRGA');

    Route::put('/update-pd', [PdController::class, 'update'])->name('updatePD');
    Route::get('/update-evaluasi/{id}', [PdController::class, 'editEvaluasi'])->name('update-evaluasi');
    Route::put('/update-evaluasi/{id}', [PdController::class, 'updateEvaluasi'])->name('update-evaluasi.update');

    Route::get('/send-pd/{modified_at}/{tahun_aktual}', [PdController::class, 'sendPD'])->name('sendPD');
    Route::get('/send-pd2/{tahun_aktual}', [PdController::class, 'sendPD2'])->name('sendPD2');

    Route::get('/people-development/filter', [PdController::class, 'getFilteredData'])->name('people_development.filter');
    Route::get('/people-development/export/pengajuan', [TrainingExportController::class, 'submissions'])->name('people_development.export.submissions');
    Route::get('/people-development/export/persetujuan', [TrainingExportController::class, 'approvals'])->name('people_development.export.approvals');
    Route::get('/people-development/export/tindak-lanjut/{tahun}', [TrainingExportController::class, 'followUp'])->whereNumber('tahun')->name('people_development.export.follow_up');
    Route::get('/people-development/export/history', [TrainingExportController::class, 'history'])->name('people_development.export.history');
    Route::get('/people-development/export/csv', [TrainingExportController::class, 'historyCsv'])->name('people_development.export.csv');

    Route::get('/trs/edit-penilaian/{id_job_position}', [PenilaianTCController::class, 'editTrs'])->name('penilaian.edit');
    Route::get('/trs/edit-dept/{id_job_position}', [PenilaianTCController::class, 'editTrs2'])->name('penilaian.edit2');
    Route::get('/trs/edit-div/{id_job_position}', [PenilaianTCController::class, 'editTrs3'])->name('penilaian.edit3');
    Route::get('/trs/view-penilaian/{id_job_position}', [PenilaianTCController::class, 'viewTrs'])->name('penilaian.view');
    Route::get('/trs/preview-penilaian/{id_job_position}', [PenilaianTCController::class, 'previewTrs'])->name('penilaian.preview');

    Route::get('/get-edit-Trs', [PenilaianTCController::class, 'getDataTrs'])->name('getDataTrs');

    Route::put('/penilaian/update/{id}', [PenilaianTCController::class, 'updateTrs'])->name('updatePenilaian');
    Route::put('/penilaian/dept/{id}', [PenilaianTCController::class, 'updateTrs2'])->name('updateDept');
    Route::put('/penilaian/div/{id}', [PenilaianTCController::class, 'updateTrs3'])->name('updateDiv');

    Route::put('/update-catatan/{id}', [PenilaianTCController::class, 'updateCatatan'])->name('updateCatatan');

    Route::put('/penilaian/{id}', [PenilaianTCController::class, 'update'])->name('penilaian.update');
    Route::delete('/penilaian/{id}', [PenilaianTCController::class, 'destroy'])->name('penilaian.destroy');

    // [1] Job Position Approval Routes
    // Route::post('/job-positions/{id}/approve', [TcJobController::class, 'approveJobPosition'])->name('jobPositions.approve');
    // Route::get('/job-positions/approvers', [TcJobController::class, 'getApprovers'])->name('jobPositions.approvers');

    // [4] Pengajuan — Submit Draft
    Route::post('/penilaian/submit-draft/{id_job_position}', [PenilaianTCController::class, 'submitDraft'])->name('penilaian.submitDraft');

    // [3] Penilaian — Koreksi post-approval
    Route::put('/penilaian/correct/{id_job_position}', [PenilaianTCController::class, 'correctPenilaian'])->name('penilaian.correct');

    // [3] Penilaian — View history per tahun
    Route::get('/penilaian/history/{id_job_position}/{year}', [PenilaianTCController::class, 'yearlyHistory'])->name('penilaian.yearlyHistory');

    // [4] Monitoring admin/HR
    Route::get('/penilaian/monitoring', [PenilaianTCController::class, 'monitoringIndex'])->name('penilaian.monitoring');

    // [3] Summary with averages
    Route::get('/penilaian/summary-averages', [PenilaianTCController::class, 'getSummaryWithAverage'])->name('penilaian.summaryAverages');

    Route::get('/download-pdf/{id}', [PdController::class, 'downloadPDF'])->name('download.pdf');
    Route::post('/update-button-status', [PdController::class, 'updateBtn'])->name('updateButtonStatus');

    //chartTC
    Route::get('/get-competency-data', [PenilaianTCController::class, 'getCompetencyData'])->name('get-competency-data');
    Route::get('/get-competency-filter', [PenilaianTCController::class, 'getCompetencyFilter'])->name('get-competency-filter');
    Route::get('/get-detail-filter', [PenilaianTCController::class, 'getDetailCompetency'])->name('get-detail-filter');

    //FPB
    Route::get('/index-po', [PoPengajuanController::class, 'indexPoPengajuan'])->name('index.PO');
    Route::get('/index-po-depthead', [PoPengajuanController::class, 'indexPoDeptHead'])->name('index.PO.Dept');
    Route::get('/index-po-user', [PoPengajuanController::class, 'indexPoUser'])->name('index.PO.user');
    Route::get('/index-po-finance', [PoPengajuanController::class, 'indexPoFinance'])->name('index.PO.finance');
    Route::get('/index-po-procurement', [PoPengajuanController::class, 'indexPoProcurement'])->name('index.PO.procurement');
    Route::get('/index-po-procurement2', [PoPengajuanController::class, 'indexPoProcurement2'])->name('index.PO.procurement2');

    Route::get('/overviewfpb', [PoPengajuanController::class, 'overviewFPB'])->name('overviewfpb');
    Route::get('/fpb/detail/{id}', [PoPengajuanController::class, 'viewformfpb'])->name('fpb.detail');

    Route::get('/po-pengajuan/{id}/edit', [PoPengajuanController::class, 'edit'])->name('edit.PoPengajuan');
    Route::get('/po-pengajuan-dept/{id}/edit', [PoPengajuanController::class, 'editDept'])->name('edit.PoPengajuan.dept');

    Route::get('/create-po', [PoPengajuanController::class, 'createPoPengajuan'])->name('createPO');
    Route::get('/form-po-secHead/{id}', [PoPengajuanController::class, 'showFPBForm'])->name('view.FormPo');
    Route::get('/form-po-dept/{id}', [PoPengajuanController::class, 'showFPBForm2'])->name('view.FormPo.2');
    Route::get('/form-po-user/{id}', [PoPengajuanController::class, 'showFPBForm3'])->name('view.FormPo.3');
    Route::get('/form-po-finn/{id}', [PoPengajuanController::class, 'showFPBForm4'])->name('view.FormPo.4');
    Route::get('/form-po-proc/{id}', [PoPengajuanController::class, 'showFPBProc'])->name('view.FormPo.proc');

    Route::post('/store-po', [PoPengajuanController::class, 'store'])->name('store.po');
    Route::put('/po-pengajuan/{id}', [PoPengajuanController::class, 'update'])->name('update.PoPengajuan');
    Route::put('/po-pengajuan-dept/{id}', [PoPengajuanController::class, 'updateDept'])->name('update.PoPengajuan.dept');
    Route::post('/update-status-by-fpb/{no_fpb}', [PoPengajuanController::class, 'updateStatusByNoFPB'])->name('kirim.fpb.secHead');
    Route::post('/update-FPB-DeptHead/{no_fpb}', [PoPengajuanController::class, 'updateStatusByDeptHead'])->name('kirim.fpb.deptHead');
    Route::post('/update-FPB-User/{no_fpb}', [PoPengajuanController::class, 'updateStatusByUser'])->name('kirim.fpb.user');
    Route::post('/update-FPB-Finance/{no_fpb}', [PoPengajuanController::class, 'updateStatusByFinance'])->name('kirim.fpb.finance');
    Route::post('/update-FPB-procurement/{no_fpb}', [PoPengajuanController::class, 'updateStatusByProcurement'])->name('kirim.fpb.procurement');
    Route::post('/update-FPB-progres/{id}', [PoPengajuanController::class, 'updateConfirmByProcurment'])->name('kirim.fpb.progres');
    Route::post('/reject-item/{id}', [PoPengajuanController::class, 'rejectItem'])->name('kirim.fpb.reject');
    Route::post('/cancel-item/{id}', [PoPengajuanController::class, 'updateCancelByProcurment'])->name('kirim.fpb.cancel');
    Route::post('/cancel-item2/{id}', [PoPengajuanController::class, 'updateCancelBySecHead'])->name('kirim.fpb.cancel2');
    Route::post('/po_pengajuan/finish/{no_fpb}', [PoPengajuanController::class, 'updateFinishByProcurment'])->name('update.PoPengajuan.finish');
    Route::post('/po-pengajuan/approve-quotation/{id}', [PoPengajuanController::class, 'updateStatusQuotation'])->name('poPengajuan.updateStatusQuotation');

    Route::get('/po-history/{no_fpb}', [PoPengajuanController::class, 'getPoHistory'])->name('po.history');
    Route::delete('/po_pengajuan/delete_multiple', [PoPengajuanController::class, 'deletePoPengajuanMultiple'])->name('delete.PoPengajuanMultiple');

    Route::get('/download-pdf-2/{no_fpb}', [PoPengajuanController::class, 'downloadPdfByNoFpb'])->name('download.pdf.2');
    Route::get('/download-file/{id}', [PoPengajuanController::class, 'downloadFile'])->name('download.file');
    Route::get('/get-data', [PoPengajuanController::class, 'getData'])->name('getData');

    // Route::get('/dashboardFPB', [PoPengajuanController::class, 'dashboardCombined'])->name('dashboardFPB');

    //E-Mading Adasi
    Route::get('/ds-E-Mading-Adasi', [MadingController::class, 'dsMading'])->name('dsMading');

    //Pengajuan Subcont
    Route::get('/dashboard-pengajuan-sales', [PengajuanSubcontController::class, 'indexSales'])->name('indexSales');
    Route::post('/dashboard-pengajuan-sales/export', [PengajuanSubcontController::class, 'exportSales'])->name('indexSales.export');
    Route::get('/dashboard-pengajuan-procurment', [PengajuanSubcontController::class, 'indexProc'])->name('indexProc');
    Route::get('/pengajuan-subcont/create', [PengajuanSubcontController::class, 'create'])->name('pengajuan-subcont.create');
    Route::get('/pengajuan-subcont/{id}/edit', [PengajuanSubcontController::class, 'edit'])->name('pengajuan-subcont.edit');
    Route::get('/pengajuan-subcont/{id}/editProc', [PengajuanSubcontController::class, 'editProc'])->name('pengajuan-subcont.editProc');
    Route::get('/pengajuan-subcont/{id}/view', [PengajuanSubcontController::class, 'viewSales'])->name('pengajuan-subcont.view');
    Route::get('/get-history/{id}', [PengajuanSubcontController::class, 'getHistory'])->name('get.history');

    Route::post('/pengajuan-subcont/store', [PengajuanSubcontController::class, 'store'])->name('pengajuan-subcont.store');

    Route::post('/pengajuan-subcont/{id}/kirim', [PengajuanSubcontController::class, 'kirimSales'])->name('pengajuan-subcont.kirim');
    Route::post('/pengajuan-subcont/{id}/kirim-proc', [PengajuanSubcontController::class, 'kirimProc'])->name('pengajuan-subcont.kirim2');
    Route::post('/submit-data/{id}/submit-proc', [PengajuanSubcontController::class, 'submitData'])->name('submit.data');
    Route::post('/submit-data/{id}/finish-proc', [PengajuanSubcontController::class, 'FinishProc'])->name('FinishProc');

    Route::put('/pengajuan-subcont/{id}', [PengajuanSubcontController::class, 'update'])->name('pengajuan-subcont.update');
    Route::delete('/pengajuan-subcont/{id}', [PengajuanSubcontController::class, 'delete'])->name('pengajuan-subcont.destroy');

    //Claim Submission
    Route::get('/claim-submission/user', [ClaimSubmissionController::class, 'indexUser'])->name('claim.indexUser');
    Route::get('/claim-submission/procurement', [ClaimSubmissionController::class, 'indexProc'])->name('claim.indexProc');
    Route::get('/claim-submission/export-excel-proc', [ClaimSubmissionController::class, 'exportExcelProc'])->name('claim.exportExcelProc');
    Route::get('/claim-submission/create', [ClaimSubmissionController::class, 'create'])->name('claim.create');
    Route::get('/claim-submission/{id}/edit', [ClaimSubmissionController::class, 'edit'])->name('claim.edit');
    Route::get('/claim-submission/{id}/view', [ClaimSubmissionController::class, 'viewClaim'])->name('claim.view');
    Route::get('/claim-submission/{id}/editProc', [ClaimSubmissionController::class, 'editProc'])->name('claim.editProc');
    Route::get('/claim-submission/{id}/history', [ClaimSubmissionController::class, 'getHistory'])->name('claim.history');
    Route::post('/claim-submission/store', [ClaimSubmissionController::class, 'store'])->name('claim.store');
    Route::put('/claim-submission/{id}', [ClaimSubmissionController::class, 'update'])->name('claim.update');
    Route::delete('/claim-submission/{id}', [ClaimSubmissionController::class, 'delete'])->name('claim.destroy');
    Route::post('/claim-submission/{id}/proses', [ClaimSubmissionController::class, 'prosesProc'])->name('claim.proses');
    Route::post('/claim-submission/{id}/submit-proc', [ClaimSubmissionController::class, 'submitProc'])->name('claim.submitProc');
    Route::post('/claim-submission/{id}/finish', [ClaimSubmissionController::class, 'finishProc'])->name('claim.finish');

    Route::get('/upload-json', [JsonToCsvController::class, 'showUploadForm'])->name('upload.json');
    Route::post('/convert-json-to-csv', [JsonToCsvController::class, 'convert'])->name('convert.json');

    // Item Code
    Route::prefix('item-code')->name('item-code.')->middleware([
        'auth',
        'role:item_code_access',
    ])->group(function () {

        // --- Form (Pembuat) ---
        Route::middleware(['role:item_code_form,item_code_special_requester'])->group(function () {
            Route::get('/form-item-code', [ItemCodeController::class, 'index'])->name('form');
            Route::get('/form-item-code/next-nomor', [ItemCodeController::class, 'nextNomor'])->name('nextNomor');
            Route::get('/form-item-code/export', [ItemCodeController::class, 'export'])->name('exportForm');
            Route::get('/form-item-code/import-template', [ItemCodeController::class, 'importTemplate'])->name('importTemplate');
            Route::post('/form-item-code/import', [ItemCodeController::class, 'import'])->name('import');
            Route::post('/form-item-code', [ItemCodeController::class, 'store'])->name('store');
            Route::post('/form-item-code/submit-all', [ItemCodeController::class, 'submitAll'])->name('submitAll');
            Route::put('/form-item-code/{id}', [ItemCodeController::class, 'update'])->name('update');
            Route::delete('/form-item-code/{id}', [ItemCodeController::class, 'destroy'])->name('destroy');
            Route::post('/form-item-code/{id}/submit', [ItemCodeController::class, 'submit'])->name('submit');
            Route::post('/form-item-code/{id}/cancel', [ItemCodeController::class, 'cancel'])
                ->middleware('role:item_code_canceller')
                ->name('cancel');
        });

        // --- Review Harga Produk Baru Mamik (Ilyas) ---
        Route::middleware(['role:item_code_price_reviewer'])->group(function () {
            Route::get('/price-review', [ItemCodeController::class, 'priceReview'])
                ->name('price-review.index');
            Route::post('/price-review/{id}/confirm', [ItemCodeController::class, 'confirmPrice'])
                ->name('price-review.confirm');
            Route::post('/price-review/{id}/return', [ItemCodeController::class, 'returnPriceReview'])
                ->name('price-review.return');
        });

        // --- Persetujuan (Approver 1, Approver 2, Finisher) ---
        Route::middleware(['role:item_code_approval'])->group(function () {
            Route::get('/persetujuan', [ApprovalController::class, 'index'])->name('approval');
            Route::get('/persetujuan/export', [ApprovalController::class, 'export'])->name('exportApproval');

            // Approve 1 — Jessica Paune (submitted → approved_1)
            Route::post('/persetujuan/approve-all', [ApprovalController::class, 'approveAll'])->name('approveAll');
            Route::post('/persetujuan/{id}/approve', [ApprovalController::class, 'approve'])->name('approve');

            // Approve 2 — Martinus Cahyo Rahasto (approved_1 → approved_2)
            Route::post('/persetujuan/approve2-all', [ApprovalController::class, 'approve2All'])->name('approve2All');
            Route::post('/persetujuan/{id}/approve2', [ApprovalController::class, 'approve2'])->name('approve2');

            // Reject — bisa dilakukan Approver 1 atau Approver 2
            Route::post('/persetujuan/{id}/reject', [ApprovalController::class, 'reject'])->name('reject');

            // Finish — Adhi Prasetiyo (approved_2 → finished)
            Route::post('/persetujuan/finish-all', [ApprovalController::class, 'finishAll'])->name('finishAll');
            Route::post('/persetujuan/{id}/finish', [ApprovalController::class, 'finish'])->name('finish');
        });

        // --- Shared (semua role item-code) ---
        Route::get('/form-item-code/{id}/history', [ItemCodeController::class, 'history'])->name('history');
        Route::get('/form-item-code/{id}/attachment', [ItemCodeController::class, 'attachment'])->name('attachment');
    });

    //Outstanding Materials
    Route::prefix('outstanding-materials')->name('outstanding-materials.')->middleware([
        'auth',
        'role:outstanding_material',
    ])->group(function () {
        Route::get('/', [OutstandingMaterialController::class, 'index'])->name('index');
        Route::get('/data', [OutstandingMaterialController::class, 'data'])->name('data');
        Route::get('/create', [OutstandingMaterialController::class, 'create'])->name('create');
        Route::post('/bulk', [OutstandingMaterialController::class, 'storeBatch'])->name('bulk-store');
        Route::post('/', [OutstandingMaterialController::class, 'store'])->name('store');
        Route::get('/export', [OutstandingMaterialController::class, 'export'])->name('export');
        Route::post('/import', [OutstandingMaterialController::class, 'import'])->name('import');
        Route::get('/import/preview/{token}', [OutstandingMaterialController::class, 'importPreview'])->whereUuid('token')->name('import.preview');
        Route::post('/import/preview/{token}/execute', [OutstandingMaterialController::class, 'importExecute'])->whereUuid('token')->name('import.preview.execute');
        Route::get('/template', [OutstandingMaterialController::class, 'template'])->name('template');
        Route::get('/show-based-on-invoice', [OutstandingMaterialController::class, 'invoiceIndex'])->name('invoice.index');
        Route::get('/show-based-on-invoice/data', [OutstandingMaterialController::class, 'invoiceData'])->name('invoice.data');
        Route::get('/show-based-on-invoice/materials', [OutstandingMaterialController::class, 'invoiceMaterials'])->name('invoice.materials');
        Route::post('/show-based-on-invoice/update', [OutstandingMaterialController::class, 'updateInvoiceFields'])->name('invoice.update');
        Route::post('/show-based-on-invoice/documents', [OutstandingMaterialController::class, 'uploadInvoiceDocuments'])->name('invoice.documents.upload');
        Route::delete('/show-based-on-invoice/{outstandingMaterial}', [OutstandingMaterialController::class, 'destroyInvoice'])->name('invoice.destroy');
        Route::get('/show-based-on-invoice/{outstandingMaterial}/materials', [OutstandingMaterialController::class, 'invoiceMaterialsForAnchor'])->name('invoice.materials.scoped');
        Route::post('/show-based-on-invoice/{outstandingMaterial}/update', [OutstandingMaterialController::class, 'updateInvoiceFieldsForAnchor'])->name('invoice.update.scoped');
        Route::post('/show-based-on-invoice/{outstandingMaterial}/documents', [OutstandingMaterialController::class, 'uploadInvoiceDocumentsForAnchor'])->name('invoice.documents.upload.scoped');
        Route::get('/{outstandingMaterial}/invoice-data', [OutstandingMaterialController::class, 'invoiceDetailData'])->name('invoice-detail.data');
        Route::get('/{outstandingMaterial}/export', [OutstandingMaterialController::class, 'invoiceDetailExport'])->name('invoice-detail.export');
        Route::get('/{outstandingMaterial}/attachment/{type?}', [OutstandingMaterialController::class, 'attachment'])
            ->whereIn('type', ['attachment', 'packing-list', 'mtc'])
            ->name('attachment');
        Route::get('/{outstandingMaterial}', [OutstandingMaterialController::class, 'show'])->name('show');
        Route::get('/{outstandingMaterial}/edit', [OutstandingMaterialController::class, 'edit'])->name('edit');
        Route::put('/{outstandingMaterial}', [OutstandingMaterialController::class, 'update'])->name('update');
        Route::delete('/{outstandingMaterial}', [OutstandingMaterialController::class, 'destroy'])->name('destroy');
    });

    Route::get('/upload-json', [JsonToCsvController::class, 'showUploadForm'])->name('upload.json');
    Route::post('/convert-json-to-csv', [JsonToCsvController::class, 'convert'])->name('convert.json');

    //custom request
    Route::get('/custom-request', [CustomRequestController::class, 'showCstmReq'])->name('showCustomRequest');
    Route::post('/custom-request/export', [CustomRequestController::class, 'exportCustomRequests'])->name('customrequest.export');
    Route::post('/materials/store', [CustomRequestController::class, 'createCstmReq'])->name('CustomRequest.store');
    Route::post('/materials/delete', [CustomRequestController::class, 'deleteCstmReq'])->name('CustomRequest.delete');
    Route::put('/materials/update/{id}', [CustomRequestController::class, 'updateCstmReq'])->name('CustomRequest.update');
    Route::get('/custom-request/form/{id}', [CustomRequestController::class, 'formCstmReq'])->name('CustomRequest.form');
    Route::get('/custom-request/form-Sales/{id}', [CustomRequestController::class, 'formCstmReqSales'])->name('CustomRequest.formSales');
    Route::get('/custom-request/form-Sales-cancel/{id}', [CustomRequestController::class, 'cancel'])->name('CustomRequest.formSalescancel');
    Route::post('/custom-request/update/{id}', [CustomRequestController::class, 'inputCstmReq'])->name('CustomRequest.updateCstmReq');
    Route::post('/custom-request/update/harga-akhir/{id}', [CustomRequestController::class, 'inputhrgakhr'])->name('CustomRequest.hargaakhir');
    Route::post('/custom-request/reject-input/{id}', [CustomRequestController::class, 'rejectInputSales'])->name('CustomRequest.rejectInput');
    Route::get('/custom-request/approve-marketing-cstmreq', [CustomRequestController::class, 'showApprovalMarketing'])->name('showApproveMarketing');
    Route::post('/custom-request/marketing-approved/{id}', [CustomRequestController::class, 'approveMarketing'])->name('approveMarketing');
    Route::post('/custom-request/marketing-approved-2/{id}', [CustomRequestController::class, 'approveMarketing2'])->name('approveMarketing2');
    Route::post('/custom-request/marketing-rejected/{id}', [CustomRequestController::class, 'rejectMarketing'])->name('rejectMarketing');
    Route::get('/custom-request/approve-finance-cstmreq', [CustomRequestController::class, 'showApprovalFinance'])->name('showApproveFinance');
    Route::post('/custom-request/finance-approved/{id}', [CustomRequestController::class, 'approveFinance'])->name('approveFinance');
    Route::post('/custom-request/finance-rejected/{id}', [CustomRequestController::class, 'rejectFinance'])->name('rejectFinance');
    Route::post('/custom-request/approved-production/{id}', [CustomRequestController::class, 'approveProduction'])->name('approvedProduction');
    Route::post('/custom-request/file/upload/{id}', [CustomRequestController::class, 'upload'])->name('cstm.fileupload');
    Route::get('/custom-request/file/download/{id}', [CustomRequestController::class, 'download'])->name('file.download');
    Route::post('/custom-request/file/approve/{id}', [CustomRequestController::class, 'fileapprove'])->name('cstm.fileapprove');
    Route::post('/custom-request/file/rejected/{id}', [CustomRequestController::class, 'filerejected'])->name('cstm.filerejected');
    Route::post('/custom-request/file/rejected-sales/{id}', [CustomRequestController::class, 'filerejectedsales'])->name('cstm.filerejectedsales');
    Route::post('/custom-request/file/delete-sales/{id}', [CustomRequestController::class, 'filehapussales'])->name('cstm.filehapussales');
    Route::post('/custom-request/subcont-send/{id}', [CustomRequestController::class, 'kirimsubcont'])->name('kirimsubcont');
    Route::post('/custom-request/sales-send/{id}', [CustomRequestController::class, 'kirimsales'])->name('kirimsales');
    Route::post('/custom-request/production-send/{id}', [CustomRequestController::class, 'kirimproduction'])->name('kirimproduction');
    Route::post('/custom-request/submit-send/{id}', [CustomRequestController::class, 'submitproduction'])->name('SubmitProduction');
    Route::post('/custom-request/rejected-production/{id}', [CustomRequestController::class, 'rejectproduction'])->name('RejectProduction');
    Route::post('/custom-request/submit-quotation/{id}', [CustomRequestController::class, 'submitData'])->name('submit.quotation');
    Route::put('/custom-request/update-no-so', [CustomRequestController::class, 'updateNoSo'])->name('customrequest.updateNoSo');

    Route::get('/dashboard-fpb', [DashboardController::class, 'dashboardFPB'])->name('dashboardFPB');
    Route::middleware('can:viewTcpdDashboard')->group(function () {
        Route::get('/dashboard-tcpd', [DashboardController::class, 'dashboardTCPD'])->name('dashboardTCPD');
        Route::get('/dashboard-tcpd/data', [DashboardController::class, 'getTcpdCompetencyData'])->name('dashboardTCPD.data');
        Route::get('/dashboard-tcpd/company-data', [DashboardController::class, 'getTcpdCompanyData'])->name('dashboardTCPD.companyData');
        Route::get('/dashboard-tcpd/sensitive-data', [DashboardController::class, 'getTcpdSensitiveData'])->name('dashboardTCPD.sensitiveData');
        Route::get('/dashboard-tcpd/export', [DashboardController::class, 'exportTcpdCompetencyData'])->name('dashboardTCPD.export');
        Route::get('/dashboard-tcpd/company-export', [DashboardController::class, 'exportTcpdCompanyData'])->name('dashboardTCPD.companyExport');
        Route::get('/dashboard-tcpd/export-all', [DashboardController::class, 'exportTcpdAll'])->name('dashboardTCPD.exportAll');
        Route::get('/dashboard-tcpd/export-employees', [DashboardController::class, 'exportTcpdEmployeesData'])->name('dashboardTCPD.exportEmployees');
        Route::get('/dashboard-tcpd/export-top-jobs', [DashboardController::class, 'exportTcpdTopJobs'])->name('dashboardTCPD.exportTopJobs');
        Route::get('/dashboard-tcpd/export-critical-focus', [DashboardController::class, 'exportTcpdCriticalFocus'])->name('dashboardTCPD.exportCriticalFocus');
        Route::post('/dashboard-tcpd/clear-cache', [DashboardController::class, 'clearTcpdCache'])
            ->middleware('can:clearTcpdDashboardCache')
            ->name('dashboardTCPD.clearCache');
    });

    // BOPM Dashboard
    Route::get('/dashboard-bopm', [BOPMController::class, 'index'])->name('bopm.dashboard.index');
    Route::get('/dashboard-bopm/chart', [BOPMController::class, 'getChartData'])->name('bopm.dashboard.chart');
    Route::get('/dashboard-bopm/table', [BOPMController::class, 'getTableData'])->name('bopm.dashboard.table');
    Route::post('/dashboard-bopm/currency/save', [BOPMController::class, 'saveCurrency'])->name('bopm.dashboard.currency.save');
    Route::get('/dashboard-bopm/currency/list', [BOPMController::class, 'getCurrencyList'])->name('bopm.dashboard.currency.list');
    Route::get('/dashboard-bopm/export-template', [BOPMController::class, 'exportTemplate'])->name('bopm.dashboard.export-template');
    Route::post('/dashboard-bopm/import', [BOPMController::class, 'importData'])->name('bopm.dashboard.import');
    Route::post('/dashboard-bopm/material/store', [BOPMController::class, 'storeMaterial'])->name('bopm.dashboard.material.store');
    Route::get('/dashboard-bopm/export-table', [BOPMController::class, 'exportTableData'])->name('bopm.dashboard.export-table');

    // Grup Rute API untuk dipanggil oleh JavaScript
    Route::prefix('api/dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/fpb', [DashboardController::class, 'getFpbData'])->name('fpb');
        Route::get('/leadtime', [DashboardController::class, 'getLeadTimeData'])->name('leadtime');
        Route::get('/inquiry', [DashboardController::class, 'getInquiryData'])->name('inquiry');
        Route::get('/crp', [DashboardController::class, 'getCrpData'])->name('crp');
    });

    // Supplier Form
    Route::get('/supplier-form/index', [SupplierFormController::class, 'index'])->name('supplierform.index');
    Route::get('/supplier-form/create', [SupplierFormController::class, 'create'])->name('supplierform.create'); // opsional
    Route::get('/supplier-form/show/{id}', [SupplierFormController::class, 'show'])->name('supplierform.show');
    Route::get('/supplier-form/{id}/download', [SupplierFormController::class, 'download'])->name('supplierform.download');
    Route::patch('/supplier-form/{id}/reject', [SupplierFormController::class, 'hapus'])->name('supplierform.reject');
    Route::post('/supplierform/generate-link', [SupplierFormController::class, 'generateLink'])->name('supplierform.generate-link');
    Route::patch('/supplierform/{id}/update-category', [SupplierFormController::class, 'updateCategory'])->name('supplierform.update.category');
    Route::patch('/supplierform/{id}/schedule-actions', [SupplierFormController::class, 'scheduleActions'])->name('supplierform.update.schedule');
    Route::post('/supplierform/{id}/submit-trial', [SupplierFormController::class, 'submitTrialEvidence'])->name('supplierform.update.submittrial');
    Route::patch('/supplierform/{id}/approve', [SupplierFormController::class, 'approve'])->name('supplierform.approve');
    Route::patch('/supplierform/{id}/disapprove', [SupplierFormController::class, 'disapprove'])->name('supplierform.disapprove');
    Route::get('/supplierform/visit-form/{id}', [SupplierFormController::class, 'createVisitAssessment'])->name('assessment.visit.create');
    Route::get('/supplierform/visit-form/edit/{id}', [SupplierFormController::class, 'editVisitAssessment'])->name('assessment.visit.edit');
    Route::put('/supplierform/visit-form/{id}', [SupplierFormController::class, 'updateVisit'])->name('assessment.visit.update');
    Route::post('/supplierform/visit-form/store/{id}', [SupplierFormController::class, 'storeVisit'])->name('assessment.visit.store');
    Route::post('/supplierform/trial-upload/store/{id}', [SupplierFormController::class, 'storeTrialUpload'])->name('assessment.trial.store');
    Route::get('/supplier-forms/{id}/print', [SupplierFormController::class, 'printReport'])->name('supplierform.print');
    Route::post('/supplier-form/{id}/choose-action', [SupplierFormController::class, 'scheduleActions'])->name('supplierform.choose.action');
    Route::post('/supplier-form/{id}/schedule-visit', [SupplierFormController::class, 'storeVisitSchedule'])->name('supplierform.schedule.visit');
    Route::post('/supplier-form/{id}/schedule-trial', [SupplierFormController::class, 'storeTrialSchedule'])->name('supplierform.schedule.trial');
    Route::get('/supplier/file/{id}/{type}', [SupplierFormController::class, 'downloadFile'])->name('supplier.file.download');
    Route::patch('/supplier-form/{id}/reject-type', [SupplierFormController::class, 'rejectType'])->name('supplierform.reject.type');
    Route::patch('/supplier-form/{id}/reject-schedule', [SupplierFormController::class, 'rejectSchedule'])->name('supplierform.reject.schedule');
    Route::patch('/supplier-form/{id}/reject-trial', [SupplierFormController::class, 'rejectTrial'])->name('supplierform.reject.trial');
    Route::post('/supplierform/{id}/trial/update', [SupplierFormController::class, 'updateTrialUpload'])->name('assessment.trial.update');
    Route::post('/supplierform/{id}/trial/delete', [SupplierFormController::class, 'deleteTrialUpload'])->name('assessment.trial.delete');
    Route::post('/supplier-form/visit-approval/{id}', [SupplierFormController::class, 'visitApproval'])->name('supplier.visit.approval');
    Route::post('/supplier-form/trial-approval/{id}', [SupplierFormController::class, 'trialApproval'])->name('supplier.trial.approval');
    Route::get('/supplier-form/trial-file/{id}', [SupplierFormController::class, 'downloadTrial'])->name('supplierform.download.trial');

    // Entertainment Routes
    Route::get('/entertain', [EntertainController::class, 'index'])->name('entertain.index');
    Route::get('/entertain/data', [EntertainController::class, 'getData'])->name('entertain.getData');
    Route::post('/entertain/store', [EntertainController::class, 'store'])->name('entertain.store');
    Route::get('/entertain/download/{id}', [EntertainController::class, 'download'])->name('entertain.download');
    Route::get('/entertain/export', [EntertainController::class, 'export'])->name('entertain.export');
    route::get('/dashboard/entertain', [EntertainController::class, 'dashboard'])->name('dashboard.entertainment');

    //jumlah karyawan
    Route::get('/jumlah-karyawan', [StrukturOrganisasiController::class, 'index'])->name('jumlahKaryawan.index');

    // Convert Word to PDF
    Route::get('/convert/word-to-pdf', [ConvertController::class, 'index'])->name('convert.wordtopdf');
    Route::post('/convert/word-to-pdf', [ConvertController::class, 'convert'])->name('convert.wordtopdf.post');

    // Feedback System
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/api/fastware/survey/submit', [FeedbackController::class, 'submit'])->name('feedback.submit');
    Route::get('/feedback/list', [FeedbackController::class, 'list'])->name('feedback.list');
    Route::get('/feedback/detail/{id}', [FeedbackController::class, 'detail'])->name('feedback.detail');
    Route::get('/feedback/dashboard', [FeedbackController::class, 'dashboard'])->name('feedback.dashboard');

    // Sales Visit Dashboard
    Route::get('/sales-visit/dashboard', [\App\Http\Controllers\SalesVisitController::class, 'index'])->name('salesvisit.dashboard');
    Route::get('/sales-visit/dashboard/data', [\App\Http\Controllers\SalesVisitController::class, 'getDashboardData'])->name('salesvisit.dashboard.data');
    Route::get('/sales-visit/dashboard/detail-data', [\App\Http\Controllers\SalesVisitController::class, 'getDetailData'])->name('salesvisit.dashboard.detail-data');
    Route::get('/sales-visit/dashboard/export', [\App\Http\Controllers\SalesVisitController::class, 'exportExcel'])->name('salesvisit.dashboard.export');

    // ===== HR Base Competency — Master Job Position =====
    Route::prefix('hr')->name('mst-job-position.')->group(function () {
        Route::get('/master-job-position', [\App\Http\Controllers\MstJobPositionController::class, 'index'])->name('index');
        Route::get('/master-job-position/create', [\App\Http\Controllers\MstJobPositionController::class, 'create'])->name('create');
        Route::post('/master-job-position', [\App\Http\Controllers\MstJobPositionController::class, 'store'])->name('store');
        Route::get('/master-job-position/{mstJobPosition}/edit', [\App\Http\Controllers\MstJobPositionController::class, 'edit'])->name('edit');
        Route::put('/master-job-position/{mstJobPosition}', [\App\Http\Controllers\MstJobPositionController::class, 'update'])->name('update');
        Route::patch('/master-job-position/{mstJobPosition}/toggle-active', [\App\Http\Controllers\MstJobPositionController::class, 'toggleActive'])->name('toggle-active');
        Route::delete('/master-job-position/{mstJobPosition}', [\App\Http\Controllers\MstJobPositionController::class, 'destroy'])->name('destroy');

        // AJAX – tambah departemen / section dari modal "+"
        Route::post('/master-job-position/ajax/store-department', [\App\Http\Controllers\MstJobPositionController::class, 'storeDepartment'])->name('ajax.store-department');
        Route::post('/master-job-position/ajax/store-section', [\App\Http\Controllers\MstJobPositionController::class, 'storeSection'])->name('ajax.store-section');
        Route::get('/master-job-position/ajax/sections/{mstDepartment}', [\App\Http\Controllers\MstJobPositionController::class, 'getSectionsByDepartment'])->name('ajax.sections-by-dept');
        Route::get('/master-job-position/ajax/departments', [\App\Http\Controllers\MstJobPositionController::class, 'getDepartments'])->name('ajax.departments');
    });

    // ===== HR Base Competency — Mapping Karyawan ke Posisi =====
    Route::prefix('hr')->name('user-job-position.')->group(function () {
        Route::get('/user-job-position', [\App\Http\Controllers\UserJobPositionController::class, 'index'])->name('index');
        Route::post('/user-job-position', [\App\Http\Controllers\UserJobPositionController::class, 'store'])->name('store');
        Route::put('/user-job-position/{userJobPosition}', [\App\Http\Controllers\UserJobPositionController::class, 'update'])->name('update');
        Route::patch('/user-job-position/{userJobPosition}/toggle-active', [\App\Http\Controllers\UserJobPositionController::class, 'toggleActive'])->name('toggle-active');
        Route::delete('/user-job-position/{userJobPosition}', [\App\Http\Controllers\UserJobPositionController::class, 'destroy'])->name('destroy');
        Route::get('/user-job-position/api/by-user', [\App\Http\Controllers\UserJobPositionController::class, 'getPositionsByUser'])->name('api.by-user');

        // Modul 3.1 — Working Experience CRUD API
        Route::get('/user-job-position/api/working-experience', [\App\Http\Controllers\UserJobPositionController::class, 'getWorkingExperiences'])->name('api.working-experience.index');
        Route::post('/user-job-position/api/working-experience', [\App\Http\Controllers\UserJobPositionController::class, 'storeWorkingExperience'])->name('api.working-experience.store');
        Route::put('/user-job-position/api/working-experience/{workingExperience}', [\App\Http\Controllers\UserJobPositionController::class, 'updateWorkingExperience'])->name('api.working-experience.update');
        Route::delete('/user-job-position/api/working-experience/{workingExperience}', [\App\Http\Controllers\UserJobPositionController::class, 'destroyWorkingExperience'])->name('api.working-experience.destroy');
        // Modul 3.2 — Working Experience Bulk Import
        Route::post('/user-job-position/api/working-experience/import', [\App\Http\Controllers\UserJobPositionController::class, 'importWorkingExperience'])->name('api.working-experience.import');
        Route::get('/user-job-position/api/working-experience/import/template', [\App\Http\Controllers\UserJobPositionController::class, 'downloadImportTemplate'])->name('api.working-experience.import.template');
    });

    // Modul 4.2 — Year Management API (HR & Administrator only)
    Route::post('/pd/active-year', [\App\Http\Controllers\PdController::class, 'setActiveYear'])->name('pd.active-year.set');
    Route::get('/pd/active-year', [\App\Http\Controllers\PdController::class, 'getActiveYear'])->name('pd.active-year.get');

    // Warehouse Consumable routes. Business endpoints are added by the
    // mission-specific controllers while this protected shell establishes the
    // canonical prefix and permission boundary.
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Warehouse\WarehouseDashboardController::class, 'index'])
            ->middleware('warehouse.permission:warehouse.dashboard.view')
            ->name('dashboard');

        Route::get('/transactions/create', [\App\Http\Controllers\Warehouse\WarehouseTransactionController::class, 'create'])
            ->middleware('warehouse.permission:warehouse.stock-out.create')
            ->name('transactions.create');
        Route::get('/transactions-bekas/create', [\App\Http\Controllers\Warehouse\WarehouseTransactionController::class, 'createUsed'])
            ->middleware('warehouse.permission:warehouse.stock-out.create')
            ->name('transactions-used.create');
        Route::get('/catalog', [\App\Http\Controllers\Warehouse\WarehouseCatalogController::class, 'index'])
            ->name('catalog.index');
        Route::post('/scans/item', [\App\Http\Controllers\Warehouse\WarehouseScanController::class, 'scanItem'])
            ->middleware('throttle:warehouse-scan')
            ->name('scans.item');
        Route::post('/scans/user', [\App\Http\Controllers\Warehouse\WarehouseScanController::class, 'scanUser'])
            ->middleware('throttle:warehouse-scan')
            ->name('scans.user');
        Route::post('/transactions', [\App\Http\Controllers\Warehouse\WarehouseTransactionController::class, 'store'])
            ->middleware('throttle:warehouse-mutation')
            ->name('transactions.store');
        Route::get('/transactions', [\App\Http\Controllers\Warehouse\WarehouseTransactionHistoryController::class, 'index'])
            ->middleware('warehouse.permission:warehouse.transaction.view')
            ->name('transactions.index');
        Route::get('/dashboard/data', [\App\Http\Controllers\Warehouse\WarehouseDashboardController::class, 'data'])
            ->name('dashboard.data');
        Route::post('/transactions/{transaction}/reverse', [\App\Http\Controllers\Warehouse\WarehouseTransactionController::class, 'reverse'])
            ->middleware(['warehouse.permission:warehouse.transaction.reverse', 'throttle:warehouse-mutation'])
            ->name('transactions.reverse');
        Route::get('/transactions/{transaction}/reverse', [\App\Http\Controllers\Warehouse\WarehouseTransactionController::class, 'reverseForm'])
            ->middleware('warehouse.permission:warehouse.transaction.reverse')
            ->name('transactions.reverse-form');
        Route::get('/transactions/{transaction}', [\App\Http\Controllers\Warehouse\WarehouseTransactionController::class, 'show'])
            ->name('transactions.show');

        Route::middleware('warehouse.permission:warehouse.stock-in.create')->group(function () {
            Route::get('/adjustments/create', [\App\Http\Controllers\Warehouse\WarehouseStockAdjustmentController::class, 'create'])->name('adjustments.create');
            Route::post('/adjustments', [\App\Http\Controllers\Warehouse\WarehouseStockAdjustmentController::class, 'store'])->middleware('throttle:warehouse-mutation')->name('adjustments.store');
        });
        Route::get('/exports/transactions', [\App\Http\Controllers\Warehouse\WarehouseExportController::class, 'transactions'])
            ->middleware('warehouse.permission:warehouse.report.export')
            ->name('exports.transactions');
        Route::get('/reports', [\App\Http\Controllers\Warehouse\WarehouseReportController::class, 'index'])
            ->middleware('warehouse.permission:warehouse.report.view')
            ->name('reports.index');

        // Stock In is the single receiving workflow. The old standalone
        // entry/list URLs are compatibility redirects; the user-facing
        // workspace is Stock In/Out Baru.
        Route::prefix('stock-in')->name('stock-in.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Warehouse\WarehouseStockInController::class, 'index'])
                ->middleware('warehouse.permission:warehouse.stock-in.create')
                ->name('index');
            Route::get('/create', [\App\Http\Controllers\Warehouse\WarehouseStockInController::class, 'create'])
                ->middleware('warehouse.permission:warehouse.stock-in.create')
                ->name('create');
            Route::post('/', [\App\Http\Controllers\Warehouse\WarehouseStockInController::class, 'store'])
                ->middleware(['warehouse.permission:warehouse.stock-in.create', 'throttle:warehouse-mutation'])
                ->name('store');
            Route::get('/{stockIn}/validate', [\App\Http\Controllers\Warehouse\WarehouseStockInController::class, 'validateForm'])
                ->whereNumber('stockIn')
                ->middleware('warehouse.permission:warehouse.stock-in.validate')
                ->name('validate-form');
            Route::post('/{stockIn}/validate', [\App\Http\Controllers\Warehouse\WarehouseStockInController::class, 'validateStockIn'])
                ->whereNumber('stockIn')
                ->middleware(['warehouse.permission:warehouse.stock-in.validate', 'throttle:warehouse-mutation'])
                ->name('validate');
            Route::post('/{stockIn}/cancel', [\App\Http\Controllers\Warehouse\WarehouseStockInController::class, 'cancel'])
                ->whereNumber('stockIn')
                ->middleware(['warehouse.permission:warehouse.stock-in.create', 'throttle:warehouse-mutation'])
                ->name('cancel');
            Route::get('/{stockIn}', [\App\Http\Controllers\Warehouse\WarehouseStockInController::class, 'show'])
                ->whereNumber('stockIn')
                ->middleware('warehouse.permission:warehouse.stock-in.create')
                ->name('show');
        });

        Route::prefix('stock-in/shipments')->name('location-shipments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Warehouse\WarehouseLocationShipmentController::class, 'index'])
                ->middleware('warehouse.permission:warehouse.location-shipment.view')
                ->name('index');
            Route::get('/create', [\App\Http\Controllers\Warehouse\WarehouseLocationShipmentController::class, 'create'])
                ->middleware('warehouse.permission:warehouse.location-shipment.create')
                ->name('create');
            Route::post('/', [\App\Http\Controllers\Warehouse\WarehouseLocationShipmentController::class, 'store'])
                ->middleware(['warehouse.permission:warehouse.location-shipment.create', 'throttle:warehouse-mutation'])
                ->name('store');
            Route::get('/{shipment}/validate', [\App\Http\Controllers\Warehouse\WarehouseLocationShipmentController::class, 'validateForm'])
                ->middleware('warehouse.permission:warehouse.location-shipment.validate')
                ->name('validate-form');
            Route::post('/{shipment}/validate', [\App\Http\Controllers\Warehouse\WarehouseLocationShipmentController::class, 'validateShipment'])
                ->middleware(['warehouse.permission:warehouse.location-shipment.validate', 'throttle:warehouse-mutation'])
                ->name('validate');
            Route::post('/{shipment}/cancel', [\App\Http\Controllers\Warehouse\WarehouseLocationShipmentController::class, 'cancel'])
                ->middleware(['warehouse.permission:warehouse.location-shipment.cancel', 'throttle:warehouse-mutation'])
                ->name('cancel');
            Route::get('/{shipment}', [\App\Http\Controllers\Warehouse\WarehouseLocationShipmentController::class, 'show'])
                ->middleware('warehouse.permission:warehouse.location-shipment.view')
                ->name('show');
        });

        Route::middleware('warehouse.permission:warehouse.master.manage')->group(function () {
            Route::get('/consumables', [\App\Http\Controllers\Warehouse\WarehouseConsumableController::class, 'index'])->name('consumables.index');
            Route::get('/consumables/create', [\App\Http\Controllers\Warehouse\WarehouseConsumableController::class, 'create'])->name('consumables.create');
            Route::post('/consumables', [\App\Http\Controllers\Warehouse\WarehouseConsumableController::class, 'store'])->name('consumables.store');
            Route::get('/consumables/{consumable}', [\App\Http\Controllers\Warehouse\WarehouseConsumableController::class, 'show'])->name('consumables.show');
            Route::get('/consumables/{consumable}/edit', [\App\Http\Controllers\Warehouse\WarehouseConsumableController::class, 'edit'])->name('consumables.edit');
            Route::put('/consumables/{consumable}', [\App\Http\Controllers\Warehouse\WarehouseConsumableController::class, 'update'])->name('consumables.update');
            Route::patch('/consumables/{consumable}/status', [\App\Http\Controllers\Warehouse\WarehouseConsumableController::class, 'toggleStatus'])->name('consumables.status');
            Route::post('/consumables/{consumable}/opening-balance', [\App\Http\Controllers\Warehouse\WarehouseConsumableController::class, 'openingBalance'])->name('consumables.opening-balance');

            Route::get('/categories', [\App\Http\Controllers\Warehouse\WarehouseCategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [\App\Http\Controllers\Warehouse\WarehouseCategoryController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [\App\Http\Controllers\Warehouse\WarehouseCategoryController::class, 'update'])->name('categories.update');

        });
    });
});
