<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · RAPOR</p>
    <h1 class="doc-title">Melihat &amp; Mencetak Rapor</h1>
    <p class="doc-lead">Cara siswa, wali kelas, dan wali murid melihat rapor — dan mengunduhnya sebagai PDF.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Rapor adalah dokumen resmi hasil belajar siswa dalam satu semester. Rapor dibentuk dari nilai akhir yang sudah difinalisasi — bukan diisi manual.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <ul>
        <li><strong>Siswa</strong> — melihat rapor sendiri &amp; mengunduh PDF (menu <em>Rapor Saya</em>).</li>
        <li><strong>Guru wali kelas / Superadmin</strong> — melihat rapor kelas, menambah catatan wali kelas, mengunduh PDF (menu <em>Rapor Wali Kelas</em>).</li>
        <li><strong>Wali murid</strong> — melihat nilai/rapor anak pada tab <em>Nilai / Rapor</em> (menu <em>Akademik &amp; Keuangan</em>).</li>
    </ul>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Rapor semester bersangkutan sudah difinalisasi (status <code>final</code>) — sebelum final, nilai belum resmi.</li>
        <li>Untuk siswa/wali: login dengan akun terkait.</li>
        <li>Untuk wali: relasi dengan siswa sudah diverifikasi.</li>
    </ul>

    <h2 id="langkah">Langkah-langkah</h2>

    <h3>Siswa: melihat &amp; mengunduh rapor</h3>
    <ol>
        <li>Buka menu <strong>Rapor Saya</strong>.</li>
        <li>Daftar rapor tersedia ditampilkan (Tahun Ajaran · Semester · Kelas · Status).</li>
        <li>Pilih rapor pada dropdown <strong>Pilih semester &amp; kelas</strong>.</li>
        <li>Tabel menampilkan <strong>Mata Pelajaran | Nilai Akhir | KKTP | Keterangan</strong> (Tuntas/Belum Tuntas).</li>
        <li>Klik <strong>Unduh PDF</strong> untuk mengunduh berkas rapor.</li>
    </ol>

    <h3>Wali kelas: melihat &amp; memberi catatan</h3>
    <ol>
        <li>Buka menu <strong>Rapor Wali Kelas</strong>.</li>
        <li>Pilih <strong>Kelas</strong>, lalu pilih <strong>Siswa</strong>.</li>
        <li>Tabel rapor siswa ditampilkan; isi <strong>Catatan</strong> (saran/motivasi) bila perlu.</li>
        <li>Klik <strong>Simpan Catatan</strong>.</li>
        <li>Klik <strong>Unduh PDF</strong> untuk mencetak rapor lengkap termasuk catatan.</li>
    </ol>

    <h3>Wali: melihat rapor anak</h3>
    <ol>
        <li>Buka menu <strong>Akademik &amp; Keuangan</strong> (menu wali).</li>
        <li>Pilih anak pada daftar (bila lebih dari satu).</li>
        <li>Buka tab <strong>Nilai / Rapor</strong>.</li>
    </ol>

    <h2 id="status">Arti keterangan &amp; status</h2>
    <table class="doc-table">
        <thead><tr><th>Keterangan</th><th>Arti</th></tr></thead>
        <tbody>
            <tr><td>Tuntas</td><td>Nilai akhir ≥ KKTP</td></tr>
            <tr><td>Belum Tuntas</td><td>Nilai akhir &lt; KKTP</td></tr>
            <tr><td>Status <code>final</code></td><td>Rapor resmi — dapat dicetak</td></tr>
            <tr><td>Belum ada rapor final</td><td>Semester belum difinalisasi — nilai belum ditampilkan/resmi</td></tr>
        </tbody>
    </table>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Orang tua membuka portal wali → tab Nilai/Rapor → melihat semua mapel anaknya tuntas di semester Ganjil. Ia meminta unduhan PDF ke wali kelas saat pembagian rapor.</p>

    <h2 id="masalah">Masalah umum</h2>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>"Belum ada rapor final yang tersedia"</td><td>Belum difinalisasi</td><td>Tunggu finalisasi oleh wali kelas/superadmin</td></tr>
            <tr><td>Nilai berbeda dengan perkiraan</td><td>Nilai akhir berbobot komponen</td><td>Lihat rincian komponen di Input Nilai (guru/superadmin)</td></tr>
            <tr><td>Guru lain tidak bisa mencatat rapor kelas bukan wali</td><td>Kepemilikan kelas ditegakkan service</td><td>Wali kelas yang berhak mengisi catatan</td></tr>
        </tbody>
    </table>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/nilai-akhir-finalisasi">Nilai Akhir &amp; Finalisasi Rapor</a></li>
        <li><a href="/welcome-docs/artikel/input-nilai">Input &amp; Koreksi Nilai</a></li>
        <li><a href="/welcome-docs/artikel/wali-akademik-keuangan">Wali: Akademik &amp; Keuangan Anak</a></li>
    </ul>
</section>
