<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru_wali') {
    die("Akses ditolak.");
}

$guru_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'add') {
        $_SESSION['swal_error'] = "Akses ditolak. Hanya Admin yang dapat menambah siswa.";
    } 
    elseif ($action == 'edit') {
        $id = intval($_POST['id']);
        $nis = mysqli_real_escape_string($conn, $_POST['nis']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $tingkatan = mysqli_real_escape_string($conn, $_POST['tingkatan']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);

        // Pastikan guru hanya bisa mengedit siswanya sendiri
        $query = "UPDATE siswa SET nis='$nis', nama='$nama', tingkatan='$tingkatan', kelas='$kelas', alamat='$alamat' WHERE id=$id AND id_guru_wali=$guru_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Data siswa berhasil diperbarui!";
        } else {
            $_SESSION['swal_error'] = "Gagal memperbarui siswa: " . mysqli_error($conn);
        }
    } 
    elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        // Pastikan guru hanya bisa menghapus siswanya sendiri
        $query = "DELETE FROM siswa WHERE id=$id AND id_guru_wali=$guru_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Data siswa berhasil dihapus!";
        } else {
            $_SESSION['swal_error'] = "Gagal menghapus siswa: " . mysqli_error($conn);
        }
    }
    elseif ($action == 'transfer') {
        $id = intval($_POST['id']);
        $id_guru_wali_baru = intval($_POST['id_guru_wali_baru']);
        $tingkatan_baru = mysqli_real_escape_string($conn, $_POST['tingkatan_baru']);

        // Pastikan guru hanya bisa mentransfer siswanya sendiri
        $query = "UPDATE siswa SET id_guru_wali=$id_guru_wali_baru, tingkatan='$tingkatan_baru' WHERE id=$id AND id_guru_wali=$guru_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Siswa berhasil ditransfer ke Guru Wali lain!";
        } else {
            $_SESSION['swal_error'] = "Gagal mentransfer siswa: " . mysqli_error($conn);
        }
    }
    
    header("Location: guru_siswa.php");
    exit();
}
?>
