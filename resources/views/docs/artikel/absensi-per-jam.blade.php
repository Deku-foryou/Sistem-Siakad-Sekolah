<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · ABSENSI</p>
    <h1 class="doc-title">Absensi Per Jam</h1>
    <p class="doc-lead">Absensi per sesi pelajaran (per pertemuan), bukan per hari — beserta batas waktu edit guru.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Berbeda dengan Absensi Harian (sekali per hari per kelas), Absensi Per Jam mencatat kehadiran siswa <strong>per pertemuan pelajaran</strong>. Data ini membentuk histori kehadiran per mapel yang dibaca siswa dan wali.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Guru</strong> yang memiliki penugasan mengajar. Superadmin belum mengakses halaman ini pada tahap ini.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai Guru.</li>
        <li>Penugasan mengajar Anda sudah terdaftar (Semester aktif).</li>
        <li>Jadwal kelas periode itu sudah diisi — pertemuan digenerate dari jadwal.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <ol>
        <li>Buka menu <strong>Absensi Per Jam</strong>.</li>
        <li>Pilih <strong>Tanggal pertemuan</strong> pada kolom tanggal.</li>
        <li>Sistem menampilkan <strong>Pertemuan Terjadwal</strong> Anda pada tanggal itu (kelas, mapel, jam ke, waktu). Pertemuan pada tanggal terpilih digenerate otomatis dari jadwal bila belum tersedia.</li>
        <li>Klik tombol <strong>Isi absensi</strong> pada pertemuan yang diinginkan.</li>
        <li>Isi status tiap siswa: <code>hadir</code> / <code>sakit</code> / <code>izin</code> / <code>alpha</code>.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <figure class="doc-figure">
        <img src="/img/docs/absensi-perjam.png" alt="Halaman Absensi Per Jam" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Daftar pertemuan terjadwal pada tanggal terpilih; pilih pertemuan lalu isi status siswa.</figcaption>
    </figure>

    <h2 id="status">Arti status &amp; aturan penting</h2>
    <table class="doc-table">
        <thead><tr><th>Keterangan</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Status pertemuan <code>terjadwal</code></td><td>Dapat diabsen</td></tr>
            <tr><td>Status pertemuan <code>batal</code> / <code>selesai</code></td><td><strong>Tidak dapat diabsen</strong></td></tr>
            <tr><td>Batas edit guru (H+1)</td><td>Guru hanya boleh mengisi/mengubah absensi sampai <strong>H+1</strong> setelah tanggal pertemuan. Lewat itu, tombol Simpan dinonaktifkan.</td></tr>
            <tr><td>Empty state "Tidak ada pertemuan"</td><td>Tidak ada jadwal mengajar Anda pada tanggal itu</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Selasa 10 Nov: Sari punya Matematika jam 1–2 di XII IPA 1. Ia membuka Absensi Per Jam, memilih tanggal 10 Nov, mengklik Isi Absensi pada pertemuan tersebut — 29 siswa Hadir, 1 Izin — lalu Simpan. Esok harinya ia masih bisa memperbaiki; setelahnya tidak bisa.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Daftar pertemuan kosong</td><td>Tidak ada jadwal / tanggal salah</td><td>Periksa tanggal; pastikan jadwal kelas terisi</td></tr>
            <tr><td>Tombol Simpan nonaktif</td><td>Pertemuan batal/selesai atau lewat H+1</td><td>Tidak dapat diubah guru; hubungi superadmin bila perlu koreksi histori</td></tr>
            <tr><td>Tidak menemukan menit ke-berapa</td><td>Yang ditampilkan jam ke dan waktu mulai–selesai pertemuan</td><td>Lihat kolom Waktu</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/absensi-harian">Absensi Harian</a></li>
        <li><a href="/welcome-docs/artikel/jadwal-guru-siswa">Jadwal Guru &amp; Jadwal Siswa</a></li>
        <li><a href="/welcome-docs/artikel/jadwal-pelajaran-kelas">Jadwal Pelajaran per Kelas</a></li>
    </ul>
</section>
