<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $bgFile = 'images/login-bg.png';
        if (!file_exists(public_path($bgFile))) {
            $bgFile = 'images/login-bg.jpg';
        }
        $bgVer = file_exists(public_path($bgFile)) ? filemtime(public_path($bgFile)) : time();
        $bgUrl = asset($bgFile) . '?v=' . $bgVer;
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Portal Informasi Orang Tua - SMP Darul Mu'minin Cibatu</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 min-h-screen flex flex-col selection:bg-brand-500 selection:text-white relative overflow-x-hidden">

    <!-- Atmospheric Subtle Campus Wallpaper with Gradient Fade -->
    <div class="absolute top-0 left-0 right-0 h-[620px] pointer-events-none -z-10 overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center opacity-20 filter blur-[0.5px] scale-105 transition-all duration-500" style="background-image: url('{{ $bgUrl }}');"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-brand-900/10 via-slate-50/80 to-slate-50"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_transparent_15%,_#f8fafc_90%)]"></div>
    </div>

    <!-- Top Announcement Bar -->
    <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-indigo-900 text-white text-xs py-2 px-4 text-center font-medium">
        <div class="max-w-7xl mx-auto flex items-center justify-center gap-2 flex-wrap">
            <span class="inline-block px-2 py-0.5 rounded bg-white/20 text-[10px] font-bold uppercase tracking-wider">Terintegrasi</span>
            <span>Portal Monitoring Siswa Resmi: Absensi Harian (SISKA) & Evaluasi Bimbingan (Guru Wali)</span>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="w-full bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-100 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between">
            
            <!-- School Brand / Logo -->
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Logo Sekolah" class="w-10 h-10 sm:w-12 sm:h-12 object-contain drop-shadow-sm" onerror="this.src='https://ui-avatars.com/api/?name=SMP+DM&background=0284c7&color=fff'">
                <div>
                    <h1 class="text-sm sm:text-lg font-black text-slate-900 leading-tight">SMP Darul Mu'minin Cibatu</h1>
                    <p class="text-[11px] sm:text-xs text-slate-500 font-medium">Portal Informasi Orang Tua & Rapor Digital</p>
                </div>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="https://wa.me/6283171379686" target="_blank" class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 transition-colors border border-slate-200">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    Bantuan Sekolah
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-bold text-brand-600 hover:text-white hover:bg-brand-600 transition-all border border-brand-200 bg-brand-50/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    <span>Login Guru / Staf</span>
                </a>
            </div>

        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-grow py-8 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full flex flex-col justify-center">
        
        <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            
            <!-- Left Column: Title, Explanations & Feature Highlights -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left order-2 lg:order-1">
                
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-50 text-brand-800 text-xs font-bold border border-brand-200/60 shadow-sm mx-auto lg:mx-0">
                    <span class="w-2 h-2 rounded-full bg-brand-600 animate-ping"></span>
                    <span>Layanan Mandiri Pantau Perkembangan Anak</span>
                </div>

                <!-- Main Headline -->
                <div class="space-y-2">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-[1.15]">
                        Pantau Nilai, Absensi & Karakter <br class="hidden sm:inline">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-600 via-indigo-600 to-purple-600">
                            Ananda dari Rumah
                        </span>
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base lg:text-lg max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        Transparansi kemajuan anak kini mudah diakses oleh orang tua. Cukup masukkan <strong class="text-slate-800">NISN</strong> atau <strong class="text-slate-800">Nama Siswa</strong> untuk melihat laporan lengkap kedisiplinan dan catatan bimbingan wali kelas secara real-time.
                    </p>
                </div>

                <!-- Campus Visual Showcase Card -->
                <div class="relative rounded-3xl overflow-hidden shadow-lg shadow-slate-200/80 border border-slate-200/80 group transition-all duration-300 hover:shadow-xl">
                    <img src="{{ $bgUrl }}" 
                         alt="Gedung Pembelajaran SMP Darul Mu'minin Cibatu" 
                         class="w-full h-44 sm:h-52 md:h-56 object-cover object-center transform group-hover:scale-105 transition-transform duration-700">
                    
                    <!-- Elegant dark gradient overlay at the bottom for crisp readability -->
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/25 to-transparent"></div>
                    
                    <!-- Top Badges -->
                    <div class="absolute top-3 left-3 sm:top-4 sm:left-4 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/95 backdrop-blur-md text-[11px] font-extrabold text-slate-800 shadow-sm border border-white/60">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Kampus Pembelajaran Terpadu
                        </span>
                        <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-brand-600/90 backdrop-blur-md text-[11px] font-bold text-white shadow-sm border border-white/20">
                            <svg class="w-3 h-3 text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                            Lingkungan Asri & Islami
                        </span>
                    </div>

                    <!-- Bottom Caption -->
                    <div class="absolute bottom-3 left-3.5 right-3.5 sm:bottom-4 sm:left-5 sm:right-5 text-left">
                        <h4 class="text-white text-sm sm:text-base font-black drop-shadow-md tracking-tight flex items-center gap-1.5">
                            <span>SMP Darul Mu'minin Cibatu</span>
                        </h4>
                        <p class="text-slate-200 text-[11px] sm:text-xs font-medium drop-shadow mt-0.5 line-clamp-1 sm:line-clamp-none">
                            Fasilitas belajar modern terintegrasi pendidikan Al-Qur'an dan pembentukan karakter mulia ananda.
                        </p>
                    </div>
                </div>

                <!-- 4 Feature Highlights Grid (Mobile & Desktop Friendly) -->
                <div class="grid grid-cols-2 sm:grid-cols-2 gap-3 sm:gap-4 pt-1">
                    
                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-left flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-brand-600 flex items-center justify-center font-bold text-base shrink-0">
                            📖
                        </div>
                        <div>
                            <h4 class="text-xs sm:text-sm font-black text-slate-800">Nilai & Evaluasi</h4>
                            <p class="text-[11px] text-slate-500 leading-snug mt-0.5">Akademik, Al-Qur'an, Sholat & Karakter</p>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-left flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base shrink-0">
                            📅
                        </div>
                        <div>
                            <h4 class="text-xs sm:text-sm font-black text-slate-800">Presensi Harian</h4>
                            <p class="text-[11px] text-slate-500 leading-snug mt-0.5">Kehadiran, sakit, izin, dan alpa</p>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-left flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-base shrink-0">
                            💬
                        </div>
                        <div>
                            <h4 class="text-xs sm:text-sm font-black text-slate-800">Catatan Wali Kelas</h4>
                            <p class="text-[11px] text-slate-500 leading-snug mt-0.5">Arahan & tindak lanjut untuk orang tua</p>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-left flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-base shrink-0">
                            ⭐
                        </div>
                        <div>
                            <h4 class="text-xs sm:text-sm font-black text-slate-800">Bakat & Potensi</h4>
                            <p class="text-[11px] text-slate-500 leading-snug mt-0.5">Rekomendasi minat & pelatihan siswa</p>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Right Column: Dedicated Parent Search Card -->
            <div class="lg:col-span-5 order-1 lg:order-2">
                <div class="bg-white/95 backdrop-blur-md rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/70 border border-slate-200/80 relative overflow-hidden">
                    
                    <!-- Decorative Accent -->
                    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-brand-500 via-indigo-600 to-purple-600"></div>

                    <!-- Header Form -->
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Pencarian Data Siswa</span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Cari Rapor Ananda</h3>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
                            Masukkan Nomor Induk Siswa Nasional (NISN) atau Nama Lengkap Siswa.
                        </p>
                    </div>

                    <!-- Flash Message Errors -->
                    @if(session('error'))
                        <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs sm:text-sm flex items-start gap-3">
                            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span class="font-medium leading-relaxed">{{ session('error') }}</span>
                        </div>
                    @endif

                    <!-- Form -->
                    <form action="{{ route('public.search') }}" method="POST" class="space-y-5">
                        @csrf
                        
                        <div>
                            <label for="nisn" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                NISN atau Nama Siswa
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input 
                                    type="text" 
                                    id="nisn" 
                                    name="nisn" 
                                    value="{{ old('nisn') }}" 
                                    required 
                                    autocomplete="off"
                                    class="block w-full pl-11 pr-4 py-3.5 sm:py-4 border-2 border-slate-200 rounded-2xl text-slate-900 text-sm sm:text-base font-semibold placeholder:text-slate-400 placeholder:font-normal focus:border-brand-600 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none bg-slate-50/50 hover:bg-white" 
                                    placeholder="Masukkan NISN atau Nama Siswa..."
                                >
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="w-full bg-gradient-to-r from-brand-600 via-indigo-600 to-brand-700 hover:from-brand-700 hover:to-indigo-700 text-white font-extrabold py-4 px-6 rounded-2xl shadow-lg shadow-brand-600/25 transform transition-all active:scale-[0.98] flex items-center justify-center gap-2 text-sm sm:text-base">
                            <span>Buka Data Rapor & Nilai</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </form>

                    <!-- Help / Guide for Parents -->
                    <div class="mt-6 pt-5 border-t border-slate-100 flex items-start gap-2.5 text-xs text-slate-500">
                        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="leading-relaxed">
                            <strong class="text-slate-700">Dimana melihat NISN?</strong> Nomor Induk Siswa Nasional dapat dilihat pada Kartu Pelajar ananda, Lembar Rapor, atau tanyakan langsung kepada Wali Kelas.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="py-6 px-4 border-t border-slate-200/80 bg-white text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <p>&copy; {{ date('Y') }} SMP Darul Mu'minin Cibatu. Hak Cipta Dilindungi.</p>
            <p class="text-slate-400">Sistem Informasi Sekolah Terpadu (SISKA & Guru Wali)</p>
        </div>
    </footer>
</body>
</html>
