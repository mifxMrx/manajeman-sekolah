@extends('layouts.admin')

@section('header', 'Pelanggaran Siswa')

@section('content')
<div class="space-y-6" x-data="{ showModal: false, showEditModal: false, editData: {} }">
    
    <div class="flex justify-between items-center">
        <p class="text-gray-600">Catat dan pantau riwayat pelanggaran kedisiplinan siswa.</p>
        <button @click="showModal = true" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary-600/30">
            + Input Pelanggaran
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h2 class="text-lg font-semibold text-gray-800">Riwayat Pelanggaran Terakhir</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium">Tanggal</th>
                        <th class="px-6 py-4 font-medium pl-10">Deskripsi Pelanggaran</th>
                        <th class="px-6 py-4 font-medium">Kategori</th>
                        <th class="px-6 py-4 font-medium text-center">Minus Poin</th>
                        <th class="px-6 py-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($groupedViolations as $studentId => $studentViolations)
                        @php $siswa = $studentViolations->first()->student; @endphp
                        <tr class="bg-gray-100">
                            <td colspan="5" class="px-6 py-3 text-sm font-bold text-gray-800">
                                {{ $siswa->name }} - {{ $siswa->classroom->grade }} {{ $siswa->classroom->name }} 
                                <span class="ml-2 text-xs font-normal text-gray-500">Total: {{ $studentViolations->count() }} Pelanggaran</span>
                            </td>
                        </tr>
                        @foreach($studentViolations as $violation)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($violation->date)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 pl-10">{{ $violation->description }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $violation->violationCategory ? $violation->violationCategory->name : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                    -{{ $violation->point_minus }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <button @click="editData = {{ $violation->toJson() }}; showEditModal = true" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                <form action="{{ route('admin.kesiswaan.pelanggaran.destroy', $violation->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pelanggaran ini? Poin siswa akan dikembalikan.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada riwayat pelanggaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Input Pelanggaran -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div @click.away="showModal = false" class="relative w-full max-w-lg p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
            <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-4">
                <h3 class="text-lg font-bold text-gray-900">Form Input Pelanggaran</h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('admin.kesiswaan.pelanggaran.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kejadian</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Siswa</label>
                        <select name="student_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <option value="">Cari Siswa...</option>
                            @foreach($students as $siswa)
                                <option value="{{ $siswa->id }}">{{ $siswa->nisn }} - {{ $siswa->name }} ({{ $siswa->classroom->grade }} {{ $siswa->classroom->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Pelanggaran (Opsional)</label>
                        <select name="violation_category_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <option value="">Pilih Kategori...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-poin="{{ $category->point_minus }}">{{ $category->name }} (-{{ $category->point_minus }} Poin)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Catatan</label>
                        <textarea name="description" rows="3" required placeholder="Jelaskan bentuk pelanggaran secara detail" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin Pengurangan (Manual/Otomatis)</label>
                        <input type="number" name="point_minus" required placeholder="Contoh: 10" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                        <p class="text-xs text-gray-500 mt-1">Jika memilih kategori di atas, pastikan mengisikan angka poin yang sesuai atau sesuai kebijakan.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none shadow-sm shadow-red-600/30">Simpan Pelanggaran</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Edit Pelanggaran -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div @click.away="showEditModal = false" class="relative w-full max-w-lg p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
            <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-4">
                <h3 class="text-lg font-bold text-gray-900">Edit Pelanggaran</h3>
                <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form :action="'{{ url('admin/kesiswaan/pelanggaran') }}/' + editData.id" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kejadian</label>
                        <input type="date" name="date" x-model="editData.date" required class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Pelanggaran (Opsional)</label>
                        <select name="violation_category_id" x-model="editData.violation_category_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Pilih Kategori...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }} (-{{ $category->point_minus }} Poin)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Catatan</label>
                        <textarea name="description" rows="3" x-model="editData.description" required class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Poin Pengurangan</label>
                        <input type="number" name="point_minus" x-model="editData.point_minus" required class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 focus:outline-none shadow-sm shadow-indigo-600/30">Update Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
