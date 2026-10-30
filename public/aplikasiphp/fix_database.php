<?php
include 'config.php';

echo "<h2>Database Fix Tool - WaliCare</h2>";

// 1. Fix last_activity column
$check_activity = mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'last_activity'");
if (mysqli_num_rows($check_activity) == 0) {
    echo "Menambahkan kolom 'last_activity'...<br>";
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN last_activity DATETIME DEFAULT CURRENT_TIMESTAMP");
}

// 2. Fix no_telp column (rename from no_wa if exists)
$check_wa = mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'no_wa'");
$check_telp = mysqli_query($conn, "SHOW COLUMNS FROM `users` LIKE 'no_telp'");

if (mysqli_num_rows($check_wa) > 0 && mysqli_num_rows($check_telp) == 0) {
    echo "Mengubah nama kolom 'no_wa' menjadi 'no_telp'...<br>";
    mysqli_query($conn, "ALTER TABLE users CHANGE no_wa no_telp VARCHAR(20)");
} elseif (mysqli_num_rows($check_telp) == 0 && mysqli_num_rows($check_wa) == 0) {
    echo "Menambahkan kolom 'no_telp'...<br>";
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN no_telp VARCHAR(20)");
}

// 3. Fix pengumuman table
$check_pengumuman = mysqli_query($conn, "SHOW TABLES LIKE 'pengumuman'");
if (mysqli_num_rows($check_pengumuman) == 0) {
    echo "Membuat tabel 'pengumuman'...<br>";
    $create_pengumuman = "CREATE TABLE `pengumuman` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `judul` varchar(255) NOT NULL,
        `isi` text NOT NULL,
        `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
        `target_role` enum('admin','guru_wali','orang_tua','kepsek','semua') DEFAULT 'semua',
        `id_pembuat` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create_pengumuman);
    mysqli_query($conn, "INSERT INTO `pengumuman` (`judul`, `isi`, `target_role`) VALUES ('Selamat Datang di WaliCare', 'Gunakan sistem ini untuk memantau perkembangan siswa secara real-time.', 'semua')");
}

// Check id_pembuat column
$check_pembuat = mysqli_query($conn, "SHOW COLUMNS FROM `pengumuman` LIKE 'id_pembuat'");
if (mysqli_num_rows($check_pembuat) == 0) {
    echo "Menambahkan kolom 'id_pembuat' pada tabel pengumuman...<br>";
    mysqli_query($conn, "ALTER TABLE pengumuman ADD COLUMN id_pembuat INT(11) DEFAULT NULL AFTER target_role");
}

// 4. Fix dilihat_ortu column in catatan_bimbingan
$check_dilihat = mysqli_query($conn, "SHOW COLUMNS FROM `catatan_bimbingan` LIKE 'dilihat_ortu'");
if (mysqli_num_rows($check_dilihat) == 0) {
    echo "Menambahkan kolom 'dilihat_ortu' pada tabel penilaian...<br>";
    mysqli_query($conn, "ALTER TABLE catatan_bimbingan ADD COLUMN dilihat_ortu DATETIME DEFAULT NULL");
}

echo "<b style='color:green;'>Selesai!</b> Database sekarang sinkron dengan kode program.<br>";
echo "<br><a href='index.php'>Kembali ke Login</a>";
?>
