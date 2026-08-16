# Review Feedback 2026-08-15 — Model Keuangan MBC

> Status: **final + dieksekusi 2026-08-16** (branch draft/excel-project-template, commit lokal, belum push). Urutan kerja bagian 5 sudah dijalankan (Step 1-6).
> Source keputusan: hasil diskusi (3 keputusan kunci + 5 klarifikasi user).
> Catatan: acuan implementasi existing ada di `MEETING.md` (bagian 5 & 7) dan `docs/AR-AP-DRAFT.md`.

## 1. Konteks

Feedback user 2026-08-15 dimulai sebagai daftar perubahan menu keuangan. Hasil evaluasi menunjukkan **mayoritas sudah masuk project** (AR/AP, Payments, Monitoring, Non-Project Expense, master Investor/Vendor/Supplier) sehingga hasil rapat dijadikan **arah restrukturisasi** (data lama tetap kepakai, menu dirapikan), **bukan** bangun dari nol dan **bukan** menghapus fitur yang sudah berjalan.

## 2. Keputusan Kunci (Terkunci)

| # | Keputusan |
|---|-----------|
| **Q1** | Payment Request & Non-Project Expense **TIDAK dihapus** — **direstrukturisasi** ke dalam satu menu kas (Cash Activity), data tetap kepakai |
| **Q2** | Buku besar / lokasi dana = **rekening fisik** (Kas Kecil, Bank BCA, Bank Mandiri, dst) |
| **Q3** | Investor = **sumber modal / pemasukan** (uang atau alat dinilai uang), **bukan** beban |
| **K1** | Hold/Release berlaku **dua sisi**: tahan **pengeluaran** (prioritas bayar by kondisi keuangan) **dan** tahan **penerimaan** yang belum tercatat |
| **K2** | Settlement History bisa **koreksi/batal** tapi **wajib approval admin** |
| **K3** | Master data **dinamis / CRUD generik** — admin bisa bikin jenis master baru bebas |
| **K4** | Voucher = **nomor seri otomatis + tanggal** saja (tanpa upload bukti/tanda tangan) |
| **K5** | Sinkron AR/AP ↔ Budget **dua arah** (koreksi di AR/AP ikut update realisasi/budget) |

## 3. Peta 11 Poin Feedback → Status

| # | Feedback | Status | Aksi |
|---|----------|--------|------|
| 1 | Payment Request → hold/release | 🔄 Remap | PR jadi bagian alur Kas; tambah **hold/release dua sisi** (K1) |
| 2 | Cashflow = Cash Activity + filter lokasi dana | 🔄+➕ | `Cashflows` → Cash Activity; **buku besar (rekening)** = baru |
| 3 | Non-project expense → Cash Activity | 🔄 Remap | Data tetap, muncul di dalam Cash Activity |
| 4 | AR/AP = Cashflow (In/Out/Transfer) | 🔄+➕ | AR/AP + Payments disatukan di menu Kas; **Fund Transfer** = baru |
| 5 | Payment = Settlement History | 🔄 Remap | Payments → Settlement History; koreksi butuh approval (K2) |
| 6 | AR bisa nambah non-project via kategori | 🔄 Remap | Non-project masuk kas; kategori dari master dinamis (K3) |
| 7 | Master data dinamis + flag AR/AP | ➕ Extend | **CRUD generik master baru** (K3/a) + penanda AR/AP |
| 8 | Filter kategori/periode/buku besar + PDF/Excel | 🔄+➕ | Export Monitoring sudah ada; **filter buku besar** = baru |
| 9 | Sesuaikan Project dengan Excel | ❓ Blocker | **Tunggu Excel final** |
| 10 | Sinkron AR/AP ↔ Budget | 🔄 Extend | Actual otomatis sudah ada; jadi **dua arah** (K5/b) |
| 11 | Cash in/out model voucher | ➕ Baru | **Voucher** = nomor seri otomatis + tanggal (K4/a) |

## 4. Backlog Final

### ➕ Baru (belum ada)
- Buku besar / rekening fisik + filter lokasi dana (#2, #8)
- Fund Transfer antar kas (#4)
- Master **CRUD dinamis** + flag AR/AP (#7, K3/a)
- Model Voucher (nomor otomatis + tanggal) (#11, K4/a)
- Status **hold/release** Cash In & Out + prioritas bayar (K1)
- Workflow **approval admin** untuk batal/koreksi Settlement (K2)

### 🔄 Remap / Reuse (sudah ada)
- Payment Request & Non-Project Expense → masuk modul Kas, data tetap (#1, #3)
- AR/AP & Payments → satu menu Kas; Payments = Settlement History + approval koreksi (#4, #5)
- Actual sinkron → dua arah (K5/b)

### ❓ Blocker
- Penyesuaian Project dengan Excel (#9) — tunggu final

## 5. Urutan Kerja yang Disarankan (setelah Excel final)

1. **Modul Kas/Cash Activity terpadu** (#1, #2, #3, #4, #5) — satu menu adalah fondasi semua.
2. **Buku besar (rekening) + Fund Transfer** (#2, #4) — dasar lokasi dana.
3. **Voucher** (#11) — sederhana (K4).
4. **Workflow approval Settlement** (K2) — susul setelah modul kas stabil.
5. **Master CRUD dinamis** (#7, K3) — paling besar & berisiko, dikerjakan terpisah.
6. **Sinkron dua arah AR/AP↔Budget** (#10, K5) — terakhir, paling rawan inkonsistensi.

## 6. Open Items Sebelum Eksekusi

- **❓ Excel project belum final** — gerbang utama; tanpa ini model inti (COA/Akun/Realisasi) jangan disentuh.
- ⚠️ Detil prioritas bayar (K1) — sumber prioritas dari mana (manual / status keuangan / threshold)?
- ⚠️ Detil "flag AR/AP" pada master (K3) — satu master bisa nyala AR & AP, atau pisah kolom?
