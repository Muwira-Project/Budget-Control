# myfinance

Aplikasi web untuk mengelola **budget (alokasi dana)**, **actual (realisasi)**, dan **variance (sisa)** per project, dilengkapi modul keuangan: Cash Activity, AR/AP, Fund Transfer, dan Monitoring periode.

> UI aplikasi berbahasa **Inggris** dengan branding **myfinance**. Dokumentasi progres per batch ada di `PROGRESS.md` dan notulensi di `MEETING.md`.

## Fitur Utama

- **Dashboard** — ringkasan project, budget, allocation, actual, variance, nilai kontrak, pajak, saldo kas, outstanding AR/AP, profit per project, grafik Budget vs Actual, actual per kategori (filter rentang tanggal).
- **Project** — data project: jenis (barang/jasa), qty, satuan, harga satuan, pajak, tanggal mulai, target selesai, status, PIC, kategori (master dinamis), sub-work, periode.
- **Account (COA)** — master chart of accounts (Pendapatan/Pengeluaran + kategori), filter jenis, dan kolom Actual realtime per periode tanggal.
- **Budget Plan** — perencanaan per project per periode (YYYY-MM): estimasi pendapatan, estimasi biaya, target laba, dan rincian per akun lengkap dengan rentang tanggal mulai–selesai.
- **Monitoring** — periode (rentang tanggal), Week & Month otomatis, Budget (dari Budget Plan), Actual In, Actual Out, Variance — berlaku per project dan global; ada halaman resume periode (seluruh akun); export summary CSV/XLS/PDF per periode.
- **Budget Allocation** — workflow persetujuan (draft → waiting → approved/rejected) sebagai sumber alokasi; **Non-Project Allocation** dapat ditambah via Budgeting page (admin only).
- **Cash Activity** — menu tersendiri dengan tab **Cash In**, **Cash Out**, **Fund Transfer**, dan **Cash Account**:
  - Cash In: pemasukan manual (mis. dari investor) dan pelunasan AR; **manual entry bisa tag Project + Pihak (Vendor/Supplier/Mandor/Investor)** → otomatis sinkron ke Realisasi saat di-post.
  - Cash Out: pengeluaran lain yang terikat akun/COA (mis. sewa, utility) dan pelunasan AP; **manual entry bisa tag Project + Pihak**.
  - Fund Transfer: pemindahan dana antar rekening.
  - Voucher otomatis per transaksi (Cash In/Out & Fund Transfer) diakses lewat tombol aksi di baris transaksi.
- **AR & AP** — satu menu dengan tab **Receivable**, **Payable**, dan **Settlement History**; AR otomatis saat project selesai, AP otomatis dari Actual, pembayaran mencatat pelunasan + memengaruhi saldo kas.
- **Actual** — ledger otomatis tanpa input manual: Actual Out dari pelunasan AP, Actual In dari pelunasan piutang.
- **Master** — Category, Vendor, Supplier, Mandor, Investor, User (foto profil, departemen, tanggal masuk, lokasi) + sub-menu master dinamis: admin bisa menambah/mengedit/menghapus menu master (mis. PIC) dengan kolom custom (text, textarea, number, date), menu baru otomatis muncul di sidebar.
- **Approval Center** — satu tempat persetujuan admin: kegiatan kas & fund transfer (waiting → approved → posted) dan pembatalan settlement.
- **Import/Export** — import Account (template xlsx, validasi per baris + laporan), export Account/Actual/Account-vs-Actual (xlsx/PDF).
- **Notifikasi** — lonceng di topbar: over budget, user baru, dan request persetujuan; status baca/belum.
- **Audit Log** — jejak otomatis semua perubahan (create/update/delete).
- **Lainnya** — pagination (per halaman + lompat halaman), bulk delete checklist, filter rentang tanggal, company branding.

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
- `staff` — dashboard, monitoring (lihat), profile, dan Budget Allocation (draft miliknya).
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

Gate terakhir: **287 test lulus** + **Pint lulus**.