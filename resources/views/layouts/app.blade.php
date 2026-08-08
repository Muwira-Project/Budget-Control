<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />

        <link rel="icon" href="{{ $companyLogoUrl ?? asset('favicon.ico') }}">
        <title>{{ $companyName ?? config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="overflow-x-clip font-sans antialiased">
        <div class="page-shell">
            <livewire:layout.navigation />

            <div class="min-w-0 overflow-x-clip lg:pl-64">
                <main>
                    @if (isset($header))
                        <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    @endif
                    <div class="mx-auto max-w-7xl px-4 pb-3 pt-6 sm:px-6 lg:px-8">
                        <x-breadcrumb />
                    </div>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
