<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Hari;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Histori absensi per jam (satu item) — baris absensi_perjam + snapshot jadwal_pertemuan.
 */
class AbsensiPerjamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tanggal = substr((string) ($this->tanggal ?? ''), 0, 10);
        $h = (int) ($this->hari ?? 0);

        return [
            'tanggal' => $tanggal !== '' ? $tanggal : null,
            'hari' => $h,
            'nama_hari' => $h > 0 ? Hari::nama($h) : null,
            'jam_ke' => (int) ($this->jam_ke ?? 0),
            'waktu_mulai' => $this->jam($this->waktu_mulai),
            'waktu_selesai' => $this->jam($this->waktu_selesai),
            'status' => (string) $this->status,
            'keterangan' => $this->keterangan,
            'pertemuan_status' => (string) ($this->pertemuan_status ?? ''),
            'kelas_nama' => $this->kelas_nama ?? null,
            'mapel_nama' => $this->mapel_nama ?? null,
            'guru_nama' => $this->guru_nama ?? null,
        ];
    }

    private function jam(mixed $waktu): ?string
    {
        $s = substr((string) $waktu, 0, 5);

        return $s === '' ? null : $s;
    }
}
