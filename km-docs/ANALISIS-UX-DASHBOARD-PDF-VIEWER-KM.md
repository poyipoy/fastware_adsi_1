# ANALISIS UX DASHBOARD DAN PDF VIEWER
## Knowledge Management

> **Status:** UX Audit and Redesign Recommendation  
> **Scope:** Dashboard Knowledge Management dan pengalaman membaca PDF  
> **Sumber analisis:** `dsKnowlege.blade.php` dan `pdf-viewer.js`

---

## Ringkasan Eksekutif

Masalah utama pada dashboard dan PDF viewer bukan hanya visual, tetapi struktur pengalaman pengguna.

Dashboard saat ini lebih berorientasi pada kumpulan komponen daripada tugas utama pengguna. Pengguna seharusnya dapat dengan cepat:

1. Mengetahui dokumen yang perlu dibaca.
2. Melanjutkan bacaan terakhir.
3. Melihat assignment yang mendekati tenggat.
4. Menemukan dokumen yang relevan.
5. Memahami progres membaca.
6. Menyelesaikan pembelajaran dengan aturan completion yang jelas.

PDF viewer juga masih terasa seperti modal preview, bukan ruang baca yang nyaman. Area baca terlalu kecil, navigasi terputus, zoom berpotensi membingungkan, tidak ada resume reading yang nyata, dan penutupan viewer melakukan reload halaman yang tidak diperlukan.

---

# 1. Analisis Dashboard

## 1.1 Leaderboard Terlalu Dominan

Leaderboard menempati satu kolom penuh di sisi kiri. Sementara itu, tugas utama halaman adalah menemukan dan membaca dokumen.

### Dampak

- Ruang horizontal dokumen berkurang.
- Card dokumen menjadi lebih sempit.
- Pada tablet, proporsi layout menjadi tidak efisien.
- Fitur gamifikasi tampil lebih penting daripada pembelajaran.

### Rekomendasi

Pindahkan leaderboard ke:

- bagian bawah dashboard;
- panel sekunder yang dapat ditutup;
- tab khusus gamifikasi;
- atau section ringkas setelah daftar dokumen.

Bagian teratas dashboard sebaiknya memprioritaskan:

- lanjutkan membaca;
- assignment mendekati tenggat;
- dokumen baru;
- ringkasan progres pengguna.

---

## 1.2 Filter Terlalu Padat

Filter saat ini memuat:

- pencarian;
- kategori;
- status baca;
- urutan;
- bookmark;
- submit;
- reset;
- tag.

Semua ditampilkan dalam satu area yang relatif padat.

### Dampak

- Banyak kontrol tidak memiliki label visual.
- Bookmark hanya ditampilkan sebagai ikon.
- Target klik relatif kecil.
- Pada mobile, filter menjadi panjang dan kurang nyaman.
- Pengguna harus mengambil terlalu banyak keputusan sebelum melihat konten.

### Rekomendasi

- Jadikan search sebagai kontrol utama berukuran besar.
- Pindahkan kategori, status, urutan, dan tag ke area filter sekunder.
- Gunakan collapse atau offcanvas untuk filter pada mobile.
- Gunakan teks `Baca nanti`, bukan hanya ikon bookmark.
- Tampilkan jumlah hasil, misalnya:
  - `24 dokumen ditemukan`.
- Tampilkan tombol reset hanya ketika ada filter aktif.
- Terapkan salah satu pola secara konsisten:
  - filter otomatis ketika select berubah; atau
  - satu tombol `Terapkan filter`.

---

## 1.3 Card Belum Membantu Pengguna Mengambil Keputusan

Card menampilkan banyak informasi, tetapi prioritasnya belum jelas:

- thumbnail;
- judul;
- kategori;
- estimasi waktu;
- tag;
- status;
- view;
- sinopsis;
- baca;
- like;
- komentar;
- bookmark.

### Masalah

- Judul dipotong menjadi satu baris.
- Sinopsis disembunyikan dalam modal tambahan.
- Status baca hanya berupa badge.
- Tidak ada progress bar.
- Tidak ada informasi halaman terakhir.
- Aksi sosial terlihat hampir setara dengan aksi membaca.
- Thumbnail memakai `object-fit: cover`, sehingga halaman dokumen dapat terpotong.
- Modal sinopsis dan insight dibuat per dokumen sehingga DOM menjadi besar.

### Rekomendasi struktur card

```text
[Thumbnail dokumen utuh]

Kategori                            Baca nanti

Judul dokumen maksimal 2–3 baris
Sinopsis singkat maksimal 2 baris

████████░░  74% · Halaman 18 dari 24
Terakhir dibaca kemarin

[Lanjutkan membaca]

12 dilihat · 3 insight
```

### Label tombol berdasarkan state

- Belum dibaca → `Mulai membaca`
- Sedang dibaca → `Lanjutkan halaman 18`
- Selesai → `Baca kembali`
- File belum tersedia → `File belum tersedia`

### Aksi sekunder

Like, insight, dan bookmark harus terlihat lebih tenang dibanding aksi membaca.

---

## 1.4 Tidak Ada Orientasi Personal

Dashboard belum menjawab pertanyaan utama pengguna:

- Apa yang harus saya baca?
- Apa yang belum selesai?
- Apa yang hampir jatuh tempo?
- Di halaman mana saya berhenti?
- Apa yang baru sejak kunjungan terakhir?

### Rekomendasi struktur dashboard

```text
┌───────────────────────────────────────────────────┐
│ Selamat datang kembali                            │
│ 2 bacaan belum selesai · 1 tugas jatuh tempo      │
└───────────────────────────────────────────────────┘

┌────────────────── Lanjutkan membaca ──────────────┐
│ Dokumen terakhir + progress + tombol lanjut       │
└───────────────────────────────────────────────────┘

[Search dokumen..................................]

[Semua] [Belum dibaca] [Sedang dibaca] [Selesai]

Dokumen untuk Anda                         24 hasil
[card] [card] [card]

Dokumen populer / Leaderboard
```

---

# 2. Analisis PDF Viewer

## 2.1 Modal Tidak Cocok untuk Pengalaman Membaca

Viewer menggunakan modal besar, tetapi area baca minimum hanya sekitar `60vh`.

### Dampak

- Terasa seperti preview singkat.
- Toolbar dan footer mengurangi ruang baca.
- Pengguna tidak mendapatkan pengalaman membaca yang fokus.
- Kurang nyaman untuk dokumen panjang.

### Rekomendasi

Gunakan salah satu:

1. Modal fullscreen.
2. Halaman viewer khusus.

Untuk kondisi saat ini, modal fullscreen merupakan perubahan paling aman.

```html
<div class="modal-dialog modal-fullscreen">
```

Pada mobile, toolbar harus disederhanakan menjadi satu atau dua baris.

---

## 2.2 Zoom Berpotensi Tidak Terasa Benar

Canvas memiliki batas:

```html
max-width: 100%;
```

Sementara JavaScript memperbesar ukuran canvas ketika zoom.

### Dampak

Canvas dapat tetap dipaksa masuk ke lebar container sehingga perubahan zoom tidak terlihat sesuai harapan.

### Rekomendasi

- Hapus `max-width: 100%` ketika mode zoom manual.
- Gunakan wrapper dengan `overflow: auto`.
- Pisahkan mode:
  - Fit width.
  - Fit page.
  - Custom zoom.

---

## 2.3 Label `100%` Menyesatkan

Render menggunakan auto-scale terhadap lebar container:

```js
const autoScale = containerWidth / unscaledViewport.width;
const finalScale = autoScale * _currentScale;
```

`_currentScale = 1` sebenarnya berarti fit-to-container, bukan ukuran PDF asli 100%.

### Rekomendasi

Pisahkan `fitMode` dan `zoomScale`.

Contoh label:

- `Lebar halaman`
- `Muat satu halaman`
- `125%`
- `150%`

---

## 2.4 Navigasi Halaman Terasa Berkedip

Setiap pergantian halaman menampilkan state loading dan menyembunyikan canvas.

### Dampak

- Bacaan terasa terputus.
- Pengguna kehilangan konteks visual.
- Navigasi terasa lambat walaupun render sebenarnya cepat.

### Rekomendasi

- Pertahankan halaman lama sampai halaman baru selesai dirender.
- Gunakan indikator loading kecil pada toolbar.
- Prefetch halaman sebelum dan sesudah halaman aktif.
- Cache halaman yang baru saja dirender.

---

## 2.5 Tidak Mendukung Continuous Reading

Viewer hanya menampilkan satu halaman.

### Dampak

- Pengguna harus terus menekan tombol Next.
- Tidak nyaman untuk dokumen panjang.
- Tidak menyerupai pola membaca PDF modern.

### Rekomendasi

Mode default:

- Desktop: continuous vertical scroll dan thumbnail sidebar.
- Mobile: continuous vertical scroll tanpa sidebar.
- Single-page mode tetap dapat disediakan sebagai alternatif.

---

## 2.6 Resume Reading Belum Nyata

Viewer belum menyimpan:

- halaman terakhir;
- persentase;
- waktu aktif;
- posisi scroll;
- timestamp terakhir.

### Rekomendasi

Viewer harus:

- membuka dokumen pada halaman terakhir;
- menyimpan progress ketika halaman berubah;
- menyimpan progress dengan debounce;
- melakukan final flush ketika modal ditutup;
- menampilkan informasi:
  - `Terakhir sampai halaman 18`.

---

## 2.7 Tombol Tutup Melakukan Reload Halaman

Saat modal ditutup, halaman direload.

### Dampak

- Posisi scroll dashboard hilang.
- Filter atau state tampilan dapat berubah.
- Network request tambahan terjadi.
- Interaksi terasa lambat.

### Rekomendasi

Tutup modal tanpa reload.

```html
<button type="button"
        class="btn btn-secondary"
        data-bs-dismiss="modal">
    Tutup
</button>
```

Update card dilakukan secara lokal setelah progress tersimpan.

---

## 2.8 Completion Belum Dijelaskan dengan Baik

Tombol `Selesai Membaca` tersedia, tetapi UI belum menjelaskan syarat completion.

Completion resmi tidak boleh ditentukan hanya dari membuka halaman terakhir.

### Rekomendasi UI

Tampilkan:

```text
Progres membaca: 90%
Waktu aktif: 8 menit
Konfirmasi pemahaman diperlukan
```

Tombol completion hanya aktif ketika syarat minimum terpenuhi.

Jika belum:

```text
Selesaikan minimal 90% halaman untuk menandai selesai.
```

---

## 2.9 Fitur Pembacaan Penting Belum Ada

Fitur yang belum tersedia:

- input nomor halaman;
- shortcut keyboard;
- fullscreen;
- fit width;
- fit page;
- rotasi;
- pencarian teks;
- pemilihan/copy teks;
- thumbnail navigation;
- progress reading;
- retry button;
- restore page;
- high-DPI rendering;
- resize/orientation handling.

### Prioritas implementasi

1. Fullscreen.
2. Navigasi tanpa flicker.
3. Resume halaman terakhir.
4. Fit width.
5. Input nomor halaman.
6. Keyboard navigation.
7. Progress completion.
8. Responsive toolbar.

---

# 3. Masalah Teknis Tambahan

## 3.1 CSS Dashboard Dimuat Dua Kali

CSS dimuat melalui:

```php
<link rel="stylesheet" href="{{ asset('css/km/dashboard.css') }}">
```

dan:

```php
@vite([
    'resources/js/km/dashboard.js',
    'resources/js/km/pdf-viewer.js',
    'resources/css/km/dashboard.css'
])
```

### Dampak

- Style duplikat.
- Risiko specificity conflict.
- Versi CSS public dan Vite dapat berbeda.
- Perubahan tidak konsisten.

### Rekomendasi

Gunakan satu sumber saja.

Karena aplikasi sudah memakai Vite, hapus:

```php
<link rel="stylesheet" href="{{ asset('css/km/dashboard.css') }}">
```

---

## 3.2 Modal Per Dokumen Membesarkan DOM

Setiap dokumen membuat:

- satu modal sinopsis;
- satu modal insight.

### Dampak

Jika halaman memiliki banyak card, jumlah node DOM dan modal menjadi besar.

### Rekomendasi

Gunakan modal global reusable:

- satu modal sinopsis;
- satu modal insight;
- isi modal dimuat berdasarkan dokumen aktif.

---

## 3.3 Inline Style dan Inline Event Handler

View masih menggunakan:

- `style=""`;
- `onclick=""`;
- `onerror=""`.

### Dampak

- Sulit dipelihara.
- Event logic tersebar.
- Menyulitkan testing.
- Sulit mengikuti Content Security Policy yang lebih ketat.

### Rekomendasi

- Pindahkan style ke CSS class.
- Gunakan `data-*` attribute.
- Pasang event listener melalui module JavaScript.

---

# 4. Arah Redesign yang Disarankan

## Dashboard

- Header personal dengan ringkasan tugas.
- Section `Lanjutkan membaca`.
- Search sebagai elemen utama.
- Filter sekunder dalam collapse/offcanvas.
- Card berorientasi progress.
- Leaderboard dipindahkan ke bagian bawah.
- Sinopsis singkat tampil langsung di card.
- Modal sinopsis dan insight dibuat global.
- Jumlah hasil dan filter aktif dibuat jelas.
- Tombol utama mengikuti state dokumen.

## PDF Viewer

- Modal fullscreen.
- Toolbar sticky.
- Judul dan metadata dokumen terlihat.
- Fit width sebagai default.
- Input nomor halaman.
- Keyboard arrow navigation.
- Tidak reload ketika modal ditutup.
- Simpan dan pulihkan halaman terakhir.
- Progress bar membaca.
- Completion hanya aktif bila persyaratan terpenuhi.
- Toolbar mobile disederhanakan.
- High-DPI canvas.
- Re-render saat resize.
- Error state memiliki tombol:
  - `Coba lagi`;
  - `Unduh file`.

---

# 5. Prioritas Implementasi

## Prioritas 1 — Dampak terbesar

- Ubah viewer menjadi fullscreen.
- Hilangkan reload ketika viewer ditutup.
- Perbaiki logika zoom.
- Simpan halaman terakhir.
- Tampilkan progress pada card.
- Ganti tombol menjadi `Mulai membaca`, `Lanjutkan`, atau `Baca kembali`.

## Prioritas 2 — Peningkatan alur

- Tambahkan section `Lanjutkan membaca`.
- Pindahkan leaderboard.
- Sederhanakan filter.
- Tampilkan jumlah hasil.
- Gunakan modal global untuk insight dan sinopsis.

## Prioritas 3 — Peningkatan pembacaan

- Continuous scroll.
- Thumbnail navigation.
- Input halaman.
- Keyboard shortcut.
- Fit page.
- Retry state.
- High-DPI canvas.
- Prefetch halaman.

---

# 6. Acceptance Criteria Awal

## Dashboard

- Pengguna dapat melihat dokumen terakhir yang sedang dibaca tanpa melakukan pencarian.
- Setiap card menampilkan status dan progress yang jelas.
- Tombol utama berubah sesuai status baca.
- Search dapat digunakan tanpa membuka filter lanjutan.
- Filter aktif dapat dihapus satu per satu.
- Leaderboard tidak mengambil ruang utama dokumen.
- Dashboard berfungsi pada lebar 320 CSS px.
- Tidak ada modal per dokumen untuk sinopsis dan insight.
- CSS dashboard hanya dimuat dari satu sumber.

## PDF Viewer

- Viewer menggunakan area hampir penuh pada desktop dan mobile.
- Dokumen dibuka pada halaman terakhir yang tersimpan.
- Menutup viewer tidak me-reload dashboard.
- Zoom memiliki mode fit width, fit page, dan custom scale.
- Navigasi halaman tidak menghilangkan canvas lama sebelum halaman baru siap.
- Progress disimpan secara idempotent.
- Completion hanya aktif ketika syarat terpenuhi.
- Toolbar dapat digunakan dengan keyboard.
- Tombol dan control memiliki accessible label.
- Error state menyediakan retry dan download.
- Viewer tetap usable ketika koneksi lambat.
- Resize browser tidak merusak ukuran canvas.

---

# 7. Kesimpulan

Perbaikan UX paling bernilai bukan mempercantik card atau menambah animasi, tetapi mengubah orientasi sistem dari:

> daftar dokumen dan kumpulan kontrol

menjadi:

> pengalaman pembelajaran personal yang membantu pengguna menemukan, melanjutkan, dan menyelesaikan bacaan.

Perubahan paling kritis adalah:

1. Menempatkan progress dan assignment di pusat dashboard.
2. Membuat card berorientasi tindakan.
3. Mengubah PDF viewer menjadi ruang baca fullscreen.
4. Menyimpan dan memulihkan progress.
5. Menghilangkan reload dan flicker.
6. Menjelaskan syarat completion secara transparan.