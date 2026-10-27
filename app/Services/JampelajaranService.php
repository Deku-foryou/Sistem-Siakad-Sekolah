<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Jampelajaran;
use App\Models\Polajam;
use App\Models\Polajamhari;
use DomainException;
use Illuminate\Support\Facades\DB;

class JampelajaranService
{
    public static function simpan(
        ?int $id,
        int $polaId,
        string $jenis,
        mixed $jamKe,
        string $mulai,
        string $selesai
    ): Jampelajaran {
        return DB::transaction(function () use ($id, $polaId, $jenis, $jamKe, $mulai, $selesai) {
            if (! in_array($jenis, ['pelajaran', 'istirahat'], true)) {
                throw new DomainException('Jenis jam harus pelajaran atau istirahat.');
            }
            if ($polaId < 1 || ! Polajam::whereKey($polaId)->exists()) {
                throw new DomainException('Pola jam tidak ditemukan.');
            }
            $mulai = self::normalisasiWaktu($mulai);
            $selesai = self::normalisasiWaktu($selesai);
            if ($mulai >= $selesai) {
                throw new DomainException('Jam selesai harus setelah jam mulai.');
            }

            if ($jenis === 'pelajaran') {
                if (! is_numeric($jamKe) || (int) $jamKe < 1) {
                    throw new DomainException('Jam ke wajib berupa angka minimal 1 untuk jam pelajaran.');
                }
                $jamKe = (int) $jamKe;
            } else {
                $jamKe = null;
            }

            $row = $id ? Jampelajaran::query()->lockForUpdate()->find($id) : new Jampelajaran;
            if ($id !== null && (! $row || $id < 1)) {
                throw new DomainException('Baris jam tidak ditemukan.');
            }
            $row ??= new Jampelajaran;
            if ($row->exists && $row->jenis === 'pelajaran'
                && ((int) $row->polajam_idpolajam !== $polaId
                    || $row->jenis !== $jenis
                    || (int) $row->jam_ke !== (int) $jamKe)
                && self::dipakai($row)) {
                throw new DomainException('Nomor jam tidak dapat diubah karena sudah dipakai jadwal.');
            }

            $duplicate = Jampelajaran::where('polajam_idpolajam', $polaId)
                ->where('jenis', 'pelajaran')
                ->where('jam_ke', $jamKe)
                ->when($id, fn ($q) => $q->where('idjampelajaran', '!=', $id))
                ->exists();
            if ($duplicate) {
                throw new DomainException('Nomor jam tersebut sudah ada pada pola ini.');
            }

            $overlap = Jampelajaran::where('polajam_idpolajam', $polaId)
                ->where('mulai', '<', $selesai)
                ->where('selesai', '>', $mulai)
                ->when($id, fn ($q) => $q->where('idjampelajaran', '!=', $id))
                ->exists();
            if ($overlap) {
                throw new DomainException('Rentang waktu bertumpang tindih dengan baris lain pada pola ini.');
            }

            $row->polajam_idpolajam = $polaId;
            $row->jenis = $jenis;
            $row->jam_ke = $jamKe;
            $row->mulai = $mulai;
            $row->selesai = $selesai;
            $row->save();

            return $row;
        });
    }

    public static function hapus(int $id): void
    {
        DB::transaction(function () use ($id) {
            $row = $id > 0 ? Jampelajaran::query()->lockForUpdate()->find($id) : null;
            if (! $row) {
                throw new DomainException('Baris jam tidak ditemukan.');
            }
            if (self::dipakai($row)) {
                throw new DomainException('Jam tidak dapat dihapus karena sudah dipakai jadwal.');
            }
            $row->delete();
        });
    }

    private static function dipakai(Jampelajaran $row): bool
    {
        if ($row->jenis !== 'pelajaran' || $row->jam_ke === null) {
            return false;
        }

        $days = Polajamhari::where('polajam_idpolajam', $row->polajam_idpolajam)->pluck('hari');

        return Jadwal::whereIn('hari', $days)->where('jam_ke', $row->jam_ke)->exists();
    }

    private static function normalisasiWaktu(string $value): string
    {
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            throw new DomainException('Waktu harus menggunakan format HH:MM.');
        }

        return $value.':00';
    }
}
