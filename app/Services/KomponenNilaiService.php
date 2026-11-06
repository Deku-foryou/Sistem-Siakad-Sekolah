<?php

namespace App\Services;

use App\Models\KomponenNilai;
use App\Models\Mapel;
use App\Models\Semester;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aturan bisnis konfigurasi komponen penilaian.
 *
 * Konfigurasi disimpan sebagai satu kesatuan per (semester, mata pelajaran)
 * agar aturan "total bobot komponen aktif tepat 100%" dapat ditegakkan tanpa
 * membuat penyimpanan baris pertama mustahil (total sementara selalu < 100).
 */
class KomponenNilaiService
{
    /**
     * Simpan seluruh konfigurasi komponen untuk satu semester + mata pelajaran.
     *
     * @param  array<int, array{idkomponen_nilai?: int|string|null, nama?: string|null, bobot?: mixed, urutan?: mixed, aktif?: mixed}>  $baris
     * @return Collection<int, KomponenNilai>
     */
    public static function simpanKonfigurasi(int $semesterId, int $mapelId, array $baris): Collection
    {
        self::pastikanSuperadmin();

        return DB::transaction(function () use ($semesterId, $mapelId, $baris) {
            if ($semesterId < 1 || $mapelId < 1) {
                throw new DomainException('Semester dan mata pelajaran wajib dipilih.');
            }

            if (! Semester::query()->lockForUpdate()->find($semesterId)) {
                throw new DomainException('Semester tidak ditemukan.');
            }

            if (! Mapel::find($mapelId)) {
                throw new DomainException('Mata pelajaran tidak ditemukan.');
            }

            if ($baris === []) {
                throw new DomainException('Minimal satu komponen penilaian harus diisi.');
            }

            $dinormalisasi = self::normalisasiBaris($baris);

            $existing = KomponenNilai::query()
                ->where('semester_idsemester', $semesterId)
                ->where('mapel_idmapel', $mapelId)
                ->lockForUpdate()
                ->get()
                ->keyBy('idkomponen_nilai');

            $idDikirim = [];
            foreach ($dinormalisasi as $index => $item) {
                if ($item['id'] === null) {
                    continue;
                }

                $current = $existing->get($item['id']);
                if (! $current) {
                    throw new DomainException(
                        'Komponen pada baris '.($index + 1).' bukan milik semester dan mata pelajaran yang dipilih.'
                    );
                }

                if (in_array($item['id'], $idDikirim, true)) {
                    throw new DomainException('Komponen yang sama dikirim lebih dari satu kali.');
                }
                $idDikirim[] = $item['id'];

                if (! self::berubah($current, $item) || ! $current->nilaiSiswa()->exists()) {
                    continue;
                }

                throw new DomainException(
                    'Komponen "'.$current->nama.'" sudah memiliki nilai sehingga nama, bobot, dan status aktifnya tidak dapat diubah.'
                );
            }

            foreach ($existing as $current) {
                if (in_array((int) $current->idkomponen_nilai, $idDikirim, true)) {
                    continue;
                }

                if ($current->nilaiSiswa()->exists()) {
                    throw new DomainException(
                        'Komponen "'.$current->nama.'" tidak dapat dihapus karena sudah memiliki nilai.'
                    );
                }

                $current->delete();
            }

            foreach ($dinormalisasi as $item) {
                $row = $item['id'] !== null
                    ? $existing->get($item['id'])
                    : new KomponenNilai;

                if (! $row) {
                    throw new DomainException('Komponen penilaian tidak ditemukan.');
                }

                $row->semester_idsemester = $semesterId;
                $row->mapel_idmapel = $mapelId;
                $row->nama = $item['nama'];
                $row->bobot = $item['bobot'];
                $row->urutan = $item['urutan'];
                $row->aktif = $item['aktif'];
                $row->save();
            }

            return KomponenNilai::query()
                ->where('semester_idsemester', $semesterId)
                ->where('mapel_idmapel', $mapelId)
                ->orderBy('urutan')
                ->orderBy('idkomponen_nilai')
                ->get();
        });
    }

    /**
     * Total bobot komponen aktif dalam satuan persen (0-100).
     */
    public static function totalBobotAktif(int $semesterId, int $mapelId): float
    {
        $total = KomponenNilai::query()
            ->where('semester_idsemester', $semesterId)
            ->where('mapel_idmapel', $mapelId)
            ->where('aktif', true)
            ->sum('bobot');

        return round((float) $total, 2);
    }

    /**
     * @param  array<int, array{idkomponen_nilai?: int|string|null, nama?: string|null, bobot?: mixed, urutan?: mixed, aktif?: mixed}>  $baris
     * @return array<int, array{id: ?int, nama: string, bobot: float, urutan: int, aktif: bool}>
     */
    private static function normalisasiBaris(array $baris): array
    {
        $hasil = [];
        $namaTerpakai = [];
        $totalAktif = 0; // satuan seperseratus agar tepat tanpa galat floating point
        $adaAktif = false;

        foreach (array_values($baris) as $index => $item) {
            if (! is_array($item)) {
                throw new DomainException('Format komponen penilaian pada baris '.($index + 1).' tidak valid.');
            }

            $nama = trim((string) ($item['nama'] ?? ''));
            if ($nama === '') {
                throw new DomainException('Nama komponen pada baris '.($index + 1).' wajib diisi.');
            }

            if (mb_strlen($nama) > 100) {
                throw new DomainException(
                    'Nama komponen pada baris '.($index + 1).' maksimal 100 karakter.'
                );
            }

            $kunciNama = mb_strtolower($nama);
            if (isset($namaTerpakai[$kunciNama])) {
                throw new DomainException('Nama komponen "'.$nama.'" dipakai lebih dari satu kali.');
            }
            $namaTerpakai[$kunciNama] = true;

            $bobotMentah = $item['bobot'] ?? null;
            if (! is_numeric($bobotMentah)) {
                throw new DomainException(
                    'Bobot komponen "'.$nama.'" wajib berupa angka.'
                );
            }

            $bobot = (float) $bobotMentah;
            if ($bobot < 0 || $bobot > 100) {
                throw new DomainException(
                    'Bobot komponen "'.$nama.'" harus berada pada rentang 0 sampai 100.'
                );
            }

            if (abs($bobot - round($bobot, 2)) > 0.00001) {
                throw new DomainException(
                    'Bobot komponen "'.$nama.'" maksimal dua angka desimal.'
                );
            }

            $bobot = round($bobot, 2);
            $bobotSeperstus = (int) round($bobot * 100);

            $urutanMentah = $item['urutan'] ?? ($index + 1);
            if (! is_numeric($urutanMentah) || (int) $urutanMentah < 1) {
                throw new DomainException(
                    'Urutan komponen "'.$nama.'" wajib berupa angka minimal 1.'
                );
            }

            $aktif = filter_var($item['aktif'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($aktif === null) {
                throw new DomainException('Status aktif komponen "'.$nama.'" tidak valid.');
            }

            if ($aktif) {
                $adaAktif = true;
                $totalAktif += $bobotSeperstus;
            }

            $idMentah = $item['idkomponen_nilai'] ?? null;
            $id = ($idMentah === null || $idMentah === '') ? null : (int) $idMentah;

            $hasil[] = [
                'id' => $id,
                'nama' => $nama,
                'bobot' => $bobot,
                'urutan' => (int) $urutanMentah,
                'aktif' => $aktif,
            ];
        }

        if (! $adaAktif) {
            throw new DomainException('Minimal satu komponen penilaian harus berstatus aktif.');
        }

        if ($totalAktif !== 10000) {
            $total = number_format($totalAktif / 100, 2, ',', '.');

            throw new DomainException(
                'Total bobot komponen aktif harus tepat 100%. Total saat ini '.$total.'%.'
            );
        }

        return $hasil;
    }

    /**
     * @param  array{id: ?int, nama: string, bobot: float, urutan: int, aktif: bool}  $item
     */
    private static function berubah(KomponenNilai $current, array $item): bool
    {
        // Urutan tidak dikunci karena hanya memengaruhi tampilan, bukan
        // perhitungan nilai. Nama, bobot, dan status aktif dikunci.
        return trim((string) $current->nama) !== $item['nama']
            || (int) round((float) $current->bobot * 100) !== (int) round($item['bobot'] * 100)
            || (bool) $current->aktif !== $item['aktif'];
    }

    private static function pastikanSuperadmin(): void
    {
        abort_unless(auth()->user()?->level === 'superadmin', 403);
    }
}
