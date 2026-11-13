<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · KEUANGAN</p>
    <h1 class="doc-title">Saldo Siswa &amp; Penggunaannya</h1>
    <p class="doc-lead">Kelebihan bayar menjadi saldo siswa — dan bagaimana saldo dipakai untuk tagihan berikutnya.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Bila pembayaran melebihi tagihan, kelebihannya tidak hilang: disimpan sebagai <strong>saldo siswa</strong> dan dapat dipakai membayar tagihan berikutnya.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Superadmin dan Admin</strong> (pencatatan &amp; penggunaan). Siswa/wali melihat hasil akhirnya lewat status tagihan/pembayaran.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Ada pembayaran lebih (mis. transfer lebih dari tagihan atau pembayaran ke tagihan yang sudah lunas).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Bagaimana saldo terbentuk</h3>
    <p>Saat verifikasi/pencatatan pembayaran, nominal yang melebihi sisa tagihan otomatis dialokasikan ke <strong>saldo</strong> — tercatat pada alokasi pembayaran (<code>jenis: saldo</code>) dan agregat saldo siswa terbentuk dari alokasi tersebut.</p>

    <h3>Menggunakan saldo untuk tagihan</h3>
    <ol>
        <li>Buka menu <strong>Bayar Tagihan</strong> (untuk siswa/wali) atau alur verifikasi (admin).</li>
        <li>Pilih siswa &amp; tagihan yang akan dibayar.</li>
        <li>Bila siswa memiliki saldo, tombol <strong>Gunakan Saldo</strong> tersedia.</li>
        <li>Klik <strong>Gunakan Saldo</strong> — saldo dialokasikan ke tagihan (alokasi berjenis <code>tagihan</code> dengan referensi saldo), mengurangi kewajiban bayar.</li>
        <li>Tagihan yang tertutup penuh menjadi <code>lunas</code>.</li>
    </ol>

    <h3>Melihat saldo</h3>
    <p>Saldo siswa ditampilkan pada ringkasan siswa di halaman pembayaran dan pada rincian alokasi. Saldo bersifat agregat dari seluruh alokasi — dapat diperiksa petugas.</p>

    <h2 id="status">Aturan penting</h2>
    <ul>
        <li><strong>Saldo terbentuk dari kelebihan bayar</strong> — bukan setor tunai langsung.</li>
        <li><strong>Saldo dipakai lebih dulu</strong> bila siswa memilih Gunakan Saldo.</li>
        <li><strong>Pengembalian dana (refund)</strong> mengurangi saldo — bila saldo tidak mencukupi, refund tidak dapat diproses (lihat artikel refund).</li>
        <li><strong>Saldo tidak dapat ditarik tunai bebas</strong> tanpa alur refund/koreksi.</li>
    </ul>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Wali Andre mentransfer Rp 300.000 untuk tagihan Rp 250.000 → setelah diverifikasi, Rp 250.000 dialokasikan ke tagihan (lunas) dan Rp 50.000 menjadi saldo. Bulan berikutnya, tagihan SPP Rp 250.000 dibayar sebagian Rp 200.000 memakai saldo + Rp 200.000, sisanya tunai.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Saldo tidak cukup saat dipakai</td><td>Saldo memang kurang dari tagihan</td><td>Bayar sisanya dengan metode lain</td></tr>
            <tr><td>Saldo berkurang tiba-tiba</td><td>Terpakai untuk tagihan / dibalik karena refund</td><td>Periksa alokasi pembayaran &amp; riwayat refund</td></tr>
            <tr><td>Saldo seharusnya ada tapi nol</td><td>Pembayaran belum diverifikasi</td><td>Tunggu verifikasi; saldo terbentuk setelah <code>success</code></td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/pembayaran-manual">Pembayaran Manual &amp; Verifikasi</a></li>
        <li><a href="/welcome-docs/artikel/refund-koreksi">Refund &amp; Koreksi Transaksi</a></li>
        <li><a href="/welcome-docs/artikel/rekonsiliasi">Rekonsiliasi Keuangan</a></li>
    </ul>
</section>
