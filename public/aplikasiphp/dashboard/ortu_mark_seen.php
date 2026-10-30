<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'orang_tua') {
    die("Akses ditolak.");
}

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // Update dilihat_ortu jika masih NULL
    $query = "UPDATE catatan_bimbingan SET dilihat_ortu = NOW() WHERE id = $id AND dilihat_ortu IS NULL";
    mysqli_query($conn, $query);
    echo "success";
}
?>
