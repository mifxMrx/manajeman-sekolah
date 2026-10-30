@extends('layouts.admin')

@section('header', 'Data Kunjungan Portal Orang Tua')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-blue-100 overflow-hidden" x-data="{ searchTerm: '' }">
    <div class="p-6 border-b border-blue-50 bg-gradient-to-r from-blue-50/50 to-transparent flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold text-blue-900">Statistik Kunjungan Orang Tua</h2>
            <p class="text-sm text-blue-600/80 mt-1">Daftar siswa yang datanya telah dilihat oleh orang tua melalui portal publik.</p>
            <div class="flex items-center gap-4 mt-3">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-semibold">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    {{ $students->total() }} siswa
                </div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs font-semibold">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    {{ $students->sum('view_count') }} total kunjungan
                </div>
                @if(request('kelas_id'))
                    <a href="{{ route('admin.master.kunjungan') }}" class="inline-flex items-center gap-1 text-xs text-red-500 hover:text-red-700 font-medium">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Reset filter
                    </a>
                @endif
            </div>
        </div>
        
        <!-- Search/Filter -->
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto justify-end">
            <form action="{{ route('admin.master.kunjungan') }}" method="GET" class="w-full sm:w-48">
                <select name="kelas_id" onchange="this.form.submit()" class="block w-full py-2 px-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50/50 text-sm">
                    <option value="">Semua Kelas</option>
                    @foreach($classrooms as $kelas)
                        <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->grade }} {{ $kelas->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" x-model="searchTerm" class="block w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50/50 text-sm" placeholder="Cari nama atau NISN...">
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-blue-50/50 text-blue-900 text-sm font-semibold tracking-wide">
                    <th class="py-4 px-6 border-b border-blue-100">Siswa</th>
                    <th class="py-4 px-6 border-b border-blue-100">NISN</th>
                    <th class="py-4 px-6 border-b border-blue-100">Kelas</th>
                    <th class="py-4 px-6 border-b border-blue-100 text-center">Jumlah Kunjungan</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-700 divide-y divide-blue-50">
                @forelse($students as $siswa)
                    <tr class="hover:bg-blue-50/30 transition-colors" x-show="searchTerm === '' || '{{ strtolower($siswa->name) }}'.includes(searchTerm.toLowerCase()) || '{{ $siswa->nisn }}'.includes(searchTerm)">
                        <td class="py-3 px-6 font-medium text-gray-900">
                            {{ $siswa->name }}
                        </td>
                        <td class="py-3 px-6">{{ $siswa->nisn }}</td>
                        <td class="py-3 px-6">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $siswa->classroom->grade }} {{ $siswa->classroom->name }}
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="inline-flex items-center justify-center min-w-[3rem] px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-700">
                                {{ $siswa->view_count }} <span class="text-xs font-normal ml-1">kali</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <p>Belum ada data kunjungan dari portal orang tua.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($students->hasPages())
        <div class="p-4 border-t border-blue-50 bg-gray-50/50">
            {{ $students->links() }}
        </div>
    @endif
</div>
@endsection
