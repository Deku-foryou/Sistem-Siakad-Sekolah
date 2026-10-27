<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Jampelajaran;
use App\Models\Polajam;
use App\Models\Polajamhari;
use DomainException;
use Illuminate\Support\Facades\DB;

class PolajamService
{
    public static function simpan(?int $id, string $nama, array $hari): Polajam
    {
        return DB::transaction(function () use ($id, $nama, $hari) {
            $nama = trim($nama);
            if ($nama === '' || mb_strlen($nama) > 255) {
                throw new DomainException('Nama pola wajib diisi (maksimal 255 karakter).');
            }
            if (Polajam::where('nama', $nama)->when($id, fn ($q) => $q->where('idpolajam', '!=', $id))->exists()) {
                throw new DomainException('Nama pola sudah digunakan.');
            }
            $hari = array_values(array_unique(array_map('intval', $hari)));
            if ($hari === [] || count(array_diff($hari, [1, 2, 3, 4, 5])) > 0) {
                throw new DomainException('Pilih minimal satu hari dari Senin sampai Jumat.');
            }

            if ($id !== null && $id < 1) {
                throw new DomainException('Pola jam tidak ditemukan.');
            }
            $pola = $id
                ? Polajam::query()->lockForUpdate()->find($id)
                : new Polajam;
            if ($id !== null && ! $pola) {
                throw new DomainException('Pola jam tidak ditemukan.');
            }
            $pola ??= new Polajam;
            $pola->nama = $nama;
            $pola->save();

            $jamPelajaran = Jampelajaran::where('polajam_idpolajam', $pola->idpolajam)
                ->where('jenis', 'pelajaran')
                ->pluck('jam_ke')
                ->map(fn ($value) => (int) $value)
                ->all();

            foreach ($hari as $nomorHari) {
                $mapping = Polajamhari::where('hari', $nomorHari)->lockForUpdate()->first();
                if ($mapping && (int) $mapping->polajam_idpolajam !== (int) $pola->idpolajam) {
                    $invalid = Jadwal::query()
                        ->where('hari', $nomorHari)
                        ->whereNotIn('jam_ke', $jamPelajaran)
                        ->exists();
                    if ($invalid) {
                        throw new DomainException('Pemetaan hari tidak dapat diubah karena masih ada jadwal di luar jam pola baru.');
                    }
                    $mapping->polajam_idpolajam = $pola->idpolajam;
                    $mapping->save();
                } elseif (! $mapping) {
                    Polajamhari::create([
                        'polajam_idpolajam' => $pola->idpolajam,
                        'hari' => $nomorHari,
                    ]);
                }
            }

            Polajamhari::where('polajam_idpolajam', $pola->idpolajam)
                ->whereNotIn('hari', $hari)
                ->lockForUpdate()
                ->get()
                ->each(function (Polajamhari $mapping) {
                    if (Jadwal::where('hari', $mapping->hari)->exists()) {
                        throw new DomainException('Pemetaan hari tidak dapat dihapus karena hari tersebut sudah memiliki jadwal.');
                    }
                    $mapping->delete();
                });

            return $pola->load('hari');
        });
    }

    public static function hapus(int $id): void
    {
        DB::transaction(function () use ($id) {
            $pola = $id > 0 ? Polajam::query()->lockForUpdate()->find($id) : null;
            if (! $pola) {
                throw new DomainException('Pola jam tidak ditemukan.');
            }
            $mappings = $pola->hari()->lockForUpdate()->get();
            $periods = $pola->jamPelajaran()->lockForUpdate()->get();
            $days = $mappings->pluck('hari')->all();
            if ($days !== [] && Jadwal::whereIn('hari', $days)->exists()) {
                throw new DomainException('Pola tidak dapat dihapus karena hari tersebut sudah memiliki jadwal.');
            }
            $mappings->each->delete();
            $periods->each->delete();
            $pola->delete();
        });
    }
}
