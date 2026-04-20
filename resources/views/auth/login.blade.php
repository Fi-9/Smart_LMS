<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | Smart Library</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-shell min-h-screen text-white">
    <div class="relative isolate flex min-h-screen overflow-hidden">
        <div class="login-grid-overlay absolute inset-0 opacity-60"></div>
        <div class="pointer-events-none absolute inset-y-0 left-[-12%] w-[36rem] rounded-full bg-primary-500/14 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-[-8rem] right-[-5rem] h-[24rem] w-[24rem] rounded-full bg-amber-300/20 blur-3xl"></div>

        <div class="relative z-10 mx-auto flex w-full max-w-7xl flex-col justify-center px-4 py-8 lg:px-8">
            <div class="grid items-center gap-8 lg:grid-cols-[1.08fr_0.92fr]">
                <section class="hidden lg:block">
                    <div class="max-w-2xl">
                        <div class="library-badge inline-flex items-center gap-3 rounded-full px-4 py-2 text-sm font-medium text-emerald-50 shadow-lg shadow-black/10 backdrop-blur">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/12">
                                <svg viewBox="0 0 64 64" class="h-6 w-6" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M18 18C18 14.6863 20.6863 12 24 12H44V50H24C20.6863 50 18 47.3137 18 44V18Z" fill="#D8B36A"/>
                                    <path d="M12 18C12 14.6863 14.6863 12 18 12H24V50H18C14.6863 50 12 47.3137 12 44V18Z" fill="#4B8B3B"/>
                                    <path d="M24 12H50C52.2091 12 54 13.7909 54 16V46C54 48.2091 52.2091 50 50 50H24V12Z" fill="#F6F4EA"/>
                                    <path d="M29 23H45" stroke="#173126" stroke-width="3.5" stroke-linecap="round"/>
                                    <path d="M29 31H41" stroke="#173126" stroke-width="3.5" stroke-linecap="round"/>
                                    <path d="M29 39H45" stroke="#173126" stroke-width="3.5" stroke-linecap="round"/>
                                </svg>
                            </span>
                            Smart Library Management
                        </div>

                        <h1 class="mt-8 max-w-xl text-5xl font-black leading-[1.05] tracking-tight text-white">
                            Panel perpustakaan yang terasa lebih hidup, rapi, dan siap dipakai harian.
                        </h1>
                        <p class="mt-6 max-w-xl text-lg leading-8 text-emerald-50/78">
                            Kelola buku, rak, QR, dan peminjaman dari satu tempat dengan pengalaman yang lebih kuat secara visual dan tetap fokus untuk operasional sekolah.
                        </p>

                        <div class="mt-8 grid max-w-xl grid-cols-3 gap-4 text-sm">
                            <div class="rounded-2xl border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                                <p class="text-2xl font-bold text-white">Books</p>
                                <p class="mt-1 text-emerald-50/70">Katalog dan pencarian cepat</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                                <p class="text-2xl font-bold text-white">QR</p>
                                <p class="mt-1 text-emerald-50/70">Identifikasi dan pelacakan</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                                <p class="text-2xl font-bold text-white">Racks</p>
                                <p class="mt-1 text-emerald-50/70">Lokasi rak lebih terstruktur</p>
                            </div>
                        </div>

                        <div class="mt-10 overflow-hidden rounded-[2rem] border border-white/10 bg-white/8 p-4 shadow-2xl shadow-black/20 backdrop-blur-md">
                            <img src="{{ asset('images/library-login-hero.svg') }}" alt="Ilustrasi rak buku dan panel Smart Library" class="w-full rounded-[1.5rem] object-cover">
                        </div>
                    </div>
                </section>

                <section class="relative mx-auto w-full max-w-lg">
                    <div class="login-glow login-card rounded-[2rem] border border-white/60 p-6 text-slate-900 sm:p-8 lg:p-10">
                        <div class="mb-8 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.38em] text-primary-700">Smart Library</p>
                                <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 sm:text-[2.5rem]">Masuk ke panel admin</h2>
                                <p class="mt-3 max-w-md text-sm leading-6 text-slate-600">
                                    Gunakan akun petugas atau admin untuk mengakses dashboard perpustakaan, pengelolaan koleksi, QR code, dan peminjaman.
                                </p>
                            </div>
                            <div class="hidden h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary-700 sm:flex">
                                <svg viewBox="0 0 64 64" class="h-9 w-9" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M18 18C18 14.6863 20.6863 12 24 12H44V50H24C20.6863 50 18 47.3137 18 44V18Z" fill="#D8B36A"/>
                                    <path d="M12 18C12 14.6863 14.6863 12 18 12H24V50H18C14.6863 50 12 47.3137 12 44V18Z" fill="#4B8B3B"/>
                                    <path d="M24 12H50C52.2091 12 54 13.7909 54 16V46C54 48.2091 52.2091 50 50 50H24V12Z" fill="#F6F4EA"/>
                                    <path d="M29 23H45" stroke="#173126" stroke-width="3.5" stroke-linecap="round"/>
                                    <path d="M29 31H41" stroke="#173126" stroke-width="3.5" stroke-linecap="round"/>
                                    <path d="M29 39H45" stroke="#173126" stroke-width="3.5" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>

                        <div class="mb-6 flex flex-wrap gap-2">
                            <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">Dashboard</span>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">QR Tracking</span>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Borrowing Control</span>
                        </div>

                        @if ($errors->any())
                            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" class="space-y-5" onsubmit="if(this.dataset.submitting) return false; this.dataset.submitting = 'true'; this.querySelector('.login-button').disabled=true; this.querySelector('.login-button').classList.add('opacity-75', 'cursor-not-allowed');">
                            @csrf

                            <div>
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="login-input">
                            </div>

                            <div>
                                <label for="password" class="form-label">Password</label>
                                <input id="password" name="password" type="password" required autocomplete="current-password" class="login-input">
                            </div>

                            <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                                Ingat saya di perangkat ini
                            </label>

                            <button type="submit" class="login-button inline-flex w-full items-center justify-center rounded-2xl px-4 py-3.5 text-sm font-semibold text-white transition duration-200 focus:outline-none focus:ring-2 focus:ring-primary-300 focus:ring-offset-2">
                                Masuk ke Smart Library
                            </button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</body>
</html>
