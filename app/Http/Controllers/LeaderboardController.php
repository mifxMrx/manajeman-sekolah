<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Classroom;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $classrooms = Classroom::with(['students.violations', 'students.attendances'])->get();

        $leaderboard = $classrooms->map(function ($kelas) {
            $totalStudents = $kelas->students->count();
            
            $totalPoin = $kelas->students->sum('poin_disiplin');
            $avgPoin = $totalStudents > 0 ? round($totalPoin / $totalStudents, 1) : 0;

            $totalViolations = $kelas->students->sum(function($student) {
                return $student->violations->count();
            });

            return [
                'kelas' => $kelas->grade . ' ' . $kelas->name,
                'avg_poin' => $avgPoin,
                'total_violations' => $totalViolations,
            ];
        })->sortByDesc('avg_poin')->values();

        $top3 = $leaderboard->take(3);

        return view('leaderboard.index', compact('leaderboard', 'top3'));
    }
}
