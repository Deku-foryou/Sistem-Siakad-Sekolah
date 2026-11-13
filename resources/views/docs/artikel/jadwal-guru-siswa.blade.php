<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · JADWAL &amp; JAM PELAJARAN</p>
    <h1 class="doc-title">Jadwal Guru &amp; Jadwal Siswa</h1>
    <p class="doc-lead">Cara guru melihat jadwal mengajarnya dan siswa melihat jadwal pelajarannya — termasuk ringkasan hari ini.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Jadwal guru dan siswa adalah turunan dari Jadwal Kelas — bukan data terpisah. Menampilkan jadwal yang sama, dari sudut pandang masing-masing.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Jadwal Guru</strong>: Guru dan Superadmin. <strong>Jadwal Siswa</strong>: Siswa (dan wali yang membuka profil anaknya — lihat portal wali).</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Jadwal kelas periode berjalan sudah diisi superadmin (lihat artikel Jadwal Kelas).</li>
        <li>Untuk siswa: siswa sudah ditempatkan di kelas (Kelassiswa aktif).</li>
        <li>Untuk guru: guru sudah ditugaskan mengajar (penugasan mengajar semester aktif).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>

    <h3>Jadwal Siswa</h3>
    <ol>
        <li>Login sebagai siswa.</li>
        <li>Klik <strong>Jadwal Siswa</strong> pada sidebar.</li>
        <li>Lihat <strong>Ringkasan Hari Ini</strong> di bagian atas — pelajaran jam berapa saja hari ini.</li>
        <li>Gunakan filter <strong>Hari</strong> untuk melihat hari tertentu (Senin–Jumat/Sabtu).</li>
        <li>Tekan tombol <strong>Cetak</strong> untuk mencetak jadwal (tampilan cetak khusus).</li>
    </ol>

    <h3>Jadwal Guru</h3>
    <ol>
        <li>Login sebagai guru.</li>
        <li>Klik <strong>Jadwal Guru</strong> pada sidebar.</li>
        <li>Lihat daftar mengajar Anda per hari (jam ke, kelas, mapel).</li>
        <li>Gunakan tombol <strong>Cetak</strong> bila perlu mencetak.</li>
    </ol>

    <h3>Cara membaca jadwal</h3>
    <ul>
        <li><strong>Hari</strong> — hari dalam seminggu (1=Senin … 6=Sabtu).</li>
        <li><strong>Jam Ke</strong> — nomor urut jam pelajaran pada hari itu (istirahat tidak dihitung sebagai jam ke).</li>
        <li><strong>Jam</strong> — waktu mulai dan selesai.</li>
        <li><strong>Mapel</strong> — mata pelajaran; <strong>Kelas</strong> — rombel; <strong>Guru</strong> (untuk siswa) — pengampu.</li>
    </ul>

    <h2 id="status">Arti status</h2>
    <p>Jadwal kelas tidak memiliki status terbit/draf (berbeda dengan jadwal lama berbasis <code>publikasijadwal</code>). Yang tampil selalu jadwal periode berjalan — sesuai semester aktif.</p>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Siswa membuka Jadwal Siswa pada Senin pagi: melihat jam 1–2 Matematika di XII IPA 1, jam 3–4 Bahasa Inggris, dan seterusnya. Guru membuka Jadwal Guru: melihat ia mengajar Matematika pukul 07.00–08.30 di tiga kelas.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Jadwal siswa kosong</td><td>Belum ditempatkan di kelas / jadwal belum diisi</td><td>Periksa Kelassiswa; hubungi superadmin</td></tr>
            <tr><td>Jadwal guru kosong</td><td>Belum ada penugasan mengajar / jadwal belum diisi</td><td>Hubungi superadmin untuk penugasan</td></tr>
            <tr><td>Jam tidak sesuai</td><td>Jam pelajaran kelas berbeda dengan ekspektasi</td><td>Jam diatur per kelas di menu Jam Pelajaran</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/jadwal-pelajaran-kelas">Jadwal Pelajaran per Kelas</a></li>
        <li><a href="/welcome-docs/artikel/absensi-per-jam">Absensi Per Jam</a></li>
        <li><a href="/welcome-docs/artikel/absensi-harian">Absensi Harian</a></li>
    </ul>
</section>
