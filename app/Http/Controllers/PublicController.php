<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Services\GuruWaliService;

class PublicController extends Controller
{
    /**
     * Display the public landing page.
     */
    public function index()
    {
        return view('public.index');
    }

    /**
     * Process the search by NISN.
     */
    public function search(Request $request, GuruWaliService $guruWaliService)
    {
        $keyword = trim($request->input('nisn', ''));

        if (empty($keyword)) {
            return back()->with('error', 'Silakan masukkan NISN atau Nama Siswa.');
        }

        // Cari siswa berdasarkan NISN terlebih dahulu, atau nama siswa
        $student = Student::with(['classroom', 'attendances', 'violations.violationCategory'])
            ->where('nisn', $keyword)
            ->orWhere('name', 'LIKE', '%' . $keyword . '%')
            ->first();

        if (!$student) {
            return redirect()->route('public.index')
                ->with('error', "Data siswa dengan kata kunci \"{$keyword}\" tidak ditemukan. Silakan periksa kembali NISN atau Nama yang dimasukkan.")
                ->withInput();
        }
        
        $student->increment('view_count');

        // Ambil data nilai dan bimbingan dari aplikasi Guru Wali
        $nilaiGuruWali = $guruWaliService->getNilaiByStudent($student);

        return view('public.result', compact('student', 'nilaiGuruWali'));
    }
}
