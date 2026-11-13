<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · KEUANGAN</p>
    <h1 class="doc-title">Refund &amp; Koreksi Transaksi</h1>
    <p class="doc-lead">Mengembalikan dana (refund) atau memperbaiki pencatatan transaksi (koreksi) lewat alur persetujuan.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Kesalahan keuangan tidak boleh dihapus begitu saja — harus lewat jalur yang tercatat. Refund mengembalikan uang; koreksi memperbaiki data alokasi/pencatatan tanpa memindah uang secara fisik.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Superadmin dan Admin</strong> mengajukan; persetujuan akhir oleh <strong>Superadmin</strong>.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Pembayaran <code>success</code> yang akan direfund/dikoreksi.</li>
        <li>Alasan yang jelas (wajib).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Mengajukan refund</h3>
    <ol>
        <li>Buka menu <strong>Refund &amp; Koreksi</strong>.</li>
        <li>Pilih <strong>pembayaran</strong> pada daftar.</li>
        <li>Klik <strong>Ajukan Refund</strong>; isi <strong>Nominal</strong> (maksimal sebesar yang dapat direfund), <strong>Metode</strong> (<code>transfer_manual</code>/<code>tunai</code>/<code>lainnya</code>), dan <strong>Alasan</strong>.</li>
        <li>Pengajuan berstatus <code>pending</code>.</li>
    </ol>

    <h3>Mengajukan koreksi</h3>
    <ol>
        <li>Pilih pembayaran/tagihan terkait.</li>
        <li>Klik <strong>Ajukan Koreksi</strong>; pilih <strong>Jenis</strong>: <code>nominal</code> / <code>alokasi</code> / <code>pencatatan_manual</code> / <code>hubungan_tagihan</code>.</li>
        <li>Isi data usulan (nominal baru, metode, alokasi ulang, dsb.) dan <strong>Alasan</strong>.</li>
        <li>Simpan — berstatus <code>pending</code>.</li>
    </ol>

    <h3>Persetujuan (Superadmin)</h3>
    <ol>
        <li>Tab pengajuan menampilkan daftar <code>pending</code>.</li>
        <li>Klik <strong>Setujui</strong> → lalu <strong>Proses</strong> (uang dikembalikan &amp; alokasi dibalik), atau <strong>Tolak</strong> dengan alasan (wajib).</li>
        <li>Koreksi yang disetujui otomatis membuat alokasi pembalik (<code>reversal-refund</code>/<code>reversal-koreksi</code>) sehingga ledger tetap seimbang.</li>
        <li>Refund yang diproses menghasilkan <strong>dokumen refund</strong> (PDF) yang dapat diunduh.</li>
    </ol>

    <h2 id="status">Arti status pengajuan</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td><code>pending</code></td><td>Menunggu keputusan superadmin</td></tr>
            <tr><td><code>disetujui</code></td><td>Disetujui, menunggu diproses</td></tr>
            <tr><td><code>ditolak</code></td><td>Ditolak; alasan tercatat</td></tr>
            <tr><td><code>terproses</code></td><td>Selesai — uang/alokasi sudah dikembalikan</td></tr>
            <tr><td><code>gagal</code></td><td>Gagal diproses; catatan tersimpan</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Siswa pindah sekolah; wali meminta pengembalian SPP semester depan. Petugas mengajukan refund Rp 250.000 untuk pembayaran tertentu → superadmin menyetujui dan memproses → dokumen refund terbit, saldo/tagihan terkait dibalik.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Nominal ditolak melebihi batas</td><td>Refund tidak boleh melebihi bagian yang dapat dikembalikan</td><td>Periksa alokasi tagihan &amp; saldo; ajukan nominal yang wajar</td></tr>
            <tr><td>Koreksi ditolak "total bobot…"</td><td>Konsistensi snapshot nilai/ledger</td><td>Ajukan sesuai kondisi data saat ini</td></tr>
            <tr><td>Refund gagal diproses</td><td>Saldo siswa tidak mencukupi untuk dibalik</td><td>Periksa saldo; gunakan koreksi alokasi bila perlu</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/pembayaran-manual">Pembayaran Manual &amp; Verifikasi</a></li>
        <li><a href="/welcome-docs/artikel/saldo">Saldo Siswa &amp; Penggunaannya</a></li>
        <li><a href="/welcome-docs/artikel/rekonsiliasi">Rekonsiliasi Keuangan</a></li>
    </ul>
</section>
