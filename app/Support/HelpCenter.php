<?php

namespace App\Support;

/**
 * Registri Pusat Dokumentasi SIAKAD.
 *
 * Artikel disimpan sebagai file Blade (resources/views/docs/artikel/{slug}.blade.php).
 * Tidak ada konten disimpan di database sehingga tidak ada duplikasi data dan
 * dokumentasi mudah diperbarui bersama perubahan aplikasi.
 *
 * Menambah artikel:
 * 1. Buat file blade di resources/views/docs/artikel/{slug}.blade.php (id sekstan: tujuan, penggunaan, status, masalah, terkait).
 * 2. Tambahkan baris di ARTICLES di bawah ini.
 * 3. Tambahkan route index / sidebar otomatis dari registry ini.
 */
final class HelpCenter
{
    /** Kategori dokumentasi (urut tampil). */
    public const CATEGORIES = [
        'mulai' => ['label' => 'Mulai di SIAKAD', 'icon' => 'fa-rocket'],
        'akun' => ['label' => 'Akun & Autentikasi', 'icon' => 'fa-user-lock'],
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
        'pengguna' => ['label' => 'Manajemen Pengguna', 'icon' => 'fa-users'],
        'master-akademik' => ['label' => 'Data Master Akademik', 'icon' => 'fa-database'],
        'tahunajaran' => ['label' => 'Tahun Ajaran & Semester', 'icon' => 'fa-calendar-days'],
        'jadwal' => ['label' => 'Jadwal & Jam Pelajaran', 'icon' => 'fa-calendar-week'],
        'absensi' => ['label' => 'Absensi', 'icon' => 'fa-user-check'],
        'penilaian' => ['label' => 'Penilaian', 'icon' => 'fa-pen-ruler'],
        'rapor' => ['label' => 'Rapor', 'icon' => 'fa-graduation-cap'],
        'pengumuman' => ['label' => 'Pengumuman', 'icon' => 'fa-bullhorn'],
        'notifikasi' => ['label' => 'Notifikasi', 'icon' => 'fa-bell'],
        'wali' => ['label' => 'Portal Wali Murid', 'icon' => 'fa-user-shield'],
        'keuangan' => ['label' => 'Keuangan', 'icon' => 'fa-sack-dollar'],
        'laporan' => ['label' => 'Laporan & Export', 'icon' => 'fa-file-export'],
        'pengaturan' => ['label' => 'Pengaturan Sistem', 'icon' => 'fa-gear'],
        'troubleshooting' => ['label' => 'Troubleshooting', 'icon' => 'fa-screwdriver-wrench'],
        'glosarium' => ['label' => 'Glosarium', 'icon' => 'fa-book-open'],
        'referensi-data' => ['label' => 'Referensi Data (Teknis)', 'icon' => 'fa-table'],
        'bantuan' => ['label' => 'Dokumentasi & Bantuan', 'icon' => 'fa-circle-question'],
    ];

    /**
     * Daftar artikel. Field:
     * slug, cat, title, desc, minutes (estimasi baca), popular (bool),
     * roles (array level yang boleh melihat), updated (string tanggal).
     * Default roles: semua role (login tetap wajib).
     */
    public const ARTICLES = [
        // ---------- Mulai ----------
        ['slug' => 'selamat-datang', 'cat' => 'mulai', 'title' => 'Apa itu SIAKAD?', 'desc' => 'Pengenalan SIAKAD: tujuan aplikasi, modul utama, jalur panduan per peran.', 'minutes' => 5, 'popular' => true, 'updated' => '2026-11-12'],
        ['slug' => 'navigasi', 'cat' => 'mulai', 'title' => 'Mengenal Sidebar & Navigasi', 'desc' => 'Memahami sidebar, kategori menu, indikator aktif, dan cara membuka halaman.', 'minutes' => 6, 'popular' => true, 'updated' => '2026-11-12'],
        ['slug' => 'peran', 'cat' => 'mulai', 'title' => 'Peran Pengguna: Superadmin, Admin, Guru, Siswa & Wali', 'desc' => 'Hak akses dan batasan masing-masing peran di SIAKAD.', 'minutes' => 6, 'popular' => true, 'updated' => '2026-11-12'],

        // ---------- Akun ----------
        ['slug' => 'login', 'cat' => 'akun', 'title' => 'Login, Logout & Pengaturan Profil', 'desc' => 'Cara masuk, keluar, dan memperbarui nama/email/foto profil Anda.', 'minutes' => 4, 'popular' => true, 'updated' => '2026-11-12'],
        ['slug' => 'password', 'cat' => 'akun', 'title' => 'Mengganti Password & Kewajiban Ganti Password', 'desc' => 'Cara mengganti kata sandi, dan alasan Anda kadang wajib menggantinya.', 'minutes' => 4, 'updated' => '2026-11-12'],
        ['slug' => 'keamanan-akun', 'cat' => 'akun', 'title' => 'Keamanan Akun & Pembatasan Akses', 'desc' => 'Aktivitas yang diblokir, alasan pembatasan, dan cara membuka blokir.', 'minutes' => 5, 'updated' => '2026-11-12'],
        ['slug' => 'profil-siswa', 'cat' => 'akun', 'title' => 'Profil Saya (Siswa)', 'desc' => 'Biodata lengkap Anda yang tersimpan di sekolah — baca saja.', 'minutes' => 4, 'roles' => ['siswa'], 'updated' => '2026-11-13'],

        // ---------- Dashboard ----------
        ['slug' => 'dashboard-utama', 'cat' => 'dashboard', 'title' => 'Membaca Dashboard (Superadmin & Admin)', 'desc' => 'Arti setiap kartu, grafik, antrean, aktivitas, pengumuman dan aksi cepat.', 'minutes' => 8, 'popular' => true, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'dashboard-keuangan', 'cat' => 'dashboard', 'title' => 'Membaca Dashboard Keuangan', 'desc' => 'Ringkasan tagihan, pembayaran, beasiswa, serta cara membaca angkanya.', 'minutes' => 6, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'beranda-siswa', 'cat' => 'dashboard', 'title' => 'Beranda Siswa', 'desc' => 'Biodata, status akun, dan jadwal pelajaran Anda hari ini.', 'minutes' => 4, 'roles' => ['siswa'], 'updated' => '2026-11-13'],
        ['slug' => 'beranda-guru', 'cat' => 'dashboard', 'title' => 'Beranda Guru', 'desc' => 'Identitas mengajar dan daftar siswa kelas yang Anda ampu.', 'minutes' => 4, 'roles' => ['guru'], 'updated' => '2026-11-13'],

        // ---------- Manajemen Pengguna ----------
        ['slug' => 'kelola-pengguna', 'cat' => 'pengguna', 'title' => 'Mengelola Akun Pengguna', 'desc' => 'Membuat, mengubah, menonaktifkan, dan mereset password akun pengguna.', 'minutes' => 8, 'roles' => ['superadmin'], 'updated' => '2026-11-12'],
        ['slug' => 'peran-paket-menu', 'cat' => 'pengguna', 'title' => 'Peran & Paket Menu', 'desc' => 'Bagaimana menu muncul untuk setiap pengguna dan mengapa menunya berbeda.', 'minutes' => 6, 'roles' => ['superadmin'], 'updated' => '2026-11-12'],

        // ---------- Data Master Akademik ----------
        ['slug' => 'data-siswa-guru', 'cat' => 'master-akademik', 'title' => 'Data Induk Siswa & Guru', 'desc' => 'Menambah, mencari, mengubah dan memvalidasi data siswa serta guru.', 'minutes' => 10, 'roles' => ['superadmin'], 'updated' => '2026-11-12'],
        ['slug' => 'data-kelas-jurusan-mapel', 'cat' => 'master-akademik', 'title' => 'Kelas, Jurusan & Mata Pelajaran', 'desc' => 'Mengelola kelas per tahun ajaran, jurusan, dan mapel yang diajarkan.', 'minutes' => 8, 'roles' => ['superadmin'], 'updated' => '2026-11-12'],
        ['slug' => 'relasi-akademik', 'cat' => 'master-akademik', 'title' => 'Relasi Siswa–Kelas–Mapel & Status Aktif', 'desc' => 'Bagaimana siswa ditempatkan di kelas dan apa arti status aktif pada setiap data.', 'minutes' => 6, 'updated' => '2026-11-12'],

        // ---------- Tahun Ajaran ----------
        ['slug' => 'tahun-ajaran-semester', 'cat' => 'tahunajaran', 'title' => 'Tahun Ajaran & Semester', 'desc' => 'Membuat tahun ajaran, mengatur semester, dan mengaktifkan konteks akademik.', 'minutes' => 7, 'roles' => ['superadmin'], 'updated' => '2026-11-12'],
        ['slug' => 'konteks-akademik', 'cat' => 'tahunajaran', 'title' => 'Konteks Akademik Aktif & Pengaruhnya', 'desc' => 'Apa arti konteks akademik, modul apa saja yang terdampak, dan kenapa itu penting.', 'minutes' => 6, 'updated' => '2026-11-12'],

        // ---------- Jadwal ----------
        ['slug' => 'jadwal-pelajaran-kelas', 'cat' => 'jadwal', 'title' => 'Jadwal Pelajaran per Kelas', 'desc' => 'Menyusun jam pelajaran dan mapel-guru pada grid jadwal per kelas.', 'minutes' => 10, 'roles' => ['superadmin'], 'updated' => '2026-11-12'],
        ['slug' => 'jadwal-guru-siswa', 'cat' => 'jadwal', 'title' => 'Jadwal Guru & Jadwal Siswa', 'desc' => 'Cara guru dan siswa membaca jadwal mengajar serta jam pelajaran per kelas.', 'minutes' => 6, 'roles' => ['superadmin', 'guru', 'siswa', 'wali'], 'updated' => '2026-11-12'],

        // ---------- Absensi ----------
        ['slug' => 'absensi-harian', 'cat' => 'absensi', 'title' => 'Absensi Harian', 'desc' => 'Mengisi dan melihat absensi harian kelas (guru & superadmin).', 'minutes' => 8, 'roles' => ['superadmin', 'guru'], 'updated' => '2026-11-12'],
        ['slug' => 'absensi-per-jam', 'cat' => 'absensi', 'title' => 'Absensi Per Jam', 'desc' => 'Absensi per sesi pelajaran oleh guru, status dan aturan pengisiannya.', 'minutes' => 8, 'roles' => ['guru'], 'updated' => '2026-11-12'],
        ['slug' => 'histori-absensi-siswa', 'cat' => 'absensi', 'title' => 'Histori Absensi Saya', 'desc' => 'Riwayat & rekap kehadiran Anda (harian dan per jam) + unduh CSV.', 'minutes' => 6, 'roles' => ['siswa'], 'updated' => '2026-11-13'],

        // ---------- Penilaian ----------
        ['slug' => 'komponen-nilai', 'cat' => 'penilaian', 'title' => 'Komponen Penilaian, Bobot & KKTP', 'desc' => 'Mengatur komponen nilai, bobot dan batas ketuntasan per mata pelajaran.', 'minutes' => 9, 'roles' => ['superadmin'], 'updated' => '2026-11-12'],
        ['slug' => 'input-nilai', 'cat' => 'penilaian', 'title' => 'Input & Koreksi Nilai', 'desc' => 'Penugasan mengajar, input nilai guru, dan koreksi nilai oleh superadmin.', 'minutes' => 8, 'roles' => ['superadmin', 'guru'], 'updated' => '2026-11-12'],
        ['slug' => 'nilai-akhir-finalisasi', 'cat' => 'penilaian', 'title' => 'Nilai Akhir & Finalisasi Rapor', 'desc' => 'Bagaimana nilai akhir dihitung, KKTP, dan kapan nilai dikunci.', 'minutes' => 7, 'roles' => ['superadmin', 'guru'], 'updated' => '2026-11-12'],

        // ---------- Rapor ----------
        ['slug' => 'rapor', 'cat' => 'rapor', 'title' => 'Melihat & Mencetak Rapor', 'desc' => 'Cara siswa, wali dan guru melihat rapor serta mencetaknya.', 'minutes' => 6, 'updated' => '2026-11-12'],

        // ---------- Pengumuman ----------
        ['slug' => 'pengumuman', 'cat' => 'pengumuman', 'title' => 'Membuat, Menerbitkan & Mengarsipkan Pengumuman', 'desc' => 'Draft, publish, target pembaca, dan keterkaitan dengan notifikasi.', 'minutes' => 7, 'roles' => ['superadmin'], 'updated' => '2026-11-12'],
        ['slug' => 'pengumuman-siswa', 'cat' => 'pengumuman', 'title' => 'Membaca Pengumuman (Siswa)', 'desc' => 'Pengumuman aktif dan riwayat, serta cara membaca detailnya.', 'minutes' => 4, 'roles' => ['siswa'], 'updated' => '2026-11-13'],

        // ---------- Notifikasi ----------
        ['slug' => 'notifikasi', 'cat' => 'notifikasi', 'title' => 'Notifikasi: Fungsi & Cara Menggunakannya', 'desc' => 'Melihat dan menandai notifikasi, serta hubungannya dengan pengumuman dan keuangan.', 'minutes' => 4, 'updated' => '2026-11-12'],

        // ---------- Portal Wali ----------
        ['slug' => 'akun-wali-relasi', 'cat' => 'wali', 'title' => 'Akun Wali & Pengajuan Relasi dengan Siswa', 'desc' => 'Login wali, mengajukan relasi, verifikasi admin, dan status hubungan.', 'minutes' => 8, 'roles' => ['wali', 'superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'wali-akademik-keuangan', 'cat' => 'wali', 'title' => 'Wali: Mengakses Akademik & Keuangan Anak', 'desc' => 'Melihat profil, jadwal, absensi, rapor, tagihan dan pembayaran anak Anda.', 'minutes' => 7, 'roles' => ['wali'], 'updated' => '2026-11-12'],

        // ---------- Keuangan ----------
        ['slug' => 'tarif-beasiswa', 'cat' => 'keuangan', 'title' => 'Tarif & Beasiswa (Potongan)', 'desc' => 'Mengatur tarif global/kelas/individu dan beasiswa persen/nominal.', 'minutes' => 8, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'penerbitan-tagihan', 'cat' => 'keuangan', 'title' => 'Penerbitan & Status Tagihan', 'desc' => 'Jenis tagihan, periode, status, dan bagaimana tagihan diterbitkan.', 'minutes' => 8, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'pembayaran-manual', 'cat' => 'keuangan', 'title' => 'Pembayaran Manual & Verifikasi', 'desc' => 'Catat bukti pembayaran, verifikasi/tolak, dan pengaruh terhadap tagihan.', 'minutes' => 9, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'pembayaran-online', 'cat' => 'keuangan', 'title' => 'Pembayaran Online & Payment Attempt', 'desc' => 'Skema pembayaran Midtrans, status attempt, dan kenyataan bahwa integrasi belum aktif.', 'minutes' => 6, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'saldo', 'cat' => 'keuangan', 'title' => 'Saldo Siswa & Penggunaannya', 'desc' => 'Bagaimana kelebihan bayar menjadi saldo, dan cara memakai saldo untuk tagihan.', 'minutes' => 6, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'refund-koreksi', 'cat' => 'keuangan', 'title' => 'Refund & Koreksi Transaksi', 'desc' => 'Mengajukan refund/koreksi, alasan, batas saldo aman, dan alur persetujuan.', 'minutes' => 9, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'denda-pembebasan', 'cat' => 'keuangan', 'title' => 'Denda & Pembebasan Denda', 'desc' => 'Denda keterlambatan, cara pembebasan dua tahap, batas jumlah denda.', 'minutes' => 7, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],
        ['slug' => 'rekonsiliasi', 'cat' => 'keuangan', 'title' => 'Rekonsiliasi Keuangan', 'desc' => 'Memeriksa konsistensi ledger, jadwal pemeriksaan, dan tindakan saat temuan.', 'minutes' => 9, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],

        // ---------- Laporan ----------
        ['slug' => 'export-laporan', 'cat' => 'laporan', 'title' => 'Laporan & Export', 'desc' => 'Export rekap/histori/bukti dan CSV di modul akademik dan keuangan.', 'minutes' => 6, 'roles' => ['superadmin', 'admin'], 'updated' => '2026-11-12'],

        // ---------- Pengaturan ----------
        ['slug' => 'pengaturan', 'cat' => 'pengaturan', 'title' => 'Pengaturan Akun & Konfigurasi Sistem', 'desc' => 'Pengaturan yang tersedia di aplikasi, dan apa yang dikelola di luar aplikasi.', 'minutes' => 6, 'roles' => ['superadmin', 'admin', 'guru', 'siswa', 'wali'], 'updated' => '2026-11-12'],

        // ---------- Troubleshooting ----------
        ['slug' => 'troubleshooting', 'cat' => 'troubleshooting', 'title' => 'Masalah Umum & Solusinya', 'desc' => 'Tidak bisa login, menu tidak muncul, nilai/rapor/pembayaran bermasalah, dan lainnya.', 'minutes' => 15, 'popular' => true, 'updated' => '2026-11-12'],

        // ---------- Glosarium ----------
        ['slug' => 'glosarium', 'cat' => 'glosarium', 'title' => 'Glosarium & Kamus Data', 'desc' => 'Istilah SIAKAD dari A–Z dan kamus data untuk setiap peran.', 'minutes' => 12, 'popular' => true, 'updated' => '2026-11-12'],

        // ---------- Referensi Data (Teknis) ----------
        ['slug' => 'referensi-tabel', 'cat' => 'referensi-data', 'title' => 'Referensi Data — Tabel & Kolom', 'desc' => 'Tabel, nama kolom, tipe data, PK/FK, enum dan modul yang menggunakannya.', 'minutes' => 40, 'roles' => ['superadmin', 'admin', 'guru', 'siswa', 'wali'], 'updated' => '2026-11-12'],
        ['slug' => 'referensi-hubungan', 'cat' => 'referensi-data', 'title' => 'Referensi Data — Relasi & Alur', 'desc' => 'Diagram relasi antar tabel, alur data per modul, dan contoh sintetis.', 'minutes' => 25, 'roles' => ['superadmin', 'admin', 'guru', 'siswa', 'wali'], 'updated' => '2026-11-12'],

        // ---------- Bantuan ----------
        ['slug' => 'pusat-bantuan', 'cat' => 'bantuan', 'title' => 'Pusat Bantuan & Cara Melapor Masalah', 'desc' => 'Cara mencari solusi di dokumentasi dan cara melaporkan masalah dengan benar.', 'minutes' => 4, 'popular' => true, 'updated' => '2026-11-12'],
    ];

    /** @return array<string, array<string,mixed>> */
    public static function categories(): array
    {
        $out = [];
        foreach (self::CATEGORIES as $slug => $meta) {
            $out[$slug] = ['slug' => $slug, 'label' => $meta['label'], 'icon' => $meta['icon']];
        }

        return $out;
    }

    /** @return array<string, array<string,mixed>> slug => meta */
    public static function articles(): array
    {
        $out = [];
        foreach (self::ARTICLES as $a) {
            $a['roles'] = $a['roles'] ?? ['superadmin', 'admin', 'guru', 'siswa', 'wali'];
            $a['toc'] = self::tocFor($a['slug']);
            $out[$a['slug']] = $a;
        }

        return $out;
    }

    /** Daftar artikel untuk satu kategori (konsisten, terurut berdasarkan title). */
    public static function byCategory(string $category): array
    {
        return collect(self::articles())
            ->filter(fn ($a) => $a['cat'] === $category)
            ->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** Artikel sesuai role pengguna (urut kategori sesuai CATEGORIES, lalu judul). */
    public static function articlesForUser(string $level): array
    {
        $urutan = array_flip(array_keys(self::CATEGORIES));

        return collect(self::articles())
            ->filter(fn ($a) => in_array($level, $a['roles'], true))
            ->sortBy(fn ($a) => sprintf('%02d-%s', $urutan[$a['cat']] ?? 99, $a['title']), SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** Artikel populer (untuk landing), sesuai role. */
    public static function popularForUser(string $level, int $limit = 6): array
    {
        return collect(self::articles())
            ->filter(fn ($a) => in_array($level, $a['roles'], true) && ($a['popular'] ?? false))
            ->take($limit)
            ->values()
            ->all();
    }

    /** Terbaru diubah (untuk landing), sesuai role. */
    public static function updatedForUser(string $level, int $limit = 6): array
    {
        return collect(self::articles())
            ->filter(fn ($a) => in_array($level, $a['roles'], true))
            ->sortByDesc('updated')
            ->values()
            ->take($limit)
            ->all();
    }

    /** Pencarian berdasarkan judul, ringkasan, tags dan kategori. */
    public static function search(string $query, string $level): array
    {
        $q = trim(mb_strtolower($query));
        if ($q === '') {
            return [];
        }

        return collect(self::articles())
            ->filter(fn ($a) => in_array($level, $a['roles'], true))
            ->filter(function ($a) use ($q) {
                $hay = mb_strtolower($a['title'].' '.$a['desc'].' '.(self::CATEGORIES[$a['cat']]['label'] ?? '').' '.$a['slug']);

                return str_contains($hay, $q);
            })
            ->values()
            ->all();
    }

    /** Kategori yang punya minimal satu artikel untuk role tertentu. */
    public static function categoriesWithArticles(string $level): array
    {
        $articles = self::articlesForUser($level);
        $present = collect($articles)->pluck('cat')->unique()->all();

        $out = [];
        foreach (self::CATEGORIES as $slug => $meta) {
            if (in_array($slug, $present, true)) {
                $out[$slug] = ['slug' => $slug, 'label' => $meta['label'], 'icon' => $meta['icon']];
            }
        }

        return $out;
    }

    public static function article(string $slug, string $level): ?array
    {
        $a = self::articles()[$slug] ?? null;

        if (! $a || ! in_array($level, $a['roles'], true)) {
            return null;
        }

        return $a;
    }

    /** Navigasi sebelumnya/ikutnya (lintas semua kategori, sesuai role). */
    public static function prevNext(string $slug, string $level): array
    {
        $list = collect(self::articlesForUser($level))->values();
        $idx = $list->search(fn ($a) => $a['slug'] === $slug);

        return [
            'prev' => is_int($idx) && $idx > 0 ? $list[$idx - 1] : null,
            'next' => is_int($idx) && $idx < $list->count() - 1 ? $list[$idx + 1] : null,
        ];
    }

    /** Daftar isi: seksyon standar yang selalu ada di setiap artikel. */
    private static function tocFor(string $slug): array
    {
        $sections = [
            'tujuan' => 'Tujuan fitur',
            'pengguna' => 'Siapa yang dapat menggunakan',
            'prasyarat' => 'Prasyarat',
            'langkah' => 'Langkah-langkah',
            'status' => 'Arti status',
            'contoh' => 'Contoh penggunaan',
            'masalah' => 'Masalah umum',
            'terkait' => 'Artikel terkait',
        ];

        if (in_array($slug, ['referensi-tabel', 'referensi-hubungan'], true)) {
            unset($sections['contoh'], $sections['masalah'], $sections['pengguna']);
        }

        return $sections;
    }
}
