<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Pagination Logic
$limit_options = [5, 10, 25];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch Data Siswa JOIN dengan Data Guru
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_search = $search ? " WHERE s.nama LIKE '%$search%' OR s.nis LIKE '%$search%' OR u.nama_lengkap LIKE '%$search%'" : "";

$query_count = "SELECT COUNT(*) as total FROM siswa s LEFT JOIN users u ON s.id_guru_wali = u.id AND u.role = 'guru_wali' $where_search";
$res_count = mysqli_query($conn, $query_count);
$total_records = mysqli_fetch_assoc($res_count)['total'];
$total_pages = ceil($total_records / $limit);

$query_siswa = "
    SELECT s.*, u.nama_lengkap as nama_guru 
    FROM siswa s 
    LEFT JOIN users u ON s.id_guru_wali = u.id AND u.role = 'guru_wali'
    $where_search
    ORDER BY s.kelas ASC, s.nama ASC
    LIMIT $limit OFFSET $offset
";
$result_siswa = mysqli_query($conn, $query_siswa);

// Fetch Daftar Guru untuk Dropdown
$query_guru = "SELECT id, nama_lengkap, tingkatan FROM users WHERE role = 'guru_wali' ORDER BY nama_lengkap ASC";
$result_guru = mysqli_query($conn, $query_guru);
$guru_list = [];
while ($row = mysqli_fetch_assoc($result_guru)) {
    $guru_list[] = $row;
}

// Fetch Daftar Orang Tua untuk Dropdown
$query_ortu = "SELECT id, nama_lengkap FROM users WHERE role = 'orang_tua' ORDER BY nama_lengkap ASC";
$result_ortu = mysqli_query($conn, $query_ortu);
$ortu_list = [];
while ($row = mysqli_fetch_assoc($result_ortu)) {
    $ortu_list[] = $row;
}

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
    <title>Data Siswa - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fafbfc; }
        .sidebar-bg { background-color: #383a79; }
        .active-menu { background-color: #4b4da3; }
        
        /* Modal Transitions */
        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity 300ms, transform 300ms; }
        .modal-leave { opacity: 1; transform: scale(1); }
        .modal-leave-active { opacity: 0; transform: scale(0.95); transition: opacity 300ms, transform 300ms; }
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
            <a href="admin.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-5 h-5 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard Admin
            </a>
            <a href="admin_siswa.php" class="flex items-center px-4 py-3.5 active-menu rounded-xl text-white font-medium shadow-sm">
                <svg class="w-5 h-5 mr-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
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
            <a href="admin_pengaturan.php" class="flex items-center px-4 py-3 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-4 h-4 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Pengaturan
            </a>
            <a href="admin_backup.php" class="flex items-center px-4 py-3 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-4 h-4 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                Backup & Restore
            </a>
        </nav>
        <div class="p-6 shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold mr-3">AD</div>
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
        <div class="flex-1 overflow-y-auto p-10">
            <!-- Header Title -->
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Database Seluruh Siswa</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola data semua siswa dan hubungkan dengan Guru Wali Pembimbing.</p>
                </div>
                <div class="flex space-x-3">
                    <div id="bulkActionArea" class="hidden flex items-center space-x-2 animate-pulse">
                        <button onclick="confirmBulkDelete()" class="flex items-center px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition-colors shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Hapus Terpilih (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                    <button onclick="openImportModal()" class="flex items-center px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Excel
                    </button>
                    <button onclick="openFormModal('add')" class="flex items-center px-4 py-2 bg-[#5e5ce6] text-white rounded-lg text-sm font-medium hover:bg-indigo-600 transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Siswa Baru
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 font-bold uppercase tracking-wider">Database Seluruh Siswa</span>
                    </div>
                    
                    <form action="" method="GET" class="flex w-full md:w-auto space-x-3">
                        <select name="limit" onchange="this.form.submit()" class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all cursor-pointer">
                            <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5 Entri</option>
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 Entri</option>
                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 Entri</option>
                        </select>
                        <div class="relative w-full md:w-80">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, NIS, atau guru..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white outline-none transition-all">
                            <svg class="w-4 h-4 absolute left-3.5 top-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </form>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-xs uppercase text-gray-500 border-b border-gray-100 bg-gray-50/50">
                            <th class="px-6 py-4 font-semibold text-center w-10">
                                <input type="checkbox" id="selectAll" class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 cursor-pointer">
                            </th>
                            <th class="px-6 py-4 font-semibold">NIS</th>
                            <th class="px-6 py-4 font-semibold">Nama Siswa</th>
                            <th class="px-6 py-4 font-semibold">Tingkatan</th>
                            <th class="px-6 py-4 font-semibold">Kelas</th>
                            <th class="px-6 py-4 font-semibold">Guru Pembimbing</th>
                            <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm">
                        <?php if(mysqli_num_rows($result_siswa) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result_siswa)): ?>
                                <tr class="hover:bg-gray-50/30 transition-colors">
                                    <td class="px-6 py-5 text-center">
                                        <input type="checkbox" name="siswa_ids[]" value="<?= $row['id'] ?>" class="user-checkbox w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 cursor-pointer">
                                    </td>
                                    <td class="px-6 py-5 text-gray-600"><?= htmlspecialchars($row['nis']) ?></td>
                                    <td class="px-6 py-5 font-semibold text-gray-800"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="px-6 py-5">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $row['tingkatan'] == 'A' ? 'bg-blue-100 text-blue-600' : ($row['tingkatan'] == 'B' ? 'bg-purple-100 text-purple-600' : 'bg-orange-100 text-orange-600') ?>">
                                            Tingkatan <?= htmlspecialchars($row['tingkatan']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-gray-600"><?= htmlspecialchars($row['kelas']) ?></td>
                                    <td class="px-6 py-5">
                                        <?php if($row['nama_guru']): ?>
                                            <p class="text-[#5e5ce6] font-medium"><?= htmlspecialchars($row['nama_guru']) ?></p>
                                        <?php else: ?>
                                            <p class="text-gray-400 font-medium italic">Belum ditentukan</p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <div class="flex items-center justify-end space-x-3">
                                            <button onclick="openFormModal('edit', <?= htmlspecialchars(json_encode($row)) ?>)" class="text-indigo-400 hover:text-indigo-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <button onclick="confirmDelete(<?= $row['id'] ?>)" class="text-red-400 hover:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada data siswa.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if($total_pages > 0): ?>
                    <div class="px-6 py-4 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/30">
                        <div class="text-sm text-gray-500 font-medium">
                            Menampilkan <?= min($offset + 1, $total_records) ?> sampai <?= min($offset + $limit, $total_records) ?> dari <?= $total_records ?> data
                        </div>
                        <div class="flex items-center space-x-1">
                            <?php if($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm text-gray-500 hover:text-[#5e5ce6] transition-colors font-medium">Previous</a>
                            <?php else: ?>
                                <span class="px-3 py-1.5 text-sm text-gray-300 cursor-not-allowed font-medium">Previous</span>
                            <?php endif; ?>
                            
                            <?php
                            $range = 1; // Jumlah halaman di sekitar halaman aktif
                            $show_dots = true;

                            for ($i = 1; $i <= $total_pages; $i++):
                                if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)):
                            ?>
                                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm rounded-lg font-medium transition-colors <?= $i == $page ? 'bg-[#5e5ce6] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' ?>">
                                    <?= $i ?>
                                </a>
                            <?php
                                    $show_dots = true;
                                elseif ($show_dots):
                            ?>
                                <span class="px-2 text-gray-400">...</span>
                            <?php
                                    $show_dots = false;
                                endif;
                            endfor;
                            ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm text-gray-500 hover:text-[#5e5ce6] transition-colors font-medium">Next</a>
                            <?php else: ?>
                                <span class="px-3 py-1.5 text-sm text-gray-300 cursor-not-allowed font-medium">Next</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Hidden Form for Delete -->
    <form id="deleteForm" action="admin_siswa_action.php" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <form id="bulkDeleteForm" action="admin_siswa_action.php" method="POST" class="hidden">
        <input type="hidden" name="action" value="bulk_delete">
        <input type="hidden" name="ids" id="bulkDeleteIds">
    </form>

    <!-- Modal Form (Add/Edit) -->
    <div id="formModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" onclick="closeFormModal()"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg z-10 p-8 modal-enter transform transition-all relative">
            <button onclick="closeFormModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 id="modalTitle" class="text-xl font-bold text-gray-800 mb-6">Tambah Siswa Baru</h3>
            <form action="admin_siswa_action.php" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIS</label>
                        <input type="text" name="nis" id="formNis" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Siswa</label>
                        <input type="text" name="nama" id="formNama" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tingkatan</label>
                            <select name="tingkatan" id="formTingkatan" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white">
                                <option value="A">Tingkatan A</option>
                                <option value="B">Tingkatan B</option>
                                <option value="C">Tingkatan C</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas / Ruang</label>
                            <input type="text" name="kelas" id="formKelas" required class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" placeholder="Misal: X-1">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guru Wali (Opsional)</label>
                        <select name="id_guru_wali" id="formGuru" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white">
                            <option value="">-- Pilih Guru Wali --</option>
                            <?php foreach($guru_list as $guru): ?>
                                <option value="<?= $guru['id'] ?>"><?= htmlspecialchars($guru['nama_lengkap']) ?> (Tingkatan <?= $guru['tingkatan'] ?? '-' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orang Tua (Opsional)</label>
                        <select name="id_orang_tua" id="formOrtu" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white">
                            <option value="">-- Pilih Orang Tua --</option>
                            <?php foreach($ortu_list as $ortu): ?>
                                <option value="<?= $ortu['id'] ?>"><?= htmlspecialchars($ortu['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" onclick="closeFormModal()" class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-lg font-medium hover:bg-gray-50 transition-colors">Batal</button>
                    <button type="submit" class="px-5 py-2.5 bg-[#5e5ce6] text-white rounded-lg font-medium hover:bg-indigo-600 transition-colors">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import CSV -->
    <div id="importModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" onclick="closeImportModal()"></div>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md z-10 p-8 modal-enter transform transition-all relative">
            <button onclick="closeImportModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <div class="text-center mb-6">
                <div class="mx-auto w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Import Data CSV</h3>
                <p class="text-sm text-gray-500 mt-1">Unggah file CSV dengan format kolom: <br><b>NIS, Nama, Tingkatan, Kelas, Username Guru, Username Ortu</b></p>
            </div>
            <form action="admin_siswa_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <div class="mt-4 border-2 border-dashed border-gray-300 rounded-xl p-6 flex flex-col items-center justify-center hover:border-indigo-500 hover:bg-indigo-50 transition-colors cursor-pointer relative">
                    <input type="file" name="file_csv" accept=".csv" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span class="text-sm font-medium text-gray-600">Klik atau seret file ke sini</span>
                    <span class="text-xs text-gray-400 mt-1">Format: .csv (Maks. 2MB)</span>
                </div>
                <div class="mt-3 text-center">
                    <a href="../templates/template_siswa.csv" download class="text-xs text-indigo-600 hover:text-indigo-800 font-medium underline">Unduh Template CSV</a>
                </div>
                <div class="mt-6">
                    <button type="submit" class="w-full py-3 bg-[#5e5ce6] text-white rounded-xl font-medium hover:bg-indigo-600 transition-colors shadow-sm">Mulai Import Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
        // Modal Handlers
        const formModal = document.getElementById('formModal');
        const importModal = document.getElementById('importModal');
        const formInner = formModal.querySelector('div:nth-child(2)');
        const importInner = importModal.querySelector('div:nth-child(2)');

        function openFormModal(action, data = null) {
            formModal.classList.remove('hidden');
            setTimeout(() => {
                formInner.classList.remove('modal-enter');
                formInner.classList.add('modal-enter-active');
            }, 10);

            const formAction = document.getElementById('formAction');
            const modalTitle = document.getElementById('modalTitle');
            
            if(action === 'add') {
                formAction.value = 'add';
                modalTitle.innerText = 'Tambah Siswa Baru';
                document.getElementById('formId').value = '';
                document.getElementById('formNis').value = '';
                document.getElementById('formNama').value = '';
                document.getElementById('formTingkatan').value = 'A';
                document.getElementById('formKelas').value = '';
                document.getElementById('formGuru').value = '';
                document.getElementById('formOrtu').value = '';
            } else if (action === 'edit' && data) {
                formAction.value = 'edit';
                modalTitle.innerText = 'Edit Data Siswa';
                document.getElementById('formId').value = data.id;
                document.getElementById('formNis').value = data.nis;
                document.getElementById('formNama').value = data.nama;
                document.getElementById('formTingkatan').value = data.tingkatan;
                document.getElementById('formKelas').value = data.kelas;
                document.getElementById('formGuru').value = data.id_guru_wali || '';
                document.getElementById('formOrtu').value = data.id_orang_tua || '';
            }
        }

        function closeFormModal() {
            formInner.classList.remove('modal-enter-active');
            formInner.classList.add('modal-leave-active');
            setTimeout(() => {
                formModal.classList.add('hidden');
                formInner.classList.remove('modal-leave-active');
                formInner.classList.add('modal-enter');
            }, 300);
        }

        function openImportModal() {
            importModal.classList.remove('hidden');
            setTimeout(() => {
                importInner.classList.remove('modal-enter');
                importInner.classList.add('modal-enter-active');
            }, 10);
        }

        function closeImportModal() {
            importInner.classList.remove('modal-enter-active');
            importInner.classList.add('modal-leave-active');
            setTimeout(() => {
                importModal.classList.add('hidden');
                importInner.classList.remove('modal-leave-active');
                importInner.classList.add('modal-enter');
            }, 300);
        }

        // Checkbox Logic
        const selectAll = document.getElementById('selectAll');
        const userCheckboxes = document.getElementsByClassName('user-checkbox');
        const bulkActionArea = document.getElementById('bulkActionArea');
        const selectedCount = document.getElementById('selectedCount');

        selectAll.addEventListener('change', function() {
            Array.from(userCheckboxes).forEach(cb => {
                cb.checked = this.checked;
            });
            updateBulkActionArea();
        });

        Array.from(userCheckboxes).forEach(cb => {
            cb.addEventListener('change', updateBulkActionArea);
        });

        function updateBulkActionArea() {
            const checkedCount = Array.from(userCheckboxes).filter(cb => cb.checked).length;
            if (checkedCount > 0) {
                bulkActionArea.classList.remove('hidden');
                selectedCount.innerText = checkedCount;
            } else {
                bulkActionArea.classList.add('hidden');
            }
            // Sync selectAll checkbox
            selectAll.checked = checkedCount === userCheckboxes.length && userCheckboxes.length > 0;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < userCheckboxes.length;
        }

        function confirmBulkDelete() {
            const ids = Array.from(userCheckboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            if (ids.length === 0) return;

            Swal.fire({
                title: 'Hapus ' + ids.length + ' Siswa Terpilih?',
                text: "Aksi ini tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-lg px-5 py-2.5 font-medium',
                    cancelButton: 'rounded-lg px-5 py-2.5 font-medium',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('bulkDeleteIds').value = JSON.stringify(ids);
                    document.getElementById('bulkDeleteForm').submit();
                }
            })
        }

        // SweetAlert Delete Confirmation
        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Data Siswa?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-lg px-5 py-2.5 font-medium',
                    cancelButton: 'rounded-lg px-5 py-2.5 font-medium',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteId').value = id;
                    document.getElementById('deleteForm').submit();
                }
            })
        }

        // SweetAlert Notifications for Success/Error from Session
        <?php if(isset($_SESSION['swal_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['swal_success'] ?>',
                timer: 2500,
                showConfirmButton: false,
                customClass: { popup: 'rounded-2xl' }
            });
            <?php unset($_SESSION['swal_success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['swal_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['swal_error'] ?>',
                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg' }
            });
            <?php unset($_SESSION['swal_error']); ?>
        <?php endif; ?>
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
    </script>
</body>
</html>
