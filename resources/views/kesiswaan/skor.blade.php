@extends('layouts.admin')

@section('header', 'Skor Disiplin Siswa')

@section('content')
<div class="space-y-6">
    <!-- Filter Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.kesiswaan.skor') }}" method="GET" class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Kelas</label>
                <select name="classroom_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($classrooms as $kelas)
                        <option value="{{ $kelas->id }}" {{ $selectedClassroom == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->grade }} {{ $kelas->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="w-full md:w-auto bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary-600/30">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-lg font-semibold text-gray-800">Daftar Poin Disiplin Keseluruhan</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium w-16 text-center">Peringkat</th>
                        <th class="px-6 py-4 font-medium">Nama Siswa</th>
                        <th class="px-6 py-4 font-medium">Kelas</th>
                        <th class="px-6 py-4 font-medium">Status Indikator</th>
                        <th class="px-6 py-4 font-medium text-right">Skor Akhir</th>
                        <th class="px-6 py-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($students as $index => $siswa)
                    @php
                        $poin = $siswa->poin_disiplin;
                        // Determine status and colors
                        if ($poin >= 90) {
                            $status = 'Sangat Baik';
                            $colorClass = 'bg-emerald-500';
                            $textClass = 'text-emerald-700';
                            $bgClass = 'bg-emerald-100';
                        } elseif ($poin >= 75) {
                            $status = 'Baik';
                            $colorClass = 'bg-blue-500';
                            $textClass = 'text-blue-700';
                            $bgClass = 'bg-blue-100';
                        } elseif ($poin >= 50) {
                            $status = 'Cukup';
                            $colorClass = 'bg-yellow-400';
                            $textClass = 'text-yellow-700';
                            $bgClass = 'bg-yellow-100';
                        } else {
                            $status = 'Perlu Perhatian';
                            $colorClass = 'bg-red-500';
                            $textClass = 'text-red-700';
                            $bgClass = 'bg-red-100';
                        }
                        
                        // Cap percentage for progress bar at 100
                        $percentage = min(100, max(0, $poin));
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors" x-data="{ showTambahModal: false }">
                        <td class="px-6 py-4 text-sm font-bold text-gray-500 text-center">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $siswa->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $siswa->classroom->grade }} {{ $siswa->classroom->name }}</td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $bgClass }} {{ $textClass }} mr-3 w-28 justify-center">
                                    {{ $status }}
                                </span>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 max-w-xs">
                                    <div class="{{ $colorClass }} h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-xl font-bold {{ $textClass }}">{{ $poin }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button type="button" @click="showTambahModal = true" class="text-blue-500 hover:text-blue-700 font-medium">Tambah Skor</button>
                            
                            <!-- Modal Tambah Skor -->
                            <div x-show="showTambahModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity text-left">
                                <div @click.away="showTambahModal = false" class="relative w-full max-w-md p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
                                    <div class="flex items-center justify-between mb-5">
                                        <h3 class="text-lg font-bold text-gray-900">Tambah Skor Disiplin</h3>
                                        <button type="button" @click="showTambahModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <form action="{{ route('admin.kesiswaan.skor.tambah', $siswa->id) }}" method="POST">
                                        @csrf
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Poin Tambahan</label>
                                                <input type="number" name="tambahan_skor" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Contoh: 10">
                                                <p class="text-xs text-gray-500 mt-1">Poin ini akan ditambahkan ke skor akhir siswa.</p>
                                            </div>
                                        </div>
                                        <div class="mt-6 flex justify-end space-x-3">
                                            <button type="button" @click="showTambahModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">Batal</button>
                                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none shadow-sm">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
