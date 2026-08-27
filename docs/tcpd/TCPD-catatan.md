BASE COMPETENCY - catatan

Module buat manage kompetensi karyawan. Ada 3 jenis: Technical Competency, Soft Skills, sama Additional Competencies.

Menu ada di dropdown Human Resource, ada 6 submenu: Form Job Position, Form Pengajuan Competency, Penilaian TC Ka. Sie, Penilaian TC Ka. Dept, Penilaian TC HR, sama Summary Competency. Akses dikontrol pake nama user, hardcoded di controller.


ROUTES (web.php)

/job ke TcJobController@jobShow
/tcShow ke TcController@tcShow  
/summary/index ke TcController@summaryData
/penilaian ke PenilaianTCController@indexTrs (ada 3 versi sie/dept/hr)
/buat-penilaian buat create form
/save-penilaian buat save data
update-status buat kirimSC sama kirimDept

Note: ada typo "sumarry" di route tapi jangan diubah, udah kepake dimana-mana


CONTROLLERS

TcJobController (222 lines)
jobShow() - list job position, group by job_position dan status
store() - simpan job position baru, loop setiap user, set status jadi 1
getUserRole() - return JSON role dari user

TcController (919 lines) - yang paling gede
tcShow() - form pengajuan competency, filter by nama user, admin liat semua, user lain cuma job position tertentu
summaryData() - summary competency  
fetchDetails() - AJAX ambil detail TC/SK/AD by job position

PenilaianTCController (1334 lines)
indexTrs/2/3() - list penilaian sie/dept/hr, ada special handling SITI MARIA ULFA
createPenilaian() - form buat penilaian
savePenilaian() - save
kirimSC() - status sie ke dept
kirimDept() - status dept ke hr
helper methods: getJobPositionData, getJobPositionDataEdit, getNilaiDataEdit, getJobPointKategori


DATABASE MODELS

TcJobPosition (table: tc_job_positions)
Field: id_user, id_role, job_position, status (1 itu aktif)

MstTc (table: mst_tcs), MstSoftSkill (table: mst_soft_skills), MstAdditionals (table: mst_additionals)
Field: id_job_position, id_poin_kategori, keterangan, deskripsi, nilai
Strukturnya sama semua cuma beda nama field aja

PoinKategori (table: tc_poin_kategoris)
Buat kategori/level penilaian, ada deskripsi_1 sampe deskripsi_4

TrsPenilaianTc (table: trs_penilaian_tcs) - table transaksi
Field: id_tc, id_sk, id_ad, id_job_position, id_user, nilai_tc, nilai_sk, nilai_ad, total_nilai
Status: 1 itu draft, 2 menunggu dept, 3 approved


VIEWS

tc_job/tc_job.blade.php 
List job position, button add job position buka modal, badge biru buat aktif

mst_tc/tc_index.blade.php
Form pengajuan competency, pake datatable, font Cambria

tc_penilaian/penilaian_index.blade.php
List penilaian, button buat penilaian, status badge (draft abu-abu, menunggu kuning, approved hijau), ada logic khusus SITI MARIA ULFA

mst_tc/summary_view.blade.php
Dropdown pilih job position, munculin 3 table TC/SK/AD, data dari AJAX fetchDetails


WORKFLOW STATUS

Status 1 (draft) terus sie bisa edit/kirim ke Status 2 (menunggu dept) terus dept approve jadi Status 3 (approved) terus hr final review. Transisi pake method kirimSC() sama kirimDept()


ACCESS CONTROL

Hardcoded pake array nama user di controller. Form Job Position cuma ADMINSTRATOR, JESSICA PAUNE, SITI MARIA ULFA. Form Pengajuan setiap user beda-beda: JESSICA PAUNE liat semua, HARDI SAPUTRA cuma warehouse/ACS/delivery, MUGI PRAMONO cuma HT positions, etc.


CATATAN

Banyak folder backup "(1)" sama "311225 -" perlu cleanup
Status pake integer, bisa bikin enum biar jelas
Filter job position repetitive, pindah ke service/database
Typo "sumarry" jangan diubah, udah kepake
Access control hardcoded, scale up pake Spatie Permission


FILE STRUCTURE

Controllers: TcJobController (222), TcController (919), PenilaianTCController (1334)
Models: TcJobPosition, MstTc, MstSoftSkill, MstAdditionals, PoinKategori, TrsPenilaianTc
Views: tc_job, mst_tc (index/create/summary), tc_penilaian (index/dept)


TODO

Cleanup folder backup
Bikin enum status
Access control ke database/Spatie
Konsolidasi 3 method penilaian
Proper logging
Unit test
Optimize query N+1 di TcController


QUICK REFERENCE

Tambah user akses: cari controller terus cari method terus tambah nama di array in_array

Tambah job position: login ADMINSTRATOR/JESSICA/SITI terus menu HR, Base Competency, Form Job Position, Add

Ubah status workflow: PenilaianTCController terus kirimSC (sie ke dept) atau kirimDept (dept ke approved)


ALUR KERJA TABLE DATABASE

Table utama yang digunakan:

mst_additionals
Field: id, id_job_position, id_poin_kategori, keterangan_ad, deskripsi_ad, nilai, timestamps
Fungsi: nyimpen data additional competencies
Relasi: ke tc_job_positions lewat id_job_position, ke tc_poin_kategoris lewat id_poin_kategori
Contoh data: LK3 Awareness, 5R, Fire safety, First aid (nilai 1-4)

mst_soft_skills  
Field: id, id_job_position, id_poin_kategori, keterangan_sk, deskripsi_sk, nilai, timestamps
Fungsi: nyimpen data soft skills
Relasi: ke tc_job_positions lewat id_job_position, ke tc_poin_kategoris lewat id_poin_kategori
Contoh data: Leadership, Communication Skills, Problem solving, Bahasa Inggris/Jepang (nilai 1-4)

mst_tcs
Field: id, id_job_position, id_poin_kategori, keterangan_tc, deskripsi_tc, nilai, timestamps
Fungsi: nyimpen data technical competencies
Relasi: sama kayak yang lain

tc_job_positions
Field: id, id_user, id_role, job_position, status, timestamps
Fungsi: master data posisi kerja
Status: 1 aktif, 0 tidak aktif

tc_poin_kategoris
Field: id, judul_keterangan, deskripsi_1, deskripsi_2, deskripsi_3, deskripsi_4, timestamps
Fungsi: kategori penilaian dengan 4 level deskripsi
Nilai 1-4 merujuk ke deskripsi masing-masing

trs_penilaian_tcs
Field: id, id_tc, id_sk, id_ad, id_job_position, id_user, nilai_tc, nilai_sk, nilai_ad, total_nilai, status, modified_at, modified_updated, timestamps
Fungsi: transaksi penilaian
Status: 1 draft, 2 menunggu dept, 3 approved


FLOW DATA

1. Bikin job position dulu di tc_job_positions (via TcJobController)
2. Input competency (TC/SK/AD) yang linked ke job position (via TcController)
3. Buat penilaian di trs_penilaian_tcs yang reference ke TC/SK/AD tadi (via PenilaianTCController)
4. Update status penilaian sesuai approval workflow
5. Summary bisa dilihat dengan fetch data dari ketiga master table


SAMPLE DATA PATTERNS

Soft Skills populer:
- Leadership (nilai 2-4)
- Communication Skills (nilai 3)
- Problem solving (nilai 3-4)
- Bahasa Inggris intermediate (nilai 2-3)
- Bahasa Jepang basic (nilai 1)
- Persuasive Sales (nilai 3-4)

Additional Competencies populer:
- LK3 Awareness (nilai 2-4)
- 5R (nilai 2-4)
- Fire safety (nilai 2)
- First aid (nilai 2)
- K3 (nilai 1-2)
- EHS (nilai 2)

Technical Competencies:
- Spesifik per job position
- Nilai range 1-4 sesuai level expertise


NOTES DATABASE

Data id pake bigint unsigned zerofill (20 digit) buat mst_additionals
Data id pake bigint biasa buat mst_soft_skills
Banyak data yang id_poin_kategori NULL (belum di-set)
Beberapa entry duplikat karena sistem insert multiple kali
Timestamps otomatis updated on update
Collation utf8mb4_general_ci


Last update Feb 2026