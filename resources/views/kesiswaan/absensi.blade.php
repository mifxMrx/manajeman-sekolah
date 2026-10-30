@extends('layouts.admin')

@section('header', 'Absensi Siswa')

@section('content')
<div class="space-y-6">
    <!-- Filter Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.kesiswaan.absensi') }}" method="GET" class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Kelas</label>
                <select name="classroom_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach($classrooms as $kelas)
                        <option value="{{ $kelas->id }}" {{ $selectedClassroom == $kelas->id ? 'selected' : '' }}>
                            {{ $kelas->grade }} {{ $kelas->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                <input type="date" name="date" value="{{ $date }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" class="w-full md:w-auto bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary-600/30">
                    Tampilkan
                </button>
            </div>
            <div class="md:ml-auto">
                <a href="{{ route('admin.kesiswaan.scan', ['date' => $date]) }}" class="w-full md:w-auto inline-flex items-center justify-center space-x-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition-all shadow-sm shadow-emerald-600/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    <span>Scan QR Absensi</span>
                </a>
            </div>
        </form>
    </div>

    @if($selectedClassroom && count($students) > 0)
    <!-- Attendance Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="{{ route('admin.kesiswaan.absensi.store') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Kehadiran Siswa</h2>
                <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm font-medium">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-medium w-16">No</th>
                            <th class="px-6 py-4 font-medium">NISN</th>
                            <th class="px-6 py-4 font-medium">Nama Lengkap</th>
                            <th class="px-6 py-4 font-medium">Status Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($students as $index => $siswa)
                        @php
                            $attendance = $siswa->attendances()->where('date', $date)->first();
                            $status = $attendance ? $attendance->status : 'hadir'; // default hadir
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $siswa->nisn }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $siswa->name }}</span>
                                    @if($attendance && $attendance->check_in_time)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-emerald-50 text-emerald-700 border border-emerald-200" title="Jam Scan QR">
                                            <svg class="w-3 h-3 mr-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ date('H:i', strtotime($attendance->check_in_time)) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex space-x-4">
                                    <label class="flex items-center space-x-2 cursor-pointer group">
                                        <input type="radio" name="attendances[{{ $siswa->id }}]" value="hadir" {{ $status == 'hadir' ? 'checked' : '' }} class="text-green-600 focus:ring-green-500 border-gray-300">
                                        <span class="text-gray-700 group-hover:text-green-600 transition-colors">Hadir</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer group">
                                        <input type="radio" name="attendances[{{ $siswa->id }}]" value="sakit" {{ $status == 'sakit' ? 'checked' : '' }} class="text-yellow-500 focus:ring-yellow-500 border-gray-300">
                                        <span class="text-gray-700 group-hover:text-yellow-600 transition-colors">Sakit</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer group">
                                        <input type="radio" name="attendances[{{ $siswa->id }}]" value="izin" {{ $status == 'izin' ? 'checked' : '' }} class="text-blue-500 focus:ring-blue-500 border-gray-300">
                                        <span class="text-gray-700 group-hover:text-blue-600 transition-colors">Izin</span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer group">
                                        <input type="radio" name="attendances[{{ $siswa->id }}]" value="alpha" {{ $status == 'alpha' ? 'checked' : '' }} class="text-red-600 focus:ring-red-500 border-gray-300">
                                        <span class="text-gray-700 group-hover:text-red-600 transition-colors font-medium">Alpha (-5 Poin)</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-6 bg-gray-50 flex justify-end rounded-b-2xl">
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary-600/30">
                    Simpan Kehadiran
                </button>
            </div>
        </form>
    </div>
    @elseif($selectedClassroom)
    <div class="text-center py-10 bg-white rounded-2xl shadow-sm border border-gray-100">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada data siswa</h3>
        <p class="mt-1 text-sm text-gray-500">Kelas ini belum memiliki siswa yang terdaftar.</p>
    </div>
    @endif
</div>
@endsection
