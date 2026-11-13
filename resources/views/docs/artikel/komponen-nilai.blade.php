<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · PENILAIAN</p>
    <h1 class="doc-title">Komponen Penilaian, Bobot &amp; KKTP</h1>
    <p class="doc-lead">Menentukan komponen nilai (ulangan, tugas, dan sebagainya), bobotnya, dan batas ketuntasan (KKTP) per mata pelajaran.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Komponen nilai memecah nilai akhir menjadi bagian-bagian yang jelas. KKTP (Kriteria Ketercapaian Tujuan Pembelajaran) adalah batas nilai minimal agar siswa dianggap tuntas. Kedua konfigurasi ini dipakai saat nilai akhir dihitung dan rapor difinalisasi.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Superadmin</strong>. Guru tidak mengubah konfigurasi ini; guru hanya menginput nilai.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai Superadmin.</li>
        <li>Semester aktif sudah diatur dan mapel terdaftar.</li>
        <li>Penugasan mengajar (guru–kelas–mapel per semester) sudah dibuat — diperlukan agar guru bisa input.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Mengatur Komponen Nilai</h3>
    <ol>
        <li>Buka menu <strong>Komponen Nilai</strong>.</li>
        <li>Pilih <strong>Semester</strong> dan <strong>Mata Pelajaran</strong>.</li>
        <li>Klik <strong>Tambah Baris</strong> untuk tiap komponen: isi <strong>Nama</strong> (mis. Ulangan Harian, Tugas, Praktik), <strong>Bobot</strong> (persen), <strong>Urutan</strong>, dan centang <strong>Aktif</strong>.</li>
        <li>Perhatikan indikator <strong>Total Bobot Aktif</strong> — harus <strong>tepat 100%</strong>.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <h3>Mengatur KKTP</h3>
    <ol>
        <li>Buka menu <strong>KKTP Mapel</strong>.</li>
        <li>Pilih <strong>Semester</strong> dan <strong>Mapel</strong>.</li>
        <li>Isi <strong>Nilai Minimal</strong> (mis. 70) dan deskripsi opsional.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <h2 id="status">Aturan penting</h2>
    <table class="doc-table">
        <thead><tr><th>Aturan</th><th>Keterangan</th></tr></thead>
        <tbody>
            <tr><td>Total bobot aktif harus tepat 100%</td><td>Bila kurang/lebih, nilai tidak dapat diinput (sistem menolak)</td></tr>
            <tr><td>Komponen nonaktif diabaikan</td><td>Tidak dihitung dalam total bobot maupun nilai akhir</td></tr>
            <tr><td>Satu konfigurasi per (semester, mapel)</td><td>Mengganti semester/mapel memuat konfigurasi tersimpannya</td></tr>
            <tr><td>KKTP per (semester, mapel)</td><td>Berbeda dengan KKM lama — istilah aplikasi ini <strong>KKTP</strong></td></tr>
            <tr><td>Terkunci</td><td>Bila semester sudah difinalisasi, indikator menampilkan status terkunci</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Superadmin mengonfigurasi Matematika semester Ganjil: Ulangan Harian 40%, Tugas 30%, Praktik 30% (total 100% ✓), KKTP 70. Guru bisa langsung menginput nilai. Bila ia hanya mengaktifkan Ulangan 40% + Tugas 30% (total 70%), input nilai Matematika ditolak sampai dilengkapi.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Guru gagal input nilai — "Konfigurasi komponen belum valid"</td><td>Total bobot aktif ≠ 100%</td><td>Perbaiki bobot di Komponen Nilai sampai tepat 100%</td></tr>
            <tr><td>Komponen tidak ikut dihitung</td><td>Komponen nonaktif</td><td>Centang Aktif pada komponen</td></tr>
            <tr><td>KKTP tidak ditemukan</td><td>KKTP belum diset untuk mapel itu</td><td>Isi di menu KKTP Mapel</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/input-nilai">Input &amp; Koreksi Nilai</a></li>
        <li><a href="/welcome-docs/artikel/nilai-akhir-finalisasi">Nilai Akhir &amp; Finalisasi Rapor</a></li>
        <li><a href="/welcome-docs/artikel/konteks-akademik">Konteks Akademik Aktif</a></li>
    </ul>
</section>
