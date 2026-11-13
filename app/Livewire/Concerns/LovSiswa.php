<?php

namespace App\Livewire\Concerns;

use App\Models\Siswa;
use App\Services\LovService;
use App\Support\Lov;

/**
 * LOV Siswa — state dan aksi bersama untuk picker siswa (maks 1 komponen = 1 siswa / multi).
 *
 * Kontrak:
 * - State: $lovSearch (teks), $lovHasil (hasil polos id/label/sublabel), $siswaLabel (single),
 *   $siswaTerpilih (multi: ['id'=>int,'nama'=>string,'nis'=>string|null]), $siswaIds (multi: array<int>).
 * - Method publik: updatedLovSearch(), pilihLovSiswa(int), gantiLovSiswa(), hapusLovSiswa(), hapusSiswaTerpilih(int).
 * - Hook dilimpaki komponen: setelahLovSiswaDipilih(Siswa $row) / setelahLovSiswaDihapus()
 *   (mis. PembayaranManual mereset $tagihanId saat siswa diganti/dihapus).
 * - Validasi ulang SELALU di backend sebelum menyimpan (selected() / selectedMany()).
 *
 * Catatan: trait hanya memakai *nama properti tercantum*; untuk komponen yang tidak punya
 * $siswaIds (mis. PembayaranManual), sinkronisasi siswaIds di-skip otomatis.
 */
trait LovSiswa
{
    public string $lovSearch = '';

    /** @var array<int, array{id:int,label:string,sublabel:string}> */
    public array $lovHasil = [];

    /** Label siswa terpilih (mode single). Kosong bila belum dipilih. */
    public string $siswaLabel = '';

    /** Chips siswa terpilih (mode multi). */
    public array $siswaTerpilih = [];

    public function updatedLovSearch(string $value): void
    {
        $this->lovHasil = LovService::search(Lov::KEY_SISWA, $value);
    }

    public function pilihLovSiswa(int $id): void
    {
        $row = LovService::selected(Lov::KEY_SISWA, $id);
        if (! $row instanceof Siswa) {
            $this->addError('lovSearch', 'Siswa tidak ditemukan atau tidak aktif.');
            $this->lovHasil = [];

            return;
        }

        if ($this->lovMode() === 'multi') {
            foreach ($this->siswaTerpilih as $terpilih) {
                if ((int) $terpilih['id'] === (int) $row->idsiswa) {
                    $this->reset('lovSearch', 'lovHasil');

                    return; // tidak menyimpan duplikat
                }
            }
            $this->siswaTerpilih[] = ['id' => (int) $row->idsiswa, 'nama' => $row->nama, 'nis' => $row->nis];
            $this->syncSiswaIdsDariTerpilih();
            $this->reset('lovSearch', 'lovHasil');
            $this->setelahLovSiswaDipilih($row);

            return;
        }

        // Mode single
        $this->siswaId = (string) $row->idsiswa;
        $this->siswaLabel = $row->nama;
        $this->reset('lovSearch', 'lovHasil');
        $this->setelahLovSiswaDipilih($row);
    }

    /** Mode single: tampilkan kembali kolom pencarian dengan pilihan saat ini (label tetap tampil sampai dihapus). */
    public function gantiLovSiswa(): void
    {
        $this->reset('lovSearch', 'lovHasil');
        $this->resetErrorBag(['lovSearch']);
    }

    /** Mode single: kosongkan pilihan siswa (dan hook komponen bila ada). */
    public function hapusLovSiswa(): void
    {
        $this->siswaId = '';
        $this->siswaLabel = '';
        $this->reset('lovSearch', 'lovHasil');
        $this->resetErrorBag(['lovSearch']);
        $this->setelahLovSiswaDihapus();
    }

    /** Mode multi: hapus satu siswa dari daftar. */
    public function hapusSiswaTerpilih(int $id): void
    {
        $this->siswaTerpilih = array_values(array_filter(
            $this->siswaTerpilih,
            fn ($s) => (int) $s['id'] !== $id
        ));
        $this->syncSiswaIdsDariTerpilih();
        $this->setelahLovSiswaDihapus();
    }

    /** Isi (untuk edit) daftar terpilih dari DB — hanya siswa AKTIF yang ditampilkan. */
    protected function isiTerpilihDariIds(array $ids): void
    {
        $rows = LovService::selectedMany(Lov::KEY_SISWA, $ids);
        $this->siswaTerpilih = $rows->map(fn (Siswa $s) => ['id' => (int) $s->idsiswa, 'nama' => $s->nama, 'nis' => $s->nis])->all();
        $this->syncSiswaIdsDariTerpilih();
    }

    /** Rebuild array<int> siswaIds dari chips (deduplikat, urut stabil). */
    protected function syncSiswaIdsDariTerpilih(): void
    {
        if (property_exists($this, 'siswaIds')) {
            $this->siswaIds = array_values(array_unique(array_map(
                fn ($s) => (int) $s['id'],
                $this->siswaTerpilih
            )));
        }
    }

    protected function lovMode(): string
    {
        return property_exists($this, 'siswaIds') ? 'multi' : 'single';
    }

    public function lovReset(): void
    {
        $this->siswaTerpilih = [];
        $this->siswaLabel = '';
        if (property_exists($this, 'siswaId')) {
            $this->siswaId = '';
        }
        if (property_exists($this, 'siswaIds')) {
            $this->siswaIds = [];
        }
        $this->reset('lovSearch', 'lovHasil');
        $this->resetErrorBag();
    }

    /** Hook komponen (opsional) — dipanggil setelah siswa terpilih. */
    protected function setelahLovSiswaDipilih(Siswa $row): void
    {
        // default: tidak ada
    }

    /** Hook komponen (opsional) — dipanggil setelah siswa dihapus/diganti. */
    protected function setelahLovSiswaDihapus(): void
    {
        // default: tidak ada
    }
}
