# myfinance

Aplikasi web untuk mengelola **budget (alokasi dana)**, **actual (realisasi)**, dan **variance (sisa)** per project, dilengkapi modul keuangan: Payment Request, Cashflow, AR/AP, dan Monitoring periode.

> UI aplikasi berbahasa **Inggris** dengan branding **myfinance**. Dokumentasi progres per batch ada di `PROGRESS.md` dan notulensi di `MEETING.md`.

## Fitur Utama

- **Dashboard** — ringkasan project, budget, allocation, actual, variance, nilai kontrak, pajak, saldo kas, outstanding AR/AP, profit per project, grafik Budget vs Actual, actual per kategori (filter rentang tanggal).
- **Project** — data project: jenis (barang/jasa), qty, satuan, harga satuan, pajak (11% barang / 2% jasa), tanggal mulai, target selesai, status.
- **Account (COA)** — master chart of accounts (Pendapatan/Pengeluaran + kategori), filter jenis, dan kolom Actual realtime per periode tanggal.
- **Budget Plan** — perencanaan per project per periode (YYYY-MM): estimasi pendapatan, estimasi biaya, target laba, dan rincian per akun lengkap dengan rentang tanggal mulai–selesai.
- **Monitoring** — pengganti menu "Budget Allocation": periode (rentang tanggal), Week & Month otomatis, Budget (dari Budget Plan), Actual, Variance — berlaku per project dan global; ada halaman resume periode (seluruh akun).
- **Budget Allocation** — workflow persetujuan (draft → waiting → approved/rejected) tetap tersedia sebagai sumber alokasi.
- **Payment Request** — workflow Draft → Waiting → Approved → Paid/Closed (atau Rejected/Cancelled); **otomatis menjadi Payable (AP) saat disetujui** (sinkron, tidak dobel).
- **Cashflow** — ledger kas: Cash In (pendapatan manual + pelunasan AR), Cash Out (Payment Request dibayar + pelunasan AP).
- **AR/AP & Payments** — AR otomatis saat project selesai; AP otomatis dari Actual dan dari Payment Request; pembayaran mencatat pelunasan + memengaruhi saldo kas.
- **Actual (Realisasi)** — transaksi penggunaan dana per akun dengan tipe pihak: Vendor (Services), Supplier (Goods), Mandor, atau Investor.
- **Master** — Vendor, Supplier, Mandor, Investor, Category, User (foto profil, departemen, tanggal masuk, lokasi).
- **Import/Export** — import Actual & Account (template xlsx, validasi per baris + laporan), export Account/Actual/Account-vs-Actual (xlsx/PDF).
- **Notifikasi** — lonceng di topbar: over budget, user baru, dan Payment Request menunggu persetujuan; status baca/belum.
- **Audit Log** — jejak otomatis semua perubahan (create/update/delete).
- **Lainnya** — pagination (10/25/50/100 + jump to page), bulk delete checklist, filter rentang tanggal, company branding.

## Teknologi

- Laravel 13, PHP 8.3+
- SQLite (development) / MySQL / MariaDB (production)
- Laravel Breeze Authentication (Livewire + Volt)
- Livewire 3, Tailwind CSS 4
- Laravel Excel (import/export), Dompdf (export PDF)

## Struktur Utama

- `app/Services` — business logic (layering Service)
- `app/Livewire` — komponen halaman (Index/Create/Edit/Pay)
- `app/Http/Requests` — validasi per modul
- `app/Models` — entity + relasi + guard domain
- `app/Notifications` — notifikasi admin
- `routes/web.php` — seluruh route aplikasi (auth + verified + draft-staff)
- `resources/views/livewire` — view per modul

## Role & Akses

- `admin` — akses semua modul.
- `staff` — dashboard, profile, Budget Allocation (draft), dan Payment Request (draft miliknya).
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
