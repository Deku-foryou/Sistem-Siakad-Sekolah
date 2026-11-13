<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · MANAJEMEN PENGGUNA</p>
    <h1 class="doc-title">Mengelola Akun Pengguna</h1>
    <p class="doc-lead">Membuat akun, mengatur peran, menonaktifkan, dan mereset password pengguna — kewenangan tertingi dipegang Superadmin.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Halaman <strong>Users</strong> (menu <em>Users</em>) adalah pusat pengelolaan akun. Dari sini Anda membuat akun untuk seluruh peran (superadmin, admin, guru, siswa, wali), menentukan hak akses (paket menu), dan mengatur keamanan akun.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Superadmin</strong>. Admin, guru, siswa dan wali tidak dapat membuka halaman ini.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai Superadmin.</li>
        <li>Untuk akun guru/siswa, data master guru/siswa sudah tersedia — karena akun guru/siswa tersambung dengan data induk (guru ↔ akun, siswa ↔ akun).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Membuat akun baru</h3>
    <ol>
        <li>Buka menu <strong>Users</strong> pada sidebar.</li>
        <li>Klik tombol <strong>Tambah User</strong>.</li>
        <li>Isi <strong>Nama</strong>, <strong>Username</strong>, <strong>Email</strong> (opsional), dan <strong>Password</strong> awal.</li>
        <li>Pilih <strong>Level</strong>: <code>superadmin</code>, <code>admin</code>, <code>guru</code>, <code>siswa</code>, <code>walikelas</code> atau <code>wali</code>.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>
    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Untuk akun <strong>guru</strong> dan <strong>siswa</strong>, disarankan melengkapi data induknya terlebih dahulu karena sinkronisasi paket dilakukan dari halaman data guru/siswa.</p>

    <h3>Mengubah data pengguna</h3>
    <ol>
        <li>Pada daftar pengguna, klik tombol <strong>Edit</strong> pada baris yang dimaksud.</li>
        <li>Ubah nama, email, level, atau password bila diperlukan.</li>
        <li>Klik <strong>Update</strong>.</li>
    </ol>

    <h3>Mereset password pengguna</h3>
    <ol>
        <li>Pada baris pengguna, klik tombol <strong>Reset Password</strong>.</li>
        <li>Password baru ditampilkan satu kali — catat dan serahkan ke pengguna dengan cara aman.</li>
        <li>Setelah direset, pengguna disarankan mengganti password tersebut.</li>
    </ol>

    <h3>Menghapus / menonaktifkan akun</h3>
    <p>Penghapusan bersifat permanen untuk akun yang belum dipakai. Untuk akun guru/siswa/wali, gunakan kolom <strong>status</strong> pada data induknya (aktif/nonaktif) agar historinya tetap tersimpan.</p>

    <h2 id="status">Arti status &amp; kolom</h2>
    <table class="doc-table">
        <thead><tr><th>Kolom / status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Level</td><td>Peran akun: <code>superadmin</code>, <code>admin</code>, <code>guru</code>, <code>siswa</code>, <code>walikelas</code>, <code>wali</code></td></tr>
            <tr><td>Status akun (aktif/nonaktif)</td><td>Nonaktif memblokir login tanpa menghapus data</td></tr>
            <tr><td>Wajib ganti password</td><td>Bila aktif, pengguna diarahkan mengganti password pada login berikutnya</td></tr>
            <tr><td>Paket menu</td><td>Daftar menu yang muncul di sidebar pengguna — diatur lewat halaman Usermenu</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Sekolah menerima wali murid baru → admin membuat akun <code>wali</code> dengan level <code>wali</code> → wali login, mengajukan relasi dengan anaknya → admin memverifikasi di halaman Wali Murid &amp; Relasi.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Username sudah dipakai</td><td>Username harus unik</td><td>Gunakan kombinasi lain (mis. <code>nama.123</code>)</td></tr>
            <tr><td>Akun guru dibuat tapi menu kosong</td><td>Paket menu belum disinkronkan</td><td>Klik tombol sinkronisasi paket di halaman Data Guru</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/peran-paket-menu">Peran &amp; Paket Menu</a></li>
        <li><a href="/welcome-docs/artikel/data-siswa-guru">Data Induk Siswa &amp; Guru</a></li>
        <li><a href="/welcome-docs/artikel/peran">Peran Pengguna</a></li>
    </ul>
</section>
