<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Classroom;
use App\Models\TeacherSchedule;
use App\Models\TeacherAttendance;
use App\Models\Holiday;

class KurikulumController extends Controller
{
    public function jadwal()
    {
        $teachers = Teacher::orderBy('name')->get();
        $classrooms = Classroom::orderBy('grade')->orderBy('name')->get();
        $schedules = TeacherSchedule::with(['teacher', 'classroom'])->orderBy('day')->orderBy('start_time')->get();

        $schedulesByClassroom = [];
        foreach ($classrooms as $c) {
            $schedulesByClassroom[$c->id] = [
                'Senin' => [], 'Selasa' => [], 'Rabu' => [], 'Kamis' => [], 'Jumat' => [], 'Sabtu' => [], 'Minggu' => []
            ];
        }

        foreach ($schedules as $sch) {
            $day = $sch->day;
            if (!isset($schedulesByClassroom[$sch->classroom_id][$day])) continue;
            
            $startTime = substr($sch->start_time, 0, 5); // H:i
            $endTime   = substr($sch->end_time, 0, 5);
            $duration  = (strtotime($endTime) - strtotime($startTime)) / 60;
            
            $schedulesByClassroom[$sch->classroom_id][$day][] = [
                'id'         => $sch->id,
                'index'      => count($schedulesByClassroom[$sch->classroom_id][$day]) + 1,
                'start'      => $startTime,
                'end'        => $endTime,
                'duration'   => (int) $duration,
                'teacher_id' => (string) $sch->teacher_id,
                'isEmpty'    => false,
            ];
        }

        $holidays = Holiday::orderBy('date')->get();

        return view('kurikulum.jadwal', compact('teachers', 'classrooms', 'schedules', 'schedulesByClassroom', 'holidays'));
    }


    public function storeJadwal(Request $request)
    {
        $validated = $request->validate([
            'day' => 'required|string|max:20',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'teacher_id' => 'required|exists:teachers,id',
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        // Tolak duplikat bentrok: day + start_time + end_time + teacher_id
        $exists = TeacherSchedule::where('teacher_id', $validated['teacher_id'])
            ->where('day', $validated['day'])
            ->where('start_time', $validated['start_time'])
            ->where('end_time', $validated['end_time'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Jadwal guru bentrok/duplikat pada hari dan waktu yang sama.')->withInput();
        }

        TeacherSchedule::create($validated);

        return redirect()->route('admin.kurikulum.jadwal')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function storeTemplateJadwal(Request $request)
    {
        $request->validate([
            'classrooms' => 'required|array',
            'classrooms.*' => 'exists:classrooms,id',
            'slots' => 'nullable|array'
        ]);

        $classrooms = $request->input('classrooms', []);
        $slotsData = $request->input('slots', []);

        \DB::beginTransaction();
        try {
            // Hapus jadwal lama untuk kelas-kelas yang dipilih
            TeacherSchedule::whereIn('classroom_id', $classrooms)->delete();

            // Simpan slot baru
            foreach ($classrooms as $classroomId) {
                foreach ($slotsData as $day => $daySlots) {
                    if (!is_array($daySlots)) continue;

                    foreach ($daySlots as $slot) {
                        // Jangan simpan jika ditandai kosong atau teacher_id tidak diisi
                        if (!isset($slot['isEmpty']) || $slot['isEmpty'] == true || $slot['isEmpty'] == 'true' || empty($slot['teacher_id'])) {
                            continue;
                        }

                        TeacherSchedule::create([
                            'teacher_id' => $slot['teacher_id'],
                            'classroom_id' => $classroomId,
                            'day' => $day,
                            'start_time' => $slot['start'],
                            'end_time' => $slot['end']
                        ]);
                    }
                }
            }

            \DB::commit();
            return response()->json(['success' => true, 'message' => 'Jadwal template berhasil diterapkan ke kelas yang dipilih.']);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan jadwal.', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateJadwal(Request $request, $id)
    {
        $schedule = TeacherSchedule::findOrFail($id);
        
        $validated = $request->validate([
            'day' => 'required|string|max:20',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'teacher_id' => 'required|exists:teachers,id',
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $exists = TeacherSchedule::where('teacher_id', $validated['teacher_id'])
            ->where('day', $validated['day'])
            ->where('start_time', $validated['start_time'])
            ->where('end_time', $validated['end_time'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Jadwal guru bentrok/duplikat pada hari dan waktu yang sama.')->withInput();
        }

        $schedule->update($validated);

        return back()->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroyJadwal($id)
    {
        TeacherSchedule::findOrFail($id)->delete();
        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function absensi(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');
        // mapping nama hari agar cocok dengan nilai field `teacher_schedules.day`
        // Saat ini controller pakai 2 kemungkinan string umum: Inggris & Indonesia.
        $dayEnglish = date('l', strtotime($date)); // Monday, Tuesday, dst
        $dayIndoMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];
        $dayIndo = $dayIndoMap[$dayEnglish] ?? $dayEnglish;

        // Cek libur nasional
        $holiday = Holiday::where('date', $date)->first();

        // tampilkan hanya jadwal guru untuk hari tsb jika bukan hari libur
        $schedules = collect();
        if (!$holiday) {
            $schedules = TeacherSchedule::with(['teacher', 'classroom'])
                ->whereIn('day', [$dayEnglish, $dayIndo])
                ->orderBy('start_time')
                ->get();
        }

        return view('kurikulum.absensi', compact('schedules', 'date', 'holiday'));
    }

    public function storeAbsensi(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'schedule_id' => 'required|exists:teacher_schedules,id',
        ]);

        $schedule = TeacherSchedule::findOrFail($request->schedule_id);

        TeacherAttendance::updateOrCreate(
            [
                'teacher_id' => $schedule->teacher_id,
                'teacher_schedule_id' => $schedule->id,
                'date' => $request->date,
            ],
            [
                'status' => 'hadir',
                'check_in_time' => now()->format('H:i:s'),
            ]
        );

        return redirect()->route('admin.kurikulum.absensi', ['date' => $request->date])->with('success', 'Guru berhasil dicatat masuk kelas.');
    }

    public function cancelAbsensi(Request $request, $scheduleId)
    {
        $schedule = TeacherSchedule::findOrFail($scheduleId);

        TeacherAttendance::where('teacher_schedule_id', $scheduleId)
            ->where('date', $request->date)
            ->delete();

        return redirect()->route('admin.kurikulum.absensi', ['date' => $request->date])->with('success', 'Kehadiran guru berhasil dibatalkan.');
    }

    public function kinerja(Request $request)
    {
        $month = $request->input('month', date('Y-m')); // Format: Y-m (e.g. 2026-07)
        
        // Parse start and end date of the month
        $startOfMonth = \Carbon\Carbon::parse($month . '-01');
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        
        // Get all schedules
        $schedules = TeacherSchedule::all();
        
        // Generate mapping of dates and their day names in that month
        $daysInMonth = [];
        $tempDate = $startOfMonth->copy();
        $dayIndoMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];
        
        while ($tempDate->lte($endOfMonth)) {
            $engDay = $tempDate->format('l');
            $indoDay = $dayIndoMap[$engDay] ?? $engDay;
            $daysInMonth[$tempDate->format('Y-m-d')] = [$engDay, $indoDay];
            $tempDate->addDay();
        }

        $teachers = Teacher::orderBy('name')->get();
        
        $holidaysInMonth = Holiday::whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])->pluck('date')->toArray();
        
        foreach ($teachers as $teacher) {
            $teacherSchedules = $schedules->where('teacher_id', $teacher->id);
            
            // Count total expected sessions in this month based on schedules
            $totalExpected = 0;
            foreach ($daysInMonth as $dateStr => $days) {
                // If the date is in the future, don't count it as expected yet
                if ($dateStr > date('Y-m-d')) {
                    continue;
                }
                
                // Skip holidays
                if (in_array($dateStr, $holidaysInMonth)) {
                    continue;
                }

                foreach ($teacherSchedules as $sch) {
                    if (in_array($sch->day, $days)) {
                        $totalExpected++;
                    }
                }
            }
            
            // Get actual attendance records in this month
            $attendances = TeacherAttendance::where('teacher_id', $teacher->id)
                ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->get();
                
            $tepatWaktu = 0;
            $terlambat = 0;
            $detailedReport = [];
            
            // Re-parse days of month for detailed report construction
            $tempDate = $startOfMonth->copy();
            while ($tempDate->lte($endOfMonth)) {
                $dateStr = $tempDate->format('Y-m-d');
                if ($dateStr <= date('Y-m-d')) {
                    $engDay = $tempDate->format('l');
                    $indoDay = $dayIndoMap[$engDay] ?? $engDay;
                    
                    $schedulesForDay = $teacherSchedules->filter(function($sch) use ($engDay, $indoDay) {
                        return in_array($sch->day, [$engDay, $indoDay]);
                    });
                    
                    if (in_array($dateStr, $holidaysInMonth) && $schedulesForDay->count() > 0) {
                        $holidayInfo = Holiday::where('date', $dateStr)->first();
                        $detailedReport[] = [
                            'tanggal' => $tempDate->translatedFormat('l, d F Y'),
                            'kelas' => '-',
                            'kehadiran' => 'Libur Nasional',
                            'keterangan' => $holidayInfo->description ?? 'Libur',
                        ];
                    } else {
                        foreach ($schedulesForDay as $sch) {
                            $att = $attendances->where('teacher_schedule_id', $sch->id)
                                ->where('date', $dateStr)
                                ->first();
                                
                            $kehadiran = 'Tidak Hadir';
                            $keterangan = '';
                            
                            if ($att && $att->status == 'hadir') {
                                $kehadiran = 'Hadir';
                                if ($att->check_in_time) {
                                    $jadwalMasuk = \Carbon\Carbon::parse($sch->start_time);
                                    $jamMasuk = \Carbon\Carbon::parse($att->check_in_time);
                                    $selisihMenit = $jadwalMasuk->diffInMinutes($jamMasuk, false);
                                    if ($selisihMenit > 30) {
                                        $terlambat++;
                                        $keterangan = 'Terlambat';
                                    } else {
                                        $tepatWaktu++;
                                        $keterangan = 'Tepat Waktu';
                                    }
                                } else {
                                    $tepatWaktu++;
                                    $keterangan = 'Tepat Waktu';
                                }
                            }
                            
                            $detailedReport[] = [
                                'tanggal' => $tempDate->translatedFormat('l, d F Y'),
                                'kelas' => $sch->classroom->name,
                                'kehadiran' => $kehadiran,
                                'keterangan' => $keterangan,
                            ];
                        }
                    }
                }
                $tempDate->addDay();
            }
            
            $totalHadir = $tepatWaktu + $terlambat;
            $absen = max(0, $totalExpected - $totalHadir);
            
            $teacher->total_expected = $totalExpected;
            $teacher->tepat_waktu = $tepatWaktu;
            $teacher->terlambat = $terlambat;
            $teacher->absen = $absen;
            $teacher->detailed_report = $detailedReport;
            $teacher->kinerja_percentage = $totalExpected > 0 ? round(($totalHadir / $totalExpected) * 100) : 0;
        }

        return view('kurikulum.kinerja', compact('teachers', 'month'));
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'description' => 'required|string|max:255',
        ]);

        Holiday::create($validated);
        return back()->with('success', 'Hari Libur Nasional berhasil ditambahkan.');
    }

    public function destroyHoliday($id)
    {
        Holiday::findOrFail($id)->delete();
        return back()->with('success', 'Hari Libur Nasional berhasil dihapus.');
    }
}
