<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · DASHBOARD</p>
    <h1 class="doc-title">Beranda Guru</h1>
    <p class="doc-lead">Halaman pertama guru — identitas, status akun, dan daftar siswa kelas yang Anda ampu.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Beranda Guru menampilkan identitas mengajar Anda beserta daftar siswa untuk kelas yang ditugaskan — agar absensi dan input nilai bisa dikerjakan tanpa mencari data terlebih dahulu.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Guru</strong>. Superadmin tidak memakai halaman ini (superadmin mengelola lewat menu administrasi).</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai guru.</li>
        <li>Akun guru terhubung ke data guru (bila tidak, halaman menampilkan peringatan).</li>
    </ul>

    <h2 id="langkah">Bagian-bagian halaman</h2>

    <figure class="doc-figure">
        <img src="/img/docs/beranda-guru.png" alt="Beranda Guru" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Beranda Guru: identitas, status akun, dan daftar siswa kelas Anda.</figcaption>
    </figure>

    <h3>1. Identitas</h3>
    <p>Nama dan <strong>NIP</strong> Anda, <strong>Status</strong> (aktif/nonaktif), <strong>Username</strong>, dan <strong>Status Akun</strong> (mis. <em>Sudah ganti password</em>). Tombol <strong>Ganti password</strong> membuka pengaturan akun.</p>

    <h3>2. Daftar siswa per kelas</h3>
    <p>Untuk setiap kelas yang ditugaskan pada semester aktif, ditampilkan tabel <strong>NISN · Nama · JK</strong> (L/P) beserta jumlah siswa (mis. <em>X IPA 1 (10) · 10 siswa</em>).</p>

    <h3>3. Kartu "Segera tersedia"</h3>
    <p>Dua kartu menampilkan teks <em>"Segera tersedia"</em> — fitur tersebut <strong>belum aktif</strong> (placeholder). Jangan mengandalkan kartu ini untuk pekerjaan; gunakan menu <em>Jadwal Guru</em> dan <em>Absensi Per Jam</em> untuk jadwal &amp; kehadiran per sesi.</p>

    <h2 id="status">Arti status</h2>
    <ul>
        <li><code>Aktif</code> — data guru Anda aktif.</li>
        <li><em>Segera tersedia</em> — bagian belum diisi, bukan kesalahan pada akun Anda.</li>
    </ul>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Bu Sari login: melihat 10 siswa X IPA 1 (kelas wali kelasnya), lalu membuka <strong>Absensi Harian</strong> atau <strong>Input Nilai</strong> langsung dari sidebar.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>"Data guru belum terhubung"</td><td>Akun guru belum ditautkan ke data induk guru</td><td>Hubungi superadmin — jangan coba mengaitkan sendiri</td></tr>
            <tr><td>Daftar siswa kosong</td><td>Belum ada penugasan mengajar semester aktif</td><td>Hubungi superadmin untuk penugasan</td></tr>
            <tr><td>Kartu "Segera tersedia" tidak berubah</td><td>Placeholder — memang belum aktif</td><td>Gunakan menu Jadwal Guru / Absensi Per Jam</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/jadwal-guru-siswa">Jadwal Guru &amp; Jadwal Siswa</a></li>
        <li><a href="/welcome-docs/artikel/absensi-harian">Absensi Harian</a></li>
        <li><a href="/welcome-docs/artikel/input-nilai">Input &amp; Koreksi Nilai</a></li>
    </ul>
</section>
