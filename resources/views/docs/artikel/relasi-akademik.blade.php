<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · DATA MASTER AKADEMIK</p>
    <h1 class="doc-title">Relasi Siswa–Kelas–Mapel &amp; Status Aktif</h1>
    <p class="doc-lead">Bagaimana siswa ditempatkan di kelas, guru ditugaskan mengajar mapel, dan arti status aktif pada setiap data.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>SIAKAD bukan hanya daftar data terpisah — setiap data saling terhubung. Memahami relasi ini membantu Anda mengisi data dengan benar dan tidak bingung ketika sebuah fitur "tidak menampilkan apa-apa" (biasanya karena relasinya belum, atau statusnya tidak aktif).</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Seluruh pengguna (dengan porsi berbeda). Superadmin mengelolanya; pengguna lain melihat hasilnya.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Data siswa, guru, mapel, kelas dan tahun ajaran sudah dibuat.</li>
    </ul>

    <h2 id="langkah">Relasi utama</h2>
    <h3>Siswa ↔ Akun pengguna</h3>
    <p>Setiap siswa <strong>terhubung satu-ke-satu</strong> dengan sebuah akun <code>users</code>. Login siswa memakai akun itu; tanpa tautan, siswa tidak dapat login.</p>

    <h3>Guru ↔ Akun pengguna</h3>
    <p>Sama seperti siswa — guru terhubung satu-ke-satu dengan akun <code>users</code>.</p>

    <h3>Siswa ↔ Kelas (lewat Kelassiswa)</h3>
    <p>Penempatan siswa ke kelas dicatat di <strong>Kelassiswa</strong> dengan status <code>aktif</code> (sedang berada di kelas), <code>selesai</code> (tahun ajaran berakhir), atau <code>pindah</code> (pindah kelas/sekolah). Seorang siswa dapat punya banyak penempatan dari waktu ke waktu — hanya yang <code>aktif</code> dipakai modul lain.</p>

    <h3>Kelas ↔ Tahun Ajaran</h3>
    <p>Setiap kelas terikat pada satu tahun ajaran. Kelas tahun depan dibuat terpisah; data nilai dan jadwal tersimpan per tahun ajaran + semester.</p>

    <h3>Guru ↔ Mapel (lewat Gurumapel)</h3>
    <p><strong>Guru Mapel</strong> mencatat mapel apa saja yang mampu diajarkan seorang guru — dipakai sebagai filter saat menugaskan guru mengajar.</p>

    <h3>Penugasan mengajar (guru–kelas–mapel per semester)</h3>
    <p>Penugasan mengajar menghubungkan <code>semester + kelas + mapel + guru</code>. Inilah yang membuat input nilai, absensi per jam, dan jadwal berjalan — tanpa penugasan, guru tidak melihat kelasnya.</p>

    <h3>Jadwal (kelas–jam–mapel–guru)</h3>
    <p>Jadwal mengacu ke <em>jam hari</em> (jam pelajaran per kelas) dan <em>penugasan mengajar</em>. Lihat <a href="/welcome-docs/artikel/jadwal-pelajaran-kelas">Jadwal Pelajaran per Kelas</a>.</p>

    <h2 id="status">Arti status aktif pada setiap data</h2>
    <table class="doc-table">
        <thead><tr><th>Data</th><th>Status</th><th>Akibat bila nonaktif/tidak aktif</th></tr></thead>
        <tbody>
            <tr><td>Akun pengguna</td><td>aktif / nonaktif</td><td>Nonaktif: tidak bisa login</td></tr>
            <tr><td>Siswa</td><td>aktif / lulus / pindah / keluar</td><td>Non-aktif: tidak tampil di penempatan &amp; absensi</td></tr>
            <tr><td>Guru</td><td>aktif / nonaktif</td><td>Nonaktif: tidak bisa ditugaskan mengajar</td></tr>
            <tr><td>Kelas</td><td>Tahun ajaran aktif/nonaktif</td><td>Kelas di TA nonaktif tetap tersimpan untuk histori</td></tr>
            <tr><td>Kelassiswa</td><td>aktif / selesai / pindah</td><td>Hanya <code>aktif</code> dihitung di kelas berjalan</td></tr>
            <tr><td>Tahun ajaran &amp; semester</td><td>aktif / nonaktif</td><td>Menentukan konteks akademik — lihat artikel terkait</td></tr>
            <tr><td>Tarif / beasiswa / pengumuman</td><td>aktif/terbit/dst.</td><td>Status menentukan berlaku tidaknya data</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Siswa <em>Andre</em> (status <code>aktif</code>) ditempatkan di <code>XII IPA 1</code> pada Kelassiswa (status <code>aktif</code>). Guru <em>Sari</em> ditugaskan mengajar Matematika di kelas itu (penugasan mengajar). Andre login → melihat jadwal Matematikanya → guru mengisi absensi → di akhir semester nilai Andre dihitung dari komponen nilai Matematika.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Siswa hilang dari kelas</td><td>Penempatan berstatus <code>pindah</code>/<code>selesai</code> atau siswa <code>lulus</code></td><td>Periksa Kelassiswa dan status siswa</td></tr>
            <tr><td>Guru tidak muncul di penugasan</td><td>Mapel guru belum dihubungkan, atau guru nonaktif</td><td>Hubungkan mapel di Data Guru; aktifkan guru</td></tr>
            <tr><td>Data nilai kosong padahal nilai diinput</td><td>Semester/TA berbeda dengan yang diinput</td><td>Pastikan konteks akademik yang dipakai sama (lihat artikel konteks akademik)</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/data-siswa-guru">Data Induk Siswa &amp; Guru</a></li>
        <li><a href="/welcome-docs/artikel/konteks-akademik">Konteks Akademik Aktif</a></li>
        <li><a href="/welcome-docs/artikel/referensi-hubungan">Referensi Data — Relasi &amp; Alur</a></li>
    </ul>
</section>
