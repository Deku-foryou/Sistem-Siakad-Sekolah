<?php

namespace App\Services;

use App\Models\JadwalPertemuan;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Fondasi logika generasi pertemuan jadwal per tanggal.
 *
 * - Sumber jadwal: jadwalkelas pada konteks akademik yang sedang berlaku.
 * - Hanya pertemuan pada hari yang cocok dengan tanggal yang di-generate.
 * - Snapshot disalin dari jadwal sumber saat pertemuan dibuat.
 * - Idempoten: pertemuan yang sudah ada tidak pernah di-overwrite;
 *   pertemuan yang sudah memiliki absensi otomatis aman (tidak ada update).
 */
class JadwalPertemuanService
{
    /**
     * Generate pertemuan untuk tanggal tertentu.
     *
     * @param  string  $tanggal  Format Y-m-d
     * @return array{tanggal:string, hari:int, total_jadwal:int, created:int, skipped:int}
     *
     * @throws \DomainException Jika tanggal tidak valid atau konteks akademik belum valid
     */
    public function generateUntukTanggal(string $tanggal): array
    {
        $parsed = $this->parseTanggal($tanggal);

        $konteks = KonteksAkademikService::ambil();
        if (! $konteks->valid()) {
            throw new DomainException('Konteks akademik belum valid: '.implode(' ', $konteks->pesanPeringatan()));
        }
        $semester = $konteks->semester;

        return DB::transaction(function () use ($parsed, $semester) {
            $hari = (int) $parsed->dayOfWeekIso; // 1=Senin .. 7=Minggu (sesuai jamhari.hari)
            $tanggalStr = $parsed->toDateString();

            // Ambil jadwal sumber (hanya jam pelajaran) pada hari yang cocok.
            // Jamhari menyimpan kelas_id (per-kelas), mapel/guru/semester dari jadwalkelas.
            $jadwals = DB::table('jadwalkelas as j')
                ->join('jamhari as jh', 'j.jamhari_idjamhari', '=', 'jh.idjamhari')
                ->where('j.semester_idsemester', $semester->idsemester)
                ->where('jh.hari', $hari)
                ->where('jh.jenis', 'pelajaran')
                ->whereNotNull('jh.kelas_id')
                ->select(
                    'j.idjadwalkelas',
                    'j.semester_idsemester',
                    'j.guru_idguru',
                    'j.mapel_idmapel',
                    'jh.kelas_id',
                    'jh.hari',
                    'jh.jam_ke',
                    'jh.mulai',
                    'jh.selesai'
                )
                ->lockForUpdate()
                ->get();

            $created = 0;
            $skipped = 0;

            foreach ($jadwals as $jadwal) {
                try {
                    $pertemuan = JadwalPertemuan::firstOrCreate(
                        [
                            'jadwalkelas_idjadwalkelas' => $jadwal->idjadwalkelas,
                            'tanggal' => $tanggalStr,
                        ],
                        [
                            'semester_idsemester' => $jadwal->semester_idsemester,
                            'kelas_idkelas' => $jadwal->kelas_id,
                            'guru_idguru' => $jadwal->guru_idguru,
                            'mapel_idmapel' => $jadwal->mapel_idmapel,
                            'hari' => $jadwal->hari,
                            'jam_ke' => $jadwal->jam_ke,
                            'waktu_mulai' => $jadwal->mulai,
                            'waktu_selesai' => $jadwal->selesai,
                            'status' => 'terjadwal',
                        ]
                    );

                    if ($pertemuan->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $skipped++;
                    }
                } catch (QueryException $e) {
                    // Dua proses generate bersamaan: baris sudah dibuat proses lain.
                    // Perlakukan sebagai "sudah ada" agar generate tetap idempoten.
                    if ($this->isDuplicateKey($e)) {
                        $skipped++;

                        continue;
                    }

                    throw $e;
                }
            }

            return [
                'tanggal' => $tanggalStr,
                'hari' => $hari,
                'total_jadwal' => $jadwals->count(),
                'created' => $created,
                'skipped' => $skipped,
            ];
        });
    }

    private function parseTanggal(string $tanggal): Carbon
    {
        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $tanggal);
        } catch (\Throwable) {
            throw new DomainException('Tanggal tidak valid. Gunakan format Y-m-d.');
        }

        if ($parsed === false || $parsed->format('Y-m-d') !== $tanggal) {
            throw new DomainException('Tanggal tidak valid. Gunakan format Y-m-d.');
        }

        return $parsed;
    }

    private function isDuplicateKey(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), '1062');
    }
}
