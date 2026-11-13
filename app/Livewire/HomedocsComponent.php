<?php

namespace App\Livewire;

use App\Support\HelpCenter;
use Livewire\Component;

/**
 * Pusat Dokumentasi SIAKAD — seluruh pengguna yang sudah login boleh membaca.
 *
 * Status tampilan:
 * - $q terisi            → daftar hasil pencarian
 * - $activeArtikel terisi → satu artikel penuh
 * - selain itu           → beranda dokumentasi (hero, kategori, populer, per-peran)
 */
class HomedocsComponent extends Component
{
    public string $q = '';

    public ?string $activeArtikel = null;

    /** @var array<int, string> daftar slug kategori yang terbuka */
    public array $expanded = ['mulai'];

    public bool $sidebarTerbuka = false;

    private const ROLES = ['superadmin', 'admin', 'guru', 'siswa', 'wali'];

    public function mount(?string $slug = null): void
    {
        abort_unless(in_array(auth()->user()?->level, self::ROLES, true), 403);

        if ($slug !== null) {
            abort_unless(HelpCenter::article($slug, $this->level()) !== null, 404);
            $this->activeArtikel = $slug;
            $this->expanded = $this->kategoriTerbukaUntuk($slug);
        } elseif (($q = request()->query('q')) !== null && is_string($q)) {
            $this->q = trim($q);
        }
    }

    public function pilihArtikel(string $slug): void
    {
        abort_unless(in_array(auth()->user()?->level, self::ROLES, true), 403);
        abort_unless(HelpCenter::article($slug, $this->level()) !== null, 404);

        $this->activeArtikel = $slug;
        $this->q = '';
        $this->sidebarTerbuka = false;
        $this->expanded = $this->kategoriTerbukaUntuk($slug);
    }

    public function kembaliKeBeranda(): void
    {
        $this->activeArtikel = null;
        $this->q = '';
        $this->sidebarTerbuka = false;
    }

    public function toggleKategori(string $kategori): void
    {
        if (in_array($kategori, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$kategori]));
        } else {
            $this->expanded[] = $kategori;
        }
    }

    public function toggleSidebar(): void
    {
        $this->sidebarTerbuka = ! $this->sidebarTerbuka;
    }

    public function render()
    {
        abort_unless(in_array(auth()->user()?->level, self::ROLES, true), 403);

        $level = $this->level();
        $kategori = HelpCenter::categoriesWithArticles($level);

        $data = [
            'level' => $level,
            'kategori' => $kategori,
            'artikelPerKategori' => collect($kategori)->mapWithKeys(
                fn ($meta, $slug) => [$slug => HelpCenter::byCategory($slug)]
            )->all(),
            'jumlahArtikel' => count(HelpCenter::articlesForUser($level)),
            'hasilCari' => $this->q !== '' ? HelpCenter::search($this->q, $level) : [],
            'aktif' => $this->activeArtikel ? HelpCenter::article($this->activeArtikel, $level) : null,
            'prevNext' => $this->activeArtikel ? HelpCenter::prevNext($this->activeArtikel, $level) : null,
            'populer' => HelpCenter::popularForUser($level, 5),
            'terbaru' => HelpCenter::updatedForUser($level, 5),
            'terlihat' => collect(HelpCenter::articlesForUser($level))->pluck('slug')->flip()->all(),
        ];

        return view('livewire.homedocs-component', $data)->layout('layout.docslayout', ['title' => 'Dokumentasi', 'docsAz' => true]);
    }

    private function level(): string
    {
        return (string) auth()->user()->level;
    }

    /** Buka kategori yang memuat artikel terpilih (sisanya tertutup). */
    private function kategoriTerbukaUntuk(string $slug): array
    {
        $artikel = HelpCenter::article($slug, $this->level());

        return $artikel ? [$artikel['cat']] : ['mulai'];
    }
}
