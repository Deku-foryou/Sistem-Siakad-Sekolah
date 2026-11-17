<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Hari;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu item riwayat absensi per jam (guru): baris absensi_perjam + snapshot jadwal_pertemuan.
 */
class GuruAbsensiPerjamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tanggal = substr((string) ($this->tanggal ?? ''), 0, 10);
        $h = (int) ($this->hari ?? 0);

        return [
            'id' => $this->idabsensi_perjam,
            'jadwal_pertemuan_id' => $this->jadwal_pertemuan_idjadwal_pertemuan,
            'tanggal' => $tanggal !== '' ? $tanggal : null,
            'hari' => $h,
            'nama_hari' => $h > 0 ? Hari::nama($h) : null,
            'jam_ke' => (int) ($this->jam_ke ?? 0),
            'waktu_mulai' => $this->jam($this->waktu_mulai),
            'waktu_selesai' => $this->jam($this->waktu_selesai),
            'status' => (string) $this->status,
            'keterangan' => $this->keterangan,
            'pertemuan_status' => (string) ($this->pertemuan_status ?? ''),
            'kelas_id' => (int) ($this->kelas_idkelas ?? 0),
            'kelas_nama' => $this->kelas_nama ?? null,
            'mapel_nama' => $this->mapel_nama ?? null,
            'siswa' => [
                'id' => $this->siswa_id,
                'nama' => $this->siswa_nama,
            ],
        ];
    }

    private function jam(mixed $waktu): ?string
    {
        $s = substr((string) $waktu, 0, 5);

        return $s === '' ? null : $s;
    }
}
