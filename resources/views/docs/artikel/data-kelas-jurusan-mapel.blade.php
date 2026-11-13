<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · DATA MASTER AKADEMIK</p>
    <h1 class="doc-title">Kelas, Jurusan &amp; Mata Pelajaran</h1>
    <p class="doc-lead">Menyiapkan kerangka akademik — jurusan, mata pelajaran, dan kelas untuk setiap tahun ajaran.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Ketiga master ini adalah kerangka seluruh kegiatan belajar. Tanpa jurusan, mata pelajaran, dan kelas yang benar, jadwal, absensi, dan penilaian tidak dapat berjalan.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Superadmin</strong>.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai Superadmin.</li>
        <li>Tahun Ajaran sudah dibuat (kelas terikat ke tahun ajaran).</li>
        <li>Jurusan sudah dibuat (untuk kelas yang berjurusan).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>

    <h3>Jurusan</h3>
    <ol>
        <li>Buka menu <strong>Jurusan</strong>.</li>
        <li>Klik <strong>Tambah Jurusan</strong>; isi <strong>Kode</strong> (mis. <code>IPA</code>) dan <strong>Nama</strong>.</li>
        <li>Aktifkan status, lalu <strong>Simpan</strong>.</li>
    </ol>

    <h3>Mata Pelajaran</h3>
    <ol>
        <li>Buka menu <strong>Mapel</strong>.</li>
        <li>Klik <strong>Tambah Mapel</strong>; isi <strong>Kode</strong>, <strong>Nama</strong>, dan <strong>Kelompok</strong> (<code>wajib</code> / <code>pilihan</code>).</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <h3>Kelas (per tahun ajaran)</h3>
    <ol>
        <li>Buka menu <strong>Kelas</strong>.</li>
        <li>Pilih <strong>Tahun Ajaran</strong> pada filter di atas tabel.</li>
        <li>Klik <strong>Tambah Kelas</strong>; isi <strong>Nama</strong> atau biarkan terbentuk otomatis dari pilihan <strong>Tingkat</strong> (10/11/12) — <strong>Jurusan</strong> — <strong>Rombel</strong> (1, 2, dst.).</li>
        <li>Pilih <strong>Jenis</strong> (<code>induk</code> untuk kelas reguler / <code>mapel_pilihan</code> untuk kelas mapel pilihan) dan <strong>Wali Kelas</strong>.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>
    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Nama kelas terbentuk otomatis, misal <code>XI IPA 1</code>. Tombol <em>Isi Rombel Otomatis</em> menyarankan nomor rombel berikutnya.</p>

    <h3>Menempatkan siswa ke kelas</h3>
    <p>Setelah kelas dibuat, buka menu <strong>Kelassiswa</strong>, pilih tahun ajaran dan kelas, lalu tambahkan siswa yang akan ditempatkan. Status penempatan: <code>aktif</code>, <code>selesai</code>, <code>pindah</code>.</p>

    <h2 id="status">Arti status &amp; kolom</h2>
    <table class="doc-table">
        <thead><tr><th>Kolom/Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Tingkat kelas</td><td><code>10</code>, <code>11</code>, <code>12</code></td></tr>
            <tr><td>Jenis kelas</td><td><code>induk</code> = kelas reguler; <code>mapel_pilihan</code> = kelas mapel pilihan</td></tr>
            <tr><td>Rombel</td><td>Nomor urut rombel di tingkat+jurusan yang sama</td></tr>
            <tr><td>Kelompok mapel</td><td><code>wajib</code> = wajib diikuti semua; <code>pilihan</code> = dipilih siswa</td></tr>
            <tr><td>Aktif (jurusan)</td><td>Nonaktif menyembunyikan jurusan dari pilihan kelas baru</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Tahun ajaran 2026/2027 dimulai → superadmin membuat jurusan (IPA, IPS) → mapel (Matematika, Bahasa Indonesia, …) → kelas (<code>X IPA 1</code>, <code>X IPA 2</code>) → tempatkan 32 siswa per kelas lewat Kelassiswa → atur jadwal dan penugasan guru.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Kelas tidak bisa dihapus</td><td>Kelas sudah memiliki siswa/jadwal</td><td>Pindahkan dulu isinya; gunakan penghapusan hanya untuk data percobaan</td></tr>
            <tr><td>Jurusan tidak muncul di kelas</td><td>Jurusan dinonaktifkan</td><td>Aktifkan jurusan</td></tr>
            <tr><td>Mapel pilihan tidak tampil di penugasan</td><td>Kelompok mapel bukan <code>pilihan</code></td><td>Periksa kolom Kelompok</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/data-siswa-guru">Data Induk Siswa &amp; Guru</a></li>
        <li><a href="/welcome-docs/artikel/tahun-ajaran-semester">Tahun Ajaran &amp; Semester</a></li>
        <li><a href="/welcome-docs/artikel/relasi-akademik">Relasi Siswa–Kelas–Mapel</a></li>
    </ul>
</section>
