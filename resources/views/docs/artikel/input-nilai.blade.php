<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · PENILAIAN</p>
    <h1 class="doc-title">Input &amp; Koreksi Nilai</h1>
    <p class="doc-lead">Menugaskan guru mengajar, menginput nilai, dan mengoreksi nilai pada rapor yang sudah difinalisasi.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Nilai siswa pada setiap komponen diinput oleh guru yang ditugaskan. Nilai dipakai untuk menghitung nilai akhir dan membentuk rapor.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Penugasan Mengajar</strong> &amp; konfigurasi nilai: Superadmin. <strong>Input nilai</strong>: Guru (hanya penugasan miliknya, pada semester aktif). <strong>Koreksi nilai rapor final</strong>: Superadmin — lewat menu <strong>Koreksi Nilai</strong> (bukan membuka indeksi input biasa).</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Semester aktif diatur.</li>
        <li>Komponen nilai mapel bersangkutan valid (total bobot aktif = 100%) dan KKTP terisi — untuk input guru.</li>
        <li>Penugasan mengajar guru tersebut terdaftar — untuk input guru.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Penugasan mengajar (Superadmin)</h3>
    <ol>
        <li>Buka menu <strong>Penugasan Mengajar</strong>.</li>
        <li>Klik <strong>Tambah</strong>; pilih <strong>Semester</strong>, <strong>Kelas</strong>, <strong>Mapel</strong>, <strong>Guru</strong>.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <h3>Input nilai (Guru)</h3>
    <ol>
        <li>Buka menu <strong>Input Nilai</strong>.</li>
        <li>Pilih <strong>Penugasan</strong> (kelas–mapel yang Anda ampu).</li>
        <li>Tabel menampilkan daftar siswa dan komponen nilai yang valid.</li>
        <li>Isi nilai per siswa per komponen (0–100).</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>

    <figure class="doc-figure">
        <img src="/img/docs/inputnilai.png" alt="Halaman Input Nilai" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Input Nilai: pilih penugasan lalu isi nilai tiap siswa per komponen.</figcaption>
    </figure>

    <h3>Koreksi nilai rapor final (Superadmin)</h3>
    <ol>
        <li>Buka menu <strong>Koreksi Nilai</strong>.</li>
        <li>Pilih kelas dan siswa yang nilainya perlu diperbaiki.</li>
        <li>Pilih komponen, masukkan <strong>nilai baru</strong> dan <strong>alasan koreksi</strong> (wajib, maksimal 1000 karakter).</li>
        <li>Klik <strong>Simpan</strong> — perubahan tercatat pada log perubahan nilai (nilai lama → nilai baru, oleh siapa, kapan, alasan).</li>
    </ol>

    <h2 id="status">Aturan penting</h2>
    <table class="doc-table">
        <thead><tr><th>Aturan</th><th>Keterangan</th></tr></thead>
        <tbody>
            <tr><td>Input guru hanya untuk penugasannya</td><td>Guru lain — meski sama mapel — tidak dapat mengisi</td></tr>
            <tr><td>Hanya semester aktif</td><td>Penugasan semester lalu tidak dapat diinput dari halaman ini</td></tr>
            <tr><td>Konfigurasi harus valid</td><td>Total bobot aktif ≠ 100% → ditolak</td></tr>
            <tr><td>Nilai draft</td><td>Dihitung dari bobot terisi (nilai kosong diabaikan dengan normalisasi)</td></tr>
            <tr><td>Koreksi hanya untuk rapor final</td><td>Rapor draf dikoreksi lewat Input Nilai biasa; final lewat Koreksi Nilai</td></tr>
            <tr><td>Alasan koreksi wajib</td><td>Setiap perubahan pada rapor final tercatat di log</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Guru Sari mengisi Ulangan Harian Matematika XII IPA 1 untuk 32 siswa, lalu Tugas. Keesokan harinya ia naikkan satu nilai tugas yang salah ketik. Setelah rapor difinalisasi dan siswa komplain, superadmin mengoreksi nilai tersebut lewat menu Koreksi Nilai dengan alasan "kesalahan input" — histori nilainya tercatat.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Daftar penugasan kosong</td><td>Belum ada penugasan mengajar Anda</td><td>Hubungi superadmin</td></tr>
            <tr><td>"Penugasan mengajar tersebut bukan milik Anda"</td><td>Bukan penugasan Anda</td><td>Gunakan penugasan Anda sendiri</td></tr>
            <tr><td>Guru tidak bisa menyimpan — konfigurasi tidak valid</td><td>Bobot aktif ≠ 100%</td><td>Superadmin perbaiki Komponen Nilai</td></tr>
            <tr><td>Koreksi ditolak "Rapor kelas ini belum difinalisasi"</td><td>Koreksi hanya untuk status final</td><td>Gunakan Input Nilai biasa</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/komponen-nilai">Komponen Penilaian, Bobot &amp; KKTP</a></li>
        <li><a href="/welcome-docs/artikel/nilai-akhir-finalisasi">Nilai Akhir &amp; Finalisasi Rapor</a></li>
        <li><a href="/welcome-docs/artikel/rapor">Melihat &amp; Mencetak Rapor</a></li>
    </ul>
</section>
