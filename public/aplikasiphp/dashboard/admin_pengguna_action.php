<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'add') {
        $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
        $password = md5($_POST['password']); 
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $tingkatan = ($role == 'guru_wali') ? mysqli_real_escape_string($conn, $_POST['tingkatan']) : 'NULL';
        $tingkatan_val = ($tingkatan == 'NULL') ? "NULL" : "'$tingkatan'";

        $query = "INSERT INTO users (nama_lengkap, username, password, role, tingkatan, no_telp) VALUES ('$nama_lengkap', '$username', '$password', '$role', $tingkatan_val, '$no_telp')";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Pengguna berhasil ditambahkan!";
        } else {
            $_SESSION['swal_error'] = "Gagal menambahkan pengguna: Username mungkin sudah digunakan.";
        }
    } 
    elseif ($action == 'edit') {
        $id = intval($_POST['id']);
        $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $no_telp = mysqli_real_escape_string($conn, $_POST['no_telp']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $tingkatan = ($role == 'guru_wali') ? mysqli_real_escape_string($conn, $_POST['tingkatan']) : 'NULL';
        $tingkatan_sql = ($tingkatan == 'NULL') ? "tingkatan=NULL" : "tingkatan='$tingkatan'";
        
        $password_sql = "";
        if (!empty($_POST['password'])) {
            $password = md5($_POST['password']);
            $password_sql = ", password='$password'";
        }

        $query = "UPDATE users SET nama_lengkap='$nama_lengkap', username='$username', no_telp='$no_telp', role='$role', $tingkatan_sql $password_sql WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Data pengguna berhasil diperbarui!";
        } else {
            $_SESSION['swal_error'] = "Gagal memperbarui pengguna: Username mungkin sudah digunakan.";
        }
    }
    elseif ($action == 'bulk_delete') {
        $ids = json_decode($_POST['ids']);
        if (is_array($ids) && count($ids) > 0) {
            $ids_string = implode(',', array_map('intval', $ids));
            $current_user_id = $_SESSION['user_id'];
            
            // Prevent self-deletion in bulk
            $query = "DELETE FROM users WHERE id IN ($ids_string) AND id != $current_user_id";
            
            if (mysqli_query($conn, $query)) {
                $count = mysqli_affected_rows($conn);
                $_SESSION['swal_success'] = "$count pengguna berhasil dihapus!";
            } else {
                $_SESSION['swal_error'] = "Gagal menghapus beberapa pengguna. (Kemungkinan masih ada data siswa yang terkait)";
            }
        }
    }
    elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        if ($id == $_SESSION['user_id']) {
            $_SESSION['swal_error'] = "Anda tidak dapat menghapus akun Anda sendiri saat sedang login!";
        } else {
            $query = "DELETE FROM users WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $_SESSION['swal_success'] = "Pengguna berhasil dihapus!";
            } else {
                $_SESSION['swal_error'] = "Gagal menghapus pengguna. (Kemungkinan masih ada data siswa yang terkait)";
            }
        }
    }
    elseif ($action == 'import') {
        if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
            $filename = $_FILES['file_csv']['tmp_name'];
            $file = fopen($filename, "r");
            $first_row = true;
            
            while (($data = fgetcsv($file, 10000, ",")) !== FALSE) {
                // Header format: Nama Lengkap, Username, Password, Role
                if ($first_row) {
                    $first_row = false;
                    continue;
                }
                
                if (count($data) >= 4) {
                    $nama_lengkap = mysqli_real_escape_string($conn, $data[0]);
                    $username = mysqli_real_escape_string($conn, $data[1]);
                    $password = md5($data[2]);
                    $role = mysqli_real_escape_string($conn, strtolower(trim($data[3])));
                    $tingkatan = (count($data) >= 5 && $role == 'guru_wali') ? mysqli_real_escape_string($conn, strtoupper(trim($data[4]))) : 'NULL';
                    $no_telp = (count($data) >= 6) ? mysqli_real_escape_string($conn, trim($data[5])) : '';
                    $tingkatan_val = ($tingkatan == 'NULL' || !in_array($tingkatan, ['A', 'B', 'C'])) ? "NULL" : "'$tingkatan'";
                    
                    // Validate role
                    $valid_roles = ['admin', 'guru_wali', 'orang_tua', 'kepsek'];
                    if (!in_array($role, $valid_roles)) $role = 'orang_tua'; // fallback
                    
                    $query = "INSERT INTO users (nama_lengkap, username, password, role, tingkatan, no_telp) VALUES ('$nama_lengkap', '$username', '$password', '$role', $tingkatan_val, '$no_telp') 
                               ON DUPLICATE KEY UPDATE nama_lengkap='$nama_lengkap', role='$role', tingkatan=$tingkatan_val, no_telp='$no_telp'";
                    mysqli_query($conn, $query);
                }
            }
            fclose($file);
            $_SESSION['swal_success'] = "Data pengguna berhasil diimpor!";
        } else {
            $_SESSION['swal_error'] = "Gagal mengunggah file. Pastikan format file adalah CSV.";
        }
    }
    
    header("Location: admin_pengguna.php");
    exit();
}
?>
