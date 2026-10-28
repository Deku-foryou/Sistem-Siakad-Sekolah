<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImportDownloadController;
use App\Http\Controllers\LoginController;
use App\Livewire\AppmenucatComponent;
use App\Livewire\AppmenunewComponent;
use App\Livewire\DashboardComponent;
use App\Livewire\DocscatComponent;
use App\Livewire\DocsComponent;
use App\Livewire\DocsContentComponent;
use App\Livewire\DocsCreateComponent;
use App\Livewire\DocsEditComponent;
use App\Livewire\GuruComponent;
use App\Livewire\HomedocsComponent;
use App\Livewire\JadwalComponent;
use App\Livewire\BuktiPublikasiComponent;
use App\Livewire\JampelajaranComponent;
use App\Livewire\KelasComponent;
use App\Livewire\KelassiswaComponent;
use App\Livewire\MapelComponent;
use App\Livewire\PengampuComponent;
use App\Livewire\PolajamComponent;
use App\Livewire\RolecatComponent;
use App\Livewire\RoleComponent;
use App\Livewire\SemesterComponent;
use App\Livewire\SiswaComponent;
use App\Livewire\TahunajaranComponent;
use App\Livewire\UsermenuComponent;
use App\Livewire\UsersComponent;
use App\Models\Usermenu;
use Illuminate\Support\Facades\Route;
use Mews\Captcha\Facades\Captcha;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

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
    function () {

        Route::get('/setting', [AuthController::class, 'index']);
        Route::post('/profil/{id}', [AuthController::class, 'update'])->name('profil.edit');

        Route::get('dashboard', DashboardComponent::class);

        Route::get('welcome-docs', HomedocsComponent::class);

        Route::get('docscat', DocscatComponent::class);
        Route::get('docs', DocsComponent::class);

        Route::get('docs-create', DocsCreateComponent::class);
        Route::get('docs-edit/{id}', DocsEditComponent::class);
        Route::get('/docs/{id}', DocsContentComponent::class);

        // Non Spatie--------------------------------------------------------------------

        // Daftar url unik dari appmenunew (tanpa join usermenu/user)
        $appMenuUrls = \App\Models\Appmenunew::query()
            ->whereNotNull('url')
            ->pluck('url')
            ->unique()
            ->values();

        // URL yang sudah didaftarkan eksplisit di bawah tidak boleh didaftarkan ulang
        // oleh loop dinamis, agar proteksi check.level tidak terlewati.
        $explicitUrls = ['appmenunew', 'appmenucat', 'rolecat', 'role', 'users', 'usermenu', 'tahunajaran', 'semester', 'guru', 'siswa', 'mapel', 'kelas', 'kelassiswa', 'polajam', 'jampelajaran', 'pengampu', 'jadwal', 'jurusan'];

        foreach ($appMenuUrls as $url) {
            if (in_array($url, $explicitUrls, true)) {
                continue;
            }

            $componentClass = 'App\\Livewire\\'.ucfirst($url).'Component';

            if (class_exists($componentClass)) {
                Route::get($url, $componentClass)->name($url)->middleware('menu.access');
            }
        }

        Route::middleware(['check.level:superadmin'])->group(function () {
            Route::get('appmenunew', AppmenunewComponent::class);
            Route::get('appmenucat', AppmenucatComponent::class);

            Route::get('rolecat', RolecatComponent::class);
            Route::get('role/{id}', RoleComponent::class);

            Route::get('tahunajaran', TahunajaranComponent::class);
            Route::get('semester', SemesterComponent::class);
            Route::get('guru', GuruComponent::class);
            Route::get('siswa', SiswaComponent::class);
            Route::get('mapel', MapelComponent::class);
            Route::get('kelas', KelasComponent::class);
            Route::get('kelassiswa', KelassiswaComponent::class);
            Route::get('polajam', PolajamComponent::class)->name('polajam');
            Route::get('jampelajaran', JampelajaranComponent::class)->name('jampelajaran');
            Route::get('pengampu', PengampuComponent::class)->name('pengampu');
            Route::get('jadwal', JadwalComponent::class)->name('jadwal');
        Route::get('jurusan', App\Livewire\JurusanComponent::class)->name('jurusan');
            Route::get('bukti-publikasi/{id}', BuktiPublikasiComponent::class)->name('bukti-publikasi');
            Route::get('import/template/{jenis}', [ImportDownloadController::class, 'template'])
                ->whereIn('jenis', ['siswa', 'guru', 'mapel', 'kelas', 'penempatan'])
                ->name('import.template');
            Route::get('import/gagal/{uuid}', [ImportDownloadController::class, 'gagal'])
                ->where('uuid', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-8][0-9a-fA-F]{3}-[89aAbB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}')
                ->name('import.gagal');
        });

        Route::middleware(['check.level:superadmin'])->group(function () {
            Route::get('users', UsersComponent::class);
            Route::get('usermenu/{id}', UsermenuComponent::class)->name('usermenu');

        });

        // Non Spatie ---------------------------------------------------------------------
    }
);
