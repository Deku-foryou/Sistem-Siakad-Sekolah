<?php

namespace App\Services;

use App\Models\Jampelajaran;
use App\Models\Polajam;
use App\Models\Polajamhari;
use App\Support\Hari;
use DomainException;
use Illuminate\Support\Facades\DB;

class JamHariService
{
    public static function polaUntukHari(int $hari): ?Polajam
    {
        $mapping = Polajamhari::where('hari', $hari)->first();
        if (! $mapping) {
            return null;
        }

        return Polajam::find($mapping->polajam_idpolajam);
    }

    public static function hariPengguna(Polajam $pola): array
    {
        return Polajamhari::where('polajam_idpolajam', $pola->idpolajam)
            ->pluck('hari')
            ->map(fn ($h) => (int) $h)
            ->values()
            ->all();
    }

    public static function pastikanPolaMandiri(int $hari): array
    {
        return DB::transaction(function () use ($hari) {
            if (! array_key_exists($hari, Hari::NAMA)) {
                throw new DomainException('Hari harus Senin sampai Minggu.');
            }

            $mapping = Polajamhari::where('hari', $hari)->lockForUpdate()->first();
            $petaJam = [];

            if (! $mapping) {
                $pola = self::buatPolaBaru('Jam '.Hari::nama($hari));
                Polajamhari::create([
                    'polajam_idpolajam' => $pola->idpolajam,
                    'hari' => $hari,
                ]);

                return ['pola' => $pola, 'petaJam' => $petaJam];
            }

            $pola = Polajam::query()->lockForUpdate()->find($mapping->polajam_idpolajam);
            if (! $pola) {
                throw new DomainException('Pola jam tidak ditemukan.');
            }

            $jumlahPengguna = Polajamhari::where('polajam_idpolajam', $pola->idpolajam)->count();
            if ($jumlahPengguna <= 1) {
                return ['pola' => $pola, 'petaJam' => $petaJam];
            }

            // copy-on-write
            $namaBaru = self::namaUnik($pola->nama.' - '.Hari::nama($hari), null);
            $polaBaru = self::buatPolaBaru($namaBaru);
            foreach ($pola->jamPelajaran()->get() as $jam) {
                $salinan = Jampelajaran::create([
                    'polajam_idpolajam' => $polaBaru->idpolajam,
                    'jenis' => $jam->jenis,
                    'jam_ke' => $jam->jam_ke,
                    'mulai' => $jam->mulai,
                    'selesai' => $jam->selesai,
                    'keterangan' => $jam->keterangan,
                ]);
                $petaJam[(int) $jam->idjampelajaran] = (int) $salinan->idjampelajaran;
            }

            // ubah pemetaan hari ini ke pola salinan
            $mapping->polajam_idpolajam = $polaBaru->idpolajam;
            $mapping->save();

            self::hapusPolaYatim((int) $pola->idpolajam);

            return ['pola' => $polaBaru, 'petaJam' => $petaJam];
        });
    }

    public static function tambahJam(int $hari, array $data, string $cakupan): Jampelajaran
    {
        return DB::transaction(function () use ($hari, $data, $cakupan) {
            if ($cakupan !== 'hari_ini' && $cakupan !== 'semua') {
                throw new DomainException('Cakupan tidak valid.');
            }
            $pola = self::polaUntukHari($hari);
            if ($cakupan === 'hari_ini' || ! $pola) {
                $hasil = self::pastikanPolaMandiri($hari);
                $pola = $hasil['pola'];
            }
            if (! $pola) {
                throw new DomainException('Hari '.$hari.' belum dipetakan ke pola jam.');
            }

            return JampelajaranService::simpan(
                null,
                (int) $pola->idpolajam,
                (string) ($data['jenis'] ?? 'pelajaran'),
                $data['jam_ke'] ?? null,
                (string) ($data['mulai'] ?? ''),
                (string) ($data['selesai'] ?? ''),
                $data['keterangan'] ?? null
            );
        });
    }

    public static function ubahJam(int $hari, int $idjam, array $data, string $cakupan): Jampelajaran
    {
        return DB::transaction(function () use ($hari, $idjam, $data, $cakupan) {
            if ($cakupan !== 'hari_ini' && $cakupan !== 'semua') {
                throw new DomainException('Cakupan tidak valid.');
            }
            $pola = self::polaUntukHari($hari);
            if (! $pola) {
                throw new DomainException('Hari '.$hari.' belum dipetakan ke pola jam.');
            }
            $petaJam = [];
            if ($cakupan === 'hari_ini') {
                $hasil = self::pastikanPolaMandiri($hari);
                $pola = $hasil['pola'];
                $petaJam = $hasil['petaJam'];
            }
            $targetId = $petaJam[$idjam] ?? $idjam;
            // ensure target exists in pola
            $row = Jampelajaran::find($targetId);
            if (! $row || (int) $row->polajam_idpolajam !== (int) $pola->idpolajam) {
                throw new DomainException('Baris jam tidak ditemukan.');
            }

            return JampelajaranService::simpan(
                (int) $targetId,
                (int) $pola->idpolajam,
                (string) ($data['jenis'] ?? $row->jenis),
                array_key_exists('jam_ke', $data) ? $data['jam_ke'] : $row->jam_ke,
                (string) ($data['mulai'] ?? substr($row->mulai, 0, 5)),
                (string) ($data['selesai'] ?? substr($row->selesai, 0, 5)),
                array_key_exists('keterangan', $data) ? $data['keterangan'] : $row->keterangan
            );
        });
    }

    public static function hapusJam(int $hari, int $idjam, string $cakupan): void
    {
        DB::transaction(function () use ($hari, $idjam, $cakupan) {
            if ($cakupan !== 'hari_ini' && $cakupan !== 'semua') {
                throw new DomainException('Cakupan tidak valid.');
            }
            $pola = self::polaUntukHari($hari);
            if (! $pola) {
                throw new DomainException('Hari '.$hari.' belum dipetakan ke pola jam.');
            }
            $petaJam = [];
            if ($cakupan === 'hari_ini') {
                $hasil = self::pastikanPolaMandiri($hari);
                $pola = $hasil['pola'];
                $petaJam = $hasil['petaJam'];
            }
            $targetId = $petaJam[$idjam] ?? $idjam;
            JampelajaranService::hapus((int) $targetId);
        });
    }

    public static function salinKeHari(int $dariHari, array $keHari): array
    {
        return DB::transaction(function () use ($dariHari, $keHari) {
            $polaAsal = self::polaUntukHari($dariHari);
            if (! $polaAsal) {
                throw new DomainException('Hari asal belum dipetakan ke pola jam.');
            }
            $jamAsal = $polaAsal->jamPelajaran()->get();
            if ($jamAsal->isEmpty()) {
                throw new DomainException('Hari asal belum memiliki jam.');
            }
            $jamKeAsal = $jamAsal->pluck('jam_ke')->filter(fn ($v) => $v !== null)->map(fn ($v) => (int) $v)->all();

            $hasil = [];
            foreach (array_values(array_unique(array_map('intval', $keHari))) as $targetHari) {
                if ($targetHari === $dariHari) {
                    continue;
                }
                if (! array_key_exists($targetHari, Hari::NAMA)) {
                    $hasil[$targetHari] = ['hari' => $targetHari, 'status' => 'ditolak', 'alasan' => 'Hari tidak valid.'];

                    continue;
                }
                // validate jadwal target
                $jadwalTarget = \App\Models\Jadwal::where('hari', $targetHari)->get();
                $jamKeTarget = $jadwalTarget->pluck('jam_ke')->map(fn ($v) => (int) $v)->unique()->all();
                $tidakAda = array_diff($jamKeTarget, $jamKeAsal);
                if (! empty($tidakAda)) {
                    $hasil[$targetHari] = [
                        'hari' => $targetHari,
                        'status' => 'ditolak',
                        'alasan' => 'Jam hari tujuan yang sudah terjadwal tidak ada di pola asal.',
                    ];

                    continue;
                }

                // create copy pola
                $namaBaru = self::namaUnik($polaAsal->nama.' - '.Hari::nama($targetHari), null);
                $polaBaru = self::buatPolaBaru($namaBaru);
                foreach ($jamAsal as $jam) {
                    Jampelajaran::create([
                        'polajam_idpolajam' => $polaBaru->idpolajam,
                        'jenis' => $jam->jenis,
                        'jam_ke' => $jam->jam_ke,
                        'mulai' => $jam->mulai,
                        'selesai' => $jam->selesai,
                        'keterangan' => $jam->keterangan,
                    ]);
                }

                // update mapping target
                $mappingTarget = Polajamhari::where('hari', $targetHari)->lockForUpdate()->first();
                $polaLamaId = $mappingTarget?->polajam_idpolajam;
                if ($mappingTarget) {
                    $mappingTarget->polajam_idpolajam = $polaBaru->idpolajam;
                    $mappingTarget->save();
                } else {
                    Polajamhari::create([
                        'polajam_idpolajam' => $polaBaru->idpolajam,
                        'hari' => $targetHari,
                    ]);
                }
                self::hapusPolaYatim($polaLamaId);

                $hasil[$targetHari] = [
                    'hari' => $targetHari,
                    'status' => 'berhasil',
                    'alasan' => null,
                    'pola' => $polaBaru,
                ];
            }

            return $hasil;
        });
    }

    private static function buatPolaBaru(string $nama): Polajam
    {
        $nama = self::namaUnik($nama, null);
        $pola = new Polajam;
        $pola->nama = $nama;
        $pola->save();

        return $pola;
    }

    private static function namaUnik(string $base, ?int $excludeId): string
    {
        $candidate = $base;
        $i = 2;
        while (Polajam::where('nama', $candidate)->when($excludeId, fn ($q) => $q->where('idpolajam', '!=', $excludeId))->exists()) {
            $candidate = $base.' ('.$i.')';
            $i++;
        }

        return $candidate;
    }

    private static function hapusPolaYatim(?int $polaId): void
    {
        if (! $polaId) {
            return;
        }
        $masihDipakai = Polajamhari::where('polajam_idpolajam', $polaId)->exists();
        if ($masihDipakai) {
            return;
        }
        Jampelajaran::where('polajam_idpolajam', $polaId)->delete();
        Polajam::where('idpolajam', $polaId)->delete();
    }
}
?>