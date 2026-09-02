# MEETING - Muwira Budget Control (MBC)

> Status: notulensi terbaru (2026-08-05) sudah masuk; keputusan kunci terkunci (bagian 5 & 5.1). Sesi lanjutan 2026-08-09: bagian 7.
> Catatan: bagian 1-4 adalah bahan diskusi awal (sejarah); bagian 5 = hasil meeting resmi terbaru.

## 1. Ringkasan Model yang Dipahami (untuk dikonfirmasi)

- Project = induk
- Akun = alokasi budget per project (contoh: "Pengeluaran Upah", "Bahan Baku & Gudang", "Sewa")
- Realisasi = transaksi penggunaan budget pada akun
- Varian = Realisasi - Budget (negatif = over budget, jadi bahan evaluasi + warning)

Asumsi yang perlu disahkan:

- Akun menggantikan Budget item dan Kategori (kode_budget -> kode_akun)
- Vendor / Supplier / Klasifikasi: masih menunggu kepastian dari tim user

## 2. Pertanyaan Kunci untuk Tim User

### 2.1 Vendor & Supplier
- [ ] Vendor dan supplier: dua entitas berbeda (jasa vs barang) atau dua istilah untuk satu hal?
- [ ] Menempel ke mana: setiap transaksi realisasi, atau ke akun (vendor/supplier tetap)?
- [ ] Apakah butuh laporan per vendor/supplier (total transaksi per vendor per project)?

### 2.2 Klasifikasi & Akun
- [ ] "Klasifikasi" = pengelompokan akun (seperti Kategori lama)?
- [ ] Satu level atau hierarki (grup -> sub-akun)?
- [ ] Klasifikasi wajib atau opsional untuk tiap akun?
- [ ] Daftar klasifikasi ditentukan sistem atau bebas dibuat user?

### 2.3 Akun & Budget
- [ ] Konfirmasi: kode_budget diganti kode_akun? Format bebas atau ada aturan?
- [ ] Semua akun wajib punya budget, atau boleh akun tanpa budget?
- [ ] Jumlah akun per project: fleksibel, dibatasi, atau daftar seragam untuk semua project?

### 2.4 Varian & Over Budget
- [ ] Konfirmasi rumus: Varian = Realisasi - Budget (negatif = over budget)
- [ ] Warning cukup di tampilan + export, atau harus memblokir input saat over budget?
- [ ] Realisasi tetap boleh diinput melebihi budget (bahan evaluasi)? Asumsi: boleh, dengan warning.

### 2.5 Menu & Alur Kerja
- [ ] Alur baru: buka Project -> daftar akun -> detail akun -> input realisasi?
- [ ] Realisasi bisa diinput tanpa akun? (tidak direkomendasikan)
- [ ] Siapa yang boleh: input realisasi, ubah budget, hapus data? (perlu role/permission?)

### 2.6 Import, Export & Laporan
- [ ] Kolom template import baru: Kode Akun, Tanggal, Vendor/Supplier, Nominal, Keterangan, Klasifikasi?
- [ ] Laporan wajib: per akun per project, ringkasan varian, per vendor/supplier, rekap seluruh project?
- [ ] Format: Excel saja, atau Excel + PDF?

### 2.7 Keputusan & Migrasi Data
- [ ] Data existing (budget, realisasi, kategori, vendor) dipindahkan ke model baru atau mulai bersih?
- [ ] Siapa yang menyetujui finalisasi model, supaya tidak berubah lagi setelah pengerjaan dimulai?

## 3. Bahan yang Dibawa ke Meeting

- Demo singkat aplikasi saat ini (5-10 menit): Project, Budget, Realisasi, master Vendor/Kategori
- Diagram 1 halaman: model lama vs model baru
- Daftar dampak perubahan (bagian 4)
- Sketsa alur halaman: Project -> Detail Project (daftar akun + varian) -> Detail Akun (realisasi)
- Notulen keputusan (bagian 5)

## 4. Dampak Perubahan yang Diprediksi

- Rename entitas budget -> akun (kode_akun, nama_akun)
- Klasifikasi melekat ke akun; kolom kategori dihapus dari realisasi
- Template import/export berubah
- Audit log menyesuaikan entitas baru
- Menu Budget & Realisasi dilebur (Project -> Akun -> Realisasi)
- Warning over budget di tampilan akun dan export
- Estimasi refactor: 1-2 hari kerja (test suite sebagai pengaman)

## 5. Hasil Meeting (notulensi terbaru 2026-08-05)

| # | Poin | Keputusan |
|---|------|-----------|
| 1 | Halaman login | Desain ulang menyerupai login page Coretax atau sejenisnya |
| 2 | Import & Export | Dipindah per modul; tambah aksi hapus dengan checklist (batch delete, ala Accurate) |
| 3 | Pagination | Batasi halaman per 25/50/dst dan bisa lompat ke halaman yang diinginkan |
| 4 | Master data | Vendor, Mandor, Supplier, Investor (master terpisah seperti Vendor & Supplier) |
| 5 | Master User | Foto profile, nama, departemen, tanggal dimasukkan, lokasi; tombol aksi edit & delete (di dalam admin) |
| 6 | COA | Tambah fitur sort; bisa pilih periode dan angkanya muncul realtime sesuai periode |
| 7 | Terminologi | "Akun" diganti tulisan "Account"; "Realisasi" diganti "Actual"; "Budget" tetap "Budget" |
| 8 | Alokasi Budget -> Monitoring | Kolom: Nomor, Periode (rentang tanggal, ex. 1 Maret 2026 - 14 Maret 2026 via menu tanggal), Week (otomatis 1/2/3), Month (otomatis), Budget (total dari modul budget ditarik per tanggal sesuai periode), Actual (sama seperti budget), Variance (selisih) |
| 9 | List & resume periode | Ada list per periode ke bawah (bisa hapus/edit); klik periode -> tampilan resume periode: seluruh akun + budget + actual |

### 5.1 Keputusan teknis (klarifikasi lanjutan)

| # | Pertanyaan | Jawaban Tim User |
|---|-----------|------------------|
| 1 | Monitoring per project atau global? | Keduanya: per project dan global |
| 2 | Sumber "budget pertanggal" (poin 8)? | Dari Budget Plan |
| 3 | Definisi Week & Month otomatis? | Ya, diambil otomatis dari tanggal awal periode |
| 4 | Mandor & Investor | Master terpisah seperti Vendor & Supplier |

## 6. Status Eksekusi & Rencana

### Status saat ini (2026-08-06)
- **Batch I SELESAI** â€” branding `myfinance`, Bahasa Inggris penuh, register nonaktif.
- **Review menyeluruh SELESAI** â€” perbaikan security (markPaid/close admin-only), pesan Indonesia diterjemahkan, nama file download Inggris.
- **Batch II berjalan** â€” pagination (25/50/100 + lompat halaman) âœ…; import/export per modul + hapus checklist âœ…; **sisa**: Master User (foto/departemen/tanggal/lokasi + edit/delete), COA sort + periode realtime.


### Catatan hasil uji manual (2026-08-06) - pengingat meeting
1. **Dashboard**: tampilan menjadi berantakan dan tidak rapi, kurang nyaman saat membaca data.
2. **Import & Export per modul**: tombol sudah pindah tapi posisinya berada di kolom yang salah (sebelah kiri kolom Modul), sehingga kolom Modul bergeser.
3. **Tombol aksi**: belum ada di tabel dan belum memakai icon.
4. **Login page**: belum berubah; sesuai notulensi terakhir diminta desain menarik & interaktif (contoh: login page Coretax dan medsos).
5. **Kesimpulan Batch I & II**: PR terbesar adalah tampilan yang berubah menjadi sedikit berantakan; beberapa implementasi masih perlu dikoreksi.

### Rencana Batch III (siang nanti)
1. Master **Mandor** & **Investor** â€” master terpisah seperti Vendor & Supplier (kode, nama, telepon, alamat).
2. Integrasi ke realisasi/payment sebagai tipe pihak (Vendor Jasa / Supplier Barang / Mandor / Investor), import/export bila relevan, seeder dummy, dan tes.

### Rencana Batch IV (siang nanti)
1. **BudgetPlanItem diberi rentang tanggal mulaiâ€“selesai** (opsi 1 â€” keputusan teknis terkunci).
2. Ubah **"Alokasi Budget" â†’ "Monitoring"**: kolom Nomor, Periode (rentang tanggal, ex. 1â€“14 Maret 2026), Week & Month otomatis dari tanggal awal, Budget (total ditarik dari Budget Plan per tanggal sesuai periode), Actual, Variance â€” berlaku **per project dan global**.
3. List periode (edit/hapus) + halaman **resume periode** (seluruh akun + budget + actual).




### Update progres perbaikan UI (2026-08-06)
- Checklist bulk: diperbaiki logika `toggleAllVisible` agar benar-benar memilih semua data yang tampil di halaman, jumlah yang dipilih akurat, dan "select all" bekerja kembali.
- Tombol Import/Export di halaman Account & Actual diperkecil & dilengkapi icon untuk mengurangi pergeseran layout module.
- Tombol aksi di tabel utama diganti menjadi icon edit/delete (button group) agar lebih rapi.
- Halaman login didesain ulang: layout split panel dengan hero panel + form panel yang lebih modern.
- Dashboard diperbaiki: header deskripsi ditambah, kartu Cash/AR/AP/Profit dirapikan, dan struktur HTML yang sebelumnya terkompres diperbaiki.
- Verifikasi: `php artisan test` = 231 lulus; `vendor/bin/pint --test` lulus.

### Catatan sementara untuk lanjutan
- Lanjut Batch II: integrasi UI untuk Master User (foto/departemen/tanggal/lokasi) dan COA sort + periode realtime.

## 7. Notulensi sesi 2026-08-09 (pembahasan lanjutan & keputusan)

### 7.1 Keputusan model bisnis

| # | Poin | Keputusan |
|---|------|-----------|
| 1 | Estimasi & target income | Dihapus dari Budget (belum final) |
| 2 | Pengeluaran non-project | Dicatat terpisah dari project dengan logika actual; tidak membebani budget project; tetap dikaitkan ke akun/COA agar laporan global tetap rapi |
| 3 | Monitoring | Berlaku per project dan global (tanpa filter project); tambah filter periode project; tambah menu export summary CSV/XLS/PDF per periode |
| 4 | Sumber Actual | Actual otomatis narik data dari modul (tanpa input manual): pengeluaran (out) dari Payment Request/AP yang dibayar + pengeluaran non-project; penerimaan (in) dari pelunasan piutang |
| 5 | AR / Actual In | Project selesai = piutang (AR); piutang dibayar = Actual (penerimaan) (belum final) |

### 7.2 Keputusan UI (sudah dieksekusi 2026-08-09)

| # | Poin | Keputusan |
|---|------|-----------|
| 1 | Menu Account Detail | Dihapus dari sidebar; diganti menu Backup |
| 2 | Fitur Backup | Database saja; unduh sekali klik + daftar riwayat (download/hapus); admin only; pakai spatie/laravel-backup. SQLite dev memakai dumper kustom salin file (tanpa binary sqlite3) |
| 3 | Dokumen pengguna | Panduan Praktis PDF (Bahasa Indonesia): `docs/Panduan-Penggunaan-myfinance.pdf`; sumber `docs/panduan-penggunaan.html` + generator `docs/generate-panduan.php` |

### 7.3 Status sesi

- `php artisan test`: **287 lulus** (753 assertions) per 2026-08-09 (setelah review babak 1 & 2).
- Pint: **lulus seluruh repo** (`vendor/bin/pint --test`).
- Review babak 2 (2026-08-09): guard cancel Payment Request, aksi Monitoring khusus admin, `TRUSTED_PROXIES` dikosongkan, null-safe form edit, nomor unik PR/MON, dll.

### 7.4 Proses lanjutan (roadmap) & area terdampak

| # | Proses lanjutan | Status | Area terdampak |
|---|-----------------|--------|----------------|
| 1 | Actual otomatis narik data dari modul (tanpa input manual) | **DIEKSEKUSI 2026-08-10 (Batch 1)** | Menu `Actual` (input manual dihapus/dibaca saja), `Realisasi` + form/import/export, `PaymentRequestService` (paid → Actual Out), `PayableService` & `PaymentService` (pelunasan AP → Actual Out), `PaymentService` (pelunasan AR → Actual In), `MonitoringPeriodService` (kolom In/Out), Dashboard, Panduan PDF |
| 2 | Pengeluaran non-project (terpisah dari project, logika actual, dikaitkan ke akun/COA) | **DIEKSEKUSI 2026-08-10 (Batch 2)** | Tabel + modul baru (mis. Non-Project Expense), menu, `MonitoringPeriodService` (Actual Out global), laporan global, seeder, tes |
| 3 | Monitoring: filter periode project + menu export summary CSV/XLS/PDF per periode | **DIEKSEKUSI 2026-08-10 (Batch 3)** | `Monitoring\Index` (filter + tombol export), `ExportController`/export service baru (CSV/XLS/PDF), template laporan, Panduan PDF |
| 4 | Hapus estimasi & target income dari Budget | DITAHAN (belum final) sampai ada perkembangan | `BudgetPlan` (model/migration), form Budget Create/Edit, `BudgetPlanService`, view, Dashboard (target laba), Panduan PDF |
| 5 | Project selesai = piutang; piutang dibayar = Actual In | DITAHAN (belum final) sampai ada perkembangan | `ReceivableService`, `PaymentService`, `CashflowService`, `MonitoringPeriodService` (Actual In), Dashboard (saldo kas) |
| 6 | Backup (ganti Account Detail) | Dieksekusi 2026-08-09 | Sidebar, `config/backup.php`, `app/Livewire/Backups`, docs |
| 7 | Review kualitas & stabilitas | Dieksekusi 2026-08-09 | Middleware staff (Monitoring lihat), guard cancel PR, aksi Monitoring admin-only, `TRUSTED_PROXIES`, null-safe edit, nomor unik PR/MON, Pint seluruh repo |

Catatan:
- Item 1-3 (keputusan final) dikerjakan lebih dulu, urut: 1 (Actual otomatis) → 2 (pengeluaran non-project) → 3 (Monitoring filter + export summary).
- Item 4-5 **DITAHAN** di catatan ini sampai ada perkembangan/konfirmasi user.
- Panduan pengguna diperbarui menyusul setiap batch yang selesai.
- Sebelum mengerjakan item 1-3, pastikan basis data dev di-backup (menu Backup) dan `php artisan test` tetap hijau setelah tiap batch.
- Dokumen `docs/Panduan-Penggunaan-myfinance.pdf` diperbarui setelah item 1-3 selesai (sumber: `docs/panduan-penggunaan.html`, render: `php docs/generate-panduan.php`).

### 7.5 Status Batch 1 - Actual otomatis (2026-08-10)

- Migration `2026_08_10_000001_add_source_to_realisasi_table` (kolom `sumber` + `sumber_id`).
- `ActualService` (baru): generate Actual Out otomatis dari Payment Request dibayar (`payment_request`) dan pelunasan AP (`pelunasan_ap`; payable dari PR dilewati agar tidak dobel).
- Menu **Actual** menjadi ledger read-only (tanpa input manual): hapus create/edit/import Actual + komponen/route/view terkait.
- Monitoring: kolom **Actual In** (pelunasan piutang) & **Actual Out** di index + resume.
- `PaymentService::delete` menghapus baris actual terkait (konsistensi).
- Seeder: actual row untuk PR paid; sinkron payable hanya untuk realisasi manual (sumber null).
- Tes: 270 lulus; Pint lulus seluruh repo.
- Catatan: Actual In hanya dari pelunasan piutang (AR) — pendapatan manual belum masuk Actual In (dapat direvisi saat item 5 dibahas).

### 7.6 Status Batch 2 - Pengeluaran non-project (2026-08-10)

- Tabel baru `non_project_expenses` (tanggal, akun_id/COA, pihak opsional: vendor/supplier/mandor/investor, nominal, keterangan, created_by).
- Modul CRUD baru menu **Non-Project Expense** (admin): index (filter akun/tanggal/cari + total per halaman + bulk delete), create, edit.
- Integrasi Monitoring: Actual Out global menambahkan pengeluaran non-project (per periode & per akun di resume); periode per-project tidak terpengaruh (tidak membebani budget project). Variance = Budget - Actual Out; di periode global non-project ikut dihitung (konsisten di index, resume, dan per akun).
- Seeder demo: 2 entri operasional kantor.
- Catatan: Dashboard tetap project-scoped (belum menampilkan non-project); Actual In tetap dari pelunasan piutang. Dapat direvisi saat item 5 dibahas.
- Tes: 280 lulus; Pint lulus seluruh repo.

### 7.7 Status Batch 3 - Monitoring filter + export summary (2026-08-10)

- `MonitoringPeriodService::summaryRows()`: baris ringkasan per periode (nomor, periode, week, month, budget, actual in, actual out, variance) dengan filter project + cari nomor.
- `MonitoringSummaryExport` (xlsx/csv/pdf) + `ExportController::monitoringSummary()` + route `exports.monitoring-summary`.
- Monitoring index: tombol export **XLSX / CSV / PDF** (khusus admin) mengikuti filter project & pencarian yang sedang aktif.
- Filter periode per project sudah tersedia sejak awal (`projectFilter`) dan kini ikut dipakai di export.
- Tes: 283 lulus; Pint lulus seluruh repo.
- SEMUA keputusan final (item 1-3) selesai; item 4-5 tetap ditahan.
### 7.8 Status review babak 3 (2026-08-10)

- Fokus: audit kode Batch 1-3 (Actual otomatis, Non-Project Expense, Monitoring export) + audit ulang umum.
- Perbaikan:
  - Variance disamakan di semua tampilan Monitoring: `Variance = Budget - Actual Out` (periode global termasuk non-project) — konsisten di index, resume, dan per akun.
  - Guard model `NonProjectExpense` (saving hook): maksimal satu pihak (vendor/supplier/mandor/investor).
  - Perbaikan error 500 sementara (referensi `$period` di closure `accountBreakdown`).
- Audit aman tanpa perubahan: query batch (tanpa N+1), validasi lengkap, route/middleware admin-only, PSR-12 bersih.
- Status: `php artisan test` = **284 lulus** (726 assertions); Pint lulus seluruh repo.

## 8. Notulensi sesi 2026-08-18 (diskusi struktur menu & role)

> Status: **DIIMPLEMENTASIKAN (2026-08-18).** Seluruh keputusan 8.1 & 8.2 dieksekusi pada branch `feature/review-16-aug-2026`; detail per poin di bawah.

### 8.1 Keputusan struktur menu (final)

| # | Poin | Keputusan |
|---|------|-----------|
| 1 | Menu Account | **TETAP "Account"** (tidak diubah jadi "Chart of Accounts") |
| 2 | Budget + Budget Allocation | **Digabung jadi satu menu "Budgeting"** dengan 2 tab: Budget Plan (admin) + Allocation (staff & admin) |
| 3 | Actual (Realisasi) | **Tetap halaman mandiri** (hubungan ringkasan↔detail dengan Monitoring); untuk **staff = angka agregat saja**, admin = detail transaksi |
| 4 | Monitoring | Tetap = agregat budget vs actual per proyek (semua role bisa lihat) |
| 5 | Aging AR/AP | **Bukan laporan terpisah** — jadi filter umur (30/60/90) di halaman AR & AP |
| 6 | Reports | Ramping: **Profit & Loss + Cash Flow** saja |
| 7 | Vendor/Supplier/Mandor/Investor | **Tetap entitas terpisah** (keputusan bisnis, tidak digabung jadi Counterparty) |
| 8 | Realisasi | **Bukan jalur input** — tetap auto-generated (hindari fungsi ganda dengan Cashflow) |

### 8.2 Keputusan role (Opsi D — staff sebagai operator)

| Role | Input | Approve | Kelola |
|------|-------|---------|--------|
| **Staff** | Cash Activity (draft) + AR & AP (buat receivable/payable/payment) | — | — |
| **Admin** | — | Cashflow, Fund Transfer, Void, Settlement | Master, User, Backup, Laporan |

### 8.3 Dampak implementasi (sudah dikerjakan 2026-08-18)

- **Fitur baru**: permission staff untuk input Cashflow draft + AR/AP (create + pay; edit/delete tetap admin-only) + Approval Center tetap admin-only menangani approval draft staff (draft → waiting → approved → posted).
- **Actual agregat untuk staff**: route `/realisasi` redirect berdasar role — admin → `realisasi.detail` (list transaksi), staff → `realisasi.summary` (agregat per proyek-akun).
- **Sidebar**: gabung Budget+Allocation jadi "Budgeting" (2 tab via `livewire:budgeting.index`, route `budgeting.index`); Aging dihapus dari Reports (sidebar + route `reports.aging`); menu Actual kini tampil untuk semua role (staff lihat agregat).
- **Aging jadi filter**: halaman AR & AP (Receivables & Payables) punya dropdown "Filter Aging" (Current / 1-30 / 31-60 / 61-90 / Over 90) berbasis `jatuh_tempo`; laporan Aging terpisah dihapus.
- **Role staff**: middleware `EnsureDraftStaffAccess` diperluas (dashboard, profile, monitoring.*, budgeting.*, allokasis.*, cashflows.index/create, ar-ap.*, receivables.index/create/pay, payables.index/create/pay, payments.index, realisasi.index/summary); admin tetap satu-satunya akses edit/delete AR-AP, fund-transfer, cash-account, reports, master, backup, trash, approvals.
- Guard tambahan: `User::isStaff()`, `CashflowPolicy::manageDraft` (admin atau pemilik draft), tab fund-transfer/cash-account disembunyikan dari staff, tombol edit/delete AR-AP disembunyikan untuk staff.
- Vendor/Supplier/Mandor/Investor tetap entitas terpisah — Master tidak berubah struktur.
- Test: `StaffDraftAccessTest`, `RealisasiTest`, `ReceivableTest`, `PayableTest`, `ReportTest` diperbarui + test baru aging filter; `php artisan test` = **303 lulus** (estimasi setelah penambahan test aging & staff).

### 8.4 Voucher print + Company Settings (selesai 2026-08-18, lanjutan 8.3)

- **Voucher → tombol aksi cetak (bukan kolom)**: kolom "Voucher" dihapus dari tabel Cash Activity; voucher kini tombol printer di kolom **Actions** untuk transaksi **posted** yang punya voucher (Cash In, Cash Out, Fund Transfer). Klik → buka tab baru **print preview** (`cashflows.print` & `fund-transfers.print` di `resources/views/cashflows/print.blade.php` & `fund-transfers/print.blade.php`) → auto `window.print()` → cetak/simpan PDF. Modal voucher lama (`viewVoucher`/`closeVoucher`/`$voucherId`) dihapus.
- **Company Settings (fitur baru, admin-only)**: halaman `/company-settings` (route `company-settings.index`, `App\Livewire\CompanySettings\Index`, menu sidebar "Company Settings" icon cog).
  - Upload **logo** perusahaan (max 2MB) & **login illustration** (max 4MB) — tersimpan di `storage/app/public/logos` & `storage/app/public/illustrations`, file lama otomatis dihapus saat replace.
  - **Pengaturan tampilan login illustration**: `illustration_fit` (cover/contain/fill) + `illustration_position` (top/center/bottom) dengan **live preview** di halaman settings — dipakai di `layouts/auth.blade.php` via `object-fit`/`object-position`.
  - Migrations: `2026_08_18_163040_add_login_illustration_to_company_settings_table` & `2026_08_18_174951_add_fit_settings_to_company_settings_table`.
  - View share: `companyLoginIllustrationUrl`, `companyIllustrationFit`, `companyIllustrationPosition` (fallback ke SVG `hero-finance.svg` saat kosong).
  - Fix penting: `save()` awalnya memakai `app(CompanySetting::class)` (instance kosong) → diganti `CompanySettingService::get()` agar update menyentuh row yang benar.
- **Test**: `CompanySettingsTest` +1 test upload login illustration; `php artisan test` = **308 lulus, 837 assertions**; Pint bersih; `npm run build` sukses.
- **Commit**: `7a921da` (P8) di branch `feature/review-16-aug-2026` — belum di-push.