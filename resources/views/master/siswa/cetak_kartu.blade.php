<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Kartu Pelajar (QR) - {{ $globalAppName }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>

    <!-- QRCodeJS Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .card-container {
                page-break-inside: avoid;
                break-inside: avoid;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        .id-card {
            width: 86mm;
            height: 54mm;
            box-sizing: border-box;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans min-h-screen p-4 sm:p-6">

    <!-- Top Action Bar (Hidden on Print) -->
    <div class="no-print max-w-6xl mx-auto mb-6 bg-white rounded-2xl shadow-sm border border-gray-200 p-4 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.master.siswa.index') }}" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h1 class="text-lg font-bold text-gray-800">Cetak Kartu Tanda Pelajar (QR Siswa)</h1>
                <p class="text-xs text-gray-500">Total: {{ count($students) }} kartu siap dicetak</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Filter Kelas -->
            <form action="{{ route('admin.master.siswa.cetakKartu') }}" method="GET" class="flex items-center space-x-2">
                <select name="kelas_id" onchange="this.form.submit()" class="rounded-xl border-gray-300 shadow-sm text-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 bg-white">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($classrooms as $kelas)
                        <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->grade && $kelas->grade !== '-' ? $kelas->grade . ' ' : '' }}{{ $kelas->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            <button onclick="window.print()" class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl shadow-md shadow-blue-500/20 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak / Cetak PDF</span>
            </button>
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="max-w-6xl mx-auto flex flex-wrap justify-center gap-6">
        @forelse($students as $index => $siswa)
        <div class="card-container id-card bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden relative flex flex-col justify-between text-gray-800 p-2.5">
            
            <!-- Card Background Deco -->
            <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-blue-50 rounded-full pointer-events-none -z-0"></div>
            <div class="absolute right-12 -top-12 w-24 h-24 bg-indigo-50/60 rounded-full pointer-events-none -z-0"></div>

            <!-- Card Header -->
            <div class="relative z-10 flex items-center justify-between border-b border-blue-500/20 pb-1.5">
                <div class="flex items-center space-x-2">
                    <img src="{{ asset('images/logo.png?v=' . $globalLogoVersion) }}" alt="Logo" class="w-7 h-7 object-contain bg-white rounded p-0.5 shadow-xs border border-gray-100">
                    <div>
                        <h2 class="text-[11px] font-extrabold uppercase tracking-wide text-blue-900 leading-tight">
                            {{ $globalAppName }}
                        </h2>
                        <p class="text-[8px] font-semibold text-blue-600 tracking-wider">KARTU TANDA PELAJAR</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-block px-1.5 py-0.5 text-[8px] font-bold bg-blue-100 text-blue-800 rounded">
                        {{ $siswa->classroom->grade && $siswa->classroom->grade !== '-' ? $siswa->classroom->grade . ' ' : '' }}{{ $siswa->classroom->name }}
                    </span>
                </div>
            </div>

            <!-- Card Body: Student Details & QR Code -->
            <div class="relative z-10 flex items-center justify-between gap-2 my-auto">
                <!-- Info Left -->
                <div class="flex-1 space-y-1 pr-1">
                    <div>
                        <span class="text-[7.5px] uppercase tracking-wider text-gray-400 block font-bold">Nama Lengkap</span>
                        <h3 class="text-[11.5px] font-bold text-gray-900 leading-tight line-clamp-2">
                            {{ $siswa->name }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 gap-1 pt-0.5">
                        <div>
                            <span class="text-[7.5px] uppercase tracking-wider text-gray-400 block font-bold">NISN</span>
                            <span class="text-[10px] font-mono font-bold text-blue-900 block">
                                {{ $siswa->nisn }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[7.5px] uppercase tracking-wider text-gray-400 block font-bold">Status</span>
                            <span class="text-[9px] font-semibold text-emerald-700 block">
                                AKTIF
                            </span>
                        </div>
                    </div>
                </div>

                <!-- QR Code Box Right -->
                <div class="flex-shrink-0 flex flex-col items-center justify-center p-1 bg-white rounded-lg border border-gray-200 shadow-xs">
                    <div id="qrcode-{{ $siswa->id }}" class="w-[68px] h-[68px] flex items-center justify-center"></div>
                    <span class="text-[7px] font-mono text-gray-500 mt-0.5">{{ $siswa->nisn }}</span>
                </div>
            </div>

            <!-- Card Footer Strip -->
            <div class="relative z-10 pt-1 border-t border-gray-100 flex items-center justify-between text-[7px] text-gray-400">
                <span class="truncate">SMP Islam Darul Mu'minin &bull; Presensi Digital</span>
                <span class="font-mono text-gray-500 font-medium">SCAN QR</span>
            </div>
        </div>
        @empty
        <div class="w-full text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100">
            <p class="text-gray-500 text-sm">Tidak ada data siswa ditemukan untuk dicetak.</p>
        </div>
        @endforelse
    </div>

    <!-- Script to Generate QR Codes for each student -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($students as $siswa)
            try {
                new QRCode(document.getElementById("qrcode-{{ $siswa->id }}"), {
                    text: "{{ $siswa->nisn }}",
                    width: 68,
                    height: 68,
                    colorDark : "#0f172a",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
            } catch (e) {
                console.error(e);
            }
            @endforeach
        });
    </script>
</body>
</html>
