<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SMP Islam Darul Mu\'minin') }} - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Outfit', 'Plus Jakarta Sans', 'sans-serif'],
                        },
                        colors: {
                            primary: {
                                50: '#f0f9ff',
                                100: '#e0f2fe',
                                500: '#0ea5e9',
                                600: '#0284c7',
                                700: '#0369a1',
                                800: '#075985',
                            }
                        }
                    }
                }
            }
        </script>
        <style>
            .glass-panel {
                background: rgba(255, 255, 255, 0.92);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.6);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.2);
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased min-h-screen relative flex items-center justify-center p-4 sm:p-6 selection:bg-sky-500 selection:text-white">
        @php
            $bgFile = 'images/login-bg.png';
            if (!file_exists(public_path($bgFile))) {
                $bgFile = 'images/login-bg.jpg';
            }
            $bgVer = file_exists(public_path($bgFile)) ? filemtime(public_path($bgFile)) : time();
            $bgUrl = asset($bgFile) . '?v=' . $bgVer;
        @endphp
        <!-- Background Photo with Modern Gradient Overlay -->
        <div class="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat transition-all duration-700" 
             style="background-image: url('{{ $bgUrl }}');">
            <!-- Modern Gradient Overlay: Deep slate & rich navy blue with soft blur -->
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950/85 via-sky-950/80 to-slate-900/90 backdrop-blur-[2px]"></div>
            <!-- Radial depth vignette -->
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_transparent_0%,_rgba(15,23,42,0.65)_100%)]"></div>
        </div>

        <div class="relative z-10 w-full max-w-md my-auto">
            {{ $slot }}
        </div>
    </body>
</html>
