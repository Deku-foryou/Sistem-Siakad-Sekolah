<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · KEUANGAN</p>
    <h1 class="doc-title">Penerbitan &amp; Status Tagihan</h1>
    <p class="doc-lead">Menerbitkan tagihan SPP &amp; insidental, memantau status, dan membatalkan tagihan.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Tagihan adalah kewajiban bayar siswa. Penerbitan mencatat tagihan per periode; statusnya berubah otomatis saat pembayaran tercatat/terverifikasi.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Superadmin dan Admin</strong>.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Tarif berlaku sudah dibuat.</li>
        <li>Siswa aktif terdaftar.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Menerbitkan tagihan SPP</h3>
    <ol>
        <li>Buka menu <strong>Data Tagihan</strong>.</li>
        <li>Isi <strong>Periode</strong> (mis. <code>2026-11</code> untuk November 2026) dan <strong>Jatuh Tempo</strong>.</li>
        <li>Klik <strong>Terbitkan SPP (Semua Siswa)</strong>.</li>
        <li>Hasil menampilkan ringkasan: <em>dibuat / dilewati / dibebaskan / tanpa tarif</em>.</li>
    </ol>

    <h3>Menerbitkan tagihan insidental (manual)</h3>
    <ol>
        <li>Isi formulir: <strong>Nama Tagihan</strong> (mis. "Biaya UAS"), <strong>Jenis</strong> (<code>ujian</code>, <code>seragam</code>, <code>buku</code>, <code>pendaftaran</code>, <code>insidental</code>, <code>lainnya</code>), <strong>Nominal</strong>, <strong>Periode</strong>, <strong>Jatuh Tempo</strong>.</li>
        <li>Pilih <strong>Cakupan</strong>: semua siswa / satu kelas / satu siswa (+ <strong>Alasan</strong> bila diperlukan).</li>
        <li>Klik <strong>Terbitkan Insidental</strong>.</li>
    </ol>

    <h3>Melihat tagihan</h3>
    <p>Gunakan filter <strong>Status</strong>, <strong>Jenis</strong>, <strong>Periode</strong> dan pencarian nama. Klik <strong>Detail</strong> untuk rincian (termasuk potongan beasiswa dan riwayat penyesuaian).</p>

    <h3>Membatalkan tagihan</h3>
    <ol>
        <li>Hanya tagihan <strong>belum lunas</strong> yang dapat dibatalkan.</li>
        <li>Klik <strong>Batalkan</strong> → isi <strong>Alasan</strong> → konfirmasi.</li>
        <li>Tagihan berstatus <code>dibatalkan</code>; tidak dihitung dalam saldo/tunggakan.</li>
    </ol>

    <h3>Membebaskan tagihan</h3>
    <p>Selain lewat beasiswa otomatis, superadmin/admin dapat membebaskan tagihan lewat tombol <strong>Bebaskan</strong> (penyesuaian tercatat di riwayat tagihan).</p>

    <h2 id="status">Arti status tagihan</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td><code>belum_dibayar</code></td><td>Belum ada pembayaran masuk</td></tr>
            <tr><td><code>dibayar_sebagian</code></td><td>Sudah ada pembayaran, kurang dari total</td></tr>
            <tr><td><code>lunas</code></td><td>Total terbayar (termasuk pembebasan 100%)</td></tr>
            <tr><td><code>dibebaskan</code></td><td>Tidak perlu dibayar (beasiswa/pembebasan)</td></tr>
            <tr><td><code>dibatalkan</code></td><td>Dibatalkan; alasan tercatat</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>1 November: superadmin menerbitkan SPP untuk 120 siswa → 115 dibuat, 3 dibebaskan (beasiswa penuh), 2 dilewati (belum ada tarif). Ia membuat tarif untuk 2 siswa tersebut, menerbitkan ulang — 2 tagihan dibuat.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>"Siswa tanpa tarif" pada hasil penerbitan</td><td>Belum ada tarif berlaku untuk siswa/kelasnya</td><td>Lengkapi tarif lalu terbitkan ulang</td></tr>
            <tr><td>Tagihan ganda</td><td>Penerbitan dua kali untuk periode sama</td><td>Tagihan ganda dicegah otomatis untuk (siswa, jenis, periode) — sistem melewati yang sudah ada</td></tr>
            <tr><td>Tidak bisa membatalkan</td><td>Tagihan sudah lunas</td><td>Gunakan jalur refund/koreksi, bukan pembatalan</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/tarif-beasiswa">Tarif &amp; Beasiswa</a></li>
        <li><a href="/welcome-docs/artikel/pembayaran-manual">Pembayaran Manual &amp; Verifikasi</a></li>
        <li><a href="/welcome-docs/artikel/dashboard-keuangan">Membaca Dashboard Keuangan</a></li>
    </ul>
</section>
