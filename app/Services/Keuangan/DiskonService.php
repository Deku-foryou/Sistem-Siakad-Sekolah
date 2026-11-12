<?php

namespace App\Services\Keuangan;

use App\Models\Beasiswa;
use App\Models\Siswa;

/**
 * Perhitungan tagihan berdasarkan tarif + beasiswa.
 *
 * Aturan:
 * - Tarif individu diprioritaskan; beasiswa TIDAK ditambahkan bila tarif individu aktif.
 * - Selain itu beasiswa pertama yang berlaku diterapkan (paling besar nominalnya? — sesuai spesifikasi: "Beasiswa yang memenuhi syarat dapat diterapkan"; pilih satu dengan nilai tertinggi).
 * - Diskon total maksimal 100% (pembebasan penuh).
 * - Pembebasan penuh -> tagihan berstatus 'dibebaskan', nominal Rp 0.
 */
class DiskonService
{
    /**
     * Hitung tagihan untuk siswa pada tanggal tertentu.
     *
     * @param  string|null  $tanggal  Tanggal referensi (YYYY-MM-DD); null = hari ini.
     * @return array{tarif_nama:?string, tarif_jenis:?string, tarif_idtarif:?int, tarif_nominal:int, diskon_nominal:int, diskon_persen:int, total:int, beasiswa_nama:?string, beasiswa_jenis:?string, pembebasan:bool}
     */
    public static function hitung(Siswa $siswa, ?string $tanggal = null): array
    {
        $tanggal = $tanggal ?? now()->format('Y-m-d');
        $resolusi = TarifService::resolve($siswa, $tanggal);
        $tarif = $resolusi['tarif'];

        if (! $tarif) {
            return [
                'tarif_nama' => null,
                'tarif_jenis' => null,
                'tarif_idtarif' => null,
                'tarif_nominal' => 0,
                'diskon_nominal' => 0,
                'diskon_persen' => 0,
                'total' => 0,
                'beasiswa_nama' => null,
                'beasiswa_jenis' => null,
                'pembebasan' => false,
            ];
        }

        $tarifNominal = (int) $tarif->nominal;
        $diskonPersen = 0;
        $diskonNominal = 0;
        $beasiswaNama = null;
        $beasiswaJenis = null;
        $pembebasan = false;

        // Terapkan beasiswa hanya bila tarif bukan individu.
        if ($tarif->jenis !== 'individu') {
            $beasiswas = TarifService::beasiswaBerlaku($siswa, $tanggal);
            $terbaik = null;
            $terbaikNominal = -1;

            foreach ($beasiswas as $b) {
                if ($b->jenis === 'pembebasan') {
                    $terbaik = $b;
                    $terbaikNominal = PHP_INT_MAX;
                    break;
                }

                $hitung = $b->jenis === 'persen'
                    ? (int) round($tarifNominal * $b->nilai / 100)
                    : (int) $b->nilai;

                if ($hitung > $terbaikNominal) {
                    $terbaik = $b;
                    $terbaikNominal = $hitung;
                }
            }

            if ($terbaik) {
                $beasiswaNama = $terbaik->nama;
                $beasiswaJenis = $terbaik->jenis;

                if ($terbaik->jenis === 'pembebasan') {
                    $pembebasan = true;
                } elseif ($terbaik->jenis === 'persen') {
                    $diskonPersen = (int) $terbaik->nilai;
                } else {
                    $diskonNominal = (int) $terbaik->nilai;
                }
            }
        }

        if ($pembebasan) {
            $diskonNominal = $tarifNominal;
            $diskonPersen = 0;
        } elseif ($diskonPersen > 0) {
            $diskonNominal = (int) round($tarifNominal * $diskonPersen / 100);
        }

        // Batasi diskon tidak melebihi tarif
        $diskonNominal = min($diskonNominal, $tarifNominal);

        $total = max(0, $tarifNominal - $diskonNominal);

        return [
            'tarif_nama' => $tarif->nama,
            'tarif_jenis' => $tarif->jenis,
            'tarif_idtarif' => $tarif->idtarif,
            'tarif_nominal' => $tarifNominal,
            'diskon_nominal' => $diskonNominal,
            'diskon_persen' => $diskonPersen,
            'total' => $total,
            'beasiswa_nama' => $beasiswaNama,
            'beasiswa_jenis' => $beasiswaJenis,
            'pembebasan' => $pembebasan,
        ];
    }
}
