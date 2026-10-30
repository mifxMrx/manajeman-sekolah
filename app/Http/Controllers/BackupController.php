<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function index()
    {
        $dbName = config('database.connections.mysql.database');
        
        // Calculate DB size
        $sizeQuery = DB::select('SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS "Size" 
                                 FROM information_schema.TABLES 
                                 WHERE table_schema = ?', [$dbName]);
        $dbSize = count($sizeQuery) > 0 ? $sizeQuery[0]->Size . ' MB' : '0 MB';

        // Get backup files
        $directory = storage_path('app/public');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $files = File::files($directory);
        $backups = [];
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'sql' && str_starts_with($file->getFilename(), 'backup_')) {
                $backups[] = [
                    'name' => $file->getFilename(),
                    'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                    'date' => Carbon::createFromTimestamp($file->getMTime())->locale('id')->isoFormat('D MMMM Y HH:mm') . ' WIB',
                    'timestamp' => $file->getMTime(),
                    'creator' => 'Admin'
                ];
            }
        }

        // Sort backups by timestamp descending
        usort($backups, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $lastBackup = count($backups) > 0 ? $backups[0]['date'] : 'Belum pernah backup';

        return view('admin.backup.index', compact('dbName', 'dbSize', 'lastBackup', 'backups'));
    }

    public function create()
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        
        $filename = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $path = storage_path('app/public/' . $filename);
        
        if (!File::exists(storage_path('app/public'))) {
            File::makeDirectory(storage_path('app/public'), 0755, true);
        }
        
        $mysqldumpPath = $this->getBinaryPath('mysqldump');
        $backupDone = false;

        // Coba backup via mysqldump jika fungsi proc_open tersedia dan file binary ada
        if ($mysqldumpPath && function_exists('proc_open') && @file_exists($mysqldumpPath)) {
            $cmd = [$mysqldumpPath, "-u{$dbUser}"];
            if ($dbPass !== null && $dbPass !== '') {
                $cmd[] = "-p{$dbPass}";
            }
            $cmd[] = "--default-character-set=utf8mb4";
            $cmd[] = $dbName;
            
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['file', $path, 'w'],
                2 => ['pipe', 'w'],
            ];
            
            $process = @proc_open($cmd, $descriptors, $pipes);
            if (is_resource($process)) {
                fclose($pipes[0]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                $returnVar = proc_close($process);

                if ($returnVar === 0 && File::exists($path) && filesize($path) > 0) {
                    $backupDone = true;
                }
            }
        }

        // Fallback: Jika mysqldump tidak ada (misal di aaPanel / error 127) atau proc_open dimatikan
        if (!$backupDone) {
            if (File::exists($path)) {
                @unlink($path);
            }

            $phpBackupError = '';
            $phpBackupSuccess = $this->backupUsingPhp($path, $dbName, $phpBackupError);
            if (!$phpBackupSuccess) {
                if (File::exists($path)) {
                    @unlink($path);
                }
                return back()->with('error', 'Gagal membuat backup database: ' . ($phpBackupError ?: 'Unknown error'));
            }
        }
        
        return back()->with('success', 'Backup database berhasil dibuat.');
    }

    public function downloadFile($filename)
    {
        $path = storage_path('app/public/' . $filename);
        
        if (!File::exists($path) || !str_starts_with($filename, 'backup_')) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }
        
        return response()->download($path);
    }
    
    public function destroy($filename)
    {
        $path = storage_path('app/public/' . $filename);
        
        if (File::exists($path) && str_starts_with($filename, 'backup_')) {
            File::delete($path);
            return back()->with('success', 'File backup berhasil dihapus.');
        }
        
        return back()->with('error', 'File backup tidak ditemukan.');
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file',
        ]);

        $file = $request->file('backup_file');
        $rawContent = file_get_contents($file->getRealPath());

        // Otomatis decompress jika file berupa gzip (.sql.gz)
        if (strlen($rawContent) >= 2 && substr($rawContent, 0, 2) === "\x1f\x8b") {
            $decompressed = @gzdecode($rawContent);
            if ($decompressed !== false) {
                $rawContent = $decompressed;
            }
        }

        // Sanitasi statement aaPanel / mysqldump yang bisa menyebabkan error pada restore lokal
        // 1. Nonaktifkan statement USE `nama_database_lain`; agar selalu masuk ke database target saat ini
        $sanitizedContent = preg_replace('/^\s*USE\s+[`\'"]?[^;`\'"]+[`\'"]?\s*;/mi', '-- [SKIPPED FOR TARGET DB] $0', $rawContent);
        // 2. Nonaktifkan statement CREATE DATABASE agar tidak bentrok
        $sanitizedContent = preg_replace('/^\s*CREATE\s+DATABASE\s+[^;]+;/mi', '-- [SKIPPED CREATE DB] $0', $sanitizedContent);
        // 3. Nonaktifkan GTID_PURGED & SQL_LOG_BIN (sering ada di backup Linux/aaPanel/RDS dan butuh SUPER privilege)
        $sanitizedContent = preg_replace('/SET\s+@@GLOBAL\.GTID_PURGED\s*=[^;]+;/mi', '-- [SKIPPED GTID] $0', $sanitizedContent);
        $sanitizedContent = preg_replace('/SET\s+@@SESSION\.SQL_LOG_BIN\s*=[^;]+;/mi', '-- [SKIPPED LOG_BIN] $0', $sanitizedContent);

        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $tempSqlPath = storage_path('app/temp_restore_' . uniqid() . '.sql');
        file_put_contents($tempSqlPath, $sanitizedContent);

        $mysqlPath = $this->getBinaryPath('mysql');
        $restoreDone = false;
        $stderr = '';

        if ($mysqlPath && function_exists('proc_open') && @file_exists($mysqlPath)) {
            $cmd = [$mysqlPath, "-u{$dbUser}"];
            if ($dbPass !== null && $dbPass !== '') {
                $cmd[] = "-p{$dbPass}";
            }
            $cmd[] = "--default-character-set=utf8mb4";
            $cmd[] = $dbName;

            $descriptors = [
                0 => ['file', $tempSqlPath, 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = @proc_open($cmd, $descriptors, $pipes);
            if (is_resource($process)) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $returnVar = proc_close($process);

                if ($returnVar === 0) {
                    $restoreDone = true;
                }
            }
        }

        @unlink($tempSqlPath);

        // Fallback: Jika mysql CLI tidak ditemukan atau gagal (misal code 127)
        if (!$restoreDone) {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::unprepared($sanitizedContent);
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                $restoreDone = true;
            } catch (\Throwable $e) {
                try { DB::statement('SET FOREIGN_KEY_CHECKS=1;'); } catch (\Throwable $t) {}
                $errMsg = trim($stderr) ?: $e->getMessage();
                return back()->with('error', 'Gagal melakukan restore database: ' . $errMsg);
            }
        }

        return back()->with('success', 'Database berhasil di-restore!');
    }

    /**
     * Cari lokasi binary mysql/mysqldump di Windows maupun aaPanel / Linux
     */
    private function getBinaryPath($binary)
    {
        $possiblePaths = [];
        if ($binary === 'mysqldump') {
            $possiblePaths = [
                'c:\xampp\mysql\bin\mysqldump.exe',
                '/www/server/mysql/bin/mysqldump',   // aaPanel MySQL
                '/www/server/mariadb/bin/mysqldump', // aaPanel MariaDB
                '/usr/bin/mysqldump',
                '/usr/local/bin/mysqldump',
                '/usr/local/mysql/bin/mysqldump',
            ];
        } else {
            $possiblePaths = [
                'c:\xampp\mysql\bin\mysql.exe',
                '/www/server/mysql/bin/mysql',   // aaPanel MySQL
                '/www/server/mariadb/bin/mysql', // aaPanel MariaDB
                '/usr/bin/mysql',
                '/usr/local/bin/mysql',
                '/usr/local/mysql/bin/mysql',
            ];
        }

        foreach ($possiblePaths as $p) {
            if (@file_exists($p)) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Backup Database murni menggunakan PHP & PDO (Tanpa butuh mysqldump / proc_open)
     * Sangat aman dan berjalan di semua server (aaPanel, cPanel, Docker, Shared Hosting)
     */
    private function backupUsingPhp($path, $dbName, &$error = null)
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $handle = fopen($path, 'w');
            if (!$handle) {
                $error = 'Gagal membuat file backup di folder storage.';
                return false;
            }

            fwrite($handle, "-- Backup Database: {$dbName}\n");
            fwrite($handle, "-- Dibuat pada: " . now()->format('Y-m-d H:i:s') . " WIB\n");
            fwrite($handle, "-- Engine: Native PHP Backup Driver (aaPanel / Universal Compatible)\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tables as $t) {
                $tArr = (array)$t;
                $tableName = current($tArr);
                if (empty($tableName)) continue;

                // 1. DROP TABLE
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

                // 2. CREATE TABLE
                $create = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createArr = (array)$create[0];
                $createSql = $createArr['Create Table'] ?? current($createArr);
                fwrite($handle, $createSql . ";\n\n");

                // 3. TABLE DATA
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $firstRow = (array)$rows->first();
                    $columns = array_keys($firstRow);
                    $colList = implode('`, `', $columns);

                    foreach ($rows->chunk(100) as $chunk) {
                        $values = [];
                        foreach ($chunk as $row) {
                            $rowArr = (array)$row;
                            $rowValues = array_map(function ($val) {
                                if ($val === null) return 'NULL';
                                return DB::getPdo()->quote($val);
                            }, $rowArr);
                            $values[] = '(' . implode(', ', $rowValues) . ')';
                        }
                        if (!empty($values)) {
                            fwrite($handle, "INSERT INTO `{$tableName}` (`{$colList}`) VALUES\n" . implode(",\n", $values) . ";\n\n");
                        }
                    }
                }
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
            return true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return false;
        }
    }
}
