<x-guest-layout>
    <div class="w-full">
        <!-- Quick back link to portal -->
        <div class="mb-5 flex justify-between items-center text-xs">
            <a href="{{ route('public.index') }}" class="inline-flex items-center gap-1.5 text-white/80 hover:text-white bg-white/10 hover:bg-white/20 px-3.5 py-1.5 rounded-full backdrop-blur-md transition-all font-semibold border border-white/20 shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Kembali ke Portal Utama</span>
            </a>
            <span class="text-white/60 font-medium tracking-wide">Portal Resmi</span>
        </div>

        <div class="glass-panel rounded-3xl p-7 sm:p-9 relative overflow-hidden">
            <!-- Decorative circle glow -->
            <div class="absolute -top-16 -right-16 w-32 h-32 bg-primary-500 rounded-full mix-blend-multiply filter blur-2xl opacity-20 pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-32 h-32 bg-indigo-500 rounded-full mix-blend-multiply filter blur-2xl opacity-20 pointer-events-none"></div>
            
            <div class="relative z-10 text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-white p-3 mb-3.5 shadow-md border border-slate-100 ring-4 ring-primary-500/15">
                    @if(file_exists(public_path('images/001.png')))
                        <img src="{{ asset('images/001.png') }}" alt="Logo SISKA" class="w-full h-full object-contain">
                    @elseif(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-full h-full object-contain drop-shadow-sm">
                    @else
                        <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    @endif
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                    {{ $globalAppName ?? 'SMP Darul Mu\'minin' }}
                </h2>
                <p class="text-xs font-bold text-primary-700 uppercase tracking-widest mt-1">Sistem Informasi Sekolah (SISKA)</p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Masuk untuk mengelola absensi & data sekolah</p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />
            <!-- Validation Errors -->
            <x-auth-validation-errors class="mb-4" :errors="$errors" />

            <form method="POST" action="{{ route('login') }}" class="relative z-10 space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email / Username</label>
                    <div class="relative">
                        <input id="email" class="w-full rounded-xl border border-slate-300 bg-white/80 focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 shadow-sm transition-all text-sm py-2.5 pl-10 pr-4 outline-none font-medium text-slate-800" type="email" name="email" :value="old('email')" required autofocus placeholder="admin@sekolah.com" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
                        @if (Route::has('password.request'))
                            <a class="text-xs text-primary-600 hover:text-primary-800 font-semibold transition-colors" href="{{ route('password.request') }}">Lupa Password?</a>
                        @endif
                    </div>
                    <div class="relative">
                        <input id="password" class="w-full rounded-xl border border-slate-300 bg-white/80 focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 shadow-sm transition-all text-sm py-2.5 pl-10 pr-4 outline-none font-medium text-slate-800" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer">
                        <input id="remember_me" type="checkbox" class="w-4 h-4 text-primary-600 bg-white border-slate-300 rounded focus:ring-primary-500 focus:ring-2 outline-none cursor-pointer" name="remember">
                        <span class="ml-2 text-xs font-medium text-slate-600">Ingat Saya</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-primary-600 via-indigo-600 to-purple-600 hover:from-primary-700 hover:via-indigo-700 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-primary-500/25 transform transition-all hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 text-sm mt-2">
                    Masuk ke Sistem
                </button>
            </form>
            
            <div class="mt-7 text-center relative z-10 pt-4 border-t border-slate-200/60">
                <p class="text-[11px] text-slate-400 font-medium">&copy; {{ date('Y') }} {{ $globalAppName ?? 'SMP Darul Mu\'minin Cibatu' }}. Hak cipta dilindungi.</p>
            </div>
        </div>
    </div>
</x-guest-layout>
