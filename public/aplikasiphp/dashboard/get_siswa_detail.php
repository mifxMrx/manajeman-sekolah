<?php
session_start();
include '../config.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'kepsek') {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Unauthorized']));
}

$type = $_GET['type'] ?? '';
$data = [];

if ($type == 'no_bimbingan') {
    $q = mysqli_query($conn, "
        SELECT s.nama, s.kelas, u.nama_lengkap as nama_guru 
        FROM siswa s 
        LEFT JOIN users u ON s.id_guru_wali = u.id 
        WHERE s.id NOT IN (SELECT id_siswa FROM catatan_bimbingan) 
        ORDER BY s.nama ASC
    ");
    while($row = mysqli_fetch_assoc($q)) {
        $data[] = $row;
    }
} elseif ($type == 'perlu_perhatian') {
    // We respect the date filter if provided from the dashboard session or just show all current issues
    $filter_start = $_GET['start'] ?? '';
    $filter_end = $_GET['end'] ?? '';
    
    $where = " WHERE cb.status = 'Perlu Perhatian'";
    if($filter_start && $filter_end) {
        $where .= " AND cb.tanggal BETWEEN '$filter_start' AND '$filter_end'";
    }
    
    $q = mysqli_query($conn, "
        SELECT s.nama, s.kelas, cb.* 
        FROM catatan_bimbingan cb 
        JOIN siswa s ON cb.id_siswa = s.id 
        $where
        ORDER BY cb.tanggal DESC
    ");
    while($row = mysqli_fetch_assoc($q)) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);
