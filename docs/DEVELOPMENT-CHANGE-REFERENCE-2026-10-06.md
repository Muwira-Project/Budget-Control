# Development Change Reference — 2026-10-06

Dokumen ini mencatat perubahan dashboard, transaksi keuangan, import/export, audit integritas, dan navigasi UI pada rangkaian kerja ini.

## Cakupan

- Cabang: `fix/ar-ap-import-issues`.
- Mencakup perubahan kumulatif dari `origin/main` sampai checkpoint `aaceb07`, serta perubahan working tree saat dokumen dibuat.
- Perubahan working tree belum di-commit.
- Koreksi database lokal di bawah ini terpisah dari kode, migration, dan deployment.

## Perubahan dan keputusan alur

### Dashboard dan struktur layanan

- Dashboard dirapikan untuk menyelaraskan tipografi, spacing, ukuran kartu, nilai mata uang, dan responsivitas layar; tampilan memakai English dan angka ditata agar lebih mudah dibaca.
- Logika dashboard, budgeting, manual cash activity, dan detail monitoring dipisahkan ke layanan dengan tanggung jawab lebih kecil. `DashboardService` dan `MonitoringPeriodService` menjadi lebih tipis.
- Komponen metric card dan bulk action disesuaikan dengan dashboard.

### Import, export, dan role

- Template, import, dan export diperluas/dirapikan untuk Cash Account, Cashflow, Fund Transfer, Receivable, dan Payable. Template AR/AP mendukung pilihan dengan Project Code atau tanpa Project Code.
- Cashflow import menolak settlement AR/AP mandiri; pembayaran harus lewat payment workflow supaya Payment dan dampak ledger terkait tercatat bersama.
- Cashflow import memvalidasi pasangan sumber/jenis: pendapatan harus Cash In, pengeluaran lain harus Cash Out.
- Cashflow dan Fund Transfer hasil import dibuat sebagai Draft untuk persetujuan admin. Entri manual Cashflow tetap langsung Posted; Fund Transfer manual juga Posted dengan voucher.
- Staff hanya boleh mengakses import transaksi yang diizinkan dan export Cashflow/AR/AP. Import/export master data dan jalur administratif tetap dibatasi.
- Project import melakukan update lewat instance model agar event/sinkronisasi model berjalan.

### Cash Activity, AR/AP, dan Project

- Create, update, dan posting Cashflow/Fund Transfer menggunakan transaksi DB; proses posting mengunci baris dan menyertakan pembuatan voucher agar tidak meninggalkan status Posted tanpa efek terkait bila proses gagal.
- Cash Out manual dengan Project atau pihak dapat menyinkronkan Realisasi dan AP. Cash In tidak membuat AP lewat sinkronisasi ini.
- Edit Cashflow menyinkronkan atau menghapus Realisasi/AP turunannya. Penghapusan tag ditolak bila AP mempunyai payment atau nominal dibayar.
- Staff hanya dapat menghapus Cashflow manual miliknya; admin dapat mengelola semua. Settlement cashflow dihapus melalui workflow void, bukan dihapus secara langsung.
- Keterangan di form Kas Langsung menjelaskan bahwa Cash Out dengan pihak dapat menghasilkan AP. Form alokasi yang disentuh diseragamkan ke English.
- Project yang selesai membuat Receivable secara idempotent. Nilainya disinkronkan saat kontrak direvisi, tanpa membuat AR duplikat.
- Receivable ditahan saat Project selesai masuk revisi dan hold terkait revisi dilepas saat Project selesai kembali. Nilai kontrak revisi di bawah jumlah yang telah dibayar ditolak untuk rekonsiliasi.
- Sinkronisasi AP dari Realisasi menangani penghapusan/pemulihan AP dan payment terkait; AP dengan settlement tidak dilepas diam-diam. Update Project dan sinkronisasi AR/AP dibungkus transaksi.

### Trash dan voucher

- Restore Payment memulihkan parent AR/AP, cash ledger, nominal terbayar, dan Realisasi AP yang diperlukan. Restore AR/AP memulihkan payment aktif/menunggu pembatalan beserta efek terkait.
- Restore Cashflow/Fund Transfer Posted membuat ulang voucher bila transaksi lama masuk Trash tanpa voucher.
- Settlement yang pembatalannya sudah disetujui tidak bisa direstore sebagai payment aktif.
- Penghapusan permanen menangani relasi Cashflow, Payment, Realisasi, dan Voucher dalam transaksi. Cashflow tidak dapat dihapus permanen bila riwayat pembayaran AP masih terkait.
- Daftar voucher memuat Cashflow dan Fund Transfer, jenis Transfer, keterangan, dan nominal voucher.

### Audit integritas

- `audit:transactions` memeriksa timestamp posting, voucher Cashflow/Fund Transfer, konsistensi total paid terhadap settlement aktif/menunggu pembatalan, dan perhitungan saldo Cash Account.
- `--fix` kini flag eksplisit dengan konfirmasi; auto-fix hanya mengisi timestamp posting dan membuat voucher yang hilang.
- `audit:transactions` dan `repair:transactions` tidak lagi memotong `nominal_dibayar` AR/AP. Ketidakcocokan harus direkonsiliasi, bukan dibetulkan dengan menghilangkan nilai.
- Saldo dibayar tanpa Payment aktif dikategorikan sebagai opening/sample balance dan dikecualikan dari mismatch settlement; jumlah pengecualian tetap ditampilkan.
- Batasan: Payment yang pernah dihapus permanen tidak dapat dibedakan dari opening balance tanpa metadata sumber yang lebih jelas.

### Navigasi UI

- Sidebar desktop dapat diciutkan dari 248 px menjadi 68 px dengan preferensi yang tersimpan.
- Hanya ada satu tombol collapse di topbar. Perubahan state disiarkan ke layout utama agar padding kiri halaman mengikuti lebar sidebar.
- Ikon tombol menunjukkan arah ciut/buka. Reports dan Master membuka flyout saat sidebar ciut.
- Mobile tetap memakai drawer penuh dan submenu biasa di dalam drawer.
- Preferensi `dashboard_preferences` di model User diizinkan untuk mass assignment dan dicast sebagai array agar penyimpanan status ciut/buka berhasil.
- Tombol Back global dihapus karena riwayat browser tidak selalu merupakan jalur kembali yang valid. Logo menuju Dashboard dan breadcrumb menjadi navigasi yang tersedia.
- Tidak ada tombol pengosongan database. Fitur reset data perlu memiliki cakupan data yang ditentukan dan alur admin yang aman sebelum dibuat.

## Perubahan data lokal

> Berikut hanya koreksi database lokal yang sudah ditinjau; bukan perubahan repo untuk dijalankan ke produksi.

- 8 Cashflow Posted yang kehilangan `posted_at` diisi dari `updated_at`.
- 12 Cashflow Posted yang kehilangan voucher diberi voucher.
- Audit ulang memastikan kedua temuan tersebut bersih.
- Tiga AR dan dua AP dengan `nominal_dibayar` tanpa Payment cocok dengan contoh `DummyDataSeeder`. Record tersebut tidak diubah; audit mengecualikannya sebagai sample/opening balance.

## Verifikasi terakhir

- `composer test`: 436 test dan 1.326 assertion lulus sebelum perubahan audit saldo awal dan pembenahan sidebar paling akhir. Test suite belum dijalankan ulang setelah dua perubahan itu.
- `php artisan audit:transactions`: tidak menemukan isu integritas lain pada database lokal; 3 AR dan 2 AP tanpa Payment dilaporkan sebagai saldo sample/opening yang dikecualikan.
- `php artisan view:cache` berhasil setelah pembenahan sidebar.
- `npm.cmd run build` berhasil setelah pembenahan sidebar.
- PHP lint berhasil untuk `app/Models/User.php` setelah perbaikan MassAssignmentException pada penyimpanan preferensi sidebar.
- `git diff --check` tidak menemukan masalah whitespace; Git mengeluarkan peringatan normalisasi CRLF pada beberapa file lama.

## File berubah terhadap `origin/main`

### Aplikasi dan layanan

`app/Console/Commands/{AuditTransactionIntegrity,RepairTransactionData}.php`; `app/Exports/{CashAccountExport,CashAccountTemplateExport,CashflowTemplateExport,FundTransferExport,FundTransferTemplateExport,PayableExport,PayableTemplateExport,ReceivableTemplateExport}.php`; `app/Http/Controllers/{ExportController,ImportTemplateController}.php`; `app/Http/Middleware/EnsureDraftStaffAccess.php`; `app/Imports/{AkunImport,CashAccountImport,CashflowImport,PayableImport,ProjectImport,ReceivableImport}.php`; `app/Livewire/{Budgeting/Index,Cashflows/Index,Exports/Index,FundTransfers/Index,Imports/ImportCashAccounts,Imports/ImportPayables,Imports/ImportReceivables,Trash/Index}.php`; `app/Models/{BudgetPlan,BudgetPlanItem,Project}.php`; `app/Services/{ActualService,BudgetingRowsService,BudgetingSummaryService,CashflowService,DashboardService,DashboardStatisticsService,FundTransferService,ManualCashActivityRowsService,MonitoringActualOnlyRowsService,MonitoringPeriodService,MonitoringVarianceDetailService,PayableService,ProjectService,ReceivableService,VoucherService}.php`; `app/Support/helpers.php`; `routes/web.php`.

### UI

`resources/views/components/{bulk-actions,icon,metric-card,sidebar-dropdown}.blade.php`; `resources/views/livewire/allokasis/_form.blade.php`; `resources/views/livewire/cash-accounts/index.blade.php`; `resources/views/livewire/cashflows/{create,index}.blade.php`; `resources/views/livewire/dashboard.blade.php`; `resources/views/livewire/dashboard/tabs/{arap,finance,overview,projects}.blade.php`; `resources/views/livewire/fund-transfers/index.blade.php`; `resources/views/livewire/imports/{import-cash-accounts,import-payables,import-receivables}.blade.php`; `resources/views/livewire/layout/navigation.blade.php`; `resources/views/livewire/vouchers/index.blade.php`.

### Test

`tests/Feature/{CashflowTest,ExportTest,StaffDraftAccessTest}.php`; `tests/Feature/Import/{ImportAkunTest,ImportCashAccountTest,ImportCashflowTest,ImportFundTransferTest,ImportPayableTest,ImportReceivableTest}.php`.
