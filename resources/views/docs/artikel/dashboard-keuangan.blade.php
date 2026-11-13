<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · DASHBOARD</p>
    <h1 class="doc-title">Membaca Dashboard Keuangan</h1>
    <p class="doc-lead">Ringkasan kondisi keuangan sekolah — tagihan, pembayaran, beasiswa, dan beban per kelas.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Dashboard keuangan membantu Anda melihat "kesehatan pembayaran" sekolah dalam satu layar.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Superadmin dan Admin</strong> yang memiliki akses ke modul keuangan.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Login sebagai superadmin atau admin.</li>
        <li>Tagihan sudah diterbitkan untuk periode aktif.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <ol>
        <li>Klik <strong>Dashboard Keuangan</strong> pada sidebar (atau buka langsung <code>/dashboardkeuangan</code>).</li>
        <li>Lihat <strong>Tagihan terbit</strong>, <strong>Lunas</strong>, <strong>Belum lunas</strong>, <strong>Sebagian</strong>, dan <strong>Dibebaskan</strong>.</li>
        <li>Perhatikan tabel <strong>Tagihan per Kelas</strong> — kelas mana yang paling banyak tagihannya.</li>
    </ol>

    <figure class="doc-figure">
        <img src="/img/docs/dashboard-keuangan.png" alt="Dashboard Keuangan" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Dashboard keuangan: jumlah tagihan per status dan distribusi per kelas.</figcaption>
    </figure>

    <h3>Arti angka</h3>
    <ul>
        <li><strong>Tagihan terbit</strong> — seluruh tagihan yang sudah diterbitkan (tidak termasuk dibatalkan).</li>
        <li><strong>Lunas</strong> — tagihan yang sudah dibayar penuh. <em>Lunas dihitung dari alokasi pembayaran sukses.</em></li>
        <li><strong>Belum lunas / Sebagian</strong> — tagihan yang belum lunas atau hanya dibayar sebagian.</li>
        <li><strong>Dibebaskan</strong> — tagihan yang dibebaskan sekolah (mis. karena beasiswa penuh).</li>
    </ul>

    <h2 id="status">Arti status tagihan</h2>
    <table class="doc-table">
        <thead><tr><th>Status</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Belum dibayar</td><td>Belum ada pembayaran masuk.</td></tr>
            <tr><td>Dibayar_sebagian</td><td>Sebagian nominal sudah dibayar.</td></tr>
            <tr><td>Lunas</td><td>Nominal penuh terbayar (termasuk beasiswa pembebasan).</td></tr>
            <tr><td>Dibebaskan</td><td>Tagihan dibebaskan sekolah (tidak perlu dibayar).</td></tr>
            <tr><td>Dibatalkan</td><td>Tagihan dibatalkan; tidak dihitung.</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Superadmin melihat <strong>79 tagihan belum lunas</strong> dan 2 beasiswa pembebasan. Ia membuka <strong>Penerbitan &amp; Status Tagihan</strong> untuk menindaklanjuti tunggakan.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Tagihan per kelas kosong</td><td>Belum ada tagihan terbit</td><td>Terbitkan tagihan periode aktif terlebih dahulu</td></tr>
            <tr><td>Angka tidak sesuai ekspektasi</td><td>Tagihan dibatalkan ikut terhitung</td><td>Angka hanya menghitung tagihan tidak dibatalkan — itu benar</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/penerbitan-tagihan">Penerbitan &amp; Status Tagihan</a></li>
        <li><a href="/welcome-docs/artikel/tarif-beasiswa">Tarif &amp; Beasiswa</a></li>
    </ul>
</section>
