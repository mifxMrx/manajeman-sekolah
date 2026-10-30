<?php
session_start();
include '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Sesi habis. Silakan login kembali.']));
}

if ($_SESSION['role'] != 'kepsek') {
    die(json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses (Role: ' . $_SESSION['role'] . ')']));
}

$action = $_POST['action'] ?? '';

if ($action == 'save_pengumuman') {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $target_role = mysqli_real_escape_string($conn, $_POST['target_role']);
    $id_pembuat = $_SESSION['user_id'];
    
    if(empty($judul) || empty($isi)) {
        die(json_encode(['success' => false, 'message' => 'Judul dan isi tidak boleh kosong.']));
    }
    
    $q = mysqli_query($conn, "INSERT INTO pengumuman (judul, isi, target_role, id_pembuat) VALUES ('$judul', '$isi', '$target_role', '$id_pembuat')");
    
    if ($q) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
} 
elseif ($action == 'edit_pengumuman') {
    $id = intval($_POST['id']);
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $isi = mysqli_real_escape_string($conn, $_POST['isi']);
    $target_role = mysqli_real_escape_string($conn, $_POST['target_role']);
    
    if(empty($judul) || empty($isi)) {
        die(json_encode(['success' => false, 'message' => 'Judul dan isi tidak boleh kosong.']));
    }
    
    $q = mysqli_query($conn, "UPDATE pengumuman SET judul='$judul', isi='$isi', target_role='$target_role' WHERE id=$id");
    
    if ($q) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
}
elseif ($action == 'delete_pengumuman') {
    $id = intval($_POST['id']);
    $q = mysqli_query($conn, "DELETE FROM pengumuman WHERE id=$id");
    
    if ($q) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
}
elseif ($action == 'get_announcements') {
    $q = mysqli_query($conn, "SELECT * FROM pengumuman ORDER BY tanggal DESC");
    $data = [];
    while($row = mysqli_fetch_assoc($q)) {
        $row['tanggal_format'] = date('d M Y H:i', strtotime($row['tanggal']));
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
}
?>
