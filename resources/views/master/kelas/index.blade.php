@extends('layouts.admin')

@section('header', 'Data Kelas')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ showModal: false }">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <h2 class="text-lg font-semibold text-gray-800">Daftar Kelas</h2>
        <button @click="showModal = true" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary-600/30">
            + Tambah Kelas
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-medium">No</th>
                    <th class="px-6 py-4 font-medium">Nama Kelas</th>
                    <th class="px-6 py-4 font-medium text-center">Jumlah Siswa</th>
                    <th class="px-6 py-4 font-medium">Wali Kelas</th>
                    <th class="px-6 py-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($classrooms as $index => $kelas)
                <tr class="hover:bg-gray-50/50 transition-colors" x-data="{ showEditModal: false }">
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700 font-medium">{{ $kelas->name }}</td>
                    <td class="px-6 py-4 text-sm text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ $kelas->students_count }} siswa
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($kelas->waliKelas)
                            <span class="text-gray-800 font-medium">{{ $kelas->waliKelas->name }}</span>
                        @else
                            <span class="text-gray-400 italic">Belum ada</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-right space-x-2">
                        <button type="button" @click="showEditModal = true" class="text-blue-500 hover:text-blue-700 font-medium">Set Wali</button>
                        <form action="{{ route('admin.master.kelas.destroy', $kelas->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kelas ini?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                        </form>

                        <!-- Modal Set Wali Kelas -->
                        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity text-left">
                            <div @click.away="showEditModal = false" class="relative w-full max-w-md p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
                                <div class="flex items-center justify-between mb-5">
                                    <h3 class="text-lg font-bold text-gray-900">Set Wali Kelas — {{ $kelas->name }}</h3>
                                    <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                <form action="{{ route('admin.master.kelas.update', $kelas->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Wali Kelas</label>
                                        <select name="teacher_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                            <option value="">— Tidak ada wali kelas —</option>
                                            @foreach($teachers as $guru)
                                                <option value="{{ $guru->id }}" {{ $kelas->teacher_id == $guru->id ? 'selected' : '' }}>
                                                    {{ $guru->name }}{{ $guru->nip ? ' (' . $guru->nip . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mt-6 flex justify-end space-x-3">
                                        <button type="button" @click="showEditModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">Batal</button>
                                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none shadow-sm">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada data kelas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Add -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div @click.away="showModal = false" class="relative w-full max-w-md p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Tambah Kelas Baru</h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('admin.master.kelas.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas</label>
                        <input type="text" name="name" required placeholder="Contoh: MIPA 1" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Wali Kelas (Opsional)</label>
                        <select name="teacher_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="">— Pilih Guru —</option>
                            @foreach($teachers as $guru)
                                <option value="{{ $guru->id }}">{{ $guru->name }}{{ $guru->nip ? ' (' . $guru->nip . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
