<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru_wali') {
    die("Akses ditolak.");
}

$guru_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'add' || $action == 'edit') {
        $id_siswa = intval($_POST['id_siswa']);
        $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
        $pertemuan = intval($_POST['pertemuan']);
        $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
        $tindak_lanjut = mysqli_real_escape_string($conn, $_POST['tindak_lanjut']);

        // Fields
        $fields = [
            'akademik_membaca', 'akademik_menulis', 'akademik_berhitung', 'akademik_bacaan_quran', 'akademik_bacaan_sholat',
            'karakter_sopan_santun', 'karakter_kedisiplinan', 'karakter_tanggung_jawab', 'karakter_kejujuran',
            'pribadi_emosional', 'pribadi_sosial', 'pribadi_kemandirian', 'pribadi_interaksi', 'pribadi_prilaku'
        ];

        $data = [];
        $has_issues = false;

        foreach ($fields as $f) {
            $val = !empty($_POST[$f]) ? $_POST[$f] : 'Baik';
            $data[$f] = mysqli_real_escape_string($conn, $val);
            if ($val == 'Kurang') {
                $has_issues = true;
            }
        }

        $catatan_potensi = mysqli_real_escape_string($conn, $_POST['catatan_potensi'] ?? '');
        $pelatihan_potensi = mysqli_real_escape_string($conn, !empty($_POST['pelatihan_potensi']) ? $_POST['pelatihan_potensi'] : 'Tidak');

        // Automatic Status calculation
        $status = $has_issues ? 'Perlu Perhatian' : 'Baik';

        if ($action == 'add') {
            // Check if student belongs to this guru
            $check = mysqli_query($conn, "SELECT id FROM siswa WHERE id = $id_siswa AND id_guru_wali = $guru_id");
            if (mysqli_num_rows($check) > 0) {
                $query = "INSERT INTO catatan_bimbingan (
                    id_siswa, id_guru, tanggal, pertemuan, catatan, tindak_lanjut, status,
                    akademik_membaca, akademik_menulis, akademik_berhitung, akademik_bacaan_quran, akademik_bacaan_sholat,
                    karakter_sopan_santun, karakter_kedisiplinan, karakter_tanggung_jawab, karakter_kejujuran,
                    pribadi_emosional, pribadi_sosial, pribadi_kemandirian, pribadi_interaksi, pribadi_prilaku,
                    catatan_potensi, pelatihan_potensi
                ) VALUES (
                    $id_siswa, $guru_id, '$tanggal', $pertemuan, '$catatan', '$tindak_lanjut', '$status',
                    '{$data['akademik_membaca']}', '{$data['akademik_menulis']}', '{$data['akademik_berhitung']}', '{$data['akademik_bacaan_quran']}', '{$data['akademik_bacaan_sholat']}',
                    '{$data['karakter_sopan_santun']}', '{$data['karakter_kedisiplinan']}', '{$data['karakter_tanggung_jawab']}', '{$data['karakter_kejujuran']}',
                    '{$data['pribadi_emosional']}', '{$data['pribadi_sosial']}', '{$data['pribadi_kemandirian']}', '{$data['pribadi_interaksi']}', '{$data['pribadi_prilaku']}',
                    '$catatan_potensi', '$pelatihan_potensi'
                )";

                if (mysqli_query($conn, $query)) {
                    $_SESSION['swal_success'] = "Evaluasi berhasil disimpan!";
                } else {
                    $_SESSION['swal_error'] = "Gagal menyimpan ke database: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['swal_error'] = "Validasi Gagal: Siswa tidak ditemukan atau tidak berada dalam pengawasan Anda. (ID: $id_siswa)";
            }
        } elseif ($action == 'edit') {
            $id = intval($_POST['id']);
            $query = "UPDATE catatan_bimbingan SET 
                id_siswa = $id_siswa,
                tanggal = '$tanggal',
                pertemuan = $pertemuan,
                catatan = '$catatan',
                tindak_lanjut = '$tindak_lanjut',
                status = '$status',
                akademik_membaca = '{$data['akademik_membaca']}',
                akademik_menulis = '{$data['akademik_menulis']}',
                akademik_berhitung = '{$data['akademik_berhitung']}',
                akademik_bacaan_quran = '{$data['akademik_bacaan_quran']}',
                akademik_bacaan_sholat = '{$data['akademik_bacaan_sholat']}',
                karakter_sopan_santun = '{$data['karakter_sopan_santun']}',
                karakter_kedisiplinan = '{$data['karakter_kedisiplinan']}',
                karakter_tanggung_jawab = '{$data['karakter_tanggung_jawab']}',
                karakter_kejujuran = '{$data['karakter_kejujuran']}',
                pribadi_emosional = '{$data['pribadi_emosional']}',
                pribadi_sosial = '{$data['pribadi_sosial']}',
                pribadi_kemandirian = '{$data['pribadi_kemandirian']}',
                pribadi_interaksi = '{$data['pribadi_interaksi']}',
                pribadi_prilaku = '{$data['pribadi_prilaku']}',
                catatan_potensi = '$catatan_potensi',
                pelatihan_potensi = '$pelatihan_potensi'
                WHERE id = $id AND id_guru = $guru_id";

            // Check if student belongs to this guru (for security on edit too)
            $check = mysqli_query($conn, "SELECT id FROM siswa WHERE id = $id_siswa AND id_guru_wali = $guru_id");
            if (mysqli_num_rows($check) > 0) {
                if (mysqli_query($conn, $query)) {
                    if (mysqli_affected_rows($conn) >= 0) {
                        $_SESSION['swal_success'] = "Evaluasi berhasil diperbarui!";
                    } else {
                        $_SESSION['swal_success'] = "Evaluasi diperbarui (tidak ada perubahan data).";
                    }
                } else {
                    $_SESSION['swal_error'] = "Gagal memperbarui database: " . mysqli_error($conn);
                }
            } else {
                $_SESSION['swal_error'] = "Gagal: Anda tidak memiliki akses untuk mengaitkan evaluasi ini dengan siswa tersebut.";
            }
        }
    } elseif ($action == 'delete') {
        $id = intval($_POST['id']);
        $query = "DELETE FROM catatan_bimbingan WHERE id = $id AND id_guru = $guru_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['swal_success'] = "Riwayat evaluasi berhasil dihapus!";
        } else {
            $_SESSION['swal_error'] = "Gagal menghapus: " . mysqli_error($conn);
        }
    }
    
    // Save Catatan Permanen if exists (Tingkatan C)
    if (isset($_POST['catatan_permanen']) && isset($_POST['id_siswa'])) {
        $id_siswa = intval($_POST['id_siswa']);
        $catatan_permanen = mysqli_real_escape_string($conn, $_POST['catatan_permanen']);
        mysqli_query($conn, "UPDATE siswa SET catatan_permanen = '$catatan_permanen' WHERE id = $id_siswa");
    }

    // Save Potensi Permanen if exists (Tingkatan A)
    if (isset($_POST['potensi_permanen']) && isset($_POST['id_siswa'])) {
        $id_siswa = intval($_POST['id_siswa']);
        $potensi_permanen = mysqli_real_escape_string($conn, $_POST['potensi_permanen']);
        mysqli_query($conn, "UPDATE siswa SET potensi_permanen = '$potensi_permanen' WHERE id = $id_siswa");
    }
    
    header("Location: guru_bimbingan.php");
    exit();
}
?>
