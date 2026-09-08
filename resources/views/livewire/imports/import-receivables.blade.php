@extends('layouts.app')

@section('title', 'Import Receivables (AR)')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Import Receivables (AR)</h1>
            <p class="mt-1 text-sm text-gray-500">
                Upload file Excel/CSV untuk mengimpor piutang. Pilih mode import terlebih dahulu.
            </p>
        </div>

        <!-- Mode Selection -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">Mode Import</label>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center cursor-pointer">
                    <input
                        type="radio"
                        wire:model="importMode"
                        value="with_project"
                        class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                    >
                    <span class="ml-2 text-sm text-gray-700">Dengan Project Code</span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                    <input
                        type="radio"
                        wire:model="importMode"
                        value="without_project"
                        class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                    >
                    <span class="ml-2 text-sm text-gray-700">Tanpa Project Code</span>
                </label>
            </div>
            <p class="mt-2 text-xs text-gray-500">
                {{ $importMode === 'with_project'
                    ? 'Invoice No. unik per project. Project Code wajib diisi.'
                    : 'Invoice No. unik global (semua project). Project Code tidak digunakan.' }}
            </p>
        </div>

        <!-- Template Download -->
        <div class="mb-6">
            <button
                wire:click="downloadTemplate"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download Template Excel
            </button>
        </div>

        <!-- File Upload -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                File Import (Excel/CSV, max 10MB)
            </label>
            <div
                class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors"
                wire:ignore.self
            >
                <input
                    type="file"
                    wire:model="file"
                    accept=".xlsx,.xls,.csv"
                    class="sr-only"
                    id="receivable-import-file"
                >
                <label for="receivable-import-file" class="cursor-pointer">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">Klik atau drag & drop file di sini</p>
                    <p class="text-xs text-gray-400">Format: .xlsx, .xls, .csv (maks 10MB)</p>
                </label>
                @if ($file)
                    <p class="mt-2 text-sm text-green-600">{{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 1) }} KB)</p>
                @endif
            </div>
            @error('file')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Import Button -->
        <div class="mb-8">
            <button
                wire:click="import"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg wire:loading class="mr-2 h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                {{ $file ? 'Import' : 'Pilih file terlebih dahulu' }}
            </button>
        </div>

        <!-- Import Report -->
        @if (!empty($report))
            <div class="mb-8 p-4 bg-white rounded-lg shadow-sm border border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Hasil Import</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="p-3 bg-green-50 rounded-lg">
                        <p class="text-sm text-green-700">Berhasil</p>
                        <p class="text-2xl font-bold text-green-900">{{ $report['success'] ?? 0 }}</p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <p class="text-sm text-yellow-700">Dilewati (Duplicate/Error)</p>
                        <p class="text-2xl font-bold text-yellow-900">{{ count($report['failures'] ?? []) }}</p>
                    </div>
                    <div class="p-3 {{ !empty($report['fatal']) ? 'bg-red-50' : 'bg-gray-50' }} rounded-lg">
                        <p class="text-sm {{ !empty($report['fatal']) ? 'text-red-700' : 'text-gray-700' }}">Fatal Error</p>
                        <p class="text-2xl font-bold {{ !empty($report['fatal']) ? 'text-red-900' : 'text-gray-900' }}">
                            {{ !empty($report['fatal']) ? 'Ya' : 'Tidak' }}
                        </p>
                    </div>
                </div>

                @if (!empty($report['fatal']))
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-800">
                        <strong>Error:</strong> {{ $report['fatal'] }}
                    </div>
                @endif

                @if (!empty($report['failures']))
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Row</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($report['failures'] as $failure)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $failure['row'] ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm text-red-600">{{ $failure['message'] ?? $failure['attribute'] ?? 'Unknown error' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        <!-- Back Link -->
        <div class="mt-6">
            <a
                href="{{ route('receivables.index') }}"
                class="text-sm text-blue-600 hover:text-blue-900"
            >
                ← Kembali ke Daftar Receivables
            </a>
        </div>
    </div>
</div>
@endsection