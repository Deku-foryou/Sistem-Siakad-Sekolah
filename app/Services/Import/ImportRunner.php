<?php

namespace App\Services\Import;

use App\Models\User;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ImportRunner
{
    private const TIME_LIMIT_SECONDS = 8;

    public function __construct(
        private readonly ImportFileReader $reader = new ImportFileReader,
        private readonly ?\Closure $importerResolver = null
    ) {}

    public function mulai(string $path, string $jenis, ?User $actor = null): array
    {
        $importer = $this->importer($jenis);
        $this->bersihkanKadaluarsa();
        $provider = $this->lockProvider();
        $lockKey = 'import:'.$jenis;
        $lock = $provider->lock($lockKey, 600);
        if (! $lock->get()) {
            throw new RuntimeException("Import jenis {$jenis} sedang berjalan. Coba lagi setelah proses tersebut selesai.");
        }

        try {
            $ukuran = $this->ukurUkuranBagianAwal();
            $manifest = $this->reader->baca($path, $importer->jenis());
            $manifest['actor_id'] = $actor?->getKey() ?? Auth::id();
            $manifest['lock_owner'] = $lock->owner();
            $manifest['rata_rata_hash_ms'] = $ukuran['rata_rata_hash_ms'];
            $manifest['ukuran_bagian'] = $ukuran['ukuran_bagian'];
            $this->saveManifest($manifest);

            return $this->state($manifest);
        } catch (Throwable $exception) {
            $lock->release();
            throw $exception;
        }
    }

    public function proses(string $uuid, ?int $ukuranBagian = null): array
    {
        $manifest = $this->manifest($uuid);
        if ($manifest['selesai']) {
            return $this->state($manifest);
        }
        $ukuranBagian ??= (int) ($manifest['ukuran_bagian'] ?? 20);
        if ($ukuranBagian < 1 || $ukuranBagian > 100) {
            $exception = new InvalidArgumentException('Ukuran bagian harus antara 1 dan 100 baris.');
            $this->tandaiErrorDanLepas($manifest, $exception);
            throw $exception;
        }

        $this->lockProvider();

        try {
            $importer = $this->importer($manifest['jenis']);
            $end = min($manifest['offset'] + $ukuranBagian, $manifest['total']);
            $partStartedAt = hrtime(true);
            $processedInPart = 0;

            while (
                $manifest['offset'] < $end
                && ($processedInPart === 0 || (hrtime(true) - $partStartedAt) / 1_000_000_000 <= self::TIME_LIMIT_SECONDS)
            ) {
                $row = $manifest['rows'][$manifest['offset']];
                try {
                    $result = DB::transaction(function () use ($importer, $row) {
                        if ($row['kesalahan_pembaca'] !== []) {
                            return ['success' => false, 'errors' => $row['kesalahan_pembaca'], 'warnings' => []];
                        }

                        return $importer->proses($row['nilai']);
                    });
                } catch (QueryException $exception) {
                    if (! $this->uniqueViolation($exception)) {
                        Log::error('Import gagal saat menyimpan satu baris.', ['exception' => $exception]);
                        $result = ['success' => false, 'errors' => ['Baris gagal disimpan karena kesalahan database.'], 'warnings' => []];
                    } else {
                        $result = ['success' => false, 'errors' => ['sudah terdaftar'], 'warnings' => []];
                    }
                } catch (RowImportException $exception) {
                    $result = ['success' => false, 'errors' => [$exception->getMessage()], 'warnings' => []];
                } catch (Throwable $exception) {
                    Log::error('Import gagal saat memproses satu baris.', ['exception' => $exception]);
                    $result = ['success' => false, 'errors' => ['Baris gagal diproses karena kesalahan teknis.'], 'warnings' => []];
                }

                $manifest['offset']++;
                if ($result['success']) {
                    $manifest['berhasil']++;
                } else {
                    $manifest['gagal']++;
                    $this->appendFailure($uuid, [
                        'baris_asli' => $row['baris_asli'],
                        'nilai' => $row['nilai'],
                        'alasan' => $result['errors'],
                    ]);
                }

                foreach ($result['warnings'] as $warning) {
                    $manifest['warning_total'] = ($manifest['warning_total'] ?? 0) + 1;
                    if (str_starts_with($warning, 'Paket menu ')) {
                        $manifest['warnings'][$warning] = true;
                    } else {
                        $this->appendWarning($uuid, [
                            'baris_asli' => $row['baris_asli'],
                            'peringatan' => $warning,
                        ]);
                    }
                }
                $processedInPart++;
            }

            $manifest['selesai'] = $manifest['offset'] >= $manifest['total'];
            if ($manifest['selesai']) {
                $this->selesaikan($manifest);
            } else {
                $this->saveManifest($manifest);
            }

            return $this->state($manifest);
        } catch (Throwable $exception) {
            $this->tandaiErrorDanLepas($manifest, $exception);
            throw $exception;
        }
    }

    public function batalkan(string $uuid): array
    {
        $manifest = $this->manifest($uuid);
        if (! $manifest['selesai']) {
            $provider = $this->lockProvider();
            $provider->restoreLock('import:'.$manifest['jenis'], $manifest['lock_owner'])->release();
            $manifest['selesai'] = true;
            $manifest['lock_owner'] = null;
            $manifest['dibatalkan'] = true;
            $this->hapusSumber($manifest);
            $this->saveManifest($manifest);
        }

        return $this->state($manifest);
    }

    public function status(string $uuid): array
    {
        return $this->state($this->manifest($uuid));
    }

    public function aktif(string $uuid): bool
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid)) {
            throw new InvalidArgumentException('UUID import tidak valid.');
        }

        $path = $this->reader->directory().DIRECTORY_SEPARATOR.$uuid.'.json';
        if (! is_file($path)) {
            return false;
        }

        return ! $this->manifest($uuid)['selesai'];
    }

    public function bersihkanKadaluarsa(): int
    {
        $directory = $this->reader->directory();
        $cutoff = time() - 86_400;
        $deleted = 0;
        foreach (new \DirectoryIterator($directory) as $item) {
            if ($item->isDot() || ! $item->isFile() || $item->getMTime() >= $cutoff) {
                continue;
            }
            if (unlink($item->getPathname())) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function ukurUkuranBagianAwal(): array
    {
        $durations = [];
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $startedAt = hrtime(true);
            Hash::make('import-benchmark');
            $durations[] = (hrtime(true) - $startedAt) / 1_000_000;
        }

        $average = array_sum($durations) / count($durations);
        $size = (int) floor(8_000 / max($average, 1));

        return [
            'rata_rata_hash_ms' => round($average, 2),
            'ukuran_bagian' => max(5, min(50, $size)),
        ];
    }

    private function lockProvider(): LockProvider
    {
        $store = Cache::getStore();
        if (! $store instanceof LockProvider) {
            $driver = (string) config('cache.default');
            throw new RuntimeException("Cache driver '{$driver}' tidak mendukung cache lock. Gunakan driver file, database, memcached, atau redis.");
        }

        return $store;
    }

    private function manifest(string $uuid): array
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid)) {
            throw new InvalidArgumentException('UUID import tidak valid.');
        }
        $path = $this->reader->directory().DIRECTORY_SEPARATOR.$uuid.'.json';
        if (! is_file($path)) {
            throw new InvalidArgumentException('Data import tidak ditemukan atau sudah kedaluwarsa.');
        }
        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (($manifest['uuid'] ?? null) !== $uuid) {
            throw new InvalidArgumentException('UUID import tidak cocok dengan data tersimpan.');
        }

        return $manifest;
    }

    private function saveManifest(array $manifest): void
    {
        $path = $this->reader->directory().DIRECTORY_SEPARATOR.$manifest['uuid'].'.json';
        $this->reader->writeJson($path, $manifest);
    }

    private function appendFailure(string $uuid, array $failure): void
    {
        $path = $this->reader->directory().DIRECTORY_SEPARATOR.$uuid.'-gagal.json';
        $failures = [];
        if (is_file($path)) {
            $failures = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }
        $failures[] = $failure;
        $this->reader->writeJson($path, $failures);
    }

    private function appendWarning(string $uuid, array $warning): void
    {
        $path = $this->reader->directory().DIRECTORY_SEPARATOR.$uuid.'-peringatan.json';
        $warnings = [];
        if (is_file($path)) {
            $warnings = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        }
        $warnings[] = $warning;
        $this->reader->writeJson($path, $warnings);
    }

    private function selesaikan(array &$manifest): void
    {
        $this->hapusSumber($manifest);
        $owner = $manifest['lock_owner'];
        if (! $manifest['activity_written']) {
            $actor = $manifest['actor_id'] ? User::find($manifest['actor_id']) : null;
            $entry = activity('import')->withProperties([
                'jenis' => $manifest['jenis'],
                'berhasil' => $manifest['berhasil'],
                'gagal' => $manifest['gagal'],
            ]);
            if ($actor) {
                $entry->causedBy($actor);
            }
            $entry->log('Import selesai');
            $manifest['activity_written'] = true;
        }
        if ($owner) {
            $this->lockProvider()->restoreLock('import:'.$manifest['jenis'], $owner)->release();
        }
        $manifest['lock_owner'] = null;
        $manifest['ringkasan_peringatan'] = array_keys($manifest['warnings'] ?? []);
        $this->saveManifest($manifest);
    }

    private function tandaiErrorDanLepas(array &$manifest, Throwable $exception): void
    {
        $this->catatBarisError($exception);
        if ($manifest['lock_owner']) {
            $this->lockProvider()->restoreLock('import:'.$manifest['jenis'], $manifest['lock_owner'])->release();
        }
        $manifest['selesai'] = true;
        $manifest['error'] = 'Import dihentikan karena terjadi kesalahan teknis.';
        $manifest['lock_owner'] = null;
        $this->hapusSumber($manifest);
        $this->saveManifest($manifest);
    }

    private function catatBarisError(Throwable $exception): void
    {
        Log::error('Import gagal saat memproses satu baris.', ['exception' => $exception]);
    }

    private function uniqueViolation(QueryException $exception): bool
    {
        $state = $exception->errorInfo[0] ?? $exception->getCode();

        return in_array((string) $state, ['23000', '23505'], true);
    }

    private function importer(string $jenis): ImportBase
    {
        if ($this->importerResolver) {
            $importer = ($this->importerResolver)($jenis);
            if (! $importer instanceof ImportBase) {
                throw new RuntimeException('Resolver import harus mengembalikan ImportBase.');
            }

            return $importer;
        }

        return ImportBase::dariJenis($jenis);
    }

    private function hapusSumber(array $manifest): void
    {
        $source = $this->reader->directory().DIRECTORY_SEPARATOR.$manifest['source'];
        if (is_file($source) && ! unlink($source)) {
            throw new RuntimeException('File sumber import tidak dapat dihapus setelah proses selesai.');
        }
    }

    private function state(array $manifest): array
    {
        return [
            'uuid' => $manifest['uuid'],
            'jenis' => $manifest['jenis'],
            'offset' => $manifest['offset'],
            'berhasil' => $manifest['berhasil'],
            'gagal' => $manifest['gagal'],
            'total' => $manifest['total'],
            'selesai' => (bool) $manifest['selesai'],
            'dibatalkan' => (bool) ($manifest['dibatalkan'] ?? false),
            'peringatan' => $manifest['ringkasan_peringatan'] ?? array_keys($manifest['warnings'] ?? []),
            'jumlah_peringatan' => $manifest['warning_total'] ?? 0,
            'error' => $manifest['error'] ?? null,
            'rata_rata_hash_ms' => $manifest['rata_rata_hash_ms'] ?? null,
            'ukuran_bagian' => $manifest['ukuran_bagian'] ?? null,
        ];
    }
}
