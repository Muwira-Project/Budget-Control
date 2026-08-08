<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />

        <link rel="icon" href="{{ $companyLogoUrl ?? asset('favicon.ico') }}">
        <title>{{ isset($pageTitle) ? $pageTitle.' | ' : '' }}{{ $companyName ?? config('app.name', 'MyFinance') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="overflow-x-clip font-sans antialiased">
        <div class="page-shell">
            <livewire:layout.navigation />

            <div class="min-w-0 lg:pl-[248px]">
                <main class="mx-auto max-w-[1440px] px-4 pb-12 pt-6 sm:px-6 lg:px-8">
                    @if (isset($header))
                        <div class="mb-6">
                            {{ $header }}
                        </div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>