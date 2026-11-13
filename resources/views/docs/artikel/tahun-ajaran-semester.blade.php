<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · TAHUN AJARAN &amp; SEMESTER</p>
    <h1 class="doc-title">Tahun Ajaran &amp; Semester</h1>
    <p class="doc-lead">Membuat tahun ajaran, menambah semester, mengaktifkan konteks akademik — gerbang utama seluruh kegiatan sekolah.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Tahun ajaran dan semester adalah <strong>konteks</strong> seluruh data akademik. Jadwal, absensi, nilai, dan rapor selalu dibaca per semester dari tahun ajaran tertentu. Tanpa konteks yang benar, modul lain tidak tahu data mana yang harus ditampilkan.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Superadmin</strong>.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai Superadmin.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Membuat tahun ajaran</h3>
    <ol>
        <li>Buka menu <strong>Tahun Ajaran</strong>.</li>
        <li>Klik <strong>Tambah</strong>; isi nama, mis. <code>2026/2027</code>.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <h3>Menambah semester</h3>
    <ol>
        <li>Pada baris tahun ajaran, klik tombol <strong>Semester</strong> (atau buka menu <strong>Semester</strong>).</li>
        <li>Klik <strong>Tambah</strong>; pilih <strong>Nama</strong>: <code>Ganjil</code> atau <code>Genap</code>.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <h3>Mengaktifkan konteks akademik</h3>
    <ol>
        <li>Pada menu <strong>Semester</strong>, klik tombol <strong>Aktifkan</strong> pada semester yang berjalan.</li>
        <li>Pada menu <strong>Tahun Ajaran</strong>, aktifkan tahun ajaran yang berjalan.</li>
        <li>Konteks akademik aktif ditampilkan di dashboard (Tahun Ajaran &amp; Semester aktif).</li>
    </ol>

    <figure class="doc-figure">
        <img src="/img/docs/tahunajaran.png" alt="Halaman Tahun Ajaran" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Menu Tahun Ajaran: daftar tahun ajaran dan status aktifnya.</figcaption>
    </figure>

    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Mengaktifkan satu semester akan menonaktifkan semester lain secara otomatis. Sama halnya dengan tahun ajaran — hanya satu yang boleh aktif pada satu waktu.</p>

    <h3>Riwayat periode akademik</h3>
    <p>Tahun ajaran lama tetap tersimpan — data nilai, jadwal, absensi dan rapor tahun lalu tidak hilang. Anda hanya berganti <em>konteks</em>, bukan menghapus data.</p>

    <h2 id="status">Arti status</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Aktif</td><td>Menjadi konteks akademik yang dipakai seluruh modul</td></tr>
            <tr><td>Nonaktif</td><td>Tidak dipakai sebagai konteks; data tetap tersimpan</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Juni 2027 → superadmin membuat <code>2027/2028</code> + semester <code>Ganjil</code> → mengaktifkannya → kelas dan jadwal tahun ajaran baru dibuat mengacu TA 2027/2028 → data 2026/2027 tetap bisa dibuka lewat filter tahun ajaran.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Dashboard menampilkan "—" pada TA/Semester</td><td>Belum ada konteks aktif</td><td>Aktifkan semester dan tahun ajaran</td></tr>
            <tr><td>Jadwal/nilai kosong setelah ganti TA</td><td>Data memang tersimpan per semester</td><td>Pilih TA/Semester yang benar di filter</td></tr>
            <tr><td>Tidak bisa menghapus TA yang dipakai</td><td>TA sudah memiliki kelas/jadwal</td><td>Jangan hapus; nonaktifkan saja</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/konteks-akademik">Konteks Akademik Aktif &amp; Pengaruhnya</a></li>
        <li><a href="/welcome-docs/artikel/jadwal-pelajaran-kelas">Jadwal Pelajaran per Kelas</a></li>
    </ul>
</section>
