<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SMP Islam Darul Mu\'minin') }} - Guru</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AlpineJS for interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside class="flex-shrink-0 w-64 bg-white border-r border-gray-200 transition-transform duration-300 ease-in-out transform absolute md:relative z-20" :class="{'translate-x-0': sidebarOpen, '-translate-x-full md:translate-x-0': !sidebarOpen}">
            <div class="h-full flex flex-col">
                <div class="h-16 flex items-center px-6 border-b border-gray-100">
                    <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-600 to-indigo-600">SMP IDM (Guru)</span>
                    <button @click="sidebarOpen = false" class="md:hidden ml-auto text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                    <a href="{{ route('guru.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg hover:bg-primary-50 hover:text-primary-600 transition-colors {{ request()->routeIs('guru.dashboard') ? 'bg-primary-50 text-primary-600' : 'text-gray-700' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col w-0 overflow-hidden bg-gray-50/50 relative">
            <!-- Header -->
            <header class="h-16 flex-shrink-0 flex items-center justify-between px-6 bg-white/80 backdrop-blur-md border-b border-gray-200 z-10 sticky top-0">
                <div class="flex items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-md p-1 mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800">@yield('header')</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500 hidden sm:block">{{ now()->format('l, d F Y') }}</span>
                    <!-- User Info Badge -->
                    <div class="flex items-center space-x-2 bg-gray-100/80 py-1 px-3 rounded-full border border-gray-200 text-xs">
                        <div class="w-6 h-6 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold text-xs uppercase">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <span class="font-semibold text-gray-800 hidden md:inline">{{ auth()->user()->name }}</span>
                    </div>
                    <!-- Logout -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
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
        }, true);

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
        }, true);
    </script>
</body>
</html>
