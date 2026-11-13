<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · PENGATURAN</p>
    <h1 class="doc-title">Pengaturan Akun &amp; Konfigurasi Sistem</h1>
    <p class="doc-lead">Pengaturan yang tersedia di dalam aplikasi — profil, email, dan password — serta apa yang dikelola di luar aplikasi.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Menjelaskan dengan jujur apa yang dapat diatur dari dalam aplikasi — dan apa yang tidak — agar tidak ada salah paham.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Semua pengguna untuk pengaturan akunnya sendiri. Tidak ada halaman "pengaturan sistem global" untuk dibuka pengguna biasa.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul><li>Login ke aplikasi.</li></ul>

    <h2 id="langkah">Pengaturan yang tersedia di dalam aplikasi</h2>
    <h3>Pengaturan Akun (menu <em>Pengaturan Akun</em>, halaman <code>/setting</code>)</h3>
    <ol>
        <li>Klik nama/foto Anda di pojok kanan atas → <strong>Setting</strong> (atau menu <em>Pengaturan Akun</em>).</li>
        <li>Ubah <strong>Nama</strong> dan <strong>Email</strong>, klik <strong>Simpan</strong>.</li>
        <li>Ganti <strong>Password</strong>: isi password lama dan password baru dua kali, klik <strong>Simpan</strong>.</li>
    </ol>
    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Perubahan nama/email langsung berlaku pada profil dan sidebar Anda.</p>

    <h3>Pengaturan yang TIDAK tersedia di dalam aplikasi</h3>
    <table class="doc-table">
        <thead><tr><th>Kebutuhan</th><th>Dikelola di mana</th></tr></thead>
        <tbody>
            <tr><td>Tahun ajaran / semester aktif</td><td>Menu <em>Tahun Ajaran</em> (superadmin)</td></tr>
            <tr><td>Tarif &amp; beasiswa</td><td>Menu <em>Tarif</em> / <em>Beasiswa</em> (superadmin &amp; admin)</td></tr>
            <tr><td>Nama sekolah, logo, identitas</td><td>Berkas aplikasi (dikelola pengembang/admin teknis), bukan halaman web</td></tr>
            <tr><td>Integrasi Midtrans (gateway)</td><td>Konfigurasi server oleh teknisi — belum diaktifkan</td></tr>
            <tr><td>Email, CAPTCHA, sesi</td><td>Konfigurasi server (di luar aplikasi) — jangan diubah tanpa teknisi</td></tr>
            <tr><td>Hak akses menu</td><td>Menu <em>Users</em> &amp; <em>Usermenu</em> (superadmin)</td></tr>
        </tbody>
    </table>
    <p class="doc-note doc-note-warn"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Jangan pernah membagikan atau menyalin kredensial/konfigurasi server kepada siapa pun. Bila konfigurasi keliru, <strong>seluruh aplikasi dapat berhenti</strong>.</p>

    <h2 id="status">Status pengaturan</h2>
    <p>Tidak ada status "pending" untuk pengaturan akun — tersimpan langsung. Perubahan konfigurasi server berlaku setelah aplikasi dimuat ulang oleh teknisi.</p>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Guru baru meminta email resmi sekolah ditampilkan pada profilnya → ia membuka Setting, mengganti email, menyimpan — selesai. Ia tidak perlu (dan tidak bisa) mengubah pengaturan server.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Perubahan nama tidak tersimpan</td><td>Formulir belum valid / koneksi</td><td>Periksa isian lalu simpan ulang</td></tr>
            <tr><td>Ingin mengganti logo sekolah</td><td>Tidak ada halaman logo</td><td>Minta teknisi mengganti berkas aset aplikasi</td></tr>
            <tr><td>CAPTCHA selalu salah</td><td>Konfigurasi server bermasalah</td><td>Laporkan ke administrator</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/password">Mengganti Password</a></li>
        <li><a href="/welcome-docs/artikel/login">Login, Logout &amp; Profil</a></li>
        <li><a href="/welcome-docs/artikel/konteks-akademik">Konteks Akademik Aktif</a></li>
    </ul>
</section>
