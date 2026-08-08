# MEETING - Muwira Budget Control (MBC)

> Status: notulensi terbaru (2026-08-05) sudah masuk; keputusan kunci terkunci (bagian 5 & 5.1).
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
