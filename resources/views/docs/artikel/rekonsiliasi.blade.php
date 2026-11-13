<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · KEUANGAN</p>
    <h1 class="doc-title">Rekonsiliasi Keuangan</h1>
    <p class="doc-lead">Memeriksa konsistensi ledger, menemukan selisih, dan menindaklanjutinya — pemeriksaan kesehatan keuangan berkala.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Rekonsiliasi membandingkan seluruh catatan keuangan (pembayaran, alokasi, tagihan, kuitansi, saldo, refund) dan melaporkan inkonsistensi. Ini adalah alat audit internal — bukan pengganti perbaikan data, tetapi penanda di mana perbaikan diperlukan.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Superadmin dan Admin</strong>.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Ada transaksi keuangan yang sudah tercatat.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <ol>
        <li>Buka menu <strong>Rekonsiliasi Keuangan</strong>.</li>
        <li>Pilih <strong>Periode</strong> pemeriksaan (opsional) dan jenis filter bila tersedia.</li>
        <li>Klik <strong>Jalankan Pemeriksaan</strong>.</li>
        <li>Ringkasan menampilkan <strong>jumlah pemeriksaan</strong>, <strong>jumlah temuan</strong>, dan <strong>jumlah kritis</strong>, serta rincian per kategori.</li>
        <li>Klik <strong>hasil</strong> untuk melihat daftar temuan: kategori, siswa, tagihan, nominal selisih, dan keterangan.</li>
        <li>Gunakan tombol <strong>Export Ringkasan</strong> / <strong>Export Temuan</strong> (CSV/PDF) untuk dokumentasi.</li>
    </ol>

    <h3>Kategori pemeriksaan</h3>
    <table class="doc-table">
        <thead><tr><th>Kategori</th><th>Arti (singkat)</th></tr></thead>
        <tbody>
            <tr><td>Attempt sukses tanpa ledger</td><td>Pembayaran online berhasil tetapi tak ada pembayaran tercatat</td></tr>
            <tr><td>Ledger online tanpa attempt</td><td>Pembayaran online tercatat tanpa attempt asalnya</td></tr>
            <tr><td>Pembayaran verified tanpa alokasi</td><td>Pembayaran sukses belum dialokasikan ke tagihan/saldo</td></tr>
            <tr><td>Alokasi melebihi nominal</td><td>Alokasi &gt; nominal pembayaran</td></tr>
            <tr><td>Alokasi pada tagihan siswa lain</td><td>Alokasi ke tagihan milik siswa berbeda</td></tr>
            <tr><td>Status tagihan tidak cocok dengan alokasi</td><td>Tagihan lunas padahal belum penuh teralokasi (atau sebaliknya)</td></tr>
            <tr><td>Saldo siswa negatif</td><td>Saldo di bawah nol — harus ditindaklanjuti</td></tr>
            <tr><td>Kuitansi tanpa pembayaran valid / data tidak cocok</td><td>Kuitansi mencurigakan</td></tr>
            <tr><td>Pembayaran verified tanpa kuitansi</td><td>Kuitansi belum terbit</td></tr>
            <tr><td>Status pembayaran manual tidak konsisten</td><td>Inkonsistensi status verifikasi</td></tr>
            <tr><td>Reversal tanpa referensi / melebihi alokasi</td><td>Pembalikan refund/koreksi tidak wajar</td></tr>
        </tbody>
    </table>

    <h3>Tindak lanjut temuan</h3>
    <p>Setiap temuan diberi status <code>perlu_ditinjau</code> / <code>kritis</code> / <code>info</code>. Temuan <code>kritis</code> (mis. saldo negatif) harus segera diperiksa — biasanya menandakan alur verifikasi/reversal yang keliru. Perbaikan dilakukan lewat menu terkait (Pembayaran, Refund &amp; Koreksi, Koreksi Tagihan), <strong>bukan</strong> dengan mengubah data langsung.</p>

    <h2 id="status">Arti status temuan</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td><code>perlu_ditinjau</code></td><td>Perlu diperiksa petugas</td></tr>
            <tr><td><code>kritis</code></td><td>Berpotensi merugikan — tangani lebih dulu</td></tr>
            <tr><td><code>info</code></td><td>Informatif; biasanya tidak butuh tindakan</td></tr>
        </tbody>
    </table>
    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Pemeriksaan dibatasi <strong>500 temuan per kategori</strong>. Bila jumlah temuan lebih besar, sistem menandainya sebagai "terpotong" — jalankan pada periode lebih kecil untuk detail lengkap.</p>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Akhir bulan, bendahara menjalankan rekonsiliasi untuk periode berjalan → 3 temuan: 1 "Pembayaran verified tanpa kuitansi" (segera terbitkan kuitansi lewat verifikasi), 1 "Saldo siswa negatif" (koreksi alokasi), 1 "info". Setelah ditindaklanjuti, rekonsiliasi ulang menampilkan nol temuan kritis.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Temuan berulang tiap periode</td><td>Masalah mendasar belum diperbaiki (mis. verifikasi ganda)</td><td>Perbaiki alur, bukan hanya menutup temuan</td></tr>
            <tr><td>Jumlah temuan sangat besar</td><td>Data awal belum bersih</td><td>Jalankan per minggu selama transisi; tandai batas per kategori</td></tr>
            <tr><td>Export kosong</td><td>Tidak ada temuan pada filter</td><td>Itu bagus — simpan sebagai bukti pemeriksaan</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/pembayaran-manual">Pembayaran Manual &amp; Verifikasi</a></li>
        <li><a href="/welcome-docs/artikel/refund-koreksi">Refund &amp; Koreksi Transaksi</a></li>
        <li><a href="/welcome-docs/artikel/export-laporan">Laporan &amp; Export</a></li>
    </ul>
</section>
