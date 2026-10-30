<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch settings
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

// Check for status messages
$status = $_SESSION['swal_success'] ?? null;
$error = $_SESSION['swal_error'] ?? null;
unset($_SESSION['swal_success'], $_SESSION['swal_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=980">
    <title>Backup & Restore - WaliCare</title>
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
            <a href="admin_backup.php" class="flex items-center px-4 py-3.5 active-menu rounded-xl text-white font-medium shadow-sm">
                <svg class="w-4 h-4 mr-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
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
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Backup & Restore</h2>
                <p class="text-sm text-gray-500 mt-1">Amankan data sistem dengan mencadangkan atau memulihkan database.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-6xl">
                <!-- Backup Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 overflow-hidden relative group">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="p-8 relative">
                        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Backup Database</h3>
                        <p class="text-gray-500 text-sm mb-8 leading-relaxed">Unduh seluruh data sistem saat ini ke dalam format `.sql`. Pastikan Anda melakukan backup secara berkala untuk mencegah kehilangan data akibat kerusakan server atau perubahan yang tidak disengaja.</p>
                        
                        <a href="admin_backup_action.php?action=backup" class="inline-flex items-center justify-center w-full px-6 py-3 bg-emerald-500 text-white rounded-xl font-medium hover:bg-emerald-600 transition-colors shadow-sm">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Unduh File Backup (.sql)
                        </a>
                    </div>
                </div>

                <!-- Restore Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-hidden relative group">
                    <div class="absolute inset-0 bg-gradient-to-br from-rose-50/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="p-8 relative">
                        <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Restore Database</h3>
                        <p class="text-gray-500 text-sm mb-6 leading-relaxed">Pulihkan sistem menggunakan file backup `.sql` sebelumnya. <br><strong class="text-rose-500">Peringatan:</strong> Proses ini akan <u>menimpa</u> seluruh data yang ada saat ini secara permanen!</p>
                        
                        <form id="restoreForm" action="admin_backup_action.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="restore">
                            
                            <div class="border-2 border-dashed border-rose-200 rounded-xl p-4 flex flex-col items-center justify-center hover:bg-rose-50/50 transition-colors cursor-pointer relative bg-white">
                                <input type="file" name="backup_file" id="backup_file" accept=".sql" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <span class="text-sm font-medium text-gray-600" id="fileNameDisplay">Pilih file .sql dari perangkat Anda</span>
                            </div>

                            <button type="button" onclick="confirmRestore()" class="w-full px-6 py-3 bg-rose-500 text-white rounded-xl font-medium hover:bg-rose-600 transition-colors shadow-sm flex justify-center items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                Mulai Proses Restore
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Script for Sidebar and Modals -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }

        // Display selected file name
        document.getElementById('backup_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Pilih file .sql dari perangkat Anda';
            document.getElementById('fileNameDisplay').textContent = fileName;
        });

        // Confirm Restore
        function confirmRestore() {
            const fileInput = document.getElementById('backup_file');
            if(fileInput.files.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'File Diperlukan',
                    text: 'Silakan pilih file .sql terlebih dahulu!',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            Swal.fire({
                title: 'PERINGATAN KRITIS!',
                html: "Tindakan ini akan <b>MENGHAPUS & MENIMPA</b> seluruh data aplikasi saat ini dengan data dari file backup.<br><br>Anda tidak dapat membatalkan tindakan ini!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e3342f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Timpa Data Sekarang!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Mohon tunggu, jangan tutup halaman ini.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading()
                        }
                    });
                    document.getElementById('restoreForm').submit();
                }
            })
        }

        <?php if($status): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= addslashes($status) ?>',
            timer: 3000,
            showConfirmButton: false
        });
        <?php endif; ?>

        <?php if($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '<?= addslashes($error) ?>',
            confirmButtonColor: '#3085d6'
        });
        <?php endif; ?>
    </script>
</body>
</html>
