<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Update last activity
$user_id = $_SESSION['user_id'];
mysqli_query($conn, "UPDATE users SET last_activity = NOW() WHERE id = $user_id");

// Fetch Stats
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'];
$total_guru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'guru_wali'"))['total'];
$total_ortu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'orang_tua'"))['total'];
$total_siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa"))['total'];

// Online Status (Activity within last 5 minutes)
$online_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"))['total'];
$offline_count = $total_users - $online_count;
$online_percent = $total_users > 0 ? round(($online_count / $total_users) * 100) : 0;
$offline_percent = 100 - $online_percent;

// Fetch Data Peringatan: Siswa tanpa Orang Tua
$q_no_parent = mysqli_query($conn, "SELECT id, nis, nama, kelas FROM siswa WHERE id_orang_tua IS NULL OR id_orang_tua = 0 ORDER BY nama ASC");
$count_no_parent = mysqli_num_rows($q_no_parent);

// Fetch Data Peringatan: Orang Tua tanpa Anak
$q_no_child = mysqli_query($conn, "
    SELECT id, nama_lengkap, username 
    FROM users 
    WHERE role = 'orang_tua' 
    AND id NOT IN (SELECT DISTINCT id_orang_tua FROM siswa WHERE id_orang_tua IS NOT NULL)
    ORDER BY nama_lengkap ASC
");
$count_no_child = mysqli_num_rows($q_no_child);

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
    <title>Dashboard Admin - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fafbfc; }
        .sidebar-bg { background-color: #383a79; }
        .active-menu { background-color: #4b4da3; }
        .dark-card { background-color: #252850; }

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
    <div class="lg:hidden fixed top-0 left-0 right-0 h-16 bg-[#383a79] text-white flex items-center justify-between px-6 z-30 shadow-md">
        <div class="flex items-center space-x-3">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <h1 class="text-lg font-bold"><?= htmlspecialchars($pengaturan['nama_aplikasi']) ?></h1>
        </div>
        <button onclick="toggleSidebar()" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
        </button>
    </div>

    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden transition-opacity duration-300 opacity-0"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 sidebar-bg text-white flex flex-col z-50 fixed inset-y-0 left-0 -translate-x-full lg:translate-x-0 lg:static transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
        <div class="h-24 flex items-center px-8 shrink-0">
            <div class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <h1 class="text-xl font-bold tracking-wide"><?= htmlspecialchars($pengaturan['nama_aplikasi']) ?></h1>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden ml-auto p-2 hover:bg-white/10 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
            <a href="admin.php" class="flex items-center px-4 py-3.5 active-menu rounded-xl text-white font-medium shadow-sm">
                <svg class="w-5 h-5 mr-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard Admin
            </a>
            <a href="admin_siswa.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-5 h-5 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Data Siswa (Admin)
            </a>
            
            <div class="pt-2 pb-1 px-4">
                <p class="text-[10px] font-bold text-indigo-300 uppercase tracking-[0.2em]">Kelola Pengguna</p>
            </div>
            <a href="admin_pengguna.php?role=guru_wali" class="flex items-center px-4 py-3 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-4 h-4 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Data Guru
            </a>
            <a href="admin_pengguna.php?role=orang_tua" class="flex items-center px-4 py-3 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-4 h-4 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Data Orang Tua
            </a>
            
            <div class="pt-2 pb-1 px-4">
                <p class="text-[10px] font-bold text-indigo-300 uppercase tracking-[0.2em]">Sistem</p>
            </div>
            <a href="admin_pengaturan.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-4 h-4 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Pengaturan Aplikasi
            </a>
            <a href="admin_backup.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-4 h-4 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Backup & Restore
            </a>
        </nav>
        <div class="p-6 shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold mr-3">
                        AD
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Admin Sistem</p>
                        <p class="text-xs text-indigo-300">Admin</p>
                    </div>
                </div>
                <a href="../logout.php" class="text-indigo-300 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden pt-16 lg:pt-0">
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-10">
            <!-- Header Title -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Dashboard Admin Sistem</h2>
                <p class="text-sm text-gray-500 mt-1">Kelola pengguna dan konfigurasi aplikasi WaliCare.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Stat Card 1 -->
                <div class="rounded-3xl p-6 shadow-xl flex items-center card-premium card-float bg-gradient-to-br from-slate-700 via-slate-800 to-slate-900 animate-gradient text-white border-0" style="animation-delay: 0s;">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center mr-5 shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-white/70 font-bold uppercase tracking-widest mb-1">Total Pengguna</p>
                        <p class="text-3xl font-black"><?= $total_users ?></p>
                    </div>
                </div>
                
                <!-- Stat Card 2 -->
                <div class="rounded-3xl p-6 shadow-xl flex items-center card-premium card-float bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-700 animate-gradient text-white border-0" style="animation-delay: 0.2s;">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center mr-5 shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-white/70 font-bold uppercase tracking-widest mb-1">Total Guru Wali</p>
                        <p class="text-3xl font-black"><?= $total_guru ?></p>
                    </div>
                </div>
                
                <!-- Stat Card 3 -->
                <div class="rounded-3xl p-6 shadow-xl flex items-center card-premium card-float bg-gradient-to-br from-emerald-500 via-emerald-600 to-teal-700 animate-gradient text-white border-0" style="animation-delay: 0.4s;">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center mr-5 shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-white/70 font-bold uppercase tracking-widest mb-1">Total Orang Tua</p>
                        <p class="text-3xl font-black"><?= $total_ortu ?></p>
                    </div>
                </div>
                
                <!-- Stat Card 4 -->
                <div class="rounded-3xl p-6 shadow-xl flex items-center card-premium card-float bg-gradient-to-br from-orange-500 via-orange-600 to-rose-700 animate-gradient text-white border-0" style="animation-delay: 0.6s;">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center mr-5 shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-white/70 font-bold uppercase tracking-widest mb-1">Total Siswa</p>
                        <p class="text-3xl font-black"><?= $total_siswa ?></p>
                    </div>
                </div>
            </div>

            <!-- Lower Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Grafik Status Online -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center mb-8">
                        <svg class="w-4 h-4 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                        Grafik Status Online Pengguna
                        <span class="ml-auto bg-green-50 text-green-500 text-xs px-2 py-1 rounded-md font-semibold flex items-center">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1 animate-pulse"></span> LIVE
                        </span>
                    </h3>
                    
                    <div class="flex items-center">
                        <!-- Circular Progress -->
                        <div class="relative w-32 h-32 mr-10 flex-shrink-0">
                            <!-- SVG for Circular Progress -->
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                <!-- Background Circle -->
                                <path class="text-gray-100" stroke-width="4" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <!-- Progress Circle -->
                                <path class="text-emerald-500" stroke-width="4" stroke-dasharray="<?= $online_percent ?>, 100" stroke="currentColor" fill="none" stroke-linecap="round" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-gray-800"><?= $online_percent ?>%</span>
                                <span class="text-[10px] font-semibold text-gray-400 tracking-wider">ONLINE</span>
                            </div>
                        </div>

                        <!-- Linear Progress Bars -->
                        <div class="flex-1 space-y-6">
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-semibold text-gray-600 flex items-center">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span> Online (<?= $online_count ?>)
                                    </span>
                                    <span class="text-sm text-gray-500"><?= $online_percent ?>%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= $online_percent ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-semibold text-gray-600 flex items-center">
                                        <span class="w-2 h-2 rounded-full bg-gray-300 mr-2"></span> Offline (<?= $offline_count ?>)
                                    </span>
                                    <span class="text-sm text-gray-500"><?= $offline_percent ?>%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-gray-300 h-2 rounded-full" style="width: <?= $offline_percent ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sistem Status Card -->
                <div class="dark-card rounded-2xl shadow-lg p-10 relative overflow-hidden flex flex-col items-center justify-center text-center">
                    <!-- Decorative Background Shield -->
                    <svg class="absolute right-[-20%] bottom-[-20%] w-64 h-64 text-indigo-400 opacity-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    
                    <div class="relative z-10">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-500/20 text-indigo-300 mb-6 border border-indigo-500/30">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">Sistem Berjalan Normal</h3>
                        <p class="text-indigo-200 text-sm max-w-sm mx-auto leading-relaxed opacity-80">
                            Seluruh fungsi manajemen pengguna, pencatatan dan pelaporan beroperasi dengan baik.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Bagian Peringatan Data -->
            <?php if ($count_no_parent > 0): ?>
            <div class="mt-8">
                <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-red-50 bg-red-50/50 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-red-800 uppercase tracking-wider">Peringatan: Siswa Belum Terhubung dengan Orang Tua</h3>
                                <p class="text-xs text-red-500 mt-0.5">Terdapat <?= $count_no_parent ?> siswa yang belum terhubung dengan akun orang tua.</p>
                            </div>
                        </div>
                        <a href="admin_siswa.php" class="text-xs font-bold text-red-600 hover:text-red-700 bg-white border border-red-200 px-4 py-2 rounded-lg transition-all shadow-sm">Lengkapi Data</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 border-b border-gray-100">
                                <tr class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">
                                    <th class="px-8 py-4">NIS</th>
                                    <th class="px-8 py-4">Nama Siswa</th>
                                    <th class="px-8 py-4">Kelas</th>
                                    <th class="px-8 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                <?php while($row = mysqli_fetch_assoc($q_no_parent)): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-8 py-4 font-mono text-xs text-gray-400"><?= htmlspecialchars($row['nis']) ?></td>
                                    <td class="px-8 py-4 font-semibold text-gray-800"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="px-8 py-4 text-gray-500"><?= htmlspecialchars($row['kelas']) ?></td>
                                    <td class="px-8 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                            Belum Terhubung
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="mt-8">
                <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-6 flex items-center">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-emerald-800">Seluruh Data Siswa Lengkap</h3>
                        <p class="text-xs text-emerald-600 mt-0.5">Semua siswa sudah terhubung dengan akun orang tua masing-masing.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bagian Peringatan Orang Tua -->
            <?php if ($count_no_child > 0): ?>
            <div class="mt-8">
                <div class="bg-white rounded-2xl shadow-sm border border-orange-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-orange-50 bg-orange-50/50 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-orange-800 uppercase tracking-wider">Peringatan: Akun Orang Tua Belum Terhubung Anak</h3>
                                <p class="text-xs text-orange-500 mt-0.5">Terdapat <?= $count_no_child ?> akun orang tua yang belum tersambung ke data siswa mana pun.</p>
                            </div>
                        </div>
                        <a href="admin_pengguna.php?role=orang_tua" class="text-xs font-bold text-orange-600 hover:text-orange-700 bg-white border border-orange-200 px-4 py-2 rounded-lg transition-all shadow-sm">Atur Akun</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 border-b border-gray-100">
                                <tr class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">
                                    <th class="px-8 py-4">Username</th>
                                    <th class="px-8 py-4">Nama Lengkap</th>
                                    <th class="px-8 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                <?php while($row = mysqli_fetch_assoc($q_no_child)): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-8 py-4 font-mono text-xs text-gray-400"><?= htmlspecialchars($row['username']) ?></td>
                                    <td class="px-8 py-4 font-semibold text-gray-800"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                    <td class="px-8 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-600">
                                            Belum Ada Anak
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
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
                overlay.classList.remove('opacity-100');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }
    </script>
</body>
</html>
