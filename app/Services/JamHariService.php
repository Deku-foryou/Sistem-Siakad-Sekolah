<?php

namespace App\Services;

use App\Models\Jamhari;
use Illuminate\Support\Facades\DB;
use DomainException;
use Illuminate\Support\Facades\Schema;

class JamHariService
{
    public static function buat(array $data): void
    {
        DB::transaction(function () use ($data) {
            $hari = (int) $data['hari'];
            $jenis = $data['jenis'];
            $jamKe = $jenis === 'pelajaran' ? (int) $data['jam_ke'] : null;
            $mulai = $data['mulai'];
            $selesai = $data['selesai'];

            if ($jenis === 'pelajaran' && $jamKe < 1) {
                throw new DomainException('Jam ke harus >= 1 untuk pelajaran');
            }
            if (strtotime($selesai) <= strtotime($mulai)) {
                throw new DomainException('Selesai harus > mulai');
            }

            // Cek tumpang tindih
            $bentrok = Jamhari::where('hari', $hari)
                ->where(function ($query) use ($mulai, $selesai) {
                    $query->where(function ($q) use ($mulai, $selesai) {
                        $q->where('mulai', '<', $selesai)->where('selesai', '>', $mulai);
                    });
                })->first();

            if ($bentrok) {
                throw new DomainException("Bertabrakan dengan jam " . ($bentrok->jam_ke ? "ke-{$bentrok->jam_ke}" : "istirahat") . " ({$bentrok->mulai}-{$bentrok->selesai})");
            }

            Jamhari::create($data);
        });
    }

    public static function ubah(int $id, array $data): void
    {
        DB::transaction(function () use ($id, $data) {
            $jam = Jamhari::findOrFail($id);
            $hari = $jam->hari;
            $jenis = $data['jenis'];
            $jamKe = $jenis === 'pelajaran' ? (int) $data['jam_ke'] : null;
            $mulai = $data['mulai'];
            $selesai = $data['selesai'];

            if ($jenis === 'pelajaran' && $jamKe < 1) {
                throw new DomainException('Jam ke harus >= 1 untuk pelajaran');
            }
            if (strtotime($selesai) <= strtotime($mulai)) {
                throw new DomainException('Selesai harus > mulai');
            }

            $bentrok = Jamhari::where('hari', $hari)
                ->where('idjamhari', '!=', $id)
                ->where(function ($query) use ($mulai, $selesai) {
                    $query->where(function ($q) use ($mulai, $selesai) {
                        $q->where('mulai', '<', $selesai)->where('selesai', '>', $mulai);
                    });
                })->first();

            if ($bentrok) {
                throw new DomainException("Bertabrakan dengan jam " . ($bentrok->jam_ke ? "ke-{$bentrok->jam_ke}" : "istirahat") . " ({$bentrok->mulai}-{$bentrok->selesai})");
            }

            $jam->update($data);
        });
    }

    public static function hapus(int $id): void
    {
        DB::transaction(function () use ($id) {
            $jam = Jamhari::findOrFail($id);
            if (self::jamDipakai($jam) > 0) {
                throw new DomainException('Jam sudah dipakai oleh jadwal kelas');
            }
            $jam->delete();
        });
    }

    private static function jamDipakai(Jamhari $jam): int
    {
        if (!Schema::hasTable('jadwalkelas')) {
            return 0;
        }
        return DB::table('jadwalkelas')
            ->where('hari', $jam->hari)
            ->where('jam_ke', $jam->jam_ke)
            ->count();
    }

    public static function salin(int $dariHari, array $keHari): array
    {
        $hasil = [];
        $jamAsal = Jamhari::where('hari', $dariHari)->get();
        if ($jamAsal->isEmpty()) {
            throw new DomainException('Hari asal tidak memiliki jam');
        }

        foreach ($keHari as $hariTujuan) {
            if ($hariTujuan == $dariHari) {
                $hasil[$hariTujuan] = ['status' => 'ditolak', 'alasan' => 'Hari sama'];
                continue;
            }

            try {
                DB::transaction(function () use ($dariHari, $hariTujuan, $jamAsal) {
                    $jamTujuan = Jamhari::where('hari', $hariTujuan)->get();
                    foreach ($jamTujuan as $jam) {
                        if (self::jamDipakai($jam) > 0) {
                            throw new DomainException('Jam hari tujuan sudah dipakai');
                        }
                    }
                    Jamhari::where('hari', $hariTujuan)->delete();
                    foreach ($jamAsal as $jam) {
                        Jamhari::create([
                            'hari' => $hariTujuan,
                            'jam_ke' => $jam->jam_ke,
                            'jenis' => $jam->jenis,
                            'mulai' => $jam->mulai,
                            'selesai' => $jam->selesai,
                            'keterangan' => $jam->keterangan,
                        ]);
                    }
                });
                $hasil[$hariTujuan] = ['status' => 'berhasil'];
            } catch (DomainException $e) {
                $hasil[$hariTujuan] = ['status' => 'ditolak', 'alasan' => $e->getMessage()];
            }
        }
        return $hasil;
    }

    public static function nilaiAwal(int $hari): array
    {
        $maxJam = Jamhari::where('hari', $hari)->max('jam_ke');
        $lastJam = Jamhari::where('hari', $hari)->orderBy('selesai', 'desc')->first();
        return [
            'jam_ke' => (int) $maxJam + 1,
            'mulai' => $lastJam ? substr($lastJam->selesai, 0, 5) : '07:00',
        ];
    }
}
