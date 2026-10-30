<?php
/**
 * API Endpoint: Data Nilai & Evaluasi Siswa dari Guru Wali (WaliCare)
 * Endpoint ini menyediakan data evaluasi perkembangan, nilai akademik, karakter,
 * dan catatan bimbingan siswa untuk ditampilkan pada Portal Orang Tua / Web Sekolah (SISKA).
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';

// Ambil parameter NIS / NISN atau Nama
$nis = isset($_REQUEST['nis']) ? trim($_REQUEST['nis']) : (isset($_REQUEST['nisn']) ? trim($_REQUEST['nisn']) : '');
$nama = isset($_REQUEST['nama']) ? trim($_REQUEST['nama']) : (isset($_REQUEST['name']) ? trim($_REQUEST['name']) : '');

if (empty($nis) && empty($nama)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter nis/nisn atau nama harus disertakan.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

$safe_nis = mysqli_real_escape_string($conn, $nis);
$safe_nama = mysqli_real_escape_string($conn, $nama);

// Query data siswa beserta guru walinya
$where_clauses = [];
if (!empty($safe_nis)) {
    $where_clauses[] = "s.nis = '$safe_nis'";
}
if (!empty($safe_nama)) {
    $where_clauses[] = "s.nama LIKE '%$safe_nama%'";
}
$where_sql = implode(' OR ', $where_clauses);

$query_siswa = "
    SELECT 
        s.*, 
        u.nama_lengkap AS nama_guru, 
        u.no_telp AS telp_guru 
    FROM siswa s 
    LEFT JOIN users u ON s.id_guru_wali = u.id 
    WHERE $where_sql 
    LIMIT 1
";

$res_siswa = mysqli_query($conn, $query_siswa);

if (!$res_siswa || mysqli_num_rows($res_siswa) === 0) {
    http_response_code(404);
    echo json_encode([
        'status' => 'not_found',
        'message' => 'Data siswa tidak ditemukan di sistem Guru Wali.',
        'data' => [
            'found' => false
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

$siswa = mysqli_fetch_assoc($res_siswa);
$siswa_id = intval($siswa['id']);

// Ambil informasi pengaturan sekolah
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id = 1 LIMIT 1");
$settings = ($q_set && mysqli_num_rows($q_set) > 0) ? mysqli_fetch_assoc($q_set) : [
    'nama_sekolah' => "SMP DARUL MU'MININ CIBATU",
    'alamat_sekolah' => 'Jln. Taman Pahlawan No. 414',
    'npsn' => '-'
];

// Ambil semua riwayat catatan bimbingan siswa ini
$query_catatan = "
    SELECT cb.*, u.nama_lengkap AS nama_pembimbing 
    FROM catatan_bimbingan cb 
    LEFT JOIN users u ON cb.id_guru = u.id 
    WHERE cb.id_siswa = $siswa_id 
    ORDER BY cb.tanggal DESC, cb.pertemuan DESC
";
$res_catatan = mysqli_query($conn, $query_catatan);

$riwayat_bimbingan = [];
$total_pertemuan = 0;
$status_counts = ['Baik' => 0, 'Cukup' => 0, 'Kurang' => 0, 'Perlu Perhatian' => 0];
$status_map = ['Baik' => 3, 'Cukup' => 2, 'Kurang' => 1, 'Perlu Perhatian' => 1];
$total_score = 0;

while ($row = mysqli_fetch_assoc($res_catatan)) {
    $riwayat_bimbingan[] = $row;
    $total_pertemuan++;
    $st = $row['status'] ?? 'Baik';
    if (isset($status_counts[$st])) {
        $status_counts[$st]++;
    }
    $total_score += $status_map[$st] ?? 3;
}

// Hitung status pendampingan keseluruhan
$avg_score = $total_pertemuan > 0 ? round($total_score / $total_pertemuan) : 3;
$status_pendampingan = 'Baik';
$status_labels = ['Baik' => 3, 'Cukup' => 2, 'Perlu Perhatian' => 1];
foreach ($status_labels as $label => $val) {
    if ($val == $avg_score) {
        $status_pendampingan = $label;
        break;
    }
}

// Catatan bimbingan terakhir
$catatan_terbaru = !empty($riwayat_bimbingan) ? $riwayat_bimbingan[0] : null;

// Susun respon JSON
$response = [
    'status' => 'success',
    'message' => 'Data nilai dan evaluasi siswa berhasil diambil.',
    'data' => [
        'found' => true,
        'siswa' => [
            'id' => $siswa['id'],
            'nis' => $siswa['nis'],
            'nama' => $siswa['nama'],
            'tingkatan' => $siswa['tingkatan'] ?? 'A',
            'kelas' => $siswa['kelas'],
            'alamat' => $siswa['alamat'] ?? '',
            'catatan_permanen' => $siswa['catatan_permanen'] ?? '',
            'potensi_permanen' => $siswa['potensi_permanen'] ?? '',
            'status_terakhir' => $siswa['status_terakhir'] ?? ($catatan_terbaru['status'] ?? 'Belum Ada Data'),
        ],
        'guru_wali' => [
            'nama' => $siswa['nama_guru'] ?? 'Wali Kelas Belum Ditentukan',
            'no_telp' => $siswa['telp_guru'] ?? '',
        ],
        'sekolah' => [
            'nama' => $settings['nama_sekolah'] ?? "SMP DARUL MU'MININ CIBATU",
            'alamat' => $settings['alamat_sekolah'] ?? '',
            'npsn' => $settings['npsn'] ?? '',
        ],
        'ringkasan' => [
            'total_pertemuan' => $total_pertemuan,
            'status_pendampingan' => $status_pendampingan,
            'tanggal_terakhir' => $catatan_terbaru ? $catatan_terbaru['tanggal'] : null,
            'catatan_terakhir' => $catatan_terbaru ? $catatan_terbaru['catatan'] : null,
            'tindak_lanjut_terakhir' => $catatan_terbaru ? $catatan_terbaru['tindak_lanjut'] : null,
            'catatan_potensi_terakhir' => $catatan_terbaru ? $catatan_terbaru['catatan_potensi'] : null,
            'pelatihan_potensi_terakhir' => $catatan_terbaru ? $catatan_terbaru['pelatihan_potensi'] : 'Tidak',
            'evaluasi_terbaru' => $catatan_terbaru ? [
                'akademik' => [
                    'membaca' => $catatan_terbaru['akademik_membaca'] ?? 'Baik',
                    'menulis' => $catatan_terbaru['akademik_menulis'] ?? 'Baik',
                    'berhitung' => $catatan_terbaru['akademik_berhitung'] ?? 'Baik',
                    'bacaan_quran' => $catatan_terbaru['akademik_bacaan_quran'] ?? 'Baik',
                    'bacaan_sholat' => $catatan_terbaru['akademik_bacaan_sholat'] ?? 'Baik',
                ],
                'karakter' => [
                    'sopan_santun' => $catatan_terbaru['karakter_sopan_santun'] ?? 'Baik',
                    'kedisiplinan' => $catatan_terbaru['karakter_kedisiplinan'] ?? 'Baik',
                    'tanggung_jawab' => $catatan_terbaru['karakter_tanggung_jawab'] ?? 'Baik',
                    'kejujuran' => $catatan_terbaru['karakter_kejujuran'] ?? 'Baik',
                ],
                'kepribadian' => [
                    'emosional' => $catatan_terbaru['pribadi_emosional'] ?? 'Baik',
                    'sosial' => $catatan_terbaru['pribadi_sosial'] ?? 'Baik',
                    'kemandirian' => $catatan_terbaru['pribadi_kemandirian'] ?? 'Baik',
                    'interaksi' => $catatan_terbaru['pribadi_interaksi'] ?? 'Baik',
                    'prilaku' => $catatan_terbaru['pribadi_prilaku'] ?? 'Baik',
                ]
            ] : null,
        ],
        'riwayat' => $riwayat_bimbingan
    ]
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
