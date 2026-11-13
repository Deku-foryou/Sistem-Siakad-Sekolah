<section class="doc-article">
    <p class="doc-eyebrow"><i class="fa-solid fa-book-open" aria-hidden="true"></i> REFERENSI DATA · (UNTUK PENGGUNA LOGIN)</p>
    <h1 class="doc-title">Referensi Data — Relasi &amp; Alur</h1>
    <p class="doc-lead">Bagaimana tabel-tabel SIAKAD saling terhubung, dan bagaimana data mengalir di setiap modul — dengan contoh sintetis.</p>

    <h2 id="tujuan">Tujuan</h2>
    <p>Memahami "mengapa data ini terhubung ke sana" — berguna saat memeriksa inkonsistensi, memberi penjelasan ke orang tua siswa, atau menelusuri sumber sebuah angka. Contoh di halaman ini selalu <strong>sintetis</strong> (mis. siswa bernama <em>Andre Uji</em>), bukan data nyata.</p>

    <h2 id="prasyarat">Prasyarat</h2>
    <ul>
        <li>Anda login.</li>
        <li>Sebaiknya sudah membaca <a href="/welcome-docs/artikel/referensi-tabel">Referensi Data — Tabel &amp; Kolom</a>.</li>
    </ul>

    <h2 id="langkah">Peta relasi utama</h2>
    <pre class="doc-diagram">users ─1:1─ guru ─N:M─ mapel            users ─1:1─ siswa
   │                 guru ─1:N─ penugasan_mengajar ─┐
   │                                               ├─N:1─ semester ─N:1─ tahunajaran
   └─1:N─ usermenu ─N:1─ appmenunew                  │
                                                     ├─N:1─ kelas ─N:1─ tahunajaran
kelassiswa: siswa N:M kelas ─────────────────────────┘         │
                                                               ├─N:1─ jurusan
gurumapel: guru N:M mapel                                      └─N:1─ guru (wali kelas)

jadwalkelas: semester × jamhari × mapel × guru
jadwal_pertemuan: 1 jadwalkelas → banyak pertemuan (tanggal) ─1:N─ absensi_perjam
absensi_harian: siswa × kelas × tanggal

nilai_siswa: siswa × komponen_nilai ─N:1─ semester
finalisasi_rapor: kelas × semester (1:1) ─→ snapshot nilai_akhir (siswa × mapel)

tagihan: siswa × (jenis, periode) ─N:1─ tahunajaran/semester
payment_attempt ─1:1?─→ pembayaran ─1:N─ pembayaran_alokasi ─N:1─ tagihan/saldo
kuitansi: pembayaran 1:1
refund_pengajuan / koreksi_pengajuan ─N:1─ pembayaran</pre>

    <h3>Alur modul akademik</h3>
    <ol>
        <li><strong>Setup:</strong> tahunajaran + semester dibuat &amp; diaktifkan → kelas dibentuk per TA → siswa ditempatkan (kelassiswa) → guru dihubungkan mapel (gurumapel) → penugasan mengajar per semester.</li>
        <li><strong>Mengajar:</strong> jamhari per kelas → jadwalkelas (hari/jam/mapel/guru) → jadwal_pertemuan saat sesi tiba → absensi_perjam. Absensi harian berdiri sendiri (tanpa jadwal).</li>
        <li><strong>Menilai:</strong> komponen_nilai (total bobot 100%) + kktp_mapel → nilai_siswa per komponen → nilai_akhir (snapshot) saat finalisasi_rapor berstatus <code>final</code>.</li>
    </ol>

    <h3>Alur modul keuangan</h3>
    <ol>
        <li><strong>Setup:</strong> tarif (global/kelas/individu, berlaku dari–sampai) + beasiswa (nominal/persen/pembebasan) dihubungkan ke siswa (beasiswa_siswa).</li>
        <li><strong>Penerbitan:</strong> tagihan dibuat per siswa × jenis × periode; potongan beasiswa dihitung otomatis; tagihan dengan pembebasan 100% menjadi <code>dibebaskan</code>.</li>
        <li><strong>Pembayaran:</strong> manual → pembayaran (<code>pending</code>) → verifikasi → <code>success</code> → pembayaran_alokasi ke tagihan/saldo → tagihan diperbarui → kuitansi terbit. Online → payment_attempt → webhook provider → pembayaran (<code>success</code>) → alokasi. (<em>Gateway belum aktif.</em>)</li>
        <li><strong>Bermasalah:</strong> kelebihan bayar → saldo; terlambat → denda (1:1 per tagihan); kesalahan → refund_pengajuan / koreksi_pengajuan → reversal alokasi.</li>
        <li><strong>Kontrol:</strong> rekonsiliasi_hasil + rekonsiliasi_temuan memeriksa konsistensi seluruh rantai di atas.</li>
    </ol>

    <h3>Alur modul wali</h3>
    <ol>
        <li>Admin membuat akun wali (waliakun_history mencatatnya).</li>
        <li>Wali mengajukan relasi (walimurid status <code>pending</code>) → admin memverifikasi/menolak (walimurid_history mencatat event-nya).</li>
        <li>Bila verified, wali dapat membuka akademik/keuangan anak; bila wali ingin mengakhiri, ia membuat walimurid_permintaan_putus → admin memutuskan.</li>
    </ol>

    <h3>Alur modul pengumuman &amp; notifikasi</h3>
    <ol>
        <li>Superadmin membuat pengumuman (draf) → Terbitkan → status <code>terbit</code> + notifikasi ke penerima target (notifikasi_penerima).</li>
        <li>Siswa/wali membaca; <code>pengumuman_baca</code> mencatat siapa yang sudah membaca.</li>
        <li>Arsipkan → status <code>arsip</code>; riwayat tetap tersimpan.</li>
    </ol>

    <h2 id="status">Aturan integritas yang ditegakkan</h2>
    <ul>
        <li><strong>FK RESTRICT</strong> — tidak ada data induk yang bisa dihapus bila masih dipakai.</li>
        <li><strong>Snapshot</strong> — data historis (nilai akhir, tarif pada tagihan, kuitansi, dokumen refund) dibekukan saat peristiwa, bukan dihitung ulang belakangan.</li>
        <li><strong>Immutability log</strong> — <code>nilai_perubahan_log</code>, <code>tarif_riwayat</code>, <code>walimurid_history</code>, <code>waliakun_history</code> tidak pernah diedit/dihapus.</li>
        <li><strong>Double-entry keuangan</strong> — setiap pergerakan uang punya alokasi; pembalik selalu lewat reversal, bukan update di tempat.</li>
        <li><strong>Unique natural key</strong> — NISN/NIS/NIP/username/order_id/nomor kuitansi unik.</li>
        <li><strong>Konsistensi status</strong> — status tagihan selalu diturunkan dari alokasi pembayaran, bukan diset manual.</li>
    </ul>

    <h3>Contoh sintetis: satu siswa dalam satu semester</h3>
    <p><em>Andre Uji</em> (NISN <code>0000000001</code>) dipindahkan ke kelas <code>XII UJI 1</code> (kelassiswa <code>aktif</code>, TA <code>2026/2027</code>). Guru <em>Sari Uji</em> ditugaskan mengajar Matematika (penugasan_mengajar). Nilai Ulangan (bobot 40) dan Tugas (60) diinput → nilai akhir 82, KKTP 70 → tuntas. Tagihan SPP November terbit dari tarif global (Rp 250.000) → dibayar sebagian Rp 150.000 → status <code>dibayar_sebagian</code> → sisanya dibayar bulan berikutnya → <code>lunas</code> + kuitansi terbit.</p>

    <h2 id="terkait">Artikel terkait</h2>
    <ul>
        <li><a href="/welcome-docs/artikel/referensi-tabel">Referensi Data — Tabel &amp; Kolom</a></li>
        <li><a href="/welcome-docs/artikel/glosarium">Glosarium &amp; Kamus Data</a></li>
        <li><a href="/welcome-docs/artikel/relasi-akademik">Relasi Siswa–Kelas–Mapel</a></li>
    </ul>
</section>
