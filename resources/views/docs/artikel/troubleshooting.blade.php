<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> PANDUAN · TROUBLESHOOTING</p>
    <h1 class="doc-title">Masalah Umum &amp; Solusinya</h1>
    <p class="doc-lead">Kumpulan gejala yang sering dialami pengguna SIAKAD, sebabnya, dan solusi sesuai implementasi aplikasi.</p>

    <h2 id="tujuan">Tujuan fitur</h2>
    <p>Sebagian besar "error" di SIAKAD bukan kerusakan, melainkan aturan aplikasi yang belum dipahami. Halaman ini memisahkan keduanya.</p>

    <h2 id="pengguna">Siapa yang dapat menggunakan</h2>
    <p>Semua pengguna.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul><li>Anda login (atau mencoba login).</li><li>Perhatikan pesan yang muncul — salin persis bila perlu melapor.</li></ul>

    <h2 id="langkah">Daftar masalah</h2>

    <h3>Akun &amp; Login</h3>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>"Username atau password salah"</td><td>Salah ketik (huruf besar/kecil penting)</td><td>Periksa CAPS LOCK; coba lagi dengan sabar</td></tr>
            <tr><td>"Captcha tidak valid"</td><td>Captcha salah/kedaluwarsa</td><td>Klik refresh captcha, isi ulang</td></tr>
            <tr><td>Akun terblokir sementara</td><td>Terlalu banyak gagal login</td><td>Tunggu beberapa menit; bila sering terjadi, hubungi admin</td></tr>
            <tr><td>Diminta mengganti password saat login</td><td>Kewajiban ganti password aktif</td><td>Isi password lama + password baru dua kali</td></tr>
            <tr><td>"Akun nonaktif"</td><td>Dinonaktifkan admin</td><td>Hubungi administrator sekolah</td></tr>
            <tr><td>Lupa password</td><td>—</td><td>Hubungi administrator; tidak ada fitur "lupa password" mandiri</td></tr>
        </tbody>
    </table>

    <h3>Halaman &amp; Menu</h3>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Menu tidak muncul di sidebar</td><td>Menu bukan untuk peran Anda / paket belum sinkron</td><td>Verifikasi peran; minta superadmin sinkronkan paket</td></tr>
            <tr><td>"403 / Forbidden" saat membuka halaman</td><td>Role tidak berwenang</td><td>Gunakan halaman sesuai peran Anda</td></tr>
            <tr><td>"404 / Halaman tidak ditemukan"</td><td>Menu lama/rusak</td><td>Refresh; gunakan sidebar; laporkan bila berulang</td></tr>
            <tr><td>Halaman kosong setelah login</td><td>Paket menu kosong / sesi habis</td><td>Logout, login ulang; hubungi admin bila tetap</td></tr>
            <tr><td>Halaman "Dokumen ini belum tersedia"</td><td>Dokumentasi belum memuat artikel itu</td><td>Gunakan mesin pencari dokumentasi (jika tersedia) atau hubungi admin</td></tr>
        </tbody>
    </table>

    <h3>Akademik (Jadwal, Absensi, Nilai, Rapor)</h3>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Jadwal siswa/guru kosong</td><td>Jadwal belum diisi / salah semester / belum ditempatkan</td><td>Periksa konteks akademik &amp; Kelassiswa; hubungi superadmin</td></tr>
            <tr><td>Grid jadwal kelas kosong</td><td>Jam Pelajaran kelas belum diatur</td><td>Atur di menu Jam Pelajaran</td></tr>
            <tr><td>"Guru tidak terdaftar mengampu …"</td><td>Relasi guru–mapel belum dibuat</td><td>Hubungkan di menu Guru</td></tr>
            <tr><td>Guru bentrok saat mengisi jadwal</td><td>Waktu tumpang tindih di kelas lain</td><td>Geser jam / pilih guru lain</td></tr>
            <tr><td>Guru tidak bisa input nilai</td><td>Bukan penugasan Anda / semester tidak aktif / konfigurasi bobot ≠ 100%</td><td>Periksa penugasan &amp; komponen nilai</td></tr>
            <tr><td>Nilai disimpan tapi KKTP merah</td><td>Di bawah KKTP — bukan error</td><td>Sesuai aturan; tidak perlu diperbaiki</td></tr>
            <tr><td>Rapor "belum ada rapor final"</td><td>Semester belum difinalisasi</td><td>Tunggu finalisasi wali kelas/superadmin</td></tr>
            <tr><td>Nilai rapor berubah?</td><td>Koreksi lewat jalur terkendali</td><td>Koreksi hanya oleh superadmin + tercatat</td></tr>
        </tbody>
    </table>

    <h3>Keuangan &amp; Pembayaran</h3>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Pembayaran online tidak bisa diselesaikan</td><td>Gateway belum diaktifkan</td><td>Gunakan Pembayaran Manual; tanyakan jadwal aktivasi</td></tr>
            <tr><td>"Pembayaran belum terverifikasi"</td><td>Status masih pending</td><td>Tunggu verifikasi petugas; hubungi tata usaha</td></tr>
            <tr><td>Tagihan tetap belum lunas padahal sudah bayar</td><td>Alokasi belum / nominal kurang</td><td>Periksa detail tagihan; hubungi admin</td></tr>
            <tr><td>Bukti pembayaran tidak bisa diunduh</td><td>Berkas belum/hilang</td><td>Minta petugas mengunggah ulang</td></tr>
            <tr><td>Denda tidak hilang meski sudah lunas</td><td>Denda terpisah; pembayaran menutupi pokok + denda</td><td>Periksa rincian tagihan; ajukan pembebasan bila berhak</td></tr>
        </tbody>
    </table>

    <h3>Lain-lain</h3>
    <table class="doc-table">
        <thead><tr><th>Gejala</th><th>Sebab</th><th>Solusi</th></tr></thead>
        <tbody>
            <tr><td>Notifikasi tidak muncul</td><td>Tidak ada kejadian yang memicu notifikasi</td><td>Tidak semua aktivitas mengirim notifikasi</td></tr>
            <tr><td>Halaman sangat lambat</td><td>Jaringan / server sibuk</td><td>Refresh; coba di luar jam sibuk; laporkan bila kronis</td></tr>
            <tr><td>Data ganda</td><td>Entri ganda oleh dua petugas</td><td>Laporkan — jangan hapus sendiri tanpa arahan</td></tr>
            <tr><td>Perubahan tidak tersimpan</td><td>Koneksi putus saat menyimpan</td><td>Periksa &amp; simpan ulang; cek pesan error</td></tr>
        </tbody>
    </table>

    <h2 id="status">Kapan harus menghubungi admin</h2>
    <ul>
        <li>Akun terkunci / lupa password.</li>
        <li>Data pribadi salah (nama, NISN, tanggal lahir).</li>
        <li>Transaksi keuangan yang keliru.</li>
        <li>Indikasi akun disalahgunakan orang lain.</li>
    </ul>

    <h2 id="contoh">Contoh penggunaan</h2>
    <p>Siswa tidak bisa login → ia ingat password baru saja diganti → ia memakai password baru; bila masih gagal, ia melapor ke tata usaha (jangan menebak berulang-ulang agar tidak terblokir).</p>

    <h2 id="masalah">Belum menemukan solusi?</h2>
    <p>Buka <a href="/welcome-docs/artikel/pusat-bantuan">Pusat Bantuan</a> — berisi cara mencari di dokumentasi dan cara melapor masalah dengan baik.</p>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/pusat-bantuan">Pusat Bantuan &amp; Cara Melapor Masalah</a></li>
        <li><a href="/welcome-docs/artikel/keamanan-akun">Keamanan Akun</a></li>
        <li><a href="/welcome-docs/artikel/login">Login &amp; Logout</a></li>
    </ul>
</section>
