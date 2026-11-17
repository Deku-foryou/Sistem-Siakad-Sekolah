<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\User;
use App\Models\Walimurid;
use App\Support\Hari;
use DomainException;

/**
 * Beranda wali (read-only) — sumber tunggal data beranda wali untuk API mobile.
 *
 * Berbeda dari beranda siswa: wali tidak melihat data akademik anak dari sini;
 * hanya identitas akun, daftar anak (relasi milik wali), dan ringkasan.
 * Data akademik anak diambil lewat endpoint /wali/anak/{id}/* yang di-guard
 * WalimuridService::pastikanAksesAkademikWali().
 */
class BerandaWaliService
{
    public static function beranda(User $user): array
    {
        if ($user->level !== 'wali') {
            throw new DomainException('Endpoint ini khusus untuk wali murid.');
        }

        $konteks = KonteksAkademikService::ambil();
        $namaHariIni = Hari::nama(now()->dayOfWeekIso);

        $relasiSemua = Walimurid::query()
            ->where('user_iduser', $user->id)
            ->get(['idwalimurid', 'siswa_idsiswa', 'status_hubungan', 'tanggal_mulai', 'tanggal_berhenti']);

        $ringkasan = [
            'jumlah_anak' => $relasiSemua->where('status_hubungan', 'verified')->count(),
            'jumlah_menunggu' => $relasiSemua->where('status_hubungan', 'pending')->count(),
            'jumlah_ditolak' => $relasiSemua->where('status_hubungan', 'rejected')->count(),
            'jumlah_berakhir' => $relasiSemua->where('status_hubungan', 'terminated')->count(),
            'notifikasi_belum_dibaca' => NotifikasiService::hitungBelumDibaca($user),
        ];

        // Siswa anak verified — untuk ditampilkan di beranda (tanpa data akademik).
        $anakIds = $relasiSemua->where('status_hubungan', 'verified')->pluck('siswa_idsiswa')->all();
        $siswaMap = Siswa::query()
            ->whereIn('idsiswa', $anakIds === [] ? [0] : $anakIds)
            ->get(['idsiswa', 'nis', 'nisn', 'nama'])
            ->keyBy('idsiswa');
        $kelasLabel = WalimuridService::labelKelasSiswa($anakIds);

        $anak = collect($anakIds)->map(fn ($siswaId) => [
            'id' => (int) $siswaId,
            'nisn' => $siswaMap[$siswaId]?->nisn,
            'nis' => $siswaMap[$siswaId]?->nis,
            'nama' => $siswaMap[$siswaId]?->nama,
            'kelas' => $kelasLabel[(int) $siswaId] ?? null,
        ])->values();

        return [
            'user' => $user,
            'konteks' => $konteks,
            'anak' => $anak,
            'ringkasan' => $ringkasan,
            'namaHariIni' => $namaHariIni,
        ];
    }
}
