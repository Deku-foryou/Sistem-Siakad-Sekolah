<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoginController;
use App\Livewire\AppmenucatComponent;
use App\Livewire\AppmenunewComponent;
use App\Livewire\DashboardComponent;
use App\Livewire\DocscatComponent;
use App\Livewire\DocsComponent;
use App\Livewire\DocsContentComponent;
use App\Livewire\DocsCreateComponent;
use App\Livewire\DocsEditComponent;
use App\Livewire\HomedocsComponent;
use App\Livewire\RolecatComponent;
use App\Livewire\RoleComponent;
use App\Livewire\SemesterComponent;
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

Route::middleware([auth::class, 'force.password'])->group(
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

        // Mengambil semua data dari Appmenu
        $appMenu = Usermenu::with(['appmenunew', 'user'])->get();

        // URL yang sudah didaftarkan eksplisit di bawah tidak boleh didaftarkan ulang
        // oleh loop dinamis, agar proteksi check.level tidak terlewati.
        $explicitUrls = ['appmenunew', 'appmenucat', 'rolecat', 'role', 'users', 'usermenu', 'tahunajaran', 'semester'];

        foreach ($appMenu as $datas) {
            if (in_array($datas->appmenunew->url, $explicitUrls, true)) {
                continue;
            }

            $componentClass = 'App\\Livewire\\'.ucfirst($datas->appmenunew->url).'Component';

            if (class_exists($componentClass)) {
                Route::get($datas->appmenunew->url, $componentClass)->name($datas->appmenunew->url)->middleware('menu.access');
            }
        }

        Route::middleware(['check.level:superadmin'])->group(function () {
            Route::get('appmenunew', AppmenunewComponent::class);
            Route::get('appmenucat', AppmenucatComponent::class);

            Route::get('rolecat', RolecatComponent::class);
            Route::get('role/{id}', RoleComponent::class);

            Route::get('tahunajaran', TahunajaranComponent::class);
            Route::get('semester', SemesterComponent::class);
        });

        Route::middleware(['check.level:superadmin'])->group(function () {
            Route::get('users', UsersComponent::class);
            Route::get('usermenu/{id}', UsermenuComponent::class)->name('usermenu');

        });

        // Non Spatie ---------------------------------------------------------------------
    }
);
