@extends('layouts.admin')

@section('header', 'Laporan Kehadiran Guru')

@section('content')
<div class="space-y-6 no-print">
    <!-- Filter Month (Screen view only) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.kurikulum.kinerja') }}" method="GET" class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bulan</label>
                <input type="month" name="month" value="{{ $month }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" class="w-full md:w-auto bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                    Filter Laporan
                </button>
            </div>
        </form>
    </div>

    <!-- Screen Summary Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-lg font-semibold text-gray-800">
                Laporan Kehadiran Mengajar — {{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}
            </h2>
            <button onclick="window.print()" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Cetak Semua Rekap Guru</span>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium w-16">No</th>
                        <th class="px-6 py-4 font-medium">Nama Guru</th>
                        <th class="px-6 py-4 font-medium">Mata Pelajaran</th>
                        <th class="px-6 py-4 font-medium text-center">Jadwal (Sesi)</th>
                        <th class="px-6 py-4 font-medium text-center text-green-600">Tepat Waktu</th>
                        <th class="px-6 py-4 font-medium text-center text-orange-600">Terlambat</th>
                        <th class="px-6 py-4 font-medium text-center text-red-600">Absen</th>
                        <th class="px-6 py-4 font-medium text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($teachers as $index => $guru)
                    <tr class="hover:bg-gray-50/50 transition-colors" x-data="{ showPrintModal: false }">
                        <td class="px-6 py-4 text-sm font-bold text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $guru->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $guru->subject ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-center font-bold text-gray-600">{{ $guru->total_expected }} kali</td>
                        <td class="px-6 py-4 text-sm text-center font-bold text-green-600">{{ $guru->tepat_waktu }}</td>
                        <td class="px-6 py-4 text-sm text-center font-bold text-orange-500">{{ $guru->terlambat }}</td>
                        <td class="px-6 py-4 text-sm text-center font-bold text-red-500">{{ $guru->absen }}</td>
                        <td class="px-6 py-4 text-sm text-center">
                            <button type="button" onclick="printSingleTeacher('print-card-{{ $guru->id }}')" class="bg-primary-50 hover:bg-primary-100 text-primary-600 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                Cetak Rekap
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada data guru.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print-only view: separate page per teacher with NO percentage indicators -->
<div class="print-only">
    @foreach($teachers as $index => $guru)
    <div id="print-card-{{ $guru->id }}" class="teacher-report-card bg-white p-8 mb-8 text-black" style="page-break-after: always; break-after: page; font-family: 'Times New Roman', Times, serif;">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold uppercase tracking-wide">REKAP KEHADIRAN GURU</h1>
            <h2 class="text-xl font-bold uppercase tracking-wide">SMP ISLAM DARUL MUKMININ CIBATU</h2>
        </div>

        <table class="mb-4 text-sm font-bold w-full max-w-md">
            <tr>
                <td class="w-32 py-1 align-top">Nama Guru</td>
                <td class="w-4 py-1 align-top">:</td>
                <td class="py-1 uppercase">{{ $guru->name }}</td>
            </tr>
            <tr>
                <td class="py-1 align-top">Mata Pelajaran</td>
                <td class="py-1 align-top">:</td>
                <td class="py-1 uppercase">{{ $guru->subject ?? '-' }}</td>
            </tr>
            <tr>
                <td class="py-1 align-top">Bulan</td>
                <td class="py-1 align-top">:</td>
                <td class="py-1 uppercase">{{ \Carbon\Carbon::parse($month . '-01')->translatedFormat('F Y') }}</td>
            </tr>
        </table>

        <div class="overflow-hidden">
            <table class="w-full text-left border-collapse border border-black text-sm">
                <thead>
                    <tr class="border-b border-black">
                        <th class="border-r border-black p-2 text-center w-12 font-bold">No</th>
                        <th class="border-r border-black p-2 font-bold text-center">Tanggal</th>
                        <th class="border-r border-black p-2 font-bold text-center">Kelas</th>
                        <th colspan="2" class="border-r border-black p-2 font-bold text-center">Kehadiran</th>
                        <th class="p-2 font-bold text-center">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($guru->detailed_report as $detIndex => $row)
                    <tr class="border-b border-black">
                        <td class="border-r border-black p-2 text-center">{{ $detIndex + 1 }}.</td>
                        <td class="border-r border-black p-2">{{ $row['tanggal'] }}</td>
                        <td class="border-r border-black p-2 text-center">{{ $row['kelas'] }}</td>
                        <td class="border-r border-black p-2 text-center w-24">{{ $row['kehadiran'] == 'Hadir' ? 'Hadir' : '' }}</td>
                        <td class="border-r border-black p-2 text-center w-24">{{ $row['kehadiran'] == 'Tidak Hadir' ? 'Tidak Hadir' : '' }}</td>
                        <td class="p-2 text-center">{{ $row['keterangan'] ?: '-' }}</td>
                    </tr>
                    @endforeach
                    
                    {{-- Fill remaining rows up to 25 --}}
                    @for($i = count($guru->detailed_report); $i < 25; $i++)
                    <tr class="border-b border-black h-8">
                        <td class="border-r border-black p-2 text-center">{{ $i + 1 }}.</td>
                        <td class="border-r border-black p-2"></td>
                        <td class="border-r border-black p-2"></td>
                        <td class="border-r border-black p-2"></td>
                        <td class="border-r border-black p-2"></td>
                        <td class="p-2"></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="mt-8 flex justify-between text-sm">
            <div>
                <p>Mengetahui,</p>
                <p class="mt-16 font-bold border-b border-black inline-block">Kepala Sekolah</p>
            </div>
            <div class="text-right">
                <p>Cibatu, {{ now()->translatedFormat('d F Y') }}</p>
                <p class="mt-16 font-bold border-b border-black inline-block">Piket Kurikulum</p>
            </div>
        </div>
    </div>
    @endforeach
</div>

<style>
    .print-only {
        display: none;
    }
    @media print {
        html, body {
            width: 100% !important;
            height: auto !important;
            min-height: auto !important;
            overflow: visible !important;
            background: white !important;
            color: black !important;
            font-size: 12pt !important;
        }
        /* Completely break out of Tailwind's flex layout for print */
        * {
            float: none !important;
        }
        .flex, .flex-1, .flex-col, .w-0, .h-screen {
            display: block !important;
            width: auto !important;
            height: auto !important;
            min-height: auto !important;
            overflow: visible !important;
            position: static !important;
        }
        /* Hide all UI elements */
        aside, header, .no-print, form {
            display: none !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
            height: auto !important;
            width: 100% !important;
            display: block !important;
        }
        /* Show print-only view */
        .print-only {
            display: block !important;
            visibility: visible !important;
            width: 100% !important;
        }
        .teacher-report-card {
            display: block; /* Removed !important to allow overriding by hide-on-print */
            page-break-after: always !important;
            break-after: page !important;
            width: 100% !important;
            padding: 0 !important;
            margin-bottom: 0 !important;
        }
        .teacher-report-card.hide-on-print {
            display: none !important;
        }
    }
</style>

<script>
    function printSingleTeacher(cardId) {
        // Temp hide other cards for individual printing
        const cards = document.querySelectorAll('.teacher-report-card');
        cards.forEach(card => {
            if (card.id === cardId) {
                card.classList.remove('hide-on-print');
            } else {
                card.classList.add('hide-on-print');
            }
        });
        
        window.print();
        
        // Restore for "Print All"
        cards.forEach(card => {
            card.classList.remove('hide-on-print');
        });
    }
</script>
@endsection
