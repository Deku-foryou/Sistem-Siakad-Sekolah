<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportDownloadController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\PembayaranManualDownloadController;
use App\Http\Controllers\RefundDokumenDownloadController;
use App\Http\Controllers\RekonsiliasiExportController;
use App\Livewire\AbsensiHarianComponent;
use App\Livewire\AppmenucatComponent;
use App\Livewire\AppmenunewComponent;
use App\Livewire\BayarTagihanComponent;
use App\Livewire\BeasiswaComponent;
use App\Livewire\BuktiPublikasiComponent;
use App\Livewire\DashboardComponent;
use App\Livewire\DashboardKeuanganComponent;
use App\Livewire\DataTagihanComponent;
use App\Livewire\DendaPembebasanComponent;
use App\Livewire\DocscatComponent;
use App\Livewire\DocsComponent;
use App\Livewire\DocsContentComponent;
use App\Livewire\DocsCreateComponent;
use App\Livewire\DocsEditComponent;
use App\Livewire\FinalisasiRaporComponent;
use App\Livewire\GuruComponent;
use App\Livewire\HomedocsComponent;
use App\Livewire\InputNilaiComponent;
use App\Livewire\KelasComponent;
use App\Livewire\KelassiswaComponent;
use App\Livewire\KktpMapelComponent;
use App\Livewire\KomponenNilaiComponent;
use App\Livewire\KoreksiNilaiComponent;
use App\Livewire\MapelComponent;
use App\Livewire\NotifikasiComponent;
use App\Livewire\PembayaranManualComponent;
use App\Livewire\PengampuComponent;
use App\Livewire\PenugasanMengajarComponent;
use App\Livewire\RaporSiswaComponent;
use App\Livewire\RefundKoreksiComponent;
use App\Livewire\RekonsiliasiKeuanganComponent;
use App\Livewire\RolecatComponent;
use App\Livewire\RoleComponent;
use App\Livewire\SiswaComponent;
use App\Livewire\TahunajaranComponent;
use App\Livewire\TarifComponent;
use App\Livewire\TransaksiPembayaranComponent;
use App\Livewire\UsersComponent;
use App\Livewire\WaliAkunComponent;
use App\Livewire\WaliBerandaComponent;
use App\Livewire\WaliKelasComponent;
use App\Models\Appmenunew;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mews\Captcha\Facades\Captcha;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
*/

// Ambil daftar URL appmenunew - hanya jika tabel sudah ada (hindari error di migrate:fresh)
$appMenuUrls = [];
if (Schema::hasTable('appmenunew')) {
    $appMenuUrls = Appmenunew::query()
        ->whereNotNull('url')
        ->pluck('url')
        ->unique()
        ->values();
}

// URL yang sudah didaftarkan eksplisit di bawah tidak boleh didaftarkan ulang
// oleh loop dinamis, agar proteksi check.level tidak terlewati.
$explicitUrls = ['appmenunew', 'appmenucat', 'rolecat', 'role', 'users', 'usermenu', 'tahunajaran', 'semester', 'guru', 'siswa', 'mapel', 'kelas', 'kelassiswa', 'pengampu', 'jurusan', 'jadwalkelas', 'jamhari', 'jampelajaran', 'berandasiswa', 'jadwalsiswa', 'absensi-siswa', 'profil-siswa', 'pengumuman-siswa', 'penugasanmengajar', 'komponennilai', 'kktpmapel', 'inputnilai', 'finalisasirapor', 'koreksinilai', 'raporwalikelas', 'raporsiswa', 'notifikasi', 'wali-akun', 'wali-beranda', 'wali-antrian-verifikasi', 'wali-relasi', 'wali-akademik', 'berandaguru', 'jadwalguru', 'dashboardkeuangan', 'datatagihan', 'tarif', 'beasiswa', 'transaksipembayaran', 'bayartagihan', 'bayar-publik', 'pembayaranmanual', 'rekonsiliasikeuangan', 'refundkoreksi', 'pembebasandenda'];

Route::get('/', function () {
    return view('login');
});

Route::get('login', [LoginController::class, 'index'])->name('login')->middleware('guest');
Route::post('login', [LoginController::class, 'authenticate'])->middleware('guest');
Route::post('logout', [LoginController::class, 'logout'])->middleware('auth');

// captcha
Route::get('refresh-captcha', function () {
    return response()->json(['captcha' => captcha_src('flat')]);
});

Route::middleware([auth::class, 'cek.akun', 'force.password'])->group(
    function () use ($appMenuUrls, $explicitUrls) {

        Route::get('/setting', [AuthController::class, 'index']);
        Route::post('/profil/{id}', [AuthController::class, 'update'])->name('profil.edit');

        Route::get('dashboard', DashboardComponent::class)
            ->middleware('check.level:superadmin,admin');

        Route::get('welcome-docs', HomedocsComponent::class)->middleware('check.level:superadmin,admin,guru,siswa,wali')->name('welcome-docs');
        Route::get('welcome-docs/artikel/{slug}', HomedocsComponent::class)->middleware('check.level:superadmin,admin,guru,siswa,wali')->name('welcome-docs.artikel');

        // Docs: membaca terbuka untuk seluruh role pengguna aplikasi; pengelolaan (create/edit) khusus superadmin & admin.
        Route::get('docscat', DocscatComponent::class)->middleware('check.level:superadmin,admin,guru,siswa,wali');

        Route::get('docs', DocsComponent::class)->middleware('check.level:superadmin,admin,guru,siswa,wali');

        Route::get('docs-create', DocsCreateComponent::class)->middleware('check.level:superadmin,admin');
        Route::get('docs-edit/{id}', DocsEditComponent::class)->middleware('check.level:superadmin,admin');
        Route::get('/docs/{id}', DocsContentComponent::class)->middleware('check.level:superadmin,admin,guru,siswa,wali');

        // Non Spatie--------------------------------------------------------------------

        // Guru & Siswa beranda routes (role-specific dashboard)
        Route::middleware(['check.level:guru'])->group(
            function () {
                Route::get('berandaguru', App\Livewire\BerandaguruComponent::class)->name('berandaguru');
            }
        );

        // Notifikasi: guru, siswa & wali (kanal database). Identitas pemilik dari akun login.
        Route::middleware(['check.level:guru,siswa,wali', 'menu.access'])->group(
            function () {
                Route::get('notifikasi', NotifikasiComponent::class)->name('notifikasi');
            }
        );

        Route::middleware(['check.level:siswa', 'menu.access'])->group(
            function () {
                Route::get('berandasiswa', App\Livewire\BerandasiswaComponent::class)->name('berandasiswa');
                Route::get('jadwalsiswa', App\Livewire\JadwalSiswaComponent::class)->name('jadwalsiswa');
                Route::get('absensi-siswa', App\Livewire\HistoriAbsensiSiswaComponent::class)->name('absensi-siswa');
                Route::get('profil-siswa', App\Livewire\ProfilSiswaComponent::class)->name('profil-siswa');
                Route::get('pengumuman-siswa', App\Livewire\PengumumanSiswaComponent::class)->name('pengumuman-siswa');
                Route::get('pengumuman-siswa/{id}', App\Livewire\PengumumanSiswaComponent::class)->name('pengumuman-siswa.detail');
                Route::get('raporsiswa', RaporSiswaComponent::class)->name('raporsiswa');
            }
        );

        // Guru routes (guru & superadmin)
        Route::middleware(['check.level:guru,superadmin'])->group(
            function () {
                Route::get('absensi-harian', AbsensiHarianComponent::class)->name('absensi-harian');
                Route::get('jadwalguru', App\Livewire\JadwalGuruComponent::class)->name('jadwalguru');
            }
        );

        // Absensi Per Jam: khusus guru (belum ada fitur superadmin pada tahap ini)
        Route::middleware(['check.level:guru'])->group(
            function () {
                Route::get('absensi-perjam', App\Livewire\AbsensiPerjamComponent::class)->name('absensi-perjam');
            }
        );

        // Input nilai: khusus guru, otorisasi penugasan ditegakkan di service.
        Route::middleware(['check.level:guru'])->group(
            function () {
                Route::get('inputnilai', InputNilaiComponent::class)->name('inputnilai');
            }
        );

        // Koreksi nilai final: superadmin, melalui jalur koreksi terkendali.
        Route::middleware(['check.level:superadmin'])->group(
            function () {
                Route::get('koreksinilai', KoreksiNilaiComponent::class)->name('koreksinilai');
            }
        );

        // Finalisasi rapor: wali kelas (guru) atau superadmin; service memeriksa kepemilikan kelas.
        Route::middleware(['check.level:guru,superadmin'])->group(
            function () {
                Route::get('finalisasirapor', FinalisasiRaporComponent::class)->name('finalisasirapor');
                Route::get('raporwalikelas', WaliKelasComponent::class)->name('raporwalikelas');
            }
        );

        // Kelola Akun Wali: superadmin & admin sekolah yang memegang menu wali-akun (T1c).
        Route::middleware(['check.level:superadmin,admin', 'menu.access'])->group(
            function () {
                Route::get('wali-akun', WaliAkunComponent::class)->name('wali-akun');
                Route::get('wali-antrian-verifikasi', \App\Livewire\WaliAntrianVerifikasiComponent::class)->name('wali-antrian-verifikasi');
            }
        );

        // Beranda wali (status akun — bukan akademik): khusus level wali.
        Route::middleware(['check.level:wali', 'menu.access'])->group(
            function () {
                Route::get('wali-beranda', WaliBerandaComponent::class)->name('wali-beranda');
                Route::get('wali-relasi', \App\Livewire\WaliRelasiComponent::class)->name('wali-relasi');
                Route::get('wali-akademik', \App\Livewire\WaliAkademikComponent::class)->name('wali-akademik');
            }
        );

        // Bayar Tagihan: siswa (milik sendiri) & wali (siswa relasi verified + aktif)
        Route::middleware(['check.level:siswa,wali', 'menu.access'])->group(
            function () {
                Route::get('bayartagihan', BayarTagihanComponent::class)->name('bayartagihan');
            }
        );

        // Daftar url unik dari appmenunew (tanpa join usermenu/user)
        foreach ($appMenuUrls as $url) {
            if (in_array($url, $explicitUrls, true)) {
                continue;
            }

            $componentClass = 'App\\Livewire\\'.ucfirst($url).'Component';

            if (class_exists($componentClass)) {
                Route::get($url, $componentClass)->name($url)->middleware('menu.access');
            }
        }

        // Keuangan: superadmin + admin (operasional)
        Route::middleware(['check.level:superadmin,admin'])->group(
            function () {
                Route::get('dashboardkeuangan', DashboardKeuanganComponent::class)->name('dashboardkeuangan');
                Route::get('datatagihan', DataTagihanComponent::class)->name('datatagihan');
                Route::get('tarif', TarifComponent::class)->name('tarif');
                Route::get('beasiswa', BeasiswaComponent::class)->name('beasiswa');
                Route::get('transaksipembayaran', TransaksiPembayaranComponent::class)->name('transaksipembayaran');
                Route::get('pembayaranmanual', PembayaranManualComponent::class)->name('pembayaranmanual');
                Route::get('rekonsiliasikeuangan', RekonsiliasiKeuanganComponent::class)->name('rekonsiliasikeuangan');
                Route::get('rekonsiliasikeuangan/export/{id}/ringkasan', [RekonsiliasiExportController::class, 'ringkasan'])->whereNumber('id')->name('rekonsiliasikeuangan.export.ringkasan');
                Route::get('rekonsiliasikeuangan/export/{id}/temuan', [RekonsiliasiExportController::class, 'temuan'])->whereNumber('id')->name('rekonsiliasikeuangan.export.temuan');
                Route::get('refundkoreksi', RefundKoreksiComponent::class)->name('refundkoreksi');
                Route::get('pembebasandenda', DendaPembebasanComponent::class)->name('pembebasandenda');
            }
        );

        // Superadmin only routes
        Route::middleware(['check.level:superadmin'])->group(
            function () {
                Route::get('appmenunew', AppmenunewComponent::class);
                Route::get('appmenucat', AppmenucatComponent::class);

                Route::get('rolecat', RolecatComponent::class);
                Route::get('role/{id}', RoleComponent::class);

                Route::get('tahunajaran', TahunajaranComponent::class);
                Route::redirect('semester', 'tahunajaran');
                Route::get('guru', GuruComponent::class);
                Route::get('siswa', SiswaComponent::class);
                Route::get('mapel', MapelComponent::class);
                Route::get('kelas', KelasComponent::class)->name('kelas');
                Route::get('kelassiswa', KelassiswaComponent::class);
                Route::get('jamhari', App\Livewire\JamhariComponent::class)->name('jamhari');
                // Route lama dipertahankan sebagai pengalihan agar tautan/bookmark tidak rusak.
                Route::redirect('jampelajaran', 'jamhari')->name('jampelajaran');
                Route::get('pengampu', PengampuComponent::class)->name('pengampu');
                Route::get('penugasanmengajar', PenugasanMengajarComponent::class)->name('penugasanmengajar');
                Route::get('komponennilai', KomponenNilaiComponent::class)->name('komponennilai');
                Route::get('kktpmapel', KktpMapelComponent::class)->name('kktpmapel');
                Route::get('jadwalkelas', App\Livewire\JadwalKelasComponent::class)->name('jadwalkelas');
                Route::get('jurusan', App\Livewire\JurusanComponent::class)->name('jurusan');
                Route::get('pengumuman', App\Livewire\PengumumanComponent::class)->name('pengumuman');
                Route::get('bukti-publikasi/{id}', BuktiPublikasiComponent::class)->name('bukti-publikasi');
                Route::get('import/template/{jenis}', [ImportDownloadController::class, 'template'])
                    ->whereIn('jenis', ['siswa', 'guru', 'mapel', 'kelas', 'penempatan'])
                    ->name('import.template');
                Route::get('import/gagal/{uuid}', [ImportDownloadController::class, 'gagal'])
                    ->where('uuid', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-8][0-9a-fA-F]{3}-[89aAbB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}')
                    ->name('import.gagal');
            }
        );

        // Download kuitansi/bukti pembayaran (otorisasi server-side)
        Route::middleware(['auth'])->group(function () {
            Route::get('pembayaran/kuitansi/{idpembayaran}', [PembayaranManualDownloadController::class, 'kuitansi'])->name('pembayaran.kuitansi');
            Route::get('pembayaran/bukti/{idpembayaran}', [PembayaranManualDownloadController::class, 'bukti'])->name('pembayaran.bukti');
            Route::get('refund/dokumen/{id}', [RefundDokumenDownloadController::class, 'dokumen'])->whereNumber('id')->name('refund.dokumen');
        });

        Route::middleware(['check.level:superadmin'])->group(
            function () {
                Route::get('users', UsersComponent::class);
                Route::get('usermenu/{id}', App\Livewire\UsermenuComponent::class)->name('usermenu');
            }
        );

        // Non Spatie ---------------------------------------------------------------------
    }
);

// ==========================================================================
// Publik (tanpa autentikasi)
// ==========================================================================

// Webhook Midtrans (server-to-server). Tanpa auth sesi; keamanan melalui
// signature SHA512 (order_id + status_code + gross_amount + SERVER_KEY) plus
// verifikasi server-side ke API status Midtrans. CSRF dikecualikan di VerifyCsrfToken.
Route::post('webhook/midtrans', MidtransWebhookController::class)->name('webhook.midtrans');

// Pembayaran publik pihak ketiga (capability token). Tanpa akun; tidak
// menampilkan data akademik siswa. Satu token = satu attempt/tagihan.
Route::get('bayar-publik/{token}', App\Livewire\BayarPublikComponent::class)
    ->where('token', '[A-Za-z0-9]{32,64}')
    ->name('bayar-publik');
