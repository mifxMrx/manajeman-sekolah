<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GuruWaliService
{
    /**
     * Mengambil data nilai dan evaluasi bimbingan siswa dari aplikasi Guru Wali (WaliCare).
     * Mendukung Direct Database Query dengan Fallback otomatis ke HTTP REST API.
     *
     * @param \App\Models\Student $student
     * @return array
     */
    public function getNilaiByStudent($student): array
    {
        $nis = trim($student->nisn ?? '');
        $nama = trim($student->name ?? '');

        // 1. Coba ambil langsung dari Database Guru Wali (Koneksi 'guru_wali')
        try {
            $dataFromDb = $this->fetchFromDatabase($nis, $nama);
            if ($dataFromDb !== null) {
                return $dataFromDb;
            }
        } catch (\Throwable $e) {
            Log::info("GuruWaliService: DB query gagal (" . $e->getMessage() . "), mencoba via API...");
        }

        // 2. Fallback: Ambil data melalui HTTP API
        try {
            $dataFromApi = $this->fetchFromApi($nis, $nama);
            if ($dataFromApi !== null) {
                return $dataFromApi;
            }
        } catch (\Throwable $e) {
            Log::warning("GuruWaliService: API request gagal: " . $e->getMessage());
        }

        // 3. Fallback jika tidak ditemukan atau sistem belum sinkron
        return [
            'found' => false,
            'connected' => false,
            'message' => 'Data penilaian dan bimbingan dari Guru Wali belum tersedia untuk siswa ini.',
            'siswa' => null,
            'guru_wali' => null,
            'sekolah' => null,
            'ringkasan' => null,
            'riwayat' => []
        ];
    }

    /**
     * Query langsung ke Database Guru Wali
     */
    protected function fetchFromDatabase(string $nis, string $nama): ?array
    {
        // Query siswa dan wali kelas
        $siswa = DB::connection('guru_wali')
            ->table('siswa as s')
            ->leftJoin('users as u', 's.id_guru_wali', '=', 'u.id')
            ->select(
                's.*',
                'u.nama_lengkap as nama_guru',
                'u.no_telp as telp_guru'
            )
            ->where(function ($query) use ($nis, $nama) {
                if (!empty($nis)) {
                    $query->where('s.nis', $nis);
                }
                if (!empty($nama)) {
                    $query->orWhere('s.nama', 'LIKE', '%' . $nama . '%');
                }
            })
            ->first();

        if (!$siswa) {
            return [
                'found' => false,
                'connected' => true,
                'message' => 'Siswa belum terdaftar pada sistem Guru Wali.',
                'siswa' => null,
                'guru_wali' => null,
                'sekolah' => null,
                'ringkasan' => null,
                'riwayat' => []
            ];
        }

        $siswaId = $siswa->id;

        // Ambil info sekolah
        $settings = DB::connection('guru_wali')
            ->table('pengaturan')
            ->where('id', 1)
            ->first();

        // Ambil riwayat catatan bimbingan / evaluasi
        $catatanRows = DB::connection('guru_wali')
            ->table('catatan_bimbingan as cb')
            ->leftJoin('users as u', 'cb.id_guru', '=', 'u.id')
            ->select('cb.*', 'u.nama_lengkap as nama_pembimbing')
            ->where('cb.id_siswa', $siswaId)
            ->orderBy('cb.tanggal', 'desc')
            ->orderBy('cb.pertemuan', 'desc')
            ->get();

        $riwayatBimbingan = [];
        $totalPertemuan = 0;
        $totalScore = 0;
        $statusMap = ['Baik' => 3, 'Cukup' => 2, 'Kurang' => 1, 'Perlu Perhatian' => 1];

        foreach ($catatanRows as $row) {
            $rowArray = (array) $row;
            $riwayatBimbingan[] = $rowArray;
            $totalPertemuan++;
            $status = $rowArray['status'] ?? 'Baik';
            $totalScore += $statusMap[$status] ?? 3;
        }

        $avgScore = $totalPertemuan > 0 ? round($totalScore / $totalPertemuan) : 3;
        $statusPendampingan = 'Baik';
        $statusLabels = ['Baik' => 3, 'Cukup' => 2, 'Perlu Perhatian' => 1];
        foreach ($statusLabels as $label => $val) {
            if ($val == $avgScore) {
                $statusPendampingan = $label;
                break;
            }
        }

        $catatanTerbaru = !empty($riwayatBimbingan) ? $riwayatBimbingan[0] : null;

        return [
            'found' => true,
            'connected' => true,
            'siswa' => [
                'id' => $siswa->id,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'tingkatan' => $siswa->tingkatan ?? 'A',
                'kelas' => $siswa->kelas,
                'alamat' => $siswa->alamat ?? '',
                'catatan_permanen' => $siswa->catatan_permanen ?? '',
                'potensi_permanen' => $siswa->potensi_permanen ?? '',
                'status_terakhir' => $siswa->status_terakhir ?? ($catatanTerbaru['status'] ?? 'Belum Ada Data'),
            ],
            'guru_wali' => [
                'nama' => $siswa->nama_guru ?? 'Wali Kelas Belum Ditentukan',
                'no_telp' => $siswa->telp_guru ?? '',
            ],
            'sekolah' => [
                'nama' => $settings->nama_sekolah ?? "SMP DARUL MU'MININ CIBATU",
                'alamat' => $settings->alamat_sekolah ?? '',
                'npsn' => $settings->npsn ?? '',
            ],
            'ringkasan' => [
                'total_pertemuan' => $totalPertemuan,
                'status_pendampingan' => $statusPendampingan,
                'tanggal_terakhir' => $catatanTerbaru['tanggal'] ?? null,
                'catatan_terakhir' => $catatanTerbaru['catatan'] ?? null,
                'tindak_lanjut_terakhir' => $catatanTerbaru['tindak_lanjut'] ?? null,
                'catatan_potensi_terakhir' => $catatanTerbaru['catatan_potensi'] ?? null,
                'pelatihan_potensi_terakhir' => $catatanTerbaru['pelatihan_potensi'] ?? 'Tidak',
                'evaluasi_terbaru' => $catatanTerbaru ? [
                    'akademik' => [
                        'membaca' => $catatanTerbaru['akademik_membaca'] ?? 'Baik',
                        'menulis' => $catatanTerbaru['akademik_menulis'] ?? 'Baik',
                        'berhitung' => $catatanTerbaru['akademik_berhitung'] ?? 'Baik',
                        'bacaan_quran' => $catatanTerbaru['akademik_bacaan_quran'] ?? 'Baik',
                        'bacaan_sholat' => $catatanTerbaru['akademik_bacaan_sholat'] ?? 'Baik',
                    ],
                    'karakter' => [
                        'sopan_santun' => $catatanTerbaru['karakter_sopan_santun'] ?? 'Baik',
                        'kedisiplinan' => $catatanTerbaru['karakter_kedisiplinan'] ?? 'Baik',
                        'tanggung_jawab' => $catatanTerbaru['karakter_tanggung_jawab'] ?? 'Baik',
                        'kejujuran' => $catatanTerbaru['karakter_kejujuran'] ?? 'Baik',
                    ],
                    'kepribadian' => [
                        'emosional' => $catatanTerbaru['pribadi_emosional'] ?? 'Baik',
                        'sosial' => $catatanTerbaru['pribadi_sosial'] ?? 'Baik',
                        'kemandirian' => $catatanTerbaru['pribadi_kemandirian'] ?? 'Baik',
                        'interaksi' => $catatanTerbaru['pribadi_interaksi'] ?? 'Baik',
                        'prilaku' => $catatanTerbaru['pribadi_prilaku'] ?? 'Baik',
                    ]
                ] : null,
            ],
            'riwayat' => $riwayatBimbingan
        ];
    }

    /**
     * Ambil data melalui HTTP REST API
     */
    protected function fetchFromApi(string $nis, string $nama): ?array
    {
        $apiUrl = config('services.guru_wali.api_url', env('GURU_WALI_API_URL'));
        if (empty($apiUrl)) {
            return null;
        }

        $response = Http::timeout(3)->get($apiUrl, [
            'nis' => $nis,
            'nama' => $nama
        ]);

        if ($response->successful()) {
            $json = $response->json();
            if (isset($json['status']) && $json['status'] === 'success' && isset($json['data'])) {
                $data = $json['data'];
                $data['connected'] = true;
                return $data;
            } elseif (isset($json['status']) && $json['status'] === 'not_found') {
                return [
                    'found' => false,
                    'connected' => true,
                    'message' => $json['message'] ?? 'Siswa belum terdaftar pada sistem Guru Wali.',
                    'siswa' => null,
                    'guru_wali' => null,
                    'sekolah' => null,
                    'ringkasan' => null,
                    'riwayat' => []
                ];
            }
        }

        return null;
    }
}
