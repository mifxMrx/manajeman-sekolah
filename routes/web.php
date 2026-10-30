<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KesiswaanController;
use App\Http\Controllers\KurikulumController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PublicController;

Route::get('/', [PublicController::class, 'index'])->name('public.index');
Route::match(['get', 'post'], '/search', [PublicController::class, 'search'])->name('public.search');

Route::get('/dashboard', function () {
    // Redirect based on role
    $role = auth()->user()->role;
    if ($role === 'admin' || $role === 'guru_piket') {
        return redirect()->route('admin.dashboard');
    } elseif ($role === 'guru') {
        return redirect()->route('guru.dashboard');
    }
    return view('dashboard'); // fallback for siswa/default
})->middleware(['auth'])->name('dashboard');

// Admin & Guru Piket Routes
Route::middleware(['auth', 'role:admin,guru_piket'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Kesiswaan
    Route::prefix('kesiswaan')->name('kesiswaan.')->group(function () {
        Route::get('/absensi', [KesiswaanController::class, 'absensi'])->name('absensi');
        Route::post('/absensi', [KesiswaanController::class, 'storeAbsensi'])->name('absensi.store');
        Route::get('/absensi/scan', [KesiswaanController::class, 'scanQr'])->name('scan');
        Route::post('/absensi/scan', [KesiswaanController::class, 'processScanQr'])->name('scan.process');
        Route::delete('/absensi/scan/{id}', [KesiswaanController::class, 'cancelScan'])->name('scan.cancel');
        Route::get('/pelanggaran', [KesiswaanController::class, 'pelanggaran'])->name('pelanggaran');
        Route::post('/pelanggaran', [KesiswaanController::class, 'storePelanggaran'])->name('pelanggaran.store');
        Route::put('/pelanggaran/{id}', [KesiswaanController::class, 'updatePelanggaran'])->name('pelanggaran.update');
        Route::delete('/pelanggaran/{id}', [KesiswaanController::class, 'destroyPelanggaran'])->name('pelanggaran.destroy');
        
        Route::middleware('role:admin')->group(function () {
            Route::get('/skor', [KesiswaanController::class, 'skor'])->name('skor');
            Route::post('/skor/{id}/tambah', [KesiswaanController::class, 'tambahSkor'])->name('skor.tambah');
        });
    });

    // Kurikulum
    Route::prefix('kurikulum')->name('kurikulum.')->group(function () {
        Route::get('/absensi', [KurikulumController::class, 'absensi'])->name('absensi');
        Route::post('/absensi', [KurikulumController::class, 'storeAbsensi'])->name('absensi.store');
        Route::post('/absensi/{scheduleId}/cancel', [KurikulumController::class, 'cancelAbsensi'])->name('absensi.cancel');

        Route::middleware('role:admin')->group(function () {
            Route::get('/jadwal', [KurikulumController::class, 'jadwal'])->name('jadwal');
            Route::post('/jadwal', [KurikulumController::class, 'storeJadwal'])->name('jadwal.store');
            Route::post('/jadwal/template', [KurikulumController::class, 'storeTemplateJadwal'])->name('jadwal.template.store');
            Route::put('/jadwal/{id}', [KurikulumController::class, 'updateJadwal'])->name('jadwal.update');
            Route::delete('/jadwal/{id}', [KurikulumController::class, 'destroyJadwal'])->name('jadwal.destroy');
            Route::get('/kinerja', [KurikulumController::class, 'kinerja'])->name('kinerja');
            
            // Libur Nasional
            Route::post('/libur', [KurikulumController::class, 'storeHoliday'])->name('libur.store');
            Route::delete('/libur/{id}', [KurikulumController::class, 'destroyHoliday'])->name('libur.destroy');
        });
    });

    Route::middleware('role:admin')->group(function () {
        // Leaderboard
        Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        
        // Backup & Restore
        Route::get('/backup', [\App\Http\Controllers\BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup/create', [\App\Http\Controllers\BackupController::class, 'create'])->name('backup.create');
        Route::get('/backup/download/{filename}', [\App\Http\Controllers\BackupController::class, 'downloadFile'])->name('backup.downloadFile');
        Route::delete('/backup/{filename}', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('backup.destroy');
        Route::post('/backup/restore', [\App\Http\Controllers\BackupController::class, 'restore'])->name('backup.restore');

        // Master Data
        Route::prefix('master')->name('master.')->group(function () {
            Route::get('/siswa', [MasterDataController::class, 'siswaIndex'])->name('siswa.index');
            Route::get('/siswa/cetak-kartu', [MasterDataController::class, 'siswaCetakKartu'])->name('siswa.cetakKartu');
            Route::post('/siswa/bulk-delete', [MasterDataController::class, 'siswaBulkDestroy'])->name('siswa.bulkDestroy');
            Route::post('/siswa', [MasterDataController::class, 'siswaStore'])->name('siswa.store');
            Route::put('/siswa/{id}', [MasterDataController::class, 'siswaUpdate'])->name('siswa.update');
            Route::delete('/siswa/{id}', [MasterDataController::class, 'siswaDestroy'])->name('siswa.destroy');
            Route::get('/siswa/template', [MasterDataController::class, 'siswaTemplate'])->name('siswa.template');
            Route::post('/siswa/import', [MasterDataController::class, 'siswaImport'])->name('siswa.import');

            Route::get('/guru', [MasterDataController::class, 'guruIndex'])->name('guru.index');
            Route::post('/guru/bulk-delete', [MasterDataController::class, 'guruBulkDestroy'])->name('guru.bulkDestroy');
            Route::post('/guru', [MasterDataController::class, 'guruStore'])->name('guru.store');
            Route::put('/guru/{id}', [MasterDataController::class, 'guruUpdate'])->name('guru.update');
            Route::delete('/guru/{id}', [MasterDataController::class, 'guruDestroy'])->name('guru.destroy');
            Route::get('/guru/template', [MasterDataController::class, 'guruTemplate'])->name('guru.template');
            Route::post('/guru/import', [MasterDataController::class, 'guruImport'])->name('guru.import');

            Route::get('/kelas', [MasterDataController::class, 'kelasIndex'])->name('kelas.index');
            Route::post('/kelas', [MasterDataController::class, 'kelasStore'])->name('kelas.store');
            Route::put('/kelas/{id}', [MasterDataController::class, 'kelasUpdate'])->name('kelas.update');
            Route::delete('/kelas/{id}', [MasterDataController::class, 'kelasDestroy'])->name('kelas.destroy');

            Route::get('/kunjungan', [MasterDataController::class, 'kunjungan'])->name('kunjungan');
        });
    });
});

// Guru Routes
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php';
