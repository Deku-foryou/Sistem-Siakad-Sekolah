<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · MULAI DI SIAKAD</p>
    <h1 class="doc-title">Mengenal Sidebar &amp; Navigasi</h1>
    <p class="doc-lead">Sidebar di sisi kiri layar adalah pintu utama menuju seluruh fitur SIAKAD. Menu dikelompokkan ke dalam <strong>kelompok yang bisa dibuka–tutup (accordion)</strong> — satu kelompok terbuka pada satu waktu. Isi sidebar berbeda untuk setiap peran.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Sidebar memuat seluruh halaman yang dapat Anda buka. Dengan pengelompokan ini Anda bisa:</p>
    <ul>
        <li>Menemukan halaman yang dicari tanpa mengetik alamat URL.</li>
        <li>Mengetahui modul apa saja yang tersedia untuk peran Anda.</li>
        <li>Melihat kelompok dan menu yang sedang aktif (ditandai hijau &amp; kelompoknya terbuka otomatis).</li>
    </ul>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Seluruh pengguna yang sudah login. Isi sidebar berbeda sesuai peran:</p>
    <table class="doc-table">
        <thead><tr><th>Peran</th><th>Menu yang terlihat (ringkas)</th></tr></thead>
        <tbody>
            <tr><td>Superadmin</td><td>Hampir semua: Dashboard, Akademik, Jadwal &amp; Absensi, Penilaian &amp; Rapor, Wali Murid, Keuangan, Komunikasi, Manajemen Sistem (Users, Paket/Kategori/Kelola Menu), Fitur Legacy, Dokumentasi</td></tr>
            <tr><td>Admin</td><td>Sesuai hak yang diberikan — mis. antrean wali &amp; akun wali; plus Dokumentasi</td></tr>
            <tr><td>Guru</td><td>Beranda Guru, Jadwal &amp; Absensi (Absensi Harian, Jadwal Guru, Absensi Per Jam), Penilaian &amp; Rapor (Input Nilai, Finalisasi Rapor, Rapor Wali Kelas), Komunikasi (Notifikasi), Manajemen Sistem (Pengaturan Akun), Dokumentasi</td></tr>
            <tr><td>Siswa</td><td>Beranda Siswa, Akademik (Profil Saya), Jadwal &amp; Absensi (Jadwal Siswa, Histori Absensi), Penilaian &amp; Rapor (Rapor Siswa), Keuangan (Bayar Tagihan), Komunikasi (Pengumuman, Notifikasi), Dokumentasi</td></tr>
            <tr><td>Wali</td><td>Beranda Wali, Wali Murid (Relasi Saya, Akademik), Keuangan (Bayar Tagihan), Komunikasi (Notifikasi), Dokumentasi</td></tr>
        </tbody>
    </table>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Anda sudah login.</li>
        <li>Layar Anda cukup lebar (sidebar tampil penuh pada desktop &amp; tablet; pada layar kecil sidebar disembunyikan dan dibuka lewat tombol menu).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah &amp; struktur sidebar</h2>
    <p>Sidebar tersusun dari <strong>menu langsung</strong> dan <strong>kelompok accordion</strong>:</p>

    <figure class="doc-figure">
        <img src="/img/docs/sidebar.png" alt="Sidebar baru dengan kelompok accordion" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Sidebar SIAKAD: kelompok accordion (Keuangan terbuka menampilkan seluruh menu keuangannya), satu kelompok terbuka pada satu waktu.</figcaption>
    </figure>

    <ol>
        <li><strong>Dashboard</strong> — menu langsung, tidak masuk accordion. Tombol utamanya menuju halaman beranda sesuai peran (mis. Dashboard untuk superadmin, Beranda Siswa, Beranda Guru, atau Beranda Wali).</li>
        <li><strong>Dokumentasi</strong> — menu langsung paling bawah; membuka pusat dokumentasi ini.</li>
        <li>Kelompok accordion (klik judul untuk membuka/menutup):
            <ul>
                <li><strong>Akademik</strong> — Tahun Ajaran, Data Siswa, Data Guru, Kelas, Jurusan, Mata Pelajaran, Penempatan Siswa, Penugasan Mengajar, Profil Saya (siswa).</li>
                <li><strong>Jadwal &amp; Absensi</strong> — Jam Pelajaran, Jadwal Kelas, Jadwal Guru, Jadwal Siswa, Absensi Harian, Absensi Per Jam (khusus guru), Histori Absensi.</li>
                <li><strong>Penilaian &amp; Rapor</strong> — Komponen Nilai, KKTP, Input Nilai, Koreksi Nilai, Finalisasi Rapor, Rapor Wali Kelas, Rapor Siswa.</li>
                <li><strong>Wali Murid</strong> — Kelola Akun Wali, Antrean Verifikasi, Relasi Wali, Akademik Wali.</li>
                <li><strong>Keuangan</strong> — Dashboard Keuangan, Data Tagihan, Tarif, Beasiswa, Transaksi Pembayaran, Pembayaran Manual, Rekonsiliasi, Refund &amp; Koreksi, Pembebasan Denda, Bayar Tagihan.</li>
                <li><strong>Komunikasi</strong> — Pengumuman, Notifikasi.</li>
                <li><strong>Manajemen Sistem</strong> — Users, Paket Menu, Kategori Menu, Kelola Menu, Pengaturan Akun (Pengaturan Sistem).</li>
                <li><strong>Fitur Legacy</strong> — Pengampu dan Jadwal (Lama). Kelompok ini diberi penanda <strong>Lama</strong> — fitur tersebut adalah sistem jadwal lama yang dipertahankan hanya untuk data lama. Gunakan <em>Jadwal Kelas</em>, <em>Jam Pelajaran</em>, dan fitur baru lainnya. <strong>Kedua menu ini disembunyikan dari sidebar superadmin</strong> (menu tetap dimiliki lewat usermenu); halamannya hanya dapat dibuka lewat URL langsung oleh superadmin (mis. <code>/pengampu</code> dan <code>/jadwal</code>).</li>
            </ul>
        </li>
        <li>Kelompok yang berisi halaman aktif akan <strong>terbuka otomatis</strong> saat halaman dimuat, dan judul kelompoknya dicetak tebal hijau.</li>
        <li>Klik kelompok yang sedang terbuka akan <strong>menutupnya</strong>; membuka kelompok lain menutup kelompok sebelumnya (hanya satu terbuka).</li>
        <li>Menu yang sedang Anda buka disorot hijau di dalam kelompoknya.</li>
    </ol>

    <h2 id="status">Arti penanda di sidebar</h2>
    <table class="doc-table">
        <thead><tr><th>Penanda</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Sorot hijau (menu)</td><td>Halaman yang sedang Anda buka</td></tr>
            <tr><td>Judul kelompok tebal hijau</td><td>Kelompok yang memuat halaman aktif</td></tr>
            <tr><td>Chevron di kanan judul kelompok</td><td>Menandakan kelompok bisa dibuka/ditutup; mengarah ke atas saat terbuka</td></tr>
            <tr><td>Badge <strong>Lama</strong> di Fitur Legacy</td><td>Fitur lama, dipertahankan hanya untuk data lama — bukan jalur kerja utama; menu disembunyikan dari sidebar superadmin, akses via URL langsung</td></tr>
            <tr><td>Kelompok tidak muncul</td><td>Tidak ada satu pun menu kelompok itu yang diberikan ke akun Anda (kelompok kosong tidak ditampilkan)</td></tr>
        </tbody>
    </table>

    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> <strong>Menu yang tidak tampil bukan berarti boleh dibuka lewat URL.</strong> Hak akses tetap ditegakkan di belakang — mengetik URL langsung untuk halaman yang bukan hak Anda akan ditolak (403/404) oleh aplikasi.</p>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p><strong>Superadmin</strong> membuka kelompok <em>Keuangan</em> → <em>Data Tagihan</em>; kelompok lain menutup otomatis. <strong>Guru</strong> membuka <em>Penilaian &amp; Rapor</em> → <em>Input Nilai</em>. <strong>Siswa</strong> membuka kelompok <em>Jadwal &amp; Absensi</em> → <em>Jadwal Siswa</em>. <strong>Wali</strong> membuka kelompok <em>Wali Murid</em> → <em>Relasi Saya</em>. <strong>Admin</strong> hanya melihat kelompok yang diberikan kepadanya (mis. <em>Wali Murid</em>) plus Dokumentasi.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Kelompok tertentu tidak ada di sidebar</td><td>Anda tidak punya menu apa pun di kelompok itu</td><td>Normal — kelompok kosong tidak ditampilkan</td></tr>
            <tr><td>Menu tidak muncul padahal biasanya ada</td><td>Menu dibatasi peran/hak akses Anda</td><td>Periksa peran akun Anda di <a href="/welcome-docs/artikel/peran">Peran Pengguna</a>; hubungi admin bila perlu ditambah</td></tr>
            <tr><td>Sidebar tidak tampil di layar kecil</td><td>Mode tersembunyi pada layar kecil</td><td>Klik tombol menu (ikon garis) di pojok kiri atas untuk membuka/menutup</td></tr>
            <tr><td>Halaman 404 saat membuka menu lama</td><td>Menu di akun Anda belum disinkronkan</td><td>Klik <em>Sinkronkan Paket</em> di halaman data induk, atau laporkan ke administrator</td></tr>
            <tr><td>URL langsung tertolak meski menu tidak tampil</td><td>Hak akses memang dibatasi</td><td>Gunakan menu yang tersedia; akses paksa ditolak oleh sistem</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/peran">Peran Pengguna</a></li>
        <li><a href="/welcome-docs/artikel/peran-paket-menu">Peran &amp; Paket Menu</a></li>
        <li><a href="/welcome-docs/artikel/dashboard-utama">Membaca Dashboard</a></li>
    </ul>
</section>
