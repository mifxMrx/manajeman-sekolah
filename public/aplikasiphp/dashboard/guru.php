<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru_wali') {
    header("Location: ../index.php");
    exit();
}

$guru_id = $_SESSION['user_id'];
$query_guru_info = mysqli_query($conn, "SELECT nama_lengkap, tingkatan FROM users WHERE id = $guru_id");
$guru_data = mysqli_fetch_assoc($query_guru_info);
$nama_guru = $guru_data['nama_lengkap'];
$tingkatan_guru = $guru_data['tingkatan'];

// Cek Pengumuman untuk Guru
$q_ann = mysqli_query($conn, "SELECT * FROM pengumuman WHERE target_role IN ('guru_wali', 'semua') ORDER BY tanggal DESC LIMIT 1");
$ann = mysqli_fetch_assoc($q_ann);
$show_ann = ($ann) ? true : false;

// Ambil info kelas wali (dari data siswa yang diampu)
$query_kelas = "SELECT DISTINCT kelas FROM siswa WHERE id_guru_wali = $guru_id LIMIT 1";
$res_kelas = mysqli_query($conn, $query_kelas);
$data_kelas = mysqli_fetch_assoc($res_kelas);
$kelas_wali = $data_kelas ? $data_kelas['kelas'] : 'Belum Ditentukan';

// Stats
$total_siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa WHERE id_guru_wali = $guru_id"))['total'];
$total_catatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM catatan_bimbingan WHERE id_guru = $guru_id"))['total'];
$perlu_perhatian = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT id_siswa) as total FROM catatan_bimbingan WHERE id_guru = $guru_id AND status = 'Perlu Perhatian'"))['total'];

// List Catatan Bimbingan
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_search = $search ? " AND (s.nama LIKE '%$search%' OR c.catatan LIKE '%$search%')" : "";

$query_catatan = "
    SELECT c.*, s.nama as nama_siswa, s.kelas 
    FROM catatan_bimbingan c
    JOIN siswa s ON c.id_siswa = s.id
    WHERE c.id_guru = $guru_id $where_search
    ORDER BY c.tanggal DESC
";
$result_catatan = mysqli_query($conn, $query_catatan);

// Fetch settings
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1");
$pengaturan = mysqli_fetch_assoc($q_set);

// Fallback if settings not found
if (!$pengaturan) {
    $pengaturan = [
        'nama_aplikasi' => 'WaliCare',
        'nama_sekolah' => 'Sekolah WaliCare',
        'alamat_sekolah' => 'Alamat Sekolah',
        'npsn' => '-'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=980">
    <title>Dashboard Wali Kelas - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/pulltorefreshjs@0.1.22/dist/index.umd.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-bg { background-color: #064e3b; } /* Deep Emerald Green */
        .active-menu { background-color: #065f46; border-left: 4px solid #34d399; }
        .stat-card-icon { transition: transform 0.3s ease; }
        .stat-card:hover .stat-card-icon { transform: scale(1.1); }

        /* Moving Gradient Animation */
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .animate-gradient {
            background-size: 200% 200%;
            animation: gradientMove 6s ease infinite;
        }

        /* Floating Animation */
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        .card-premium {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-premium:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .card-float {
            animation: floating 4s ease-in-out infinite;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <!-- Mobile Header -->
    <div class="lg:hidden fixed top-0 left-0 right-0 h-16 bg-emerald-900 text-white flex items-center justify-between px-6 z-30 shadow-md">
        <div class="flex items-center space-x-3">
            <div class="p-1.5 bg-emerald-500/20 rounded-lg">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </div>
            <h1 class="text-lg font-bold">WaliCare</h1>
        </div>
        <button onclick="toggleSidebar()" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
        </button>
    </div>

    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden transition-opacity duration-300 opacity-0"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-72 sidebar-bg text-white flex flex-col z-50 fixed inset-y-0 left-0 -translate-x-full lg:translate-x-0 lg:static transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
        <div class="h-24 flex items-center px-8 shrink-0">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-emerald-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <h1 class="text-xl font-bold tracking-tight">WaliCare</h1>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden ml-auto p-2 hover:bg-white/10 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <a href="guru.php" class="flex items-center px-4 py-3.5 active-menu rounded-r-xl text-white font-semibold transition-all">
                <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard Wali
            </a>
            <a href="guru_siswa.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-r-xl text-emerald-100 hover:text-white transition-all">
                <svg class="w-5 h-5 mr-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Data Siswa Asuh
            </a>
            <a href="guru_bimbingan.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-r-xl text-emerald-100 hover:text-white transition-all">
                <svg class="w-5 h-5 mr-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Catatan Bimbingan
            </a>
            <a href="guru_laporan.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-r-xl text-emerald-100 hover:text-white transition-all">
                <svg class="w-5 h-5 mr-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Laporan & Ekspor
            </a>
        </nav>

        <div class="p-6 border-t border-white/10 shrink-0">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center font-bold text-white shadow-lg">
                    <?= strtoupper(substr($nama_guru, 0, 2)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold truncate"><?= htmlspecialchars($nama_guru) ?></p>
                    <p class="text-xs text-emerald-300 truncate">Guru Wali Tingkatan <?= $tingkatan_guru ?? '-' ?></p>
                </div>
                <a href="../logout.php" class="text-emerald-300 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f8fafc] pt-16 lg:pt-0">
        <div id="main-scroll-container" class="flex-1 overflow-y-auto p-10">
            <!-- Header -->
            <div class="mb-8">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Dashboard Monitoring Wali</h2>
                <p class="text-sm text-slate-500 mt-1 font-medium">Ringkasan perkembangan dan status siswa asuh yang menjadi tanggung jawab Anda.</p>
            </div>

            <!-- Tingkat Pengawasan Banner -->
            <?php
            $banner_colors = [
                'A' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-100', 'icon' => 'bg-blue-500', 'text_small' => 'text-blue-800', 'text_main' => 'text-blue-900'],
                'B' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-100', 'icon' => 'bg-purple-500', 'text_small' => 'text-purple-800', 'text_main' => 'text-purple-900'],
                'C' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-100', 'icon' => 'bg-orange-500', 'text_small' => 'text-orange-800', 'text_main' => 'text-orange-900'],
            ];
            $style = $banner_colors[$tingkatan_guru] ?? ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-100', 'icon' => 'bg-emerald-500', 'text_small' => 'text-emerald-800', 'text_main' => 'text-emerald-900'];
            ?>
            <div class="<?= $style['bg'] ?> border <?= $style['border'] ?> rounded-2xl p-4 mb-8 flex items-center shadow-sm transition-all duration-500">
                <div class="w-10 h-10 rounded-xl <?= $style['icon'] ?> text-white flex items-center justify-center mr-4 shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold <?= $style['text_small'] ?> uppercase tracking-widest">Tingkat Pengawasan</p>
                    <p class="text-sm font-extrabold <?= $style['text_main'] ?>">Tingkatan <?= htmlspecialchars($tingkatan_guru ?? '-') ?></p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
                <div class="rounded-3xl p-8 shadow-xl flex items-center card-premium card-float bg-gradient-to-br from-blue-600 via-indigo-600 to-indigo-800 animate-gradient text-white border-0" style="animation-delay: 0s;">
                    <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center mr-6 shadow-lg">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-white/70 font-bold uppercase tracking-widest mb-1">Siswa Bimbingan Saya</p>
                        <p class="text-4xl font-black"><?= $total_siswa ?></p>
                    </div>
                </div>

                <div class="rounded-3xl p-8 shadow-xl flex items-center card-premium card-float bg-gradient-to-br from-indigo-500 via-purple-600 to-purple-800 animate-gradient text-white border-0" style="animation-delay: 0.2s;">
                    <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center mr-6 shadow-lg">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-white/70 font-bold uppercase tracking-widest mb-1">Catatan Bimbingan</p>
                        <p class="text-4xl font-black"><?= $total_catatan ?></p>
                    </div>
                </div>

                <div class="rounded-3xl p-8 shadow-xl flex items-center card-premium card-float bg-gradient-to-br from-rose-500 via-orange-600 to-orange-800 animate-gradient text-white border-0" style="animation-delay: 0.4s;">
                    <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center mr-6 shadow-lg">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-white/70 font-bold uppercase tracking-widest mb-1">Perlu Perhatian</p>
                        <p class="text-4xl font-black"><?= $perlu_perhatian ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Lower Grid: Chart & Banner -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                <!-- Chart Card -->
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-lg font-extrabold text-slate-800">Status Perkembangan Siswa</h3>
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-bold text-slate-500 uppercase">Real-time</span>
                        </div>
                    </div>
                    <div class="relative h-64 flex items-center justify-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Welcome/Instruction Card -->
                <div class="bg-slate-900 rounded-[2rem] p-10 relative overflow-hidden flex flex-col justify-center">
                    <div class="absolute top-[-10%] right-[-10%] w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <h3 class="text-2xl font-black text-white mb-4">Optimalkan Bimbingan Anda</h3>
                        <p class="text-slate-400 text-sm leading-relaxed mb-8">
                            Gunakan data grafik di samping untuk memantau siswa yang membutuhkan perhatian lebih secara cepat. 
                            Pastikan setiap pertemuan tercatat dengan baik dalam sistem untuk laporan akhir semester yang akurat.
                        </p>
                        <a href="guru_bimbingan.php" class="inline-flex items-center text-emerald-400 font-bold hover:text-emerald-300 transition-colors">
                            Mulai Catatan Baru
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4-4m0 0l-4-4m4-4H3"></path></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- List Catatan Section -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                    <h3 class="text-lg font-extrabold text-slate-800">Catatan Bimbingan Siswa Asuh</h3>
                    <form action="" method="GET" class="w-full md:w-96 relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nama Siswa..." class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all outline-none">
                        <svg class="w-5 h-5 absolute left-4 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </form>
                </div>

                <div class="space-y-6">
                    <?php if(mysqli_num_rows($result_catatan) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result_catatan)): ?>
                            <div class="bg-white border border-slate-100 rounded-[1.5rem] p-6 hover:shadow-md transition-all">
                                <div class="flex flex-col lg:flex-row gap-6">
                                    <!-- Date and Info Side -->
                                    <div class="flex flex-col items-center lg:w-48 flex-shrink-0">
                                        <div class="flex items-center text-indigo-600 font-bold text-xs mb-3">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <?= date('Y-m-d', strtotime($row['tanggal'])) ?>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="px-3 py-1.5 bg-slate-100 text-slate-600 text-[10px] font-extrabold rounded-full uppercase tracking-tighter"><?= htmlspecialchars($row['tipe_evaluasi']) ?></span>
                                            <span class="px-3 py-1.5 bg-indigo-50 text-indigo-600 text-[10px] font-extrabold rounded-full uppercase tracking-tighter">Pert. <?= $row['pertemuan'] ?></span>
                                        </div>
                                    </div>

                                    <!-- Content Side -->
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start mb-4">
                                            <div>
                                                <h4 class="text-lg font-extrabold text-slate-800"><?= htmlspecialchars($row['nama_siswa']) ?></h4>
                                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-0.5">Kelas: <?= htmlspecialchars($row['kelas']) ?></p>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="flex gap-2 mr-4">
                                                    <button class="p-2 text-indigo-400 hover:bg-indigo-50 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    </button>
                                                    <button class="p-2 text-rose-400 hover:bg-rose-50 rounded-lg transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </div>
                                                <?php if($row['status'] == 'Perlu Perhatian'): ?>
                                                    <span class="px-3 py-1 bg-rose-50 text-rose-600 text-[10px] font-extrabold rounded-lg uppercase border border-rose-100">Perlu Perhatian</span>
                                                <?php else: ?>
                                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-extrabold rounded-lg uppercase border border-emerald-100">Baik</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="bg-slate-50/50 border border-slate-100 rounded-2xl p-5 text-sm text-slate-600 leading-relaxed">
                                            <span class="font-bold text-slate-800">Catatan Tambahan:</span> <?= nl2br(htmlspecialchars($row['catatan'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="py-20 text-center">
                            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <p class="text-slate-500 font-medium">Belum ada catatan bimbingan ditemukan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script>
        // Chart Initialization
        const ctx = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Baik', 'Perlu Perhatian', 'Belum Ada Data'],
                datasets: [{
                    data: [
                        <?= ($total_siswa - $perlu_perhatian) > 0 ? ($total_siswa - $perlu_perhatian) : 0 ?>, 
                        <?= $perlu_perhatian ?>, 
                        <?= $total_siswa == 0 ? 1 : 0 ?>
                    ],
                    backgroundColor: ['#10b981', '#f43f5e', '#e2e8f0'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                family: "'Plus Jakarta Sans'",
                                size: 12,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });
    </script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.add('opacity-100'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.opacity = '0';
                setTimeout(() => {
                    overlay.classList.add('hidden');
                    overlay.classList.remove('opacity-100');
                }, 300);
            }
        }

    <?php if($show_ann): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '<div class="flex items-center justify-center space-x-2 text-amber-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167H3.3a1.598 1.598 0 01-1.558-1.43l-.117-1.303a1.584 1.584 0 011.511-1.727h.044l4.015-.024L10.603 5.2a1.747 1.747 0 012.42 1.391zM14.967 8.22a2.3 2.3 0 010 3.56m1.61 2.158a4.803 4.803 0 010-7.916M18.187 14.719a7.307 7.307 0 010-10.878"></path></svg><span>Pengumuman Penting!</span></div>',
            html: `
                <div class="mt-4 text-left">
                    <h3 class="text-lg font-bold text-slate-800 mb-2"><?= addslashes(htmlspecialchars($ann['judul'])) ?></h3>
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 text-sm text-slate-600 leading-relaxed italic">
                        "<?= addslashes(htmlspecialchars($ann['isi'])) ?>"
                    </div>
                    <p class="mt-4 text-[10px] text-slate-400 text-right">Diterbitkan pada: <?= date('d M Y H:i', strtotime($ann['tanggal'])) ?></p>
                </div>
            `,
            confirmButtonText: 'Saya Mengerti',
            confirmButtonColor: '#f59e0b',
            allowOutsideClick: false
        });
    });
    <?php endif; ?>

    // Pull to Refresh Initialization
    const ptr = PullToRefresh.init({
        mainElement: '#main-scroll-container',
        triggerElement: '#main-scroll-container',
        onRefresh() {
            window.location.reload();
        },
        distMax: 80,
        distThreshold: 60,
        instructionsPullToRefresh: 'Tarik ke bawah untuk menyegarkan',
        instructionsReleaseToRefresh: 'Lepaskan untuk menyegarkan',
        instructionsRefreshing: 'Sedang menyegarkan...',
        iconArrow: '&#8595;',
        iconRefreshing: '<div class="w-4 h-4 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>',
        shouldPullToRefresh() {
            return document.getElementById('main-scroll-container').scrollTop === 0;
        }
    });
    </script>
</body>
</html>
