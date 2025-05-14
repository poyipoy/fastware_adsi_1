<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FormFPPController;
use App\Http\Controllers\HandlingController;
use App\Http\Controllers\HeatTreatmentController;
use App\Http\Controllers\InquirySalesController;
use App\Http\Controllers\KmPengajuanController;
use App\Http\Controllers\MesinController;
use App\Http\Controllers\PenilaianTCController;
use App\Http\Controllers\PdController;
use App\Http\Controllers\PreventiveController;
use App\Http\Controllers\SafetyController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\SumbangSaranController;
use App\Http\Controllers\TcController;
use App\Http\Controllers\TcJobController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MadingController;
use App\Http\Controllers\PoPengajuanController;
use App\Http\Controllers\PengajuanSubcontController;
use App\Http\Controllers\JsonToCsvController;
use App\Http\Controllers\CrpController;
use App\Http\Controllers\CustomRequestController;
use App\Http\Controllers\ImportAdministrationController;
use App\Models\InquirySales;
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

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');

Route::post('/login', [AuthController::class, 'login'])->name('login_post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('full-calender', [EventController::class, 'blokMaintanence'])->name('blokMaintanence');
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
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');

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
    Route::post('/inquiry/confirm-purchase/{id}', [InquirySalesController::class, 'confirmPurchase'])->name('confirmPurchase');
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
    Route::post('/insights/add', [KmPengajuanController::class, 'addInsight'])->name('insights.add');

    // // tc
    Route::get('/job', [TcJobController::class, 'jobShow'])->name('jobShow');
    Route::get('/tcShow', [TcController::class, 'tcShow'])->name('tcShow');
    Route::get('/tcCreate', [TcController::class, 'createTC'])->name('tcCreate');
    Route::post('/mst_tc/store', [TcController::class, 'storeTC'])->name('mst_tc.store');

    Route::get('mst_tc/{id}/edit', [TcController::class, 'edit'])->name('mst_tc.edit');
    Route::get('mst_sk/{id}/editSoftSKills', [TcController::class, 'editSoftSKills'])->name('mst_sk.editSoftSKills');
    Route::get('mst_ad/{id}/editAdditionals', [TcController::class, 'editAdditional'])->name('mst_ad.editAdditionals');
    Route::get('/job-position/{id}/edit', [TcJobController::class, 'getJobPositionData'])->name('getJobPosition');

    Route::delete('/job-positions/delete-row', [TcJobController::class, 'deleteRow'])->name('jobPositions.deleteRow');

    Route::put('mst_tc/{id}', [TcController::class, 'update'])->name('mst_tc.update');
    Route::put('mst_sk/{id}/updateSoftSkills', [TcController::class, 'updateSoftSkills'])->name('mst_sk.updateSoftSkills');
    Route::put('mst_ad/{id}/updateAdditionals', [TcController::class, 'updateAdditionals'])->name('mst_ad.updateAdditionals');
    Route::get('/employees-by-job-position', [TcController::class, 'fetchEmployeesByJobPosition'])->name('employees.by.job.position');

    Route::get('/summary/index', [TcController::class, 'summaryData'])->name('job.positions.index');
    Route::post('/sumarry/details', [TcController::class, 'fetchDetails'])->name('job.positions.details');
    Route::get('/job/positions/details2/{job_position}', [TcController::class, 'fetchDetails2'])->name('job.positions.details2');


    Route::get('/users/{userId}/role', [TcJobController::class, 'getUserRole'])->name('users.role');
    Route::post('/job-positions', [TcJobController::class, 'store'])->name('jobPositions.store');
    Route::put('/job-positions/{id}', [TcJobController::class, 'updateJob'])->name('jobPositions.update');
    Route::delete('/job-positions/{id}', [TcJobController::class, 'deleteRow'])->name('jobPositions.destroy');
    Route::delete('/delete-tc-row/{id}', [TcController::class, 'deleteTcRow'])->name('tc.deleteRow');
    Route::delete('/delete-sk-row/{id}', [TcController::class, 'deleteSkRow'])->name('sk.deleteRow');
    Route::delete('/delete-ad-row/{id}', [TcController::class, 'deleteAdRow'])->name('ad.deleteRow');

    //Route untuk menampilkan halaman penilaian (index)
    Route::get('/penilaian', [PenilaianTCController::class, 'indexTrs'])->name('penilaian.index');
    Route::get('/penilaian-dept', [PenilaianTCController::class, 'indexTrs2'])->name('penilaian.index2');
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

    Route::post('/save-penilaian', [PenilaianTCController::class, 'savePenilaian'])->name('savePenilaian');
    Route::post('/save-pd-pengajuan', [PdController::class, 'savePdPengajuan'])->name('savePdPengajuan');
    Route::post('/save-pd-pengajuan-Dept', [PdController::class, 'savePdPengajuanDept'])->name('savePdPengajuanDept');

    Route::put('/updated-pd-hrga', [PdController::class, 'updatePdPlan'])->name('updatePdPlan');

    Route::post('/update-data', [PdController::class, 'updateData'])->name('updateData');

    Route::put('/updated-pd-hrga2', [PdController::class, 'updatePdPlan2'])->name('updatePdPlan2');
    Route::post('/update-status/{id_job_position}', [PenilaianTCController::class, 'kirimSC'])->name('update.status');
    Route::post('/update-status-dept/{id_job_position}', [PenilaianTCController::class, 'kirimDept'])->name('update.status2');

    Route::get('/editPdPengajuan/{modified_at}/{tahun_aktual}', [PdController::class, 'editPdPengajuan'])->name('editPdPengajuan');
    Route::get('/editPdPengajuan-HRGA/{tahun_aktual}', [PdController::class, 'editPdPengajuanHRGA'])->name('editPdPengajuanHRGA');

    Route::put('/update-pd', [PdController::class, 'update'])->name('updatePD');
    Route::get('/update-evaluasi/{id}', [PdController::class, 'editEvaluasi'])->name('update-evaluasi');
    Route::put('/update-evaluasi/{id}', [PdController::class, 'updateEvaluasi'])->name('update-evaluasi.update');

    Route::get('/send-pd/{modified_at}/{tahun_aktual}', [PdController::class, 'sendPD'])->name('sendPD');
    Route::get('/send-pd2/{tahun_aktual}', [PdController::class, 'sendPD2'])->name('sendPD2');

    Route::get('/people-development/filter', [PdController::class, 'getFilteredData'])->name('people_development.filter');

    Route::get('/trs/edit-penilaian/{id_job_position}', [PenilaianTCController::class, 'editTrs'])->name('penilaian.edit');
    Route::get('/trs/edit-dept/{id_job_position}', [PenilaianTCController::class, 'editTrs2'])->name('penilaian.edit2');
    Route::get('/trs/view-penilaian/{id_job_position}', [PenilaianTCController::class, 'viewTrs'])->name('penilaian.view');
    Route::get('/trs/preview-penilaian/{id_job_position}', [PenilaianTCController::class, 'previewTrs'])->name('penilaian.preview');

    Route::get('/get-edit-Trs', [PenilaianTCController::class, 'getDataTrs'])->name('getDataTrs');

    Route::put('/penilaian/update/{id}', [PenilaianTCController::class, 'updateTrs'])->name('updatePenilaian');
    Route::put('/penilaian/dept/{id}', [PenilaianTCController::class, 'updateTrs2'])->name('updateDept');

    Route::put('/update-catatan/{id}', [PenilaianTCController::class, 'updateCatatan'])->name('updateCatatan');

    Route::put('/penilaian/{id}', [PenilaianTCController::class, 'update'])->name('penilaian.update');
    Route::delete('/penilaian/{id}', [PenilaianTCController::class, 'destroy'])->name('penilaian.destroy');

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

    Route::get('/dashboardFPB', [PoPengajuanController::class, 'dashboardFPB'])->name('dashboardFPB');


    //E-Mading Adasi
    Route::get('/ds-E-Mading-Adasi', [MadingController::class, 'dsMading'])->name('dsMading');

    //Pengajuan Subcont
    Route::get('/dashboard-pengajuan-sales', [PengajuanSubcontController::class, 'indexSales'])->name('indexSales');
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

    Route::get('/upload-json', [JsonToCsvController::class, 'showUploadForm'])->name('upload.json');
    Route::post('/convert-json-to-csv', [JsonToCsvController::class, 'convert'])->name('convert.json');

    //custom request
    Route::get('/custom-request', [CustomRequestController::class, 'showCstmReq'])->name('showCustomRequest');
    Route::post('/materials/store', [CustomRequestController::class, 'createCstmReq'])->name('CustomRequest.store');
    Route::post('/materials/delete', [CustomRequestController::class, 'deleteCstmReq'])->name('CustomRequest.delete');
    Route::put('/materials/update/{id}', [CustomRequestController::class, 'updateCstmReq'])->name('CustomRequest.update');
    Route::get('/custom-request/approve-marketing-cstmreq', [CustomRequestController::class, 'showApprovalMarketing'])->name('showApproveMarketing');
    Route::post('/custom-request/marketing-approved/{id}', [CustomRequestController::class, 'approveMarketing'])->name('approveMarketing');
    Route::get('/custom-request/approve-finance-cstmreq', [CustomRequestController::class, 'showApprovalFinance'])->name('showApproveFinance');
    Route::post('/custom-request/finance-approved/{id}', [CustomRequestController::class, 'approveFinance'])->name('approveFinance');

});
