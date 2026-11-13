<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · KEUANGAN</p>
    <h1 class="doc-title">Pembayaran Online &amp; Payment Attempt</h1>
    <p class="doc-lead">Skema pembayaran online (Midtrans) dan status percobaan — serta kenyataan integrasi belum aktif.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Pembayaran online memungkinkan siswa/wali membayar tagihan lewat gateway (Midtrans: Virtual Account, QRIS, e-wallet) tanpa datang ke sekolah. Setiap percobaan tercatat sebagai <strong>payment attempt</strong>.</p>

    <p class="doc-note doc-note-warn"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> <strong>Penting — status implementasi:</strong> Integrasi gateway pembayaran <strong>belum diaktifkan</strong> di lingkungan aplikasi saat ini. Fitur pembayaran online <strong>belum dapat dipakai</strong> untuk transaksi nyata. Dokumentasi ini menjelaskan alur yang disediakan agar sekolah siap mengaktifkannya. Untuk pembayaran berjalan, gunakan <a href="/welcome-docs/artikel/pembayaran-manual">Pembayaran Manual</a>.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Siswa &amp; wali (alur bayar) dan Superadmin/Admin (pemantauan &amp; rekonsiliasi).</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai siswa/wali dengan tagihan belum lunas.</li>
        <li>Untuk aktivasi nyata: kredensial Midtrans &amp; pengaturan gateway (belum tersedia).</li>
    </ul>

    <h2 id="langkah">Alur (untuk pemahaman)</h2>
    <ol>
        <li>Siswa/wali membuka <strong>Bayar Tagihan</strong>.</li>
        <li>Memilih siswa (untuk wali) dan tagihan yang akan dibayar.</li>
        <li>Klik <strong>Bayar</strong> → sistem membuat <strong>payment attempt</strong> (<code>created</code>) dan meminta <em>Snap token</em> ke provider.</li>
        <li>Pengguna menyelesaikan pembayaran di halaman/pop-up gateway.</li>
        <li>Provider mengirim notifikasi (webhook) → status attempt diperbarui (<code>pending</code> → <code>success</code>/<code>failed</code>/<code>expired</code>).</li>
        <li>Bila <code>success</code>, pembayaran tercatat dan dialokasikan ke tagihan (status tagihan diperbarui; kuitansi terbit).</li>
    </ol>

    <h3>Memantau payment attempt</h3>
    <p>Menu <strong>Transaksi Pembayaran</strong> menampilkan seluruh attempt: filter <strong>Status</strong>, <strong>Periode</strong>, pencarian (order ID/nama). Klik satu baris untuk melihat detail payload &amp; status provider.</p>

    <h2 id="status">Arti status payment attempt</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td><code>created</code></td><td>Attempt dibuat, belum ada respons provider</td></tr>
            <tr><td><code>pending</code></td><td>Menunggu pembayaran pengguna di gateway</td></tr>
            <tr><td><code>success</code></td><td>Pembayaran valid — tercatat &amp; teralokasi</td></tr>
            <tr><td><code>failed</code></td><td>Gagal (ditolak/gagal di gateway)</td></tr>
            <tr><td><code>expired</code></td><td>Batas waktu pembayaran terlewati</td></tr>
            <tr><td><code>cancelled</code></td><td>Dibatalkan</td></tr>
        </tbody>
    </table>

    <h3>Link pembayaran publik</h3>
    <p>Sistem juga menyediakan tautan pembayaran publik berkode token (untuk perpanjangan/penyebaran). Tautan ini hanya aktif bila integrasi provider diaktifkan.</p>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Wali membuka tab Keuangan → Bayar → dialihkan ke halaman Midtrans → membayar via QRIS → gateway mengonfirmasi → tagihan menjadi lunas dan kuitansi tersedia untuk diunduh.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Tombol Bayar tidak dapat menyelesaikan pembayaran</td><td>Integrasi gateway belum aktif</td><td>Gunakan Pembayaran Manual; hubungi pihak sekolah mengenai jadwal aktivasi</td></tr>
            <tr><td>Status "pending" selamanya</td><td>Pengguna belum menyelesaikan pembayaran / notifikasi provider belum masuk</td><td>Selesaikan di gateway atau batalkan; cek menu Transaksi</td></tr>
            <tr><td>Tagihan tetap belum lunas setelah dibayar</td><td>Notifikasi provider belum diproses</td><td>Gunakan tombol <strong>Periksa Status</strong> pada attempt; hubungi admin</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/pembayaran-manual">Pembayaran Manual &amp; Verifikasi</a></li>
        <li><a href="/welcome-docs/artikel/saldo">Saldo Siswa &amp; Penggunaannya</a></li>
        <li><a href="/welcome-docs/artikel/rekonsiliasi">Rekonsiliasi Keuangan</a></li>
    </ul>
</section>
