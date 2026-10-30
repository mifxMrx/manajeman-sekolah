<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_aplikasi = mysqli_real_escape_string($conn, $_POST['nama_aplikasi']);
    $nama_sekolah = mysqli_real_escape_string($conn, $_POST['nama_sekolah']);
    $alamat_sekolah = mysqli_real_escape_string($conn, $_POST['alamat_sekolah']);
    $npsn = mysqli_real_escape_string($conn, $_POST['npsn']);

    $query = "UPDATE pengaturan SET nama_aplikasi='$nama_aplikasi', nama_sekolah='$nama_sekolah', alamat_sekolah='$alamat_sekolah', npsn='$npsn' WHERE id=1";
    if (mysqli_query($conn, $query)) {
        $_SESSION['swal_success'] = "Pengaturan berhasil disimpan!";
    } else {
        $_SESSION['swal_error'] = "Gagal menyimpan pengaturan: " . mysqli_error($conn);
    }
    
    header("Location: admin_pengaturan.php");
    exit();
}
?>
