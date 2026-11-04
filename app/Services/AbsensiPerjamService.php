<?php

namespace App\Services;

use App\Models\AbsensiPerjam;
use App\Models\JadwalPertemuan;
use App\Models\Kelassiswa;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Fondasi logika bisnis absensi per jam.
 *
 * Aturan:
 * - Guru hanya dapat mengabsen jadwal yang ditugaskan kepadanya (superadmin bebas).
 * - Siswa harus memiliki penempatan kelassiswa aktif pada kelas pertemuan.
 * - Pertemuan harus berstatus 'terjadwal' dan tidak berasal dari tanggal masa depan.
 * - Satu siswa hanya memiliki satu catatan per pertemuan.
 * - Guru hanya boleh mengedit sampai H+1 tanggal pertemuan; superadmin tidak dibatasi.
 * - Audit pencatatan: diinput_oleh saat buat, diubah_oleh saat edit.
 */
class AbsensiPerjamService
{
    public const STATUS_VALID = ['hadir', 'sakit', 'izin', 'alpha'];

    /**
     * Simpan absensi seorang siswa pada satu pertemuan.
     *
     * @throws \DomainException
     */
    public function simpan(int $pertemuanId, int $siswaId, string $status, ?string $keterangan, User $user): AbsensiPerjam
    {
        $this->assertLevelAllowed($user);
        $this->assertStatusValid($status);

        return DB::transaction(function () use ($pertemuanId, $siswaId, $status, $keterangan, $user) {
            /** @var JadwalPertemuan $pertemuan */
            $pertemuan = JadwalPertemuan::query()
                ->lockForUpdate()
                ->find($pertemuanId);

            if (! $pertemuan) {
                throw new DomainException('Pertemuan tidak ditemukan.');
            }

            $this->assertGuruDitugaskanAtauSuperadmin($user, $pertemuan);
            $this->assertPertemuanBisaDiabsen($pertemuan);
            $this->assertSiswaAktifDiKelas($pertemuan, $siswaId);

            // Cek duplikat sebelum insert agar pesan error ramah, unik constraint tetap backstop.
            $sudahAda = AbsensiPerjam::query()
                ->where('jadwal_pertemuan_idjadwal_pertemuan', $pertemuan->idjadwal_pertemuan)
                ->where('siswa_idsiswa', $siswaId)
                ->lockForUpdate()
                ->exists();

            if ($sudahAda) {
                throw new DomainException('Absensi siswa ini sudah tercatat pada pertemuan tersebut.');
            }

            try {
                return AbsensiPerjam::create([
                    'jadwal_pertemuan_idjadwal_pertemuan' => $pertemuan->idjadwal_pertemuan,
                    'siswa_idsiswa' => $siswaId,
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'diinput_oleh' => $user->id,
                    'diubah_oleh' => null,
                ]);
            } catch (QueryException $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), '1062')) {
                    throw new DomainException('Absensi siswa ini sudah tercatat pada pertemuan tersebut.');
                }

                throw $e;
            }
        });
    }

    /**
     * Edit absensi seorang siswa.
     *
     * @throws \DomainException
     */
    public function edit(int $absensiId, string $status, ?string $keterangan, User $user): AbsensiPerjam
    {
        $this->assertLevelAllowed($user);
        $this->assertStatusValid($status);

        return DB::transaction(function () use ($absensiId, $status, $keterangan, $user) {
            /** @var AbsensiPerjam $absensi */
            $absensi = AbsensiPerjam::query()
                ->with('jadwalPertemuan')
                ->lockForUpdate()
                ->find($absensiId);

            if (! $absensi) {
                throw new DomainException('Data absensi tidak ditemukan.');
            }

            $pertemuan = $absensi->jadwalPertemuan;
            $this->assertGuruDitugaskanAtauSuperadmin($user, $pertemuan);
            $this->assertPertemuanBisaDiabsen($pertemuan);
            $this->assertBatasEditHplusOne($user, $pertemuan);

            $absensi->status = $status;
            $absensi->keterangan = $keterangan;
            $absensi->diubah_oleh = $user->id;
            $absensi->save();

            return $absensi;
        });
    }

    private function assertLevelAllowed(User $user): void
    {
        if (! in_array($user->level, ['superadmin', 'guru'], true)) {
            throw new DomainException('Hanya guru penanggung jawab atau superadmin yang dapat mengelola absensi.');
        }
    }

    private function assertStatusValid(string $status): void
    {
        if (! in_array($status, self::STATUS_VALID, true)) {
            throw new DomainException("Status '{$status}' tidak valid.");
        }
    }

    private function assertGuruDitugaskanAtauSuperadmin(User $user, JadwalPertemuan $pertemuan): void
    {
        if ($user->level === 'superadmin') {
            return;
        }

        $guruId = $this->idGuruMilikUser($user);
        if ($guruId === null || (int) $pertemuan->guru_idguru !== $guruId) {
            throw new DomainException('Anda tidak ditugaskan mengampu jadwal pada pertemuan ini.');
        }
    }

    private function assertPertemuanBisaDiabsen(JadwalPertemuan $pertemuan): void
    {
        if ($pertemuan->status !== 'terjadwal') {
            throw new DomainException('Pertemuan tidak berstatus terjadwal.');
        }

        if (Carbon::parse($pertemuan->tanggal)->isAfter(Carbon::today())) {
            throw new DomainException('Tidak dapat mencatat absensi untuk pertemuan di masa depan.');
        }
    }

    private function assertBatasEditHplusOne(User $user, JadwalPertemuan $pertemuan): void
    {
        if ($user->level === 'superadmin') {
            return;
        }

        $deadline = Carbon::parse($pertemuan->tanggal)->addDay()->endOfDay();
        if (Carbon::now()->gt($deadline)) {
            throw new DomainException('Batas waktu edit guru sudah terlampaui (paling lambat H+1 tanggal pertemuan).');
        }
    }

    private function assertSiswaAktifDiKelas(JadwalPertemuan $pertemuan, int $siswaId): void
    {
        $aktif = Kelassiswa::query()
            ->where('kelas_idkelas', $pertemuan->kelas_idkelas)
            ->where('siswa_idsiswa', $siswaId)
            ->where('status', 'aktif')
            ->exists();

        if (! $aktif) {
            throw new DomainException('Siswa tidak memiliki penempatan aktif pada kelas pertemuan.');
        }
    }

    private function idGuruMilikUser(User $user): ?int
    {
        $guruId = DB::table('guru')->where('user_iduser', $user->id)->value('idguru');

        return $guruId === null ? null : (int) $guruId;
    }
}
