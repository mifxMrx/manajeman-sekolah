@extends('layouts.admin')

@section('header', 'Absensi Guru')

@section('content')
<div class="mb-6 flex justify-between items-end">
    <div>
        <h1 class="text-2xl font-semibold text-gray-800">Absensi Kehadiran Guru</h1>
        <p class="text-sm text-gray-500 mt-1">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, j F Y') }}</p>
    </div>
    <a href="{{ route('admin.kurikulum.absensi', ['date' => $date]) }}" class="p-2 bg-white text-gray-600 rounded-lg shadow-sm border border-gray-100 hover:bg-gray-50 hover:text-gray-800 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column (Form and Cards) -->
    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col sm:flex-row justify-between sm:items-center space-y-4 sm:space-y-0">
            <h2 class="text-base font-semibold text-gray-800">Absensi Masuk Kelas</h2>
            <form action="{{ route('admin.kurikulum.absensi') }}" method="GET">
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:border-[#8b5cf6] focus:ring-[#8b5cf6]">
            </form>
        </div>

        <div class="bg-indigo-50/50 border border-indigo-100/50 rounded-xl p-4 flex items-start">
            <svg class="w-4 h-4 text-indigo-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
            <p class="text-sm text-indigo-700">Pilih kelas yang diajar, lalu klik "Masuk Kelas" untuk mencatat kehadiran</p>
        </div>

        @if($holiday)
            <div class="bg-red-50 border-2 border-dashed border-red-200 rounded-xl p-10 text-center flex flex-col items-center justify-center h-[300px]">
                <svg class="w-16 h-16 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <h3 class="text-lg font-bold text-red-600 uppercase tracking-wider">HARI LIBUR NASIONAL</h3>
                <p class="text-sm text-red-500 mt-2 font-medium">{{ $holiday->description }}</p>
                <p class="text-xs text-red-400 mt-1">Tidak ada jadwal yang aktif untuk hari ini.</p>
            </div>
        @else
            @php
                $teachersGrouped = $schedules->groupBy('teacher_id');
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @forelse($teachersGrouped as $teacherId => $teacherSchedules)
                    @php
                        $teacher = $teacherSchedules->first()->teacher;
                        
                        // Count how many classes this teacher has checked in for today
                        $attendedCount = 0;
                        foreach($teacherSchedules as $sch) {
                            if ($sch->attendances()->where('date', $date)->where('status', 'hadir')->exists()) {
                                $attendedCount++;
                            }
                        }
                    @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col justify-between hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-medium text-gray-800 text-sm">{{ $teacher->name }}</h3>
                                <p class="text-xs text-gray-500 mt-1">{{ $teacher->subject ?? 'Mata Pelajaran' }}</p> 
                            </div>
                            <div class="bg-gray-100 text-gray-500 w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold">
                                {{ $attendedCount }}
                            </div>
                        </div>
                        
                        <form action="{{ route('admin.kurikulum.absensi.store') }}" method="POST" class="mt-auto">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date }}">
                            <div class="mb-3">
                                <select name="schedule_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#8b5cf6] focus:ring-[#8b5cf6] appearance-none bg-white bg-no-repeat bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23666%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E')] bg-[length:10px_10px] bg-[position:right_10px_center]">
                                    <option value="" disabled selected>Pilih Kelas</option>
                                    @foreach($teacherSchedules as $sch)
                                        @php
                                            $isAttended = $sch->attendances()->where('date', $date)->where('status', 'hadir')->exists();
                                        @endphp
                                        @if(!$isAttended)
                                            <option value="{{ $sch->id }}">{{ $sch->classroom->name }} ({{ \Carbon\Carbon::parse($sch->start_time)->format('H:i') }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="w-full bg-[#8b5cf6] hover:bg-[#7c3aed] text-white py-2 rounded-lg text-sm font-medium transition-colors flex justify-center items-center shadow-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                                Masuk Kelas
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="col-span-full bg-white p-8 text-center rounded-xl border border-gray-100 shadow-sm">
                        <p class="text-gray-500 text-sm">Belum ada guru yang dijadwalkan pada tanggal ini.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    <!-- Right Column (History) -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-full min-h-[400px]">
            <h3 class="text-base font-semibold text-gray-800 mb-6">Riwayat Hari Ini</h3>
            
            @php
                $attendedSchedules = collect();
                foreach($schedules as $sch) {
                    $att = $sch->attendances()->where('date', $date)->where('status', 'hadir')->first();
                    if ($att) {
                        $sch->attendance_record = $att;
                        $attendedSchedules->push($sch);
                    }
                }
                $attendedSchedules = $attendedSchedules->sortByDesc(function($sch) {
                    return $sch->attendance_record->check_in_time;
                });
            @endphp

            @if($attendedSchedules->isEmpty())
                <div class="flex flex-col items-center justify-center h-64 text-gray-400">
                    <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <p class="text-sm">Belum ada absensi hari ini</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($attendedSchedules as $sch)
                        <div class="border-b border-gray-50 pb-4 last:border-0 last:pb-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $sch->teacher->name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Masuk kelas {{ $sch->classroom->name }}</p>
                                </div>
                                <span class="text-xs text-gray-500 font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($sch->attendance_record->check_in_time)->format('H:i') }}</span>
                            </div>
                            <form action="{{ route('admin.kurikulum.absensi.cancel', $sch->id) }}" method="POST" class="mt-2 flex justify-end">
                                @csrf
                                <button type="submit" onclick="return confirm('Batalkan kehadiran?')" class="text-xs text-red-500 hover:text-red-700 transition-colors font-medium">Batalkan</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
