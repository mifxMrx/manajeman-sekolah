@extends('layouts.admin')

@section('header', 'Scan QR Absensi Siswa')

@section('content')
<div class="space-y-6" x-data="qrScanner()">
    <!-- Top Bar & Live Clock -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <div class="p-2.5 bg-gradient-to-tr from-blue-600 to-indigo-600 text-white rounded-xl shadow-md shadow-blue-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Presensi QR Siswa</h2>
                    <p class="text-xs text-gray-500">Scan QR Code pada kartu pelajar atau ketik NISN siswa untuk absensi instan</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Live Clock Widget -->
            <div class="bg-white border border-gray-200 px-3.5 py-1.5 rounded-xl shadow-xs flex items-center space-x-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span class="text-xs font-semibold text-gray-500">Waktu Sistem:</span>
                <span class="text-sm font-mono font-bold text-blue-900" x-text="liveTime"></span>
                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">WIB</span>
            </div>

            <!-- Filter Tanggal -->
            <form action="{{ route('admin.kesiswaan.scan') }}" method="GET" class="flex items-center space-x-2">
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="rounded-xl border-gray-300 shadow-sm text-sm py-2 px-3 focus:ring-primary-500 focus:border-primary-500 bg-white">
            </form>

            <a href="{{ route('admin.kesiswaan.absensi') }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-colors">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Absensi Manual</span>
            </a>
            
            <a href="{{ route('admin.master.siswa.cetakKartu') }}" target="_blank" class="inline-flex items-center space-x-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-md shadow-indigo-600/20 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak Kartu Siswa</span>
            </a>
        </div>
    </div>

    <!-- Banner Guru Piket Bertugas -->
    <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-4 sm:p-5 text-white shadow-md border border-blue-800/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20 text-yellow-400 text-2xl flex-shrink-0 shadow-inner">
                👨‍🏫
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-500/30 text-blue-200 border border-blue-400/30">
                        Petugas Piket
                    </span>
                    <span class="text-xs text-blue-300">Presensi: {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
                </div>
                <h3 class="text-lg font-bold text-white mt-0.5 flex items-center space-x-2">
                    <span x-text="selectedTeacherName ? selectedTeacherName : 'Belum Memilih Guru Piket'"></span>
                    <span x-show="selectedTeacherName" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                        <svg class="w-3 h-3 mr-1 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        Sedang Bertugas
                    </span>
                </h3>
            </div>
        </div>

        <!-- Dropdown Selector Guru Piket -->
        <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/20 self-start md:self-auto w-full md:w-auto">
            <label for="selectGuruPiket" class="text-xs font-semibold text-blue-200 whitespace-nowrap flex items-center space-x-1.5">
                <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span>Pilih Guru Piket:</span>
            </label>
            <select id="selectGuruPiket" x-model="selectedTeacherId" @change="onTeacherChange($event)" class="text-xs font-semibold rounded-lg bg-white text-gray-800 border-none py-1.5 pl-2.5 pr-8 focus:ring-2 focus:ring-yellow-400 cursor-pointer shadow-sm">
                <option value="">-- Pilih Guru Piket --</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}" data-name="{{ $teacher->name }}">
                        {{ $teacher->name }} {{ $teacher->nip ? '('.$teacher->nip.')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Hadir Hari Ini</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1" x-text="countHadir">{{ $totalHadir }}</h3>
            </div>
            <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Izin / Sakit</p>
                <h3 class="text-2xl font-bold text-blue-600 mt-1">
                    <span x-text="countIzin">{{ $totalIzin }}</span> / <span x-text="countSakit">{{ $totalSakit }}</span>
                </h3>
            </div>
            <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Alpha</p>
                <h3 class="text-2xl font-bold text-red-600 mt-1" x-text="countAlpha">{{ $totalAlpha }}</h3>
            </div>
            <div class="w-11 h-11 bg-red-50 rounded-xl flex items-center justify-center text-red-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Siswa</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ $totalStudents }}</h3>
            </div>
            <div class="w-11 h-11 bg-gray-100 rounded-xl flex items-center justify-center text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Main Scanner & Feedback Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Column: Camera Scanner & USB Barcode input (7 Cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Card Header -->
                <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center space-x-2">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" x-show="isScanning"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3" :class="isScanning ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                        </span>
                        <h3 class="font-bold text-gray-800 text-sm sm:text-base">Kamera Scanner QR</h3>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- Sound toggle -->
                        <button type="button" @click="soundEnabled = !soundEnabled" class="p-2 rounded-lg border text-xs font-medium transition-colors" :class="soundEnabled ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-gray-50 text-gray-500 border-gray-200'" title="Suara Efek">
                            <span x-show="soundEnabled" class="flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                                <span class="hidden sm:inline">Suara Aktif</span>
                            </span>
                            <span x-show="!soundEnabled" class="flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path></svg>
                                <span class="hidden sm:inline">Mute</span>
                            </span>
                        </button>

                        <!-- Camera Switch -->
                        <select x-model="selectedCamera" @change="restartCamera()" class="text-xs rounded-lg border-gray-300 py-1.5 pl-2 pr-7 bg-white focus:ring-primary-500 focus:border-primary-500">
                            <template x-for="cam in cameras" :key="cam.id">
                                <option :value="cam.id" x-text="cam.label || 'Kamera ' + cam.id"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Scanner Viewport Area -->
                <div class="p-4 sm:p-6 bg-slate-900 relative">
                    <div id="reader" class="w-full rounded-xl overflow-hidden bg-black aspect-video flex items-center justify-center relative">
                        <!-- Laser line animation when scanning -->
                        <div x-show="isScanning" class="scanner-laser pointer-events-none"></div>
                    </div>

                    <!-- Overlay instructions -->
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-300">
                        <span class="flex items-center space-x-1.5">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Arahkan barcode / QR kartu ke tengah kotak kamera</span>
                        </span>
                        <div class="space-x-2">
                            <button type="button" @click="toggleCamera()" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 transition">
                                <span x-text="isScanning ? 'Jeda Kamera' : 'Mulai Kamera'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Barcode Gun / Manual Input Bar -->
                <div class="p-4 sm:p-5 bg-gray-50/70 border-t border-gray-100">
                    <form @submit.prevent="submitManualNisn()" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label for="inputNisn" class="text-xs font-semibold uppercase tracking-wider text-gray-700 flex items-center space-x-1.5">
                                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                <span>Input NISN / Scanner USB Barcode</span>
                            </label>
                            <span class="text-[11px] text-gray-500">Auto-submit saat tekan Enter / Tembak barcode</span>
                        </div>

                        <div class="flex rounded-xl shadow-sm">
                            <div class="relative flex-grow focus-within:z-10">
                                <input type="text" id="inputNisn" x-model="manualNisn" x-ref="nisnInput" placeholder="Ketik NISN atau scan dengan scanner barcode USB..." class="block w-full rounded-none rounded-l-xl border-gray-300 py-3 pl-4 pr-10 text-sm focus:border-primary-500 focus:ring-primary-500 font-mono tracking-wider" autocomplete="off">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                            </div>
                            <button type="submit" :disabled="loading" class="relative -ml-px inline-flex items-center space-x-2 rounded-r-xl border border-transparent bg-primary-600 px-6 py-3 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 transition-colors shadow-sm shadow-primary-600/30">
                                <span x-show="!loading">Absen</span>
                                <span x-show="loading" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
                            </button>
                        </div>

                        <!-- Status Option Radio Pills -->
                        <div class="flex items-center space-x-2 pt-1">
                            <span class="text-xs text-gray-500 mr-2">Status:</span>
                            <label class="cursor-pointer">
                                <input type="radio" name="scanStatus" value="hadir" x-model="selectedStatus" class="sr-only">
                                <span class="px-3 py-1 rounded-lg text-xs font-semibold transition-all inline-block" :class="selectedStatus === 'hadir' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Hadir</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="scanStatus" value="sakit" x-model="selectedStatus" class="sr-only">
                                <span class="px-3 py-1 rounded-lg text-xs font-semibold transition-all inline-block" :class="selectedStatus === 'sakit' ? 'bg-yellow-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Sakit</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="scanStatus" value="izin" x-model="selectedStatus" class="sr-only">
                                <span class="px-3 py-1 rounded-lg text-xs font-semibold transition-all inline-block" :class="selectedStatus === 'izin' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Izin</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="scanStatus" value="alpha" x-model="selectedStatus" class="sr-only">
                                <span class="px-3 py-1 rounded-lg text-xs font-semibold transition-all inline-block" :class="selectedStatus === 'alpha' ? 'bg-red-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">Alpha (-5)</span>
                            </label>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Live Result & Student Info Card (5 Cols) -->
        <div class="lg:col-span-5 space-y-4">
            
            <!-- Result Feedback Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all duration-300"
                 :class="{
                     'border-emerald-300 ring-4 ring-emerald-50': lastResult?.status === 'created' || lastResult?.status === 'updated',
                     'border-amber-300 ring-4 ring-amber-50': lastResult?.status === 'already_checked',
                     'border-rose-300 ring-4 ring-rose-50': lastResult?.status === 'not_found' || lastResult?.status === 'error'
                 }">
                
                <!-- State: Empty / Waiting -->
                <div x-show="!lastResult" class="text-center py-12">
                    <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl mx-auto flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    </div>
                    <h4 class="text-base font-bold text-gray-800">Menunggu Scan Siswa...</h4>
                    <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto">Arahkan kartu pelajar ke scanner atau ketikkan NISN pada kolom yang tersedia.</p>
                    <div class="mt-4 inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-ping"></span>
                        Siap Memindai
                    </div>
                </div>

                <!-- State: Result Received -->
                <div x-show="lastResult" x-cloak class="space-y-4">
                    <!-- Status Header Banner -->
                    <div class="p-3.5 rounded-xl flex items-center space-x-3"
                         :class="{
                             'bg-emerald-50 text-emerald-800 border border-emerald-200': lastResult?.status === 'created' || lastResult?.status === 'updated',
                             'bg-amber-50 text-amber-800 border border-amber-200': lastResult?.status === 'already_checked',
                             'bg-rose-50 text-rose-800 border border-rose-200': lastResult?.status === 'not_found' || lastResult?.status === 'error'
                         }">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <template x-if="lastResult?.status === 'created' || lastResult?.status === 'updated'">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </template>
                            <template x-if="lastResult?.status === 'already_checked'">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </template>
                            <template x-if="lastResult?.status === 'not_found' || lastResult?.status === 'error'">
                                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </template>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-bold" x-text="lastResult?.message"></h4>
                        </div>
                    </div>

                    <!-- Student Info Card (when found) -->
                    <template x-if="lastResult?.student">
                        <div class="border border-gray-100 rounded-xl p-4 bg-gray-50/50 space-y-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-lg flex items-center justify-center shadow-md">
                                    <span x-text="lastResult?.student.name.charAt(0)"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-bold text-gray-900 truncate" x-text="lastResult?.student.name"></h3>
                                    <div class="flex items-center space-x-2 mt-0.5">
                                        <span class="text-xs text-gray-500 font-mono" x-text="'NISN: ' + lastResult?.student.nisn"></span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800" x-text="lastResult?.student.classroom"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-gray-200/60 text-xs">
                                <div>
                                    <span class="text-gray-500 block">Poin Disiplin:</span>
                                    <span class="font-bold text-gray-800 text-sm" x-text="lastResult?.student.poin_disiplin"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block">Waktu Absen:</span>
                                    <span class="font-bold text-emerald-700 text-sm" x-text="(lastResult?.time || '-') + ' WIB'"></span>
                                </div>
                                <div class="col-span-2 pt-1.5 border-t border-gray-200/50 flex items-center justify-between text-[11px] text-gray-500">
                                    <span>Petugas Piket:</span>
                                    <span class="font-bold text-blue-900" x-text="selectedTeacherName || 'Belum Dipilih'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Tips & Helper Card -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50/50 rounded-2xl p-5 border border-blue-100/80 text-blue-900 text-xs space-y-2">
                <div class="flex items-center space-x-2 font-bold text-blue-800">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Petunjuk Penggunaan Scanner QR:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-blue-800/80 leading-relaxed">
                    <li>Gunakan <strong>kartu pelajar</strong> siswa yang telah dicetak dengan QR Code NISN.</li>
                    <li>Scanner kamera bekerja otomatis mendeteksi kode QR begitu terlihat di layar.</li>
                    <li>Alat <strong>USB Barcode Scanner</strong> tembak laser dapat langsung digunakan kapan saja tanpa perlu menyentuh mouse.</li>
                    <li>Jika siswa sudah absen pada hari ini, sistem otomatis memberi peringatan dan mencegah duplikasi.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Live Attendance Log (Today's Attendances) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-gray-50/50">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Riwayat Kehadiran Siswa Hari Ini</h3>
                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }} — Data diperbarui secara real-time</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <div x-show="selectedTeacherName" class="hidden sm:flex items-center space-x-1.5 text-xs text-blue-900 bg-blue-50 px-3 py-1.5 rounded-xl border border-blue-200/60 font-semibold">
                    <span class="text-gray-500 font-normal">Guru Piket:</span>
                    <span x-text="selectedTeacherName"></span>
                </div>
                <input type="text" x-model="searchQuery" placeholder="Cari nama / NISN..." class="text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-primary-500 focus:border-primary-500 bg-white">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium w-16">No</th>
                        <th class="px-6 py-4 font-medium">Jam Masuk</th>
                        <th class="px-6 py-4 font-medium">NISN</th>
                        <th class="px-6 py-4 font-medium">Nama Siswa</th>
                        <th class="px-6 py-4 font-medium">Kelas</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <template x-for="(item, index) in filteredHistory" :key="item.id">
                        <tr class="hover:bg-blue-50/30 transition-colors" :class="item.isNew ? 'bg-emerald-50/50 animate-pulse' : ''">
                            <td class="px-6 py-4 text-gray-500" x-text="index + 1"></td>
                            <td class="px-6 py-4 font-semibold text-gray-800">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium bg-gray-100 text-gray-800" x-text="item.time"></span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-mono text-xs" x-text="item.nisn"></td>
                            <td class="px-6 py-4 font-semibold text-gray-900" x-text="item.name"></td>
                            <td class="px-6 py-4 text-gray-700">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800" x-text="item.classroom"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize"
                                      :class="{
                                          'bg-green-100 text-green-800': item.status === 'hadir',
                                          'bg-yellow-100 text-yellow-800': item.status === 'sakit',
                                          'bg-blue-100 text-blue-800': item.status === 'izin',
                                          'bg-red-100 text-red-800': item.status === 'alpha'
                                      }" x-text="item.status"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="cancelAttendance(item.id, item.name)" class="text-xs font-medium text-red-600 hover:text-red-800 p-1 hover:bg-red-50 rounded transition-colors" title="Batalkan absensi">
                                    Batalkan
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredHistory.length === 0">
                        <td colspan="7" class="px-6 py-10 text-center text-gray-400 text-sm">
                            Belum ada siswa yang diabsen pada tanggal ini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- HTML5-QRCODE Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<style>
/* CSS scanning laser line */
.scanner-laser {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, rgba(16, 185, 129, 0) 0%, rgba(16, 185, 129, 1) 50%, rgba(16, 185, 129, 0) 100%);
    box-shadow: 0 0 10px 2px rgba(16, 185, 129, 0.8);
    animation: scanAnimation 2s ease-in-out infinite alternate;
    z-index: 10;
}
@keyframes scanAnimation {
    0% { top: 5%; }
    100% { top: 95%; }
}
#reader video {
    border-radius: 0.75rem;
    object-fit: cover !important;
}
</style>

<script>
function qrScanner() {
    return {
        html5QrCode: null,
        isScanning: false,
        cameras: [],
        selectedCamera: '',
        soundEnabled: true,
        manualNisn: '',
        selectedStatus: 'hadir',
        loading: false,
        lastResult: null,
        searchQuery: '',
        currentDate: '{{ $date }}',
        liveTime: '',
        selectedTeacherId: localStorage.getItem('piket_teacher_id') || '',
        selectedTeacherName: localStorage.getItem('piket_teacher_name') || '',
        countHadir: {{ $totalHadir }},
        countSakit: {{ $totalSakit }},
        countIzin: {{ $totalIzin }},
        countAlpha: {{ $totalAlpha }},
        history: [
            @foreach($attendances as $att)
            {
                id: {{ $att->id }},
                nisn: '{{ $att->student->nisn }}',
                name: '{{ addslashes($att->student->name) }}',
                classroom: '{{ $att->student->classroom ? ($att->student->classroom->grade && $att->student->classroom->grade !== "-" ? $att->student->classroom->grade . " " : "") . $att->student->classroom->name : "-" }}',
                status: '{{ $att->status }}',
                time: '{{ $att->check_in_time ? date("H:i:s", strtotime($att->check_in_time)) : ($att->created_at ? $att->created_at->format("H:i:s") : "-") }}',
                isNew: false
            },
            @endforeach
        ],

        onTeacherChange(event) {
            const select = event.target;
            const opt = select.options[select.selectedIndex];
            this.selectedTeacherId = select.value;
            this.selectedTeacherName = opt && select.value ? (opt.getAttribute('data-name') || opt.text.trim()) : '';
            localStorage.setItem('piket_teacher_id', this.selectedTeacherId);
            localStorage.setItem('piket_teacher_name', this.selectedTeacherName);
            localStorage.setItem('piket_date', '{{ now()->format('Y-m-d') }}');

            window.dispatchEvent(new CustomEvent('piket-teacher-changed', {
                detail: { id: this.selectedTeacherId, name: this.selectedTeacherName }
            }));
        },

        get filteredHistory() {
            if (!this.searchQuery) return this.history;
            const q = this.searchQuery.toLowerCase();
            return this.history.filter(h => h.name.toLowerCase().includes(q) || h.nisn.toLowerCase().includes(q) || h.classroom.toLowerCase().includes(q));
        },

        init() {
            if (this.selectedTeacherId) {
                this.$nextTick(() => {
                    const sel = document.getElementById('selectGuruPiket');
                    if (sel) {
                        sel.value = this.selectedTeacherId;
                        const opt = sel.options[sel.selectedIndex];
                        if (opt && !this.selectedTeacherName) {
                            this.selectedTeacherName = opt.getAttribute('data-name') || opt.text.trim();
                        }
                    }
                });
            }

            window.addEventListener('piket-teacher-changed', (e) => {
                this.selectedTeacherId = e.detail.id;
                this.selectedTeacherName = e.detail.name;
                const sel = document.getElementById('selectGuruPiket');
                if (sel) sel.value = this.selectedTeacherId;
            });

            this.initAudio();
            this.initCamera();
            this.startLiveClock();
            this.$nextTick(() => {
                if (this.$refs.nisnInput) this.$refs.nisnInput.focus();
            });
        },

        startLiveClock() {
            const update = () => {
                const now = new Date();
                this.liveTime = now.toLocaleTimeString('id-ID', { hour12: false });
            };
            update();
            setInterval(update, 1000);
        },

        // Web Audio API for offline sounds
        audioCtx: null,
        initAudio() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (AudioContext) {
                    this.audioCtx = new AudioContext();
                }
            } catch (e) {
                console.log('Web Audio not supported');
            }
        },

        playSound(type) {
            if (!this.soundEnabled || !this.audioCtx) return;
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            try {
                const now = this.audioCtx.currentTime;
                if (type === 'success') {
                    // Pleasant two-tone chime
                    const osc = this.audioCtx.createOscillator();
                    const gain = this.audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(this.audioCtx.destination);
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, now); // A5
                    osc.frequency.exponentialRampToValueAtTime(1174.66, now + 0.15); // D6
                    gain.gain.setValueAtTime(0.3, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.35);
                    osc.start(now);
                    osc.stop(now + 0.35);
                } else if (type === 'warning') {
                    // Double tone warning
                    const osc = this.audioCtx.createOscillator();
                    const gain = this.audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(this.audioCtx.destination);
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(520, now);
                    osc.frequency.setValueAtTime(420, now + 0.12);
                    gain.gain.setValueAtTime(0.3, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
                    osc.start(now);
                    osc.stop(now + 0.3);
                } else {
                    // Error buzz
                    const osc = this.audioCtx.createOscillator();
                    const gain = this.audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(this.audioCtx.destination);
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(180, now);
                    gain.gain.setValueAtTime(0.3, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
                    osc.start(now);
                    osc.stop(now + 0.3);
                }
            } catch (e) {
                console.error(e);
            }
        },

        initCamera() {
            if (typeof Html5Qrcode === 'undefined') {
                setTimeout(() => this.initCamera(), 500);
                return;
            }

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    this.cameras = devices;
                    // Default to back camera on mobile or first available
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('environment'));
                    this.selectedCamera = backCam ? backCam.id : devices[0].id;
                    this.startCamera();
                } else {
                    console.log('No cameras found.');
                }
            }).catch(err => {
                console.warn('Unable to get cameras', err);
            });
        },

        startCamera() {
            if (!this.selectedCamera) return;
            if (this.html5QrCode) {
                this.stopCamera().then(() => this.startCamera());
                return;
            }

            this.html5QrCode = new Html5Qrcode("reader");
            const config = {
                fps: 15,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.777778
            };

            this.html5QrCode.start(
                this.selectedCamera,
                config,
                (decodedText, decodedResult) => {
                    this.onScanSuccess(decodedText);
                },
                (errorMessage) => {
                    // Scanning ongoing...
                }
            ).then(() => {
                this.isScanning = true;
            }).catch(err => {
                console.error("Camera start error:", err);
                this.isScanning = false;
            });
        },

        stopCamera() {
            if (this.html5QrCode && this.isScanning) {
                return this.html5QrCode.stop().then(() => {
                    this.isScanning = false;
                    this.html5QrCode.clear();
                    this.html5QrCode = null;
                }).catch(err => console.error(err));
            }
            return Promise.resolve();
        },

        restartCamera() {
            this.stopCamera().then(() => {
                setTimeout(() => this.startCamera(), 300);
            });
        },

        toggleCamera() {
            if (this.isScanning) {
                this.stopCamera();
            } else {
                this.startCamera();
            }
        },

        lastScannedCode: null,
        lastScannedTime: 0,

        onScanSuccess(decodedText) {
            const now = Date.now();
            // Prevent duplicate triggers within 2.5s for identical string
            if (this.lastScannedCode === decodedText && (now - this.lastScannedTime) < 2500) {
                return;
            }
            this.lastScannedCode = decodedText;
            this.lastScannedTime = now;

            this.processNisn(decodedText);
        },

        submitManualNisn() {
            if (!this.manualNisn) return;
            const nisn = this.manualNisn.trim();
            this.manualNisn = '';
            this.processNisn(nisn);
        },

        processNisn(nisn) {
            if (this.loading) return;
            this.loading = true;

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('{{ route("admin.kesiswaan.scan.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    nisn: nisn,
                    date: this.currentDate,
                    status: this.selectedStatus
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                this.loading = false;
                this.lastResult = body;

                if (body.success) {
                    this.playSound('success');

                    // Update local history
                    const student = body.student;
                    const attendance = body.attendance;
                    const time = body.time;

                    // If existing in history, update; else prepend
                    const existingIndex = this.history.findIndex(h => h.nisn === student.nisn);
                    if (existingIndex !== -1) {
                        const oldStatus = this.history[existingIndex].status;
                        this.adjustCount(oldStatus, -1);
                        this.adjustCount(body.attendance.status, 1);

                        this.history[existingIndex].status = body.attendance.status;
                        this.history[existingIndex].time = time;
                        this.history[existingIndex].isNew = true;
                    } else {
                        this.adjustCount(body.attendance.status, 1);
                        this.history.unshift({
                            id: attendance.id,
                            nisn: student.nisn,
                            name: student.name,
                            classroom: student.classroom,
                            status: attendance.status,
                            time: time,
                            isNew: true
                        });
                    }

                    setTimeout(() => {
                        this.history.forEach(h => h.isNew = false);
                    }, 3000);

                } else if (body.status === 'already_checked') {
                    this.playSound('warning');
                } else {
                    this.playSound('error');
                }

                if (this.$refs.nisnInput) {
                    this.$refs.nisnInput.focus();
                }
            })
            .catch(err => {
                this.loading = false;
                this.playSound('error');
                this.lastResult = {
                    status: 'error',
                    message: 'Terjadi kesalahan sistem saat memproses absensi.'
                };
            });
        },

        adjustCount(status, diff) {
            if (status === 'hadir') this.countHadir += diff;
            else if (status === 'sakit') this.countSakit += diff;
            else if (status === 'izin') this.countIzin += diff;
            else if (status === 'alpha') this.countAlpha += diff;
        },

        cancelAttendance(id, name) {
            Swal.fire({
                title: 'Batalkan Absensi?',
                text: `Hapus catatan kehadiran untuk ${name}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Tutup'
            }).then((res) => {
                if (res.isConfirmed) {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    fetch(`{{ url('admin/kesiswaan/absensi/scan') }}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const idx = this.history.findIndex(h => h.id === id);
                            if (idx !== -1) {
                                const st = this.history[idx].status;
                                this.adjustCount(st, -1);
                                this.history.splice(idx, 1);
                            }
                            Swal.fire('Dibatalkan', data.message, 'success');
                        }
                    });
                }
            });
        }
    }
}
</script>
@endsection
