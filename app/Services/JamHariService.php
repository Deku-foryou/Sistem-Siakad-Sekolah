<?php

namespace App\Services;

use App\Models\Jamhari;
use App\Models\Kelas;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JamHariService
{
    /**
     * Classes must follow the project's major rules and belong to the active
     * academic year; elective classes are allowed without a major.
     */
    public static function kelasAktifQuery(): Builder
    {
        return Kelas::query()
            ->whereHas('tahunajaran', fn (Builder $query) => $query->where('aktif', true))
            ->where(function (Builder $query) {
                $query->where(function (Builder $induk) {
                    $induk->where('jenis', 'induk')
                        ->whereNotNull('jurusan_id')
                        ->whereHas('jurusan', fn (Builder $jurusan) => $jurusan->where('aktif', true));
                })->orWhere(function (Builder $pilihan) {
                    $pilihan->where('jenis', 'mapel_pilihan')
                        ->whereNull('jurusan_id');
                });
            });
    }

    public static function pastikanKelasAktif(int $kelasId): Kelas
    {
        $kelas = self::kelasAktifQuery()->where('idkelas', $kelasId)->first();

        if (! $kelas) {
            throw new DomainException('Kelas tidak ditemukan atau tidak aktif pada tahun ajaran aktif.');
        }

        return $kelas;
    }

    public static function jamUntukKelas(int $kelasId, int $id): Jamhari
    {
        self::pastikanKelasAktif($kelasId);

        return Jamhari::query()
            ->where('kelas_id', $kelasId)
            ->where('idjamhari', $id)
            ->firstOrFail();
    }

    public static function buat(int $kelasId, array $data): void
    {
        DB::transaction(function () use ($kelasId, $data) {
            self::kunciKelasAktif($kelasId);

            $jamData = self::validasiData($data);
            self::pastikanSlotBelumAda($kelasId, $jamData['hari'], $jamData['jam_ke']);
            self::pastikanTidakBentrok($kelasId, $jamData['hari'], $jamData['mulai'], $jamData['selesai']);

            Jamhari::create($jamData + ['kelas_id' => $kelasId]);
        });
    }

    public static function ubah(int $kelasId, int $id, array $data): void
    {
        DB::transaction(function () use ($kelasId, $id, $data) {
            self::kunciKelasAktif($kelasId);
            $jam = Jamhari::query()
                ->where('kelas_id', $kelasId)
                ->where('idjamhari', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (self::jamDipakai($jam) > 0) {
                throw new DomainException('Jam sudah dipakai oleh jadwal kelas, tidak bisa diubah');
            }

            $jamData = self::validasiData($data, (int) $jam->hari);
            self::pastikanSlotBelumAda($kelasId, $jamData['hari'], $jamData['jam_ke'], $id);
            self::pastikanTidakBentrok($kelasId, $jamData['hari'], $jamData['mulai'], $jamData['selesai'], $id);

            $jam->update(collect($jamData)->except('hari')->all());
        });
    }

    public static function hapus(int $kelasId, int $id): void
    {
        DB::transaction(function () use ($kelasId, $id) {
            self::kunciKelasAktif($kelasId);
            $jam = Jamhari::query()
                ->where('kelas_id', $kelasId)
                ->where('idjamhari', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (self::jamDipakai($jam) > 0) {
                throw new DomainException('Jam sudah dipakai oleh jadwal kelas');
            }

            $jam->delete();
        });
    }

    public static function salin(int $kelasId, int $dariHari, array $keHari): array
    {
        self::pastikanKelasAktif($kelasId);
        self::pastikanHariValid($dariHari);

        $jamAsal = Jamhari::query()
            ->where('kelas_id', $kelasId)
            ->where('hari', $dariHari)
            ->orderBy('mulai')
            ->get();

        if ($jamAsal->isEmpty()) {
            throw new DomainException('Hari asal tidak memiliki jam pada kelas ini');
        }

        $tujuanUnik = [];
        foreach ($keHari as $nilaiHariTujuan) {
            if (! is_int($nilaiHariTujuan)
                && (! is_string($nilaiHariTujuan) || ! ctype_digit($nilaiHariTujuan))) {
                throw new DomainException('Hari tujuan tidak valid');
            }

            $hariTujuan = filter_var($nilaiHariTujuan, FILTER_VALIDATE_INT);
            if ($hariTujuan === false || $hariTujuan < 1 || $hariTujuan > 7) {
                throw new DomainException('Hari tujuan tidak valid');
            }

            $tujuanUnik[$hariTujuan] = $hariTujuan;
        }

        $hasil = [];
        foreach ($tujuanUnik as $hariTujuan) {
            if ($hariTujuan === $dariHari) {
                $hasil[$hariTujuan] = ['status' => 'ditolak', 'alasan' => 'Hari sama'];

                continue;
            }

            try {
                DB::transaction(function () use ($kelasId, $dariHari, $hariTujuan) {
                    self::kunciKelasAktif($kelasId);
                    $jamAsal = Jamhari::query()
                        ->where('kelas_id', $kelasId)
                        ->where('hari', $dariHari)
                        ->orderBy('mulai')
                        ->get();

                    if ($jamAsal->isEmpty()) {
                        throw new DomainException('Hari asal tidak memiliki jam pada kelas ini');
                    }

                    $jamTujuan = Jamhari::query()
                        ->where('kelas_id', $kelasId)
                        ->where('hari', $hariTujuan)
                        ->lockForUpdate()
                        ->get();

                    foreach ($jamTujuan as $jam) {
                        if (self::jamDipakai($jam) > 0) {
                            throw new DomainException('Jam hari tujuan sudah dipakai');
                        }
                    }

                    Jamhari::query()
                        ->where('kelas_id', $kelasId)
                        ->where('hari', $hariTujuan)
                        ->delete();

                    foreach ($jamAsal as $jam) {
                        Jamhari::create([
                            'kelas_id' => $kelasId,
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
            } catch (DomainException $exception) {
                $hasil[$hariTujuan] = ['status' => 'ditolak', 'alasan' => $exception->getMessage()];
            }
        }

        return $hasil;
    }

    public static function nilaiAwal(int $kelasId, int $hari): array
    {
        self::pastikanKelasAktif($kelasId);
        self::pastikanHariValid($hari);

        $jamHari = Jamhari::query()
            ->where('kelas_id', $kelasId)
            ->where('hari', $hari);

        $maxJam = (clone $jamHari)->max('jam_ke');
        $lastJam = (clone $jamHari)->orderByDesc('selesai')->first();

        return [
            'jam_ke' => (int) $maxJam + 1,
            'mulai' => $lastJam ? substr((string) $lastJam->selesai, 0, 5) : '07:00',
        ];
    }

    private static function validasiData(array $data, ?int $hariTetap = null): array
    {
        $hari = $hariTetap ?? filter_var($data['hari'] ?? null, FILTER_VALIDATE_INT);
        self::pastikanHariValid($hari);

        $jenis = $data['jenis'] ?? null;
        if (! in_array($jenis, ['pelajaran', 'istirahat'], true)) {
            throw new DomainException('Jenis jam tidak valid');
        }

        $jamKe = $jenis === 'pelajaran'
            ? filter_var($data['jam_ke'] ?? null, FILTER_VALIDATE_INT)
            : null;
        if ($jenis === 'pelajaran' && ($jamKe === false || $jamKe < 1)) {
            throw new DomainException('Jam ke harus >= 1 untuk pelajaran');
        }

        $mulai = (string) ($data['mulai'] ?? '');
        $selesai = (string) ($data['selesai'] ?? '');
        if (! self::waktuValid($mulai) || ! self::waktuValid($selesai)
            || strtotime($selesai) <= strtotime($mulai)) {
            throw new DomainException('Waktu selesai harus setelah waktu mulai dan menggunakan format HH:MM');
        }

        return [
            'hari' => (int) $hari,
            'jam_ke' => $jamKe === false ? null : $jamKe,
            'jenis' => $jenis,
            'mulai' => $mulai,
            'selesai' => $selesai,
            'keterangan' => $data['keterangan'] ?? null,
        ];
    }

    private static function waktuValid(string $waktu): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $waktu) === 1;
    }

    private static function pastikanHariValid(mixed $hari): void
    {
        if (! is_int($hari) || $hari < 1 || $hari > 7) {
            throw new DomainException('Hari harus berada antara 1 dan 7');
        }
    }

    private static function pastikanSlotBelumAda(int $kelasId, int $hari, ?int $jamKe, ?int $excludeId = null): void
    {
        if ($jamKe === null) {
            return;
        }

        $query = Jamhari::query()
            ->where('kelas_id', $kelasId)
            ->where('hari', $hari)
            ->where('jam_ke', $jamKe);

        if ($excludeId !== null) {
            $query->where('idjamhari', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new DomainException("Jam ke-{$jamKe} pada hari ini sudah ada di kelas ini");
        }
    }

    private static function pastikanTidakBentrok(
        int $kelasId,
        int $hari,
        string $mulai,
        string $selesai,
        ?int $excludeId = null
    ): void {
        $query = Jamhari::query()
            ->where('kelas_id', $kelasId)
            ->where('hari', $hari)
            ->where('mulai', '<', $selesai)
            ->where('selesai', '>', $mulai);

        if ($excludeId !== null) {
            $query->where('idjamhari', '!=', $excludeId);
        }

        $bentrok = $query->first();
        if ($bentrok) {
            $labelJam = $bentrok->jam_ke ? "ke-{$bentrok->jam_ke}" : 'istirahat';
            throw new DomainException(
                "Bertabrakan dengan jam {$labelJam} ({$bentrok->mulai}-{$bentrok->selesai}) di kelas ini"
            );
        }
    }

    private static function jamDipakai(Jamhari $jam): int
    {
        if (! Schema::hasTable('jadwalkelas')) {
            return 0;
        }

        return DB::table('jadwalkelas')
            ->where('jamhari_idjamhari', $jam->idjamhari)
            ->count();
    }

    private static function kunciKelasAktif(int $kelasId): Kelas
    {
        $kelas = self::kelasAktifQuery()
            ->where('idkelas', $kelasId)
            ->lockForUpdate()
            ->first();

        if (! $kelas) {
            throw new DomainException('Kelas tidak ditemukan atau tidak aktif pada tahun ajaran aktif.');
        }

        return $kelas;
    }
}
