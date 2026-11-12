<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Backup database MySQL ke direktori eksternal terkonfigurasi.
 *
 * Prinsip:
 * - Tidak pernah menulis backup ke dalam direktori proyek atau publikk.
 * - Tidak melakukan apa pun bila tujuan backup tidak dikonfigurasi/valid.
 * - Tidak membacanya lewat .env; kredensial dibaca dari config database koneksi default
 *   hanya untuk diteruskan ke mysqldump lewat MYSQL_PWD (tidak pernah dicetak).
 * - Tidak menghapus backup lama kecuali diminta eksplisit lewat --retensi-hari >= 1.
 */
class BackupDatabase extends Command
{
    protected $signature = 'siakad:backup-db
                            {--path= : Direktori tujuan backup (default: config("siakad.backup.path"))}
                            {--retensi-hari= : Hapus backup siakad-*.sql lebih lama dari N hari (default 0 = tidak menghapus)}';

    protected $description = 'Backup database MySQL ke direktori eksternal yang terkonfigurasi';

    public function handle(): int
    {
        $path = $this->option('path') ?: config('siakad.backup.path');

        if (! is_string($path) || trim($path) === '') {
            $this->error('Tujuan backup belum dikonfigurasi (config("siakad.backup.path") atau opsi --path).');
            Log::warning('siakad:backup-db dibatalkan: tujuan backup tidak dikonfigurasi.');

            return self::FAILURE;
        }

        $path = rtrim($path, '\\/');

        if (! is_dir($path) || ! is_writable($path)) {
            $this->error('Direktori tujuan backup tidak ada atau tidak bisa ditulis.');
            Log::warning('siakad:backup-db dibatalkan: direktori tujuan tidak valid.');

            return self::FAILURE;
        }

        if ($this->pathInsideProject($path)) {
            $this->error('Direktori tujuan berada di dalam direktori proyek; ditolak demi keamanan artefak.');
            Log::warning('siakad:backup-db dibatalkan: direktori tujuan berada di dalam proyek.');

            return self::FAILURE;
        }

        $mysqldump = $this->locateMysqldump();

        if ($mysqldump === null) {
            $this->error('mysqldump tidak ditemukan di PATH. Install MySQL client tools atau isi config("siakad.backup.mysqldump").');
            Log::warning('siakad:backup-db dibatalkan: mysqldump tidak ditemukan.');

            return self::FAILURE;
        }

        $conn = (array) config('database.connections.mysql', []);
        $database = (string) ($conn['database'] ?? '');

        if ($database === '') {
            $this->error('Nama database tidak diketahui dari konfigurasi.');
            Log::warning('siakad:backup-db dibatalkan: database tidak dikonfigurasi.');

            return self::FAILURE;
        }

        $namaFile = 'siakad-'.now()->format('Ymd-His').'.sql';
        $target = $path.DIRECTORY_SEPARATOR.$namaFile;

        $mulai = microtime(true);
        $handle = fopen($target, 'wb');

        if ($handle === false) {
            $this->error('Tidak dapat membuat file backup di direktori tujuan.');
            Log::warning('siakad:backup-db dibatalkan: gagal membuat file tujuan.');

            return self::FAILURE;
        }

        $process = new Process([
            $mysqldump,
            '--single-transaction',
            '--skip-lock-tables',
            '--quick',
            '-h', (string) ($conn['host'] ?? '127.0.0.1'),
            '-P', (string) ($conn['port'] ?? '3306'),
            '-u', (string) ($conn['username'] ?? 'root'),
            $database,
        ], null, array_merge($_SERVER, ['MYSQL_PWD' => (string) ($conn['password'] ?? '')]), null, 600);

        $process->run(function ($type, $buffer) use ($handle) {
            if ($type === Process::OUT && is_resource($handle)) {
                fwrite($handle, $buffer);
            }
        });

        fclose($handle);

        $exitCode = $process->getExitCode();
        $size = is_file($target) ? (int) filesize($target) : 0;

        if ($exitCode !== 0 || ! is_file($target) || $size < 1024) {
            @unlink($target);
            Log::error('siakad:backup-db: backup gagal.', ['exit_code' => $exitCode, 'size' => $size]);
            $this->error('Backup gagal (exit code '.$exitCode.'). Detail tidak ditampilkan; periksa log.');

            return self::FAILURE;
        }

        $hash = substr((string) hash_file('sha256', $target), 0, 16);
        $durasi = (int) ((microtime(true) - $mulai) * 1000);

        Log::info('siakad:backup-db: backup berhasil.', [
            'file' => $namaFile,
            'size' => $size,
            'sha256_16' => $hash,
            'durasi_ms' => $durasi,
        ]);

        $this->info('Backup berhasil: '.$namaFile.' ('.$size.' byte, sha256[0:16]='.$hash.').');

        $retensi = (int) ($this->option('retensi-hari') ?: 0);

        if ($retensi >= 1) {
            $this->terapkanRetensi($path, $retensi);
        }

        return self::SUCCESS;
    }

    /**
     * True bila path berada di dalam base path proyek (case-insensitive untuk Windows).
     */
    private function pathInsideProject(string $path): bool
    {
        $normal = static function (string $p): string {
            $real = realpath($p);

            return str_replace('\\', '/', $real !== false ? $real : $p);
        };

        $target = rtrim(strtolower($normal($path)), '/').'/';
        $project = rtrim(strtolower(str_replace('\\', '/', base_path())), '/').'/';

        return str_starts_with($target, $project);
    }

    private function locateMysqldump(): ?string
    {
        $configured = config('siakad.backup.mysqldump');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $finder = PHP_OS_FAMILY === 'Windows' ? ['where', 'mysqldump'] : ['sh', '-c', 'command -v mysqldump'];
        $process = new Process($finder);
        $process->run();

        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());

            if ($output !== '') {
                return strtok($output, PHP_EOL) ?: null;
            }
        }

        return null;
    }

    /**
     * Hapus hanya file siakad-*.sql di direktori tujuan yang lebih tua dari $hari.
     * Retensi TIDAK aktif kecuali dipanggil eksplisit dengan --retensi-hari >= 1.
     */
    private function terapkanRetensi(string $path, int $hari): void
    {
        $batas = now()->subDays($hari);
        $dihapus = 0;

        foreach (glob(rtrim($path, '\\/').DIRECTORY_SEPARATOR.'siakad-*.sql') ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }

            $tanggal = @filemtime($file);

            if ($tanggal !== false && $tanggal < $batas->getTimestamp()) {
                @unlink($file);
                $dihapus++;
            }
        }

        Log::info('siakad:backup-db: retensi diterapkan.', ['dihapus' => $dihapus, 'retensi_hari' => $hari]);
        $this->info('Retensi diterapkan ('.$hari.' hari), '.$dihapus.' file lama dihapus.');
    }
}
