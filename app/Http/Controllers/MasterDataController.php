<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Classroom;

class MasterDataController extends Controller
{
    // SISWA
    public function siswaIndex(Request $request)
    {
        $query = Student::with('classroom')->orderBy('name');
        
        if ($request->filled('kelas_id')) {
            $query->where('classroom_id', $request->kelas_id);
        }

        $students = $query->get();
        $classrooms = Classroom::orderBy('grade')->orderBy('name')->get();
        return view('master.siswa.index', compact('students', 'classrooms'));
    }

    public function siswaBulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (!empty($ids) && is_array($ids)) {
            Student::whereIn('id', $ids)->delete();
            return back()->with('success', count($ids) . ' data siswa berhasil dihapus.');
        }
        return back()->with('error', 'Tidak ada data yang dipilih.');
    }

    public function siswaStore(Request $request)
    {
        $request->validate([
            'nisn' => 'required|unique:students',
            'name' => 'required',
            'kelas_manual' => 'required|string',
        ]);

        $classroom = Classroom::firstOrCreate(
            ['name' => $request->kelas_manual],
            ['grade' => '-']
        );

        Student::create([
            'nisn' => $request->nisn,
            'name' => $request->name,
            'classroom_id' => $classroom->id,
            'poin_disiplin' => 100,
        ]);

        return back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function siswaUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'nisn' => 'required|unique:students,nisn,' . $id,
            'name' => 'required',
            'kelas_manual' => 'required|string',
        ]);

        $classroom = Classroom::firstOrCreate(
            ['name' => $request->kelas_manual],
            ['grade' => '-']
        );

        Student::findOrFail($id)->update([
            'nisn' => $request->nisn,
            'name' => $request->name,
            'classroom_id' => $classroom->id,
        ]);

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function siswaDestroy($id)
    {
        Student::findOrFail($id)->delete();
        return back()->with('success', 'Data siswa berhasil dihapus.');
    }

    public function siswaCetakKartu(Request $request)
    {
        $query = Student::with('classroom')->orderBy('name');
        
        if ($request->filled('kelas_id')) {
            $query->where('classroom_id', $request->kelas_id);
        }
        if ($request->filled('student_id')) {
            $query->where('id', $request->student_id);
        }

        $students = $query->get();
        $classrooms = Classroom::orderBy('grade')->orderBy('name')->get();
        $selectedClassroom = $request->kelas_id ? Classroom::find($request->kelas_id) : null;

        return view('master.siswa.cetak_kartu', compact('students', 'classrooms', 'selectedClassroom'));
    }

    public function siswaTemplate()
    {
        $file = public_path('templates/template_siswa.xlsx');
        return response()->download($file, 'template_siswa.xlsx');
    }

    public function siswaImport(Request $request)
    {
        $data = json_decode($request->input('data'), true);
        if (!$data || !is_array($data)) {
            return back()->withErrors('Data tidak valid atau kosong.');
        }

        foreach ($data as $row) {
            $nisn = $row['NISN'] ?? $row['nisn'] ?? null;
            $name = $row['Nama'] ?? $row['nama'] ?? $row['Nama Lengkap'] ?? null;
            $kelas = $row['Kelas'] ?? $row['kelas'] ?? null;

            if (!$nisn || !$name || !$kelas) continue;

            $classroom = Classroom::firstOrCreate(
                ['name' => $kelas],
                ['grade' => '-']
            );

            Student::updateOrCreate(
                ['nisn' => $nisn],
                [
                    'name' => $name,
                    'classroom_id' => $classroom->id,
                    'poin_disiplin' => 100
                ]
            );
        }

        return back()->with('success', 'Data siswa berhasil diimport dari Excel.');
    }

    // GURU
    public function guruIndex()
    {
        $teachers = Teacher::orderBy('name')->get();
        return view('master.guru.index', compact('teachers'));
    }

    public function guruBulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (!empty($ids) && is_array($ids)) {
            Teacher::whereIn('id', $ids)->delete();
            return back()->with('success', count($ids) . ' data guru berhasil dihapus.');
        }
        return back()->with('error', 'Tidak ada data yang dipilih.');
    }

    public function guruStore(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'nullable|unique:teachers',
            'name' => 'required',
            'subject' => 'nullable',
        ]);

        Teacher::create($validated);

        return back()->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function guruUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'nip' => 'nullable|unique:teachers,nip,' . $id,
            'name' => 'required',
            'subject' => 'nullable',
        ]);

        Teacher::findOrFail($id)->update($validated);

        return back()->with('success', 'Data guru berhasil diubah.');
    }

    public function guruDestroy($id)
    {
        Teacher::findOrFail($id)->delete();
        return back()->with('success', 'Data guru berhasil dihapus.');
    }

    public function guruTemplate()
    {
        $file = public_path('templates/template_guru.xlsx');
        return response()->download($file, 'template_guru.xlsx');
    }

    public function guruImport(Request $request)
    {
        $data = json_decode($request->input('data'), true);
        if (!$data || !is_array($data)) {
            return back()->withErrors('Data tidak valid atau kosong.');
        }

        foreach ($data as $row) {
            $nip = $row['NIP'] ?? $row['nip'] ?? null;
            $name = $row['Nama'] ?? $row['nama'] ?? $row['Nama Lengkap'] ?? null;
            $subject = $row['Mata Pelajaran'] ?? $row['mata pelajaran'] ?? $row['Subject'] ?? null;

            if (!$name) continue;

            Teacher::updateOrCreate(
                ['name' => $name],
                [
                    'nip' => $nip,
                    'subject' => $subject,
                ]
            );
        }

        return back()->with('success', 'Data guru berhasil diimport dari Excel.');
    }

    // KELAS
    public function kelasIndex()
    {
        $classrooms = Classroom::withCount('students')->with('waliKelas')->orderBy('grade')->orderBy('name')->get();
        $teachers = Teacher::orderBy('name')->get();
        return view('master.kelas.index', compact('classrooms', 'teachers'));
    }

    public function kelasStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
        ]);

        $validated['grade'] = '-';
        $validated['teacher_id'] = $request->input('teacher_id') ?: null;

        Classroom::create($validated);

        return back()->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function kelasUpdate(Request $request, $id)
    {
        $request->validate(['teacher_id' => 'nullable|exists:teachers,id']);
        Classroom::findOrFail($id)->update(['teacher_id' => $request->input('teacher_id') ?: null]);
        return back()->with('success', 'Wali kelas berhasil diperbarui.');
    }

    public function kelasDestroy($id)
    {
        Classroom::findOrFail($id)->delete();
        return back()->with('success', 'Data kelas berhasil dihapus.');
    }

    public function kunjungan(Request $request)
    {
        $query = Student::with('classroom')->where('view_count', '>', 0);

        if ($request->filled('kelas_id')) {
            $query->where('classroom_id', $request->kelas_id);
        }

        $students = $query->orderBy('view_count', 'desc')->paginate(20)->withQueryString();
            
        $classrooms = Classroom::orderBy('grade')->orderBy('name')->get();
        return view('admin.master.kunjungan.index', compact('students', 'classrooms'));
    }
}
