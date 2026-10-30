<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Auto-create table and insert default settings if not exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS pengaturan (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    nama_aplikasi VARCHAR(100) NOT NULL,
    nama_sekolah VARCHAR(150) NOT NULL,
    alamat_sekolah TEXT NOT NULL,
    npsn VARCHAR(20) DEFAULT '-'
)";
mysqli_query($conn, $createTableQuery);

// Migration: Add npsn column if not exists
$checkColumn = mysqli_query($conn, "SHOW COLUMNS FROM pengaturan LIKE 'npsn'");
if (mysqli_num_rows($checkColumn) == 0) {
    mysqli_query($conn, "ALTER TABLE pengaturan ADD COLUMN npsn VARCHAR(20) DEFAULT '-'");
}

$checkQuery = "SELECT * FROM pengaturan WHERE id = 1";
$result = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($result) == 0) {
    mysqli_query($conn, "INSERT INTO pengaturan (id, nama_aplikasi, nama_sekolah, alamat_sekolah, npsn) VALUES (1, 'WaliCare', 'SMA Negeri 1 Nusantara', 'Jl. Pendidikan No. 123, Kota Pendidikan, Provinsi Kode 12345', '12345678')");
    $result = mysqli_query($conn, $checkQuery);
}

$pengaturan = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=980">
    <title>Pengaturan Aplikasi - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fafbfc; }
        .sidebar-bg { background-color: #383a79; }
        .active-menu { background-color: #4b4da3; }
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
            <a href="admin_siswa.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-5 h-5 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Data Siswa (Admin)
            </a>
            <a href="admin_pengguna.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-5 h-5 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                Kelola Pengguna
            </a>
            <div class="pt-2 pb-1 px-4">
                <p class="text-[10px] font-bold text-indigo-300 uppercase tracking-[0.2em]">Sistem</p>
            </div>
            <a href="admin_pengaturan.php" class="flex items-center px-4 py-3.5 active-menu rounded-xl text-white font-medium shadow-sm">
                <svg class="w-5 h-5 mr-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Pengaturan Aplikasi
            </a>
            <a href="admin_backup.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-xl text-indigo-100 hover:text-white transition-colors font-medium">
                <svg class="w-5 h-5 mr-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
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
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Pengaturan Aplikasi</h2>
                <p class="text-sm text-gray-500 mt-1">Sesuaikan identitas aplikasi, nama sekolah, dan informasi cetak.</p>
            </div>

            <!-- Settings Form Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-8 max-w-4xl">
                <form action="admin_pengaturan_action.php" method="POST">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Aplikasi</label>
                        <input type="text" name="nama_aplikasi" value="<?= htmlspecialchars($pengaturan['nama_aplikasi']) ?>" required class="w-full max-w-xl px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                        <p class="text-xs text-gray-400 mt-2">Nama ini akan ditampilkan pada halaman login, sidebar, dan judul atas aplikasi.</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Sekolah / Institusi</label>
                        <input type="text" name="nama_sekolah" value="<?= htmlspecialchars($pengaturan['nama_sekolah']) ?>" required class="w-full max-w-xl px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                        <p class="text-xs text-gray-400 mt-2">Akan digunakan sebagai Kop Surat (Header) pada dokumen laporan PDF.</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">NPSN Sekolah</label>
                        <input type="text" name="npsn" value="<?= htmlspecialchars($pengaturan['npsn'] ?? '-') ?>" required class="w-full max-w-xl px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>

                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Lengkap Sekolah</label>
                        <textarea name="alamat_sekolah" rows="3" required class="w-full max-w-xl px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"><?= htmlspecialchars($pengaturan['alamat_sekolah']) ?></textarea>
                    </div>

                    <div class="flex items-center space-x-3 max-w-xl justify-center">
                        <button type="submit" class="flex items-center justify-center px-6 py-2.5 bg-[#5e5ce6] text-white rounded-lg font-medium hover:bg-indigo-600 transition-colors shadow-sm">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script>
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
                customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg px-5 py-2' }
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
