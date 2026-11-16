<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detail rapor final siswa. Nilai dari nilai_akhir (snapshot finalisasi) — tidak dihitung ulang.
 */
class RaporDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        return [
            'kelas' => [
                'id' => $resource['kelas']->idkelas,
                'nama' => $resource['kelas']->nama,
                'tingkat' => $resource['kelas']->tingkat,
            ],
            'semester' => [
                'id' => $resource['konteks']->semester->idsemester,
                'nama' => $resource['konteks']->semester->nama,
                'tahunajaran' => [
                    'id' => $resource['konteks']->tahunAjaran?->idtahunajaran,
                    'nama' => $resource['konteks']->tahunAjaran?->nama,
                ],
            ],
            'is_final' => true,
            'nilai' => $resource['nilai_final']->map(fn ($n) => [
                'mapel_id' => $n->mapel_idmapel,
                'mapel_kode' => $n->mapel?->kode,
                'mapel_nama' => $n->mapel?->nama,
                'nilai_akhir' => (float) $n->nilai_akhir,
                'nilai_kktp' => $n->nilai_kktp !== null ? (float) $n->nilai_kktp : null,
                'keterangan' => $n->keterangan,
            ])->values(),
            'rekap_kehadiran' => [
                'hadir' => (int) $resource['rekap_kehadiran']['hadir'],
                'sakit' => (int) $resource['rekap_kehadiran']['sakit'],
                'izin' => (int) $resource['rekap_kehadiran']['izin'],
                'alpha' => (int) $resource['rekap_kehadiran']['alpha'],
                'total' => (int) $resource['rekap_kehadiran']['total'],
            ],
            'catatan' => $resource['catatan']?->catatan ?? null,
            'nama_sekolah' => $resource['nama_sekolah'],
        ];
    }
}
