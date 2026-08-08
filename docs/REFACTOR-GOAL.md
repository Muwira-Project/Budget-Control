# Blueprint Refactor: Muwira Budget Control → Budget Control System

> Status: desain terkunci (2026-08-04). Eksekusi bertahap, tiap fase di-gate tes.
> Menyusul pembahasan dengan user; detail AR/AP di `docs/AR-AP-DRAFT.md`.

## 1. Tujuan

Refactor menjadi sistem yang mengikuti siklus keuangan perusahaan:

```
Pendapatan → Cashflow → Budget Planning → Penentuan Prioritas Pembayaran
→ Alokasi Dana → Payment Request → Approval → Realisasi → AP/AR
→ Dashboard & Laporan
```

Semua fitur lama dipertahankan: Dashboard, Project, Akun (jadi COA), Realisasi,
Audit Log, Import/Export, Company Profile.

## 2. Ketentuan inti

- **Akun → Chart of Accounts (COA)**: 19 akun tetap sebagai master, namun **fleksibel**
  (bisa ditambah/dikurangi user). Project TIDAK membuat akun baru — project
  **mengalokasikan budget ke akun master**.
- **Budget Planning** (sebelum alokasi): periode, estimasi pendapatan, estimasi biaya,
  target laba, rincian budget per akun.
- **Penentuan Prioritas Pembayaran**: urut berdasarkan saldo kas, jatuh tempo, prioritas
  (High/Medium/Low).
- **Budget Allocation**: tiga nilai utama — Budget, Allocation, Realisasi.
  Allocation hanya bisa dibuat setelah disetujui.
- **Payment Request + Approval Workflow**: Draft → Waiting Approval → Approved →
  Paid → Closed/Cancelled. Semua perubahan tercatat di Audit Log.
- **Vendor vs Supplier**: Vendor = penyedia jasa; Supplier = penyedia material/barang.
- **Cashflow**: mencatat Cash In & Cash Out dari seluruh transaksi.
- **AP (Payables)**: otomatis dari transaksi Supplier/Vendor yang belum lunas.
- **AR (Receivables)**: otomatis saat Project berstatus Done, dari nilai kontrak.
- **Dashboard**: Saldo Kas, Cash In, Cash Out, Budget, Allocation, Realisasi, Variance,
  Outstanding AP, Outstanding AR, Profit Project, grafik Budget vs Realisasi.
- **Rumus wajib**:
  - `Variance = Budget − Realisasi`
  - `Remaining Allocation = Allocation − Realisasi`
  - `Available Budget = Budget − Allocation`
- Tetap memakai arsitektur Laravel yang ada (Service Layer, Livewire, Policy, Request
  Validation, Audit Log, Import/Export) tanpa menurunkan kualitas kode/test.

## 3. Keputusan desain (terkunci)

| # | Poin | Keputusan |
|---|------|-----------|
| 1 | COA 19 akun | Master tetap, **fleksibel**: bisa ditambah/dikurangi user |
| 2 | Trigger AR | Project status **`completed` (Done)** → AR dari nilai kontrak |
| 3 | Approval workflow | Butuh **role/permission**: `admin` (approve) vs `staff` (buat draft) |
| 4 | Cashflow | Cash In = **pendapatan + pelunasan AR**; Cash Out = **payment request + pelunasan AP** |
| 5 | PR ↔ AP | PR yang **approved** → jadi AP + memicu Cash Out; AP "belum lunas" dihitung dari situ (sinkron, tidak dobel) |
| 6 | Budget Planning | Periode **per project**; sumber dana dari **pendapatan + pelunasan AR** |
| 7 | Sumber AP | Tetap dari **transaksi realisasi per vendor/supplier** |

## 4. Urutan eksekusi (fase)

1. **Fase A — COA 19 akun + struktur budget**: akun jadi master; tabel penghubung
   project→akun menyimpan `budget` / `allocation` / `realisasi`.
2. **Fase B — Vendor vs Supplier**: pisah tabel; realisasi menunjuk salah satu.
3. **Fase C — Budget Planning**: periode per project, estimasi pendapatan/biaya,
   target laba, rincian per akun.
4. **Fase D — Budget Allocation**: allocation hanya setelah disetujui.
5. **Fase E — Prioritas Pembayaran + Payment Request + Approval Workflow**:
   Draft → Waiting Approval → Approved → Paid → Closed/Cancelled (Audit Log).
6. **Fase F — Cashflow**: Cash In/Out dari PR/AR/AP/pendapatan.
7. **Fase G — AP/AR otomatis**: AP dari realisasi belum lunas; AR dari project Done.
8. **Fase H — Dashboard & laporan**: saldo kas, outstanding AP/AR, profit project,
   grafik Budget vs Realisasi.

## 5. Catatan migrasi

- Fase 1 (Budget → Akun) sudah selesai: tabel `akuns` + `realisasi.akun_id`.
- Bentuk akhir: `akuns` jadi **master COA** (tanpa `project_id`); alokasi per project
  pindah ke tabel penghubung (mis. `project_akuns`).
- **Fase A (2026-08-04) SELESAI**: `akuns` sudah menjadi master COA (`jenis_akun`, `kategori_id`); alokasi per project ada di `project_akuns` (`budget`, `allocation`); fondasi `budget_plans` + `budget_plan_items` dibuat. Gate: 122 tes + pint lulus.
- **Fase B (2026-08-04) SELESAI**: tabel `suppliers` + CRUD master Supplier; `realisasi.supplier_id` ditambah, realisasi menunjuk salah satu `vendor_id` (jasa) / `supplier_id` (barang); import/export/seeder/tes disesuaikan. Gate: 133 tes + pint lulus.
- **Fase C (2026-08-04) SELESAI**: UI Budget Plan lengkap (Index/Create/Edit/Delete) — periode per project, estimasi pendapatan, estimasi biaya otomatis dari rincian per akun, target laba; validasi satu plan per project per periode; service layer + Audit Log. Gate: 144 tes + pint lulus.
- **Fase D (2026-08-04) SELESAI**: Budget Allocation + workflow approval — role `admin`/`staff` di `users`; `project_akuns.status` (draft/waiting/approved/rejected) + `approved_by`/`approved_at`; UI Alokasi Budget (buat draft, ajukan, setujui/tolak); alokasi hanya efektif setelah disetujui (dashboard, ekspor, syarat realisasi). Gate: 157 tes + pint lulus.
- **Fase E (2026-08-04) SELESAI**: Payment Request + prioritas + approval workflow — tabel `payment_requests` (nomor otomatis, prioritas high/medium/low, status draft/waiting/approved/paid/closed/cancelled/rejected); UI Payment Request dengan workflow lengkap dan urutan prioritas. Gate: 172 tes + pint lulus.
- **Fase F (2026-08-04) SELESAI**: Cashflow — tabel `cashflows` (jenis masuk/keluar, sumber payment_request/pendapatan/pelunasan_ar/pelunasan_ap); Cash Out otomatis saat PR dibayar (anti dobel); Cash In manual pendapatan; UI Cashflow dengan Total Masuk/Keluar/Saldo + filter. Gate: 182 tes + pint lulus.
- **Fase G (2026-08-04) SELESAI**: AR/AP + payments — `receivables` (auto saat project selesai), `payables` (auto dari realisasi, detail pajak AP), `payments` (pelunasan → update nominal_dibayar + cashflow masuk/keluar); UI Piutang/Hutang/Pembayaran. Gate: 204 tes + pint lulus.
- **Fase H (2026-08-04) SELESAI**: Dashboard lanjutan — saldo kas (cash in/out), outstanding AR/AP, profit per project, grafik Budget vs Realisasi (top 10), tabel Profit per Project. **Seluruh fase A-H selesai.** Gate: 207 tes + pint lulus.
- Seeder dummy & tes perlu disesuaikan di tiap fase; gate: `php artisan test` + pint.
