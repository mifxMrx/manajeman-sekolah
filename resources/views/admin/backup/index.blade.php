@extends('layouts.admin')

@section('header', 'Backup Database')

@section('content')
<div class="space-y-6">
    <!-- Deskripsi Singkat -->
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Backup Database</h2>
        <p class="text-gray-500 mt-1">Kelola backup dan restore database secara mudah.</p>
    </div>

    <!-- Informasi Database -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 font-semibold text-gray-700">
            Informasi Database
        </div>
        <div class="p-0">
            <table class="w-full text-sm text-left">
                <tbody>
                    <tr class="border-b border-gray-100">
                        <td class="px-6 py-4 font-medium text-gray-600 w-1/4">Nama Database</td>
                        <td class="px-6 py-4 text-gray-800">: {{ $dbName }}</td>
                    </tr>
                    <tr class="border-b border-gray-100">
                        <td class="px-6 py-4 font-medium text-gray-600 w-1/4">Ukuran Database</td>
                        <td class="px-6 py-4 text-gray-800">: {{ $dbSize }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-600 w-1/4">Backup Terakhir</td>
                        <td class="px-6 py-4 text-gray-800">: {{ $lastBackup }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tombol Aksi -->
    <div class="flex flex-wrap gap-4">
        <form action="{{ route('admin.backup.create') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="bg-[#10B981] hover:bg-[#059669] text-white px-5 py-2.5 rounded shadow-sm font-medium transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Backup Sekarang
            </button>
        </form>

        @if(count($backups) > 0)
        <a href="{{ route('admin.backup.downloadFile', $backups[0]['name']) }}" class="bg-[#3B82F6] hover:bg-[#2563EB] text-white px-5 py-2.5 rounded shadow-sm font-medium transition-colors flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Download Backup
        </a>
        @else
        <button disabled class="bg-gray-400 text-white px-5 py-2.5 rounded shadow-sm font-medium flex items-center cursor-not-allowed">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Download Backup
        </button>
        @endif

        <button onclick="document.getElementById('restore_file').click()" class="bg-[#F59E0B] hover:bg-[#D97706] text-white px-5 py-2.5 rounded shadow-sm font-medium transition-colors flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            Restore Database
        </button>

        @if(count($backups) > 0)
        <form action="{{ route('admin.backup.destroy', $backups[count($backups)-1]['name']) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus backup lama ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-[#EF4444] hover:bg-[#DC2626] text-white px-5 py-2.5 rounded shadow-sm font-medium transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Hapus Backup Lama
            </button>
        </form>
        @endif
    </div>

    <!-- Riwayat Backup -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 font-semibold text-gray-700">
            Riwayat Backup
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600">
                <thead class="text-xs text-gray-700 bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="px-6 py-4 font-semibold">No</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Nama File</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Ukuran</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Tanggal</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Dibuat Oleh</th>
                        <th scope="col" class="px-6 py-4 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $index => $backup)
                    <tr class="bg-white border-b hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">{{ $backup['name'] }}</td>
                        <td class="px-6 py-4">{{ $backup['size'] }}</td>
                        <td class="px-6 py-4">{{ $backup['date'] }}</td>
                        <td class="px-6 py-4">{{ $backup['creator'] }}</td>
                        <td class="px-6 py-4 flex items-center space-x-2">
                            <a href="{{ route('admin.backup.downloadFile', $backup['name']) }}" class="text-blue-600 hover:text-blue-800 font-medium">Download</a>
                            <span class="text-gray-300">|</span>
                            <form action="{{ route('admin.backup.destroy', $backup['name']) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus backup ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Belum ada file backup.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Restore Database -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 font-semibold text-gray-700">
            Restore Database
        </div>
        <div class="p-6">
            <form action="{{ route('admin.backup.restore') }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row items-start md:items-center gap-4">
                @csrf
                <label for="restore_file" class="font-medium text-gray-700 whitespace-nowrap">Pilih file backup (.sql / .gz)</label>
                
                <input type="file" id="restore_file" name="backup_file" accept=".sql,.gz,.sql.gz,.txt" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 border border-gray-300 rounded focus:outline-none flex-grow">
                
                <button type="submit" class="bg-[#10B981] hover:bg-[#059669] text-white px-6 py-2 rounded shadow-sm font-medium transition-colors whitespace-nowrap" onclick="return confirm('Peringatan: Seluruh data saat ini akan tertimpa! Apakah Anda yakin ingin melakukan restore?')">
                    Restore Database
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
