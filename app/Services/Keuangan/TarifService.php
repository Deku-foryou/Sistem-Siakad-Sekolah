<?php

namespace App\Services\Keuangan;

use App\Models\Beasiswa;
use App\Models\Kelassiswa;
use App\Models\Siswa;

/**
 * Resolusi tarif berdasarkan prioritas: individu > kelas > global.
 *
 * Semua nominal dalam rupiah (integer).
 */
class TarifService
{
    /**
     * Tarif yang berlaku untuk siswa pada tanggal tertentu.
     *
     * @return array{tarif: \App\Models\Tarif|null, sumber: string}
     */
    public static function resolve(Siswa $siswa, ?string $tanggal = null): array
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');

        // 1. Tarif individual (prioritas tertinggi)
        $tarifIndividu = \App\Models\Tarif::query()
            ->where('jenis', 'individu')
            ->where('siswa_idsiswa', $siswa->idsiswa)
            ->where('status', 'aktif')
            ->where('berlaku_dari', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('berlaku_sampai')
                    ->orWhere('berlaku_sampai', '>=', $tanggal);
            })
            ->orderByDesc('berlaku_dari')
            ->first();

        if ($tarifIndividu) {
            return ['tarif' => $tarifIndividu, 'sumber' => 'individu'];
        }

        // 2. Tarif kelas — kelas aktif siswa pada tanggal tersebut
        $kelasId = self::kelasAktifSiswaId($siswa->idsiswa, $tanggal);

        if ($kelasId) {
            $tarifKelas = \App\Models\Tarif::query()
                ->where('jenis', 'kelas')
                ->where('kelas_idkelas', $kelasId)
                ->where('status', 'aktif')
                ->where('berlaku_dari', '<=', $tanggal)
                ->where(function ($q) use ($tanggal) {
                    $q->whereNull('berlaku_sampai')
                        ->orWhere('berlaku_sampai', '>=', $tanggal);
                })
                ->orderByDesc('berlaku_dari')
                ->first();

            if ($tarifKelas) {
                return ['tarif' => $tarifKelas, 'sumber' => 'kelas'];
            }
        }

        // 3. Tarif global
        $tarifGlobal = \App\Models\Tarif::query()
            ->where('jenis', 'global')
            ->where('status', 'aktif')
            ->where('berlaku_dari', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('berlaku_sampai')
                    ->orWhere('berlaku_sampai', '>=', $tanggal);
            })
            ->orderByDesc('berlaku_dari')
            ->first();

        return ['tarif' => $tarifGlobal, 'sumber' => 'global'];
    }

    /**
     * Kelas aktif siswa pada tanggal tertentu (kelassiswa aktif).
     */
    public static function kelasAktifSiswaId(int $siswaId, ?string $tanggal = null): ?int
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');

        return Kelassiswa::query()
            ->where('siswa_idsiswa', $siswaId)
            ->where('status', 'aktif')
            ->orderByDesc('idkelassiswa')
            ->value('kelas_idkelas');
    }

    /**
     * Beasiswa yang berlaku untuk siswa pada tanggal tertentu.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Beasiswa>
     */
    public static function beasiswaBerlaku(Siswa $siswa, ?string $tanggal = null): \Illuminate\Database\Eloquent\Collection
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');

        return Beasiswa::query()
            ->where('status', 'aktif')
            ->where('berlaku_dari', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('berlaku_sampai')
                    ->orWhere('berlaku_sampai', '>=', $tanggal);
            })
            ->whereExists(function ($q) use ($siswa, $tanggal) {
                $q->selectRaw('1')
                    ->from('beasiswa_siswa as bs')
                    ->whereColumn('bs.beasiswa_idbeasiswa', 'beasiswa.idbeasiswa')
                    ->where('bs.siswa_idsiswa', $siswa->idsiswa)
                    ->where('bs.status', 'aktif')
                    ->where('bs.berlaku_dari', '<=', $tanggal)
                    ->where(function ($qq) use ($tanggal) {
                        $qq->whereNull('bs.berlaku_sampai')
                            ->orWhere('bs.berlaku_sampai', '>=', $tanggal);
                    });
            })
            ->orderByDesc('berlaku_dari')
            ->get();
    }
}
