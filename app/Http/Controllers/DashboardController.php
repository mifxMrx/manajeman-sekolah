<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\StudentViolation;
use App\Models\TeacherSchedule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $isFiltered = $startDate && $endDate;

        $today = date('Y-m-d');
        
        $attStart = $startDate ?: $today;
        $attEnd = $endDate ?: $today;

        $violStart = $startDate ?: date('Y-m-01');
        $violEnd = $endDate ?: $today;

        // Dasar
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();

        $dayMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu'
        ];
        $dayNameIndo = $dayMap[date('l', strtotime($today))];

        // Kehadiran Siswa
        $studentAttendancesToday = StudentAttendance::whereBetween('date', [$attStart, $attEnd])->get();
        $studentPresentToday = $studentAttendancesToday->where('status', 'hadir')->count();
        $studentNotPresentCount = $studentAttendancesToday->where('status', '!=', 'hadir')->count();
        $totalStudentAttRecords = $studentAttendancesToday->count();

        $studentAttendancePercent = $totalStudentAttRecords > 0
            ? ($studentPresentToday / $totalStudentAttRecords) * 100
            : ($totalStudents > 0 ? ($studentPresentToday / $totalStudents) * 100 : 0);

        // Kehadiran Guru (berdasarkan record absensi yang benar-benar dicatat)
        $allTeacherAttendances = TeacherAttendance::whereBetween('date', [$attStart, $attEnd])->get();
        $teacherPresentToday = $allTeacherAttendances->where('status', 'hadir')->count();
        $teachersScheduledToday = $allTeacherAttendances->count();

        $teacherAttendancePercent = $teachersScheduledToday > 0
            ? ($teacherPresentToday / $teachersScheduledToday) * 100
            : 0;

        // Pelanggaran
        $violationsThisMonth = StudentViolation::whereBetween('date', [$violStart, $violEnd])->count();

        $studentViolationPercent = $totalStudents > 0
            ? ($violationsThisMonth / $totalStudents) * 100
            : 0;

        // Efektifitas
        $effectiveSchoolPercent = max(
            0,
            min(100, round((($studentAttendancePercent + $teacherAttendancePercent) / 2) - $studentViolationPercent, 1))
        );

        $teacherPresentCount = $teacherPresentToday;

        // Aktivitas Terkini
        $recentViolations = StudentViolation::with('student')
            ->whereBetween('date', [$violStart, $violEnd])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Data Grafik Tren (5 Hari Terakhir)
        $trendData = [];
        for ($i = 4; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $dNameIndo = $dayMap[date('l', strtotime($d))];

            $d_schedules = TeacherSchedule::where('day', $dNameIndo)->get();
            $d_scheduleIds = $d_schedules->pluck('id')->toArray();
            
            $d_teacherAttendances = TeacherAttendance::where('date', $d)
                ->whereIn('teacher_schedule_id', $d_scheduleIds)
                ->get();
            
            $d_teacherAttendanceTotal = $d_schedules->count();
            $d_teacherHadir = $d_teacherAttendances->where('status', 'hadir')->count();
            
            $kurikulumPercent = $d_teacherAttendanceTotal > 0 
                ? round(($d_teacherHadir / $d_teacherAttendanceTotal) * 100) 
                : 0;
            
            $d_studentAttendances = StudentAttendance::where('date', $d)->get();
            $d_studentHadir = $d_studentAttendances->where('status', 'hadir')->count();
            $d_studentAttendancePercent = $totalStudents > 0 
                ? ($d_studentHadir / $totalStudents) * 100 
                : 0;

            $d_violationsToday = StudentViolation::where('date', $d)->count();
            $d_violationPercent = $totalStudents > 0 
                ? ($d_violationsToday / $totalStudents) * 100 
                : 0;
                
            $kesiswaanPercent = max(0, min(100, round($d_studentAttendancePercent - $d_violationPercent)));

            $trendData[] = [
                'day' => $dNameIndo,
                'date' => date('d/m', strtotime($d)),
                'kurikulum' => $kurikulumPercent,
                'kesiswaan' => $kesiswaanPercent
            ];
        }

        return view('dashboard.index', compact(
            'totalStudents', 'totalTeachers',
            'studentPresentToday', 'studentNotPresentCount',
            'teacherPresentToday', 'teacherPresentCount',
            'teachersScheduledToday',
            'violationsThisMonth',
            'effectiveSchoolPercent',
            'studentAttendancePercent', 'studentViolationPercent', 'teacherAttendancePercent',
            'recentViolations',
            'trendData'
        ));
    }
}
