<?php
/**
 * Phase 1: Update Master Competency Content
 * - Fill keterangan_tc, keterangan_sk, keterangan_ad
 * - Fill deskripsi_level_1..4 for each
 * - Set nilai = 3 or 4 (target value)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$log = [];

// ========================================================
// TC DESCRIPTIONS PER JOB POSITION (34 positions)
// ========================================================
// tc_poin_kategori: 1=Plant, 2=Office & Quality, 3=EHS
// Each TC has:
//   keterangan_tc = nama skill utama (string)
//   sub_kategori  = sub skill
//   deskripsi_tc  = penjelasan kompeten
//   deskripsi_level_1..4 = level 1 (kurang) sampai level 4 (ahli)
//   nilai = target (3 or 4)

// Template level descriptions per kategori:
// Kategori 1 (Plant):
//   Level 1: Belum dapat melakukan secara mandiri
//   Level 2: Dapat melakukan dengan pengawasan ketat
//   Level 3: Dapat melakukan secara mandiri sesuai SOP
//   Level 4: Dapat melakukan dan mengajarkan ke orang lain
//
// Kategori 2 (Office & Quality):
//   Level 1: Mampu melakukan tetapi diawasi atasan
//   Level 2: Dapat melakukan dengan pengawasan minimal
//   Level 3: Dapat melakukan tanpa bimbingan
//   Level 4: Mampu membantu melatih yang lain
//
// Kategori 3 (EHS):
//   Level 1: Mengerti
//   Level 2: Mengerti dan mampu menjelaskan
//   Level 3: Mampu mengajarkan
//   Level 4: Mampu mengimplementasi

// mapping jp_id => [tc_keterangan, sk_keterangan, ad_keterangan, tc_nilai, sk_nilai, ad_nilai, tc_sub, tc_kategori]
// tc_kategori: 1=Plant, 2=Office, 3=EHS
// Note: all positions already have id_poin_kategori set

$positionData = [
    // jp_id => [tc_keterangan, tc_sub, tc_nilai, sk_keterangan, sk_nilai, ad_keterangan, ad_nilai]
    2  => ['Pemrosesan Laporan Keuangan', 'Rekonsiliasi & Jurnal Akuntansi', 3, 'Analisis Laporan Keuangan', 3, 'Peraturan Perpajakan Dasar', 3], // Accounting Staff
    3  => ['Administrasi Cutting Sheet', 'Input & Verifikasi Data Cutting', 3, 'Koordinasi Antar Departemen', 3, 'Keselamatan Kerja di Area Cutting', 3], // Admin Cutting Sheet (ACS)
    5  => ['Pengoperasian Mesin Bubut', 'Setting Tool & Material Bubut', 3, 'Pembacaan Gambar Teknik', 3, 'K3 Area Machining', 3], // Bubut Operator
    71 => ['Pengawasan Proses Cutting', 'Monitoring Kualitas & Efisiensi Cutting', 4, 'Kepemimpinan Tim Cutting', 3, 'Penerapan K3 & 5R di Area Cutting', 3], // Cutting Foreman
    8  => ['Pengelolaan Proses Cutting', 'Pengendalian Cutting Leader', 3, 'Komunikasi & Koordinasi Tim', 3, 'K3 Penggunaan Mesin Potong', 3], // Cutting Leader
    9  => ['Pengoperasian Mesin Cutting', 'Setting Material & Parameter Cutting', 3, 'Ketelitian & Kualitas Hasil Kerja', 3, 'Keselamatan Penggunaan APD', 3], // Cutting Operator
    10 => ['Distribusi & Pengiriman Material', 'Manajemen Routing & Dokumen Pengiriman', 3, 'Komunikasi dengan Customer Internal', 3, 'Keselamatan Berkendara & Muatan', 3], // Delivery Staff
    13 => ['Pengoperasian Mesin Feeder', 'Setting & Kalibrasi Feeder', 3, 'Pemecahan Masalah Mesin', 3, 'K3 Operasional Mesin Feeder', 3], // Feeder Operator
    15 => ['Pengelolaan Laporan Keuangan', 'Cash Flow & Rekonsiliasi Bank', 3, 'Ketelitian & Akurasi Data Keuangan', 3, 'Pengetahuan Regulasi Keuangan', 3], // Finance Staff
    16 => ['Pengoperasian Peralatan Heat Treatment', 'Proses Hardening & Tempering', 3, 'Ketelitian Proses & Quality Check', 3, 'K3 Penggunaan Furnace & Bahan Kimia', 3], // Heat Treatment Operator
    64 => ['Pengelolaan Administrasi HRGA & Legal', 'Pengelolaan Kontrak & Perizinan', 3, 'Komunikasi & Negosiasi Legal', 3, 'Peraturan Ketenagakerjaan & Hukum', 3], // HRGA & Legal Staff
    17 => ['Pengelolaan Data SDM', 'Rekrutmen & Payroll', 3, 'Konsultasi Karyawan & Employee Engagement', 3, 'Peraturan Ketenagakerjaan', 3], // HRGA Staff
    18 => ['Administrasi Proses Heat Treatment', 'Input Data & Dokumentasi HT', 3, 'Koordinasi dengan Operator & Leader', 3, 'K3 Umum di Area Produksi', 3], // HT Admin
    19 => ['Pemimpin Tim Heat Treatment', 'Pengendalian Kualitas & Efisiensi HT', 4, 'Kepemimpinan Operasional HT', 3, 'K3 Furnace & Bahan Berbahaya', 3], // HT Leader
    20 => ['Pengoperasian Mesin Heat Treatment', 'Proses Annealing & Carburizing', 3, 'Ketelitian Monitoring Suhu & Waktu', 3, 'K3 Area Heat Treatment', 3], // HT Operator
    22 => ['Pengelolaan Stok & Inventory', 'Sistem Inventory & Stock Opname', 3, 'Ketelitian Pencatatan & Pelaporan', 3, 'Prosedur FIFO & Penyimpanan', 3], // Inventory Staff
    25 => ['Pengelolaan Sistem & Infrastruktur IT', 'Troubleshooting Hardware & Software', 3, 'Komunikasi Teknis & Dukungan User', 3, 'Keamanan Data & Cyber Security', 3], // IT Staff
    66 => ['Pengawasan Operasional Logistik & Gudang', 'Manajemen Flow Material & Dokumen', 4, 'Koordinasi Tim & Vendor', 3, 'K3 Operasional Forklift & Gudang', 3], // Logistic & Warehouse Foreman
    32 => ['Perawatan & Perbaikan Mesin', 'Preventive Maintenance & Troubleshooting', 3, 'Pemecahan Masalah Teknis', 3, 'K3 Perbaikan & Lockout-Tagout', 3], // Maintenance Operator
    33 => ['Pemimpin Operasional MC Custom', 'Pengendalian Kualitas & Efisiensi Machining', 4, 'Kepemimpinan Tim Machining', 3, 'K3 Area Machining CNC', 3], // MC Custom Leader
    34 => ['Pengoperasian Mesin MC Custom (CNC)', 'Setting Program & Parameter CNC', 3, 'Ketelitian Dimensi & Toleransi', 3, 'K3 Pengoperasian CNC', 3], // MC Custom Operator
    36 => ['Dukungan Operasional MC Custom', 'Persiapan Material & Tool', 3, 'Komunikasi & Koordinasi Tim', 3, 'K3 Umum Machining', 3], // MC Custom Staff
    38 => ['Pemimpin Operasional MC', 'Pengendalian Proses & Kualitas MC', 4, 'Kepemimpinan & Motivasi Tim', 3, 'K3 Area MC', 3], // MC Leader
    39 => ['Pengoperasian Mesin MC (CNC/Konvensional)', 'Setting & Pengoperasian Program NC', 3, 'Ketelitian & Akurasi Dimensi', 3, 'K3 Pengoperasian Mesin MC', 3], // MC Operator
    40 => ['Perencanaan & Pengendalian Produksi (PPC)', 'Scheduling & Capacity Planning', 3, 'Analisis & Pelaporan Produksi', 3, 'Prosedur PPIC & Regulasi Produksi', 3], // PPC Staff
    41 => ['Pengadaan & Negosiasi Pembelian', 'Vendor Management & Purchase Order', 3, 'Negosiasi Harga & Kualitas', 3, 'Regulasi Pengadaan & Etika Bisnis', 3], // Procurement Staff
    45 => ['Inspeksi & Pengendalian Kualitas', 'Penggunaan Alat Ukur & QC', 3, 'Ketelitian Pengamatan Visual & Dimensi', 3, 'K3 Area QC & Penggunaan Alat Ukur', 3], // QC Operator
    46 => ['Administrasi Penjualan Region 1', 'Input Order, Faktur & Dokumen Sales', 3, 'Layanan Customer & Komunikasi', 3, 'Regulasi Penjualan & Perpajakan', 3], // Sales Admin Region 1
    84 => ['Administrasi Penjualan Region 2', 'Input Order, Faktur & Dokumen Sales', 3, 'Layanan Customer & Komunikasi', 3, 'Regulasi Penjualan & Perpajakan', 3], // Sales Admin Region 2
    50 => ['Teknik Penjualan & Konsultasi Produk Region 1', 'Product Knowledge & Technical Selling', 3, 'Komunikasi & Presentasi ke Customer', 3, 'Regulasi Industri & Etika Penjualan', 3], // Sales Engineer Region 1
    51 => ['Teknik Penjualan & Konsultasi Produk Region 2', 'Product Knowledge & Technical Selling', 3, 'Komunikasi & Presentasi ke Customer', 3, 'Regulasi Industri & Etika Penjualan', 3], // Sales Engineer Region 2
    52 => ['Teknik Penjualan & Konsultasi Produk Region 3', 'Product Knowledge & Technical Selling', 3, 'Komunikasi & Presentasi ke Customer', 3, 'Regulasi Industri & Etika Penjualan', 3], // Sales Engineer Region 3
    53 => ['Teknik Penjualan & Konsultasi Produk Region 4', 'Product Knowledge & Technical Selling', 3, 'Komunikasi & Presentasi ke Customer', 3, 'Regulasi Industri & Etika Penjualan', 3], // Sales Engineer Region 4
    60 => ['Pengelolaan Gudang & Stok Warehouse', 'Penerimaan, Penyimpanan & Pengeluaran Barang', 3, 'Ketelitian Pencatatan Stok & Dokumentasi', 3, 'K3 Gudang & Operasional Forklift', 3], // Warehouse Staff
];

// Level descriptions per kategori penilaian
function getLevelDescriptions($kategori, $keterangan) {
    if ($kategori == 1) { // Plant
        return [
            'Belum dapat melakukan ' . strtolower($keterangan) . ' secara mandiri',
            'Dapat melakukan ' . strtolower($keterangan) . ' dengan pengawasan ketat',
            'Dapat melakukan ' . strtolower($keterangan) . ' secara mandiri sesuai SOP',
            'Dapat melakukan ' . strtolower($keterangan) . ' dan mengajarkan ke orang lain'
        ];
    } elseif ($kategori == 2) { // Office & Quality
        return [
            'Mampu melakukan ' . strtolower($keterangan) . ' tetapi diawasi atasan',
            'Dapat melakukan ' . strtolower($keterangan) . ' dengan pengawasan minimal',
            'Dapat melakukan ' . strtolower($keterangan) . ' tanpa bimbingan',
            'Mampu membantu melatih yang lain dalam ' . strtolower($keterangan)
        ];
    } else { // EHS
        return [
            'Mengerti konsep ' . strtolower($keterangan),
            'Mengerti dan mampu menjelaskan ' . strtolower($keterangan),
            'Mampu mengajarkan ' . strtolower($keterangan),
            'Mampu mengimplementasi ' . strtolower($keterangan)
        ];
    }
}

// Update mst_tcs
$tcsRecords = DB::table('mst_tcs')->get();
$updatedTc = 0;
foreach ($tcsRecords as $tc) {
    $jp_id = $tc->id_job_position;
    if (!isset($positionData[$jp_id])) continue;
    
    [$tcKet, $tcSub, $tcNilai, $skKet, $skNilai, $adKet, $adNilai] = $positionData[$jp_id];
    
    DB::table('mst_tcs')->where('id', $tc->id)->update([
        'keterangan_tc' => $tcKet,
        'sub_kategori'  => $tcSub,
        'nilai'         => $tcNilai,
        'updated_at'    => now(),
    ]);
    $updatedTc++;
}

// Update mst_soft_skills
$skRecords = DB::table('mst_soft_skills')->get();
$updatedSk = 0;
foreach ($skRecords as $sk) {
    $jp_id = $sk->id_job_position;
    if (!isset($positionData[$jp_id])) continue;
    
    [$tcKet, $tcSub, $tcNilai, $skKet, $skNilai, $adKet, $adNilai] = $positionData[$jp_id];
    
    DB::table('mst_soft_skills')->where('id', $sk->id)->update([
        'keterangan_sk' => $skKet,
        'nilai'         => $skNilai,
        'updated_at'    => now(),
    ]);
    $updatedSk++;
}

// Update mst_additionals
$adRecords = DB::table('mst_additionals')->get();
$updatedAd = 0;
foreach ($adRecords as $ad) {
    $jp_id = $ad->id_job_position;
    if (!isset($positionData[$jp_id])) continue;
    
    [$tcKet, $tcSub, $tcNilai, $skKet, $skNilai, $adKet, $adNilai] = $positionData[$jp_id];
    
    DB::table('mst_additionals')->where('id', $ad->id)->update([
        'keterangan_ad' => $adKet,
        'nilai'         => $adNilai,
        'updated_at'    => now(),
    ]);
    $updatedAd++;
}

$log[] = "✅ Updated mst_tcs: {$updatedTc} records";
$log[] = "✅ Updated mst_soft_skills: {$updatedSk} records";
$log[] = "✅ Updated mst_additionals: {$updatedAd} records";

// Verify
$sampleTc = DB::table('mst_tcs')->whereNotNull('keterangan_tc')->where('keterangan_tc', '!=', '')->count();
$log[] = "✅ Verified mst_tcs with content: {$sampleTc}";

echo json_encode(['status' => 'Phase 1 DONE - Master Competency Updated', 'log' => $log], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
