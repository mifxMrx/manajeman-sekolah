<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SMP Islam Darul Mu\'minin') }} - Admin</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AlpineJS for interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- SheetJS for Excel Import -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        /* Optional custom scrollbar for sidebar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.4); }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="font-sans antialiased text-gray-900 bg-gradient-to-br from-blue-50 to-blue-200 min-h-screen" x-data="adminLayout()" x-init="initLayout()">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="flex-shrink-0 bg-gradient-to-b from-blue-700 to-blue-500 text-white shadow-2xl transition-all duration-300 ease-in-out transform absolute md:relative z-20 flex flex-col" :class="{'translate-x-0': sidebarOpen, '-translate-x-full md:translate-x-0': !sidebarOpen, 'w-20': sidebarCollapsed, 'w-64': !sidebarCollapsed}">
            <div class="h-full flex flex-col overflow-hidden">
                <div class="h-16 flex items-center justify-between border-b border-white/20 bg-blue-800/20 transition-all duration-300" :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-6'">
                    <!-- Logo when collapsed -->
                    <div x-show="sidebarCollapsed" x-cloak class="flex items-center justify-center w-full">
                        <img src="{{ asset('images/logo.png?v=' . $globalLogoVersion) }}" alt="Logo" class="w-10 h-10 object-contain rounded-md shadow-sm bg-white p-1">
                    </div>
                    <!-- Full Title & Logo when expanded -->
                    <div x-show="!sidebarCollapsed" class="flex items-center space-x-3 w-full overflow-hidden">
                        <img src="{{ asset('images/logo.png?v=' . $globalLogoVersion) }}" alt="Logo" class="w-8 h-8 flex-shrink-0 object-contain rounded-md shadow-sm bg-white p-0.5">
                        <span class="text-xl font-bold tracking-wider drop-shadow-md truncate">{{ $globalAppName }}</span>
                    </div>
                    <button x-show="!sidebarCollapsed" @click="sidebarOpen = false" class="md:hidden ml-auto text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 space-y-1 transition-all duration-300" :class="sidebarCollapsed ? 'px-2' : 'px-3'">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Dashboard">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Dashboard</span>
                    </a>
                    
                    <div class="pt-5 pb-2" :class="sidebarCollapsed ? 'text-center' : 'px-3'">
                        <p x-show="!sidebarCollapsed" class="text-xs font-bold text-blue-200 uppercase tracking-wider opacity-80 whitespace-nowrap">Kesiswaan</p>
                        <p x-show="sidebarCollapsed" x-cloak class="text-[10px] font-bold text-blue-300 uppercase tracking-wider opacity-80">—</p>
                    </div>
                    <a href="{{ route('admin.kesiswaan.absensi') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.kesiswaan.absensi') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Absensi Siswa">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Absensi Siswa</span>
                    </a>
                    <a href="{{ route('admin.kesiswaan.scan') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.kesiswaan.scan*') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Scan QR Absensi">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap flex items-center">
                            Scan QR Siswa
                            <span class="ml-2 px-1.5 py-0.5 text-[9px] font-bold bg-amber-400 text-blue-900 rounded-full">QR</span>
                        </span>
                    </a>
                    <a href="{{ route('admin.kesiswaan.pelanggaran') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.kesiswaan.pelanggaran') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Pelanggaran">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Pelanggaran</span>
                    </a>
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.kesiswaan.skor') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.kesiswaan.skor') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Skor Disiplin">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Skor Disiplin</span>
                    </a>
                    @endif

                    <div class="pt-5 pb-2" :class="sidebarCollapsed ? 'text-center' : 'px-3'">
                        <p x-show="!sidebarCollapsed" class="text-xs font-bold text-blue-200 uppercase tracking-wider opacity-80 whitespace-nowrap">Kurikulum</p>
                        <p x-show="sidebarCollapsed" x-cloak class="text-[10px] font-bold text-blue-300 uppercase tracking-wider opacity-80">—</p>
                    </div>
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.kurikulum.jadwal') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.kurikulum.jadwal') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Jadwal Mengajar">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Jadwal Mengajar</span>
                    </a>
                    @endif
                    <a href="{{ route('admin.kurikulum.absensi') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.kurikulum.absensi') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Absensi Guru">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Absensi Guru</span>
                    </a>
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.kurikulum.kinerja') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.kurikulum.kinerja') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Kinerja Guru">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Kinerja Guru</span>
                    </a>
                    @endif

                    @if(auth()->user()->role === 'admin')
                    <div class="pt-5 pb-2" :class="sidebarCollapsed ? 'text-center' : 'px-3'">
                        <p x-show="!sidebarCollapsed" class="text-xs font-bold text-blue-200 uppercase tracking-wider opacity-80 whitespace-nowrap">Laporan & Master</p>
                        <p x-show="sidebarCollapsed" x-cloak class="text-[10px] font-bold text-blue-300 uppercase tracking-wider opacity-80">—</p>
                    </div>
                    <a href="{{ route('admin.leaderboard.index') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.leaderboard.*') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Leaderboard Kelas">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Leaderboard Kelas</span>
                    </a>
                    <a href="{{ route('admin.master.kunjungan') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.master.kunjungan') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Data Kunjungan">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Kunjungan Portal</span>
                    </a>
                    <a href="{{ route('admin.master.siswa.index') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.master.siswa.*') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Data Siswa">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Data Siswa</span>
                    </a>
                    <a href="{{ route('admin.master.guru.index') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.master.guru.*') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Data Guru">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Data Guru</span>
                    </a>
                    <a href="{{ route('admin.master.kelas.index') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.master.kelas.*') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Data Kelas">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Data Kelas</span>
                    </a>
                    <a href="{{ route('admin.settings.index') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Pengaturan Aplikasi">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Pengaturan Aplikasi</span>
                    </a>
                    <a href="{{ route('admin.backup.index') }}" class="flex items-center py-2.5 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.backup.*') ? 'bg-white/20 text-white shadow-inner' : 'text-blue-50 hover:bg-white/10 hover:text-white' }}" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-3'" title="Backup Database">
                        <svg class="w-5 h-5 flex-shrink-0" :class="sidebarCollapsed ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Backup Database</span>
                    </a>
                    @endif
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col w-0 overflow-hidden relative bg-transparent">
            <!-- Header -->
            <header class="h-16 flex-shrink-0 flex items-center justify-between px-6 bg-white/40 backdrop-blur-md border-b border-white/40 z-10 sticky top-0 shadow-sm">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-blue-700 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md p-1 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <!-- Desktop minimize toggle -->
                    <button @click="sidebarCollapsed = !sidebarCollapsed" class="hidden md:block text-blue-700 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md p-1 mr-4 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h1 class="text-xl font-bold text-blue-900 drop-shadow-sm">@yield('header')</h1>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <span class="text-xs sm:text-sm font-semibold text-blue-800 hidden xl:block bg-blue-100/50 px-3 py-1 rounded-full">{{ now()->translatedFormat('l, d F Y') }}</span>

                    @if(auth()->check() && auth()->user()->role === 'guru_piket')
                    <!-- Dropdown Guru Piket di Navbar (Sejajar dengan Logout) -->
                    <div class="flex items-center space-x-1.5 bg-white/90 backdrop-blur-md px-2.5 py-1.5 rounded-xl border border-blue-200 shadow-xs hover:border-blue-300 transition-colors">
                        <span class="text-sm">👨‍🏫</span>
                        <div class="hidden lg:block text-left pr-1">
                            <span class="text-[9px] uppercase font-bold text-blue-600 block leading-tight">Guru Piket:</span>
                        </div>
                        <select x-model="piketTeacherId" @change="updatePiketTeacher($event)" class="text-xs font-semibold text-gray-800 bg-transparent border-none py-0 pl-1 pr-6 focus:ring-0 cursor-pointer">
                            <option value="">-- Pilih Guru Piket --</option>
                            @foreach($globalTeachers ?? [] as $teacher)
                                <option value="{{ $teacher->id }}" data-name="{{ $teacher->name }}">
                                    {{ $teacher->name }} {{ $teacher->nip ? '('.$teacher->nip.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- User Info Badge -->
                    <div class="flex items-center space-x-2 bg-white/70 py-1 px-3 rounded-full border border-blue-200/60 shadow-xs">
                        <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-xs">
                            <span x-text="isGuruPiket && piketTeacherName ? piketTeacherName.charAt(0) : '{{ substr(auth()->user()->name, 0, 1) }}'">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                        <div class="text-left hidden md:block">
                            <span class="text-xs font-bold text-gray-800 leading-none block" x-text="isGuruPiket && piketTeacherName ? piketTeacherName : '{{ auth()->user()->name }}'">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] text-blue-600 font-semibold leading-none capitalize block mt-0.5">
                                {{ auth()->user()->role === 'guru_piket' ? 'Guru Piket' : (auth()->user()->role === 'admin' ? 'Administrator' : auth()->user()->role) }}
                            </span>
                        </div>
                    </div>

                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors px-3 sm:px-4 py-1.5 rounded-lg shadow-md border border-blue-500">
                            Logout
                        </button>
                    </form>
                </div>
            </header>

            <!-- Main view -->
            <main class="flex-1 overflow-y-auto p-6 md:p-8">
                <!-- Global Floating Notification (Toast) -->
                <div x-data="{ show: false, message: '', type: 'success' }" 
                     x-init="
                        @if(session('success')) 
                            message = '{{ addslashes(session('success')) }}'; 
                            type = 'success'; 
                            show = true; 
                            setTimeout(() => show = false, 3500); 
                        @elseif(session('error')) 
                            message = '{{ addslashes(session('error')) }}'; 
                            type = 'error'; 
                            show = true; 
                            setTimeout(() => show = false, 3500); 
                        @endif
                     "
                     x-show="show" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="fixed bottom-5 right-5 z-[100] flex items-center p-4 mb-4 text-sm rounded-xl shadow-2xl border border-gray-100"
                     :class="type === 'success' ? 'bg-white text-gray-500' : 'bg-red-50 text-red-600 border-red-100'"
                     role="alert" style="display: none;">
                    <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg"
                         :class="type === 'success' ? 'text-green-500 bg-green-100' : 'text-red-500 bg-red-100'">
                        <svg x-show="type === 'success'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        <svg x-show="type === 'error'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    </div>
                    <div class="ms-3 text-sm font-bold tracking-wide" :class="type === 'success' ? 'text-gray-700' : 'text-red-700'" x-text="message"></div>
                    <button @click="show = false" type="button" class="ms-auto -mx-1.5 -my-1.5 bg-transparent hover:bg-gray-100 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 inline-flex items-center justify-center h-8 w-8 ml-4 transition-colors" :class="type === 'success' ? 'text-gray-400 hover:text-gray-900' : 'text-red-400 hover:text-red-900'">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 14 14" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                    </button>
                </div>
                @yield('content')
            </main>
        </div>
    </div>
    <!-- Global SweetAlert Confirmation Interceptor -->
    <script>
        document.addEventListener('click', function(e) {
            let target = e.target.closest('[onclick*="return confirm"]');
            if (target) {
                e.preventDefault();
                e.stopPropagation();
                
                let onclickAttr = target.getAttribute('onclick');
                let match = onclickAttr.match(/confirm\(['"](.*?)['"]\)/);
                let message = match ? match[1] : 'Apakah Anda yakin?';
                
                Swal.fire({
                    title: 'Konfirmasi',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#9ca3af',
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Batal',
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-gray-100',
                        confirmButton: 'rounded-lg px-4 py-2 font-medium',
                        cancelButton: 'rounded-lg px-4 py-2 font-medium'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        target.removeAttribute('onclick');
                        target.click();
                    }
                });
            }
        }, true); // Use capture phase to intercept before inline onclick

        document.addEventListener('submit', function(e) {
            let target = e.target;
            if (target.hasAttribute('onsubmit') && target.getAttribute('onsubmit').includes('return confirm')) {
                e.preventDefault();
                e.stopPropagation();
                
                let onsubmitAttr = target.getAttribute('onsubmit');
                let match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
                let message = match ? match[1] : 'Apakah Anda yakin?';
                
                Swal.fire({
                    title: 'Konfirmasi',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#9ca3af',
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Batal',
                    customClass: {
                        popup: 'rounded-2xl shadow-xl border border-gray-100',
                        confirmButton: 'rounded-lg px-4 py-2 font-medium',
                        cancelButton: 'rounded-lg px-4 py-2 font-medium'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        target.removeAttribute('onsubmit');
                        target.submit();
                    }
                });
            }
        }, true); // Intercept form submits as well
    </script>

    @if(auth()->check() && auth()->user()->role === 'guru_piket')
    <!-- Modal Popup Pilihan Guru Piket Saat Pertama Masuk Akun Guru Piket -->
    <div x-show="showPiketModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-piket-title" 
         role="dialog" 
         aria-modal="true">
        <!-- Backdrop -->
        <div x-show="showPiketModal" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showPiketModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
                
                <!-- Decorative Top Border -->
                <div class="h-2.5 bg-gradient-to-r from-blue-600 via-indigo-600 to-sky-500"></div>

                <div class="p-6 sm:p-7">
                    <!-- Icon -->
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-100 to-indigo-100 text-blue-700 flex items-center justify-center text-3xl mx-auto shadow-inner border border-blue-200/50 mb-4">
                        👨‍🏫
                    </div>

                    <div class="text-center">
                        <span class="inline-block px-3 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60 uppercase tracking-wider mb-2">
                            Petugas Piket Presensi
                        </span>
                        <h3 class="text-xl font-bold text-gray-900" id="modal-piket-title">
                            Pilih Guru Piket Hari Ini
                        </h3>
                        <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                            Selamat bertugas! Silakan tentukan nama guru yang bertugas piket hari ini (<strong class="text-blue-900">{{ now()->translatedFormat('l, d F Y') }}</strong>) untuk presensi siswa.
                        </p>
                    </div>

                    <!-- Dropdown Form -->
                    <div class="mt-5 space-y-2">
                        <label for="modalSelectGuruPiket" class="block text-xs font-bold text-gray-700">
                            Nama Guru Piket Yang Bertugas:
                        </label>
                        <div class="relative">
                            <select id="modalSelectGuruPiket" 
                                    x-model="tempModalTeacherId" 
                                    class="w-full text-sm rounded-xl border border-gray-300 py-3 pl-3.5 pr-10 bg-white text-gray-800 font-medium focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-xs cursor-pointer">
                                <option value="">-- Pilih Nama Guru --</option>
                                @foreach($globalTeachers ?? [] as $teacher)
                                    <option value="{{ $teacher->id }}" data-name="{{ $teacher->name }}">
                                        {{ $teacher->name }} {{ $teacher->nip ? '('.$teacher->nip.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-[11px] text-gray-400">
                            * Anda juga dapat mengubah pilihan ini kapan saja lewat menu dropdown di navbar atas.
                        </p>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 flex items-center space-x-3">
                        <button type="button" 
                                @click="showPiketModal = false" 
                                class="w-1/3 py-2.5 px-4 rounded-xl border border-gray-200 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                            Nanti Saja
                        </button>
                        <button type="button" 
                                @click="confirmPiketModal()" 
                                :disabled="!tempModalTeacherId" 
                                class="w-2/3 py-2.5 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-xs font-bold text-white shadow-md shadow-blue-500/30 disabled:opacity-50 transition-all flex items-center justify-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Mulai Bertugas</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
    function adminLayout() {
        return {
            sidebarOpen: false,
            sidebarCollapsed: false,
            isGuruPiket: {{ (auth()->check() && auth()->user()->role === 'guru_piket') ? 'true' : 'false' }},
            piketTeacherId: localStorage.getItem('piket_teacher_id') || '',
            piketTeacherName: localStorage.getItem('piket_teacher_name') || '',
            showPiketModal: false,
            tempModalTeacherId: '',

            initLayout() {
                if (this.isGuruPiket) {
                    const today = '{{ now()->format('Y-m-d') }}';
                    const savedDate = localStorage.getItem('piket_date');
                    // Tampilkan popup modal jika belum memilih guru piket untuk tanggal hari ini
                    if (!this.piketTeacherId || savedDate !== today) {
                        this.showPiketModal = true;
                    } else {
                        this.tempModalTeacherId = this.piketTeacherId;
                    }
                }

                window.addEventListener('piket-teacher-changed', (e) => {
                    this.piketTeacherId = e.detail.id;
                    this.piketTeacherName = e.detail.name;
                    this.tempModalTeacherId = e.detail.id;
                });
            },

            updatePiketTeacher(event) {
                const select = event.target;
                const opt = select.options[select.selectedIndex];
                this.piketTeacherId = select.value;
                this.piketTeacherName = opt && select.value ? (opt.getAttribute('data-name') || opt.text.trim()) : '';
                this.tempModalTeacherId = this.piketTeacherId;
                localStorage.setItem('piket_teacher_id', this.piketTeacherId);
                localStorage.setItem('piket_teacher_name', this.piketTeacherName);
                localStorage.setItem('piket_date', '{{ now()->format('Y-m-d') }}');

                window.dispatchEvent(new CustomEvent('piket-teacher-changed', {
                    detail: { id: this.piketTeacherId, name: this.piketTeacherName }
                }));
            },

            confirmPiketModal() {
                if (!this.tempModalTeacherId) return;
                const sel = document.getElementById('modalSelectGuruPiket');
                const opt = sel ? sel.options[sel.selectedIndex] : null;
                this.piketTeacherId = this.tempModalTeacherId;
                this.piketTeacherName = opt ? (opt.getAttribute('data-name') || opt.text.trim()) : '';
                localStorage.setItem('piket_teacher_id', this.piketTeacherId);
                localStorage.setItem('piket_teacher_name', this.piketTeacherName);
                localStorage.setItem('piket_date', '{{ now()->format('Y-m-d') }}');
                this.showPiketModal = false;

                window.dispatchEvent(new CustomEvent('piket-teacher-changed', {
                    detail: { id: this.piketTeacherId, name: this.piketTeacherName }
                }));
            }
        };
    }
    </script>
</body>
</html>
