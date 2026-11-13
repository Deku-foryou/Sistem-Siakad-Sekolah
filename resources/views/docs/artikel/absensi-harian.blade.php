<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · ABSENSI</p>
    <h1 class="doc-title">Absensi Harian</h1>
    <p class="doc-lead">Mengisi dan melihat absensi harian siswa per kelas — oleh guru dan superadmin.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Absensi harian mencatat kehadiran siswa untuk satu hari — sekali isi per kelas. Data ini dipakai rapor, rekap kehadiran, dan notifikasi ketidakhadiran.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Guru</strong> (untuk kelas yang ditugaskan / wali kelas) dan <strong>Superadmin</strong>. Siswa dan wali hanya melihat hasil rekap, bukan mengisi.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai guru atau superadmin.</li>
        <li>Tahun ajaran &amp; semester aktif dipilih.</li>
        <li>Kelas sudah memiliki daftar siswa (Kelassiswa aktif).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Mengisi absensi harian</h3>
    <ol>
        <li>Buka menu <strong>Absensi Harian</strong>.</li>
        <li>Pilih <strong>Tahun Ajaran</strong>, <strong>Semester</strong>, <strong>Kelas</strong>, dan <strong>Tanggal</strong>.</li>
        <li>Daftar siswa muncul. Untuk setiap siswa pilih status: <code>Hadir</code> / <code>Sakit</code> / <code>Izin</code> / <code>Alpha</code>.</li>
        <li>Isi <strong>Catatan</strong> bila perlu (mis. "sakit tipes, surat dokter").</li>
        <li>Klik <strong>Simpan Absensi</strong>.</li>
    </ol>

    <h3>Mengubah absensi yang sudah tersimpan</h3>
    <ol>
        <li>Pilih kelas dan tanggal yang sama — data tersimpan akan dimuat.</li>
        <li>Klik tombol <strong>ubah</strong> pada baris siswa yang perlu dikoreksi (baris menjadi kuning).</li>
        <li>Ubah status/catatan, lalu klik <strong>Simpan</strong> — hanya baris yang diubah yang tersimpan.</li>
    </ol>

    <figure class="doc-figure">
        <img src="/img/docs/absensi-harian.png" alt="Halaman Absensi Harian" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Absensi Harian: pilih TA, semester, kelas dan tanggal, lalu isi status per siswa.</figcaption>
    </figure>

    <h3>Ringkasan di bagian atas</h3>
    <p>Chips ringkasan menampilkan jumlah <em>Hadir / Izin / Sakit / Alpha</em> dan jumlah yang <em>belum diisi</em>. Siswa belum diisi <strong>tidak</strong> dihitung Alpha.</p>

    <h2 id="status">Arti status kehadiran</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th><th>Warna</th></tr></thead>
        <tbody>
            <tr><td>Hadir</td><td>Siswa hadir seperti biasa</td><td>Hijau</td></tr>
            <tr><td>Sakit</td><td>Tidak hadir karena sakit (dibuktikan surat/keterangan)</td><td>Kuning</td></tr>
            <tr><td>Izin</td><td>Tidak hadir dengan izin (acara keluarga, dsb.)</td><td>Biru</td></tr>
            <tr><td>Alpha</td><td>Tidak hadir tanpa keterangan</td><td>Merah</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Guru wali kelas XII IPA 1 membuka Absensi Harian setiap pagi: memilih tanggal hari ini, menekan <em>Sakit</em> untuk satu siswa yang titip surat, <em>Izin</em> untuk dua siswa, sisanya <em>Hadir</em>, lalu klik <strong>Simpan Absensi</strong>. Ringkasan menampilkan 29 hadir, 2 izin, 1 sakit.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>"Gagal memuat data absensi"</td><td>Kelas/TA/semester tidak valid atau kelas kosong</td><td>Periksa Kelassiswa kelas bersangkutan</td></tr>
            <tr><td>Absensi tidak bisa disimpan</td><td>Ada baris belum lengkap / koneksi terputus</td><td>Pastikan semua siswa berstatus; coba simpan ulang</td></tr>
            <tr><td>Siswa ganda dalam daftar</td><td>Kelassiswa berisi penempatan ganda</td><td>Periksa penempatan siswa di Kelassiswa</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/absensi-per-jam">Absensi Per Jam</a></li>
        <li><a href="/welcome-docs/artikel/relasi-akademik">Relasi Siswa–Kelas–Mapel</a></li>
        <li><a href="/welcome-docs/artikel/jadwal-guru-siswa">Jadwal Guru &amp; Jadwal Siswa</a></li>
    </ul>
</section>
