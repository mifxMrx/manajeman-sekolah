<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'add') {
        $nis = mysqli_real_escape_string($conn, $_POST['nis']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $tingkatan = mysqli_real_escape_string($conn, $_POST['tingkatan']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        $id_guru_wali = empty($_POST['id_guru_wali']) ? 'NULL' : intval($_POST['id_guru_wali']);
        $id_orang_tua = empty($_POST['id_orang_tua']) ? 'NULL' : intval($_POST['id_orang_tua']);

        // Check for duplicate NIS
        $check_nis = mysqli_query($conn, "SELECT id FROM siswa WHERE nis = '$nis'");
        if (mysqli_num_rows($check_nis) > 0) {
            $_SESSION['swal_error'] = "Gagal: NIS '$nis' sudah terdaftar dalam sistem!";
            header("Location: admin_siswa.php");
            exit();
        }

        $query = "INSERT INTO siswa (nis, nama, tingkatan, kelas, id_guru_wali, id_orang_tua) VALUES ('$nis', '$nama', '$tingkatan', '$kelas', $id_guru_wali, $id_orang_tua)";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Siswa berhasil ditambahkan!";
        } else {
            $_SESSION['swal_error'] = "Gagal menambahkan siswa: " . mysqli_error($conn);
        }
    } 
    elseif ($action == 'edit') {
        $id = intval($_POST['id']);
        $nis = mysqli_real_escape_string($conn, $_POST['nis']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $tingkatan = mysqli_real_escape_string($conn, $_POST['tingkatan']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        $id_guru_wali = empty($_POST['id_guru_wali']) ? 'NULL' : intval($_POST['id_guru_wali']);
        $id_orang_tua = empty($_POST['id_orang_tua']) ? 'NULL' : intval($_POST['id_orang_tua']);

        // Check for duplicate NIS (excluding current ID)
        $check_nis = mysqli_query($conn, "SELECT id FROM siswa WHERE nis = '$nis' AND id != $id");
        if (mysqli_num_rows($check_nis) > 0) {
            $_SESSION['swal_error'] = "Gagal: NIS '$nis' sudah digunakan oleh siswa lain!";
            header("Location: admin_siswa.php");
            exit();
        }

        $query = "UPDATE siswa SET nis='$nis', nama='$nama', tingkatan='$tingkatan', kelas='$kelas', id_guru_wali=$id_guru_wali, id_orang_tua=$id_orang_tua WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Data siswa berhasil diperbarui!";
        } else {
            $_SESSION['swal_error'] = "Gagal memperbarui siswa: " . mysqli_error($conn);
        }
    } 
    elseif ($action == 'bulk_delete') {
        $ids = json_decode($_POST['ids']);
        if (is_array($ids) && count($ids) > 0) {
            $ids_string = implode(',', array_map('intval', $ids));
            $query = "DELETE FROM siswa WHERE id IN ($ids_string)";
            if (mysqli_query($conn, $query)) {
                $count = mysqli_affected_rows($conn);
                $_SESSION['swal_success'] = "$count data siswa berhasil dihapus!";
            } else {
                $_SESSION['swal_error'] = "Gagal menghapus beberapa data siswa.";
            }
        }
    }
    elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        $query = "DELETE FROM siswa WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Data siswa berhasil dihapus!";
        } else {
            $_SESSION['swal_error'] = "Gagal menghapus siswa: " . mysqli_error($conn);
        }
    }
    elseif ($action == 'import') {
        if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
            $filename = $_FILES['file_csv']['tmp_name'];
            $file = fopen($filename, "r");
            $first_row = true;
            
            $inserted = 0;
            $skipped = 0;
            
            while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
                // Header format: NIS, Nama, Tingkatan, Kelas, Username Guru, Username Ortu
                if ($first_row) {
                    $first_row = false;
                    continue;
                }
                
                if (count($data) >= 4) {
                    $nis = mysqli_real_escape_string($conn, $data[0]);
                    $nama = mysqli_real_escape_string($conn, $data[1]);
                    $tingkatan = mysqli_real_escape_string($conn, strtoupper(trim($data[2])));
                    $kelas = mysqli_real_escape_string($conn, $data[3]);
                    
                    if (!in_array($tingkatan, ['A', 'B', 'C'])) $tingkatan = 'A';
                    
                    // Check if NIS already exists
                    $check = mysqli_query($conn, "SELECT id FROM siswa WHERE nis = '$nis'");
                    if (mysqli_num_rows($check) > 0) {
                        $skipped++;
                        continue;
                    }

                    $id_guru_wali = 'NULL';
                    $id_orang_tua = 'NULL';

                    // Find Guru by Username
                    if (isset($data[4]) && !empty(trim($data[4]))) {
                        $uname_guru = mysqli_real_escape_string($conn, trim($data[4]));
                        $q_guru = mysqli_query($conn, "SELECT id FROM users WHERE username='$uname_guru' AND role='guru_wali' LIMIT 1");
                        if ($r_guru = mysqli_fetch_assoc($q_guru)) {
                            $id_guru_wali = $r_guru['id'];
                        }
                    }

                    // Find Ortu by Username
                    if (isset($data[5]) && !empty(trim($data[5]))) {
                        $uname_ortu = mysqli_real_escape_string($conn, trim($data[5]));
                        $q_ortu = mysqli_query($conn, "SELECT id FROM users WHERE username='$uname_ortu' AND role='orang_tua' LIMIT 1");
                        if ($r_ortu = mysqli_fetch_assoc($q_ortu)) {
                            $id_orang_tua = $r_ortu['id'];
                        }
                    }

                    $query = "INSERT INTO siswa (nis, nama, tingkatan, kelas, id_guru_wali, id_orang_tua) 
                              VALUES ('$nis', '$nama', '$tingkatan', '$kelas', $id_guru_wali, $id_orang_tua)";
                    if (mysqli_query($conn, $query)) {
                        $inserted++;
                    }
                }
            }
            fclose($file);
            
            if ($skipped > 0) {
                $_SESSION['swal_success'] = "$inserted data berhasil diimpor. $skipped data dilewati karena NIS sudah ada.";
            } else {
                $_SESSION['swal_success'] = "$inserted data siswa berhasil diimpor!";
            }
        } else {
            $_SESSION['swal_error'] = "Gagal mengunggah file. Pastikan format file adalah CSV.";
        }
    }
    
    header("Location: admin_siswa.php");
    exit();
}
?>
