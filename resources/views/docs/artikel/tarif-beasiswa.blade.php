<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · KEUANGAN</p>
    <h1 class="doc-title">Tarif &amp; Beasiswa (Potongan)</h1>
    <p class="doc-lead">Mengatur tarif global/kelas/individu dan beasiswa berupa nominal, persen, atau pembebasan penuh.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Tarif menentukan berapa yang harus dibayar siswa per jenis/periode tagihan. Beasiswa mengurangi atau membebaskan tagihan tertentu. Keduanya tercatat, termasuk riwayat perubahannya.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Superadmin dan Admin</strong> (sesuai kebijakan sekolah).</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sesuai peran.</li>
        <li>Kelas dan siswa sudah terdaftar.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Membuat tarif</h3>
    <ol>
        <li>Buka menu <strong>Tarif</strong>.</li>
        <li>Klik <strong>Tambah</strong>; pilih <strong>Jenis</strong>:<br>
            <code>global</code> — berlaku untuk semua siswa;<br>
            <code>kelas</code> — hanya kelas tertentu;<br>
            <code>individu</code> — hanya siswa tertentu.</li>
        <li>Isi <strong>Nama</strong> (mis. "SPP Reguler 2026/2027"), <strong>Nominal</strong>, <strong>Berlaku Dari</strong> (dan <strong>Berlaku Sampai</strong> opsional).</li>
        <li>Untuk jenis kelas/individu, pilih kelas/siswa bersangkutan.</li>
        <li>Klik <strong>Simpan</strong>.</li>
    </ol>
    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Tarif berlaku <strong>mulai tanggal berlaku</strong>-nya. Mengubah tarif <strong>tidak</strong> mengubah tagihan yang sudah diterbitkan — gunakan menu koreksi tagihan bila memang perlu.</p>

    <h3>Menonaktifkan tarif</h3>
    <p>Klik tombol status pada tarif — tarif nonaktif tidak lagi dipakai untuk penerbitan baru; histori tetap tersimpan.</p>

    <h3>Membuat beasiswa</h3>
    <ol>
        <li>Buka menu <strong>Beasiswa</strong>.</li>
        <li>Klik <strong>Tambah</strong>; pilih <strong>Jenis</strong>: <code>nominal</code> (potongan tetap), <code>persen</code> (potongan %), atau <code>pembebasan</code> (digratiskan).</li>
        <li>Isi <strong>Nilai</strong> (persen 1–100 untuk persen; nominal untuk nominal; untuk pembebasan tidak perlu nilai besar).</li>
        <li>Isi periode berlaku dan <strong>Alasan</strong>.</li>
        <li><strong>Simpan</strong>, lalu hubungkan ke siswa lewat tombol <strong>Berikan ke Siswa</strong> (pilih siswa).</li>
    </ol>
    <p class="doc-note doc-note-info"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Bila beasiswa siswa setara <strong>100%</strong> (pembebasan), tagihan otomatis berstatus <code>dibebaskan</code> saat diterbitkan — tidak perlu membayar.</p>

    <h3>Riwayat tarif</h3>
    <p>Setiap pembuatan/perubahan/penonaktifan tarif tercatat di riwayat — siapa, kapan, nilai lama → baru, dan alasan.</p>

    <h2 id="status">Arti status &amp; prioritas diskon</h2>
    <table class="doc-table">
        <thead><tr><th>Keterangan</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Tarif status aktif/nonaktif</td><td>Nonaktif = tidak dipakai penerbitan baru</td></tr>
            <tr><td>Pembebasan 100%</td><td>Total tagihan menjadi Rp 0 → status <code>dibebaskan</code></td></tr>
            <tr><td>Potongan</td><td>Diskon terbaik (nominal) atau persen terbesar diterapkan; diskon maksimal 100%</td></tr>
            <tr><td>Berlaku sampai kosong</td><td>Berlaku seterusnya sampai dinonaktifkan</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Sekolah menetapkan SPP global Rp 250.000/bulan. Dua siswa kurang mampu mendapat beasiswa <code>persen</code> 50%; satu siswa mendapat <code>pembebasan</code> penuh. Saat tagihan SPP terbit, dua siswa itu dipotong 50% dan satu siswa otomatis <code>dibebaskan</code>.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Tagihan terbit tanpa nominal / dilewati</td><td>Siswa belum punya tarif berlaku</td><td>Buat/perbaiki tarif untuk siswa itu</td></tr>
            <tr><td>Beasiswa tidak terpotong</td><td>Beasiswa belum dihubungkan ke siswa / sudah tidak berlaku</td><td>Hubungkan; periksa periode berlaku</td></tr>
            <tr><td>Tarif lama masih dipakai</td><td>Berlaku sampai kosong</td><td>Isi berlaku sampai / nonaktifkan</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/penerbitan-tagihan">Penerbitan &amp; Status Tagihan</a></li>
        <li><a href="/welcome-docs/artikel/pembayaran-manual">Pembayaran Manual &amp; Verifikasi</a></li>
        <li><a href="/welcome-docs/artikel/saldo">Saldo Siswa &amp; Penggunaannya</a></li>
    </ul>
</section>
