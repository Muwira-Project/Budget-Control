<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />

    <link rel="icon" href="{{ $companyLogoUrl ?? asset('favicon.ico') }}">
    <title>{{ __('Login') }} | {{ $companyName ?? config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="flex min-h-screen bg-slate-50">
        <!-- Brand / Hero Panel -->
        <div class="relative hidden w-[54%] overflow-hidden bg-gradient-to-br from-[#043d16] via-emerald-950 to-teal-950 lg:flex lg:flex-col">
            <!-- Decorative blobs -->
            <div class="pointer-events-none absolute -left-20 -top-20 h-80 w-80 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-teal-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-emerald-400/10 blur-2xl"></div>

            <div class="relative flex flex-1 items-center justify-center p-6 xl:p-10">
                <div class="relative w-full max-w-5xl aspect-square">
                    <div class="relative overflow-hidden rounded-[2rem] border border-white/15 bg-slate-900/30 p-3 ">
                        <img src="{{ asset('images/hero-finance.svg') }}" alt="Ilustrasi dashboard keuangan dan pengendalian budget" class="aspect-square w-full rounded-[1.6rem] object-cover" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Panel -->
        <div class="flex w-full items-center justify-center px-6 py-12 lg:w-[46%] lg:px-10">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <!-- White box + green logo, merged with the white background (no shadow) -->
                    <div class="mb-6 inline-flex items-center justify-center gap-3">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white ring-1 ring-emerald-600/10">
                            <x-application-logo class="h-8 w-auto fill-current text-emerald-600" />
                        </span>
                        <span class="text-xl font-bold tracking-tight text-emerald-700">myfinance</span>
                    </div>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">{{ __('Welcome back') }}</h2>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white px-8 py-10 ">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>

</html>

