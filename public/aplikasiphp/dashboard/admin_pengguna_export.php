<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

$role = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : 'orang_tua';

// Set header untuk download file Excel/CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Data_Akun_'.ucfirst($role).'_'.date('Y-m-d').'.csv');

// Buka stream untuk menulis file
$output = fopen('php://output', 'w');

// Header kolom
if ($role == 'orang_tua') {
    fputcsv($output, ['Nama Orang Tua', 'Username Login', 'Nomor Telepon', 'Nama Anak']);
    
    // Query data orang tua beserta nama anaknya
    $query = "
        SELECT u.nama_lengkap, u.username, u.no_telp, 
               (SELECT GROUP_CONCAT(nama SEPARATOR ', ') FROM siswa WHERE id_orang_tua = u.id) as nama_anak
        FROM users u 
        WHERE u.role = 'orang_tua'
        ORDER BY u.nama_lengkap ASC
    ";
} else {
    fputcsv($output, ['Nama Lengkap', 'Username Login', 'Nomor Telepon', 'Peran']);
    $query = "SELECT nama_lengkap, username, no_telp, role FROM users WHERE role = '$role' ORDER BY nama_lengkap ASC";
}

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
