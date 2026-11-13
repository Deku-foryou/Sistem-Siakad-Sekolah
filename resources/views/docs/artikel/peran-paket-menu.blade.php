<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · MANAJEMEN PENGGUNA</p>
    <h1 class="doc-title">Peran &amp; Paket Menu</h1>
    <p class="doc-lead">Mengapa sidebar Anda berbeda dengan rekan kerja Anda — dan siapa yang mengaturnya.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Paket menu menentukan daftar menu yang muncul di sidebar setiap pengguna. Setiap pengguna memiliki satu paket yang disinkronkan otomatis berdasarkan <strong>level</strong> dan <strong>data induk</strong> akunnya.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Superadmin</strong> mengatur paket melalui manajemen data (tombol sinkronisasi di halaman Data Guru / Data Siswa) dan halaman <strong>Usermenu</strong> (penugasan menu per pengguna).</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Menu-menu sudah terdaftar di aplikasi (dikelola lewat menu <em>Appmenunew</em>).</li>
        <li>Kategori menu sudah dibuat di <em>Appmenucat</em>.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Melihat paket menu pengguna</h3>
    <ol>
        <li>Buka menu <strong>Users</strong>.</li>
        <li>Klik tombol <strong>Menu</strong> pada baris pengguna yang diinginkan.</li>
        <li>Anda diarahkan ke halaman Usermenu untuk pengguna tersebut — centang menu yang diizinkan.</li>
    </ol>

    <h3>Sinkronisasi otomatis</h3>
    <p>Setiap akun guru/siswa/wali memiliki paket standar sesuai perannya. Saat data induk disimpan, paket disinkronkan otomatis (tombol <em>Sinkronkan Paket</em> pada halaman Data Guru/Data Siswa juga dapat dipakai manual).</p>

    <h2 id="status">Pola paket standar</h2>
    <table class="doc-table">
        <thead><tr><th>Level</th><th>Menu yang muncul</th></tr></thead>
        <tbody>
            <tr><td>Superadmin</td><td>Hampir semua menu operasional</td></tr>
            <tr><td>Admin</td><td>Beranda, Notifikasi, Wali Murid &amp; Relasi, Antrian Verifikasi, Dokumentasi</td></tr>
            <tr><td>Guru</td><td>Beranda Guru, Jadwal Guru, Input Nilai, Absensi, Dokumentasi</td></tr>
            <tr><td>Siswa</td><td>Beranda Siswa, Jadwal, Histori Absensi, Rapor, Profil, Pengumuman, Notifikasi, Keuangan (bila diaktifkan)</td></tr>
            <tr><td>Wali</td><td>Beranda Wali, Relasi, Akademik &amp; Keuangan anak, Notifikasi</td></tr>
        </tbody>
    </table>
    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Perubahan paket menu tidak menghapus akses dari sisi keamanan aplikasi — authorization per halaman tetap berlaku sesuai peran.</p>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Admin baru hanya melihat menu Wali Murid &amp; Dokumentasi — menu Data Siswa dan Keuangan tidak muncul di sidebarnya dan halaman tersebut tetap ditolak bila diakses langsung.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Menu yang seharusnya ada tidak muncul</td><td>Paket belum disinkronkan</td><td>Klik <em>Sinkronkan Paket</em> di halaman data induk</td></tr>
            <tr><td>Menu muncul tapi halaman menolak (403)</td><td>Level tidak berhak — ada selisih paket dan authorization</td><td>Laporkan ke superadmin; jangan tahan menu di paket</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/kelola-pengguna">Mengelola Akun Pengguna</a></li>
        <li><a href="/welcome-docs/artikel/peran">Peran Pengguna</a></li>
        <li><a href="/welcome-docs/artikel/navigasi">Mengenal Sidebar &amp; Navigasi</a></li>
    </ul>
</section>
