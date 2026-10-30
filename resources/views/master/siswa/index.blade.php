@extends('layouts.admin')

@section('header', 'Data Siswa')

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ 
    showModal: false, 
    showEditModal: false, 
    editData: {}, 
    showQrModal: false, 
    qrData: {},
    openQrModal(id, name, nisn, kelas) {
        this.qrData = { id, name, nisn, kelas };
        this.showQrModal = true;
        this.$nextTick(() => {
            const container = document.getElementById('modal-qrcode');
            if (container) {
                container.innerHTML = '';
                new QRCode(container, {
                    text: nisn,
                    width: 140,
                    height: 140,
                    colorDark : '#0f172a',
                    colorLight : '#ffffff',
                    correctLevel : QRCode.CorrectLevel.M
                });
            }
        });
    }
}">
    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center bg-gray-50/50 gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Daftar Siswa</h2>
        <div class="flex flex-wrap space-x-2 sm:space-x-3 items-center">
            <!-- Filter Kelas -->
            <form action="{{ route('admin.master.siswa.index') }}" method="GET" class="flex items-center space-x-2">
                <select name="kelas_id" class="rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm py-2">
                    <option value="">Semua Kelas</option>
                    @foreach($classrooms as $kelas)
                        <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->grade && $kelas->grade !== '-' ? $kelas->grade . ' ' : '' }}{{ $kelas->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                    Filter
                </button>
            </form>

            <a href="{{ route('admin.master.siswa.cetakKartu', ['kelas_id' => request('kelas_id')]) }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-indigo-600/30 flex items-center space-x-1.5" title="Cetak Kartu Siswa & QR">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                <span>Cetak Kartu QR</span>
            </a>

            <button type="button" onclick="confirmBulkDelete()" class="bg-red-500 hover:bg-red-600 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                Hapus Terpilih
            </button>
            <a href="{{ route('admin.master.siswa.template') }}" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm hidden sm:inline-block">
                Unduh Template
            </a>
            <button onclick="document.getElementById('import_file').click()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-emerald-600/30">
                Import Excel
            </button>
            <button @click="showModal = true" class="bg-primary-600 hover:bg-primary-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary-600/30">
                + Tambah Siswa
            </button>
        </div>

    <!-- Hidden form for import -->
    <form id="import_form" action="{{ route('admin.master.siswa.import') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="data" id="import_data">
        <input type="file" id="import_file" accept=".xlsx,.xls,.csv" onchange="handleFile(event)" class="hidden">
    </form>
    </div>

    <form id="bulk-delete-form" action="{{ route('admin.master.siswa.bulkDestroy') }}" method="POST">
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium w-12">
                            <input type="checkbox" onchange="document.querySelectorAll('.chk-siswa').forEach(c => c.checked = this.checked)" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th class="px-6 py-4 font-medium">NISN</th>
                        <th class="px-6 py-4 font-medium">Nama Siswa</th>
                        <th class="px-6 py-4 font-medium">Kelas</th>
                        <th class="px-6 py-4 font-medium">Poin Disiplin</th>
                        <th class="px-6 py-4 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($students as $siswa)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <input type="checkbox" name="ids[]" value="{{ $siswa->id }}" class="chk-siswa rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $siswa->nisn }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $siswa->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                {{ $siswa->classroom->grade && $siswa->classroom->grade !== '-' ? $siswa->classroom->grade . ' ' : '' }}{{ $siswa->classroom->name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="font-bold {{ $siswa->poin_disiplin < 50 ? 'text-red-500' : 'text-green-500' }}">{{ $siswa->poin_disiplin }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                            <button type="button" @click="openQrModal({{ $siswa->id }}, '{{ addslashes($siswa->name) }}', '{{ addslashes($siswa->nisn) }}', '{{ addslashes(($siswa->classroom->grade && $siswa->classroom->grade !== '-' ? $siswa->classroom->grade . ' ' : '') . $siswa->classroom->name) }}')" class="text-indigo-600 hover:text-indigo-800 font-medium mr-3 inline-flex items-center space-x-1" title="Lihat Kartu & QR Siswa">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                <span>QR</span>
                            </button>
                            <button type="button" @click="editData = { id: {{ $siswa->id }}, nisn: '{{ addslashes($siswa->nisn) }}', name: '{{ addslashes($siswa->name) }}', kelas_manual: '{{ addslashes($siswa->classroom->name) }}' }; showEditModal = true" class="text-blue-500 hover:text-blue-700 font-medium mr-3">Edit</button>
                            <button type="button" onclick="confirmDelete('{{ route('admin.master.siswa.destroy', $siswa->id) }}', '{{ addslashes($siswa->name) }}')" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada data siswa.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Hidden single-delete form (outside bulk form to avoid nesting) --}}
    <form id="single-delete-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    <!-- Modal Edit -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity" style="display: none;">
        <div @click.away="showEditModal = false" class="relative w-full max-w-md p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Edit Data Siswa</h3>
                <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form :action="`{{ url('admin/master/siswa') }}/${editData.id}`" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NISN</label>
                        <input type="text" name="nisn" x-model="editData.nisn" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" x-model="editData.name" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                        <input type="text" name="kelas_manual" x-model="editData.kelas_manual" required placeholder="Contoh: X IPA 1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Add -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div @click.away="showModal = false" class="relative w-full max-w-md p-6 bg-white rounded-2xl shadow-xl border border-gray-100 transform transition-all">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Tambah Siswa Baru</h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form action="{{ route('admin.master.siswa.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NISN</label>
                        <input type="text" name="nisn" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                        <input type="text" name="kelas_manual" required placeholder="Contoh: X IPA 1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none shadow-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal QR Code & Kartu Pelajar -->
    <div x-show="showQrModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-gray-900/50 backdrop-blur-sm transition-opacity">
        <div @click.away="showQrModal = false" class="relative w-full max-w-sm p-6 bg-white rounded-2xl shadow-2xl border border-gray-100 transform transition-all text-center">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-900">QR Code Siswa</h3>
                <button @click="showQrModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Preview Card -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50/60 rounded-xl p-4 border border-blue-100/80 mb-4 space-y-3">
                <div class="flex items-center justify-center space-x-2 border-b border-blue-200/50 pb-2">
                    <img src="{{ asset('images/logo.png?v=' . $globalLogoVersion) }}" alt="Logo" class="w-6 h-6 object-contain bg-white rounded p-0.5">
                    <span class="text-xs font-bold text-blue-900 uppercase">{{ $globalAppName }}</span>
                </div>
                
                <div id="modal-qrcode" class="p-3 bg-white rounded-xl shadow-xs inline-flex items-center justify-center mx-auto border border-gray-200"></div>

                <div>
                    <h4 class="font-bold text-gray-900 text-sm" x-text="qrData.name"></h4>
                    <p class="text-xs font-mono text-blue-800 font-semibold mt-0.5" x-text="'NISN: ' + qrData.nisn"></p>
                    <span class="inline-block px-2.5 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full mt-1.5" x-text="'Kelas: ' + qrData.kelas"></span>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <a :href="'{{ route('admin.master.siswa.cetakKartu') }}?student_id=' + qrData.id" target="_blank" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold py-2.5 px-4 rounded-xl shadow-sm shadow-indigo-600/30 transition-colors flex items-center justify-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span>Cetak Kartu Siswa</span>
                </a>
                <button type="button" @click="showQrModal = false" class="px-4 py-2.5 text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- JS for Parsing & Delete Confirmation -->
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

        function confirmDelete(url, name) {
            Swal.fire({
                title: 'Hapus Siswa?',
                html: `Siswa <b>${name}</b> akan dihapus secara permanen.`,
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

        function confirmBulkDelete() {
            const checked = document.querySelectorAll('.chk-siswa:checked');
            if (checked.length === 0) {
                Swal.fire({
                    title: 'Tidak Ada Data',
                    text: 'Pilih setidaknya satu siswa untuk dihapus.',
                    icon: 'info',
                    confirmButtonColor: '#3b82f6',
                    customClass: { popup: 'rounded-2xl shadow-xl', confirmButton: 'rounded-lg px-4 py-2 font-medium' }
                });
                return;
            }
            Swal.fire({
                title: `Hapus ${checked.length} Siswa?`,
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
@endsection
