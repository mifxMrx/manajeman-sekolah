<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); // Menggunakan MD5 untuk demo, di produksi gunakan password_hash()

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
        $_SESSION['tingkatan'] = $row['tingkatan'];

        // Redirect based on role
        if ($row['role'] == 'admin') {
            header("Location: dashboard/admin.php");
        } else if ($row['role'] == 'guru_wali') {
            header("Location: dashboard/guru.php");
        } else if ($row['role'] == 'orang_tua') {
            header("Location: dashboard/ortu.php");
        } else if ($row['role'] == 'kepsek') {
            header("Location: dashboard/kepsek.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Username atau password salah!";
        header("Location: index.php");
        exit();
    }
}
?>
