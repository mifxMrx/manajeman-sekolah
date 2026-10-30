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
$show_ann = false;
if ($ann && (!isset($_SESSION['last_ann_id_guru']) || $_SESSION['last_ann_id_guru'] != $ann['id'])) {
    $show_ann = true;
    $_SESSION['last_ann_id_guru'] = $ann['id'];
}

// Pagination Logic
$limit_options = [5, 10, 25];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$query_count = "SELECT COUNT(*) as total FROM siswa WHERE id_guru_wali = $guru_id";
$res_count = mysqli_query($conn, $query_count);
$total_records = mysqli_fetch_assoc($res_count)['total'];
$total_pages = ceil($total_records / $limit);

// Fetch Siswa Asuh with their latest status from catatan_bimbingan
$query = "
    SELECT s.*, 
    (SELECT status FROM catatan_bimbingan WHERE id_siswa = s.id ORDER BY tanggal DESC LIMIT 1) as status_terakhir
    FROM siswa s
    WHERE s.id_guru_wali = $guru_id
    ORDER BY s.nama ASC
    LIMIT $limit OFFSET $offset
";
$result = mysqli_query($conn, $query);

// Fetch other Guru Wali for transfer feature
$query_other_gurus = mysqli_query($conn, "SELECT id, nama_lengkap, tingkatan FROM users WHERE role = 'guru_wali' AND id != $guru_id ORDER BY nama_lengkap ASC");
$other_gurus = [];
while($g = mysqli_fetch_assoc($query_other_gurus)){
    $other_gurus[] = $g;
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
    <title>Data Siswa Asuh - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-bg { background-color: #064e3b; }
        .active-menu { background-color: #065f46; border-left: 4px solid #34d399; }
        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: opacity 300ms, transform 300ms; }
        .modal-leave { opacity: 1; transform: scale(1); }
        .modal-leave-active { opacity: 0; transform: scale(0.95); transition: opacity 300ms, transform 300ms; }
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
            <a href="guru_siswa.php" class="flex items-center px-4 py-3.5 active-menu rounded-r-xl text-white font-semibold transition-all">
                <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
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
    <main class="flex-1 flex flex-col h-screen overflow-hidden pt-16 lg:pt-0">
        <div class="flex-1 overflow-y-auto p-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Manajemen Data Siswa Asuh</h2>
                    <p class="text-sm text-slate-500 mt-1 font-medium">Daftar siswa yang saat ini berada di bawah perwalian Anda.</p>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-8 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/30">
                    <form action="" method="GET" class="flex items-center space-x-2">
                        <span class="text-sm font-bold text-slate-600">Tampilkan</span>
                        <select name="limit" onchange="this.form.submit()" class="border border-slate-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer bg-white font-medium text-slate-700 shadow-sm">
                            <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        </select>
                        <span class="text-sm font-bold text-slate-600">entri</span>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 text-xs uppercase text-slate-400 border-b border-slate-100">
                                <th class="px-8 py-5 font-bold">NIS</th>
                                <th class="px-8 py-5 font-bold">Nama Siswa</th>
                                <th class="px-8 py-5 font-bold">Tingkatan</th>
                                <th class="px-8 py-5 font-bold">Kelas</th>
                                <th class="px-8 py-5 font-bold">Alamat</th>
                                <th class="px-8 py-5 font-bold">Status Dinamis</th>
                                <th class="px-8 py-5 font-bold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-8 py-6 text-sm text-slate-600 font-medium"><?= htmlspecialchars($row['nis']) ?></td>
                                        <td class="px-8 py-6 text-sm font-extrabold text-slate-800"><?= htmlspecialchars($row['nama']) ?></td>
                                        <td class="px-8 py-6 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $row['tingkatan'] == 'A' ? 'bg-blue-100 text-blue-600' : ($row['tingkatan'] == 'B' ? 'bg-purple-100 text-purple-600' : 'bg-orange-100 text-orange-600') ?>">
                                                Tingkatan <?= htmlspecialchars($row['tingkatan']) ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-sm text-slate-500 font-medium"><?= htmlspecialchars($row['kelas']) ?></td>
                                        <td class="px-8 py-6 text-sm text-slate-500 max-w-xs truncate"><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
                                        <td class="px-8 py-6">
                                            <?php if($row['status_terakhir'] == 'Perlu Perhatian'): ?>
                                                <span class="px-3 py-1 bg-rose-50 text-rose-600 text-[10px] font-extrabold rounded-lg uppercase border border-rose-100">Perlu Perhatian</span>
                                            <?php elseif($row['status_terakhir'] == 'Baik'): ?>
                                                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-extrabold rounded-lg uppercase border border-emerald-100">Baik</span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 bg-slate-50 text-slate-400 text-[10px] font-extrabold rounded-lg uppercase border border-slate-100">Belum Ada Data</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="flex items-center justify-center space-x-4">
                                                <button onclick="openFormModal('edit', <?= htmlspecialchars(json_encode(['id' => $row['id'], 'nis' => $row['nis'], 'nama' => $row['nama'], 'tingkatan' => $row['tingkatan'], 'kelas' => $row['kelas'], 'alamat' => $row['alamat'] ?? ''])) ?>)" class="p-2 text-indigo-400 hover:bg-indigo-50 rounded-lg transition-colors border border-transparent hover:border-indigo-100" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </button>
                                                <button onclick="openTransferModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama'])) ?>', '<?= htmlspecialchars($row['tingkatan']) ?>')" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors border border-transparent hover:border-blue-100" title="Transfer Siswa">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                                </button>
                                                <button onclick="confirmDelete(<?= $row['id'] ?>)" class="p-2 text-rose-400 hover:bg-rose-50 rounded-lg transition-colors border border-transparent hover:border-rose-100" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-8 py-20 text-center text-slate-400 font-medium italic">Anda belum memiliki siswa asuh.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($total_pages > 0): ?>
                    <div class="px-8 py-5 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/30">
                        <div class="text-sm text-slate-500 font-medium">
                            Menampilkan <?= min($offset + 1, $total_records) ?> sampai <?= min($offset + $limit, $total_records) ?> dari <?= $total_records ?> data
                        </div>
                        <div class="flex items-center space-x-1">
                            <?php if($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm text-slate-500 hover:text-emerald-600 transition-colors font-medium">Previous</a>
                            <?php else: ?>
                                <span class="px-3 py-1.5 text-sm text-slate-300 cursor-not-allowed font-medium">Previous</span>
                            <?php endif; ?>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm rounded-lg font-medium transition-colors <?= $i == $page ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" class="px-3 py-1.5 text-sm text-slate-500 hover:text-emerald-600 transition-colors font-medium">Next</a>
                            <?php else: ?>
                                <span class="px-3 py-1.5 text-sm text-slate-300 cursor-not-allowed font-medium">Next</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Hidden Form for Delete -->
    <form id="deleteForm" action="guru_siswa_action.php" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <!-- Modal Form (Add/Edit) -->
    <div id="formModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeFormModal()"></div>
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg z-10 p-8 modal-enter transform transition-all relative">
            <button onclick="closeFormModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 id="modalTitle" class="text-xl font-extrabold text-slate-800 mb-6">Tambah Siswa Asuh</h3>
            <form action="guru_siswa_action.php" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">NIS</label>
                        <input type="text" name="nis" id="formNis" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nama Lengkap Siswa</label>
                        <input type="text" name="nama" id="formNama" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition-all">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tingkatan</label>
                            <select name="tingkatan" id="formTingkatan" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition-all">
                                <option value="A">Tingkatan A</option>
                                <option value="B">Tingkatan B</option>
                                <option value="C">Tingkatan C</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Kelas / Ruang</label>
                            <input type="text" name="kelas" id="formKelas" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition-all" placeholder="Contoh: X-1">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Alamat (Opsional)</label>
                        <textarea name="alamat" id="formAlamat" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white outline-none transition-all"></textarea>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" onclick="closeFormModal()" class="px-6 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-50 transition-colors">Batal</button>
                    <button type="submit" class="px-6 py-3 bg-[#5e5ce6] text-white rounded-xl font-bold shadow-lg shadow-indigo-500/30 hover:bg-indigo-600 transition-colors">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Transfer Siswa -->
    <div id="transferModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeTransferModal()"></div>
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg z-10 p-8 modal-enter transform transition-all relative">
            <button onclick="closeTransferModal()" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 class="text-xl font-extrabold text-slate-800 mb-2">Transfer Siswa</h3>
            <p class="text-sm text-slate-500 mb-6">Pindahkan <strong id="transferStudentName" class="text-slate-700"></strong> ke Guru Wali lain.</p>
            
            <form action="guru_siswa_action.php" method="POST">
                <input type="hidden" name="action" value="transfer">
                <input type="hidden" name="id" id="transferId" value="">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Guru Wali Tujuan</label>
                        <select name="id_guru_wali_baru" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all">
                            <option value="">-- Pilih Guru Tujuan --</option>
                            <?php foreach($other_gurus as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_lengkap']) ?> (Tingkat <?= $g['tingkatan'] ?? '-' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Tingkatan Siswa</label>
                        <select name="tingkatan_baru" id="transferTingkatan" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition-all">
                            <option value="A">Tingkatan A</option>
                            <option value="B">Tingkatan B</option>
                            <option value="C">Tingkatan C</option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" onclick="closeTransferModal()" class="px-6 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-50 transition-colors">Batal</button>
                    <button type="submit" class="px-6 py-3 bg-blue-500 text-white rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-600 transition-colors">Transfer Siswa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
        const formModal = document.getElementById('formModal');
        const formInner = formModal.querySelector('div:nth-child(2)');
        
        const transferModal = document.getElementById('transferModal');
        const transferInner = transferModal.querySelector('div:nth-child(2)');

        function openFormModal(action, data = null) {
            formModal.classList.remove('hidden');
            setTimeout(() => {
                formInner.classList.remove('modal-enter');
                formInner.classList.add('modal-enter-active');
            }, 10);

            const formAction = document.getElementById('formAction');
            const modalTitle = document.getElementById('modalTitle');
            const formNis = document.getElementById('formNis');
            
            if(action === 'add') {
                formAction.value = 'add';
                modalTitle.innerText = 'Tambah Siswa Asuh';
                formNis.readOnly = false;
                formNis.classList.remove('bg-slate-100', 'cursor-not-allowed', 'text-slate-500');
                formNis.classList.add('bg-slate-50');
                document.getElementById('formId').value = '';
                formNis.value = '';
                document.getElementById('formNama').value = '';
                document.getElementById('formTingkatan').value = '<?= $tingkatan_guru ?>';
                document.getElementById('formKelas').value = '';
                document.getElementById('formAlamat').value = '';
            } else if (action === 'edit' && data) {
                formAction.value = 'edit';
                modalTitle.innerText = 'Edit Data Siswa';
                formNis.readOnly = true;
                formNis.classList.add('bg-slate-100', 'cursor-not-allowed', 'text-slate-500');
                formNis.classList.remove('bg-slate-50');
                document.getElementById('formId').value = data.id;
                formNis.value = data.nis;
                document.getElementById('formNama').value = data.nama;
                document.getElementById('formTingkatan').value = data.tingkatan;
                document.getElementById('formKelas').value = data.kelas;
                document.getElementById('formAlamat').value = data.alamat;
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

        function openTransferModal(id, nama, tingkatan) {
            transferModal.classList.remove('hidden');
            setTimeout(() => {
                transferInner.classList.remove('modal-enter');
                transferInner.classList.add('modal-enter-active');
            }, 10);

            document.getElementById('transferId').value = id;
            document.getElementById('transferStudentName').innerText = nama;
            document.getElementById('transferTingkatan').value = tingkatan;
        }

        function closeTransferModal() {
            transferInner.classList.remove('modal-enter-active');
            transferInner.classList.add('modal-leave-active');
            setTimeout(() => {
                transferModal.classList.add('hidden');
                transferInner.classList.remove('modal-leave-active');
                transferInner.classList.add('modal-enter');
            }, 300);
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Hapus Siswa Asuh?',
                text: "Semua riwayat bimbingan siswa ini juga akan terhapus!",
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
                text: '<?= $_SESSION['swal_success'] ?>',
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
                text: '<?= $_SESSION['swal_error'] ?>',
                customClass: { popup: 'rounded-3xl', confirmButton: 'rounded-xl px-6 py-3' }
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
