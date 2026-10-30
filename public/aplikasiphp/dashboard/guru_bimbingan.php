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

// Fetch School Settings
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1");
$pengaturan = mysqli_fetch_assoc($q_set);
if (!$pengaturan) {
    $pengaturan = [
        'nama_aplikasi' => 'WaliCare',
        'nama_sekolah' => 'Sekolah WaliCare',
        'alamat_sekolah' => 'Alamat Sekolah',
        'npsn' => '-'
    ];
}

// Fetch Siswa for dropdown
$query_siswa = "SELECT id, nis, nama, catatan_permanen, potensi_permanen FROM siswa WHERE id_guru_wali = $guru_id ORDER BY nama ASC";
$res_siswa = mysqli_query($conn, $query_siswa);

// Pagination Logic
$limit_options = [5, 10, 25];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_search = $search ? " AND (s.nama LIKE '%$search%' OR c.catatan LIKE '%$search%')" : "";

// Count total records for pagination
$query_count = "
    SELECT COUNT(*) as total 
    FROM catatan_bimbingan c
    JOIN siswa s ON c.id_siswa = s.id
    WHERE c.id_guru = $guru_id $where_search
";
$res_count = mysqli_query($conn, $query_count);
$total_records = mysqli_fetch_assoc($res_count)['total'];
$total_pages = ceil($total_records / $limit);

$query_riwayat = "
    SELECT c.*, s.nama as nama_siswa, s.nis, s.kelas, s.tingkatan, s.catatan_permanen, s.potensi_permanen
    FROM catatan_bimbingan c
    JOIN siswa s ON c.id_siswa = s.id
    WHERE c.id_guru = $guru_id $where_search
    ORDER BY c.tanggal DESC 
    LIMIT $limit OFFSET $offset
";
$result_riwayat = mysqli_query($conn, $query_riwayat);

$options = ['Baik', 'Cukup', 'Kurang'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=980">
    <title>Catatan Bimbingan - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-bg { background-color: #064e3b; }
        .active-menu { background-color: #065f46; border-left: 4px solid #34d399; }
        
        /* Custom styling for inputs */
        .input-premium {
            @apply w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#5e5ce6] focus:bg-white focus:border-transparent outline-none transition-all text-sm text-slate-700;
        }
        .label-premium {
            @apply block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2;
        }
        .section-title {
            @apply text-sm font-extrabold text-[#5e5ce6] uppercase tracking-widest;
        }
        @keyframes highlightForm {
            0% { box-shadow: 0 0 0 0 rgba(94, 92, 230, 0.4); border-color: #5e5ce6; }
            70% { box-shadow: 0 0 0 20px rgba(94, 92, 230, 0); border-color: #e2e8f0; }
            100% { box-shadow: 0 0 0 0 rgba(94, 92, 230, 0); border-color: #f1f5f9; }
        }
        .highlight-active {
            animation: highlightForm 1.5s ease-out;
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
            <h1 class="text-lg font-bold"><?= htmlspecialchars($pengaturan['nama_aplikasi']) ?></h1>
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
                <h1 class="text-xl font-bold tracking-tight"><?= htmlspecialchars($pengaturan['nama_aplikasi']) ?></h1>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden ml-auto p-2 hover:bg-white/10 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
            <a href="guru.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-r-xl text-emerald-100 hover:text-white transition-all">
                <svg class="w-5 h-5 mr-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard Wali
            </a>
            <a href="guru_siswa.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-r-xl text-emerald-100 hover:text-white transition-all">
                <svg class="w-5 h-5 mr-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Data Siswa Asuh
            </a>
            <a href="guru_bimbingan.php" class="flex items-center px-4 py-3.5 active-menu rounded-r-xl text-white font-semibold transition-all">
                <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
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
        <div class="flex-1 overflow-y-auto p-6 md:p-10 space-y-8">
            
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-[22px] font-extrabold text-[#0a1930] tracking-tight">Formulir Evaluasi Siswa</h2>
                <p class="text-[13px] text-slate-500 mt-1 font-medium">Isi rekam jejak evaluasi akademik, karakter, dan pribadi siswa secara komprehensif.</p>
            </div>

            <!-- FORM SECTION -->
            <form id="evaluasiForm" action="guru_bimbingan_action.php" method="POST">
                <div id="formContainer" class="bg-white rounded-[1.25rem] shadow-sm border border-slate-200 p-8 transition-colors duration-300 mb-8">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" id="formId" value="">

                    <!-- Top row: Siswa, Tanggal, Pertemuan -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8">
                        <div class="md:col-span-6">
                            <label class="block text-[13px] font-medium text-slate-600 mb-2">Pilih Siswa Asuh</label>
                            <div class="relative">
                                <select name="id_siswa" id="id_siswa" required class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] focus:border-transparent outline-none text-[13px] text-slate-700 appearance-none cursor-pointer">
                                    <option value="">-- Silakan Pilih Siswa --</option>
                                    <?php while($s = mysqli_fetch_assoc($res_siswa)): ?>
                                        <option value="<?= $s['id'] ?>" 
                                                data-permanen="<?= htmlspecialchars($s['catatan_permanen'] ?? '') ?>"
                                                data-potensi="<?= htmlspecialchars($s['potensi_permanen'] ?? '') ?>">
                                            <?= htmlspecialchars($s['nis'] . ' - ' . $s['nama']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-800">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[13px] font-medium text-slate-600 mb-2">Tanggal Evaluasi</label>
                            <input type="date" name="tanggal" id="tanggal" required class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] focus:border-transparent outline-none text-[13px] text-slate-700" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-[13px] font-medium text-slate-600 mb-2">Pertemuan Ke-</label>
                            <input type="number" name="pertemuan" id="pertemuan" required class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] focus:border-transparent outline-none text-[13px] text-slate-700" min="1" placeholder="Contoh: 1">
                        </div>
                    </div>

                    <!-- Catatan Siswa Permanen (Moved to Top for Tingkatan C) -->
                    <?php if($tingkatan_guru == 'C'): ?>
                    <div class="mb-8">
                        <div class="flex items-center mb-2">
                            <span class="w-2 h-2 bg-rose-500 rounded-full mr-2"></span>
                            <label class="block text-[13px] font-extrabold text-slate-800 uppercase tracking-tight">Catatan Siswa Permanen (Acuan Guru Wali)</label>
                        </div>
                        <textarea name="catatan_permanen" id="catatan_permanen" rows="4" class="w-full px-4 py-3 bg-rose-50/30 border border-rose-100 rounded-lg focus:ring-2 focus:ring-rose-400 outline-none transition-all text-[13px] text-slate-700 font-medium" placeholder="Catatan ini bersifat permanen dan akan menjadi acuan penilaian berikutnya..."></textarea>
                        <p class="text-[11px] text-rose-400 mt-1.5 italic font-medium">* Catatan ini melekat pada siswa dan akan diperbarui setiap kali Anda menyimpannya.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Inner Blue Box for Criteria -->
                    <div class="bg-[#f8faff] border border-[#e5edff] rounded-xl p-6 mb-8">
                        <div class="flex items-center mb-6 pb-4 border-b border-[#e5edff]">
                            <svg class="w-5 h-5 text-[#4338ca] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <h3 class="text-[15px] font-bold text-[#1e3a8a]">Evaluasi Seluruh Kriteria Bimbingan</h3>
                        </div>

                        <?php if($tingkatan_guru == 'A'): ?>
                            <!-- FORM KHUSUS TINGKATAN A -->
                            <div class="mb-8">
                                <span class="inline-block bg-[#e0e7ff] text-[#4338ca] text-[11px] font-bold px-2.5 py-1 rounded-md mb-4 uppercase tracking-wider">Kepribadian</span>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                                    <?php
                                    $kepribadian_fields = [
                                        'karakter_sopan_santun' => 'Sopan Santun',
                                        'pribadi_interaksi' => 'Interaksi',
                                        'pribadi_prilaku' => 'Prilaku'
                                    ];
                                    foreach($kepribadian_fields as $name => $label): ?>
                                        <div>
                                            <label class="block text-[12px] font-medium text-slate-600 mb-1.5"><?= $label ?></label>
                                            <div class="relative">
                                                <select name="<?= $name ?>" id="<?= $name ?>" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] outline-none text-[13px] text-slate-700 cursor-pointer appearance-none">
                                                    <option value="" disabled selected>-- Pilih --</option>
                                                    <?php foreach($options as $opt): ?>
                                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-800">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[13px] font-medium text-slate-600 mb-2">Catatan Potensi Anak (Permanen)</label>
                                    <textarea name="potensi_permanen" id="potensi_permanen" rows="3" class="w-full px-4 py-3 bg-emerald-50/30 border border-emerald-100 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] outline-none transition-all text-[13px] text-slate-700 font-medium" placeholder="Potensi yang dikuasai siswa secara permanen..."></textarea>
                                    <p class="text-[10px] text-emerald-500 mt-1 italic">* Catatan potensi ini bersifat permanen untuk siswa.</p>
                                </div>
                                <div>
                                    <label class="block text-[13px] font-medium text-slate-600 mb-2">Pelatihan Pengembangan Potensi</label>
                                    <div class="relative">
                                        <select name="pelatihan_potensi" id="pelatihan_potensi" required class="w-full px-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] outline-none text-[13px] text-slate-700 cursor-pointer appearance-none">
                                            <option value="" disabled selected>-- Pilih --</option>
                                            <option value="Tidak">Tidak</option>
                                            <option value="Ya">Ya</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-800">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- AKADEMIK -->
                            <div class="mb-8">
                                <span class="inline-block bg-[#e0e7ff] text-[#4338ca] text-[11px] font-bold px-2.5 py-1 rounded-md mb-4 uppercase tracking-wider">Akademik</span>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                                    <?php
                                    $akademik_fields = [
                                        'akademik_membaca' => 'Membaca',
                                        'akademik_menulis' => 'Menulis',
                                        'akademik_berhitung' => 'Menghitung',
                                        'akademik_bacaan_quran' => "Bacaan Qur'an",
                                        'akademik_bacaan_sholat' => 'Bacaan Shalat'
                                    ];
                                    foreach($akademik_fields as $name => $label): ?>
                                        <div>
                                            <label class="block text-[12px] font-medium text-slate-600 mb-1.5"><?= $label ?></label>
                                            <div class="relative">
                                                <select name="<?= $name ?>" id="<?= $name ?>" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] outline-none text-[13px] text-slate-700 cursor-pointer appearance-none">
                                                    <option value="" disabled selected>-- Pilih --</option>
                                                    <?php foreach($options as $opt): ?>
                                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-800">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- KARAKTER -->
                            <div class="mb-8">
                                <span class="inline-block bg-[#e0e7ff] text-[#4338ca] text-[11px] font-bold px-2.5 py-1 rounded-md mb-4 uppercase tracking-wider">Karakter</span>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4">
                                    <?php
                                    $is_custom_level = ($tingkatan_guru == 'B' || $tingkatan_guru == 'C');
                                    $karakter_fields = [
                                        'karakter_sopan_santun' => 'Sopan Santun',
                                        'karakter_kedisiplinan' => $is_custom_level ? 'Penampilan' : 'Kedisiplinan',
                                        'karakter_tanggung_jawab' => $is_custom_level ? 'Interaksi Sosial' : 'Tanggung Jawab',
                                        'karakter_kejujuran' => 'Kejujuran'
                                    ];
                                    foreach($karakter_fields as $name => $label): ?>
                                        <div>
                                            <label class="block text-[12px] font-medium text-slate-600 mb-1.5"><?= $label ?></label>
                                            <div class="relative">
                                                <select name="<?= $name ?>" id="<?= $name ?>" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] outline-none text-[13px] text-slate-700 cursor-pointer appearance-none">
                                                    <option value="" disabled selected>-- Pilih --</option>
                                                    <?php foreach($options as $opt): ?>
                                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-800">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- KELUARGA/PRIBADI -->
                            <?php if($tingkatan_guru != 'B' && $tingkatan_guru != 'C'): ?>
                            <div>
                                <span class="inline-block bg-[#e0e7ff] text-[#4338ca] text-[11px] font-bold px-2.5 py-1 rounded-md mb-4 uppercase tracking-wider">Keluarga/Pribadi</span>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                                    <?php
                                    $pribadi_fields = [
                                        'pribadi_emosional' => 'Kondisi Emosional',
                                        'pribadi_sosial' => 'Interaksi Sosial',
                                        'pribadi_kemandirian' => 'Kemandirian'
                                    ];
                                    foreach($pribadi_fields as $name => $label): ?>
                                        <div>
                                            <label class="block text-[12px] font-medium text-slate-600 mb-1.5"><?= $label ?></label>
                                            <div class="relative">
                                                <select name="<?= $name ?>" id="<?= $name ?>" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] outline-none text-[13px] text-slate-700 cursor-pointer appearance-none">
                                                    <option value="" disabled selected>-- Pilih --</option>
                                                    <?php foreach($options as $opt): ?>
                                                        <option value="<?= $opt ?>"><?= $opt ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-800">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <!-- Textareas (Catatan Tambahan & Tindak Lanjut at Bottom) -->
                    <div class="space-y-6 mb-8 pt-4 border-t border-slate-100">
                        <div>
                            <label class="block text-[13px] font-medium text-slate-700 mb-2">Uraian / Catatan Tambahan Guru Wali</label>
                            <textarea name="catatan" id="catatan" rows="3" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] outline-none transition-all text-[13px] text-slate-700 resize-y" placeholder="Deskripsikan perkembangan atau masalah siswa secara umum..."></textarea>
                        </div>
                        <div>
                            <label class="block text-[13px] font-medium text-slate-700 mb-2">Tindak Lanjut (Follow-up)</label>
                            <input type="text" name="tindak_lanjut" id="tindak_lanjut" class="w-full px-4 py-3 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#5e5ce6] outline-none transition-all text-[13px] text-slate-700" placeholder="Tindakan yang akan dilakukan selanjutnya...">
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end items-center space-x-4 pt-4">
                        <button type="button" onclick="document.getElementById('evaluasiForm').reset(); resetAction();" class="text-[13px] font-bold text-slate-500 hover:text-slate-800 transition-colors bg-transparent border-0 cursor-pointer">
                            Reset Form
                        </button>
                        <button type="submit" id="btnSubmit" class="px-6 py-2.5 bg-[#0f172a] text-white rounded-lg text-[13px] font-bold shadow-md hover:bg-black transition-all cursor-pointer">
                            Simpan Evaluasi
                        </button>
                    </div>
                </div>
            </form>

            <!-- RIWAYAT SECTION -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h2 class="text-lg font-extrabold text-slate-800 tracking-tight">Riwayat Catatan Bimbingan</h2>
                    </div>
                    <form action="" method="GET" class="flex flex-col md:flex-row w-full md:w-auto gap-3">
                        <div class="flex items-center">
                            <select name="limit" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition-all cursor-pointer">
                                <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5 Entri</option>
                                <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 Entri</option>
                                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 Entri</option>
                            </select>
                        </div>
                        <div class="relative w-full md:w-64">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari Nama Siswa..." class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all outline-none">
                            <svg class="w-4 h-4 absolute left-4 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                    <?php if(mysqli_num_rows($result_riwayat) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result_riwayat)): ?>
                            <div class="bg-white border border-slate-100 rounded-[1.5rem] p-6 hover:shadow-md transition-all">
                                <div class="flex flex-col lg:flex-row gap-6">
                                    <!-- Info Side -->
                                    <div class="flex flex-col items-start lg:w-48 flex-shrink-0">
                                        <h4 class="text-sm font-extrabold text-slate-800 mb-1"><?= htmlspecialchars($row['nama_siswa']) ?></h4>
                                        <div class="flex items-center text-indigo-500 font-bold text-[10px] uppercase tracking-wider mb-3">
                                            PERT. <?= $row['pertemuan'] ?> <span class="mx-2 text-slate-300">•</span> <?= date('Y-m-d', strtotime($row['tanggal'])) ?>
                                        </div>
                                        <?php if($row['status'] == 'Perlu Perhatian'): ?>
                                            <span class="px-3 py-1 bg-rose-50 text-rose-600 text-[10px] font-extrabold rounded-lg uppercase border border-rose-100 w-full text-center">Perlu Perhatian</span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-extrabold rounded-lg uppercase border border-emerald-100 w-full text-center">Baik</span>
                                        <?php endif; ?>

                                        <!-- Status Dilihat Ortu -->
                                        <?php if ($row['dilihat_ortu']): ?>
                                            <div class="mt-3 flex items-center text-[9px] font-bold text-emerald-500 uppercase tracking-widest bg-emerald-50/50 p-2 rounded-lg border border-emerald-100/50 w-full">
                                                <svg class="w-3 h-3 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                <span>Dilihat: <?= date('d/m H:i', strtotime($row['dilihat_ortu'])) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="mt-3 flex items-center text-[9px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 p-2 rounded-lg border border-slate-100 w-full">
                                                <svg class="w-3 h-3 mr-1.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                                <span>Belum Dilihat</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Content Side -->
                                    <div class="flex-1">
                                        <div class="bg-slate-50/50 rounded-2xl p-5 text-sm text-slate-600 leading-relaxed mb-3">
                                            <span class="font-bold text-slate-800">Catatan:</span> <?= nl2br(htmlspecialchars($row['catatan'] ?? 'Tidak ada catatan.')) ?>
                                        </div>
                                        <?php if(!empty($row['tindak_lanjut'])): ?>
                                        <div class="bg-indigo-50/50 rounded-2xl p-5 text-sm text-indigo-800 leading-relaxed">
                                            <span class="font-bold text-indigo-900">Tindak Lanjut:</span> <?= nl2br(htmlspecialchars($row['tindak_lanjut'])) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex lg:flex-col gap-2 justify-center">
                                        <button onclick='showReport(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)' class="p-2 text-emerald-500 hover:bg-emerald-50 rounded-lg transition-colors border border-transparent hover:border-emerald-100" title="Lihat Laporan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>
                                        <button onclick='editData(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)' class="p-2 text-indigo-400 hover:bg-indigo-50 rounded-lg transition-colors border border-transparent hover:border-indigo-100" title="Edit Data">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <button onclick="confirmDelete(<?= $row['id'] ?>)" class="p-2 text-rose-400 hover:bg-rose-50 rounded-lg transition-colors border border-transparent hover:border-rose-100" title="Hapus Data">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="py-10 text-center">
                            <p class="text-slate-500 font-medium text-sm">Belum ada riwayat catatan bimbingan.</p>
                        </div>
                    <?php endif; ?>

                    <?php if($total_pages > 0): ?>
                        <div class="px-2 py-4 mt-4 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                            <div class="text-sm text-slate-500 font-medium">
                                Menampilkan <?= min($offset + 1, $total_records) ?> sampai <?= min($offset + $limit, $total_records) ?> dari <?= $total_records ?> data
                            </div>
                            <div class="flex items-center space-x-1">
                                <?php if($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm text-slate-500 hover:text-slate-800 transition-colors font-medium">Previous</a>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 text-sm text-slate-300 cursor-not-allowed font-medium">Previous</span>
                                <?php endif; ?>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm rounded-lg font-medium transition-colors <?= $i == $page ? 'bg-[#0f172a] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm text-slate-500 hover:text-slate-800 transition-colors font-medium">Next</a>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 text-sm text-slate-300 cursor-not-allowed font-medium">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Hidden Form for Delete -->
    <form id="deleteForm" action="guru_bimbingan_action.php" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <!-- Report Modal -->
    <div id="reportModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-4xl overflow-hidden shadow-2xl flex flex-col max-h-[95vh]">
            <div class="p-4 border-b border-slate-100 flex justify-end space-x-2 no-print">
                <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold hover:bg-indigo-700 transition-all flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 002 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2m8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Laporan
                </button>
                <button onclick="closeReport()" class="p-2 bg-slate-100 text-slate-400 hover:text-slate-600 rounded-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-10 bg-white print-content" id="printableArea">
                <!-- Header SMA -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-black text-slate-800 tracking-tighter uppercase"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></h2>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1"><?= htmlspecialchars($pengaturan['alamat_sekolah']) ?></p>
                    <div class="mt-6 border-b-2 border-slate-800 w-full"></div>
                    <div class="mt-1 border-b border-slate-400 w-full"></div>
                </div>

                <div class="text-center mb-10">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">LAPORAN PERKEMBANGAN & BIMBINGAN SISWA</h3>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Tahun Ajaran 2025/2026</p>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-y-2 text-[11px] font-bold text-slate-700 mb-8 px-4">
                    <div class="flex"><span class="w-32 flex-shrink-0">Nama Lengkap</span> <span class="mr-2">:</span> <span id="repNama" class="uppercase"></span></div>
                    <div class="flex"><span class="w-32 flex-shrink-0">Kelas</span> <span class="mr-2">:</span> <span id="repKelas" class="uppercase"></span></div>
                    <div class="flex"><span class="w-32 flex-shrink-0">NIS</span> <span class="mr-2">:</span> <span id="repNis"></span></div>
                    <div class="flex"><span class="w-32 flex-shrink-0">Status Saat Ini</span> <span class="mr-2">:</span> <span id="repStatus" class="uppercase"></span></div>
                </div>

                <!-- Table -->
                <div class="border border-slate-800">
                    <table class="w-full border-collapse text-[10px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-800 font-black text-slate-800 uppercase">
                                <th class="border-r border-slate-800 p-3 w-20 text-center">Tanggal</th>
                                <th class="border-r border-slate-800 p-3 w-20 text-center">Pertemuan</th>
                                <th class="border-r border-slate-800 p-3 text-left">Evaluasi Kriteria Lengkap</th>
                                <th class="border-r border-slate-800 p-3 w-28 text-center">Nilai</th>
                                <th class="p-3 w-64 text-left">Catatan & Tindak Lanjut</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-700" id="repTableBody">
                            <!-- JS injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
    <script>
        const reportModal = document.getElementById('reportModal');
        const repTableBody = document.getElementById('repTableBody');

        function showReport(data) {
            document.getElementById('repNama').innerText = data.nama_siswa;
            document.getElementById('repNis').innerText = data.nis;
            document.getElementById('repKelas').innerText = data.kelas;
            document.getElementById('repStatus').innerText = data.status;

            const categories = [];
            const isTingkatanA = "<?= $tingkatan_guru ?>" === 'A';
            const isCustomLevel = (data.tingkatan || "<?= $tingkatan_guru ?>") === 'B' || (data.tingkatan || "<?= $tingkatan_guru ?>") === 'C';

                if (isTingkatanA) {
                    categories.push({
                        title: 'Kepribadian',
                        fields: [
                            { label: 'Sopan Santun', key: 'karakter_sopan_santun' },
                            { label: 'Interaksi', key: 'pribadi_interaksi' },
                            { label: 'Prilaku', key: 'pribadi_prilaku' }
                        ]
                    });
                } else {
                    categories.push(
                        {
                            title: 'Akademik',
                            fields: [
                                { label: 'Membaca', key: 'akademik_membaca' },
                                { label: 'Menulis', key: 'akademik_menulis' },
                                { label: 'Menghitung', key: 'akademik_berhitung' },
                                { label: 'Bacaan Qur\'an', key: 'akademik_bacaan_quran' },
                                { label: 'Bacaan Shalat', key: 'akademik_bacaan_sholat' }
                            ]
                        },
                        {
                            title: 'Karakter',
                            fields: [
                                { label: 'Sopan Santun', key: 'karakter_sopan_santun' },
                                { label: isCustomLevel ? 'Penampilan' : 'Kedisiplinan', key: 'karakter_kedisiplinan' },
                                { label: isCustomLevel ? 'Interaksi Sosial' : 'Tanggung Jawab', key: 'karakter_tanggung_jawab' },
                                { label: 'Kejujuran', key: 'karakter_kejujuran' }
                            ]
                        }
                    );

                    if (!isCustomLevel) {
                        categories.push({
                            title: 'Keluarga/Pribadi',
                            fields: [
                                { label: 'Kondisi Emosional', key: 'pribadi_emosional' },
                                { label: 'Interaksi Sosial', key: 'pribadi_sosial' },
                                { label: 'Kemandirian', key: 'pribadi_kemandirian' }
                            ]
                        });
                    }
                }

            let html = `
                <tr class="border-b border-slate-800">
                    <td class="border-r border-slate-800 p-3 text-center align-top" rowspan="15">${data.tanggal}</td>
                    <td class="border-r border-slate-800 p-3 text-center align-top" rowspan="15">Ke-${data.pertemuan}</td>
                    <td colspan="2" class="p-0 align-top border-r border-slate-800">
                        <table class="w-full">
            `;

            categories.forEach((cat, idx) => {
                html += `
                    <tr class="bg-slate-50 border-b border-slate-800">
                        <td colspan="2" class="p-2 font-black uppercase text-[9px]">${cat.title}</td>
                    </tr>
                `;
                cat.fields.forEach(f => {
                    html += `
                        <tr class="border-b border-slate-800 last:border-0">
                            <td class="p-2 pl-4 border-r border-slate-800">${f.label}</td>
                            <td class="p-2 w-28 text-center font-bold">${data[f.key]}</td>
                        </tr>
                    `;
                });
            });

            html += `
                        </table>
                    </td>
                    <td class="p-4 align-top leading-relaxed">
                        <?php if($tingkatan_guru == 'A'): ?>
                            <p class="font-black mb-1 uppercase text-slate-800 text-[10px]">Catatan Potensi:</p>
                            <p class="mb-3 text-indigo-700 font-semibold italic">${data.catatan_potensi ?? '-'}</p>
                            <p class="font-black mb-1 uppercase text-slate-800 text-[10px]">Pelatihan Pengembangan:</p>
                            <p class="mb-5 flex items-center">
                                ${data.pelatihan_potensi === 'Ya' ? 
                                    '<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-[10px] font-bold">YA</span>' : 
                                    '<span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded text-[10px] font-bold">TIDAK</span>'}
                            </p>
                        <?php endif; ?>
                        <p class="font-black mb-2 uppercase text-slate-800">Catatan Guru Wali:</p>
                        <p class="mb-6">${data.catatan}</p>
                        <p class="font-black mb-2 uppercase text-slate-800">Follow-up:</p>
                        <p>${data.tindak_lanjut ?? '-'}</p>
                    </td>
                </tr>
            `;

            repTableBody.innerHTML = html;
            reportModal.classList.remove('hidden');
        }

        function closeReport() {
            reportModal.classList.add('hidden');
        }

        function editData(data) {
            // Scroll to form container with smooth animation
            const formContainer = document.getElementById('formContainer');
            formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Trigger highlight animation
            formContainer.classList.remove('highlight-active');
            void formContainer.offsetWidth; // Force reflow
            formContainer.classList.add('highlight-active');
            
            // Set values
            document.querySelector('input[name="action"]').value = 'edit';
            document.getElementById('formId').value = data.id;
            document.getElementById('id_siswa').value = data.id_siswa;
            document.getElementById('tanggal').value = data.tanggal;
            document.getElementById('pertemuan').value = data.pertemuan;
            
            // Set Selects
            const fields = [
                'akademik_membaca', 'akademik_menulis', 'akademik_berhitung', 'akademik_bacaan_quran', 'akademik_bacaan_sholat',
                'karakter_sopan_santun', 'karakter_kedisiplinan', 'karakter_tanggung_jawab', 'karakter_kejujuran',
                'pribadi_emosional', 'pribadi_sosial', 'pribadi_kemandirian', 'pribadi_interaksi', 'pribadi_prilaku'
            ];
            fields.forEach(field => {
                const el = document.getElementById(field);
                if(el && data[field]) el.value = data[field];
            });

            if(document.getElementById('catatan_potensi')) document.getElementById('catatan_potensi').value = data.catatan_potensi || '';
            if(document.getElementById('pelatihan_potensi')) document.getElementById('pelatihan_potensi').value = data.pelatihan_potensi || 'Tidak';

            document.getElementById('catatan').value = data.catatan;
            document.getElementById('tindak_lanjut').value = data.tindak_lanjut;

            if(document.getElementById('catatan_permanen')) {
                document.getElementById('catatan_permanen').value = data.catatan_permanen || '';
            }
            if(document.getElementById('potensi_permanen')) {
                document.getElementById('potensi_permanen').value = data.potensi_permanen || '';
            }
            
            // Change button text
            document.getElementById('btnSubmit').innerText = 'Perbarui Evaluasi';
            document.getElementById('btnSubmit').classList.remove('bg-[#0f172a]', 'hover:bg-black');
            document.getElementById('btnSubmit').classList.add('bg-[#5e5ce6]', 'hover:bg-indigo-600', 'shadow-indigo-500/30');
        }

        function resetAction() {
            document.querySelector('input[name="action"]').value = 'add';
            document.getElementById('formId').value = '';
            document.getElementById('btnSubmit').innerText = 'Simpan Evaluasi';
            document.getElementById('btnSubmit').classList.add('bg-[#0f172a]', 'hover:bg-black');
            document.getElementById('btnSubmit').classList.remove('bg-[#5e5ce6]', 'hover:bg-indigo-600', 'shadow-indigo-500/30');
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Evaluasi?',
                text: "Data evaluasi ini tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-3xl',
                    confirmButton: 'rounded-xl px-6 py-3 font-bold',
                    cancelButton: 'rounded-xl px-6 py-3 font-bold',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteId').value = id;
                    document.getElementById('deleteForm').submit();
                }
            })
        }

        <?php if(isset($_SESSION['swal_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: <?= json_encode($_SESSION['swal_success']) ?>,
                timer: 2500,
                showConfirmButton: false,
                customClass: { popup: 'rounded-3xl' }
            });
            <?php unset($_SESSION['swal_success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['swal_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: <?= json_encode($_SESSION['swal_error']) ?>,
                customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl px-6 py-3' }
            });
            <?php unset($_SESSION['swal_error']); ?>
        <?php endif; ?>
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
        // Catatan Permanen Auto-update
        document.getElementById('id_siswa').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const permanen = selected.getAttribute('data-permanen');
            const potensi = selected.getAttribute('data-potensi');
            
            const targetPermanen = document.getElementById('catatan_permanen');
            if(targetPermanen) targetPermanen.value = permanen || '';
            
            const targetPotensi = document.getElementById('potensi_permanen');
            if(targetPotensi) targetPotensi.value = potensi || '';
        });
    </script>
</body>
</html>
