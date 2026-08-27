<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\Api\SalesVisitController;
use App\Http\Controllers\Api\ServerDrivenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route login tidak perlu middleware auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('auth/login', [ServerDrivenController::class, 'login']);


// Protected routes require Sanctum token
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/sales-visit', [SalesVisitController::class, 'store']);
    Route::get('/sales-visit-list', [SalesVisitController::class, 'index']);
    Route::get('/sales-visit-detail', [SalesVisitController::class, 'getSalesVisitDetail']);
    Route::get('/sales-plan-list', [SalesVisitController::class, 'indexplan']);
    Route::get('/sales-chart', [SalesVisitController::class, 'chart']);
    Route::patch('/sales-update/{id}', [SalesVisitController::class, 'update']);
    Route::get('/customers-list', [SalesVisitController::class, 'customerList']);
    Route::post('/planning/submit', [SalesVisitController::class, 'submitPlanning']);
    Route::get('dept-head/sales-performance', [SalesVisitController::class, 'getSalesPerformance']);
    Route::get('dept-head/sales-users', [SalesVisitController::class, 'getSalesUsers']);
    Route::get('dashboard-data', [SalesVisitController::class, 'chartadmin']);
    Route::get('/sales-performance-list', [SalesVisitController::class, 'indexsales']);
    Route::get('/sales-regions', [SalesVisitController::class, 'getAvailableRegions']);
    Route::get('sales-visit/{visit}/files/download', [SalesVisitController::class, 'downloadAll'])->name('visit.files.downloadAll');
    Route::get('sales-visit/{visit}/files/{fileName}', [SalesVisitController::class, 'downloadSingle'])->name('visit.files.single');
    Route::get('/depthead/data', [SalesVisitController::class, 'getDeptHeadData']);
    Route::get('/logbook-visits', [SalesVisitController::class, 'getFilteredVisits']);
    Route::get('/reports/visits/summary',  [SalesVisitController::class, 'summary']);
    Route::get('/sales-summary', [SalesVisitController::class, 'salesSummary']);
    Route::get('/reports/visits/download', [SalesVisitController::class, 'downloadVisits']);

    Route::middleware('can:viewTcpdDashboard')->group(function () {
        Route::get('dashboard/tcpd', [ApiDashboardController::class, 'tcpdOverview']);
        Route::get('dashboard/tcpd/company', [ApiDashboardController::class, 'getTcpdCompanyData']);
        Route::get('dashboard/tcpd/sensitive', [ApiDashboardController::class, 'getTcpdSensitiveData']);
        Route::get('dashboard/tcpd/job', [ApiDashboardController::class, 'getTcpdCompetencyData']);
    });

    Route::get('navigation/drawer', [ServerDrivenController::class, 'drawer']);

    Route::get('pages/{route}', [ServerDrivenController::class, 'page'])
         ->where('route', '.*');

    Route::post('pages/{route}/submit', [ServerDrivenController::class, 'submit'])
         ->where('route', '.*');

    // Rute logout dari controller Anda sebelumnya.
    // Ini dipanggil oleh onLogout() di HomeScreen Anda.
    Route::post('logout', [ServerDrivenController::class, 'logout']);

    // Rute user standar (opsional, tapi sering berguna)
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/check-seed-data', function() {
        $jsonPath = base_path('database/data/karyawan_job_position_seed (1).json');
        if (!file_exists($jsonPath)) {
            $jsonPath = base_path('karyawan_job_position_seed (1).json');
        }
        if (!file_exists($jsonPath)) return response()->json(['error' => 'JSON not found']);
        $jsonData = json_decode(file_get_contents($jsonPath), true);
        $karyawanList = $jsonData['karyawan'];

        $users = \App\Models\User::all()->pluck('name')->toArray();
        $usersLower = array_map('strtolower', $users);

        $positions = \App\Models\MstJobPosition::all()->pluck('position_name')->toArray();
        $positionsLower = array_map('strtolower', $positions);

        $missingUsers = [];
        $missingPositions = [];

        foreach ($karyawanList as $k) {
            $name = trim($k['nama_karyawan']);
            $job = trim($k['job_position']);

            if (!in_array(strtolower($name), $usersLower)) {
                if (!in_array($name, $missingUsers)) {
                    $missingUsers[] = $name;
                }
            }

            if (!in_array(strtolower($job), $positionsLower)) {
                if (!in_array($job, $missingPositions)) {
                    $missingPositions[] = $job;
                }
            }
        }

        return response()->json([
            'total_json_records' => count($karyawanList),
            'missing_users' => $missingUsers,
            'missing_positions' => $missingPositions
        ]);
    });

    // Update FCM Token
    Route::post('user/fcm-token', [AuthController::class, 'updateFcmToken']);
});
