# Draft Modul AR (Piutang Usaha) & AP (Hutang Usaha)

> Status: desain final (disepakati sesi diskusi). Belum dieksekusi.
> Tanggal: 2026-08-04
>
> Pembaruan 2026-08-04: prasyarat langkah 1 (Budget → Akun) dan langkah 2 (Vendor vs Supplier) sudah selesai; modul AR/AP + payments sudah dieksekusi di Fase G (skema di bawah, minus penyesuaian kecil: `payables.realisasi_id` untuk sinkron otomatis dari realisasi).

## 1. Definisi & Sumber (notulensi)
- **AR (piutang)** = tagihan ke **klien**, sebesar **nilai kontrak project** (`qty × harga_satuan <span>` include pajak`). Terbentuk saat project **selesai** (`done = piutang`).
- **AP (hutang)** = kewajiban ke **vendor (jasa) / supplier (barang)**, dari **biaya realisasi**. Terbentuk saat project **berjalan** (`progress = hutang`).
- Basis AR (kontrak) dan AP (biaya realisasi) berbeda → jangan disatukan sebagai satu angka di laporan.

## 2. Teknologi / ketergantungan
- AR/AP dibangun **setelah refactor Project → Akun** dan **pemisahan Vendor (jasa) vs Supplier (barang)**.
- Urutan agenda:
  1. Refactor **Budget → Akun** (`kode_akun`, `nama_akun`, klasifikasi 6 kategori).
  2. **Vendor (jasa) vs Supplier (barang)** — pisah tabel; realisasi menunjuk salah satu.
  3. Bangun **AR/AP + payment** di atas struktur anyar.

## 3. Skema data
### `receivables` (satu baris per project)
- `project_id`, `tanggal`, `jatuh_tempo` (optional), `nominal` (dari `nilai_total` project, sudah include pajak), `nominal_dibayar`.
- `status` dihitung otomatis: `belum_dibayar` / `sebagian` / `lunas`.

### `payables` (per vendor/supplier)
- `project_id`, `vendor_id` (jasa) **atau** `supplier_id` (barang), `akun_id` (setelah refactor), `tanggal`, `jatuh_tempo`, `nominal`.
- `jenis_pajak` (`ppn` / `pph`) + `pajak_include` (bool) untuk membedakan "sudah termasuk pajak" vs "pajak terpisah".
- `nominal_dibayar`, `status` otomatis (`belum_bayar` / `sebagian` / `lunas`).

### `payments` (riwayat pelunasan)
- `payable_id` / `receivable_id` (salah satu nullable), `tanggal`, `nominal`, `jenis` (`masuk` untuk AR, `keluar` untuk AP), `keterangan`.
- Setiap `payment` menambah `nominal_dibayar` dan meng-regenerate `status`.

## 4. Alur
1. Realisasi → buat **AP** (nilai + pajak) → progress = hutang.
2. Project selesai → buat **AR** satu baris = `nilai_total` kontrak.
3. Pelunasan → `payments` → update `nominal_dibayar` + `status`.
4. Laporan: rekap AR/AP per project & per vendor/supplier, sisa, aging jatuh tempo.

## 5. Keleputan terpaku
| Aspek | Keputusan |
|---|---|
| Sumber AR | Kontrak project, saat selesai |
| Sumber AP | Realisasi per vendor/supplier, saat berjalan |
| Mekanisme | Hybrid (draft otomatis + status bayar manual) |
| Pembayaran | `status` + `nominal_dibayar` + tabel `payments` |
| Penamaan | `Receivable`/`Payable`; label "Piutang Usaha"/"Hutang Usaha" |
| Penempatan | Setelah refactor Project → Akun & pisah Vendor/Supplier |
| Vendor vs Supplier | Vendor = jasa; Supplier = material/barang |
| AR per project | Satu AR per project (mudah user); AP tetap bisa banyak |
| Pajak AR | Otomatis include (nilai kontrak sudah + pajak) |
| Pajak AP | Detail `jenis_pajak` (`ppn`/`pph`) + `pajak_include` |
