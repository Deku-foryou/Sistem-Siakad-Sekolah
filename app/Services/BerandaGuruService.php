<?php

namespace App\Services;

use App\Models\AbsensiHarian;
use App\Models\AbsensiPerjam;
use App\Models\JadwalPertemuan;
use App\Models\User;
use App\Support\Hari;
use DomainException;

/**
 * Beranda guru (read-only) — sumber tunggal data beranda untuk API mobile.
 *
 * Aturan:
 * - Konteks akademik selalu dari KonteksAkademikService::ambil().
 * - Jadwal hari ini dari jadwalkelas + jamhari (kanonis; bukan jadwal legacy).
 * - Identitas guru dari akun login. Tidak membaca nilai/keuangan/pengumuman.
 * - Tidak ada side effect: pertemuan TIDAK di-generate di sini (GET harus aman).
 */
class BerandaGuruService
{
    public static function beranda(User $user): array
    {
        $guru = GuruApiService::pastikanGuru($user);
        $konteks = KonteksAkademikService::ambil();
        $namaHariIni = Hari::nama(now()->dayOfWeekIso);

        $kelasList = collect();
        $jadwalHariIni = collect();

        if ($konteks->valid()) {
            $sumber = GuruApiService::sumberKelas($guru, $konteks);
            $ids = array_keys($sumber);

            if ($ids !== []) {
                $kelasList = \App\Models\Kelas::query()
                    ->withCount(['kelassiswa as jumlah_siswa' => fn ($query) => $query->where('status', 'aktif')])
                    ->whereIn('idkelas', $ids)
                    ->orderBy('nama')
                    ->get()
                    ->map(function ($kelas) use ($sumber) {
                        $kelas->sumber = $sumber[(int) $kelas->idkelas] ?? [];

                        return $kelas;
                    });
            }

            $hariIni = now()->dayOfWeekIso;
            $jadwalHariIni = app(JadwalSiswaService::class)->urutkan(
                GuruApiService::jadwalGuru($guru, $konteks, $hariIni)
            );
        }

        // Ringkasan absensi relevan hari ini (snapshot pertemuan yang SUDAH ter-generate).
        $pertemuanHariIni = JadwalPertemuan::query()
            ->where('guru_idguru', $guru->idguru)
            ->whereDate('tanggal', now()->format('Y-m-d'))
            ->count();

        $absensiPerjamHariIni = AbsensiPerjam::query()
            ->whereHas('jadwalPertemuan', fn ($query) => $query
                ->where('guru_idguru', $guru->idguru)
                ->whereDate('tanggal', now()->format('Y-m-d')))
            ->count();

        $absensiHarianHariIni = AbsensiHarian::query()
            ->where('diinput_oleh', $user->id)
            ->whereDate('tanggal', now()->format('Y-m-d'))
            ->count();

        try {
            $jumlahPenugasan = NilaiSiswaService::daftarPenugasan($user)->count();
        } catch (DomainException) {
            $jumlahPenugasan = 0;
        }

        return [
            'guru' => $guru,
            'akun' => [
                'username' => $user->username ?: '-',
                'wajibGantiPassword' => (bool) $user->wajib_ganti_password,
            ],
            'konteks' => $konteks,
            'kelasList' => $kelasList,
            'jadwalHariIni' => $jadwalHariIni,
            'namaHariIni' => $namaHariIni,
            'ringkasan' => [
                'jumlah_kelas' => $kelasList->count(),
                'jumlah_penugasan' => $jumlahPenugasan,
                'pertemuan_hari_ini' => $pertemuanHariIni,
                'absensi_perjam_hari_ini' => $absensiPerjamHariIni,
                'absensi_harian_hari_ini' => $absensiHarianHariIni,
            ],
        ];
    }
}
