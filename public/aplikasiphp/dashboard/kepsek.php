<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kepsek') {
    header("Location: ../index.php");
    exit();
}

// Fetch stats
$q_guru = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'guru_wali'");
$jml_guru = mysqli_fetch_assoc($q_guru)['total'];

$q_siswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa");
$jml_siswa = mysqli_fetch_assoc($q_siswa)['total'];

// Date filter for Bimbingan
$filter_start = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : date('Y-m-d');
$filter_end = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : date('Y-m-d');

$where_bimbingan = "";
if ($filter_start && $filter_end) {
    $where_bimbingan = " WHERE tanggal BETWEEN '$filter_start' AND '$filter_end'";
} elseif ($filter_start) {
    $where_bimbingan = " WHERE tanggal >= '$filter_start'";
} elseif ($filter_end) {
    $where_bimbingan = " WHERE tanggal <= '$filter_end'";
}

$q_bimbingan = mysqli_query($conn, "SELECT COUNT(DISTINCT id_siswa) as total FROM catatan_bimbingan $where_bimbingan");
$jml_bimbingan = mysqli_fetch_assoc($q_bimbingan)['total'];

// Count Perlu Perhatian
$where_perhatian = $where_bimbingan ? $where_bimbingan . " AND status = 'Perlu Perhatian'" : " WHERE status = 'Perlu Perhatian'";
$q_perhatian = mysqli_query($conn, "SELECT COUNT(DISTINCT id_siswa) as total FROM catatan_bimbingan $where_perhatian");
$jml_perhatian = mysqli_fetch_assoc($q_perhatian)['total'];

// Pagination for Kinerja Guru Wali
$limit_guru = 4;
$pg_guru = isset($_GET['pg_guru']) ? (int)$_GET['pg_guru'] : 1;
if ($pg_guru < 1) $pg_guru = 1;
$offset_guru = ($pg_guru - 1) * $limit_guru;

// Total rows for guru wali
$total_guru_wali = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'guru_wali'"))['total'];
$total_pg_guru = ceil($total_guru_wali / $limit_guru);

// Fetch Kinerja Guru Wali
$where_bimbingan_sub = str_replace(" WHERE ", " AND ", $where_bimbingan);
$q_kinerja = mysqli_query($conn, "
    SELECT u.id, u.nama_lengkap, u.tingkatan, 
    (SELECT COUNT(*) FROM siswa WHERE id_guru_wali = u.id) as total_siswa,
    (SELECT COUNT(DISTINCT id_siswa) FROM catatan_bimbingan cb JOIN siswa s ON cb.id_siswa = s.id WHERE s.id_guru_wali = u.id $where_bimbingan_sub) as jml_bimbingan
    FROM users u 
    WHERE u.role = 'guru_wali' 
    ORDER BY jml_bimbingan DESC 
    LIMIT $limit_guru OFFSET $offset_guru
");

// Students Never Counseled
$q_no_bimbingan = mysqli_query($conn, "
    SELECT s.nama, s.kelas
    FROM siswa s
    WHERE s.id NOT IN (SELECT id_siswa FROM catatan_bimbingan)
    LIMIT 3
");

// Latest Students with Attention Needed
$q_recent_perhatian = mysqli_query($conn, "
    SELECT s.nama, cb.tanggal, cb.catatan
    FROM catatan_bimbingan cb
    JOIN siswa s ON cb.id_siswa = s.id
    WHERE cb.status = 'Perlu Perhatian' $where_bimbingan_sub
    ORDER BY cb.tanggal DESC
    LIMIT 3
");

// Total counts for Detail buttons
$total_no_bimbingan = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM siswa WHERE id NOT IN (SELECT id_siswa FROM catatan_bimbingan)"));
$total_perhatian = $jml_perhatian;

// Fetch Latest Announcement
$q_announcement = mysqli_query($conn, "SELECT * FROM pengumuman ORDER BY tanggal DESC LIMIT 1");
$latest_ann = ($q_announcement && mysqli_num_rows($q_announcement) > 0) ? mysqli_fetch_assoc($q_announcement) : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=980">
    <title>Dashboard Kepala Sekolah - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
    </style>
</head>
<body class="flex w-full h-screen overflow-hidden bg-gradient-to-br from-slate-50 via-slate-50 to-blue-50/50 min-h-screen relative">
    <!-- Decorative Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-200/20 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-200/20 rounded-full blur-[120px]"></div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden w-full relative z-10">
        <!-- Header -->
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm flex items-center justify-between px-8 z-10 sticky top-0 border-b border-slate-100">
            <div class="flex items-center space-x-4">
                <h1 class="text-2xl font-bold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-yellow-500 mr-4">WaliCare</h1>
                <h2 class="text-lg font-bold text-slate-800 border-l-2 border-slate-200 pl-4 hidden sm:block">Dashboard Eksekutif</h2>
            </div>
            <div class="flex items-center space-x-6">
                <button onclick="modalPengumuman()" class="hidden sm:flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-amber-200/50 transition-all active:scale-95 group">
                    <svg class="w-4 h-4 mr-2 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167H3.3a1.598 1.598 0 01-1.558-1.43l-.117-1.303a1.584 1.584 0 011.511-1.727h.044l4.015-.024L10.603 5.2a1.747 1.747 0 012.42 1.391zM14.967 8.22a2.3 2.3 0 010 3.56m1.61 2.158a4.803 4.803 0 010-7.916M18.187 14.719a7.307 7.307 0 010-10.878"></path></svg>
                    Buat Pengumuman
                </button>
                <div class="flex items-center space-x-3">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-slate-900"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></p>
                        <p class="text-xs text-slate-500">Kepala Sekolah</p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-slate-800 flex items-center justify-center text-yellow-400 font-bold border-2 border-yellow-400 shadow-md">
                        <?= substr($_SESSION['nama_lengkap'], 0, 1) ?>
                    </div>
                </div>
                <a href="../logout.php" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors border border-transparent hover:border-rose-100" title="Keluar Sistem">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <!-- Latest Announcement Banner -->
            <?php if($latest_ann): ?>
            <div class="mb-8 bg-gradient-to-r from-amber-500/10 to-transparent border-l-4 border-amber-500 p-4 rounded-r-2xl flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-2 bg-amber-500 text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest">Pengumuman Terakhir (<?= date('d M Y', strtotime($latest_ann['tanggal'])) ?>)</p>
                        <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($latest_ann['judul']) ?></p>
                    </div>
                </div>
                <button onclick="showAnnouncementHistory()" class="px-4 py-1.5 bg-white border border-amber-200 text-amber-600 text-xs font-bold rounded-lg hover:bg-amber-50 transition-colors">Baca Lengkap</button>
            </div>
            <?php endif; ?>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h3 class="text-lg font-bold text-slate-800">Ringkasan Data</h3>
                <form action="" method="GET" class="flex flex-col sm:flex-row gap-3 bg-white p-2 rounded-xl border border-slate-200 shadow-sm">
                    <div class="flex items-center space-x-2 px-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filter Bimbingan:</span>
                    </div>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($filter_start) ?>" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none text-slate-600">
                    <span class="text-slate-400 self-center">-</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($filter_end) ?>" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none text-slate-600">
                    <button type="submit" class="px-4 py-1.5 bg-blue-50 text-blue-600 font-bold rounded-lg text-sm hover:bg-blue-100 transition-colors">Terapkan</button>
                    <?php if($filter_start || $filter_end): ?>
                        <a href="kepsek.php" class="px-4 py-1.5 bg-slate-50 text-slate-500 font-bold rounded-lg text-sm hover:bg-slate-100 transition-colors">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Stat Card 1: Jumlah Siswa -->
                <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-6 shadow-lg shadow-blue-200/50 flex flex-col relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
                    <div class="absolute -right-4 -top-4 w-28 h-28 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-colors"></div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <p class="text-xs text-blue-50 font-bold uppercase tracking-wider">Total Siswa</p>
                    </div>
                    <div class="flex items-end justify-between relative z-10">
                        <p class="text-4xl font-black text-white"><?= number_format($jml_siswa) ?></p>
                        <span class="text-[10px] text-blue-100 bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm">Database Riil</span>
                    </div>
                </div>
                
                <!-- Stat Card 2: Sudah Bimbingan -->
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-3xl p-6 shadow-lg shadow-emerald-200/50 flex flex-col relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
                    <div class="absolute -right-4 -top-4 w-28 h-28 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-colors"></div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-xs text-emerald-50 font-bold uppercase tracking-wider">Sudah Bimbingan</p>
                    </div>
                    <div class="flex items-end justify-between relative z-10">
                        <p class="text-4xl font-black text-white"><?= number_format($jml_bimbingan) ?></p>
                        <span class="text-[10px] text-emerald-100 bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm">Tercatat</span>
                    </div>
                </div>

                <!-- Stat Card 3: Perlu Perhatian -->
                <div class="bg-gradient-to-br from-rose-500 to-orange-600 rounded-3xl p-6 shadow-lg shadow-rose-200/50 flex flex-col relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
                    <div class="absolute -right-4 -top-4 w-28 h-28 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-colors"></div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <p class="text-xs text-rose-50 font-bold uppercase tracking-wider">Perlu Perhatian</p>
                    </div>
                    <div class="flex items-end justify-between relative z-10">
                        <p class="text-4xl font-black text-white"><?= number_format($jml_perhatian) ?></p>
                        <span class="text-[10px] text-rose-100 bg-white/20 px-2 py-1 rounded-md font-bold backdrop-blur-sm">Penting</span>
                    </div>
                </div>

                <!-- Stat Card 4: Guru Wali -->
                <div class="bg-gradient-to-br from-violet-600 to-purple-700 rounded-3xl p-6 shadow-lg shadow-purple-200/50 flex flex-col relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
                    <div class="absolute -right-4 -top-4 w-28 h-28 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-colors"></div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-white/20 rounded-lg backdrop-blur-md">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <p class="text-xs text-purple-50 font-bold uppercase tracking-wider">Guru Wali</p>
                    </div>
                    <div class="flex items-end justify-between relative z-10">
                        <p class="text-4xl font-black text-white"><?= number_format($jml_guru) ?></p>
                        <span class="text-[10px] text-purple-100 bg-white/10 px-2 py-1 rounded-md backdrop-blur-sm">Aktif</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Large Section: Kinerja -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Kinerja Guru Wali</h3>
                        <button class="text-sm font-medium text-blue-600 hover:text-blue-800">Laporan Lengkap &rarr;</button>
                    </div>
                    <div class="space-y-4">
                        <?php if(mysqli_num_rows($q_kinerja) > 0): ?>
                            <?php while($guru = mysqli_fetch_assoc($q_kinerja)): ?>
                                <?php 
                                    $persen = $guru['total_siswa'] > 0 ? round(($guru['jml_bimbingan'] / $guru['total_siswa']) * 100) : 0;
                                    $color_bg = $persen > 80 ? 'bg-green-500' : ($persen > 50 ? 'bg-blue-500' : 'bg-amber-500');
                                    $color_text = $persen > 80 ? 'text-green-600' : ($persen > 50 ? 'text-blue-600' : 'text-amber-600');
                                ?>
                                <div class="flex items-center justify-between p-4 border border-slate-50 rounded-xl hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 rounded-lg bg-slate-800 text-yellow-400 flex items-center justify-center font-bold text-lg mr-4 border border-slate-700">
                                            <?= htmlspecialchars($guru['tingkatan'] ?? '-') ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800"><?= htmlspecialchars($guru['nama_lengkap']) ?></p>
                                            <p class="text-xs text-slate-500">Tingkatan <?= htmlspecialchars($guru['tingkatan'] ?? '-') ?> • <?= $guru['total_siswa'] ?> Siswa</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="flex items-center justify-end mb-1">
                                            <span class="text-xs font-medium text-slate-500 mr-2">Progres Bimbingan</span>
                                            <span class="font-bold <?= $color_text ?>"><?= $persen ?>%</span>
                                        </div>
                                        <div class="w-32 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full <?= $color_bg ?> rounded-full" style="width: <?= $persen ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center py-8 text-slate-400 italic">Belum ada data kinerja guru.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination: Kinerja Guru Wali -->
                    <div class="flex items-center justify-between mt-8 pt-4 border-t border-slate-100">
                        <p class="text-[11px] text-slate-500 font-medium italic">
                            Halaman <span class="text-slate-800 font-bold"><?= $pg_guru ?></span> dari <?= $total_pg_guru ?>
                        </p>
                        <div class="flex space-x-2">
                            <?php if($pg_guru > 1): ?>
                                <a href="?pg_guru=<?= $pg_guru - 1 ?>&start_date=<?= $filter_start ?>&end_date=<?= $filter_end ?>" 
                                   class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors shadow-sm">&larr; Prev</a>
                            <?php endif; ?>
                            
                            <?php if($pg_guru < $total_pg_guru): ?>
                                <a href="?pg_guru=<?= $pg_guru + 1 ?>&start_date=<?= $filter_start ?>&end_date=<?= $filter_end ?>" 
                                   class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-slate-600 hover:bg-slate-50 transition-colors shadow-sm">Next &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Small Section: Status Perhatian & Manajemen Pengumuman -->
                <div class="flex flex-col gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <h3 class="text-lg font-bold text-slate-800 mb-6">Status Perhatian</h3>
                        
                        <div class="space-y-6">
                            <!-- Section: Perlu Perhatian -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex flex-col">
                                        <p class="text-[11px] font-black text-rose-500 uppercase tracking-widest">Butuh Perhatian</p>
                                        <span class="text-[10px] text-slate-400 font-bold">Total: <?= $total_perhatian ?> Siswa</span>
                                    </div>
                                    <button onclick="showDetail('perlu_perhatian')" class="text-[10px] bg-rose-50 text-rose-600 px-2 py-1 rounded-lg font-bold hover:bg-rose-100 transition-colors border border-rose-100">Lihat Detail</button>
                                </div>
                                <div class="space-y-3">
                                    <?php if(mysqli_num_rows($q_recent_perhatian) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($q_recent_perhatian)): ?>
                                            <div class="p-3 bg-rose-50/50 rounded-xl border border-rose-100 group hover:bg-rose-50 transition-colors">
                                                <div class="flex justify-between items-start mb-1">
                                                    <p class="text-xs font-bold text-slate-800"><?= htmlspecialchars($row['nama']) ?></p>
                                                    <span class="text-[9px] text-slate-400"><?= date('d/m', strtotime($row['tanggal'])) ?></span>
                                                </div>
                                                <p class="text-[10px] text-slate-600 line-clamp-2"><?= htmlspecialchars($row['catatan'] ?: 'Tidak ada catatan detail.') ?></p>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="text-[11px] text-slate-400 italic py-2">Semua siswa terpantau baik.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Section: Belum Bimbingan -->
                            <div class="pt-6 border-t border-slate-100">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex flex-col">
                                        <p class="text-[11px] font-black text-amber-500 uppercase tracking-widest">Belum Bimbingan</p>
                                        <span class="text-[10px] text-slate-400 font-bold">Total: <?= $total_no_bimbingan ?> Siswa</span>
                                    </div>
                                    <button onclick="showDetail('no_bimbingan')" class="text-[10px] bg-amber-50 text-amber-600 px-2 py-1 rounded-lg font-bold hover:bg-amber-100 transition-colors border border-amber-100">Lihat Detail</button>
                                </div>
                                <div class="space-y-2">
                                    <?php if(mysqli_num_rows($q_no_bimbingan) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($q_no_bimbingan)): ?>
                                            <div class="flex items-center justify-between p-2.5 hover:bg-slate-50 rounded-xl transition-colors border border-transparent hover:border-slate-100">
                                                <div class="flex items-center">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-2.5"></div>
                                                    <p class="text-xs font-medium text-slate-700"><?= htmlspecialchars($row['nama']) ?></p>
                                                </div>
                                                <span class="text-[9px] font-bold bg-slate-100 px-2 py-0.5 rounded text-slate-500"><?= htmlspecialchars($row['kelas']) ?></span>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="text-[11px] text-slate-400 italic py-2">Seluruh siswa sudah bimbingan.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    let currentData = [];
    let currentPage = 1;
    const itemsPerPage = 5;

    async function showDetail(type) {
        const title = type === 'no_bimbingan' ? 'Siswa Belum Pernah Bimbingan' : 'Siswa Butuh Perhatian Segera';
        const color = type === 'no_bimbingan' ? '#f59e0b' : '#e11d48';

        Swal.fire({
            title: 'Memuat data...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const url = `get_siswa_detail.php?type=${type}&start=<?= $filter_start ?>&end=<?= $filter_end ?>`;
            const response = await fetch(url);
            currentData = await response.json();

            if (currentData.length === 0) {
                Swal.fire({
                    title: 'Kosong',
                    text: 'Tidak ada data siswa untuk kategori ini.',
                    icon: 'info',
                    confirmButtonColor: '#334155'
                });
                return;
            }

            currentPage = 1;
            renderModalContent(title, color, type);

        } catch (error) {
            Swal.fire('Error', 'Gagal memuat data dari server.', 'error');
        }
    }

    function renderModalContent(title, color, type) {
        const start = (currentPage - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, currentData.length);
        const paginatedData = currentData.slice(start, end);

        let tableHtml = `
            <div class="overflow-x-auto mt-4">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="p-3 font-bold text-slate-600">Nama Siswa</th>
                            <th class="p-3 font-bold text-slate-600">Kelas</th>
                            ${type === 'perlu_perhatian' ? '<th class="p-3 font-bold text-slate-600">Aksi</th>' : '<th class="p-3 font-bold text-slate-600">Wali Kelas</th>'}
                        </tr>
                    </thead>
                    <tbody>
        `;

        paginatedData.forEach((item, index) => {
            const realIndex = start + index;
            tableHtml += `
                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                    <td class="p-3 font-medium text-slate-800">${item.nama}</td>
                    <td class="p-3 text-slate-600">${item.kelas}</td>
                    <td class="p-3 text-slate-600">
                        ${type === 'perlu_perhatian' 
                            ? `<button onclick="viewStudentGrades(${realIndex})" class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-xs font-bold hover:bg-blue-100 transition-colors border border-blue-100 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Lihat Nilai
                               </button>` 
                            : (item.nama_guru || '-')}
                    </td>
                </tr>
            `;
        });

        tableHtml += `</tbody></table></div>`;

        const totalPages = ceil(currentData.length / itemsPerPage);
        
        let footerHtml = `
            <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4 border-t border-slate-100 pt-4">
                <p class="text-xs text-slate-500 font-medium">
                    Menampilkan <span class="font-bold text-slate-800">${start + 1}</span> sampai <span class="font-bold text-slate-800">${end}</span> dari <span class="font-bold text-slate-800">${currentData.length}</span> data
                </p>
                <div class="flex items-center space-x-2">
                    <button onclick="changePage(-1, '${type}', '${title}', '${color}')" 
                        ${currentPage === 1 ? 'disabled' : ''} 
                        class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 ${currentPage === 1 ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'} transition-all">
                        &larr; Previous
                    </button>
                    <button onclick="changePage(1, '${type}', '${title}', '${color}')" 
                        ${end >= currentData.length ? 'disabled' : ''} 
                        class="px-3 py-1.5 text-xs font-bold rounded-lg border border-slate-200 ${end >= currentData.length ? 'text-slate-300 bg-slate-50 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'} transition-all">
                        Next &rarr;
                    </button>
                </div>
            </div>
        `;

        Swal.fire({
            title: `<span style="color:${color}">${title}</span>`,
            html: tableHtml + footerHtml,
            width: '850px',
            showConfirmButton: true,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#334155'
        });
    }

    function viewStudentGrades(index) {
        const item = currentData[index];
        
        Swal.fire({
            title: `<span class="text-rose-600">Detail Penilaian: ${item.nama}</span>`,
            width: '700px',
            html: `
                <div class="mt-4 text-left">
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Akademik -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <p class="text-[11px] font-black text-slate-500 uppercase tracking-widest mb-3 border-b border-slate-200 pb-2">Aspek Akademik</p>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Membaca:</span> <span class="font-bold text-slate-800">${item.akademik_membaca}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Menulis:</span> <span class="font-bold text-slate-800">${item.akademik_menulis}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Berhitung:</span> <span class="font-bold text-slate-800">${item.akademik_berhitung}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Al-Quran:</span> <span class="font-bold text-slate-800">${item.akademik_bacaan_quran}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Sholat:</span> <span class="font-bold text-slate-800">${item.akademik_bacaan_sholat}</span></div>
                            </div>
                        </div>
                        <!-- Karakter & Pribadi -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <p class="text-[11px] font-black text-slate-500 uppercase tracking-widest mb-3 border-b border-slate-200 pb-2">Karakter & Pribadi</p>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Sopan Santun:</span> <span class="font-bold text-slate-800">${item.karakter_sopan_santun}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Disiplin:</span> <span class="font-bold text-slate-800">${item.karakter_kedisiplinan}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Kejujuran:</span> <span class="font-bold text-slate-800">${item.karakter_kejujuran}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Emosional:</span> <span class="font-bold text-slate-800">${item.pribadi_emosional}</span></div>
                                <div class="flex justify-between text-sm"><span class="text-slate-500">Sosial:</span> <span class="font-bold text-slate-800">${item.pribadi_sosial}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 bg-rose-50 p-4 rounded-xl border border-rose-100">
                        <p class="text-[11px] font-black text-rose-500 uppercase tracking-widest mb-2">Catatan Guru Wali (${item.tanggal})</p>
                        <p class="text-sm text-slate-700 leading-relaxed italic">"${item.catatan || 'Tidak ada catatan.'}"</p>
                        <div class="mt-3 pt-3 border-t border-rose-100">
                            <p class="text-[11px] font-black text-rose-500 uppercase tracking-widest mb-1">Rencana Tindak Lanjut</p>
                            <p class="text-sm text-slate-700">${item.tindak_lanjut || '-'}</p>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: 'Kembali ke Daftar',
            confirmButtonColor: '#334155',
        }).then((result) => {
            if (result.isConfirmed) {
                // Re-show the list modal
                const type = item.status === 'Perlu Perhatian' ? 'perlu_perhatian' : 'no_bimbingan';
                const title = type === 'no_bimbingan' ? 'Siswa Belum Pernah Bimbingan' : 'Siswa Butuh Perhatian Segera';
                const color = type === 'no_bimbingan' ? '#f59e0b' : '#e11d48';
                renderModalContent(title, color, type);
            }
        });
    }

    function changePage(dir, type, title, color) {
        currentPage += dir;
        renderModalContent(title, color, type);
    }

    function ceil(num) {
        return Math.ceil(num);
    }

    function modalPengumuman() {
        Swal.fire({
            title: 'Buat Pengumuman Baru',
            html: `
                <div class="text-left mt-4">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Target Pengumuman</label>
                    <select id="target_role" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-slate-800 font-medium mb-4">
                        <option value="semua">Semua (Guru & Orang Tua)</option>
                        <option value="guru_wali">Khusus Guru Wali</option>
                        <option value="orang_tua">Khusus Orang Tua</option>
                    </select>

                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Judul Pengumuman</label>
                    <input type="text" id="judul_pengumuman" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-slate-800 font-medium mb-4" placeholder="Masukkan judul...">
                    
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Isi Pengumuman</label>
                    <textarea id="isi_pengumuman" rows="5" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-slate-800 leading-relaxed" placeholder="Tuliskan pesan Anda di sini..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Siarkan Sekarang',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#94a3b8',
            preConfirm: () => {
                const target = document.getElementById('target_role').value;
                const judul = document.getElementById('judul_pengumuman').value;
                const isi = document.getElementById('isi_pengumuman').value;
                if (!judul || !isi) {
                    Swal.showValidationMessage('Harap isi judul dan pesan!');
                }
                return { target, judul, isi };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                savePengumuman(result.value);
            }
        });
    }

    async function savePengumuman(data) {
        Swal.fire({ title: 'Menyiarkan...', didOpen: () => { Swal.showLoading(); } });
        
        try {
            const formData = new FormData();
            formData.append('action', data.id ? 'edit_pengumuman' : 'save_pengumuman');
            if (data.id) formData.append('id', data.id);
            formData.append('target_role', data.target);
            formData.append('judul', data.judul);
            formData.append('isi', data.isi);
            
            const response = await fetch('kepsek_action.php', {
                method: 'POST',
                body: formData
            });
            const resData = await response.json();
            
            if (resData.success) {
                Swal.fire('Berhasil!', data.id ? 'Pengumuman telah diperbarui.' : 'Pengumuman telah disiarkan ke seluruh sistem.', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal', resData.message || 'Terjadi kesalahan.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Gagal menghubungkan ke server.', 'error');
        }
    }

    function editPengumuman(id, judul, isi, target) {
        Swal.fire({
            title: 'Edit Pengumuman',
            html: `
                <div class="text-left mt-4">
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Target Pengumuman</label>
                    <select id="target_role" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-slate-800 font-medium mb-4">
                        <option value="semua" ${target === 'semua' ? 'selected' : ''}>Semua (Guru & Orang Tua)</option>
                        <option value="guru_wali" ${target === 'guru_wali' ? 'selected' : ''}>Khusus Guru Wali</option>
                        <option value="orang_tua" ${target === 'orang_tua' ? 'selected' : ''}>Khusus Orang Tua</option>
                    </select>

                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Judul Pengumuman</label>
                    <input type="text" id="judul_pengumuman" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-slate-800 font-medium mb-4" placeholder="Masukkan judul..." value="${judul}">
                    
                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Isi Pengumuman</label>
                    <textarea id="isi_pengumuman" rows="5" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-slate-800 leading-relaxed" placeholder="Tuliskan pesan Anda di sini...">${isi}</textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Simpan Perubahan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#94a3b8',
            preConfirm: () => {
                const target = document.getElementById('target_role').value;
                const judul = document.getElementById('judul_pengumuman').value;
                const isi = document.getElementById('isi_pengumuman').value;
                if (!judul || !isi) {
                    Swal.showValidationMessage('Harap isi judul dan pesan!');
                }
                return { id, target, judul, isi };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                savePengumuman(result.value);
            }
        });
    }

    function deletePengumuman(id) {
        Swal.fire({
            title: 'Hapus Pengumuman?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                confirmDeletePengumuman(id);
            }
        });
    }

    async function confirmDeletePengumuman(id) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_pengumuman');
            formData.append('id', id);
            
            const response = await fetch('kepsek_action.php', {
                method: 'POST',
                body: formData
            });
            const resData = await response.json();
            
            if (resData.success) {
                Swal.fire('Terhapus!', 'Pengumuman telah dihapus.', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal', resData.message || 'Terjadi kesalahan.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Gagal menghubungkan ke server.', 'error');
        }
    }

    function viewAnnouncement(judul, isi, target, tanggal) {
        const targetText = target === 'semua' ? 'Semua (Guru & Ortu)' : (target === 'guru_wali' ? 'Khusus Guru Wali' : 'Khusus Orang Tua');
        
        Swal.fire({
            title: `<div class="text-left"><p class="text-xs font-black text-amber-600 uppercase tracking-widest mb-1">${targetText}</p><h2 class="text-xl font-black text-slate-800">${judul}</h2></div>`,
            html: `
                <div class="text-left mt-4">
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-slate-700 leading-relaxed text-sm whitespace-pre-wrap">
                        ${isi}
                    </div>
                    <p class="mt-4 text-[10px] font-bold text-slate-400 italic">Disiarkan pada: ${tanggal}</p>
                </div>
            `,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#334155',
            showCloseButton: true,
            width: '600px'
        });
    }

    async function showAnnouncementHistory() {
        Swal.fire({ title: 'Memuat riwayat...', didOpen: () => { Swal.showLoading(); } });
        
        try {
            const formData = new FormData();
            formData.append('action', 'get_announcements');
            
            const response = await fetch('kepsek_action.php', {
                method: 'POST',
                body: formData
            });
            const resData = await response.json();
            
            if (resData.success) {
                let html = `
                    <div class="text-left mt-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                        <div class="space-y-4">
                `;
                
                resData.data.forEach(ann => {
                    const targetText = ann.target_role === 'semua' ? 'SEMUA' : (ann.target_role === 'guru_wali' ? 'GURU' : 'ORTU');
                    html += `
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 relative group">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded bg-amber-100 text-amber-700 uppercase tracking-widest">${targetText}</span>
                                    <span class="text-[9px] text-slate-400 font-bold">${ann.tanggal_format}</span>
                                </div>
                                <div class="flex space-x-1">
                                    <button onclick='event.stopPropagation(); editPengumuman(${ann.id}, "${ann.judul.replace(/"/g, '&quot;')}", "${ann.isi.replace(/"/g, '&quot;')}", "${ann.target_role}")' class="p-1 text-blue-600 hover:bg-blue-100 rounded transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button onclick='event.stopPropagation(); deletePengumuman(${ann.id})' class="p-1 text-rose-600 hover:bg-rose-100 rounded transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800 mb-1">${ann.judul}</h4>
                            <p class="text-xs text-slate-600 leading-relaxed">${ann.isi}</p>
                        </div>
                    `;
                });
                
                html += `</div></div>`;
                
                Swal.fire({
                    title: '<h2 class="text-xl font-black text-slate-800 text-left">Riwayat Pengumuman</h2>',
                    html: html,
                    width: '600px',
                    confirmButtonText: 'Tutup',
                    confirmButtonColor: '#334155'
                });
            } else {
                Swal.fire('Gagal', resData.message || 'Gagal mengambil data.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'Gagal menghubungkan ke server.', 'error');
        }
    }
    </script>
</body>
</html>
