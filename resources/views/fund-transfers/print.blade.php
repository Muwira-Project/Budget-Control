<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - {{ $transfer->voucher?->nomor ?? 'Fund Transfer #' . $transfer->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 min-h-screen p-8">
    <div class="no-print mb-4 text-center">
        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Cetak / Simpan PDF</button>
        <button onclick="window.close()" class="ml-2 bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Tutup</button>
    </div>

    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">VOUCHER</h1>
                    <p class="text-sm text-gray-500">Bukti Transfer Dana</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Nomor</p>
                    <p class="text-xl font-mono font-bold text-gray-900">{{ $transfer->voucher?->nomor ?? '-' }}</p>
                </div>
            </div>
            <hr class="border-gray-300">
        </div>

        <!-- Info Transaksi -->
        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div>
                <p class="text-gray-500">Tanggal</p>
                <p class="font-medium">{{ $transfer->tanggal->format('d F Y') }}</p>
            </div>
            <div>
                <p class="text-gray-500">Status</p>
                <p class="font-medium capitalize">{{ $transfer->status->label() }}</p>
            </div>
            <div>
                <p class="text-gray-500">Dari Rekening</p>
                <p class="font-medium">{{ $transfer->dariCashAccount?->kode ?? '-' }} - {{ $transfer->dariCashAccount?->nama ?? '-' }}</p>
            </div>
            <div>
                <p class="text-gray-500">Ke Rekening</p>
                <p class="font-medium">{{ $transfer->keCashAccount?->kode ?? '-' }} - {{ $transfer->keCashAccount?->nama ?? '-' }}</p>
            </div>
            <div class="col-span-2">
                <p class="text-gray-500">Keterangan</p>
                <p class="font-medium">{{ $transfer->keterangan ?? '-' }}</p>
            </div>
        </div>

        <!-- Nominal -->
        <div class="border-t-2 border-gray-300 pt-4 mb-6">
            <div class="flex justify-between items-baseline">
                <span class="text-lg font-medium text-gray-500">Jumlah Transfer</span>
                <span class="text-3xl font-bold text-gray-900">{{ number_format($transfer->nominal, 0, ',', '.') }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Rupiah</p>
        </div>

        <!-- Tanda Tangan -->
        <div class="grid grid-cols-3 gap-8 text-center text-sm">
            <div>
                <div class="h-16 border-b border-gray-300 mb-2"></div>
                <p class="text-gray-500">Dibuat Oleh</p>
                <p class="font-medium">{{ $transfer->createdBy?->name ?? '-' }}</p>
            </div>
            <div>
                <div class="h-16 border-b border-gray-300 mb-2"></div>
                <p class="text-gray-500">Diperiksa</p>
                <p class="font-medium"> </p>
            </div>
            <div>
                <div class="h-16 border-b border-gray-300 mb-2"></div>
                <p class="text-gray-500">Disetujui</p>
                <p class="font-medium"> </p>
            </div>
        </div>

        <div class="mt-8 text-center text-xs text-gray-400">
            <p>Dicetak pada {{ now()->format('d F Y H:i') }} oleh {{ auth()->user()->name ?? 'System' }}</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>