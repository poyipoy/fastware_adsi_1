# ATURAN RANTAI APPROVAL: KASUS RICHARDUS CHRISTIAN (SUB-SECTION HEAD)

## Konteks
Di master data (`Mapping (OK)`), section **Finance** dan section **Accounting** adalah dua section yang terpisah, masing-masing punya nama section sendiri. Tapi keduanya **digabung di bawah satu Section Head yang sama**, yaitu **Adhi Prasetiyo**, dengan job title *"Finance Accounting Sec Head"*.

Sementara itu, **Richardus Christian** punya job title *"Finance Sec Head"* — secara harfiah namanya mengandung kata "Sec Head", tapi dia **BUKAN** section head tertinggi untuk Finance. Dia berada **satu level di bawah** Adhi Prasetiyo.

## Catatan asli dari sumber data (WAJIB dipatuhi, ini override terhadap asumsi default)
> *"PENTING: Ada Kasus unik untuk RICHARDUS yaitu dia merupakan Finance sec head yang harus melewati Finance Accounting Sec Head dulu (ADHI PRASETIYO karena dia pegang 2 section yaitu finance dan accounting). Jadi alurnya: Employee > Sub section head > section head > dept head."*

## Aturan yang harus diimplementasikan

**JANGAN** menganggap Richardus Christian sebagai Section Head final untuk staff Finance. Dia adalah **Sub-Section Head**.

Rantai approval / approver chain yang BENAR untuk setiap staff yang section-nya = `Finance` adalah **4 level**, bukan 3 level:

```
Employee (staff Finance)
   → Richardus Christian     [role: Sub-Section Head]
      → Adhi Prasetiyo       [role: Section Head, menaungi Finance + Accounting]
         → Martinus Cahyo Rahasto  [role: Dept. Head, Finance Accounting & HRGA]
```

**Salah** (jangan implementasi seperti ini):
```
Employee → Richardus Christian → Martinus Cahyo Rahasto   ❌ (skip 1 level, Adhi terlewat)
```

## Batasan cakupan aturan ini
- Aturan 4 level ini **HANYA berlaku untuk staff dengan Section = `Finance`**.
- Staff dengan Section = `Accounting` tetap alur normal 3 level: `Employee → Adhi Prasetiyo (Section Head) → Martinus Cahyo Rahasto (Dept. Head)` — karena Adhi langsung jadi atasan mereka, tidak ada sub-section head di tengah untuk Accounting.
- Adhi Prasetiyo sendiri (sebagai Section Head) **tidak** melalui Richardus — posisinya di atas Richardus, bukan sejajar atau di bawahnya.

## Implementasi teknis (jika berupa kode/database)
Saat membangun tabel/relasi approval chain:
1. Tambahkan field/kolom yang membedakan level approver: `sub_section_head_id`, `section_head_id`, `dept_head_id` (bukan cuma satu kolom generik `approver_id` yang cuma 1 level) — supaya kasus 4-level seperti Richardus bisa direpresentasikan tanpa hack.
2. Untuk **semua employee lain** yang section-nya bukan `Finance`, kolom `sub_section_head_id` boleh `NULL`, alur tetap 3 level standar (`Employee → Section Head → Dept. Head`).
3. Jangan generalisasi pola ini ke section lain secara otomatis — ini murni kasus khusus by-design dari struktur organisasi Finance & Accounting, bukan pola umum di seluruh perusahaan (lihat kasus lain: Mugi Pramono dan Hardi Saputra punya pola khusus yang BEDA, jangan disamakan logikanya).

## Validasi
Setelah implementasi, pastikan:
- [ ] Seluruh staff section `Finance` (bukan Accounting) rantai approvalnya 4 level, melewati Richardus **lalu** Adhi
- [ ] Staff section `Accounting` tetap 3 level standar via Adhi langsung
- [ ] Richardus Christian sendiri, jika perlu di-approve/dinilai kinerjanya, naik ke Adhi Prasetiyo (bukan langsung ke Martinus)