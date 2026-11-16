<?php

namespace App\Services;

use App\Models\AbsensiHarian;
use App\Support\KonteksAkademik;
use Illuminate\Database\Eloquent\Builder;

/**
 * Histori & rekap absensi siswa (read-only).
 *
 * Satu sumber perhitungan untuk website (HistoriAbsensiSiswaComponent) dan API.
 * Tidak ada metode ini yang mengubah data. Identitas siswa selalu dari parameter —
 * pemanggil wajib mengambilnya dari akun login.
 *
 * Aturan:
 * - Harian: absensi_harian, semester & tahun ajaran aktif (bisa diabaikan untuk ekspor).
 * - Per jam: kanonis lewat jadwal_pertemuan (snapshot publikasi) via AbsensiPerjamService::historiSiswa().
 */
class RekapAbsensiService
{
    public const STATUS = ['hadir', 'sakit', 'izin', 'alpha'];

    /**
     * Query dasar histori harian.
     *
     * $abaikanFilter = true untuk rekap semester aktif (tanpa filter dari/sampai/status).
     */
    public function queryHarian(int $siswaId, KonteksAkademik $konteks, bool $abaikanFilter = false, ?string $dari = null, ?string $sampai = null, ?string $status = null): Builder
    {
        $query = AbsensiHarian::query()->forSiswa($siswaId);

        if ($konteks->valid() && $konteks->semester) {
            $query->forSemester($konteks->semester->idsemester);
        }

        if ($konteks->valid() && $konteks->tahunAjaran) {
            $query->forTahunajaran($konteks->tahunAjaran->idtahunajaran);
        }

        if ($abaikanFilter) {
            return $query;
        }

        if ($status !== null && in_array($status, self::STATUS, true)) {
            $query->where('absensi_harian.status', $status);
        }

        if ($dari !== null && $dari !== '') {
            $query->whereDate('absensi_harian.tanggal', '>=', $dari);
        }

        if ($sampai !== null && $sampai !== '') {
            $query->whereDate('absensi_harian.tanggal', '<=', $sampai);
        }

        return $query;
    }

    /**
     * Query kanonis histori per jam (join jadwal_pertemuan + kelas + mapel + guru).
     */
    public function queryPerJam(int $siswaId, ?string $dari = null, ?string $sampai = null, ?string $status = null): Builder
    {
        return app(AbsensiPerjamService::class)->historiSiswa($siswaId, $dari, $sampai, $status);
    }

    /**
     * Rekap jumlah per status dari query basis (algoritme grupBy + hitung —
     * identik dengan yang dipakai portal/eksper).
     */
    public function ringkasan(Builder $base, string $kolomStatus): array
    {
        $ringkasan = ['total' => 0, 'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0];

        $raw = (clone $base)
            ->selectRaw($kolomStatus.', COUNT(*) as jumlah')
            ->groupBy($kolomStatus)
            ->get();

        foreach ($raw as $row) {
            if (array_key_exists($row->status, $ringkasan)) {
                $ringkasan[$row->status] = (int) $row->jumlah;
                $ringkasan['total'] += (int) $row->jumlah;
            }
        }

        return $ringkasan;
    }

    /** Persentase kehadiran "hadir / total" dalam format lokal (satu desimal, koma). */
    public function persen(int $hadir, int $total): string
    {
        $persen = $total > 0 ? round($hadir / $total * 100, 1) : 0.0;

        return number_format($persen, 1, ',', '').'%';
    }
}
