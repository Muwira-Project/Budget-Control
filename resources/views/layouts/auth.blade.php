<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />

    <link rel="icon" href="{{ $companyLogoUrl ?? asset('favicon.ico') }}">
    <title>{{ __('Login') }} | {{ $companyName ?? config('app.name', 'MyFinance') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="flex min-h-screen bg-slate-50">

        {{-- LEFT: Illustration only (desktop/tablet) --}}
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700 md:flex md:w-[40%] lg:w-1/2">
            {{-- Soft glows --}}
            <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-emerald-400/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-20 h-96 w-96 rounded-full bg-emerald-300/10 blur-3xl"></div>
            <div class="pointer-events-none absolute left-1/2 top-1/2 h-[28rem] w-[28rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-400/10 blur-3xl"></div>

            {{-- Small subtle logo, top-left corner (no marketing text) --}}
            <a href="{{ route('login') }}" class="absolute left-8 top-8 z-10" aria-label="ERGE MyFinance">
                <x-application-logo class="h-9 w-auto object-contain" />
            </a>

            {{-- Finance illustration merged with background (no card frame, no heavy shadow) --}}
            <div class="relative flex w-full items-center justify-center">
                <img src="{{ asset('images/hero-finance.svg') }}" alt="" aria-hidden="true" class="h-full w-full object-cover" />
            </div>
        </div>

        {{-- RIGHT: Login form --}}
        <div class="flex w-full items-center justify-center bg-slate-50 px-6 py-12 md:w-[60%] lg:w-1/2 lg:px-10">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <div class="mb-5 inline-flex items-center justify-center gap-2.5">
                        <x-application-logo class="h-8 w-auto object-contain" />
                        <span class="text-xl font-bold tracking-tight text-slate-900">myfinance</span>
                    </div>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Welcome back') }}</h2>
                    <p class="mt-2 text-sm text-slate-500">{{ __('Sign in to continue to') }} <span class="font-semibold text-slate-700">{{ $companyName ?? 'MyFinance' }}</span></p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-7 shadow-card sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>