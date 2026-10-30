@extends('layouts.admin')

@section('header', 'Dashboard Utama')

@section('content')
<div class="space-y-8 animate-fade-in-up">
    
    <!-- Welcome Banner & Filter -->
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-3xl p-8 shadow-2xl text-white mb-6">
        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
        <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/20 rounded-full blur-3xl"></div>
        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-indigo-400/30 rounded-full blur-2xl"></div>
        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between">
            <div class="mb-6 md:mb-0">
                <h2 class="text-3xl font-extrabold tracking-tight mb-2">Selamat Datang di {{ $globalAppName }}</h2>
                <p class="text-blue-100 font-medium text-lg max-w-xl">Pantau efektivitas sekolah, absensi, dan kedisiplinan siswa dalam satu panel pintar yang terintegrasi.</p>
            </div>
            <form action="{{ route('admin.dashboard') }}" method="GET" class="bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20 shadow-lg flex flex-col sm:flex-row gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-blue-100 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date', date('Y-m-d')) }}" class="w-full bg-white/90 text-gray-800 rounded-lg border-0 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-blue-100 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date', date('Y-m-d')) }}" class="w-full bg-white/90 text-gray-800 rounded-lg border-0 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" class="bg-white text-indigo-600 hover:bg-gray-50 font-bold px-4 py-2 rounded-lg text-sm shadow-md transition-colors">
                    Filter
                </button>
                @if(request('start_date') || request('end_date'))
                <a href="{{ route('admin.dashboard') }}" class="bg-transparent border border-white/40 hover:bg-white/20 text-white font-medium px-3 py-2 rounded-lg text-sm transition-colors text-center">
                    Reset
                </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6">
        <!-- Stat Card 1: Efektivitas Sekolah -->
        <div class="bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-indigo-200 group cursor-default">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Efektivitas</p>
                    <h3 class="text-4xl font-black text-indigo-700 tracking-tight">{{ $effectiveSchoolPercent }}%</h3>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center text-indigo-600 shadow-inner group-hover:rotate-12 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
            <div class="mt-5 relative z-10">
                <div class="flex justify-between text-xs font-semibold text-gray-500 mb-2">
                    <span>Target: 100%</span>
                    <span class="text-indigo-600">Terbaik</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2.5 shadow-inner overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2.5 rounded-full shadow-md relative" style="width: {{ $effectiveSchoolPercent }}%">
                        <div class="absolute top-0 right-0 bottom-0 left-0 bg-[url('https://www.transparenttextures.com/patterns/diagonal-stripes.png')] opacity-20"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stat Card 2: Guru hadir hari ini -->
        <a href="{{ route('admin.kurikulum.absensi') }}" class="block bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-blue-200 group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Guru Hadir</p>
                    <h3 class="text-4xl font-black text-blue-600 tracking-tight">
                        {{ $teacherPresentToday }}<span class="text-2xl text-blue-300">/{{ $teachersScheduledToday }}</span>
                    </h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 shadow-inner group-hover:-rotate-12 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-5 relative z-10 flex items-center space-x-2 text-sm font-semibold text-gray-500">
                <span class="flex items-center text-emerald-500 bg-emerald-50 px-2 py-1 rounded-md">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Sesuai Jadwal Hari Ini
                </span>
            </div>
        </a>

        <!-- Stat Card 3: Siswa Hadir -->
        <a href="{{ route('admin.kesiswaan.absensi') }}" class="block bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-teal-200 group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-teal-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Siswa Hadir</p>
                    <h3 class="text-4xl font-black text-teal-600 tracking-tight">
                        {{ $studentPresentToday }}
                    </h3>
                </div>
                <div class="w-12 h-12 bg-teal-100 rounded-2xl flex items-center justify-center text-teal-600 shadow-inner group-hover:-rotate-12 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div class="mt-5 relative z-10 flex items-center space-x-2 text-sm font-semibold text-gray-500">
                <span class="flex items-center text-teal-500 bg-teal-50 px-2 py-1 rounded-md">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Tercatat Hadir
                </span>
            </div>
        </a>

        <!-- Stat Card 4: Siswa tidak hadir -->
        <a href="{{ route('admin.kesiswaan.absensi') }}" class="block bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-orange-200 group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-orange-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Siswa Absen</p>
                    <h3 class="text-4xl font-black text-orange-500 tracking-tight">{{ $studentNotPresentCount }}</h3>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600 shadow-inner group-hover:rotate-12 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-5 relative z-10 flex items-center space-x-2 text-sm font-semibold text-gray-500">
                <span class="flex items-center text-orange-500 bg-orange-50 px-2 py-1 rounded-md">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Hari ini
                </span>
                <span>Perlu Pantauan</span>
            </div>
        </a>

        <!-- Stat Card 4: Total pelanggaran -->
        <a href="{{ route('admin.kesiswaan.pelanggaran') }}" class="block bg-white rounded-3xl p-6 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-red-200 group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-1">Pelanggaran</p>
                    <h3 class="text-4xl font-black text-red-600 tracking-tight">{{ $violationsThisMonth }}</h3>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center text-red-600 shadow-inner group-hover:-rotate-12 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="mt-5 relative z-10 flex items-center space-x-2 text-sm font-semibold text-gray-500">
                <span class="flex items-center text-red-500 bg-red-50 px-2 py-1 rounded-md">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Bulan ini
                </span>
                <span>Tinjau Laporan</span>
            </div>
        </a>
    </div>

    <!-- Secondary Grid: Chart & Activity Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Comparison Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between" x-data="{ viewMode: 'summary' }">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800">Perbandingan Kinerja</h3>
                <div class="bg-gray-100 p-1 rounded-lg inline-flex">
                    <button @click="viewMode = 'summary'" :class="viewMode === 'summary' ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1.5 text-xs font-bold rounded-md transition-all">Ringkasan</button>
                    <button @click="viewMode = 'daily'; setTimeout(() => initChart(), 100)" :class="viewMode === 'daily' ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1.5 text-xs font-bold rounded-md transition-all">Grafik Harian</button>
                </div>
            </div>
            
            @php
                // Hitung performa Kesiswaan & Kurikulum dari data yang dikirim controller (sudah mendukung filter tanggal)
                $kesiswaanScore = max(0, min(100, round($studentAttendancePercent - $studentViolationPercent)));
                $kurikulumScore = round($teacherAttendancePercent);
            @endphp

            <div x-show="viewMode === 'summary'" x-transition.opacity class="relative h-64 w-full flex pl-8 pb-8">
                <!-- Y-axis values & Grid lines -->
                <div class="absolute inset-0 flex flex-col justify-between pointer-events-none z-0 pb-8">
                    <div class="border-b border-gray-200 w-full h-0 flex items-center"><span class="text-sm text-gray-500 w-8 text-right pr-2 absolute left-0">100</span></div>
                    <div class="border-b border-gray-200 w-full h-0 flex items-center"><span class="text-sm text-gray-500 w-8 text-right pr-2 absolute left-0">80</span></div>
                    <div class="border-b border-gray-200 w-full h-0 flex items-center"><span class="text-sm text-gray-500 w-8 text-right pr-2 absolute left-0">60</span></div>
                    <div class="border-b border-gray-200 w-full h-0 flex items-center"><span class="text-sm text-gray-500 w-8 text-right pr-2 absolute left-0">40</span></div>
                    <div class="border-b border-gray-200 w-full h-0 flex items-center"><span class="text-sm text-gray-500 w-8 text-right pr-2 absolute left-0">20</span></div>
                    <div class="border-b border-gray-300 w-full h-0 flex items-center"><span class="text-sm text-gray-500 w-8 text-right pr-2 absolute left-0">0</span></div>
                </div>

                <!-- Bars Container -->
                <div class="w-full flex justify-around items-end z-10 h-full border-l border-b border-gray-200 pl-2">
                    
                    <!-- Bar 1: Kesiswaan -->
                    <div class="w-1/3 flex flex-col items-center group relative h-full justify-end">
                        <div class="w-full rounded-t-lg transition-all duration-300" 
                             style="height: {{ max(1, $kesiswaanScore) }}%; background-color: #818cf8;">
                        </div>
                        <span class="absolute -bottom-7 text-sm text-gray-500 font-medium">Kesiswaan</span>
                        <!-- Optional Tooltip -->
                        <div class="absolute -top-10 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs py-1 px-2 rounded pointer-events-none">
                            {{ $kesiswaanScore }}%
                        </div>
                    </div>

                    <!-- Border separator line between bars (optional based on image, looks like there is a subtle vertical line dividing sections or just grid) -->
                    <div class="h-full border-l border-gray-100 absolute left-1/2 -z-10"></div>

                    <!-- Bar 2: Kurikulum -->
                    <div class="w-1/3 flex flex-col items-center group relative h-full justify-end">
                        <div class="w-full rounded-t-lg transition-all duration-300" 
                             style="height: {{ max(1, $kurikulumScore) }}%; background-color: #00fc9bff;">
                        </div>
                        <span class="absolute -bottom-7 text-sm text-gray-500 font-medium">Kurikulum</span>
                        <!-- Optional Tooltip -->
                        <div class="absolute -top-10 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs py-1 px-2 rounded pointer-events-none">
                            {{ $kurikulumScore }}%
                        </div>
                    </div>

                </div>
            </div>

            <!-- Daily View -->
            <div x-show="viewMode === 'daily'" x-transition.opacity style="display: none;" class="relative h-64 w-full mt-4">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <!-- Premium Activity Feed -->
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 flex flex-col overflow-hidden h-[450px] relative">
            <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center z-10">
                <h3 class="text-lg font-extrabold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Aktivitas Terkini
                </h3>
                <a href="{{ route('admin.kesiswaan.pelanggaran') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50 px-3 py-1.5 rounded-full">Lihat Semua</a>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-3 relative z-0 custom-scrollbar pb-10">
                @forelse($recentViolations as $violation)
                <a href="{{ route('admin.kesiswaan.pelanggaran') }}" class="block p-4 rounded-2xl border border-gray-100 bg-white hover:bg-indigo-50/50 hover:border-indigo-100 transition-all duration-300 group shadow-sm hover:shadow-md">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 mt-1">
                            <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center group-hover:scale-110 group-hover:rotate-12 transition-all duration-300 shadow-sm border border-red-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 font-medium">
                                <span class="font-extrabold text-gray-900 group-hover:text-indigo-700 transition-colors">{{ $violation->student->name }}</span>
                                melakukan pelanggaran kedisiplinan.
                            </p>
                            <p class="text-xs font-bold text-gray-400 mt-1 flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ $violation->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </a>
                @empty
                <div class="h-full flex flex-col items-center justify-center p-6 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <p class="text-sm font-bold text-gray-500">Belum ada aktivitas pelanggaran terkini.</p>
                    <p class="text-xs text-gray-400 mt-1">Semua siswa tertib!</p>
                </div>
                @endforelse
            </div>
            
            <!-- Fade out effect for scrolling list -->
            <div class="absolute bottom-0 left-0 right-0 h-10 bg-gradient-to-t from-white to-transparent pointer-events-none rounded-b-3xl z-10"></div>
        </div>
    </div>

</div>

<!-- Add subtle entrance animation -->
<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out forwards;
    }
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #e5e7eb;
        border-radius: 20px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: #d1d5db;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let chartInstance = null;
    function initChart() {
        if(chartInstance) return; // prevent duplicate initialization
        const ctx = document.getElementById('dailyChart').getContext('2d');
        const trendData = @json($trendData);
        
        const labels = trendData.map(d => d.day + ' (' + d.date + ')');
        const dataKesiswaan = trendData.map(d => d.kesiswaan);
        const dataKurikulum = trendData.map(d => d.kurikulum);

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Kinerja Kesiswaan',
                        data: dataKesiswaan,
                        borderColor: '#818cf8',
                        backgroundColor: 'rgba(129, 140, 248, 0.2)',
                        borderWidth: 3,
                        pointBackgroundColor: '#818cf8',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#818cf8',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Kinerja Kurikulum',
                        data: dataKurikulum,
                        borderColor: '#00fc9b',
                        backgroundColor: 'rgba(0, 252, 155, 0.2)',
                        borderWidth: 3,
                        pointBackgroundColor: '#00fc9b',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#00fc9b',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            font: {
                                family: "'Inter', sans-serif",
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleFont: { size: 13, family: "'Inter', sans-serif" },
                        bodyFont: { size: 12, family: "'Inter', sans-serif" },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                        },
                        ticks: {
                            font: { family: "'Inter', sans-serif" },
                            callback: function(value) { return value + "%" }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false,
                        },
                        ticks: {
                            font: { family: "'Inter', sans-serif" }
                        }
                    }
                }
            }
        });
    }
</script>

@endsection
