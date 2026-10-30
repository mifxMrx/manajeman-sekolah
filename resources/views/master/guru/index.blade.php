@extends('layouts.admin')

@section('header', 'Data Guru')

@section('content')
    @if ($errors->any())
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm">
            <div class="flex items-center mb-1">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                <span class="font-bold">Gagal menyimpan data</span>
            </div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ showModal: false }">
    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center bg-gray-50/50 gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Daftar Guru</h2>
        <div class="flex space-x-3 items-center">
            <button type="button" onclick="confirmBulkDeleteGuru()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                Hapus Terpilih
            </button>
            <a href="{{ route('admin.master.guru.template') }}" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                Unduh Template
            </a>
            <button onclick="document.getElementById('import_file').click()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-emerald-600/30">
                Import Excel
            </button>
            <button @click="showModal = true" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary-600/30">
                + Tambah Guru
            </button>
        </div>

    <!-- Hidden form for import -->
    <form id="import_form" action="{{ route('admin.master.guru.import') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="data" id="import_data">
        <input type="file" id="import_file" accept=".xlsx,.xls,.csv" onchange="handleFile(event)" class="hidden">
    </form>
    </div>

    <form id="bulk-delete-form" action="{{ route('admin.master.guru.bulkDestroy') }}" method="POST">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium w-12">
                            <input type="checkbox" onchange="document.querySelectorAll('.chk-guru').forEach(c => c.checked = this.checked)" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="px-6 py-4 font-medium">NIP</th>
                        <th class="px-6 py-4 font-medium">Nama Guru</th>
                        <th class="px-6 py-4 font-medium">Mata Pelajaran</th>
                        <th class="px-6 py-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($teachers as $guru)
                    <tr class="hover:bg-gray-50/50 transition-colors" x-data="{ showEditModal: false }">
                        <td class="px-6 py-4">
                            <input type="checkbox" name="ids[]" value="{{ $guru->id }}" class="chk-guru rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $guru->nip ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $guru->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $guru->subject ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-right">
                            <button type="button" @click="showEditModal = true" class="text-blue-500 hover:text-blue-700 font-medium mr-3">Edit</button>
                            
                            <button type="button" onclick="confirmDeleteGuru('{{ route('admin.master.guru.destroy', $guru->id) }}', '{{ addslashes($guru->name) }}')" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>

                            <!-- Modal Edit -->
                            <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity text-left">
                                <div @click.away="showEditModal = false" class="relative w-full max-w-md p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
                                    <div class="flex items-center justify-between mb-5">
                                        <h3 class="text-lg font-bold text-gray-900">Edit Guru</h3>
                                        <button type="button" @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <form action="{{ route('admin.master.guru.update', $guru->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">NIP (Opsional)</label>
                                                <input type="text" name="nip" value="{{ $guru->nip }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                                <input type="text" name="name" value="{{ $guru->name }}" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran (Opsional)</label>
                                                <input type="text" name="subject" value="{{ $guru->subject }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                            </div>
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
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada data guru.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <form id="single-delete-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    
    <script>
        function confirmDeleteGuru(url, name) {
            Swal.fire({
                title: 'Hapus Guru?',
                html: `Guru <b>${name}</b> akan dihapus secara permanen.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-2xl shadow-xl',
                    confirmButton: 'rounded-lg px-4 py-2 font-medium',
                    cancelButton: 'rounded-lg px-4 py-2 font-medium'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('single-delete-form');
                    form.action = url;
                    form.submit();
                }
            });
        }

        function confirmBulkDeleteGuru() {
            const checked = document.querySelectorAll('.chk-guru:checked');
            if (checked.length === 0) {
                Swal.fire({
                    title: 'Tidak Ada Data',
                    text: 'Pilih setidaknya satu guru untuk dihapus.',
                    icon: 'info',
                    confirmButtonColor: '#3b82f6',
                    customClass: { popup: 'rounded-2xl shadow-xl', confirmButton: 'rounded-lg px-4 py-2 font-medium' }
                });
                return;
            }
            Swal.fire({
                title: `Hapus ${checked.length} Guru?`,
                text: 'Data yang sudah dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-2xl shadow-xl',
                    confirmButton: 'rounded-lg px-4 py-2 font-medium',
                    cancelButton: 'rounded-lg px-4 py-2 font-medium'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('bulk-delete-form').submit();
                }
            });
        }
    </script>

    <!-- Modal Add -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div @click.away="showModal = false" class="relative w-full max-w-md p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Tambah Guru Baru</h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('admin.master.guru.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP (Opsional)</label>
                        <input type="text" name="nip" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran (Opsional)</label>
                        <input type="text" name="subject" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- JS for Parsing -->
    <script>
        function handleFile(e) {
            var file = e.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, {type: 'array'});
                var firstSheet = workbook.SheetNames[0];
                var excelRows = XLSX.utils.sheet_to_json(workbook.Sheets[firstSheet]);
                document.getElementById('import_data').value = JSON.stringify(excelRows);
                document.getElementById('import_form').submit();
            };
            reader.readAsArrayBuffer(file);
        }
    </script>
@endsection
