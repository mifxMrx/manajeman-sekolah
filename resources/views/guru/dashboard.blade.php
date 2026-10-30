@extends('layouts.guru')

@section('header', 'Dashboard Guru')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 flex flex-col items-center justify-center text-center">
        <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Selamat datang, {{ Auth::user()->name }}!</h2>
        <p class="text-gray-500 max-w-lg">
            Saat ini Anda berada di portal Guru. Hubungi administrator sekolah Anda untuk mendapatkan tugas lebih lanjut atau mengatur jadwal kehadiran Anda.
        </p>
    </div>
</div>
@endsection
