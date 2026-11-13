<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · DATA MASTER AKADEMIK</p>
    <h1 class="doc-title">Data Induk Siswa &amp; Guru</h1>
    <p class="doc-lead">Menambah, mencari, mengubah, dan memvalidasi data siswa dan guru — fondasi seluruh modul akademik dan keuangan.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Halaman <strong>Siswa</strong> dan <strong>Guru</strong> menyimpan identitas peserta didik dan tenaga pengajar. Data ini tersambung ke akun pengguna, penempatan kelas, jadwal, absensi, nilai, dan tagihan.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Superadmin</strong>.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai Superadmin.</li>
        <li>Tahun Ajaran aktif sudah dibuat (untuk penempatan kelas siswa).</li>
        <li>Untuk data siswa baru: siapkan NISN, NIK (opsional), nama, tempat/tanggal lahir, dan nama orang tua.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Menambah data siswa</h3>
    <ol>
        <li>Buka menu <strong>Siswa</strong>.</li>
        <li>Klik <strong>Tambah Siswa</strong>.</li>
        <li>Isi data utama: <strong>Nama</strong>, <strong>NISN</strong>, <strong>NIS</strong>, <strong>Jenis Kelamin</strong>, <strong>Tempat/Tanggal Lahir</strong>, <strong>Agama</strong>, <strong>Alamat</strong>.</li>
        <li>Isi data orang tua: <strong>Nama Ayah</strong>, <strong>Nama Ibu</strong>, <strong>Telepon Ortu</strong>.</li>
        <li>Pilih <strong>Tahun Masuk</strong> dan <strong>Status</strong> (aktif/lulus/pindah/keluar).</li>
        <li>Klik <strong>Simpan</strong>. Akun siswa otomatis dibuat bila sinkronisasi paket aktif.</li>
    </ol>

    <h3>Menambah data guru</h3>
    <ol>
        <li>Buka menu <strong>Guru</strong>.</li>
        <li>Klik <strong>Tambah Guru</strong>.</li>
        <li>Isi <strong>NIP</strong>, <strong>Nama</strong>, <strong>Jenis Kelamin</strong>, <strong>Telepon</strong>, <strong>Alamat</strong>.</li>
        <li>Pilih <strong>Mata Pelajaran</strong> yang diampu (dapat lebih dari satu).</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <h3>Mencari data</h3>
    <p>Kolom pencarian di atas tabel mencari berdasarkan nama, NISN/NIS (siswa) atau NIP/nama (guru). Gunakan tombol <strong>Detail</strong> pada baris untuk membuka ringkasan lengkap.</p>

    <h3>Mengubah data</h3>
    <p>Klik <strong>Edit</strong> → ubah kolom → <strong>Update</strong>. Untuk akun siswa/guru, perubahan nama ikut tercatat pada akun pengguna yang terhubung.</p>

    <h2 id="status">Arti status &amp; kolom penting</h2>
    <table class="doc-table">
        <thead><tr><th>Kolom/Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>NISN</td><td>Nomor Induk Siswa Nasional, unik, wajib</td></tr>
            <tr><td>NIS</td><td>Nomor induk internal sekolah (opsional, unik bila diisi)</td></tr>
            <tr><td>Status siswa</td><td><code>aktif</code>, <code>lulus</code>, <code>pindah</code>, <code>keluar</code> — hanya <code>aktif</code> yang tampil di penempatan kelas</td></tr>
            <tr><td>Status guru</td><td><code>aktif</code> / <code>nonaktif</code></td></tr>
            <tr><td>Kode guru</td><td>Kode internal (opsional, dapat dipakai untuk integrasi/pencocokan)</td></tr>
            <tr><td>Jenis kelamin</td><td><code>L</code> (laki-laki) / <code>P</code> (perempuan)</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Superadmin menginput 30 siswa baru dari formulir pendaftaran. Untuk tiap siswa: isi NISN, nama, tanggal lahir, lalu tempatkan ke kelas lewat menu <strong>Kelassiswa</strong>. Siswa yang lupa passwordnya dapat direset dari menu <strong>Users</strong>.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>NISN ditolak "sudah digunakan"</td><td>NISN harus unik</td><td>Periksa apakah siswa sudah terdaftar; gunakan Detail untuk mencari</td></tr>
            <tr><td>Siswa tidak muncul di Kelassiswa</td><td>Status siswa bukan <code>aktif</code></td><td>Ubah status menjadi aktif</td></tr>
            <tr><td>Guru tidak bisa dipilih di jadwal</td><td>Guru nonaktif</td><td>Aktifkan kembali status guru</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/data-kelas-jurusan-mapel">Kelas, Jurusan &amp; Mata Pelajaran</a></li>
        <li><a href="/welcome-docs/artikel/relasi-akademik">Relasi Siswa–Kelas–Mapel</a></li>
        <li><a href="/welcome-docs/artikel/kelola-pengguna">Mengelola Akun Pengguna</a></li>
    </ul>
</section>
