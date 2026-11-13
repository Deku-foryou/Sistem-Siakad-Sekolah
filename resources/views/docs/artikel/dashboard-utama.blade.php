<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · DASHBOARD</p>
    <h1 class="doc-title">Membaca Dashboard (Superadmin &amp; Admin)</h1>
    <p class="doc-lead">Dashboard adalah pusat informasi sekolah Anda — statistik, kehadiran, antrean kerja, aktivitas, dan akses cepat ke pekerjaan penting.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Dashboard memberi ringkasan kondisi sekolah dan pekerjaan yang menunggu Anda, tanpa harus membuka banyak menu.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Hanya <strong>Superadmin</strong> dan <strong>Admin</strong>. Guru, siswa, dan wali murid tidak dapat mengakses halaman ini.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Anda login sebagai Superadmin atau Admin.</li>
        <li>Tahun ajaran dan semester aktif sudah diatur sekolah (abaikan bila belum — dashboard tetap tampil dengan catatan pada bagian berisi data kelas).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah &amp; bagian dashboard</h2>

    <figure class="doc-figure">
        <img src="/img/docs/dashboard.png" alt="Dashboard superadmin" class="doc-shot">
        <figcaption><strong>Gambar 1</strong> — Dashboard superadmin: ringkasan sekolah, pekerjaan menunggu, statistik, dan aktivitas terbaru.</figcaption>
    </figure>

    <h3>1. Sapaan &amp; konteks</h3>
    <p>Baris atas menampilkan sapaan sesuai waktu dan nama Anda, tanggal, serta <strong>Tahun Ajaran</strong> dan <strong>Semester</strong> yang aktif — selalu gunakan konteks ini sebagai acuan seluruh angka di dashboard.</p>

    <h3>2. Ikhtisar sekolah (panel kiri)</h3>
    <p>Identitas SIAKAD dan deskripsi singkat sekolah. Tombol <em>Kelola akademik</em> (khusus superadmin) membuka halaman Tahun Ajaran &amp; Semester.</p>

    <h3>3. Butuh perhatian (panel kanan)</h3>
    <p>Daftar pekerjaan yang menunggu tindakan: verifikasi wali, permintaan putus hubungan, pembayaran manual yang perlu diverifikasi, refund, koreksi dan pembebasan denda. Klik item untuk membuka halaman terkait. Bila tidak ada yang menunggu, panel menampilkan <em>"Semua beres"</em>.</p>

    <h3>4. Statistik utama</h3>
    <ul>
        <li><strong>Siswa Aktif</strong> — jumlah siswa dengan status aktif.</li>
        <li><strong>Guru Aktif</strong> — jumlah guru dengan status aktif.</li>
        <li><strong>Kelas Aktif</strong> — jumlah kelas pada tahun ajaran yang aktif.</li>
        <li><strong>Mata Pelajaran</strong> — jumlah mata pelajaran terdaftar.</li>
    </ul>

    <h3>5. Tren kehadiran &amp; kehadiran hari ini</h3>
    <p>Grafik garis menampilkan jumlah siswa hadir per tanggal (7/30/90 hari terakhir). <strong>Kehadiran Hari Ini</strong> menampilkan jumlah hadir/izin/sakit/alpa serta <strong>Belum Diabsen</strong> — siswa yang belum diabsen <strong>tidak</strong> dihitung sebagai alpa.</p>

    <h3>6. Aktivitas terbaru</h3>
    <p>Daftar aktivitas data terakhir (mis. "Superadmin membuat Jurusan") dari catatan aktivitas sistem. Hanya superadmin yang melihat bagian ini.</p>

    <h3>7. Pengumuman terbaru</h3>
    <p>Lima pengumuman terakhir dengan statusnya (terbit/draf/arsip). Superadmin melihat semua; admin hanya yang berstatus <strong>terbit</strong>.</p>

    <h3>8. Ringkasan keuangan</h3>
    <p>Tagihan tertunda, pembayaran masuk bulan ini, dan tunggakan. Bagian ini hanya tampil untuk <strong>superadmin dan admin</strong>.</p>

    <h2 id="status">Arti indikator penting</h2>
    <ul>
        <li>Badge <strong>Operasional</strong> — menunjukkan Anda mengelola sekolah dengan akses admin.</li>
        <li>Angka <strong>Butuh Perhatian</strong> — total item yang menunggu tindakan; klik untuk membuka antreannya.</li>
        <li>Donut chart <strong>Komposisi Siswa</strong> — juga tampil di halaman dashboard bila ada data jurusan.</li>
    </ul>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Superadmin membuka dashboard pagi hari, melihat <em>2 verifikasi wali menunggu</em>, mengekliknya dan langsung memverifikasi. Lalu ia memeriksa tren kehadiran 30 hari terakhir untuk melihat penurunan kehadiran kelas XII IPA 1.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Angka kelas aktif "—"</td><td>Konteks akademik belum disinkronkan</td><td>Kelola tahun ajaran &amp; semester, aktifkan salah satunya</td></tr>
            <tr><td>Notifikasi belum dibaca banyak</td><td>Belum dibuka</td><td>Klik ikon lonceng atau menu <em>Notifikasi</em></td></tr>
            <tr><td>Keuangan tidak tampil</td><td>Peran Anda tidak berwenang</td><td>Sesuai kebijakan sekolah; hubungi superadmin</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/dashboard-keuangan">Membaca Dashboard Keuangan</a></li>
        <li><a href="/welcome-docs/artikel/konteks-akademik">Konteks Akademik Aktif</a></li>
    </ul>
</section>
