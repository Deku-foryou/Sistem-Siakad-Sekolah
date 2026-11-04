<?php

namespace App\Services;

use App\Models\AbsensiHarian;
use App\Models\Kelas;
use App\Models\Kelassiswa;
use App\Models\Pengampu;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AbsensiHarianService
{
    /**
     * Dapatkan siswa aktif di kelas untuk absensi.
     * Hanya siswa dengan kelassiswa.status = 'aktif'
     *
     * @param  int  $kelasId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSiswaAktif($kelasId)
    {
        return Kelassiswa::query()
            ->where('kelas_idkelas', $kelasId)
            ->where('status', 'aktif')
            ->with('siswa')
            ->orderBy('siswa_idsiswa')
            ->get();
    }

    /**
     * Validasi konteks absensi: tahun, semester, kelas, tanggal.
     *
     * @param  int  $tahunAjaranId
     * @param  int  $semesterId
     * @param  int  $kelasId
     * @param  string  $tanggal  (Y-m-d)
     *
     * @throws \Exception
     */
    public function validateContext($tahunAjaranId, $semesterId, $kelasId, $tanggal)
    {
        // Validasi tahun ajaran & semester exist dan related
        $semester = \App\Models\Semester::findOrFail($semesterId);
        if ((int) $semester->tahunajaran_idtahunajaran !== (int) $tahunAjaranId) {
            throw new \Exception('Semester tidak sesuai dengan tahun ajaran.');
        }

        // Validasi kelas exist
        $kelas = Kelas::findOrFail($kelasId);
        if ((int) $kelas->tahunajaran_idtahunajaran !== (int) $tahunAjaranId) {
            throw new \Exception('Kelas tidak sesuai dengan tahun ajaran.');
        }

        // Validasi tanggal
        if (! $this->isValidDate($tanggal)) {
            throw new \Exception('Tanggal tidak valid.');
        }

        // Jangan izinkan absensi di masa depan
        if (Carbon::parse($tanggal)->isAfter(Carbon::today())) {
            throw new \Exception('Tidak dapat mencatat absensi untuk hari di masa depan.');
        }
    }

    /**
     * Cek apakah guru berwenang untuk kelas tertentu.
     * Guru berwenang jika:
     * 1. Superadmin
     * 2. Wali kelas (kelas.guru_idguru)
     * 3. Mengampu di kelas (pengampu relation)
     *
     * @param  \App\Models\User  $user
     * @param  int  $kelasId
     * @return bool
     */
    public function checkGuruAccess($user, $kelasId)
    {
        if ($user->level === 'superadmin') {
            return true;
        }

        if ($user->level !== 'guru') {
            return false;
        }

        $guru = $user->guru;
        if (! $guru) {
            return false;
        }

        $kelas = Kelas::find($kelasId);
        if (! $kelas) {
            return false;
        }

        // Cek wali kelas
        if ((int) $kelas->guru_idguru === (int) $guru->idguru) {
            return true;
        }

        // Cek pengampu (guru mengajar mapel di kelas ini)
        $isPengampu = Pengampu::query()
            ->where('kelas_idkelas', $kelasId)
            ->where('guru_idguru', $guru->idguru)
            ->exists();

        return $isPengampu;
    }

    /**
     * Cek apakah user dapat mengedit absensi (H+1 deadline).
     * Guru hanya boleh edit untuk hari ini dan kemarin.
     * Superadmin boleh kapan saja.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\AbsensiHarian  $absensi
     * @return bool
     */
    public function checkEditDeadline($user, $absensi)
    {
        if ($user->level === 'superadmin') {
            return true;
        }

        if ($user->level !== 'guru') {
            return false;
        }

        $daysOld = Carbon::today()->diffInDays(Carbon::parse($absensi->tanggal), false);

        // Guru bisa edit untuk hari ini (daysOld = 0) dan kemarin (daysOld = -1)
        return $daysOld >= -1 && $daysOld <= 0;
    }

    /**
     * Cek duplikasi absensi untuk siswa per hari per kelas.
     *
     * @param  int  $tahunAjaranId
     * @param  int  $semesterId
     * @param  int  $kelasId
     * @param  string  $tanggal
     * @param  int  $siswaId
     * @param  int  $excludeId  (optional, untuk update)
     * @return bool true jika sudah ada (duplikasi), false jika belum ada
     */
    public function checkDuplicate($tahunAjaranId, $semesterId, $kelasId, $tanggal, $siswaId, $excludeId = null)
    {
        $query = AbsensiHarian::query()
            ->where('tahunajaran_idtahunajaran', $tahunAjaranId)
            ->where('semester_idsemester', $semesterId)
            ->where('kelas_idkelas', $kelasId)
            ->where('tanggal', $tanggal)
            ->where('siswa_idsiswa', $siswaId);

        if ($excludeId) {
            $query->where('idabsensi_harian', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Simpan absensi massal secara transaksional.
     *
     * @param  int  $tahunAjaranId
     * @param  int  $semesterId
     * @param  int  $kelasId
     * @param  string  $tanggal
     * @param  array  $absensiData  [ siswaId => ['status' => '...', 'catatan' => '...'], ... ]
     * @param  int  $userId  (diinput_oleh)
     * @param  \App\Models\User|null  $user  (untuk validasi akses guru)
     * @return array ['success' => true, 'message' => '...', 'count' => N]
     *
     * @throws \Exception
     */
    public function saveAbsensiMassal($tahunAjaranId, $semesterId, $kelasId, $tanggal, $absensiData, $userId, $user = null)
    {
        return DB::transaction(function () use ($tahunAjaranId, $semesterId, $kelasId, $tanggal, $absensiData, $userId, $user) {
            // T1 FIX: Validasi akses guru terhadap kelas (harus di service layer, bukan hanya component)
            if ($user && $user->level === 'guru') {
                if (! $this->checkGuruAccess($user, $kelasId)) {
                    throw new \Exception('Anda tidak berwenang mengakses kelas ini.');
                }
            }

            // Validasi konteks: tahun ajaran, semester, kelas, tanggal
            $this->validateContext($tahunAjaranId, $semesterId, $kelasId, $tanggal);

            // T2 FIX: Pre-load daftar siswa aktif di kelas untuk validasi
            $activeSiswaIds = Kelassiswa::query()
                ->where('kelas_idkelas', $kelasId)
                ->where('status', 'aktif')
                ->pluck('siswa_idsiswa')
                ->toArray();

            // Validasi semua siswaId dalam absensiData adalah siswa aktif di kelas
            foreach (array_keys($absensiData) as $siswaId) {
                if (! in_array($siswaId, $activeSiswaIds, true)) {
                    throw new \Exception("Siswa {$siswaId} tidak terdaftar aktif di kelas ini.");
                }
            }

            $validStatuses = ['hadir', 'sakit', 'izin', 'alpha'];
            $count = 0;

            foreach ($absensiData as $siswaId => $data) {
                $status = $data['status'] ?? 'hadir';
                $catatan = $data['catatan'] ?? null;

                // Validasi status
                if (! in_array($status, $validStatuses, true)) {
                    throw new \Exception("Status '{$status}' tidak valid untuk siswa {$siswaId}.");
                }

                // Cek duplikasi
                if ($this->checkDuplicate($tahunAjaranId, $semesterId, $kelasId, $tanggal, $siswaId)) {
                    throw new \Exception("Absensi sudah tercatat untuk siswa {$siswaId} pada tanggal {$tanggal}.");
                }

                // Insert record
                AbsensiHarian::create([
                    'tahunajaran_idtahunajaran' => $tahunAjaranId,
                    'semester_idsemester' => $semesterId,
                    'kelas_idkelas' => $kelasId,
                    'tanggal' => $tanggal,
                    'siswa_idsiswa' => $siswaId,
                    'status' => $status,
                    'catatan' => $catatan,
                    'diinput_oleh' => $userId,
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

    /**
     * Edit absensi untuk siswa tertentu.
     *
     * @param  int  $absensiId
     * @param  string  $status
     * @param  string|null  $catatan
     * @param  int  $userId  (diubah_oleh)
     * @param  \App\Models\User|null  $user  (untuk validasi akses guru)
     * @return \App\Models\AbsensiHarian
     *
     * @throws \Exception
     */
    public function editAbsensi($absensiId, $status, $catatan, $userId, $user = null)
    {
        $validStatuses = ['hadir', 'sakit', 'izin', 'alpha'];

        if (! in_array($status, $validStatuses, true)) {
            throw new \Exception("Status '{$status}' tidak valid.");
        }

        $absensi = AbsensiHarian::findOrFail($absensiId);

        // T1 FIX: Validasi akses guru terhadap kelas (harus di service layer)
        if ($user && $user->level === 'guru') {
            if (! $this->checkGuruAccess($user, $absensi->kelas_idkelas)) {
                throw new \Exception('Anda tidak berwenang mengakses kelas ini.');
            }
        }

        $absensi->update([
            'status' => $status,
            'catatan' => $catatan,
            'diubah_oleh' => $userId,
        ]);

        return $absensi;
    }

    /**
     * Helper: validasi format date Y-m-d
     */
    private function isValidDate($date)
    {
        try {
            Carbon::createFromFormat('Y-m-d', $date);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retrieve absensi untuk kelas & tanggal tertentu dengan relasi.
     *
     * @param  int  $kelasId
     * @param  string  $tanggal
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAbsensiKelasHari($kelasId, $tanggal)
    {
        return AbsensiHarian::forKelas($kelasId)
            ->forTanggal($tanggal)
            ->with(['siswa', 'userInput', 'userUbah'])
            ->orderBy('siswa_idsiswa')
            ->get();
    }
}
