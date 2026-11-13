<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · PORTAL WALI MURID</p>
    <h1 class="doc-title">Akun Wali &amp; Pengajuan Relasi dengan Siswa</h1>
    <p class="doc-lead">Cara wali murid mendapatkan akun, mengajukan hubungan dengan siswa, dan menunggu verifikasi admin.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Portal wali memerlukan dua langkah sebelum informasi anak dapat diakses: (1) akun wali yang dibuat admin, (2) <strong>relasi wali–siswa yang diverifikasi</strong>. Keduanya melindungi data anak agar tidak jatuh ke tangan yang salah.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <ul>
        <li><strong>Wali murid</strong> — login, mengajukan/melihat relasi, melihat status.</li>
        <li><strong>Superadmin &amp; Admin</strong> — membuat akun wali, verifikasi/tolak pengajuan relasi, menyetujui/menolak pemutusan, reset sandi, aktif/nonaktif akun (menu <em>Kelola Akun Wali</em> dan <em>Antrian Verifikasi</em>).</li>
    </ul>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Akun wali sudah dibuat admin sekolah (wali tidak dapat membuat akun sendiri).</li>
        <li>Siswa yang akan dihubungkan sudah terdaftar aktif di aplikasi.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <figure class="doc-figure">
        <img src="/img/docs/wali-relasi.png" alt="Halaman Relasi Wali" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Halaman Relasi: pilih siswa, tentukan hubungan, lalu ajukan.</figcaption>
    </figure>

    <h3>Untuk Admin/Superadmin: membuat akun wali</h3>
    <ol>
        <li>Buka menu <strong>Kelola Akun Wali</strong>.</li>
        <li>Klik <strong>Tambah Akun Wali</strong>.</li>
        <li>Isi <strong>Nama</strong>, <strong>Username</strong>, <strong>Email</strong> (opsional), dan <strong>Alasan pembuatan</strong> bila diminta.</li>
        <li>Klik <strong>Simpan</strong> — <strong>sandi sementara ditampilkan sekali</strong>; salin sekarang dan serahkan ke wali dengan cara aman.</li>
        <li>Wali login dengan sandi sementara; ia akan diminta menggantinya.</li>
    </ol>

    <h3>Untuk Wali: mengajukan relasi dengan siswa</h3>
    <ol>
        <li>Login sebagai wali.</li>
        <li>Buka menu <strong>Relasi Siswa</strong> (menu wali).</li>
        <li>Klik <strong>Pilih Siswa</strong>, cari nama anak Anda, lalu pilih.</li>
        <li>Pilih <strong>Hubungan</strong>: <code>Ayah</code> / <code>Ibu</code> / <code>Wali</code> / <code>Lainnya</code>.</li>
        <li>Isi <strong>Alasan pengajuan</strong>, klik <strong>Ajukan</strong>.</li>
        <li>Status menjadi <strong>Menunggu verifikasi</strong>.</li>
    </ol>

    <h3>Untuk Admin: memverifikasi pengajuan</h3>
    <ol>
        <li>Buka menu <strong>Antrian Verifikasi</strong>.</li>
        <li>Tab <strong>Menunggu</strong> menampilkan pengajuan baru.</li>
        <li>Klik <strong>Verifikasi</strong> (atau <strong>Tolak</strong> dengan alasan).</li>
        <li>Untuk pemutusan hubungan, tab menampilkan <strong>Permintaan pemutusan</strong> menunggu keputusan (setujui/tolak).</li>
    </ol>

    <h3>Pemutusan hubungan</h3>
    <p>Wali dapat mengajukan pemutusan dari menu Relasi (<em>Ajukan Pemutusan</em>). Admin/superadmin memutuskan setujui/tolak pada antrian. Setelah disetujui, akses akademik/keuangan berakhir tetapi histori tetap tersimpan.</p>

    <h3>Berakhir &amp; periode</h3>
    <p>Relasi terverifikasi berlaku dengan periode (tanggal mulai — selesai opsional). Admin dapat mengubah periode atau memutus hubungan dengan alasan.</p>

    <h2 id="status">Arti status hubungan</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th><th>Dapat melihat data anak?</th></tr></thead>
        <tbody>
            <tr><td><code>pending</code> (Menunggu)</td><td>Pengajuan menunggu pemeriksaan</td><td>Tidak</td></tr>
            <tr><td><code>verified</code> (Terverifikasi)</td><td>Relasi disetujui</td><td><strong>Ya</strong> (sesuai periode)</td></tr>
            <tr><td><code>rejected</code> (Ditolak)</td><td>Tidak disetujui</td><td>Tidak — dapat ajukan ulang</td></tr>
            <tr><td><code>terminated</code> (Diputus)</td><td>Hubungan diakhiri</td><td>Tidak; histori tetap ada</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Wali <em>Budi Santoso</em> menerima sandi dari admin → login → mengajukan relasi dengan anaknya <em>Andre</em> (hubungan: Ayah) → admin memverifikasi di antrian → Budi kini dapat membuka <em>Akademik &amp; Keuangan</em> dan melihat nilai, absensi, jadwal, serta tagihan Andre.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Tidak menemukan nama anak saat memilih siswa</td><td>Siswa belum terdaftar/aktif</td><td>Hubungi admin sekolah</td></tr>
            <tr><td>Pengajuan ditolak</td><td>Alasan tidak memadai / siswa bukan tanggungan</td><td>Perbaiki alasan &amp; ajukan ulang</td></tr>
            <tr><td>Sudah verified tapi tidak bisa membuka data</td><td>Periode berakhir / akun nonaktif</td><td>Periksa periode; hubungi admin</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/wali-akademik-keuangan">Wali: Akademik &amp; Keuangan Anak</a></li>
        <li><a href="/welcome-docs/artikel/keamanan-akun">Keamanan Akun</a></li>
    </ul>
</section>
