<?php

namespace App\Services;

use App\Models\Jamhari;
use DomainException;
use Illuminate\Support\Facades\DB;

class JadwalKelasService
{
    public static function simpan(int $semesterId, int $jamhariId, int $mapelId, int $guruId, ?int $idPengganti = null): void
    {
        DB::transaction(function () use ($semesterId, $jamhariId, $mapelId, $guruId, $idPengganti) {
            $semester = DB::table('semester')->where('idsemester', $semesterId)->first();
            if (! $semester) {
                throw new DomainException('Semester tidak ditemukan');
            }

            $jamhari = Jamhari::find($jamhariId);
            if (! $jamhari || ! $jamhari->kelas_id || $jamhari->jenis !== 'pelajaran') {
                throw new DomainException('Jam pelajaran tidak valid');
            }

            $mapel = DB::table('mapel')->where('idmapel', $mapelId)->first();
            if (! $mapel) {
                throw new DomainException('Mapel tidak ditemukan');
            }

            $guru = DB::table('guru')->where('idguru', $guruId)->first();
            if (! $guru || $guru->status !== 'aktif') {
                throw new DomainException('Guru tidak aktif atau tidak ditemukan');
            }

            $guruMapel = DB::table('gurumapel')
                ->where('guru_idguru', $guruId)
                ->where('mapel_idmapel', $mapelId)
                ->exists();
            if (! $guruMapel) {
                throw new DomainException("Guru {$guru->nama} tidak terdaftar mengampu {$mapel->nama}");
            }

            // Cek tahun ajaran
            $kelas = DB::table('kelas')->where('idkelas', $jamhari->kelas_id)->first();
            if (! $kelas || (int) $kelas->tahunajaran_idtahunajaran !== (int) $semester->tahunajaran_idtahunajaran) {
                throw new DomainException('Kelas tidak sesuai dengan tahun ajaran semester');
            }

            $jadwalLama = null;
            if ($idPengganti !== null) {
                $jadwalLama = DB::table('jadwalkelas')
                    ->where('idjadwalkelas', $idPengganti)
                    ->where('semester_idsemester', $semesterId)
                    ->where('jamhari_idjamhari', $jamhariId)
                    ->first();

                if (! $jadwalLama) {
                    throw new DomainException('Jadwal yang akan diubah tidak ditemukan pada slot ini');
                }
            }

            // Cek slot
            $slot = DB::table('jadwalkelas')
                ->where('semester_idsemester', $semesterId)
                ->where('jamhari_idjamhari', $jamhariId)
                ->lockForUpdate()
                ->first();

            if ($slot && ($idPengganti === null || $slot->idjadwalkelas !== $idPengganti)) {
                throw new DomainException('Slot jam ini sudah terisi');
            }

            // Cek bentrok guru
            $bentrok = DB::table('jadwalkelas as j')
                ->join('jamhari as jh', 'j.jamhari_idjamhari', '=', 'jh.idjamhari')
                ->join('kelas as k', 'jh.kelas_id', '=', 'k.idkelas')
                ->where('j.semester_idsemester', $semesterId)
                ->where('j.guru_idguru', $guruId)
                ->where('jh.hari', $jamhari->hari)
                ->where(function ($q) use ($jamhari) {
                    $q->where('jh.mulai', '<', $jamhari->selesai)
                        ->where('jh.selesai', '>', $jamhari->mulai);
                })
                ->where('j.idjadwalkelas', '!=', $idPengganti ?? 0)
                ->select('k.nama as nama_kelas', 'jh.hari', 'jh.mulai', 'jh.selesai')
                ->first();

            if ($bentrok) {
                $namaHari = \App\Support\Hari::nama($bentrok->hari);
                throw new DomainException("Guru {$guru->nama} sudah mengajar di {$bentrok->nama_kelas} pada {$namaHari} {$bentrok->mulai}-{$bentrok->selesai}");
            }

            if ($idPengganti) {
                DB::table('jadwalkelas')->where('idjadwalkelas', $idPengganti)->update([
                    'semester_idsemester' => $semesterId,
                    'jamhari_idjamhari' => $jamhariId,
                    'mapel_idmapel' => $mapelId,
                    'guru_idguru' => $guruId,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('jadwalkelas')->insert([
                    'semester_idsemester' => $semesterId,
                    'jamhari_idjamhari' => $jamhariId,
                    'mapel_idmapel' => $mapelId,
                    'guru_idguru' => $guruId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public static function hapus(int $id): void
    {
        DB::transaction(function () use ($id) {
            DB::table('jadwalkelas')->where('idjadwalkelas', $id)->delete();
        });
    }
}
