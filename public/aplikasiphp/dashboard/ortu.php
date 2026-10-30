<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'orang_tua') {
    header("Location: ../index.php");
    exit();
}

$ortu_id = $_SESSION['user_id'];

// Cek Pengumuman untuk Orang Tua
$q_ann = mysqli_query($conn, "SELECT * FROM pengumuman WHERE target_role IN ('orang_tua', 'semua') ORDER BY tanggal DESC LIMIT 1");
$ann = mysqli_fetch_assoc($q_ann);
$show_ann = ($ann) ? true : false;

// Ambil data siswa yang terhubung dengan orang tua ini
$query_siswa = "
    SELECT s.*, u.nama_lengkap as nama_guru, u.no_telp as telp_guru 
    FROM siswa s 
    LEFT JOIN users u ON s.id_guru_wali = u.id 
    WHERE s.id_orang_tua = $ortu_id 
    LIMIT 1
";
$result_siswa = mysqli_query($conn, $query_siswa);
$siswa = mysqli_fetch_assoc($result_siswa);

if (!$siswa) {
    // Jika belum ada siswa yang terhubung
    $siswa_nama = "Belum Terdaftar";
    $siswa_nis = "-";
    $siswa_kelas = "-";
    $siswa_tingkatan = "-";
    $guru_nama = "-";
    $telp_guru = "";
} else {
    $siswa_nama = $siswa['nama'];
    $siswa_nis = $siswa['nis'];
    $siswa_kelas = $siswa['kelas'];
    $siswa_tingkatan = $siswa['tingkatan'];
    $guru_nama = $siswa['nama_guru'] ?? "Belum Ditentukan";
    $telp_guru = $siswa['telp_guru'] ?? "";
    $siswa_id = $siswa['id'];
    $catatan_permanen = $siswa['catatan_permanen'] ?? "";
    $potensi_permanen = $siswa['potensi_permanen'] ?? "";

    // Filter Logic
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $where_filter = "";
    if ($filter == 'week') {
        $where_filter = " AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($filter == 'month') {
        $where_filter = " AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    // Ambil catatan bimbingan terbaru
    $query_catatan = "SELECT * FROM catatan_bimbingan WHERE id_siswa = $siswa_id $where_filter ORDER BY tanggal DESC";
    $result_catatan = mysqli_query($conn, $query_catatan);
    
    // Ambil statistik evaluasi untuk grafik (misal: tren nilai terakhir)
    $query_chart = "SELECT tanggal, status, pertemuan FROM catatan_bimbingan WHERE id_siswa = $siswa_id $where_filter ORDER BY tanggal ASC LIMIT 15";
    $result_chart = mysqli_query($conn, $query_chart);
    $chart_labels = [];
    $chart_data = [];
    $status_counts = ['Baik' => 0, 'Cukup' => 0, 'Kurang' => 0, 'Perlu Perhatian' => 0];
    $status_map = ['Baik' => 3, 'Cukup' => 2, 'Kurang' => 1, 'Perlu Perhatian' => 1];
    
    while($c_row = mysqli_fetch_assoc($result_chart)) {
        $chart_labels[] = 'P-' . $c_row['pertemuan'];
        $chart_data[] = $status_map[$c_row['status']] ?? 3;
        $status_counts[$c_row['status']]++;
    }

    // Helper function untuk menghitung rata-rata kategori
    function getAverageStatus($row, $fields) {
        $map = ['Baik' => 3, 'Cukup' => 2, 'Kurang' => 1];
        $total = 0;
        foreach ($fields as $f) {
            $total += $map[$row[$f]] ?? 3;
        }
        $avg = round($total / count($fields));
        foreach ($map as $status => $val) {
            if ($val == $avg) return $status;
        }
        return 'Baik';
    }

    // Fetch School Settings
    $q_set = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1");
    $settings = mysqli_fetch_assoc($q_set);
    
    // Fallback if settings not found
    if (!$settings) {
        $settings = [
            'nama_sekolah' => 'SMP Darul Mukinin Cibatu',
            'alamat_sekolah' => 'Jl. Taman Pahlawan No. 141
            'npsn' => '20209169
        ];
    }

    // Calculate Global Average Status Pendampingan
    $query_avg = "SELECT status FROM catatan_bimbingan WHERE id_siswa = $siswa_id";
    $result_avg = mysqli_query($conn, $query_avg);
    $total_score = 0;
    $count_meetings = 0;
    while($row_avg = mysqli_fetch_assoc($result_avg)) {
        $total_score += $status_map[$row_avg['status']] ?? 3;
        $count_meetings++;
    }
    $avg_score = $count_meetings > 0 ? round($total_score / $count_meetings) : 3;
    
    // Reverse map to get label
    $status_pendampingan = 'Baik';
    $status_color = 'blue';
    $status_labels = ['Baik' => 3, 'Cukup' => 2, 'Kurang' => 1, 'Perlu Perhatian' => 1];
    foreach ($status_labels as $label => $val) {
        if ($val == $avg_score) {
            $status_pendampingan = $label;
            break;
        }
    }

    if ($status_pendampingan == 'Baik') $status_color = 'blue';
    elseif ($status_pendampingan == 'Cukup') $status_color = 'amber';
    else $status_color = 'rose';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=980">
    <title>Portal Orang Tua - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/pulltorefreshjs@0.1.22/dist/index.umd.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .shadow-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        @media print {
            nav, button, .flex-shrink-0, .filter-container, a { display: none !important; }
            body { background-color: white; }
            .shadow-sm, .shadow-hover { box-shadow: none !important; border: 1px solid #eee !important; }
            .max-h-\[700px\] { max-height: none !important; overflow: visible !important; }
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm border-b border-gray-100 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="../../images/logo.png" alt="Logo Sekolah" class="w-10 h-10 mr-3 object-contain drop-shadow-md">
                        <h1 class="text-xl font-bold text-gray-800 tracking-tight">SMP Darul Mu'minin Cibatu <span class="font-normal text-gray-400 text-sm ml-1">Portal informasi Sekolah</span></h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
                        <p class="text-xs text-blue-600">Orang Tua Wali</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold border border-blue-200">
                        <?= substr($_SESSION['nama_lengkap'], 0, 1) ?>
                    </div>
                    <a href="../logout.php" class="ml-4 p-2 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- School Info Card -->
        <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-100 relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:opacity-[0.05] transition-opacity">
                <svg class="w-32 h-32 text-slate-900" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z"/></svg>
            </div>
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-start space-x-5">
                    <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200 shrink-0">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div class="max-w-xl">
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight leading-tight mb-1 uppercase"><?= htmlspecialchars($settings['nama_sekolah']) ?></h2>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <?= htmlspecialchars($settings['alamat_sekolah']) ?>
                        </p>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 rounded-2xl border border-slate-100 text-center md:text-left">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">NPSN Sekolah</p>
                    <p class="text-lg font-black text-blue-600 tracking-tight"><?= htmlspecialchars($settings['npsn']) ?></p>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-2xl font-bold text-gray-800">Perkembangan Anak Anda</h2>
            <p class="text-gray-500 mt-1">Pantau kemajuan anak anda di aplikasi walicare</p>
        </div>

        <!-- Student Profile Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="h-32 bg-gradient-to-r from-blue-500 to-cyan-500"></div>
            <div class="px-8 pb-8 relative">
                <div class="-mt-16 mb-4 flex justify-between items-end">
                    <div class="w-32 h-32 rounded-full border-4 border-white bg-white overflow-hidden shadow-lg flex items-center justify-center text-5xl">
                        <?= $siswa ? '👦' : '❔' ?>
                    </div>
                    <?php if($siswa && !empty($telp_guru)): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $telp_guru) ?>" target="_blank" class="bg-emerald-50 text-emerald-600 px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-emerald-100 transition-colors shadow-sm flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.94 3.659 1.437 5.634 1.437h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        WhatsApp Wali
                    </a>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 class="text-3xl font-black text-gray-800 tracking-tight"><?= htmlspecialchars($siswa_nama) ?></h3>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-500 font-medium">
                        <span class="flex items-center px-3 py-1.5 bg-gray-50 rounded-lg"><svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg> NIS: <?= htmlspecialchars($siswa_nis) ?></span>
                        <span class="flex items-center px-3 py-1.5 bg-gray-50 rounded-lg"><svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg> Kelas: <?= htmlspecialchars($siswa_kelas) ?></span>
                        <span class="flex items-center px-3 py-1.5 bg-gray-50 rounded-lg"><svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> Wali: <?= htmlspecialchars($guru_nama) ?></span>
                        <span class="flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg font-bold">Tingkatan <?= htmlspecialchars($siswa_tingkatan) ?></span>
                        <span class="flex items-center px-3 py-1.5 bg-<?= $status_color ?>-50 text-<?= $status_color ?>-600 rounded-lg font-bold border border-<?= $status_color ?>-100 shadow-sm animate-pulse">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Status Pendampingan: <?= $status_pendampingan ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Permanent Note Card (Tingkatan C) -->
        <?php if($siswa && $siswa_tingkatan == 'C' && !empty($catatan_permanen)): ?>
        <div class="bg-rose-50 rounded-[2rem] p-8 border border-rose-100 shadow-sm relative overflow-hidden group mb-8">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl group-hover:bg-rose-500/20 transition-all"></div>
            <div class="relative">
                <div class="flex items-center mb-4">
                    <div class="p-2.5 bg-rose-500 rounded-xl mr-4 shadow-lg shadow-rose-200">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-rose-900 tracking-tight leading-none mb-1">Catatan Khusus Guru Wali</h3>
                        <p class="text-[10px] font-bold text-rose-400 uppercase tracking-[0.2em]">Pesan Penting Terkait Perkembangan Anak</p>
                    </div>
                </div>
                <div class="bg-white/60 backdrop-blur-sm p-6 rounded-2xl border border-rose-200/50">
                    <p class="text-slate-700 font-medium leading-relaxed italic">"<?= nl2br(htmlspecialchars($catatan_permanen)) ?>"</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Potential Note Card (Tingkatan A) -->
        <?php if($siswa && $siswa_tingkatan == 'A' && !empty($potensi_permanen)): ?>
        <div class="bg-emerald-50 rounded-[2rem] p-8 border border-emerald-100 shadow-sm relative overflow-hidden group mb-8">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
            <div class="relative">
                <div class="flex items-center mb-4">
                    <div class="p-2.5 bg-emerald-500 rounded-xl mr-4 shadow-lg shadow-emerald-200">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-emerald-900 tracking-tight leading-none mb-1">Potensi & Bakat Anak</h3>
                        <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-[0.2em]">Catatan Potensi yang Dimiliki Siswa</p>
                    </div>
                </div>
                <div class="bg-white/60 backdrop-blur-sm p-6 rounded-2xl border border-emerald-200/50">
                    <p class="text-slate-700 font-medium leading-relaxed italic">"<?= nl2br(htmlspecialchars($potensi_permanen)) ?>"</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Two Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Summary Stats Card -->
            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-gray-100 h-full">
                    <h3 class="text-lg font-black text-gray-800 mb-2 tracking-tight">Tren Perkembangan</h3>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-8 text-center"><?= $filter == 'all' ? '15 Laporan Terakhir' : ($filter == 'week' ? 'Laporan Minggu Ini' : 'Laporan Bulan Ini') ?></p>
                    <div class="relative h-64 flex items-center justify-center mb-6">
                        <canvas id="parentChart"></canvas>
                    </div>
                    <div class="space-y-3">
                    </div>
                </div>
            </div>

            <!-- Detailed History -->
            <div class="lg:col-span-2 bg-white rounded-[2rem] shadow-sm border border-gray-100 p-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 border-b border-gray-50 pb-6 gap-4">
                    <div>
                        <h3 class="text-lg font-black text-gray-800 tracking-tight">Riwayat Penilaian & Catatan</h3>
                        <p class="text-xs text-gray-400 font-bold uppercase mt-1 tracking-widest"><?= mysqli_num_rows($result_catatan) ?> Laporan Tersedia</p>
                    </div>
                    <div class="flex flex-wrap items-center bg-gray-50 p-1.5 rounded-xl border border-gray-100 gap-2">
                        <div class="flex items-center">
                            <a href="?filter=all" class="px-4 py-2 text-[10px] font-black uppercase tracking-wider rounded-lg transition-all <?= $filter == 'all' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600' ?>">Semua</a>
                            <a href="?filter=week" class="px-4 py-2 text-[10px] font-black uppercase tracking-wider rounded-lg transition-all <?= $filter == 'week' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600' ?>">Minggu Ini</a>
                            <a href="?filter=month" class="px-4 py-2 text-[10px] font-black uppercase tracking-wider rounded-lg transition-all <?= $filter == 'month' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-400 hover:text-gray-600' ?>">Bulan Ini</a>
                        </div>
                        <div class="w-px h-4 bg-gray-200 hidden sm:block mx-1"></div>
                        <button onclick="window.print()" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-white hover:shadow-sm rounded-lg transition-all" title="Cetak Laporan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 002 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2m8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        </button>
                    </div>
                </div>
                
                <div class="space-y-6 max-h-[700px] overflow-y-auto pr-4 scroll-smooth custom-scrollbar">
                    <?php if($siswa && mysqli_num_rows($result_catatan) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result_catatan)): ?>
                            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 group hover:border-blue-200 hover:bg-white transition-all shadow-hover">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="md:w-32 flex-shrink-0">
                                        <p class="text-xs font-black text-blue-600 mb-1 uppercase tracking-tighter"><?= date('d M Y', strtotime($row['tanggal'])) ?></p>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase">Pertemuan <?= $row['pertemuan'] ?></p>
                                        <div class="mt-4">
                                            <?php if($row['status'] == 'Perlu Perhatian'): ?>
                                                <span class="inline-block px-3 py-1 bg-rose-50 text-rose-600 text-[10px] font-black rounded-lg uppercase border border-rose-100">Perhatian</span>
                                            <?php else: ?>
                                                <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-lg uppercase border border-emerald-100">Sangat Baik</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 space-y-4">
                                        <div class="bg-white p-5 rounded-2xl text-sm text-gray-600 border border-slate-100 shadow-sm">
                                            <div class="flex items-center mb-2">
                                                <div class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-2"></div>
                                                <p class="font-black text-gray-800 text-[11px] uppercase tracking-wider">Catatan Guru Wali</p>
                                            </div>
                                            <p class="text-slate-600 leading-relaxed italic">"<?= htmlspecialchars($row['catatan'] ?: 'Tidak ada catatan bimbingan.') ?>"</p>
                                        </div>

                                        <div class="bg-blue-50/50 p-5 rounded-2xl text-sm text-gray-600 border border-blue-100/50 shadow-sm">
                                            <div class="flex items-center mb-2">
                                                <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2"></div>
                                                <p class="font-black text-emerald-800 text-[11px] uppercase tracking-wider">Tindak Lanjut (Follow-up)</p>
                                            </div>
                                            <p class="text-slate-600 font-medium"><?= htmlspecialchars($row['tindak_lanjut'] ?: 'Tidak ada instruksi tindak lanjut khusus.') ?></p>
                                        </div>

                                        <div class="flex justify-end pt-2">
                                            <button onclick='showDetail(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)' class="px-6 py-2.5 bg-blue-600 text-white text-[11px] font-black rounded-xl hover:bg-blue-700 transition-all shadow-md shadow-blue-100 flex items-center uppercase tracking-wider">
                                                <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                Lihat Detail Penilaian
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="py-20 text-center flex flex-col items-center">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center text-3xl mb-4 grayscale opacity-50">📋</div>
                            <p class="text-gray-500 font-medium italic">Belum ada catatan penilaian untuk anak Anda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Fixed Footer -->
    <footer class="fixed bottom-0 left-0 right-0 bg-white/80 backdrop-blur-md border-t border-slate-100 py-3 no-print z-40">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col items-center justify-center text-center gap-1">
                <div class="flex items-center justify-center">
                    <div class="w-5 h-5 rounded bg-slate-800 flex items-center justify-center text-white font-bold text-[9px] mr-2">W</div>
                    <p class="text-[11px] text-slate-500 font-medium">&copy; <?= date('Y') ?> <span class="text-slate-800 font-bold">WaliCare</span>. Hak Cipta Dilindungi.</p>
                </div>
                <div class="flex items-center justify-center space-x-3">
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-[0.2em]"><?= htmlspecialchars($settings['nama_sekolah']) ?></p>
                    <div class="w-1 h-1 bg-blue-400 rounded-full animate-pulse"></div>
                    <p class="text-[9px] text-slate-400 font-medium italic">Monitoring Siswa Real-time</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Spacer for fixed footer -->
    <div class="h-20 no-print"></div>

    <!-- Detail Modal -->
    <div id="detailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-2xl overflow-hidden shadow-2xl modal-enter transform transition-all">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center no-print">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight"><?= htmlspecialchars($settings['nama_sekolah']) ?></h4>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest"><?= htmlspecialchars($settings['alamat_sekolah']) ?></p>
                    </div>
                </div>
                <button onclick="closeDetailModal()" class="p-2 bg-slate-100 text-slate-400 hover:text-slate-600 rounded-xl transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="bg-blue-600 p-8 text-white">
                <p id="modalDate" class="text-xs font-bold uppercase tracking-[0.2em] opacity-80 mb-1">TANGGAL EVALUASI</p>
                <h3 class="text-2xl font-black">Detail Penilaian Lengkap</h3>
            </div>
            <div class="p-8 max-h-[70vh] overflow-y-auto">
                <div id="modalContent" class="space-y-8">
                    <!-- Content will be injected here -->
                </div>
            </div>
            <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button onclick="closeDetailModal()" class="px-8 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-50 transition-colors shadow-sm">Tutup</button>
            </div>
        </div>
    </div>
    <script>
        const detailModal = document.getElementById('detailModal');
        const modalContent = document.getElementById('modalContent');
        const modalDate = document.getElementById('modalDate');

        function showDetail(data) {
            // Catat bahwa orang tua sudah melihat (AJAX)
            fetch('ortu_mark_seen.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + data.id
            });

            modalDate.innerText = 'EVALUASI TANGGAL ' + data.tanggal;
            
            const categories = [];
            const isTingkatanA = "<?= $siswa_tingkatan ?>" === 'A';

            if (isTingkatanA) {
                categories.push({
                    title: 'Aspek Kepribadian',
                    icon: '👤',
                    fields: [
                        { label: 'Sopan Santun', key: 'karakter_sopan_santun' },
                        { label: 'Interaksi', key: 'pribadi_interaksi' },
                        { label: 'Prilaku', key: 'pribadi_prilaku' }
                    ]
                });
            } else {
                categories.push(
                    {
                        title: 'Aspek Akademik',
                        icon: '📚',
                        fields: [
                            { label: 'Membaca', key: 'akademik_membaca' },
                            { label: 'Menulis', key: 'akademik_menulis' },
                            { label: 'Berhitung', key: 'akademik_berhitung' },
                            { label: 'Bacaan Quran', key: 'akademik_bacaan_quran' },
                            { label: 'Bacaan Sholat', key: 'akademik_bacaan_sholat' }
                        ]
                    },
                    {
                        title: 'Aspek Karakter',
                        icon: '✨',
                        fields: [
                            { label: 'Sopan Santun', key: 'karakter_sopan_santun' },
                            { label: ("<?= $siswa_tingkatan ?>" === 'B' || "<?= $siswa_tingkatan ?>" === 'C') ? 'Penampilan' : 'Kedisiplinan', key: 'karakter_kedisiplinan' },
                            { label: ("<?= $siswa_tingkatan ?>" === 'B' || "<?= $siswa_tingkatan ?>" === 'C') ? 'Interaksi Sosial' : 'Tanggung Jawab', key: 'karakter_tanggung_jawab' },
                            { label: 'Kejujuran', key: 'karakter_kejujuran' }
                        ]
                    }
                );

                if ("<?= $siswa_tingkatan ?>" !== 'B' && "<?= $siswa_tingkatan ?>" !== 'C') {
                    categories.push({
                        title: 'Aspek Pribadi',
                        icon: '👤',
                        fields: [
                            { label: 'Emosional', key: 'pribadi_emosional' },
                            { label: 'Sosial', key: 'pribadi_sosial' },
                            { label: 'Kemandirian', key: 'pribadi_kemandirian' }
                        ]
                    });
                }
            }

            let html = '';
            categories.forEach(cat => {
                html += `<div>
                            <h4 class="text-sm font-black text-slate-800 flex items-center mb-4 uppercase tracking-wider">
                                <span class="mr-2 text-lg">${cat.icon}</span> ${cat.title}
                            </h4>
                            <div class="grid grid-cols-2 gap-3">`;
                cat.fields.forEach(f => {
                    const val = data[f.key];
                    const colorClass = val === 'Kurang' ? 'text-rose-600 bg-rose-50' : 
                                     (val === 'Cukup' ? 'text-amber-600 bg-amber-50' : 'text-emerald-600 bg-emerald-50');
                    html += `
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex justify-between items-center">
                            <span class="text-[11px] font-bold text-slate-500 uppercase">${f.label}</span>
                            <span class="text-[10px] font-black px-2 py-0.5 rounded-md ${colorClass}">${val}</span>
                        </div>
                    `;
                });
                html += `   </div>
                        </div>`;
            });

            if (isTingkatanA) {
                html += `
                    <div class="p-5 bg-indigo-50 border border-indigo-100 rounded-2xl">
                        <div class="flex items-center mb-3">
                            <span class="text-xl mr-2">🚀</span>
                            <h4 class="text-xs font-black text-indigo-800 uppercase tracking-widest">Catatan Potensi Siswa</h4>
                        </div>
                        <p class="text-sm text-indigo-900 font-bold italic mb-4">"${data.catatan_potensi || '-'}"</p>
                        <div class="flex items-center justify-between pt-3 border-t border-indigo-200">
                            <span class="text-[10px] font-black text-indigo-500 uppercase">Pelatihan Pengembangan</span>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black ${data.pelatihan_potensi === 'Ya' ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500'}">
                                ${data.pelatihan_potensi === 'Ya' ? 'REKOMENDASI: YA' : 'TIDAK'}
                            </span>
                        </div>
                    </div>
                `;
            }

            html += `<div class="p-6 bg-blue-50/50 rounded-2xl border border-blue-100">
                        <h4 class="text-xs font-black text-blue-800 uppercase mb-3 tracking-widest">Catatan & Tindak Lanjut</h4>
                        <p class="text-sm text-slate-600 leading-relaxed mb-4 italic">"${data.catatan}"</p>
                        ${data.tindak_lanjut ? `
                        <div class="pt-4 border-t border-blue-200/50">
                            <span class="text-[10px] font-black text-blue-500 uppercase block mb-1">Rencana Tindak Lanjut</span>
                            <p class="text-xs font-bold text-blue-900">${data.tindak_lanjut}</p>
                        </div>` : ''}
                    </div>`;

            modalContent.innerHTML = html;
            detailModal.classList.remove('hidden');
        }

        function closeDetailModal() {
            detailModal.classList.add('hidden');
        }

        // Chart Initialization
        const ctx = document.getElementById('parentChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Status Perkembangan',
                    data: <?= json_encode($chart_data) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const map = {3: 'Baik', 2: 'Cukup', 1: 'Kurang'};
                                return ' Status: ' + map[context.parsed.y];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 5,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                const map = {3: 'Baik', 2: 'Cukup', 1: 'Kurang'};
                                return map[value] || '';
                            },
                            font: {
                                size: 9,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: true,
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10,
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });

    <?php if($show_ann): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '<div class="flex items-center justify-center space-x-2 text-rose-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167H3.3a1.598 1.598 0 01-1.558-1.43l-.117-1.303a1.584 1.584 0 011.511-1.727h.044l4.015-.024L10.603 5.2a1.747 1.747 0 012.42 1.391zM14.967 8.22a2.3 2.3 0 010 3.56m1.61 2.158a4.803 4.803 0 010-7.916M18.187 14.719a7.307 7.307 0 010-10.878"></path></svg><span>Pesan dari Sekolah</span></div>',
            html: `
                <div class="mt-4 text-left">
                    <h3 class="text-lg font-bold text-slate-800 mb-2"><?= addslashes(htmlspecialchars($ann['judul'])) ?></h3>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-sm text-slate-600 leading-relaxed italic">
                        "<?= addslashes(htmlspecialchars($ann['isi'])) ?>"
                    </div>
                    <p class="mt-4 text-[10px] text-slate-400 text-right">Diterbitkan pada: <?= date('d M Y H:i', strtotime($ann['tanggal'])) ?></p>
                </div>
            `,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#e11d48',
            allowOutsideClick: false
        });
    });
    <?php endif; ?>

    // Pull to Refresh Initialization
    const ptr = PullToRefresh.init({
        mainElement: 'body',
        onRefresh() {
            window.location.reload();
        },
        distMax: 80,
        distThreshold: 60,
        instructionsPullToRefresh: 'Tarik ke bawah untuk menyegarkan',
        instructionsReleaseToRefresh: 'Lepaskan untuk menyegarkan',
        instructionsRefreshing: 'Sedang menyegarkan...',
        iconArrow: '&#8595;',
        iconRefreshing: '<div class="w-4 h-4 border-2 border-rose-500 border-t-transparent rounded-full animate-spin mx-auto"></div>',
        shouldPullToRefresh() {
            return !window.scrollY;
        }
    });
    </script>
</body>
</html>
