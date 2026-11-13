<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · ABSENSI</p>
    <h1 class="doc-title">Histori Absensi Saya</h1>
    <p class="doc-lead">Riwayat kehadiran Anda — harian dan per jam — beserta rekap dan unduhan CSV.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Histori Absensi memungkinkan Anda memeriksa sendiri: kapan Anda absen, statusnya apa, dan bagaimana rekapitulasinya per semester — tanpa harus bertanya ke guru.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Siswa</strong> (data milik Anda). Wali murid melihat rekap anak lewat portal wali.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul><li>Login sebagai siswa.</li></ul>

    <h2 id="langkah">Bagian-bagian halaman</h2>

    <figure class="doc-figure">
        <img src="/img/docs/absensi-siswa.png" alt="Histori Absensi" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Histori Absensi: rekap kehadiran (harian &amp; per jam) dan daftar detail dengan filter.</figcaption>
    </figure>

    <h3>1. Rekap Kehadiran</h3>
    <p>Bagian atas menampilkan dua kartu:</p>
    <ul>
        <li><strong>Harian</strong> — hadir/sakit/izin/alpha <strong>semester &amp; tahun ajaran aktif</strong> (tidak dipengaruhi filter di bawah).</li>
        <li><strong>Per Jam</strong> — rekap seluruh riwayat pertemuan (tidak dibatasi semester).</li>
    </ul>
    <p>Keduanya menampilkan persentase kehadiran (mis. <em>Kehadiran 100,0%</em>).</p>

    <h3>2. Tab Harian / Per Jam</h3>
    <p>Pilih tab untuk melihat detail. Keduanya punya filter:</p>
    <ul>
        <li><strong>Dari tanggal</strong> dan <strong>Sampai tanggal</strong> — rentang tanggal (boleh kosong).</li>
        <li><strong>Status</strong> — Semua / Hadir / Sakit / Izin / Alpha.</li>
        <li>Tombol <strong>Reset filter</strong> untuk mengosongkan kembali.</li>
    </ul>
    <p>Chips ringkasan (mis. <em>Total 10 · Hadir 10</em>) mengikuti filter yang sedang aktif.</p>

    <h3>3. Tabel detail</h3>
    <p><strong>Tanggal · Hari · Kelas · Status · Catatan</strong> (Harian) atau <strong>Tanggal · Jam · Waktu · Kelas · Mata Pelajaran · Status · Keterangan</strong> (Per Jam). Tabel dipaginasi; entri yang belum diisi guru tidak ditampilkan.</p>

    <h3>4. Unduh Rekap (CSV)</h3>
    <p>Tombol <strong>Unduh Rekap (CSV)</strong> mengunduh berkas CSV (delimiter <code>;</code>, UTF-8) berisi rekap harian (semester aktif), rekap per jam (riwayat), dan daftar detail — berguna untuk beasiswa, lomba, atau arsip orang tua.</p>

    <h2 id="status">Arti status</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Hadir</td><td>Anda tercatat hadir</td></tr>
            <tr><td>Sakit</td><td>Sakit (dengan/tanpa surat dokter)</td></tr>
            <tr><td>Izin</td><td>Izin (acara keluarga, dll.)</td></tr>
            <tr><td>Alpha</td><td>Tidak hadir tanpa keterangan</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Untuk laporan beasiswa, Andre menekan <strong>Unduh Rekap (CSV)</strong>, membuka berkas di Excel, dan melampirkan halaman rekap ke berkasnya — tanpa perlu meminta surat keterangan aktif dari sekolah.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Data hari tertentu tidak tampil</td><td>Guru belum mengisi absensi tanggal itu</td><td>Hanya absensi yang sudah diisi yang tampil — tunggu/laporkan ke guru</td></tr>
            <tr><td>CSV terbuka berantakan di Excel</td><td>Pemisah belum dikenali</td><td>Buka via Data → From Text/CSV, pilih delimiter <code>;</code></td></tr>
            <tr><td>Rekap per jam berbeda dengan harian</td><td>Dua jenis absensi diisi oleh guru yang berbeda</td><td>Wajar — keduanya dicatat terpisah; laporkan bila ada yang jelas salah</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/absensi-harian">Absensi Harian</a></li>
        <li><a href="/welcome-docs/artikel/absensi-per-jam">Absensi Per Jam</a></li>
        <li><a href="/welcome-docs/artikel/export-laporan">Laporan &amp; Export</a></li>
    </ul>
</section>
