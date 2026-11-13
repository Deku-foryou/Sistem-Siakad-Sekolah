<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · LAPORAN &amp; EXPORT</p>
    <h1 class="doc-title">Laporan &amp; Export</h1>
    <p class="doc-lead">Daftar export yang benar-benar tersedia di aplikasi, formatnya, dan hak aksesnya.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Export menghasilkan berkas untuk arsip, pelaporan, atau bukti — langsung dari data aplikasi tanpa mengetik ulang.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Export keuangan (rekonsiliasi, kuitansi, bukti) hanya untuk <strong>Superadmin/Admin</strong>; rapor PDF untuk <strong>Siswa &amp; Wali Kelas</strong>; rekap absensi untuk <strong>Siswa</strong> (miliknya) dan <strong>Wali</strong> (anaknya); export Users untuk <strong>Superadmin</strong>.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul><li>Login dengan peran di atas.</li></ul>

    <h2 id="langkah">Daftar export yang tersedia</h2>
    <table class="doc-table">
        <thead><tr><th>Export</th><th>Lokasi</th><th>Format</th><th>Isi</th></tr></thead>
        <tbody>
            <tr><td>Rekap Absensi Siswa</td><td>Histori Absensi (siswa)</td><td>CSV (UTF-8, pemisah <code>;</code>)</td><td>Rekap harian (semester/TA aktif), rekap per jam (riwayat), daftar detail</td></tr>
            <tr><td>Rekap Kehadiran Anak</td><td>Akademik &amp; Keuangan (wali)</td><td>CSV (UTF-8, pemisah <code>;</code>)</td><td>Rekap kehadiran anak yang relasinya terverifikasi</td></tr>
            <tr><td>Rapor (Siswa)</td><td>Rapor Saya</td><td>PDF</td><td>Nilai akhir semua mapel + KKTP + keterangan</td></tr>
            <tr><td>Rapor (Wali Kelas)</td><td>Rapor Wali Kelas</td><td>PDF</td><td>Rapor siswa terpilih + catatan wali kelas</td></tr>
            <tr><td>Kuitansi Pembayaran</td><td>Pembayaran (setelah bayar/verifikasi)</td><td>PDF</td><td>Kuitansi resmi bernomor unik (kembali permanen)</td></tr>
            <tr><td>Bukti Pembayaran</td><td>Pembayaran Manual</td><td>PDF/berkas bukti</td><td>Berkas bukti yang diunggah petugas</td></tr>
            <tr><td>Dokumen Refund</td><td>Refund &amp; Koreksi</td><td>PDF</td><td>Dokumen resmi refund yang diproses</td></tr>
            <tr><td>Export Rekonsiliasi — Ringkasan</td><td>Rekonsiliasi Keuangan</td><td>CSV</td><td>Ringkasan hasil run pemeriksaan</td></tr>
            <tr><td>Export Rekonsiliasi — Temuan</td><td>Rekonsiliasi Keuangan</td><td>CSV</td><td>Seluruh temuan kategori</td></tr>
            <tr><td>Export Users</td><td>Users</td><td>Excel (.xlsx) &amp; PDF</td><td>Daftar akun (id, username, nama, email, level)</td></tr>
            <tr><td>Template/gagal Import</td><td>Halaman terkait import</td><td>Excel/CSV</td><td>Template kosong &amp; daftar baris gagal</td></tr>
        </tbody>
    </table>

    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> <strong>Anti formula injection:</strong> sel CSV yang diawali <code>= + - @</code> dinetralkan otomatis sehingga berkas aman dibuka di Excel. Kolom dengan pemisah <code>;</code> memudahkan dibuka langsung di Excel berbahasa Indonesia.</p>

    <h2 id="status">Catatan keamanan ekspor</h2>
    <ul>
        <li>Export CSV rekonsiliasi dikirim sebagai <strong>stream download</strong> — tidak disimpan di folder publik.</li>
        <li>Berkas final (kuitansi, dokumen refund) memiliki <strong>nomor unik &amp; checksum</strong> — tidak dapat dipalsukan lewat edit ringan.</li>
        <li>Hanya peran pemilik data/tugas yang dapat mengunduh.</li>
    </ul>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Siswa mengunduh rekap absensinya untuk keperluan beasiswa: menu Histori Absensi → <strong>Unduh Rekap</strong> → berkas CSV terbuka langsung di Excel dengan tiga bagian (harian, per jam, detail).</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>CSV terbuka bertumpuk satu kolom</td><td>Pemisah tidak dikenali Excel</td><td>Gunakan "From Text/CSV" dan pilih delimiter <code>;</code></td></tr>
            <tr><td>Export Users tidak muncul</td><td>Bukan superadmin</td><td>Hanya superadmin</td></tr>
            <tr><td>PDF kosong</td><td>Data memang belum tersedia</td><td>Periksa data sumber (mis. rapor final)</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/rekonsiliasi">Rekonsiliasi Keuangan</a></li>
        <li><a href="/welcome-docs/artikel/rapor">Melihat &amp; Mencetak Rapor</a></li>
        <li><a href="/welcome-docs/artikel/pembayaran-manual">Pembayaran Manual &amp; Verifikasi</a></li>
    </ul>
</section>
