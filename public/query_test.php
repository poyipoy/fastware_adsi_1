<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate request
$req = new \Illuminate\Http\Request();
$req->merge([
    'id_job_position' => 1,
    'id_user' => [1],
    'nilai_tc' => [1 => [4, 3]],
    'id_tc' => [1 => [10, 11]],
    'nilai_ad' => [1 => [2, 1]],
    'id_ad' => [1 => [30, 31]],
]);

// Call logic inside savePenilaian manually
$userId = 1;
$nilaiTc = $req->input('nilai_tc', []);
$nilaiAd = $req->input('nilai_ad', []);
$idTc = $req->input('id_tc', []);
$idAd = $req->input('id_ad', []);

$maxCount = max(
    count($nilaiTc[$userId] ?? []),
    count($nilaiAd[$userId] ?? [])
);

$results = [];
for ($index = 0; $index < $maxCount; $index++) {
    $nilaiTcValue = isset($nilaiTc[$userId][$index]) ? (int) $nilaiTc[$userId][$index] : null;
    $nilaiAdValue = isset($nilaiAd[$userId][$index]) ? (int) $nilaiAd[$userId][$index] : null;
    
    $idTcValue = isset($idTc[$userId][$index]) ? (int) $idTc[$userId][$index] : null;
    $idAdValue = isset($idAd[$userId][$index]) ? (int) $idAd[$userId][$index] : null;

    $results[] = [
        'match' => [
            'id_tc' => $idTcValue,
            'id_ad' => $idAdValue,
        ],
        'update' => [
            'nilai_tc' => $nilaiTcValue,
            'nilai_ad' => $nilaiAdValue,
        ]
    ];
}

echo json_encode($results);
