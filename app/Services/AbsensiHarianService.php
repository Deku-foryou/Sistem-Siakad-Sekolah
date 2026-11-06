<?php

namespace App\Services;

use App\Models\AbsensiHarian;
use App\Models\Guru;
use App\Models\JadwalKelas;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Semester;
use App\Models\Tahunajaran;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AbsensiHarianService
{
    private const VALID_STATUSES = ['hadir', 'sakit', 'izin', 'alpha'];

    public function getKelasTersedia(User $user, int $tahunAjaranId, int $semesterId): Collection
    {
        $this->validateAcademicContext($tahunAjaranId, $semesterId);
        $query = Kelas::query()
            ->where('tahunajaran_idtahunajaran', $tahunAjaranId);

        if ($user->level === 'guru') {
            $guru = $this->getGuruAktif($user);

            if (! $guru) {
                return $query->whereRaw('1 = 0')->get();
            }

            $query->whereExists(function ($subquery) use ($guru, $semesterId) {
                $subquery->selectRaw('1')
                    ->from('jadwalkelas as jk')
                    ->join('jamhari as jh', 'jh.idjamhari', '=', 'jk.jamhari_idjamhari')
                    ->whereColumn('jh.kelas_id', 'kelas.idkelas')
                    ->where('jk.semester_idsemester', $semesterId)
                    ->where('jk.guru_idguru', $guru->idguru)
                    ->where('jh.jenis', 'pelajaran');
            });
        } elseif ($user->level !== 'superadmin') {
            abort(403);
        }

        return $query->orderBy('nama')->get();
    }

    public function getSiswaAktif(int $kelasId, User $user, int $tahunAjaranId, int $semesterId): Collection
    {
        $this->authorizeClass($user, $tahunAjaranId, $semesterId, $kelasId);

        return Kelassiswa::query()
            ->where('kelas_idkelas', $kelasId)
            ->where('status', 'aktif')
            ->with('siswa')
            ->orderBy('siswa_idsiswa')
            ->get();
    }

    public function validateContext(int $tahunAjaranId, int $semesterId, int $kelasId, string $tanggal, User $user): void
    {
        $this->validateAcademicContext($tahunAjaranId, $semesterId, $kelasId);
        $this->validateDate($tanggal);
        $this->authorizeClass($user, $tahunAjaranId, $semesterId, $kelasId);

        if (Carbon::parse($tanggal)->isAfter(Carbon::today())) {
            throw new DomainException('Tidak dapat mencatat absensi untuk hari di masa depan.');
        }
    }

    public function checkGuruAccess(User $user, int $tahunAjaranId, int $semesterId, int $kelasId): bool
    {
        if ($user->level === 'superadmin') {
            return true;
        }

        if ($user->level !== 'guru') {
            return false;
        }

        try {
            $this->validateAcademicContext($tahunAjaranId, $semesterId, $kelasId);
        } catch (DomainException $exception) {
            return false;
        }

        $guru = $this->getGuruAktif($user);
        if (! $guru) {
            return false;
        }

        return JadwalKelas::query()
            ->where('semester_idsemester', $semesterId)
            ->where('guru_idguru', $guru->idguru)
            ->whereHas('jamhari', function ($query) use ($kelasId, $tahunAjaranId) {
                $query->where('kelas_id', $kelasId)
                    ->where('jenis', 'pelajaran')
                    ->whereHas('kelas', fn ($kelasQuery) => $kelasQuery->where('tahunajaran_idtahunajaran', $tahunAjaranId));
            })
            ->exists();
    }

    public function checkEditDeadline(User $user, string $tanggal): bool
    {
        if ($user->level === 'superadmin') {
            return true;
        }

        if ($user->level !== 'guru') {
            return false;
        }

        $daysOld = Carbon::today()->diffInDays(Carbon::parse($tanggal), false);

        return $daysOld >= -1 && $daysOld <= 0;
    }

    public function checkDuplicate(int $tahunAjaranId, int $semesterId, int $kelasId, string $tanggal, int $siswaId, ?int $excludeId = null): bool
    {
        $query = AbsensiHarian::query()
            ->where('tahunajaran_idtahunajaran', $tahunAjaranId)
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->whereDate('tanggal', $tanggal)
            ->where('siswa_idsiswa', $siswaId);

        if ($excludeId) {
            $query->where('idabsensi_harian', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function saveAbsensiMassal(
        int $tahunAjaranId,
        int $semesterId,
        int $kelasId,
        string $tanggal,
        array $absensiData,
        int $userId,
        User $user
    ): array {
        abort_unless((int) $user->getKey() === $userId, 403);

        return DB::transaction(function () use ($tahunAjaranId, $semesterId, $kelasId, $tanggal, $absensiData, $user) {
            $this->validateContext($tahunAjaranId, $semesterId, $kelasId, $tanggal, $user);
            $this->assertEditDeadline($user, $tanggal);

            $activeSiswaIds = Kelassiswa::query()
                ->where('kelas_idkelas', $kelasId)
                ->where('status', 'aktif')
                ->pluck('siswa_idsiswa')
                ->map(fn ($id) => (string) $id)
                ->all();

            $count = 0;
            foreach ($absensiData as $siswaId => $data) {
                $siswaId = (string) $siswaId;
                if (! ctype_digit($siswaId) || ! in_array($siswaId, $activeSiswaIds, true)) {
                    throw new DomainException("Siswa {$siswaId} tidak terdaftar aktif di kelas ini.");
                }

                if (! is_array($data)) {
                    throw new DomainException("Data absensi siswa {$siswaId} tidak valid.");
                }

                $status = $data['status'] ?? 'hadir';
                $catatan = $data['catatan'] ?? null;

                if (! is_string($status)) {
                    throw new DomainException("Status absensi siswa {$siswaId} tidak valid.");
                }

                if (! in_array($status, self::VALID_STATUSES, true)) {
                    throw new DomainException("Status '{$status}' tidak valid untuk siswa {$siswaId}.");
                }

                if ($catatan !== null && ! is_string($catatan)) {
                    throw new DomainException("Catatan absensi siswa {$siswaId} tidak valid.");
                }

                if ($this->checkDuplicate($tahunAjaranId, $semesterId, $kelasId, $tanggal, (int) $siswaId)) {
                    throw new DomainException("Absensi sudah tercatat untuk siswa {$siswaId} pada tanggal {$tanggal}.");
                }

                AbsensiHarian::create([
                    'tahunajaran_idtahunajaran' => $tahunAjaranId,
                    'semester_idsemester' => $semesterId,
                    'kelas_idkelas' => $kelasId,
                    'tanggal' => $tanggal,
                    'siswa_idsiswa' => (int) $siswaId,
                    'status' => $status,
                    'catatan' => $catatan,
                    'diinput_oleh' => $user->getKey(),
                ]);

                $count++;
            }

            return [
                'success' => true,
                'message' => "Absensi {$count} siswa berhasil disimpan.",
                'count' => $count,
            ];
        });
    }

    public function authorizeRecordForEdit(
        User $user,
        int $absensiId,
        int $tahunAjaranId,
        int $semesterId,
        int $kelasId,
        string $tanggal
    ): AbsensiHarian {
        $this->validateContext($tahunAjaranId, $semesterId, $kelasId, $tanggal, $user);

        $absensi = AbsensiHarian::query()
            ->where('idabsensi_harian', $absensiId)
            ->where('tahunajaran_idtahunajaran', $tahunAjaranId)
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->whereDate('tanggal', $tanggal)
            ->firstOrFail();

        $this->assertEditDeadline($user, $tanggal);
        $this->assertActivePlacementForGuru($user, $absensi->kelas_idkelas, $absensi->siswa_idsiswa);

        return $absensi;
    }

    public function editAbsensi(
        int $absensiId,
        mixed $status,
        mixed $catatan,
        int $userId,
        User $user,
        int $tahunAjaranId,
        int $semesterId,
        int $kelasId,
        string $tanggal
    ): AbsensiHarian {
        abort_unless((int) $user->getKey() === $userId, 403);

        if (! is_string($status)) {
            throw new DomainException('Status absensi tidak valid.');
        }

        if (! in_array($status, self::VALID_STATUSES, true)) {
            throw new DomainException("Status '{$status}' tidak valid.");
        }

        if ($catatan !== null && ! is_string($catatan)) {
            throw new DomainException('Catatan absensi tidak valid.');
        }

        return DB::transaction(function () use (
            $absensiId,
            $status,
            $catatan,
            $user,
            $tahunAjaranId,
            $semesterId,
            $kelasId,
            $tanggal
        ) {
            $absensi = AbsensiHarian::query()
                ->where('idabsensi_harian', $absensiId)
                ->where('tahunajaran_idtahunajaran', $tahunAjaranId)
                ->where('semester_idsemester', $semesterId)
                ->where('kelas_idkelas', $kelasId)
                ->whereDate('tanggal', $tanggal)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateContext(
                $tahunAjaranId,
                $semesterId,
                $kelasId,
                $tanggal,
                $user
            );
            $this->assertEditDeadline($user, $tanggal);
            $this->assertActivePlacementForGuru($user, (int) $absensi->kelas_idkelas, (int) $absensi->siswa_idsiswa);

            $absensi->update([
                'status' => $status,
                'catatan' => $catatan,
                'diubah_oleh' => $user->getKey(),
            ]);

            return $absensi->refresh();
        });
    }

    public function getAbsensiKelasHari(
        int $tahunAjaranId,
        int $semesterId,
        int $kelasId,
        string $tanggal,
        User $user
    ): Collection {
        $this->validateContext($tahunAjaranId, $semesterId, $kelasId, $tanggal, $user);

        return AbsensiHarian::query()
            ->where('tahunajaran_idtahunajaran', $tahunAjaranId)
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->whereDate('tanggal', $tanggal)
            ->with(['siswa', 'userInput', 'userUbah'])
            ->orderBy('siswa_idsiswa')
            ->get();
    }

    private function validateAcademicContext(int $tahunAjaranId, int $semesterId, ?int $kelasId = null): void
    {
        if ($tahunAjaranId < 1 || $semesterId < 1 || ($kelasId !== null && $kelasId < 1)) {
            throw new DomainException('Konteks tahun ajaran, semester, atau kelas tidak valid.');
        }

        $tahunAjaran = Tahunajaran::query()->findOrFail($tahunAjaranId);
        $semester = Semester::query()->findOrFail($semesterId);

        if ((int) $semester->tahunajaran_idtahunajaran !== (int) $tahunAjaran->idtahunajaran) {
            throw new DomainException('Semester tidak sesuai dengan tahun ajaran.');
        }

        if ($kelasId !== null) {
            $kelas = Kelas::query()->findOrFail($kelasId);
            if ((int) $kelas->tahunajaran_idtahunajaran !== (int) $tahunAjaran->idtahunajaran) {
                throw new DomainException('Kelas tidak sesuai dengan tahun ajaran.');
            }
        }
    }

    private function validateDate(string $tanggal): void
    {
        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $tanggal);
        } catch (\Throwable $exception) {
            throw new DomainException('Tanggal tidak valid.', previous: $exception);
        }

        if (! $parsed || $parsed->format('Y-m-d') !== $tanggal) {
            throw new DomainException('Tanggal tidak valid.');
        }
    }

    private function authorizeClass(User $user, int $tahunAjaranId, int $semesterId, int $kelasId): void
    {
        if ($user->level === 'superadmin') {
            return;
        }

        abort_unless(
            $user->level === 'guru' && $this->checkGuruAccess($user, $tahunAjaranId, $semesterId, $kelasId),
            403
        );
    }

    private function getGuruAktif(User $user): ?Guru
    {
        return $user->guru()
            ->where('status', 'aktif')
            ->first();
    }

    private function assertEditDeadline(User $user, string $tanggal): void
    {
        if (! $this->checkEditDeadline($user, $tanggal)) {
            throw new DomainException('Guru hanya dapat mencatat atau mengubah absensi untuk hari ini dan kemarin (H+1 deadline).');
        }
    }

    private function assertActivePlacementForGuru(User $user, int $kelasId, int $siswaId): void
    {
        if ($user->level !== 'guru') {
            return;
        }

        $isActive = Kelassiswa::query()
            ->where('kelas_idkelas', $kelasId)
            ->where('siswa_idsiswa', $siswaId)
            ->where('status', 'aktif')
            ->exists();

        if (! $isActive) {
            throw new DomainException("Siswa {$siswaId} tidak lagi memiliki penempatan aktif di kelas ini.");
        }
    }
}
