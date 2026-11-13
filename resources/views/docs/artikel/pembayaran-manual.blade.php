<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · KEUANGAN</p>
    <h1 class="doc-title">Pembayaran Manual &amp; Verifikasi</h1>
    <p class="doc-lead">Mencatat bukti pembayaran dari wali/siswa (tunai/transfer), lalu memverifikasi atau menolaknya.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Pembayaran manual dipakai ketika siswa/wali membayar di luar pembayaran online (langsung ke tata usaha, transfer bank dibantu, dsb.). Bukti dicatat oleh petugas; verifikasi menentukan valid tidaknya pembayaran.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Superadmin dan Admin</strong> (kedua tahap: mencatat &amp; verifikasi).</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Tagihan yang akan dibayar sudah terbit.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Mencatat pembayaran (kasir/petugas)</h3>
    <ol>
        <li>Buka menu <strong>Pembayaran Manual</strong>.</li>
        <li><strong>Pilih siswa</strong> (cari nama/NIS).</li>
        <li>Pilih <strong>tagihan</strong> yang dibayar dari daftar tagihan terbuka siswa.</li>
        <li>Isi <strong>Nominal</strong>, <strong>Metode</strong> (<code>tunai</code> / <code>transfer</code> / dsb.), <strong>Nama pembayar</strong>, <strong>Tanggal</strong>, dan <strong>Catatan</strong>.</li>
        <li>Unggah <strong>bukti pembayaran</strong> (foto/PDF, bila ada).</li>
        <li>Klik <strong>Catat</strong>. Pembayaran berstatus <code>pending</code> sampai diverifikasi.</li>
    </ol>

    <h3>Memverifikasi (menyetujui/menolak)</h3>
    <ol>
        <li>Tab <strong>Menunggu Verifikasi</strong> menampilkan pembayaran <code>pending</code>.</li>
        <li>Klik <strong>Pilih</strong> pada baris, periksa bukti (tombol <strong>Lihat/Unduh Bukti</strong>).</li>
        <li>Klik <strong>Setujui</strong> — pembayaran menjadi <code>success</code>, alokasi ke tagihan, status tagihan diperbarui, dan kuitansi diterbitkan (bila nominal mencukupi).</li>
        <li>Atau klik <strong>Tolak</strong> — isi <strong>Alasan penolakan</strong> (wajib); pembayaran menjadi gagal/ditolak dan tidak memengaruhi tagihan.</li>
    </ol>

    <figure class="doc-figure">
        <img src="/img/docs/pembayaran-manual.png" alt="Pembayaran Manual" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Pembayaran Manual: pilih siswa → tagihan → catat bukti; tab verifikasi menampilkan daftar menunggu.</figcaption>
    </figure>

    <h3>Kuitansi &amp; bukti</h3>
    <p>Setiap pembayaran <code>success</code> dapat diunduh <strong>kuitansinya</strong> (tombol Kuitansi, untuk tagihan terkait) dan <strong>bukti</strong> yang diunggah. Berkas kuitansi diberi nomor unik dan tersimpan permanen.</p>

    <h2 id="status">Arti status pembayaran</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td><code>pending</code></td><td>Menunggu verifikasi petugas</td></tr>
            <tr><td><code>success</code></td><td>Valid — mengurangi tagihan/menambah saldo</td></tr>
            <tr><td><code>failed</code>/<code>cancelled</code>/<code>expired</code></td><td>Tidak dihitung; alasan tercatat</td></tr>
            <tr><td><code>review</code></td><td>Perlu pemeriksaan lebih lanjut</td></tr>
            <tr><td><code>refunded</code></td><td>Sudah dikembalikan lewat refund</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Wali datang ke tata usaha membayar SPP tunai Rp 250.000 → petugas mencatat pembayaran manual dengan nama pembayar "Budi Santoso (wali Andre)" dan foto struk → petugas lain memverifikasi → tagihan Andre berubah dari <code>belum_dibayar</code> menjadi <code>lunas</code>; kuitansi terbit otomatis.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Tagihan tidak lunas setelah verifikasi</td><td>Nominal kurang, atau dialokasikan ke saldo/sebagian</td><td>Periksa alokasi pembayaran di Detail Tagihan</td></tr>
            <tr><td>Bukti tidak bisa diunduh</td><td>Berkas belum terunggah/hilang</td><td>Minta petugas mengunggah ulang; hubungi admin bila berkas rusak</td></tr>
            <tr><td>Pembayaran salah siswa</td><td>Kesalahan pencatatan</td><td>Jangan verifikasi; tolak, lalu catat ulang dengan benar</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/penerbitan-tagihan">Penerbitan &amp; Status Tagihan</a></li>
        <li><a href="/welcome-docs/artikel/saldo">Saldo Siswa &amp; Penggunaannya</a></li>
        <li><a href="/welcome-docs/artikel/refund-koreksi">Refund &amp; Koreksi Transaksi</a></li>
    </ul>
</section>
