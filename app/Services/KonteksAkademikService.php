<?php

namespace App\Services;

use App\Models\Semester;
use App\Models\Tahunajaran;
use App\Support\KonteksAkademik;
use DomainException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Sumber tunggal konteks akademik yang sedang berlaku.
 *
 * Mengumpulkan pembacaan dan pengubahan konteks akademik agar hanya satu
 * tahun ajaran dan satu semester aktif pada satu waktu. Tidak mengubah data
 * kecuali dipanggil secara eksplisit (aktifkanSemester).
 */
class KonteksAkademikService
{
    public static function ambil(): KonteksAkademik
    {
        $tahunAjaran = Tahunajaran::aktif();

        $semester = Semester::query()
            ->with('tahunajaran')
            ->where('aktif', true)
            ->first();

        return KonteksAkademik::susun($tahunAjaran, $semester);
    }

    /**
     * Aktifkan semester beserta tahun ajaran induknya.
     *
     * Aturan:
     * - Semester yang dipilih harus ada dan memiliki tahun ajaran induk.
     * - Semua semester lain dan tahun ajaran lain dinonaktifkan.
     * - Tahun ajaran induk semester menjadi aktif.
     * - Operasi seluruhnya dalam satu transaksi dengan lockForUpdate().
     *
     * @throws \DomainException Jika data tidak valid
     * @throws \RuntimeException Jika hasil akhir konteks tidak valid
     */
    public static function aktifkanSemester(int $semesterId): KonteksAkademik
    {
        return DB::transaction(function () use ($semesterId) {
            // Kunci baris yang relevan untuk mencegah request bersamaan
            // menghasilkan dua semester/tahun ajaran aktif.
            $semester = Semester::query()->lockForUpdate()->findOrFail($semesterId);
            $tahunAjaran = $semester->tahunajaran()->lockForUpdate()->first();

            if (! $tahunAjaran) {
                throw new DomainException('Semester tidak memiliki tahun ajaran induk yang valid.');
            }

            // Nonaktifkan semua semester dan tahun ajaran lainnya.
            Semester::query()
                ->where('idsemester', '!=', $semester->idsemester)
                ->update(['aktif' => false]);

            Tahunajaran::query()
                ->where('idtahunajaran', '!=', $tahunAjaran->idtahunajaran)
                ->update(['aktif' => false]);

            // Aktifkan tahun ajaran induk dan semester yang dipilih.
            $tahunAjaran->aktif = true;
            $semester->aktif = true;

            $tahunAjaran->save();
            $semester->save();

            // Verifikasi hasil akhir dalam transaksi yang sama.
            $konteks = self::ambil();
            if (! $konteks->valid()) {
                throw new RuntimeException('Konteks akademik tidak valid setelah aktivasi.');
            }

            return $konteks;
        });
    }
}
