<?php

namespace App\Livewire;

use App\Models\DendaPengajuan;
use App\Models\Kelas;
use App\Models\KoreksiPengajuan;
use App\Models\Mapel;
use App\Models\Pembayaran;
use App\Models\Pengumuman;
use App\Models\RefundPengajuan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Walimurid;
use App\Models\WalimuridPermintaanPutus;
use App\Services\KonteksAkademikService;
use App\Support\KonteksAkademik;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Dashboard utama superadmin & admin — pusat informasi operasional sekolah.
 *
 * Otorisasi:
 * - Akses halaman: level superadmin & admin (route middleware check.level).
 * - Aktivitas terbaru: hanya superadmin (read-only dari activity_log).
 * - Aksi cepat & link antrean: hanya untuk menu yang dimiliki pengguna (usermenu).
 * - Keuangan: hanya level superadmin & admin (pola yang sama dengan modul keuangan).
 * - Pengumuman terbaru: superadmin melihat semua (termasuk draf); admin hanya yang terbit.
 *
 * Tidak ada data dummy; semua angka dari database.
 */
class DashboardComponent extends Component
{
    public function mount(): void
    {
        $this->pastikanBolehMelihat();
    }

    public function render()
    {
        // Pemeriksaan diulang di render() karena mount() hanya berjalan pada
        // pemuatan awal, sedangkan request Livewire lanjutan (update) tidak melewati mount().
        $this->pastikanBolehMelihat();

        $user = auth()->user();
        $isSuperadmin = $user?->level === 'superadmin';
        $konteks = KonteksAkademikService::ambil();
        $menus = $this->menuPengguna();
        $lihatKeuangan = in_array($user?->level, ['superadmin', 'admin'], true);

        return view('livewire.dashboard-component', [
            'pengguna' => $user,
            'waktu' => Carbon::now(config('app.timezone', 'Asia/Jakarta')),
            'konteks' => $konteks,
            'isSuperadmin' => $isSuperadmin,
            'statistik' => $this->ambilStatistik($konteks),
            'antrean' => $this->antreanPekerjaan($menus),
            'lihatKeuangan' => $lihatKeuangan,
            'keuangan' => $lihatKeuangan ? $this->ringkasanKeuangan() : null,
            'aktivitas' => $isSuperadmin ? $this->aktivitasTerbaru() : [],
            'pengumuman' => $this->pengumumanTerbaru($isSuperadmin),
            'aksiCepat' => $this->aksiCepat($menus),
            'notifikasiUnread' => $this->notifikasiBelumDibaca($user?->id),
            'komposisiPengguna' => $this->komposisiPengguna(),
            'statusTagihan' => $this->statusTagihan(),
        ]);
    }

    private function pastikanBolehMelihat(): void
    {
        abort_unless(in_array(auth()->user()?->level, ['superadmin', 'admin'], true), 403);
    }

    /** @return array<int, string> Daftar URL menu yang dimiliki pengguna (ground truth menu.access). */
    private function menuPengguna(): array
    {
        return DB::table('usermenu')
            ->join('appmenunew', 'appmenunew.idappmenunew', '=', 'usermenu.appmenunew_idappmenunew')
            ->where('usermenu.user_iduser', auth()->id())
            ->pluck('appmenunew.url')
            ->all();
    }

    private function notifikasiBelumDibaca(?int $userId): int
    {
        if (! $userId) {
            return 0;
        }

        return (int) DB::table('notifikasi_penerima')
            ->where('user_iduser', $userId)
            ->whereNull('dibaca_pada')
            ->count();
    }

    private function ambilStatistik(KonteksAkademik $konteks): array
    {
        return [
            'siswa_aktif' => (int) Siswa::where('status', 'aktif')->count(),
            'guru_aktif' => (int) DB::table('guru')->where('status', 'aktif')->count(),
            'kelas' => $konteks->valid() ? (int) Kelas::where('tahunajaran_idtahunajaran', $konteks->tahunAjaran->idtahunajaran)->count() : null,
            'mapel' => (int) Mapel::count(),
        ];
    }

    /** Item admin yang benar-benar menunggu tindakan pengguna (berdasarkan usermenu). */
    private function antreanPekerjaan(array $menus): array
    {
        $items = [];

        if (in_array('wali-antrian-verifikasi', $menus, true)) {
            $n = (int) Walimurid::where('status_hubungan', 'pending')->count();
            if ($n > 0) {
                $items[] = ['label' => 'Verifikasi wali', 'count' => $n, 'url' => '/wali-antrian-verifikasi', 'icon' => 'fa-user-check'];
            }
            $n = (int) WalimuridPermintaanPutus::where('status_permintaan', 'menunggu')->count();
            if ($n > 0) {
                $items[] = ['label' => 'Permintaan putus relasi', 'count' => $n, 'url' => '/wali-antrian-verifikasi', 'icon' => 'fa-people-arrows'];
            }
        }

        if (in_array('pembayaranmanual', $menus, true)) {
            $n = (int) Pembayaran::where('status', 'pending')->count();
            if ($n > 0) {
                $items[] = ['label' => 'Pembayaran manual', 'count' => $n, 'url' => '/pembayaranmanual', 'icon' => 'fa-hand-holding-dollar'];
            }
        }

        if (in_array('refundkoreksi', $menus, true)) {
            $n = (int) RefundPengajuan::where('status', 'pending')->count();
            if ($n > 0) {
                $items[] = ['label' => 'Pengajuan refund', 'count' => $n, 'url' => '/refundkoreksi', 'icon' => 'fa-money-bill-transfer'];
            }
            $n = (int) KoreksiPengajuan::where('status', 'pending')->count();
            if ($n > 0) {
                $items[] = ['label' => 'Pengajuan koreksi', 'count' => $n, 'url' => '/refundkoreksi', 'icon' => 'fa-pen-circle'];
            }
        }

        if (in_array('pembebasandenda', $menus, true)) {
            $n = (int) DendaPengajuan::where('status', 'pending')->count();
            if ($n > 0) {
                $items[] = ['label' => 'Pembebasan denda', 'count' => $n, 'url' => '/pembebasandenda', 'icon' => 'fa-circle-dollar-to-slot'];
            }
        }

        return $items;
    }

    private function ringkasanKeuangan(): array
    {
        $open = ['belum_dibayar', 'dibayar_sebagian'];

        $openTotal = (int) Tagihan::whereIn('status', $open)->sum('total_tagihan');
        $openCount = (int) Tagihan::whereIn('status', $open)->count();

        $now = Carbon::now(config('app.timezone', 'Asia/Jakarta'));
        $terbayarBulanIni = (int) Pembayaran::where('status', 'success')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('nominal_dibayar');

        $openAllocated = (int) DB::table('pembayaran_alokasi as a')
            ->join('pembayaran as p', 'p.idpembayaran', '=', 'a.pembayaran_idpembayaran')
            ->join('tagihan as t', 't.idtagihan', '=', 'a.tagihan_idtagihan')
            ->where('p.status', 'success')
            ->where('a.jenis', 'tagihan')
            ->whereIn('t.status', $open)
            ->sum('a.nominal');

        return [
            'open_total' => $openTotal,
            'open_count' => $openCount,
            'terbayar_bulan_ini' => $terbayarBulanIni,
            'tunggakan' => max(0, $openTotal - $openAllocated),
        ];
    }

    /** Aktivitas terbaru dari activity_log (read-only). Hanya superadmin. */
    private function aktivitasTerbaru(): array
    {
        $labels = [
            'App\\Models\\Jurusan' => 'Jurusan',
            'App\\Models\\Mapel' => 'Mata pelajaran',
            'App\\Models\\Kelas' => 'Kelas',
            'App\\Models\\Siswa' => 'Siswa',
            'App\\Models\\Guru' => 'Guru',
            'App\\Models\\Tahunajaran' => 'Tahun ajaran',
            'App\\Models\\Semester' => 'Semester',
            'App\\Models\\Pengumuman' => 'Pengumuman',
            'App\\Models\\Appmenucat' => 'Kategori menu',
            'App\\Models\\Appmenunew' => 'Menu',
            'App\\Models\\User' => 'Pengguna',
            'App\\Models\\Tagihan' => 'Tagihan',
            'App\\Models\\Pembayaran' => 'Pembayaran',
            'App\\Models\\Walimurid' => 'Relasi wali-murid',
        ];

        $icons = [
            'App\\Models\\Jurusan' => 'fa-sitemap',
            'App\\Models\\Mapel' => 'fa-book',
            'App\\Models\\Kelas' => 'fa-school',
            'App\\Models\\Siswa' => 'fa-user-graduate',
            'App\\Models\\Guru' => 'fa-chalkboard-user',
            'App\\Models\\Tahunajaran' => 'fa-calendar',
            'App\\Models\\Semester' => 'fa-calendar-days',
            'App\\Models\\Pengumuman' => 'fa-bullhorn',
            'App\\Models\\Appmenucat' => 'fa-folder',
            'App\\Models\\Appmenunew' => 'fa-bars',
            'App\\Models\\User' => 'fa-user',
            'App\\Models\\Tagihan' => 'fa-file-invoice',
            'App\\Models\\Pembayaran' => 'fa-sack-dollar',
            'App\\Models\\Walimurid' => 'fa-user-check',
        ];

        $rows = DB::table('activity_log as al')
            ->leftJoin('users as u', 'u.id', '=', 'al.causer_id')
            ->orderByDesc('al.created_at')
            ->limit(8)
            ->get(['al.id', 'al.log_name', 'al.description', 'al.subject_type', 'al.created_at', 'u.name as actor']);

        return $rows->map(function ($r) use ($labels, $icons) {
            $subject = $labels[$r->subject_type] ?? class_basename((string) $r->subject_type);
            $desc = (string) $r->description;
            $verb = match ($desc) {
                'created' => 'dibuat',
                'updated' => 'diubah',
                'deleted' => 'dihapus',
                default => $desc,
            };

            return [
                'actor' => $r->actor ?: 'Sistem',
                'subject' => $subject,
                'verb' => $verb,
                'icon' => $icons[$r->subject_type] ?? 'fa-bolt',
                'at' => Carbon::parse($r->created_at)->locale('id')->diffForHumans(),
            ];
        })->all();
    }

    private function pengumumanTerbaru(bool $isSuperadmin): array
    {
        $q = Pengumuman::query()->latest('waktu_terbit');
        if (! $isSuperadmin) {
            $q->where('status', 'terbit'); // Admin hanya melihat yang terbit
        }

        return $q->limit(5)->get(['idpengumuman', 'judul', 'isi', 'status', 'waktu_terbit', 'created_at'])
            ->map(fn ($p) => [
                'id' => $p->idpengumuman,
                'judul' => $p->judul,
                'ringkasan' => \Illuminate\Support\Str::limit(trim((string) strip_tags($p->isi)), 90),
                'status' => $p->status,
                'at' => $p->waktu_terbit ?? $p->created_at,
            ])->all();
    }

    private function aksiCepat(array $menus): array
    {
        $candidates = [
            ['label' => 'Tambah Siswa', 'url' => '/siswa', 'icon' => 'fa-user-plus', 'key' => 'siswa'],
            ['label' => 'Tambah Guru', 'url' => '/guru', 'icon' => 'fa-chalkboard-user', 'key' => 'guru'],
            ['label' => 'Buat Pengumuman', 'url' => '/pengumuman', 'icon' => 'fa-bullhorn', 'key' => 'pengumuman'],
            ['label' => 'Kelola Kelas', 'url' => '/kelas', 'icon' => 'fa-school', 'key' => 'kelas'],
            ['label' => 'Kelola Jadwal', 'url' => '/jadwalkelas', 'icon' => 'fa-calendar-days', 'key' => 'jadwalkelas'],
            ['label' => 'Data Tagihan', 'url' => '/datatagihan', 'icon' => 'fa-file-invoice', 'key' => 'datatagihan'],
            ['label' => 'Input Pembayaran', 'url' => '/pembayaranmanual', 'icon' => 'fa-hand-holding-dollar', 'key' => 'pembayaranmanual'],
            ['label' => 'Kelola Tarif', 'url' => '/tarif', 'icon' => 'fa-tags', 'key' => 'tarif'],
            ['label' => 'Verifikasi Wali', 'url' => '/wali-antrian-verifikasi', 'icon' => 'fa-user-check', 'key' => 'wali-antrian-verifikasi'],
        ];

        return array_values(array_filter($candidates, fn ($c) => in_array($c['key'], $menus, true)));
    }

    /** Komposisi pengguna per level akun (seluruh tabel users). */
    private function komposisiPengguna(): array
    {
        $c = DB::table('users')
            ->selectRaw('level, COUNT(*) as n')
            ->groupBy('level')
            ->pluck('n', 'level');

        $siswa = (int) ($c['siswa'] ?? 0);
        $guru = (int) ($c['guru'] ?? 0);
        $wali = (int) ($c['wali'] ?? 0);
        $admin = (int) (($c['superadmin'] ?? 0) + ($c['admin'] ?? 0) + ($c['walikelas'] ?? 0));

        return [
            'siswa' => $siswa,
            'guru' => $guru,
            'wali' => $wali,
            'admin' => $admin,
            'total' => $siswa + $guru + $wali + $admin,
        ];
    }

    /** Status tagihan aktif (dibatalkan dikecualikan). */
    private function statusTagihan(): array
    {
        $c = Tagihan::query()
            ->where('status', '!=', 'dibatalkan')
            ->selectRaw('status, COUNT(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $out = [
            'lunas' => (int) ($c['lunas'] ?? 0),
            'dibayar_sebagian' => (int) ($c['dibayar_sebagian'] ?? 0),
            'belum_dibayar' => (int) ($c['belum_dibayar'] ?? 0),
            'dibebaskan' => (int) ($c['dibebaskan'] ?? 0),
        ];
        $out['total'] = array_sum($out);

        return $out;
    }
}
