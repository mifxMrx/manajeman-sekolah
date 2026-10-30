<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\StudentAttendance;
use App\Models\ViolationCategory;
use App\Models\StudentViolation;
use App\Models\Teacher;

class KesiswaanController extends Controller
{
    // Absensi
    public function absensi(Request $request)
    {
        $classrooms = Classroom::orderBy('grade')->orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();
        $students = [];
        $selectedClassroom = $request->classroom_id;
        $date = $request->date ?? now()->format('Y-m-d');
        
        if ($selectedClassroom) {
            $students = Student::where('classroom_id', $selectedClassroom)->orderBy('name')->get();
        }

        return view('kesiswaan.absensi', compact('classrooms', 'students', 'selectedClassroom', 'date', 'teachers'));
    }

    public function storeAbsensi(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendances' => 'required|array',
        ]);

        foreach ($request->attendances as $studentId => $status) {
            $attendance = StudentAttendance::where('student_id', $studentId)
                            ->where('date', $request->date)
                            ->first();
                            
            $oldStatus = $attendance ? $attendance->status : null;

            StudentAttendance::updateOrCreate(
                ['student_id' => $studentId, 'date' => $request->date],
                ['status' => $status]
            );

            // Jika status baru adalah alpha dan sebelumnya bukan alpha
            if ($status === 'alpha' && $oldStatus !== 'alpha') {
                $student = Student::find($studentId);
                $student->decrement('poin_disiplin', 5);
            }
        }

        return back()->with('success', 'Absensi berhasil disimpan.');
    }

    // Halaman Scan QR Absensi Siswa
    public function scanQr(Request $request)
    {
        $date = $request->date ?? now()->format('Y-m-d');
        $totalStudents = Student::count();
        $teachers = Teacher::orderBy('name')->get();

        // Ambil data absensi pada tanggal tersebut
        $attendances = StudentAttendance::with(['student.classroom'])
            ->where('date', $date)
            ->orderBy('check_in_time', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        $totalHadir = $attendances->where('status', 'hadir')->count();
        $totalSakit = $attendances->where('status', 'sakit')->count();
        $totalIzin = $attendances->where('status', 'izin')->count();
        $totalAlpha = $attendances->where('status', 'alpha')->count();

        return view('kesiswaan.scan_qr', compact(
            'date',
            'totalStudents',
            'attendances',
            'totalHadir',
            'totalSakit',
            'totalIzin',
            'totalAlpha',
            'teachers'
        ));
    }

    // Proses Scan QR / Input NISN
    public function processScanQr(Request $request)
    {
        $request->validate([
            'nisn' => 'required|string',
            'date' => 'nullable|date',
            'status' => 'nullable|in:hadir,sakit,izin,alpha',
        ]);

        $rawNisn = trim($request->nisn);
        $date = $request->date ?: now()->format('Y-m-d');
        $status = $request->status ?: 'hadir';
        $nowTime = now()->format('H:i:s');

        // Cari siswa berdasarkan NISN
        $student = Student::with('classroom')->where('nisn', $rawNisn)->first();

        // Jika tidak ketemu langsung, coba filter angka jika ada prefix
        if (!$student) {
            if (preg_match('/(?:nisn[\"\'\s:]+)?(\d{4,20})/i', $rawNisn, $matches)) {
                $student = Student::with('classroom')->where('nisn', $matches[1])->first();
            }
        }

        if (!$student) {
            return response()->json([
                'success' => false,
                'status' => 'not_found',
                'message' => 'Siswa dengan NISN "' . htmlspecialchars($rawNisn) . '" tidak ditemukan!',
            ], 404);
        }

        $classroomName = $student->classroom 
            ? (($student->classroom->grade && $student->classroom->grade !== '-' ? $student->classroom->grade . ' ' : '') . $student->classroom->name) 
            : '-';

        // Cek absensi siswa di tanggal tersebut
        $attendance = StudentAttendance::where('student_id', $student->id)
            ->where('date', $date)
            ->first();

        if ($attendance) {
            $jamFormatted = $attendance->check_in_time 
                ? date('H:i:s', strtotime($attendance->check_in_time)) 
                : ($attendance->created_at ? $attendance->created_at->format('H:i:s') : $nowTime);

            if ($attendance->status === $status) {
                return response()->json([
                    'success' => false,
                    'status' => 'already_checked',
                    'message' => 'Siswa ' . $student->name . ' (' . $classroomName . ') SUDAH diabsen ' . strtoupper($status) . ' hari ini pada pukul ' . $jamFormatted . '.',
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'nisn' => $student->nisn,
                        'classroom' => $classroomName,
                        'poin_disiplin' => $student->poin_disiplin,
                    ],
                    'attendance' => $attendance,
                    'time' => $jamFormatted,
                ], 200);
            }

            // Jika status lama alpha dan berubah jadi hadir/sakit/izin, kembalikan 5 poin
            if ($attendance->status === 'alpha' && $status !== 'alpha') {
                $student->increment('poin_disiplin', 5);
            } elseif ($attendance->status !== 'alpha' && $status === 'alpha') {
                $student->decrement('poin_disiplin', 5);
            }

            $attendance->update([
                'status' => $status,
                'check_in_time' => $nowTime,
            ]);

            return response()->json([
                'success' => true,
                'status' => 'updated',
                'message' => 'Status absensi ' . $student->name . ' berhasil diperbarui menjadi ' . strtoupper($status) . '.',
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'classroom' => $classroomName,
                    'poin_disiplin' => $student->poin_disiplin,
                ],
                'attendance' => $attendance,
                'time' => $nowTime,
            ]);
        }

        // Simpan absensi baru
        $attendance = StudentAttendance::create([
            'student_id' => $student->id,
            'date' => $date,
            'status' => $status,
            'check_in_time' => $nowTime,
        ]);

        if ($status === 'alpha') {
            $student->decrement('poin_disiplin', 5);
        }

        return response()->json([
            'success' => true,
            'status' => 'created',
            'message' => 'Absensi berhasil! ' . $student->name . ' (' . $classroomName . ') tercatat ' . strtoupper($status) . '.',
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->nisn,
                'classroom' => $classroomName,
                'poin_disiplin' => $student->poin_disiplin,
            ],
            'attendance' => $attendance,
            'time' => $nowTime,
        ]);
    }

    // Batalkan / Hapus Data Absensi
    public function cancelScan($id)
    {
        $attendance = StudentAttendance::with('student')->findOrFail($id);
        if ($attendance->status === 'alpha' && $attendance->student) {
            $attendance->student->increment('poin_disiplin', 5);
        }
        $attendance->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Data absensi berhasil dihapus/dibatalkan.']);
        }

        return back()->with('success', 'Data absensi berhasil dibatalkan.');
    }

    // Pelanggaran
    public function pelanggaran()
    {
        $students = Student::with('classroom')->orderBy('name')->get();
        $categories = ViolationCategory::orderBy('name')->get();
        $allViolations = StudentViolation::with(['student.classroom', 'violationCategory'])->orderBy('date', 'desc')->get();
        $groupedViolations = $allViolations->groupBy('student_id');

        return view('kesiswaan.pelanggaran', compact('students', 'categories', 'groupedViolations'));
    }

    public function storePelanggaran(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'violation_category_id' => 'nullable|exists:violation_categories,id',
            'description' => 'required|string',
            'point_minus' => 'required|integer',
            'date' => 'required|date',
        ]);

        StudentViolation::create($request->all());

        $student = Student::find($request->student_id);
        $student->decrement('poin_disiplin', $request->point_minus);

        return back()->with('success', 'Pelanggaran berhasil dicatat.');
    }

    public function updatePelanggaran(Request $request, $id)
    {
        $violation = StudentViolation::findOrFail($id);
        $request->validate([
            'description' => 'required|string',
            'point_minus' => 'required|integer',
            'date' => 'required|date',
        ]);
        $pointDiff = $request->point_minus - $violation->point_minus;
        $violation->update($request->all());
        if ($pointDiff != 0) {
            $student = Student::find($violation->student_id);
            $student->decrement('poin_disiplin', $pointDiff);
        }
        return back()->with('success', 'Data pelanggaran berhasil diperbarui.');
    }

    public function destroyPelanggaran($id)
    {
        $violation = StudentViolation::findOrFail($id);
        $student = Student::find($violation->student_id);
        $student->increment('poin_disiplin', $violation->point_minus);
        $violation->delete();
        return back()->with('success', 'Data pelanggaran berhasil dihapus.');
    }

    // Skor Disiplin
    public function skor(Request $request)
    {
        $classrooms = Classroom::orderBy('grade')->orderBy('name')->get();
        $selectedClassroom = $request->classroom_id;

        $query = Student::with('classroom');
        if ($selectedClassroom) {
            $query->where('classroom_id', $selectedClassroom);
        }
        
        $students = $query->orderBy('poin_disiplin', 'desc')->get();
        return view('kesiswaan.skor', compact('classrooms', 'students', 'selectedClassroom'));
    }

    public function tambahSkor(Request $request, $id)
    {
        $request->validate([
            'tambahan_skor' => 'required|integer'
        ]);

        $student = Student::findOrFail($id);
        $student->increment('poin_disiplin', $request->tambahan_skor);

        return back()->with('success', 'Skor berhasil ditambahkan.');
    }
}
