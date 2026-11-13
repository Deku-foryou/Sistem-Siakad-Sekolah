<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · PENILAIAN</p>
    <h1 class="doc-title">Nilai Akhir &amp; Finalisasi Rapor</h1>
    <p class="doc-lead">Bagaimana nilai akhir dihitung dari komponen, apa itu KKTP, dan kapan rapor dikunci.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Nilai akhir adalah ringkasan nilai siswa per mapel dalam satu semester. Finalisasi mengunci nilai itu sebagai rapor resmi periode tersebut.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p><strong>Finalisasi</strong>: guru wali kelas (guru yang ditunjuk sebagai wali kelas) atau Superadmin. <strong>Koreksi pasca final</strong>: Superadmin (lihat artikel Input &amp; Koreksi Nilai).</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Semester aktif diatur.</li>
        <li>Seluruh komponen nilai mapel valid (total bobot aktif = 100%).</li>
        <li>KKTP seluruh mapel terisi.</li>
        <li>Nilai seluruh siswa pada komponen sudah terisi (tidak ada yang kosong).</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>
    <h3>Menghitung &amp; memeriksa nilai akhir (draft)</h3>
    <p>Nilai akhir draft dihitung dari komponen dengan rumus berbobot: jumlah (nilai × bobot) dibagi total bobot terisi. Komponen kosong pada perhitungan draft ikut diabaikan (dinormalisasi terhadap bobot terisi) sehingga guru dapat menyimpan bertahap. Semua angka ditampilkan pada halaman input dan saat finalisasi.</p>

    <h3>Memeriksa kesiapan kelas</h3>
    <ol>
        <li>Buka menu <strong>Finalisasi Rapor</strong>.</li>
        <li>Pilih <strong>Kelas</strong>.</li>
        <li>Klik <strong>Periksa</strong> — sistem menampilkan daftar kesalahan bila ada: komponen belum valid, KKTP kosong, atau nilai siswa belum lengkap (per mapel/siswa).</li>
    </ol>

    <h3>Memfinalisasi rapor</h3>
    <ol>
        <li>Setelah daftar kesalahan kosong, klik <strong>Finalisasi</strong>.</li>
        <li>Sistem mengunci kelas (transaksi), menghitung <strong>snapshot nilai akhir</strong> tepat saat itu, dan mengubah status finalisasi kelas menjadi <code>final</code>.</li>
        <li>Setelah final, nilai rapor tidak dapat diubah lewat input biasa.</li>
    </ol>

    <h2 id="status">Status &amp; aturan penting</h2>
    <table class="doc-table">
        <thead><tr><th>Status / aturan</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td><code>draf</code></td><td>Belum difinalisasi — nilai masih bisa diubah</td></tr>
            <tr><td><code>final</code></td><td>Terkunci — nilai akhir tersimpan sebagai snapshot; perubahan hanya lewat Koreksi Nilai</td></tr>
            <tr><td>Koreksi lewat jalur terkendali</td><td>Menu Koreksi Nilai dengan alasan wajib &amp; log perubahan</td></tr>
            <tr><td>Snapshot saat finalisasi</td><td>Nilai akhir rapor dihitung TEPAT saat finalisasi, bukan saat dibaca</td></tr>
            <tr><td>Semester finalisasi selalu semester aktif</td><td>Tidak bisa memfinalisasi semester lampau dari halaman ini</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Akhir semester Ganjil: wali kelas XII IPA 1 membuka Finalisasi Rapor → Periksa → muncul "2 nilai belum lengkap: Budi — Praktik" → guru mapel melengkapi → Periksa lagi → bersih → klik Finalisasi → rapor kelas terkunci dan tercetak.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>"Rapor kelas ini pada semester aktif sudah difinalisasi"</td><td>Status sudah final</td><td>Koreksi lewat menu Koreksi Nilai (superadmin)</td></tr>
            <tr><td>Daftar kesalahan panjang</td><td>Data belum siap</td><td>Perbaiki satu per satu: komponen, KKTP, nilai kosong</td></tr>
            <tr><td>Tidak bisa finalisasi — "bukan semester aktif"</td><td>Konteks akademik bukan semester berjalan</td><td>Aktifkan semester yang benar</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/komponen-nilai">Komponen Penilaian, Bobot &amp; KKTP</a></li>
        <li><a href="/welcome-docs/artikel/input-nilai">Input &amp; Koreksi Nilai</a></li>
        <li><a href="/welcome-docs/artikel/rapor">Melihat &amp; Mencetak Rapor</a></li>
    </ul>
</section>
