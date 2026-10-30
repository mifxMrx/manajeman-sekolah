@extends('layouts.admin')

@section('header', 'Leaderboard Kelas')

@section('content')
<div class="space-y-10">

    <!-- Podium Section -->
    <div class="flex justify-center items-end space-x-2 md:space-x-8 pt-10 pb-6 px-4">
        @if($top3->count() >= 2)
        <!-- Rank 2 -->
        <div class="flex flex-col items-center group">
            <div class="bg-gray-100 border-2 border-gray-300 w-20 h-20 md:w-24 md:h-24 rounded-full flex items-center justify-center mb-4 shadow-lg transform transition-transform group-hover:-translate-y-2 z-10 relative">
                <span class="text-3xl">🥈</span>
                <div class="absolute -bottom-2 bg-gray-800 text-white text-xs px-2 py-0.5 rounded-full font-bold">#2</div>
            </div>
            <div class="bg-gradient-to-t from-gray-200 to-gray-100 w-24 md:w-32 h-32 md:h-40 rounded-t-lg border-t-4 border-gray-400 flex flex-col items-center pt-4 relative overflow-hidden">
                <div class="absolute inset-0 bg-white/40 pattern-diagonal-lines-sm opacity-20"></div>
                <h4 class="font-bold text-gray-800 text-center relative z-10">{{ $top3[1]['kelas'] }}</h4>
                <p class="text-sm font-semibold text-gray-500 relative z-10">{{ $top3[1]['avg_poin'] }} Poin</p>
            </div>
        </div>
        @endif

        @if($top3->count() >= 1)
        <!-- Rank 1 -->
        <div class="flex flex-col items-center group">
            <div class="bg-yellow-100 border-4 border-yellow-400 w-24 h-24 md:w-32 md:h-32 rounded-full flex items-center justify-center mb-4 shadow-xl transform transition-transform group-hover:-translate-y-3 z-10 relative">
                <span class="text-5xl">👑</span>
                <div class="absolute -bottom-3 bg-yellow-500 text-white text-sm px-3 py-1 rounded-full font-bold shadow">#1</div>
            </div>
            <div class="bg-gradient-to-t from-yellow-100 to-yellow-50 w-28 md:w-40 h-48 md:h-56 rounded-t-lg border-t-4 border-yellow-400 flex flex-col items-center pt-6 relative overflow-hidden shadow-[0_0_15px_rgba(250,204,21,0.3)]">
                <div class="absolute inset-0 bg-yellow-400/10 pattern-diagonal-lines-sm opacity-20"></div>
                <h4 class="font-bold text-yellow-900 text-center text-lg relative z-10">{{ $top3[0]['kelas'] }}</h4>
                <p class="text-md font-bold text-yellow-600 relative z-10">{{ $top3[0]['avg_poin'] }} Poin</p>
                <div class="mt-auto pb-4 relative z-10 flex">
                    <span class="animate-bounce">⭐</span>
                    <span class="animate-bounce" style="animation-delay: 100ms">⭐</span>
                    <span class="animate-bounce" style="animation-delay: 200ms">⭐</span>
                </div>
            </div>
        </div>
        @endif

        @if($top3->count() >= 3)
        <!-- Rank 3 -->
        <div class="flex flex-col items-center group">
            <div class="bg-orange-50 border-2 border-orange-300 w-20 h-20 md:w-24 md:h-24 rounded-full flex items-center justify-center mb-4 shadow-lg transform transition-transform group-hover:-translate-y-2 z-10 relative">
                <span class="text-3xl">🥉</span>
                <div class="absolute -bottom-2 bg-orange-700 text-white text-xs px-2 py-0.5 rounded-full font-bold">#3</div>
            </div>
            <div class="bg-gradient-to-t from-orange-100 to-orange-50 w-24 md:w-32 h-24 md:h-32 rounded-t-lg border-t-4 border-orange-400 flex flex-col items-center pt-4 relative overflow-hidden">
                <div class="absolute inset-0 bg-white/40 pattern-diagonal-lines-sm opacity-20"></div>
                <h4 class="font-bold text-gray-800 text-center relative z-10">{{ $top3[2]['kelas'] }}</h4>
                <p class="text-sm font-semibold text-gray-500 relative z-10">{{ $top3[2]['avg_poin'] }} Poin</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Full Leaderboard Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h2 class="text-lg font-semibold text-gray-800">Peringkat Keseluruhan</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium w-16 text-center">Rank</th>
                        <th class="px-6 py-4 font-medium">Kelas</th>
                        <th class="px-6 py-4 font-medium text-center">Rata-rata Disiplin</th>
                        <th class="px-6 py-4 font-medium text-center">Total Pelanggaran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($leaderboard as $index => $row)
                    <tr class="hover:bg-gray-50/50 transition-colors {{ $index < 3 ? 'bg-yellow-50/30' : '' }}">
                        <td class="px-6 py-4 text-sm font-bold text-gray-500 text-center">
                            @if($index == 0) <span class="text-yellow-500 text-lg">1</span>
                            @elseif($index == 1) <span class="text-gray-400 text-lg">2</span>
                            @elseif($index == 2) <span class="text-orange-500 text-lg">3</span>
                            @else {{ $index + 1 }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['kelas'] }}</td>
                        <td class="px-6 py-4 text-sm font-bold text-center {{ $row['avg_poin'] >= 75 ? 'text-green-600' : 'text-red-600' }}">{{ $row['avg_poin'] }}</td>
                        <td class="px-6 py-4 text-sm text-center text-gray-600">{{ $row['total_violations'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-400 text-sm">Data belum tersedia.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
