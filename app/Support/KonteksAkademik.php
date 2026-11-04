<?php

namespace App\Support;

use App\Models\Semester;
use App\Models\Tahunajaran;

/**
 * Nilai konteks akademik yang sedang berlaku.
 *
 * Dibentuk oleh KonteksAkademikService agar aturan "tahun ajaran aktif" dan
 * "semester aktif" hanya didefinisikan di satu tempat. Tidak mengubah data.
 */
class KonteksAkademik
{
    public const TAHUN_BELUM_AKTIF = 'tahun_belum_aktif';

    public const SEMESTER_BELUM_AKTIF = 'semester_belum_aktif';

    public const SEMESTER_INDUK_TIDAK_AKTIF = 'semester_induk_tidak_aktif';

    /**
     * @param  array<int, string>  $peringatan  daftar kode peringatan
     */
    public function __construct(
        public readonly ?Tahunajaran $tahunAjaran,
        public readonly ?Semester $semester,
        public readonly array $peringatan = [],
    ) {}

    public static function susun(?Tahunajaran $tahunAjaran, ?Semester $semester): self
    {
        $peringatan = [];

        if (! $tahunAjaran) {
            $peringatan[] = self::TAHUN_BELUM_AKTIF;
        }

        if (! $semester) {
            $peringatan[] = self::SEMESTER_BELUM_AKTIF;
        } elseif (! $tahunAjaran || (int) $semester->tahunajaran_idtahunajaran !== (int) $tahunAjaran->idtahunajaran) {
            // Semester aktif ada, tetapi induknya bukan tahun ajaran aktif
            // (termasuk kondisi tidak ada tahun ajaran aktif sama sekali).
            $peringatan[] = self::SEMESTER_INDUK_TIDAK_AKTIF;
        }

        return new self($tahunAjaran, $semester, $peringatan);
    }

    public function valid(): bool
    {
        return $this->peringatan === [];
    }

    public function punyaPeringatan(): bool
    {
        return $this->peringatan !== [];
    }

    public function tahunAjaranNama(): ?string
    {
        return $this->tahunAjaran?->nama;
    }

    public function semesterNama(): ?string
    {
        return $this->semester?->nama;
    }

    public function semesterTahunAjaranNama(): ?string
    {
        return $this->semester?->tahunajaran?->nama;
    }

    /**
     * Pesan peringatan siap tampil (Bahasa Indonesia).
     *
     * @return array<int, string>
     */
    public function pesanPeringatan(): array
    {
        return array_map(fn (string $kode): string => $this->pesan($kode), $this->peringatan);
    }

    private function pesan(string $kode): string
    {
        return match ($kode) {
            self::TAHUN_BELUM_AKTIF => 'Belum ada tahun ajaran aktif. Dashboard belum dapat menampilkan konteks akademik sampai tahun ajaran diaktifkan.',
            self::SEMESTER_BELUM_AKTIF => 'Belum ada semester aktif.',
            self::SEMESTER_INDUK_TIDAK_AKTIF => $this->pesanSemesterIndukTidakAktif(),
            default => 'Konteks akademik belum valid.',
        };
    }

    private function pesanSemesterIndukTidakAktif(): string
    {
        $semester = $this->semesterNama() ?: '-';
        $tahunSemester = $this->semesterTahunAjaranNama() ?: '-';

        if (! $this->tahunAjaran) {
            return "Inkonsistensi data: semester aktif ({$semester}) berasal dari tahun ajaran {$tahunSemester} yang tidak aktif.";
        }

        return "Inkonsistensi data: semester aktif ({$semester}) berasal dari tahun ajaran {$tahunSemester}, bukan tahun ajaran aktif ({$this->tahunAjaranNama()}).";
    }
}
