<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · KEUANGAN</p>
    <h1 class="doc-title">Denda &amp; Pembebasan Denda</h1>
    <p class="doc-lead">Denda keterlambatan pembayaran dan cara mengajukan pembebasannya — dua tahap persetujuan.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Denda diterapkan otomatis/iniasi pada tagihan yang lewat jatuh tempo. Pembebasan denda memungkinkan sekolah menghapus denda bagi siswa yang berhak — dengan kontrol ketat dua tahap.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <ul>
        <li><strong>Superadmin &amp; Admin</strong> — mengajukan pembebasan denda.</li>
        <li><strong>Superadmin</strong> — menyetujui/menolak (admin tidak dapat menyetujui, dan superadmin tidak dapat menyetujui pengajuannya sendiri).</li>
    </ul>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Tagihan memiliki denda dan belum lunas.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Mengajukan pembebasan denda</h3>
    <ol>
        <li>Buka menu <strong>Pembebasan Denda</strong>.</li>
        <li>Pilih <strong>tagihan</strong> yang berdenda.</li>
        <li>Isi <strong>Alasan</strong> (wajib).</li>
        <li>Klik <strong>Ajukan</strong> — pengajuan berstatus <code>pending</code>.</li>
    </ol>

    <h3>Menyetujui / menolak (Superadmin)</h3>
    <ol>
        <li>Daftar pengajuan <code>pending</code> ditampilkan.</li>
        <li>Klik <strong>Setujui</strong> — denda dibebaskan; tagihan disesuaikan (penyesuaian <code>denda_dibebaskan</code> tercatat); atau <strong>Tolak</strong> dengan alasan.</li>
        <li>Pembebasan tidak dapat diterapkan bila denda sudah tertutupi sisa pembayaran.</li>
    </ol>

    <h2 id="status">Arti status pengajuan</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td><code>pending</code></td><td>Menunggu persetujuan superadmin</td></tr>
            <tr><td><code>terproses</code></td><td>Pembebasan diterapkan — denda <code>dibebaskan</code></td></tr>
            <tr><td><code>ditolak</code></td><td>Pengajuan ditolak; alasan tercatat</td></tr>
            <tr><td><code>gagal</code></td><td>Gagal diterapkan (mis. denda sudah tertutupi)</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Siswa Andre terlambat membayar SPP → denda 2% berjalan. Orang tuanya membuktikan kesulitan ekonomi sementara → admin mengajukan pembebasan denda dengan alasan "permohonan orang tua + bukti" → superadmin menyetujui → denda dibebaskan, tagihan tetap berdurasi sama dengan yang jatuh tempo.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Pengajuan ditolak otomatis</td><td>Tagihan sudah lunas / denda sudah tertutupi pembayaran</td><td>Periksa sisa tagihan terlebih dahulu</td></tr>
            <tr><td>Tombol Setujui tidak tersedia untuk admin</td><td>Hanya superadmin</td><td>Serahkan ke superadmin</td></tr>
            <tr><td>Superadmin tidak bisa menyetujui pengajuannya sendiri</td><td>Kebijakan dua mata (maker–checker)</td><td>Minta superadmin lain memproses</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/penerbitan-tagihan">Penerbitan &amp; Status Tagihan</a></li>
        <li><a href="/welcome-docs/artikel/refund-koreksi">Refund &amp; Koreksi Transaksi</a></li>
        <li><a href="/welcome-docs/artikel/rekonsiliasi">Rekonsiliasi Keuangan</a></li>
    </ul>
</section>
