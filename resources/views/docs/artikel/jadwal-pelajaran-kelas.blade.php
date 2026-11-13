<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · JADWAL &amp; JAM PELAJARAN</p>
    <h1 class="doc-title">Jadwal Pelajaran per Kelas</h1>
    <p class="doc-lead">Menyusun jam pelajaran, mengisi mapel dan guru pada grid jadwal kelas, dan memahami validasi bentrok.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Jadwal kelas menentukan pelajaran apa yang berjalan kapan dan di kelas mana. Jadwal ini dibaca oleh guru (jadwal mengajarnya), siswa (jadwal pelajarannya), dan absensi per jam (jadwal pertemuan dibentuk dari jadwal kelas).</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Superadmin</strong> untuk mengelola. Guru dan siswa hanya melihat.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai Superadmin.</li>
        <li>Tahun ajaran &amp; semester aktif sudah diatur.</li>
        <li>Kelas dan mapel sudah ada.</li>
        <li>Guru sudah dihubungkan dengan mapel yang diampunya (menu <strong>Guru</strong> → kolom mapel).</li>
        <li>Jam pelajaran kelas sudah diatur di menu <strong>Jam Pelajaran</strong> (jamhari) untuk kelas bersangkutan — tanpa ini grid jadwal kosong.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>1. Atur Jam Pelajaran (Jamhari) per kelas</h3>
    <ol>
        <li>Buka menu <strong>Jam Pelajaran</strong>.</li>
        <li>Pilih <strong>Kelas</strong>.</li>
        <li>Tambah baris per hari: tentukan <strong>Hari</strong>, <strong>Jenis</strong> (<code>pelajaran</code> / <code>istirahat</code>), <strong>Jam Ke</strong>, <strong>Mulai</strong>, <strong>Selesai</strong>.</li>
        <li>Jam pelajaran tersinkron ke grid jadwal kelas.</li>
    </ol>

    <h3>2. Isi jadwal pada grid</h3>
    <ol>
        <li>Buka menu <strong>Jadwal Kelas</strong>.</li>
        <li>Filter <strong>Jurusan</strong> (opsional) lalu <strong>Kelas</strong>.</li>
        <li>Grid menampilkan <strong>Hari × Jam Ke</strong> sesuai Jam Pelajaran kelas.</li>
        <li>Klik tombol <strong>+</strong> pada slot kosong, pilih <strong>Mapel</strong> dan <strong>Guru</strong>, lalu <strong>Simpan</strong>.</li>
        <li>Untuk mengubah isi slot: klik tombol <strong>edit</strong> (pensil); untuk menghapus: tombol <strong>hapus</strong>.</li>
        <li>Slot <em>istirahat</em> ditampilkan nonaktif dan tidak bisa diisi.</li>
    </ol>

    <figure class="doc-figure">
        <img src="/img/docs/jadwalkelas.png" alt="Grid Jadwal Kelas" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Grid Jadwal Kelas: hari × jam ke. Klik + pada slot untuk mengisi mapel dan guru.</figcaption>
    </figure>

    <h3>3. Validasi otomatis saat menyimpan</h3>
    <p>Sistem menolak penyimpanan bila:</p>
    <ul>
        <li><strong>Slot sudah terisi</strong> (jam ke tersebut sudah dipakai jadwal lain).</li>
        <li><strong>Guru tidak terdaftar mengampu mapel</strong> yang dipilih.</li>
        <li><strong>Guru bentrok</strong> — ia sudah mengajar di kelas lain pada waktu tumpang tindih di hari yang sama.</li>
        <li><strong>Guru nonaktif</strong>.</li>
        <li>Kelas tidak sesuai dengan tahun ajaran semester yang aktif.</li>
    </ul>

    <h2 id="status">Arti tampilan grid</h2>
    <table class="doc-table">
        <thead><tr><th>Tampilan</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Slot kosong (+)</td><td>Belum ada jadwal pada jam tersebut</td></tr>
            <tr><td>Slot terisi (mapel + guru)</td><td>Jadwal sudah terdaftar</td></tr>
            <tr><td>Istirahat</td><td>Bukan jam pelajaran — tidak dapat diisi</td></tr>
            <tr><td>Grid kosong seluruhnya</td><td>Kelas belum punya Jam Pelajaran — atur di menu Jam Pelajaran</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Kelas <code>XII IPA 1</code> punya jam pelajaran Senin–Jumat. Superadmin mengisi Senin jam 1–2: <em>Matematika — Sari Wulandari</em>. Sistem otomatis menolak bila Sari juga dijadwalkan di kelas lain pada Senin jam 1–2.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>"Guru … sudah mengajar di … pada …"</td><td>Bentrok jadwal guru</td><td>Pilih guru lain atau geser jam pelajaran</td></tr>
            <tr><td>"Guru tidak terdaftar mengampu …"</td><td>Relasi guru–mapel belum dibuat</td><td>Hubungkan mapel pada menu Guru</td></tr>
            <tr><td>"Slot jam ini sudah terisi"</td><td>Slot sudah dipakai</td><td>Edit slot yang ada, jangan buat ganda</td></tr>
            <tr><td>Grid kosong</td><td>Jam pelajaran belum diatur / kelas belum dipilih</td><td>Atur Jam Pelajaran; pilih kelas di filter</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/jadwal-guru-siswa">Jadwal Guru &amp; Jadwal Siswa</a></li>
        <li><a href="/welcome-docs/artikel/konteks-akademik">Konteks Akademik Aktif</a></li>
        <li><a href="/welcome-docs/artikel/absensi-per-jam">Absensi Per Jam</a></li>
    </ul>
</section>
