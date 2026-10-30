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

// Fetch Siswa
$query_siswa = "SELECT id, nis, nama, kelas FROM siswa WHERE id_guru_wali = $guru_id ORDER BY nama ASC";
$res_siswa = mysqli_query($conn, $query_siswa);

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
    <title>Laporan & Ekspor - WaliCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f0f2f5; }
        .sidebar-bg { background-color: #064e3b; }
        .active-menu { background-color: #065f46; border-left: 4px solid #34d399; }
        
        /* Custom DataTables Styling - Minimalist */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 9999px; /* full rounded */
            padding: 8px 16px;
            margin-left: 12px;
            outline: none;
            font-size: 13px;
            transition: all 0.2s;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #5e5ce6;
            box-shadow: 0 0 0 3px rgba(94, 92, 230, 0.1);
        }
        .dt-buttons .dt-button {
            background: white !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 8px 16px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            margin-right: 8px !important;
            transition: all 0.2s !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .dt-buttons .dt-button:hover {
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
            color: #0f172a !important;
        }
        table.dataTable {
            border-collapse: collapse !important;
            margin-top: 20px !important;
            margin-bottom: 20px !important;
        }
        table.dataTable thead th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.dataTable tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 14px;
            vertical-align: middle;
        }
        table.dataTable.no-footer {
            border-bottom: 1px solid #e2e8f0;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 13px;
            outline: none;
        }
        
        /* Dropdown Opsi Button */
        .btn-opsi {
            background-color: white;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-opsi:hover {
            border-color: #5e5ce6;
            color: #5e5ce6;
            background-color: #f5f3ff;
        }
        
        /* Custom Pagination */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            font-size: 13px !important;
            padding: 6px 12px !important;
            border: 1px solid transparent !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #5e5ce6 !important;
            color: white !important;
            border: 1px solid #5e5ce6 !important;
            font-weight: bold !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
            background: #f1f5f9 !important;
            border: 1px solid #e2e8f0 !important;
            color: #0f172a !important;
        }
        .dataTables_info {
            font-size: 13px;
            color: #64748b !important;
        }

        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
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
            <a href="guru_bimbingan.php" class="flex items-center px-4 py-3.5 hover:bg-white/10 rounded-r-xl text-emerald-100 hover:text-white transition-all">
                <svg class="w-5 h-5 mr-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Catatan Bimbingan
            </a>
            <a href="guru_laporan.php" class="flex items-center px-4 py-3.5 active-menu rounded-r-xl text-white font-semibold transition-all">
                <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
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
    <main class="flex-1 flex flex-col h-screen overflow-hidden pt-16 lg:pt-0 bg-[#f8fafc]">
        <div class="flex-1 overflow-y-auto p-6 md:p-10 space-y-8">
            <!-- Header Minimalist -->
            <div class="mb-2">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Laporan & Ekspor</h2>
                <p class="text-sm text-slate-500 mt-1 font-medium">Kelola dan unduh data siswa asuh Anda dengan mudah.</p>
            </div>

            <!-- Table Container -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-8">
                <table id="laporanTable" class="w-full" style="width:100%">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Siswa</th>
                            <th>NIS</th>
                            <th width="15%" class="text-center">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($res_siswa) > 0):
                            while($row = mysqli_fetch_assoc($res_siswa)): 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="font-medium"><?= htmlspecialchars($row['nama']) ?></td>
                            <td><?= htmlspecialchars($row['nis']) ?></td>
                            <td class="text-center">
                                <!-- Dropdown Opsi -->
                                <div class="relative inline-block text-left group">
                                    <button type="button" class="btn-opsi flex items-center justify-center space-x-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span>Opsi</span>
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <!-- Dropdown menu -->
                                    <div class="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-slate-100 z-10 hidden group-hover:block">
                                        <div class="py-1">
                                            <button onclick="showReport(<?= $row['id'] ?>)" class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                Lihat Evaluasi
                                            </button>
                                            <a href="guru_bimbingan.php?search=<?= urlencode($row['nama']) ?>" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                                Edit Data
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-slate-500">Belum ada data siswa asuh.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Report Modal -->
    <div id="reportModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-5xl overflow-hidden shadow-2xl flex flex-col max-h-[95vh]">
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
                    <h2 id="repSchoolName" class="text-2xl font-black text-slate-800 tracking-tighter uppercase"></h2>
                    <p id="repSchoolAddress" class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1"></p>
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

    <!-- jQuery and DataTables Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#laporanTable').DataTable({
                dom: '<"flex flex-wrap justify-between items-center mb-6"<"flex items-center"B><"flex items-center"f>>rt<"flex justify-between items-center mt-4"ip>',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        className: 'dt-button',
                        exportOptions: { columns: [0, 1, 2] }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: 'Pdf',
                        className: 'dt-button',
                        exportOptions: { columns: [0, 1, 2] }
                    },
                    {
                        extend: 'print',
                        text: 'Print',
                        className: 'dt-button',
                        exportOptions: { columns: [0, 1, 2] }
                    },
                    {
                        extend: 'pageLength',
                        text: 'Tampilkan Baris',
                        className: 'dt-button'
                    }
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "_MENU_",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data"
                },
                lengthMenu: [
                    [15, 25, 50, -1],
                    ['15 Baris', '25 Baris', '50 Baris', 'Semua']
                ],
                pageLength: 15
            });
        });

        const reportModal = document.getElementById('reportModal');
        const repTableBody = document.getElementById('repTableBody');

        function showReport(siswaId) {
            fetch(`guru_get_evaluasi.php?siswa_id=${siswaId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    document.getElementById('repNama').innerText = data.siswa.nama;
                    document.getElementById('repNis').innerText = data.siswa.nis;
                    document.getElementById('repKelas').innerText = data.siswa.kelas;
                    
                    // Dynamic School Info
                    document.getElementById('repSchoolName').innerText = data.settings.nama_sekolah;
                    document.getElementById('repSchoolAddress').innerText = data.settings.alamat_sekolah;
                    
                    // Determine latest status
                    let latestStatus = '-';
                    if(data.evaluations.length > 0) {
                        latestStatus = data.evaluations[0].status;
                    }
                    document.getElementById('repStatus').innerText = latestStatus;

                    let html = '';
                    if(data.evaluations.length === 0) {
                        html = '<tr><td colspan="5" class="p-10 text-center text-slate-400 font-medium italic">Belum ada riwayat evaluasi untuk siswa ini.</td></tr>';
                    } else {
                        data.evaluations.forEach(ev => {
                            const categories = [];
                            const isTingkatanA = data.siswa.tingkatan === 'A';

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
                                            { label: (data.siswa.tingkatan === 'B' || data.siswa.tingkatan === 'C') ? 'Penampilan' : 'Kedisiplinan', key: 'karakter_kedisiplinan' },
                                            { label: (data.siswa.tingkatan === 'B' || data.siswa.tingkatan === 'C') ? 'Interaksi Sosial' : 'Tanggung Jawab', key: 'karakter_tanggung_jawab' },
                                            { label: 'Kejujuran', key: 'karakter_kejujuran' }
                                        ]
                                    }
                                );

                                if (data.siswa.tingkatan !== 'B' && data.siswa.tingkatan !== 'C') {
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

                            html += `
                                <tr class="border-b-2 border-slate-800">
                                    <td class="border-r border-slate-800 p-3 text-center align-top">
                                        <div class="font-bold">${ev.tanggal}</div>
                                        <div class="mt-2 text-[8px] uppercase tracking-tighter">
                                            ${ev.dilihat_ortu ? 
                                                `<span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100 flex items-center justify-center">
                                                    <svg class="w-2 h-2 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    DILIHAT
                                                 </span>` : 
                                                `<span class="text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100 flex items-center justify-center">
                                                    BELUM DICEK
                                                 </span>`
                                            }
                                        </div>
                                    </td>
                                    <td class="border-r border-slate-800 p-3 text-center align-top">Ke-${ev.pertemuan}</td>
                                    <td colspan="2" class="p-0 align-top border-r border-slate-800">
                                        <table class="w-full">
                            `;

                            categories.forEach(cat => {
                                html += `
                                    <tr class="bg-slate-50 border-b border-slate-800">
                                        <td colspan="2" class="p-2 font-black uppercase text-[9px]">${cat.title}</td>
                                    </tr>
                                `;
                                cat.fields.forEach(f => {
                                    html += `
                                        <tr class="border-b border-slate-800 last:border-0">
                                            <td class="p-2 pl-4 border-r border-slate-800">${f.label}</td>
                                            <td class="p-2 w-28 text-center font-bold">${ev[f.key]}</td>
                                        </tr>
                                    `;
                                });
                            });

                            html += `
                                        </table>
                                    </td>
                                    <td class="p-4 align-top leading-relaxed">
                                        ${isTingkatanA ? `
                                            <p class="font-black mb-1 uppercase text-slate-800 text-[9px]">Potensi:</p>
                                            <p class="mb-3 text-indigo-700 font-bold">${ev.catatan_potensi || '-'}</p>
                                            <p class="font-black mb-1 uppercase text-slate-800 text-[9px]">Latih Potensi:</p>
                                            <p class="mb-5 font-bold text-slate-600">${ev.pelatihan_potensi === 'Ya' ? 'YA (REKOMENDASI)' : 'TIDAK'}</p>
                                        ` : ''}
                                        <p class="font-black mb-2 uppercase text-slate-800">Catatan:</p>
                                        <p class="mb-6">${ev.catatan}</p>
                                        <p class="font-black mb-2 uppercase text-slate-800">Follow-up:</p>
                                        <p>${ev.tindak_lanjut ?? '-'}</p>
                                    </td>
                                </tr>
                            `;
                        });
                    }

                    repTableBody.innerHTML = html;
                    reportModal.classList.remove('hidden');
                });
        }

        function closeReport() {
            reportModal.classList.add('hidden');
        }
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
