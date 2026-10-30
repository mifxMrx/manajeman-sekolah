<?php
session_start();
include '../config.php';

// Tingkatkan batas eksekusi script dan memori untuk database besar
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == 'backup') {
    // 1. Ambil daftar semua tabel di database
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    $sqlScript = "-- Backup Database WaliCare\n";
    $sqlScript .= "-- Waktu Backup: " . date("Y-m-d H:i:s") . "\n\n";
    $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // 2. Loop melalui setiap tabel
    foreach ($tables as $table) {
        // Ambil struktur tabel
        $result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
        $row = mysqli_fetch_row($result);
        
        $sqlScript .= "\n-- Struktur tabel untuk `$table`\n";
        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlScript .= $row[1] . ";\n\n";

        // Ambil data dari tabel
        $result = mysqli_query($conn, "SELECT * FROM `$table`");
        $columnCount = mysqli_num_fields($result);
        
        $rowCount = mysqli_num_rows($result);
        if ($rowCount > 0) {
            $sqlScript .= "-- Data untuk tabel `$table`\n";
            while ($row = mysqli_fetch_row($result)) {
                $sqlScript .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $columnCount; $j++) {
                    $row[$j] = $row[$j];
                    if (isset($row[$j])) {
                        $row[$j] = mysqli_real_escape_string($conn, $row[$j]);
                        // Jangan gunakan escape karakter new line jika itu string, biarkan escape handle itu
                        $sqlScript .= "'" . $row[$j] . "'";
                    } else {
                        $sqlScript .= "NULL";
                    }
                    if ($j < ($columnCount - 1)) {
                        $sqlScript .= ",";
                    }
                }
                $sqlScript .= ");\n";
            }
            $sqlScript .= "\n";
        }
    }

    $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // 3. Force download file
    $backup_file_name = 'backup_walicare_' . date("Ymd_His") . '.sql';
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($backup_file_name) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($sqlScript));
    
    // Matikan output buffering jika aktif
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    echo $sqlScript;
    exit();

} elseif ($action == 'restore') {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
        $filename = $_FILES['backup_file']['tmp_name'];
        $ext = pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION);
        
        if (strtolower($ext) !== 'sql') {
            $_SESSION['swal_error'] = "File harus berformat .sql";
            header("Location: admin_backup.php");
            exit();
        }

        // Baca seluruh isi file
        $sql = file_get_contents($filename);
        
        // Hapus komentar dari file SQL untuk menghindari error pada delimiter parsing
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/^\s*$/m', '', $sql);
        
        // Nonaktifkan pemeriksaan kunci asing sementara
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");
        
        // Jika file terlalu besar untuk `mysqli_multi_query`, kita pecah per statement
        // Ini adalah cara yang lebih aman untuk mencegah 'MySQL server has gone away'
        $statements = explode(';', $sql);
        $success = true;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                if (!mysqli_query($conn, $statement)) {
                    $success = false;
                    $error_msg = mysqli_error($conn);
                    break;
                }
            }
        }
        
        // Aktifkan kembali pemeriksaan kunci asing
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");

        if ($success) {
            $_SESSION['swal_success'] = "Database berhasil dipulihkan dari file backup!";
        } else {
            $_SESSION['swal_error'] = "Terjadi kesalahan saat memulihkan: " . $error_msg;
        }

    } else {
        $_SESSION['swal_error'] = "Gagal mengunggah file backup.";
    }
    header("Location: admin_backup.php");
    exit();
} else {
    header("Location: admin_backup.php");
    exit();
}
?>
