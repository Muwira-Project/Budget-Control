# Kamus Status & Istilah - MyFinance Budget Control

> Dibuat pada Fase A (konsistensi). Nilai DB, label UI, dan warna badge harus mengikuti tabel ini.
> Bahasa UI konsisten: English.

## 1. Status Project
| Nilai DB | Label UI | Warna Badge | Makna |
|---|---|---|---|
| progress | In Progress | blue | Sedang berjalan; belum generate Receivable |
| done | Done | green | Selesai; memicu pembuatan Receivable (AR) otomatis |
| cancelled | Cancelled | red | Dibatalkan (seragam dengan PR & Settlement) |

## 2. Status Kegiatan Kas (Cash In, Non-Project Expense, Fund Transfer) - Fase B
| Nilai DB | Label UI | Makna |
|---|---|---|
| draft | Draft | Dibuat user/approver, belum diajukan |
| waiting | Pending Approval | Dikirim, menunggu admin |
| approved | Approved | Disetujui admin |
| posted | Posted | Sudah masuk ke buku besar (saldo rekening terpengaruh) |

## 3. Status Payment Request
| Nilai DB | Label UI | Makna |
|---|---|---|
| draft | Draft | Belum dikirim |
| waiting | Pending Approval | Menunggu admin |
| approved | Approved | Disetujui |
| paid | Paid | Dibayar (cashflow keluar dibuat) |
| closed | Closed | Ditutup tanpa pembayaran |
| cancelled | Cancelled | Dibatalkan |
| rejected | Rejected | Ditolak |

## 4. Status Settlement (Payment / AR-AP)
| Nilai DB | Label UI | Warna Badge | Makna |
|---|---|---|---|
| active | Active | green | Aktif, tercatat |
| pending_cancel | Pending Cancellation | amber | Menunggu approval pembatalan |
| cancelled | Cancelled | red | Dibatalkan (saldo dikembalikan) |

## 5. Status Alokasi Budget (ProjectAkun)
| Nilai DB | Label UI | Makna |
|---|---|---|
| draft | Draft | Draft |
| waiting | Pending Approval | Menunggu admin |
| approved | Approved | Disetujui |
| rejected | Rejected | Ditolak |

## 6. Status Entitas Lain
| Entitas | Nilai DB | Label UI |
|---|---|---|
| Cash Account | active / inactive | Active / Inactive |
| AR | belum_dibayar / sebagian / lunas | Unpaid / Partial / Paid |
| AP | belum_bayar / sebagian / lunas | Unpaid / Partial / Paid |
| Master Type/Item | aktif (bool) | Active / Inactive |

## 7. Istilah Basis Pelaporan
- **Accrual**: Budget, Realisasi, Profit (nilai kontrak - realisasi). Basis perencanaan project.
- **Cash**: Arus Kas, saldo per rekening (Cash Account), Fund Transfer, Voucher.
- Jangan dicampur dalam satu angka; beri label basis di setiap laporan.

## 8. Prinsip Penamaan
- Nilai DB: snake_case.
- Label UI: English (Title Case).
- Warna status: draft = gray, waiting/pending = amber, approved = blue/green, done/paid/posted = green, cancelled/rejected = red.
- Enum PHP: satu case, satu label() - tidak ada istilah ganda.