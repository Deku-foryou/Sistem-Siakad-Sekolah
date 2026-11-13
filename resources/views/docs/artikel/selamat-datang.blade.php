<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · MULAI DI SIAKAD</p>
    <h1 class="doc-title">Apa itu SIAKAD?</h1>
    <p class="doc-lead">SIAKAD adalah sistem informasi akademik sekolah. Semua proses administrasi sekolah — dari data siswa, jadwal pelajaran, absensi, penilaian, rapor, pengumuman, hingga keuangan — berlangsung di satu aplikasi dengan satu akun.</p>

    <div class="doc-quick">
        <div class="doc-quick-item"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i><span>Untuk siswa &amp; wali</span></div>
        <div class="doc-quick-item"><i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i><span>Untuk guru</span></div>
        <div class="doc-quick-item"><i class="fa-solid fa-user-shield" aria-hidden="true"></i><span>Untuk admin &amp; superadmin</span></div>
    </div>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>SIAKAD dirancang untuk menggantikan pencatatan manual yang tersebar di banyak buku dan berkas. Dengan SIAKAD:</p>
    <ul>
        <li><strong>Data akademik</strong> (siswa, guru, kelas, mata pelajaran, jadwal) berada dalam satu tempat.</li>
        <li><strong>Absensi dan penilaian</strong> tercatat rapi dan dapat dilihat riwayatnya.</li>
        <li><strong>Rapor</strong> dibentuk dari nilai yang sudah diinput, bukan diketik ulang.</li>
        <li><strong>Keuangan sekolah</strong> (tagihan, pembayaran, bukti, refund) tercatat di ledger.</li>
        <li><strong>Wali murid</strong> dapat memantau perkembangan anaknya dari mana saja.</li>
    </ul>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>SIAKAD memiliki lima peran utama. Hak akses masing-masing berbeda; satu akun hanya memiliki satu peran.</p>
    <ol>
        <li><strong>Superadmin</strong> — pengelola utama aplikasi; mengelola seluruh data, pengguna, pengaturan, dan modul keuangan.</li>
        <li><strong>Admin</strong> — pengelola operasional sekolah (saat ini fokus utama pada akun wali murid dan antrean verifikasi).</li>
        <li><strong>Guru</strong> — mengajar, mengisi absensi, menginput nilai, dan melihat jadwal mengajar.</li>
        <li><strong>Siswa</strong> — melihat profil, jadwal, absensi, pengumuman, rapor, dan informasi keuangan sendiri.</li>
        <li><strong>Wali murid</strong> — memantau anaknya dan mengelola keuangannya.</li>
    </ol>
    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Panduan ini berlaku untuk seluruh peran. Rincian hak akses per peran dibahas di <a href="/welcome-docs/artikel/peran">Peran Pengguna</a>.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Anda memiliki <strong>akun</strong> yang dibuat oleh administrator sekolah.</li>
        <li>Anda mengetahui <strong>username dan password</strong> Anda.</li>
        <li>Aplikasi dibuka lewat browser di <strong>jaringan sekolah</strong> atau sesuai alamat yang diberikan sekolah.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <ol>
        <li>Buka alamat aplikasi (mis. <code>http://siakad.sekolah.sch.id</code> — ganti sesuai perintah sekolah).</li>
        <li>Masukkan <strong>Username</strong> dan <strong>Password</strong>.</li>
        <li>Masukkan kode <strong>Captcha</strong> yang muncul.</li>
        <li>Klik tombol <strong>Log in</strong>.</li>
        <li>Setelah berhasil, Anda akan diarahkan ke beranda sesuai peran Anda.</li>
    </ol>
    <p>Rincian lengkap login, logout, dan ganti password ada di <a href="/welcome-docs/artikel/login">Cara Login &amp; Logout</a>.</p>

    <h2 id="status">Arti status di SIAKAD</h2>
    <p>Setiap modul memiliki statusnya masing-masing. Beberapa status yang sering dijumpai:</p>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Aktif</td><td>Data/akun dapat digunakan seperti biasa.</td></tr>
            <tr><td>Nonaktif</td><td>Data/akun tidak dapat digunakan tetapi historinya tetap tersimpan.</td></tr>
            <tr><td>Menunggu / Pending</td><td>Ada tindakan yang menunggu persetujuan/verifikasi.</td></tr>
            <tr><td>Terverifikasi</td><td>Data/tindakan telah diperiksa dan disetujui.</td></tr>
            <tr><td>Lunas</td><td>Tagihan telah dibayar sepenuhnya.</td></tr>
            <tr><td>Dibayar Sebagian</td><td>Sebagian nominal sudah dibayar.</td></tr>
            <tr><td>Dibatalkan</td><td>Data dibatalkan; tidak dihitung sebagai aktif.</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p><strong>Seorang siswa</strong> membuka SIAKAD untuk melihat jadwal pelajarannya hari ini, lalu mengecek status absensinya. <strong>Seorang guru</strong> mengisi absensi kelas dan menginput nilai ulangan. <strong>Seorang wali murid</strong> membuka SIAKAD untuk melihat tagihan SPP anak dan melaporkan pembayaran. <strong>Superadmin</strong> memantau dashboard dan mengelola data.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Kemungkinan sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Tidak bisa login</td><td>Password/Captcha salah, atau akun dinonaktifkan</td><td>Periksa kembali password &amp; captcha; hubungi administrator bila tetap gagal</td></tr>
            <tr><td>Halaman "Forbidden"</td><td>Halaman bukan untuk peran Anda</td><td>Gunakan menu sesuai peran; referensi di <a href="/welcome-docs/artikel/peran">Peran Pengguna</a></td></tr>
            <tr><td>Menu tidak muncul</td><td>Menu Anda dibatasi sesuai peran</td><td>Referensi di <a href="/welcome-docs/artikel/peran-paket-menu">Peran &amp; Paket Menu</a></td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/navigasi">Mengenal Sidebar &amp; Navigasi</a></li>
        <li><a href="/welcome-docs/artikel/peran">Peran Pengguna</a></li>
        <li><a href="/welcome-docs/artikel/login">Login &amp; Logout</a></li>
    </ul>
</section>
