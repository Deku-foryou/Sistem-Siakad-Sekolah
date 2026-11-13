<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · TAHUN AJARAN &amp; SEMESTER</p>
    <h1 class="doc-title">Konteks Akademik Aktif &amp; Pengaruhnya</h1>
    <p class="doc-lead">Apa arti "konteks akademik", modul apa saja yang terdampak, dan kesalahan umum yang sering terjadi.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p><strong>Konteks akademik</strong> adalah pasangan <em>Tahun Ajaran aktif + Semester aktif</em>. Hampir seluruh modul akademik membaca data berdasarkan konteks ini. Ini bukan sekadar label — ini penentu data yang ditampilkan dan yang disimpan.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Semua pengguna melihat dampaknya; hanya Superadmin yang menggantinya.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Tahun ajaran dan semester sudah dibuat (lihat artikel terkait).</li>
    </ul>

    <h2 id="langkah">Apa yang terpengaruh konteks akademik</h2>
    <table class="doc-table">
        <thead><tr><th>Modul</th><th>Pengaruh konteks</th></tr></thead>
        <tbody>
            <tr><td>Dashboard</td><td>Menampilkan TA &amp; Semester aktif di bagian atas</td></tr>
            <tr><td>Kelas</td><td>Daftar kelas mengikuti tahun ajaran (filter TA tersedia)</td></tr>
            <tr><td>Jadwal Kelas</td><td>Menampilkan jadwal semester aktif; jadwal semester lain tersimpan</td></tr>
            <tr><td>Penugasan Mengajar</td><td>Per semester</td></tr>
            <tr><td>Komponen Nilai / KKTP</td><td>Per semester + mapel</td></tr>
            <tr><td>Input &amp; Koreksi Nilai</td><td>Hanya untuk penugasan di semester yang sedang dibuka</td></tr>
            <tr><td>Nilai Akhir &amp; Finalisasi Rapor</td><td>Per semester + kelas</td></tr>
            <tr><td>Absensi Harian</td><td>Mengacu TA + semester (filter tersedia)</td></tr>
            <tr><td>Absensi Per Jam / Jadwal Pertemuan</td><td>Terikat ke semester aktif</td></tr>
            <tr><td>Pengumuman</td><td>Tidak terikat konteks, tetapi tampil bersamaan dengan modul lain</td></tr>
            <tr><td>Keuangan (tarif, tagihan)</td><td>Tagihan menyimpan TA/semester asal penerbitan</td></tr>
        </tbody>
    </table>

    <h2 id="status">Saat Anda mengganti konteks</h2>
    <p>Mengaktifkan TA/Semester baru <strong>tidak mengubah atau menghapus</strong> data lama. Yang terjadi:</p>
    <ol>
        <li>TA/Semester lama menjadi nonaktif.</li>
        <li>Modul menampilkan data periode baru (biasanya kosong — itu normal).</li>
        <li>Histori tetap dapat diakses lewat filter tahun ajaran/semester di masing-masing halaman.</li>
    </ol>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Semester Genap 2026/2027 dimulai. Superadmin mengaktifkan konteks. Guru membuka Input Nilai → kelas dan mapelnya tampil (penugasan semester baru dibuat dulu). Dashboard menampilkan "2026/2027 · Ganjil/Genap" sesuai aktifnya.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Setelah ganti semester, nilai lama "hilang"</td><td>Memang disimpan per semester</td><td>Buka semester lama di filter untuk melihat</td></tr>
            <tr><td>Jadwal kosong setelah ganti TA</td><td>Jadwal belum dibuat untuk periode baru</td><td>Salin/buat jadwal baru (lihat tutorial Jadwal Kelas)</td></tr>
            <tr><td>Dua semester aktif bersamaan</td><td>Tidak mungkin — sistem hanya mengizinkan satu aktif</td><td>Verifikasi di menu Semester</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/tahun-ajaran-semester">Tahun Ajaran &amp; Semester</a></li>
        <li><a href="/welcome-docs/artikel/jadwal-pelajaran-kelas">Jadwal Pelajaran per Kelas</a></li>
        <li><a href="/welcome-docs/artikel/input-nilai">Input &amp; Koreksi Nilai</a></li>
    </ul>
</section>
