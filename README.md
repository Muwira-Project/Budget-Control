# myfinance

Aplikasi web untuk mengelola **budget (alokasi dana)**, **actual (realisasi)**, dan **variance (sisa)** per project, dilengkapi modul keuangan: Payment Request, Cashflow, AR/AP, dan Monitoring periode.

> UI aplikasi berbahasa **Inggris** dengan branding **myfinance**. Dokumentasi progres per batch ada di `PROGRESS.md` dan notulensi di `MEETING.md`.

## Fitur Utama

- **Dashboard** Ã¢â‚¬â€ ringkasan project, budget, allocation, actual, variance, nilai kontrak, pajak, saldo kas, outstanding AR/AP, profit per project, grafik Budget vs Actual, actual per kategori (filter rentang tanggal).
- **Project** Ã¢â‚¬â€ data project: jenis (barang/jasa), qty, satuan, harga satuan, pajak (11% barang / 2% jasa), tanggal mulai, target selesai, status.
- **Account (COA)** Ã¢â‚¬â€ master chart of accounts (Pendapatan/Pengeluaran + kategori), filter jenis, dan kolom Actual realtime per periode tanggal.
- **Budget Plan** Ã¢â‚¬â€ perencanaan per project per periode (YYYY-MM): estimasi pendapatan, estimasi biaya, target laba, dan rincian per akun lengkap dengan rentang tanggal mulaiÃ¢â‚¬â€œselesai.
- **Monitoring** Ã¢â‚¬â€ pengganti menu "Budget Allocation": periode (rentang tanggal), Week & Month otomatis, Budget (dari Budget Plan), Actual In, Actual Out, Variance Ã¢â‚¬â€ berlaku per project dan global; ada halaman resume periode (seluruh akun); export summary CSV/XLS/PDF per periode.
- **Budget Allocation** Ã¢â‚¬â€ workflow persetujuan (draft Ã¢â€ â€™ waiting Ã¢â€ â€™ approved/rejected) tetap tersedia sebagai sumber alokasi.
- **Payment Request** Ã¢â‚¬â€ workflow Draft Ã¢â€ â€™ Waiting Ã¢â€ â€™ Approved Ã¢â€ â€™ Paid/Closed (atau Rejected/Cancelled); **otomatis menjadi Payable (AP) saat disetujui** (sinkron, tidak dobel).
- **Cashflow** Ã¢â‚¬â€ ledger kas: Cash In (pendapatan manual + pelunasan AR), Cash Out (Payment Request dibayar + pelunasan AP).
- **AR/AP & Payments** Ã¢â‚¬â€ AR otomatis saat project selesai; AP otomatis dari Actual dan dari Payment Request; pembayaran mencatat pelunasan + memengaruhi saldo kas.
- **Actual** — ledger otomatis tanpa input manual: Actual Out dari Payment Request dibayar & pelunasan AP, Actual In dari pelunasan piutang.
- **Non-Project Expense** — pengeluaran di luar project, terikat ke akun/COA, ikut Actual Out global di Monitoring (tidak membebani budget project).
- **Master** Ã¢â‚¬â€ Vendor, Supplier, Mandor, Investor, Category, User (foto profil, departemen, tanggal masuk, lokasi).
- **Import/Export** Ã¢â‚¬â€ import Account (template xlsx, validasi per baris + laporan), export Account/Actual/Account-vs-Actual (xlsx/PDF).
- **Notifikasi** Ã¢â‚¬â€ lonceng di topbar: over budget, user baru, dan Payment Request menunggu persetujuan; status baca/belum.
- **Audit Log** Ã¢â‚¬â€ jejak otomatis semua perubahan (create/update/delete).
- **Lainnya** Ã¢â‚¬â€ pagination (10/25/50/100 + jump to page), bulk delete checklist, filter rentang tanggal, company branding.

## Teknologi

- Laravel 13, PHP 8.3+
- SQLite (development) / MySQL / MariaDB (production)
- Laravel Breeze Authentication (Livewire + Volt)
- Livewire 3, Tailwind CSS 4
- Laravel Excel (import/export), Dompdf (export PDF)

## Struktur Utama

- `app/Services` Ã¢â‚¬â€ business logic (layering Service)
- `app/Livewire` Ã¢â‚¬â€ komponen halaman (Index/Create/Edit/Pay)
- `app/Http/Requests` Ã¢â‚¬â€ validasi per modul
- `app/Models` Ã¢â‚¬â€ entity + relasi + guard domain
- `app/Notifications` Ã¢â‚¬â€ notifikasi admin
- `routes/web.php` Ã¢â‚¬â€ seluruh route aplikasi (auth + verified + draft-staff)
- `resources/views/livewire` Ã¢â‚¬â€ view per modul

## Role & Akses

- `admin` Ã¢â‚¬â€ akses semua modul.
- `staff` Ã¢â‚¬â€ dashboard, monitoring (lihat), profile, Budget Allocation (draft miliknya), dan Payment Request (draft miliknya).
- Middleware `draft-staff` di `app/Http/Middleware/EnsureDraftStaffAccess.php`.

## Menjalankan

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Data dummy & akun demo:

```bash
php artisan db:seed --class=DummyDataSeeder
# admin@muwira.test / password
# staff@muwira.test / password
```

## Testing

```bash
php artisan test          # test suite
vendor/bin/pint --test    # PSR-12 check
```

Gate terakhir: **278 test lulus** + **Pint lulus** (lihat `PROGRESS.md`).
