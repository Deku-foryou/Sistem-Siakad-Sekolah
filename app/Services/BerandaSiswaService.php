<?php

namespace App\Services;

use App\Models\User;
use App\Support\Hari;

/**
 * Beranda siswa (read-only) — sumber tunggal data beranda untuk website dan API.
 *
 * Aturan:
 * - Konteks akademik selalu dari KonteksAkademikService::ambil().
 * - Jadwal hari ini dari JadwalSiswaService (jadwalkelas + jamhari; bukan jadwal legacy).
 * - Identitas siswa dari akun login. Tidak membaca tagihan/saldo/pengumuman/notifikasi.
 */
class BerandaSiswaService
{
    public static function beranda(User $user): array
    {
        abort_unless($user->level === 'siswa', 403);

        $konteks = KonteksAkademikService::ambil();
        $siswa = $user->siswa()
            ->select([
                'idsiswa', 'user_iduser', 'nisn', 'nis', 'nama',
                'tahun_masuk', 'status',
            ])
            ->first();

        $kelasList = collect();
        $jadwalHariIni = collect();
        $namaHariIni = Hari::nama(now()->dayOfWeekIso);

        if ($siswa && $konteks->valid()) {
            // Sumber tunggal: JadwalSiswaService (penempatan aktif TA aktif, jadwal semester aktif).
            $jadwalSiswa = app(JadwalSiswaService::class);
            $kelasList = $jadwalSiswa->kelasAktif($siswa->idsiswa, $konteks);

            $kelasIds = $kelasList->pluck('idkelas')->all();

            if ($kelasIds) {
                $hariIni = now()->dayOfWeekIso;
                $jadwalHariIni = $jadwalSiswa->urutkan(
                    $jadwalSiswa->jadwal($konteks, $kelasIds, $hariIni)
                );
            }
        }

        return [
            'siswa' => $siswa,
            'akun' => [
                'username' => $user->username ?: '-',
                'wajibGantiPassword' => (bool) $user->wajib_ganti_password,
            ],
            'konteks' => $konteks,
            'kelasList' => $kelasList,
            'jadwalHariIni' => $jadwalHariIni,
            'namaHariIni' => $namaHariIni,
        ];
    }
}
