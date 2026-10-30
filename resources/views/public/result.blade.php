<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Rapor Perkembangan: {{ $student->name }} - Portal Orang Tua</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', '-apple-system', 'sans-serif'],
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
    <style>
        /* Smooth Scrolling & Custom Scrollbar */
        html { scroll-behavior: smooth; -webkit-tap-highlight-color: transparent; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 9999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Print Optimization */
        @media print {
            nav, .no-print, button, .tab-buttons, #modalBody, footer { display: none !important; }
            body { background: white !important; color: black !important; font-size: 11pt; }
            .print-full { max-height: none !important; overflow: visible !important; }
            .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl { box-shadow: none !important; border: 1px solid #e2e8f0 !important; }
            #tab-content-nilai, #tab-content-absensi, #tab-content-riwayat { display: block !important; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800 min-h-screen flex flex-col selection:bg-brand-500 selection:text-white pb-12 sm:pb-6">

    @php
        $hasGuruWali = isset($nilaiGuruWali) && ($nilaiGuruWali['found'] ?? false);
        $gwSiswa = $hasGuruWali ? ($nilaiGuruWali['siswa'] ?? []) : [];
        $gwGuru = $hasGuruWali ? ($nilaiGuruWali['guru_wali'] ?? []) : [];
        $gwRingkasan = $hasGuruWali ? ($nilaiGuruWali['ringkasan'] ?? []) : [];
        $gwRiwayat = $hasGuruWali ? ($nilaiGuruWali['riwayat'] ?? []) : [];
        $gwSekolah = $hasGuruWali ? ($nilaiGuruWali['sekolah'] ?? []) : [];

        $tingkatan = $gwSiswa['tingkatan'] ?? 'A';
        $statusPendampingan = $gwRingkasan['status_pendampingan'] ?? 'Baik';
        $evalTerbaru = $gwRingkasan['evaluasi_terbaru'] ?? null;

        $namaGuru = !empty($gwGuru['nama']) ? $gwGuru['nama'] : 'Wali Kelas';
        $cleanWa = !empty($gwGuru['no_telp']) ? preg_replace('/[^0-9]/', '', $gwGuru['no_telp']) : '';
        if (!empty($cleanWa) && str_starts_with($cleanWa, '0')) {
            $cleanWa = '62' . substr($cleanWa, 1);
        }
        $waMessage = urlencode("Assalamu'alaikum Warahmatullahi Wabarakatuh, Bapak/Ibu {$namaGuru}. Saya orang tua dari {$student->name} (NISN: {$student->nisn}). Saya ingin berkonsultasi mengenai hasil evaluasi perkembangan dan nilai ananda di sekolah.");

        // Helper penilaian mobile-first: fleksibel, rapi, tidak memotong teks
        function getScoreInfo($val) {
            $val = $val ?? 'Baik';
            if ($val === 'Baik' || $val === 'Sangat Baik') {
                return [
                    'text' => 'Sangat Baik',
                    'stars' => '⭐⭐⭐',
                    'percent' => '100%',
                    'barColor' => 'bg-emerald-500',
                    'dotColor' => 'bg-emerald-500',
                    'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200'
                ];
            } elseif ($val === 'Cukup' || $val === 'Cukup Baik') {
                return [
                    'text' => 'Cukup Baik',
                    'stars' => '⭐⭐',
                    'percent' => '66%',
                    'barColor' => 'bg-amber-500',
                    'dotColor' => 'bg-amber-500',
                    'badge' => 'bg-amber-50 text-amber-800 border-amber-200'
                ];
            } else {
                return [
                    'text' => 'Perlu Latihan',
                    'stars' => '⭐',
                    'percent' => '33%',
                    'barColor' => 'bg-rose-500',
                    'dotColor' => 'bg-rose-500',
                    'badge' => 'bg-rose-50 text-rose-700 border-rose-200'
                ];
            }
        }

        // Hitung kehadiran
        $hadirCount = $student->attendances->where('status', 'hadir')->count();
        $izinCount = $student->attendances->where('status', 'izin')->count();
        $sakitCount = $student->attendances->where('status', 'sakit')->count();
        $alpaCount = $student->attendances->where('status', 'alpa')->count();
        $totalAbsen = $hadirCount + $izinCount + $sakitCount + $alpaCount;
        $persenHadir = $totalAbsen > 0 ? round(($hadirCount / $totalAbsen) * 100) : 100;
    @endphp

    <!-- ============================================================== -->
    <!-- 1. TOP APP BAR (MOBILE HEADER NATIVE FEELING)                   -->
    <!-- ============================================================== -->
    <header class="w-full bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 transition-all">
        <div class="max-w-3xl mx-auto px-3.5 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-2">
            <!-- Back Button -->
            <a href="{{ route('public.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 hover:text-brand-700 hover:bg-slate-100 transition-colors active:scale-95">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                <span>Cari Lain</span>
            </a>

            <!-- Title in Header -->
            <div class="text-center min-w-0 px-1">
                <h1 class="text-xs sm:text-sm font-black text-slate-900 truncate">
                    Rapor Siswa Digital
                </h1>
                <p class="text-[10px] text-slate-400 font-medium truncate">{{ $gwSekolah['nama'] ?? "SMP Darul Mu'minin Cibatu" }}</p>
            </div>

            <!-- Print Action -->
            <button onclick="window.print()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 transition-colors active:scale-95 no-print" title="Cetak Rapor">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 002 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2m8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span class="hidden sm:inline">Cetak</span>
            </button>
        </div>
    </header>

    <!-- ============================================================== -->
    <!-- MAIN CONTAINER (KOMPAK, RAPI, TIDAK ADA OVERFLOW DI HP)         -->
    <!-- ============================================================== -->
    <main class="flex-grow py-3.5 sm:py-5 px-3.5 sm:px-6 max-w-3xl mx-auto w-full space-y-3.5 sm:space-y-4">

        <!-- ============================================================== -->
        <!-- 2. KARTU IDENTITAS DIGITAL SISWA (HERO CARD ELEGANT & COMPACT)  -->
        <!-- ============================================================== -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-200/90 relative overflow-hidden">
            <!-- Accent Top Stripe -->
            <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-brand-600 via-indigo-600 to-sky-500"></div>

            <!-- Student Profile Info -->
            <div class="flex items-start gap-3 sm:gap-4 pt-1">
                <!-- Avatar Initial -->
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white flex items-center justify-center font-black text-2xl shadow-md shrink-0 ring-4 ring-brand-50">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>

                <!-- Name & Details -->
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5 mb-1">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Siswa Aktif
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-bold border border-indigo-200">
                            Tingkatan {{ $tingkatan }}
                        </span>
                    </div>

                    <h2 class="text-base sm:text-lg font-black text-slate-900 leading-tight">
                        {{ $student->name }}
                    </h2>

                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-600 font-medium">
                        <span>NISN: <strong class="text-slate-800">{{ $student->nisn }}</strong></span>
                        <span class="text-slate-300">•</span>
                        <span>Kelas: <strong class="text-slate-800">{{ $student->classroom->grade ?? '' }} {{ $student->classroom->name ?? '-' }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- 2 Summary Cards (Kedisiplinan & Bimbingan) -->
            <div class="grid grid-cols-2 gap-2 sm:gap-3 mt-3.5 pt-3 border-t border-slate-100">
                <!-- Disiplin -->
                <div class="bg-slate-50 rounded-xl p-2.5 sm:p-3 border border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Kedisiplinan</span>
                        <span class="text-xs">🏆</span>
                    </div>
                    <div class="mt-0.5 flex items-baseline gap-1">
                        <span class="text-lg sm:text-xl font-black text-slate-900 leading-none">{{ $student->poin_disiplin ?? 100 }}</span>
                        <span class="text-[10px] font-bold text-slate-400">/ 100 Poin</span>
                    </div>
                    <p class="text-[10px] font-bold text-emerald-700 mt-1 truncate">
                        {{ ($student->poin_disiplin ?? 100) >= 90 ? 'Sangat Tertib' : (($student->poin_disiplin ?? 100) >= 75 ? 'Cukup Tertib' : 'Perlu Perhatian') }}
                    </p>
                </div>

                <!-- Bimbingan Wali Kelas -->
                <div class="bg-slate-50 rounded-xl p-2.5 sm:p-3 border border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status Belajar</span>
                        <span class="text-xs">⭐</span>
                    </div>
                    <div class="mt-0.5 flex items-baseline gap-1">
                        <span class="text-base sm:text-lg font-black text-slate-900 leading-none truncate">{{ $statusPendampingan }}</span>
                    </div>
                    <p class="text-[10px] font-bold text-brand-700 mt-1 truncate">
                        {{ $gwRingkasan['total_pertemuan'] ?? 0 }} Sesi Evaluasi
                    </p>
                </div>
            </div>

            <!-- Tombol Hubungi Guru Wali via WhatsApp (Full Width di Mobile, Ramah Jempol) -->
            @if(!empty($cleanWa))
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <a href="https://wa.me/{{ $cleanWa }}?text={{ $waMessage }}" target="_blank" class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold text-xs shadow-sm transition-all active:scale-[0.98]">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        <span>Konsultasi Wali Kelas ({{ $namaGuru }})</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- 3. PESAN WALI KELAS UNTUK ORANG TUA (WARM & READABLE)          -->
        <!-- ============================================================== -->
        @if($hasGuruWali && !empty($gwRingkasan['catatan_terakhir']))
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50/40 border border-blue-200/80 rounded-2xl p-4 sm:p-5 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                            💬
                        </span>
                        <h3 class="text-xs sm:text-sm font-black text-blue-950">Catatan Wali Kelas untuk Ayah & Bunda</h3>
                    </div>
                    @if(!empty($gwRingkasan['tanggal_terakhir']))
                        <span class="text-[10px] font-bold text-blue-700 bg-white/80 px-2 py-0.5 rounded-md border border-blue-100 shrink-0">
                            {{ \Carbon\Carbon::parse($gwRingkasan['tanggal_terakhir'])->locale('id')->isoFormat('D MMM YYYY') }}
                        </span>
                    @endif
                </div>

                <!-- Kutipan Catatan Perkembangan -->
                <div class="bg-white rounded-xl p-3 border border-blue-100 shadow-2xs">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-blue-800 mb-1">Perkembangan Ananda:</p>
                    <p class="text-xs sm:text-sm text-slate-700 italic leading-relaxed">
                        "{{ $gwRingkasan['catatan_terakhir'] }}"
                    </p>
                </div>

                <!-- Rekomendasi di Rumah -->
                @if(!empty($gwRingkasan['tindak_lanjut_terakhir']))
                    <div class="bg-emerald-50/90 rounded-xl p-3 border border-emerald-200/80">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 mb-1 flex items-center gap-1">
                            <span>💡</span> Rekomendasi Pendampingan di Rumah:
                        </p>
                        <p class="text-xs text-slate-800 font-semibold leading-relaxed">
                            {{ $gwRingkasan['tindak_lanjut_terakhir'] }}
                        </p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Peringatan Khusus Jika Tingkatan C -->
        @if($hasGuruWali && $tingkatan === 'C' && !empty($gwSiswa['catatan_permanen']))
            <div class="bg-rose-50 border border-rose-200 rounded-2xl p-3.5 flex items-start gap-2.5">
                <span class="text-lg shrink-0">⚠️</span>
                <div>
                    <h4 class="text-xs font-black text-rose-900 uppercase">Catatan Perhatian Khusus (Tingkatan C)</h4>
                    <p class="text-xs text-rose-800 mt-0.5 leading-relaxed font-medium">{{ $gwSiswa['catatan_permanen'] }}</p>
                </div>
            </div>
        @endif

        <!-- ============================================================== -->
        <!-- 4. SEGMENTED TAB SWITCHER (3 TABS BERSIH & JELAS DI HP)         -->
        <!-- ============================================================== -->
        <div class="bg-slate-200/80 p-1 rounded-2xl no-print">
            <div class="grid grid-cols-3 gap-1 text-center">
                <button onclick="switchTab('nilai')" id="tab-btn-nilai" class="tab-btn py-2.5 px-1.5 text-xs font-bold rounded-xl transition-all bg-white text-brand-700 shadow-sm flex items-center justify-center gap-1.5 active:scale-95">
                    <span>📘</span>
                    <span>Nilai Guru</span>
                </button>
                <button onclick="switchTab('absensi')" id="tab-btn-absensi" class="tab-btn py-2.5 px-1.5 text-xs font-bold rounded-xl transition-all text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5 active:scale-95">
                    <span>📅</span>
                    <span>Kehadiran</span>
                </button>
                <button onclick="switchTab('riwayat')" id="tab-btn-riwayat" class="tab-btn py-2.5 px-1.5 text-xs font-bold rounded-xl transition-all text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5 active:scale-95">
                    <span>📋</span>
                    <span>Riwayat ({{ count($gwRiwayat) }})</span>
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 1: NILAI & EVALUASI GURU WALI (DESAIN DUA BARIS RAPI)       -->
        <!-- ============================================================== -->
        <div id="tab-content-nilai" class="space-y-3.5">
            
            @if(!$hasGuruWali)
                <div class="bg-white rounded-2xl p-8 text-center border border-slate-200 shadow-xs">
                    <p class="text-3xl mb-2">📋</p>
                    <h4 class="text-sm font-bold text-slate-800">Data Evaluasi Belum Terinput</h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Guru wali ananda belum menginput catatan evaluasi untuk periode ini.
                    </p>
                </div>
            @else
                
                @if($evalTerbaru)
                    <!-- Panduan Bintang Singkat (Mudah Dipahami Orang Tua) -->
                    <div class="bg-white px-3.5 py-2.5 rounded-xl border border-slate-200/90 text-[11px] flex items-center justify-between text-slate-600 shadow-2xs">
                        <span class="font-bold text-slate-700">Skala Penilaian:</span>
                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="text-emerald-700 font-extrabold flex items-center gap-0.5">⭐⭐⭐ Baik</span>
                            <span class="text-amber-700 font-extrabold flex items-center gap-0.5">⭐⭐ Cukup</span>
                            <span class="text-rose-700 font-extrabold flex items-center gap-0.5">⭐ Latihan</span>
                        </div>
                    </div>

                    <!-- 1. Kemampuan Akademik & Ibadah -->
                    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200/90 space-y-3">
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="text-base">📖</span>
                                <h4 class="text-xs sm:text-sm font-black text-slate-900">Kemampuan Akademik & Ibadah</h4>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400">5 Indikator</span>
                        </div>

                        @php
                            $akademikItems = [
                                ['label' => 'Membaca', 'sub' => 'Kelancaran membaca teks, intonasi & pemahaman', 'val' => $evalTerbaru['akademik']['membaca'] ?? 'Baik'],
                                ['label' => 'Menulis', 'sub' => 'Kerapihan tulisan, ketepatan ejaan & tanda baca', 'val' => $evalTerbaru['akademik']['menulis'] ?? 'Baik'],
                                ['label' => 'Berhitung', 'sub' => 'Logika angka dasar & pemahaman soal', 'val' => $evalTerbaru['akademik']['berhitung'] ?? 'Baik'],
                                ['label' => 'Bacaan Al-Qur\'an', 'sub' => 'Hafalan surat pendek, kelancaran tajwid & makhraj', 'val' => $evalTerbaru['akademik']['bacaan_quran'] ?? 'Baik'],
                                ['label' => 'Bacaan Sholat', 'sub' => 'Kelancaran gerakan, bacaan wajib & sunnah sholat', 'val' => $evalTerbaru['akademik']['bacaan_sholat'] ?? 'Baik'],
                            ];
                        @endphp

                        <div class="space-y-2.5">
                            @foreach($akademikItems as $item)
                                @php $info = getScoreInfo($item['val']); @endphp
                                <div class="p-3 bg-slate-50/70 hover:bg-slate-50 rounded-xl border border-slate-100 transition-colors">
                                    <!-- Baris Atas: Nama Indikator + Badge Predikat (Tidak Terpotong) -->
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs sm:text-sm font-bold text-slate-800">{{ $item['label'] }}</span>
                                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md border {{ $info['badge'] }} shrink-0">
                                            {{ $info['stars'] }} {{ $info['text'] }}
                                        </span>
                                    </div>

                                    <!-- Keterangan Mudah Dipahami -->
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $item['sub'] }}</p>

                                    <!-- Visual Progress Bar -->
                                    <div class="mt-2 w-full bg-slate-200/70 rounded-full h-1.5 overflow-hidden">
                                        <div class="{{ $info['barColor'] }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $info['percent'] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- 2. Karakter & Budi Pekerti -->
                    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200/90 space-y-3">
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🛡️</span>
                                <h4 class="text-xs sm:text-sm font-black text-slate-900">Karakter & Budi Pekerti</h4>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400">4 Indikator</span>
                        </div>

                        @php
                            $karakterItems = [
                                ['label' => 'Sopan Santun', 'sub' => 'Adab berbicara & menghormati guru serta teman', 'val' => $evalTerbaru['karakter']['sopan_santun'] ?? 'Baik'],
                                ['label' => 'Kedisiplinan', 'sub' => 'Ketepatan waktu, ketertiban seragam & aturan kelas', 'val' => $evalTerbaru['karakter']['kedisiplinan'] ?? 'Baik'],
                                ['label' => 'Tanggung Jawab', 'sub' => 'Penyelesaian tugas harian & amanah piket kelas', 'val' => $evalTerbaru['karakter']['tanggung_jawab'] ?? 'Baik'],
                                ['label' => 'Kejujuran', 'sub' => 'Integritas perkataan & kejujuran saat ujian/ulangan', 'val' => $evalTerbaru['karakter']['kejujuran'] ?? 'Baik'],
                            ];
                        @endphp

                        <div class="space-y-2.5">
                            @foreach($karakterItems as $item)
                                @php $info = getScoreInfo($item['val']); @endphp
                                <div class="p-3 bg-slate-50/70 hover:bg-slate-50 rounded-xl border border-slate-100 transition-colors">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs sm:text-sm font-bold text-slate-800">{{ $item['label'] }}</span>
                                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md border {{ $info['badge'] }} shrink-0">
                                            {{ $info['stars'] }} {{ $info['text'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $item['sub'] }}</p>
                                    <div class="mt-2 w-full bg-slate-200/70 rounded-full h-1.5 overflow-hidden">
                                        <div class="{{ $info['barColor'] }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $info['percent'] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- 3. Kepribadian & Sosial -->
                    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200/90 space-y-3">
                        <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🌱</span>
                                <h4 class="text-xs sm:text-sm font-black text-slate-900">Kepribadian & Sosial</h4>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400">5 Indikator</span>
                        </div>

                        @php
                            $pribadiItems = [
                                ['label' => 'Kestabilan Emosional', 'sub' => 'Pengendalian diri, kesabaran & respon atas masalah', 'val' => $evalTerbaru['kepribadian']['emosional'] ?? 'Baik'],
                                ['label' => 'Kepedulian Sosial', 'sub' => 'Rasa empati, kepekaan & gemar membantu sesama', 'val' => $evalTerbaru['kepribadian']['sosial'] ?? 'Baik'],
                                ['label' => 'Kemandirian', 'sub' => 'Inisiatif belajar mandiri & tidak bergantung pada orang lain', 'val' => $evalTerbaru['kepribadian']['kemandirian'] ?? 'Baik'],
                                ['label' => 'Interaksi Teman', 'sub' => 'Kemampuan berbaur, kerjasama tim & menjaga kerukunan', 'val' => $evalTerbaru['kepribadian']['interaksi'] ?? 'Baik'],
                                ['label' => 'Prilaku di Sekolah', 'sub' => 'Kepatuhan umum terhadap ketertiban lingkungan sekolah', 'val' => $evalTerbaru['kepribadian']['prilaku'] ?? 'Baik'],
                            ];
                        @endphp

                        <div class="space-y-2.5">
                            @foreach($pribadiItems as $item)
                                @php $info = getScoreInfo($item['val']); @endphp
                                <div class="p-3 bg-slate-50/70 hover:bg-slate-50 rounded-xl border border-slate-100 transition-colors">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs sm:text-sm font-bold text-slate-800">{{ $item['label'] }}</span>
                                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md border {{ $info['badge'] }} shrink-0">
                                            {{ $info['stars'] }} {{ $info['text'] }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $item['sub'] }}</p>
                                    <div class="mt-2 w-full bg-slate-200/70 rounded-full h-1.5 overflow-hidden">
                                        <div class="{{ $info['barColor'] }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $info['percent'] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Bakat & Potensi Siswa -->
                @if(!empty($gwRingkasan['catatan_potensi_terakhir']) || !empty($gwSiswa['potensi_permanen']))
                    <div class="bg-amber-50/80 border border-amber-200/90 rounded-2xl p-4 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="text-base">⭐</span>
                                <h4 class="text-xs sm:text-sm font-black text-amber-950">Potensi & Bakat Menonjol</h4>
                            </div>
                            @if(($gwRingkasan['pelatihan_potensi_terakhir'] ?? 'Tidak') === 'Ya')
                                <span class="text-[9px] font-black bg-amber-500 text-white px-2 py-0.5 rounded-md uppercase">
                                    Disarankan Ekskul
                                </span>
                            @endif
                        </div>
                        <p class="text-xs sm:text-sm text-slate-800 font-semibold leading-relaxed bg-white/90 p-3 rounded-xl border border-amber-100">
                            {{ $gwRingkasan['catatan_potensi_terakhir'] ?? ($gwSiswa['potensi_permanen'] ?? 'Bakat ananda terus didukung melalui kegiatan sekolah.') }}
                        </p>
                    </div>
                @endif

            @endif

        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: KEHADIRAN (PRESENSI) & KEDISIPLINAN                    -->
        <!-- ============================================================== -->
        <div id="tab-content-absensi" class="space-y-3.5 hidden">
            
            <!-- Ringkasan 4 Kotak Kehadiran di Mobile (2x2 Grid) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="bg-white p-3 rounded-2xl border border-slate-200/90 text-center shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Hadir</span>
                    <strong class="text-lg sm:text-xl font-black text-emerald-600 block">{{ $hadirCount }} Hari</strong>
                    <span class="text-[9px] text-slate-400">({{ $persenHadir }}% Masuk)</span>
                </div>
                <div class="bg-white p-3 rounded-2xl border border-slate-200/90 text-center shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Izin</span>
                    <strong class="text-lg sm:text-xl font-black text-blue-600 block">{{ $izinCount }} Hari</strong>
                    <span class="text-[9px] text-slate-400">Pemberitahuan</span>
                </div>
                <div class="bg-white p-3 rounded-2xl border border-slate-200/90 text-center shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Sakit</span>
                    <strong class="text-lg sm:text-xl font-black text-amber-600 block">{{ $sakitCount }} Hari</strong>
                    <span class="text-[9px] text-slate-400">Surat Dokter/Ortu</span>
                </div>
                <div class="bg-white p-3 rounded-2xl border border-slate-200/90 text-center shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Alpa / Tanpa Ket.</span>
                    <strong class="text-lg sm:text-xl font-black text-rose-600 block">{{ $alpaCount }} Hari</strong>
                    <span class="text-[9px] text-slate-400">{{ $alpaCount == 0 ? 'Sangat Bagus!' : 'Perlu Diperbaiki' }}</span>
                </div>
            </div>

            <!-- List Kehadiran Harian -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200/90 space-y-3">
                <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span>📅</span>
                        <h4 class="text-xs sm:text-sm font-black text-slate-900">Catatan Presensi Harian</h4>
                    </div>
                    <input type="month" id="filterMonth" class="text-xs border border-slate-200 rounded-lg px-2 py-1 focus:ring-brand-500 focus:border-brand-500 font-medium" title="Filter Bulan">
                </div>

                @if($student->attendances->isEmpty())
                    <p class="text-slate-400 text-xs italic py-6 text-center">Belum ada riwayat kehadiran tercatat.</p>
                @else
                    <div class="space-y-1.5 max-h-72 overflow-y-auto pr-1 custom-scrollbar" id="attendanceList">
                        @foreach($student->attendances->sortByDesc('date') as $absen)
                            <div class="flex justify-between items-center text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-100 attendance-item" data-date="{{ $absen->date }}">
                                <span class="text-slate-700 font-bold text-xs">{{ \Carbon\Carbon::parse($absen->date)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</span>
                                @if($absen->status == 'hadir')
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md font-extrabold text-[10px]">Hadir</span>
                                @elseif($absen->status == 'sakit')
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-md font-extrabold text-[10px]">Sakit</span>
                                @elseif($absen->status == 'izin')
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-md font-extrabold text-[10px]">Izin</span>
                                @else
                                    <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded-md font-extrabold text-[10px]">Alpa</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <p id="noAttendanceMsg" class="text-slate-400 text-xs italic py-6 text-center hidden">Tidak ada data kehadiran pada bulan ini.</p>
                @endif
            </div>

            <!-- Catatan Pelanggaran Tata Tertib -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200/90 space-y-3">
                <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100">
                    <span>🛡️</span>
                    <h4 class="text-xs sm:text-sm font-black text-slate-900">Catatan Pelanggaran Tata Tertib</h4>
                </div>

                @if($student->violations->isEmpty())
                    <div class="py-5 text-center">
                        <span class="text-2xl mb-1 inline-block">🌟</span>
                        <p class="text-xs sm:text-sm font-black text-slate-800">Sangat Disiplin & Tertib!</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Alhamdulillah, tidak ada catatan pelanggaran tata tertib sekolah.</p>
                    </div>
                @else
                    <div class="space-y-2 max-h-72 overflow-y-auto pr-1 custom-scrollbar">
                        @foreach($student->violations->sortByDesc('date') as $violation)
                            <div class="p-3 bg-rose-50/70 border border-rose-100 rounded-xl text-xs space-y-1">
                                <div class="flex justify-between items-start">
                                    <span class="font-bold text-rose-900">{{ $violation->violationCategory->name ?? 'Pelanggaran' }}</span>
                                    <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($violation->date)->format('d/m/Y') }}</span>
                                </div>
                                <p class="text-slate-700 text-[11px] leading-relaxed">{{ $violation->description }}</p>
                                <span class="text-[10px] font-black text-rose-700 block">-{{ $violation->point_minus }} Poin</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: RIWAYAT PERTEMUAN BIMBINGAN WALI KELAS                   -->
        <!-- ============================================================== -->
        <div id="tab-content-riwayat" class="space-y-3 hidden">
            
            @if(empty($gwRiwayat))
                <div class="bg-white rounded-2xl p-8 text-center border border-slate-200/90 shadow-xs">
                    <p class="text-3xl mb-1">📋</p>
                    <p class="text-xs text-slate-400 italic">Belum ada catatan riwayat pertemuan bimbingan.</p>
                </div>
            @else
                <div class="space-y-2.5">
                    @foreach($gwRiwayat as $item)
                        @php
                            $st = $item['status'] ?? 'Baik';
                            $stClass = match($st) {
                                'Baik' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'Cukup' => 'bg-amber-50 text-amber-800 border-amber-200',
                                default => 'bg-rose-50 text-rose-800 border-rose-200'
                            };
                        @endphp
                        <div class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/90 shadow-xs space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-700 border border-brand-200 flex items-center justify-center font-black text-xs shrink-0">
                                        P{{ $item['pertemuan'] }}
                                    </span>
                                    <span class="text-xs font-black text-slate-900">
                                        {{ \Carbon\Carbon::parse($item['tanggal'])->locale('id')->isoFormat('D MMMM YYYY') }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-black px-2 py-0.5 rounded border {{ $stClass }}">
                                    {{ $st }}
                                </span>
                            </div>

                            <p class="text-xs text-slate-600 italic bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                "{{ $item['catatan'] ?: 'Tidak ada catatan khusus.' }}"
                            </p>

                            <button onclick='openDetailModal(@json($item))' class="w-full py-2 bg-slate-100 hover:bg-brand-600 hover:text-white text-slate-700 text-xs font-bold rounded-xl transition-colors flex items-center justify-center gap-1.5 active:scale-95">
                                <span>Lihat Rincian Lengkap</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>

    </main>

    <!-- Footer -->
    <footer class="py-4 px-4 text-center text-[11px] text-slate-400 mt-auto border-t border-slate-200 bg-white">
        <p>&copy; {{ date('Y') }} {{ $gwSekolah['nama'] ?? "SMP Darul Mu'minin Cibatu" }}. Terhubung dengan Aplikasi Guru Wali.</p>
    </footer>

    <!-- ============================================================== -->
    <!-- MODAL DETAIL EVALUASI (NATIVE BOTTOM-SHEET DI MOBILE)           -->
    <!-- ============================================================== -->
    <div id="detailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white rounded-t-3xl sm:rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[85vh] animate-in slide-in-from-bottom duration-200">
            
            <!-- Drag Handle Bar for Mobile Sheet Feel -->
            <div class="pt-2 pb-1 bg-gradient-to-r from-brand-600 to-indigo-700 sm:hidden">
                <div class="w-10 h-1 bg-white/40 rounded-full mx-auto"></div>
            </div>

            <!-- Modal Header -->
            <div class="p-4 bg-gradient-to-r from-brand-600 to-indigo-700 text-white flex justify-between items-center shrink-0">
                <div>
                    <p id="modalDate" class="text-[10px] font-bold uppercase tracking-wider text-brand-200">RINCIAN EVALUASI</p>
                    <h3 class="text-sm sm:text-base font-black">Rincian Nilai Siswa</h3>
                </div>
                <button onclick="closeDetailModal()" class="w-7 h-7 rounded-full bg-white/20 text-white flex items-center justify-center hover:bg-white/30 transition-colors">
                    ✕
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-4 overflow-y-auto space-y-4 flex-grow custom-scrollbar" id="modalBody">
                <!-- Injected via JavaScript -->
            </div>

            <!-- Modal Footer -->
            <div class="p-3 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
                <button onclick="closeDetailModal()" class="w-full sm:w-auto px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition-colors active:scale-95">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Tab Switcher
        function switchTab(tab) {
            const contentNilai = document.getElementById('tab-content-nilai');
            const contentAbsensi = document.getElementById('tab-content-absensi');
            const contentRiwayat = document.getElementById('tab-content-riwayat');
            const allBtns = document.querySelectorAll('.tab-btn');

            allBtns.forEach(btn => {
                btn.classList.remove('bg-white', 'text-brand-700', 'shadow-sm', 'font-black');
                btn.classList.add('text-slate-600', 'font-bold');
            });

            const activeBtn = document.getElementById('tab-btn-' + tab);
            if (activeBtn) {
                activeBtn.classList.add('bg-white', 'text-brand-700', 'shadow-sm', 'font-black');
                activeBtn.classList.remove('text-slate-600');
            }

            contentNilai.classList.add('hidden');
            contentAbsensi.classList.add('hidden');
            contentRiwayat.classList.add('hidden');

            if (tab === 'nilai') {
                contentNilai.classList.remove('hidden');
            } else if (tab === 'absensi') {
                contentAbsensi.classList.remove('hidden');
            } else if (tab === 'riwayat') {
                contentRiwayat.classList.remove('hidden');
            }
        }

        // Modal Functionality
        const detailModal = document.getElementById('detailModal');
        const modalDate = document.getElementById('modalDate');
        const modalBody = document.getElementById('modalBody');

        function openDetailModal(data) {
            modalDate.innerText = 'PERTEMUAN ' + (data.pertemuan || 1) + ' • ' + (data.tanggal || '-');
            
            const badge = (val) => {
                if (val === 'Baik') return '<span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200 shrink-0">⭐⭐⭐ Baik</span>';
                if (val === 'Cukup') return '<span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 shrink-0">⭐⭐ Cukup</span>';
                return '<span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-rose-50 text-rose-800 border border-rose-200 shrink-0">⭐ Latihan</span>';
            };

            const row = (label, val) => `
                <div class="flex justify-between items-center py-2 border-b border-slate-100 text-xs">
                    <span class="text-slate-800 font-bold">${label}</span>
                    ${badge(val || 'Baik')}
                </div>
            `;

            let html = `
                <!-- Catatan Guru & Tindak Lanjut -->
                <div class="space-y-2 text-xs">
                    <div class="p-3 bg-blue-50/80 rounded-xl border border-blue-100">
                        <strong class="text-blue-900 block text-[10px] uppercase font-black">Catatan Wali Kelas:</strong>
                        <p class="text-slate-700 italic mt-0.5">"${data.catatan || 'Tidak ada catatan.'}"</p>
                    </div>
                    ${data.tindak_lanjut ? `
                        <div class="p-3 bg-emerald-50/80 rounded-xl border border-emerald-100">
                            <strong class="text-emerald-900 block text-[10px] uppercase font-black">Rekomendasi di Rumah:</strong>
                            <p class="text-slate-800 font-bold mt-0.5">${data.tindak_lanjut}</p>
                        </div>
                    ` : ''}
                </div>

                <!-- Akademik -->
                <div class="border border-slate-200 rounded-xl p-3 bg-white">
                    <h5 class="text-[11px] font-black uppercase text-slate-800 mb-1 flex items-center gap-1">
                        <span>📖</span> Kemampuan Akademik & Ibadah
                    </h5>
                    ${row('Membaca', data.akademik_membaca)}
                    ${row('Menulis', data.akademik_menulis)}
                    ${row('Berhitung', data.akademik_berhitung)}
                    ${row('Bacaan Al-Qur\'an', data.akademik_bacaan_quran)}
                    ${row('Bacaan Sholat', data.akademik_bacaan_sholat)}
                </div>

                <!-- Karakter -->
                <div class="border border-slate-200 rounded-xl p-3 bg-white">
                    <h5 class="text-[11px] font-black uppercase text-slate-800 mb-1 flex items-center gap-1">
                        <span>🛡️</span> Karakter & Budi Pekerti
                    </h5>
                    ${row('Sopan Santun', data.karakter_sopan_santun)}
                    ${row('Kedisiplinan', data.karakter_kedisiplinan)}
                    ${row('Tanggung Jawab', data.karakter_tanggung_jawab)}
                    ${row('Kejujuran', data.karakter_kejujuran)}
                </div>

                <!-- Kepribadian -->
                <div class="border border-slate-200 rounded-xl p-3 bg-white">
                    <h5 class="text-[11px] font-black uppercase text-slate-800 mb-1 flex items-center gap-1">
                        <span>🌱</span> Kepribadian & Sosial
                    </h5>
                    ${row('Kestabilan Emosional', data.pribadi_emosional)}
                    ${row('Kepedulian Sosial', data.pribadi_sosial)}
                    ${row('Kemandirian', data.pribadi_kemandirian)}
                    ${row('Interaksi Teman', data.pribadi_interaksi)}
                    ${row('Prilaku di Sekolah', data.pribadi_prilaku)}
                </div>
            `;

            modalBody.innerHTML = html;
            detailModal.classList.remove('hidden');
        }

        function closeDetailModal() {
            detailModal.classList.add('hidden');
        }

        // Close modal when clicking outside
        detailModal.addEventListener('click', function(e) {
            if (e.target === detailModal) {
                closeDetailModal();
            }
        });

        // Attendance Filter Month
        document.addEventListener('DOMContentLoaded', function() {
            const filterMonth = document.getElementById('filterMonth');
            const attendanceItems = document.querySelectorAll('.attendance-item');
            const noAttendanceMsg = document.getElementById('noAttendanceMsg');

            if(filterMonth) {
                const now = new Date();
                const currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
                filterMonth.value = currentMonth;
                filterAttendances(currentMonth);

                filterMonth.addEventListener('change', function() {
                    filterAttendances(this.value);
                });

                function filterAttendances(selectedMonth) {
                    let visibleCount = 0;
                    attendanceItems.forEach(item => {
                        const itemDate = item.getAttribute('data-date');
                        if (!selectedMonth || itemDate.startsWith(selectedMonth)) {
                            item.style.display = 'flex';
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    if (noAttendanceMsg) {
                        if (visibleCount === 0 && attendanceItems.length > 0) {
                            noAttendanceMsg.style.display = 'block';
                        } else {
                            noAttendanceMsg.style.display = 'none';
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
