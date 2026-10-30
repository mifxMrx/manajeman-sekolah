<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru_wali') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['siswa_id'])) {
    $siswa_id = (int)$_GET['siswa_id'];
    
    // Fetch Student Info
    $q_siswa = mysqli_query($conn, "SELECT nama, nis, kelas, tingkatan, catatan_permanen FROM siswa WHERE id = $siswa_id");
    $siswa = mysqli_fetch_assoc($q_siswa);
    
    if (!$siswa) {
        echo json_encode(['error' => 'Siswa not found']);
        exit();
    }
    
    // Fetch Evaluations
    $q_eval = mysqli_query($conn, "SELECT * FROM catatan_bimbingan WHERE id_siswa = $siswa_id ORDER BY tanggal DESC");
    $evaluations = [];
    while ($row = mysqli_fetch_assoc($q_eval)) {
        $evaluations[] = $row;
    }

    // Fetch School Settings
    $q_set = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1");
    $settings = mysqli_fetch_assoc($q_set);
    if (!$settings) {
        $settings = ['nama_sekolah' => 'SMA Negeri 1 Nusantara', 'alamat_sekolah' => 'Jl. Pendidikan No. 123', 'npsn' => '-'];
    }
    
    echo json_encode([
        'siswa' => $siswa,
        'evaluations' => $evaluations,
        'settings' => $settings
    ]);
} else {
    echo json_encode(['error' => 'No student ID']);
}
?>
