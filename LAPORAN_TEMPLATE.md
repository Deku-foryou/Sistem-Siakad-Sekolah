# Laporan Template Web (Laravel)

## 1. Versi dan Stack

### 1.1 Versi utama (composer.json)
- Laravel Framework: `^12.0`
- PHP: `^8.2`
- Livewire: `^3.7` (`livewire/livewire`)
- Laravel Sanctum: `^4.0`
- Laravel Tinker: `^2.8`

### 1.2 Package pihak ketiga (selain bawaan Laravel)
| Package | Fungsi |
|---|---|
| `barryvdh/laravel-dompdf` | Export data ke PDF |
| `guzzlehttp/guzzle` | HTTP client |
| `jantinnerezo/livewire-alert` | Alert/toast pada komponen Livewire |
| `livewire/livewire` | Komponen reaktif server-side |
| `mews/captcha` | Captcha pada halaman login |
| `opcodesio/log-viewer` | Viewer log aplikasi |
| `phpoffice/phpspreadsheet` | Export data ke Excel |
| `spatie/laravel-activitylog` | Pencatatan aktivitas (audit log) |
| `laravel/sanctum` | Token API (personal access tokens) |

Dev: `fakerphp/faker`, `laravel/pint`, `laravel/sail`, `mockery/mockery`, `nunomaduro/collision`, `phpunit/phpunit`, `spatie/laravel-ignition`.

### 1.3 Frontend
- Styling: Bootstrap/CoreUI (asset offline di `public/css`, `public/asset_offline`), Font Awesome, DM Sans.
- Build tool: Vite (`vite.config.js`, `package.json`) dengan `laravel-vite-plugin`; input `resources/css/app.css` (kosong) dan `resources/js/app.js` (hanya import bootstrap). Asset offline dipakai langsung via `asset()`.
- Tidak ada Tailwind/Bootstrap npm; tidak ada dependency frontend selain `axios`, `vite`, `laravel-vite-plugin`.

## 2. Struktur Folder

### 2.1 `app/`
- `Console/Kernel.php`
- `Exceptions/Handler.php`
- `Http/Controllers/`: `AuthController`, `LoginController`, `UsersController`, `RolesController`, `PermissionsController`, `Model_has_rolesController`, `Role_has_permissionsController`
- `Http/Middleware/`: `Authenticate`, `RedirectIfAuthenticated`, `CheckUserLevel`, `MenuAccessMiddleware`, dll.
- `Http/Traits/FileUpload.php`
- `Livewire/`: 20+ komponen (lihat bagian 6)
- `Livewire/Layouts/Base.php`
- `Models/`: 15 model
- `Providers/`: `AppServiceProvider`, `AuthServiceProvider`, `BroadcastServiceProvider`, `EventServiceProvider`, `RouteServiceProvider`
- `Traits/`: `FileUpload`, `PaginationLov`, `PaginationTrait`

### 2.2 `app/Livewire`
Komponen: `ActivitylogComponent`, `AppmenucatComponent`, `AppmenunewComponent`, `DashboardComponent`, `DocscatComponent`, `DocsComponent`, `DocsContentComponent`, `DocsCreateComponent`, `DocsEditComponent`, `HomedocsComponent`, `ModelHasRolesComponent`, `PermissionsComponent`, `RolecatComponent`, `RoleComponent`, `RoleHasPermissionsComponent`, `RolesComponent`, `UsermenuComponent`, `UsersComponent`, `Layouts/Base`.

### 2.3 `app/Models`
`Appmenucat`, `Appmenunew`, `Docs`, `Docscat`, `Model_has_roles`, `Permissions`, `Role`, `Rolecat`, `Roles`, `Role_has_permissions`, `User`, `Usermenu`.

### 2.4 `resources/views`
- Layout: `layout/mainlayout.blade.php`, `layout/docslayout.blade.php`, `layout/pdf_layout.blade.php`
- Auth: `login.blade.php`, `setting.blade.php`
- Partials: `assets/sidebar.blade.php`, `assets/navbar.blade.php`, `assets/scripts.blade.php`, `assets/style.blade.php`, `assets/docs_sidebar.blade.php`
- `livewire/*.blade.php` (satu view per komponen + varian `-lov`)
- `livewire/frontend/home-component.blade.php`
- `errors/403.blade.php`, `errors/404.blade.php`

Layout utama (`mainlayout.blade.php`): HTML5 + CoreUI/Bootstrap offline, sidebar + navbar, slot content, Livewire styles/scripts.

### 2.5 `routes/`
`web.php`, `api.php`, `console.php`.

### 2.6 Pola penamaan dan konvensi
- Komponen Livewire: `NamaComponent` (PascalCase) + view `nama-component.blade.php` (kebab-case).
- Model: singular PascalCase; tabel: lowercase tanpa pluralisasi (`appmenucat`, `appmenunew`, `usermenu`).
- Primary key: `id` + nama tabel (`idappmenucat`, `idappmenunew`, `idusermenu`, `iddocs`, dll.).
- Foreign key: `namatabel_idprimarykey` (mis. `appmenucat_idappmenucat`).
- Komponen CRUD hasil generator: properti publik kolom tabel + `search`, `perPage`, `tglstart`, `tglend`, method `storeX`, `editX`, `updateX`, `deleteConfirmationX`, `deleteX`, `exportToExcel`, `exportToPdf`.
- Scope `search()` pada model untuk pencarian global.

## 3. Routing

### 3.1 Isi `routes/web.php` (lengkap)
```php
<?php

use App\Models\Usermenu;

use App\Livewire\RoleComponent;
use App\Livewire\UsersComponent;
use App\Livewire\RolecatComponent;
use App\Livewire\DocsEditComponent;
use App\Livewire\HomedocsComponent;
use App\Livewire\UsermenuComponent;
use App\Livewire\DashboardComponent;
use App\Livewire\AppmenucatComponent;
use App\Livewire\AppmenunewComponent;
use App\Livewire\DocsCreateComponent;
use Illuminate\Support\Facades\Route;
use App\Livewire\DocsContentComponent;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoginController;

use App\Livewire\DocsComponent;
use App\Livewire\DocscatComponent;

use Mews\Captcha\Facades\Captcha;

/*
|--------------------------------------------------------------------------

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


Route::middleware([auth::class])->group(
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

        foreach ($appMenu as $datas) {
            $componentClass = 'App\\Livewire\\' . ucfirst($datas->appmenunew->url) . 'Component';

            if (class_exists($componentClass)) {
                Route::get($datas->appmenunew->url, $componentClass)->name($datas->appmenunew->url)->middleware('menu.access');
            }
        }

        Route::middleware(['check.level:superadmin'])->group(function () {
            Route::get('appmenunew', AppmenunewComponent::class);
            Route::get('appmenucat', AppmenucatComponent::class);

            Route::get('rolecat', RolecatComponent::class);
            Route::get('role/{id}', RoleComponent::class);
        });


        Route::middleware(['check.level:admin,superadmin'])->group(function () {
            Route::get('users', UsersComponent::class);
            Route::get('usermenu/{id}', UsermenuComponent::class)->name('usermenu');

        });


        // Non Spatie ---------------------------------------------------------------------
    }
);
```

### 3.2 Daftar route (ringkasan)
| URL | Method | Handler | Middleware |
|---|---|---|---|
| `/` | GET | Closure view login | - |
| `login` | GET | `LoginController@index` | guest |
| `login` | POST | `LoginController@authenticate` | guest |
| `logout` | POST | `LoginController@logout` | auth |
| `refresh-captcha` | GET | Closure captcha_src | - |
| `/setting` | GET | `AuthController@index` | auth |
| `/profil/{id}` | POST | `AuthController@update` | auth |
| `dashboard` | GET | `DashboardComponent` | auth |
| `welcome-docs` | GET | `HomedocsComponent` | auth |
| `docscat` | GET | `DocscatComponent` | auth |
| `docs` | GET | `DocsComponent` | auth |
| `docs-create` | GET | `DocsCreateComponent` | auth |
| `docs-edit/{id}` | GET | `DocsEditComponent` | auth |
| `/docs/{id}` | GET | `DocsContentComponent` | auth |
| `/{url}` (dari tabel usermenu) | GET | `App\Livewire\{Url}Component` | auth + menu.access |
| `appmenunew` | GET | `AppmenunewComponent` | auth + check.level:superadmin |
| `appmenucat` | GET | `AppmenucatComponent` | auth + check.level:superadmin |
| `rolecat` | GET | `RolecatComponent` | auth + check.level:superadmin |
| `role/{id}` | GET | `RoleComponent` | auth + check.level:superadmin |
| `users` | GET | `UsersComponent` | auth + check.level:admin,superadmin |
| `usermenu/{id}` | GET | `UsermenuComponent` | auth + check.level:admin,superadmin |
| `api/user` | GET | Closure | auth:sanctum |

Catatan: route dinamis dibuat dari seluruh baris `usermenu` (tanpa filter user), sehingga URL menu yang pernah ditambahkan ke user manapun menjadi route global.

## 4. Autentikasi dan Otorisasi

### 4.1 Login
- `LoginController::index()` menampilkan `login.blade.php`.
- `authenticate()` memvalidasi email, password, dan captcha (`captcha|captcha`), lalu `Auth::attempt`. Sukses: regenerasi session, redirect ke `dashboard`. Gagal: redirect back dengan error.
- Captcha Mews: route `refresh-captcha` mengembalikan `captcha_src('flat')`.
- Logout: `Auth::logout`, invalidate session, regenerate token, redirect ke login.

### 4.2 Profil
- `AuthController::index()` menampilkan `setting.blade.php` dengan data user login.
- `update()` mengubah nama/email, verifikasi password lama, dan ganti password jika diisi + konfirmasi cocok.

### 4.3 Role dan menu dinamis
Tabel terlibat:
- `users`: memiliki kolom `level` (superadmin/admin/user) + email/password.
- `appmenucat`: kategori menu (`idappmenucat`, `appmenucat`, `urut`).
- `appmenunew`: item menu (`idappmenunew`, `appmenunew`, `url`, `icon`, `urut`, `appmenucat_idappmenucat`).
- `rolecat`: "paket menu" (`idrolecat`, `rolecat`).
- `role`: relasi paket menu ↔ item menu (`rolecat_idrolecat`, `appmenunew_idappmenunew`).
- `usermenu`: relasi user ↔ item menu (`user_iduser`, `appmenunew_idappmenunew`).

Relasi model:
- `Appmenucat hasMany Appmenunew`
- `Appmenunew belongsTo Appmenucat`
- `Role belongsTo Rolecat` dan `belongsTo Appmenunew`
- `Usermenu belongsTo Appmenunew` dan `belongsTo User`
- `User hasMany Usermenu` dan `hasMany Model_has_roles`
- `Rolecat`/`Roles`/`Permissions` (tabel `roles`, `permissions`, `role_has_permissions`, `model_has_roles`) juga ada modelnya, tetapi tidak terhubung ke tabel menu di atas.

Cara render menu:
- `AppServiceProvider` view composer untuk `assets.sidebar` mengambil `Appmenucat` (semua kategori) dan `Usermenu` milik user login (join appmenunew), diurutkan `urut`.
- Sidebar menampilkan kategori; di dalamnya item menu milik user. Superadmin juga melihat menu tetap: Paket Menu, Kategori Menu, Kelola Menu, Users.
- `MenuAccessMiddleware` mengecek apakah user memiliki `usermenu` dengan `appmenunew.url` sama dengan nama route yang diakses; jika tidak, abort 404.

Proteksi level:
- `CheckUserLevel` memeriksa `Auth::user()->level` terhadap parameter middleware (`superadmin`, `admin,superadmin`), abort 404 jika tidak sesuai.

Middleware yang dipakai:
- `auth` (Authenticate) untuk seluruh grup.
- `guest` untuk login.
- `menu.access` (MenuAccessMiddleware) untuk route dinamis.
- `check.level:...` (CheckUserLevel) untuk menu manajemen.

## 5. Database

### 5.1 Migration
Hanya 3 migration, semuanya untuk tabel `activity_log` (Spatie activitylog):
- `2026_05_05_082415_create_activity_log_table.php`
- `2026_05_05_082416_add_event_column_to_activity_log_table.php`
- `2026_05_05_082417_add_batch_uuid_column_to_activity_log_table.php`

Tabel lain dibuat dari `templatedb.sql` (dump MySQL), bukan migration.

### 5.2 Tabel dan kolom (dari templatedb.sql)
- `activity_log`: id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, event, batch_uuid, created_at, updated_at.
- `appmenucat`: idappmenucat, appmenucat, urut, created_at, updated_at.
- `appmenunew`: idappmenunew, appmenunew, url, icon, urut, appmenucat_idappmenucat, created_at, updated_at, created_by, updated_by.
- `docs`: iddocs, title, content, urut, docscat_iddocscat, created_at, updated_at.
- `docscat`: iddocscat, docscat, urut, created_at, updated_at.
- `failed_jobs`: id, uuid, connection, queue, payload, exception, failed_at.
- `migrations`: id, migration, batch.
- `password_reset_tokens`: email, token, created_at.
- `personal_access_tokens`: id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at.
- `role`: idrole, rolecat_idrolecat, appmenunew_idappmenunew, created_at, updated_at.
- `rolecat`: idrolecat, rolecat, created_at, updated_at.
- `usermenu`: idusermenu, appmenunew_idappmenunew, user_iduser, created_at, updated_at.
- `users`: id, name, email, level, email_verified_at, password, remember_token, created_at, updated_at.

Catatan: model `Roles`, `Permissions`, `Role_has_permissions`, `Model_has_roles` merujuk tabel `roles`, `permissions`, `role_has_permissions`, `model_has_roles` yang tidak ada di dump (fitur Spatie-like belum tersedia). Seeder `RakSeeder` merujuk model `App\Models\Rak` yang tidak ada.

### 5.3 Relasi antar model
- `Appmenucat hasMany Appmenunew`
- `Appmenunew belongsTo Appmenucat`
- `Docscat hasMany Docs`
- `Docs belongsTo Docscat`
- `Role belongsTo Rolecat`, `Role belongsTo Appmenunew`
- `Usermenu belongsTo Appmenunew`, `Usermenu belongsTo User`
- `User hasMany Usermenu`, `User hasMany Model_has_roles`
- `Roles hasMany Role_has_permissions`, `Roles hasMany Model_has_roles`
- `Permissions hasMany Role_has_permissions`
- `Role_has_permissions belongsTo Roles`, `belongsTo Permissions`
- `Model_has_roles belongsTo Roles`, `belongsTo User` (dua alias: `model()`, `users()`)

### 5.4 Seeder
- `DatabaseSeeder`: kosong (tidak memanggil seeder lain).
- `RakSeeder`: membuat 899 data `Rak` (model tidak ada) → tidak bisa dijalankan.

Data awal tersedia di `templatedb.sql`: 3 user (admin/superadmin/budi), kategori menu, item menu, paket menu, usermenu, docs category, activity log, personal access token (token sensitif—jangan dipakai ulang).

## 6. Komponen Livewire

Ringkasan per komponen (fungsi, properti publik utama, method utama, view):

1. `ActivitylogComponent` — viewer/audit log activity_log dengan filter user/aksi/menu/tanggal dan hapus per bulan. Props: selectedUser, selectedAction, selectedMenu, tglstart, tglend, openRow, selectedBulan, dataBulan. Methods: mount, toggle, deleteLog, render. View: `livewire/activitylog-component.blade.php`.

2. `AppmenucatComponent` — CRUD kategori menu (Appmenucat). Props: idappmenucat, appmenucat, urut, appmenucatName, search/searchlov, tglstart/tglend, perPage. Methods: store/edit/update/delete, export Excel/PDF, LOV. View: `livewire/appmenucat-component.blade.php`.

3. `AppmenunewComponent` — CRUD item menu (Appmenunew) + pilih icon. Props: idappmenunew, appmenunew, url, icon, urut, appmenucat_idappmenucat, created_by, updated_by, searchIcons. Methods: store/edit/update/delete, selectIcon, export. View: `livewire/appmenunew-component.blade.php`.

4. `DashboardComponent` — dashboard kosong. View: `livewire/dashboard-component.blade.php`.

5. `DocscatComponent` — CRUD kategori dokumentasi. View: `livewire/docscat-component.blade.php`.

6. `DocsComponent` — CRUD dokumen (listing). View: `livewire/docs-component.blade.php`.

7. `DocsCreateComponent` — form upload dokumen PDF. Props: title, content, iddocs. Methods: storeDocs, close. View: `livewire/docs-create-component.blade.php`.

8. `DocsEditComponent` — edit dokumen. Props: id, title, content, iddocs. Methods: mount, updateDocs. View: `livewire/docs-edit-component.blade.php`.

9. `DocsContentComponent` — tampilan detail dokumen. Props: id. Methods: mount, render. View: `livewire/docs-content-component.blade.php`.

10. `HomedocsComponent` — halaman docs depan (kosong). View: `livewire/homedocs-component.blade.php`.

11. `ModelHasRolesComponent` — assign role (Spatie) ke user; setengah jadi. Props: role_id[], model_id, search, perPage, id. Methods: mount, updatingSearch, storeModel_has_roles, render. View: `livewire/model-has-roles-component.blade.php`.

12. `PermissionsComponent` — CRUD tabel permissions (custom). View: `livewire/permissions-component.blade.php`.

13. `RolecatComponent` — CRUD paket menu (Rolecat). View: `livewire/rolecat-component.blade.php`.

14. `RoleComponent` — assign item menu ke paket menu (rolecat). Props: role_id[], idrole, rolecat_idrolecat, appmenunew_idappmenunew, id. Methods: mount, tambahMenuRole, deleteRolemenu, render. View: `livewire/role-component.blade.php`.

15. `RoleHasPermissionsComponent` — assign permission ke role (custom). View: `livewire/role-has-permissions-component.blade.php`.

16. `RolesComponent` — CRUD tabel roles (custom). View: `livewire/roles-component.blade.php`.

17. `UsermenuComponent` — assign menu ke user (satuan/paket rolecat). Props: menu_id[], rolecat_id[], idusermenu, appmenunew_idappmenunew, user_iduser, id. Methods: mount, tambahMenuUser, hapusPaket, deletePaketRole, deleteUsermenu, render. View: `livewire/usermenu-component.blade.php`.

18. `UsersComponent` — CRUD user. Props: id, name, email, email_verified_at, password, remember_token, level. Methods: store/edit/update/delete, export. View: `livewire/users-component.blade.php`.

### 6.1 Contoh pola CRUD lengkap: `AppmenucatComponent`
```php
<?php
namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Appmenucat;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Barryvdh\DomPDF\Facade\Pdf;

class AppmenucatComponent extends Component
{

  use WithPagination;
  use WithFileUploads;
  use LivewireAlert;

  public $idappmenucat, $appmenucat, $urut;
  public $appmenucatName;
  public $searchlov = '';
  public $search = '';
  public $tglstart = '';
  public $tglend = '';
  public $perPage = 10;

  public function updatingSearch()
  {
    $this->resetPage();
  }

  public function updatingSearchlov()
  {
    $this->resetPage();
    $this->resetPage('pageLOV');
  }

  public function updatingPerpage()
  {
    $this->resetPage();
    $this->resetPage('pageLOV');
  }

  public function storeAppmenucat()
  {
    $this->validate([
      'appmenucat' => 'required ',
      'urut' => 'required|unique:appmenucat,urut|numeric',
    ]);

    $appmenucat = new Appmenucat();
    $appmenucat->appmenucat = $this->appmenucat;
    $appmenucat->urut = $this->urut;
    $appmenucat->save();
    $this->reset();

    $this->resetPage('pageLOV');

    $this->alert('success', 'New appmenucat has been added successfully');

    $this->dispatch('close-appmenucat-modal');
  }

  public function close()
  {
    $this->reset();
    $this->resetValidation();
    $this->dispatch('close-appmenucat-modal');
  }

  public function editAppmenucat($idappmenucat)
  {
    $appmenucat = Appmenucat::where('idappmenucat', $idappmenucat)->first();

    $this->idappmenucat = $appmenucat->idappmenucat;
    $this->appmenucat = $appmenucat->appmenucat;
    $this->urut = $appmenucat->urut;

    $this->dispatch('show-edit-appmenucat-modal');
  }

  public function updateAppmenucat()
  {
    $this->validate([
      'appmenucat' => 'required ',
    ]);
    $appmenucat = Appmenucat::where('idappmenucat', $this->idappmenucat)->first();
    $appmenucat->idappmenucat = $this->idappmenucat;
    $appmenucat->appmenucat = $this->appmenucat;
    $appmenucat->urut = $this->urut;
    $appmenucat->save();

    $this->reset();

    $this->resetPage('pageLOV');

    $this->alert('success', 'Update data appmenucat has been added successfully');

    $this->dispatch('close-appmenucat-modal');
  }

  public function deleteConfirmationAppmenucat($idappmenucat)
  {
    $this->idappmenucat = $idappmenucat;
    $this->dispatch('show-delete-confirmation-appmenucat-modal');
  }

  public function deleteAppmenucat()
  {
    $appmenucat = Appmenucat::where('idappmenucat', $this->idappmenucat)->first();

    try {
      $appmenucat->delete();
      $this->alert('success', 'Data appmenucat Berhasil Di hapus');
    } catch (\Throwable $th) {
      $this->alert('error', 'Terdapat Data yang terkait, Data Tidak Bisa di Hapus');
    }

    $this->dispatch('close-appmenucat-modal');
    $this->idappmenucat = '';
  }

  public function exportToExcel()
  {
    $queryResult = Appmenucat::latest()
      ->search($this->search)
      ->paginate($this->perPage);
    $data = [['idappmenucat', 'appmenucat', 'urut']];
    foreach ($queryResult as $result) {
      $data[] = [$result->idappmenucat, $result->appmenucat, $result->urut];
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($data, null, 'A1');
    $writer = new Xlsx($spreadsheet);
    $filename = 'exported-appmenucat-data.xlsx';
    $writer->save($filename);
    return response()->download($filename)->deleteFileAfterSend(true);
  }

  public function exportToPdf()
  {
    $headers = ['idappmenucat', 'appmenucat', 'urut'];
    $queryResult = Appmenucat::latest()
      ->search($this->search)
      ->paginate($this->perPage);
    $data = [];
    foreach ($queryResult as $result) {
      $data[] = [$result->idappmenucat, $result->appmenucat, $result->urut];
    }
    $pdf = PDF::loadView('layout.pdf_layout', compact('data', 'headers'));
    $pdf->setPaper('A4', 'portrait');
    return response()->streamDownload(function () use ($pdf) {
      echo $pdf->stream();
    }, 'Appmenucat.pdf');
  }

  public function appmenucatOption($id = null)
  {
    $result = Appmenucat::get();
    $options = '';
    foreach ($result as $row) {
      $options .= '<option value=' . $row->idappmenucat . '';
      if ($id == $row->idappmenucat) {
        $options .= ' selected';
      }
      $options .= '>' . $row->appmenucat . '</option>';
    }
    return $options;
  }

  public function appmenucatLOV($id)
  {
    $appmenucat = Appmenucat::where('idappmenucat', $id)->first();
    $this->appmenucatName = $appmenucat->appmenucat;
    $this->alert('success', 'Terpilih : ' . $appmenucat->appmenucat);
    $this->resetPage('pageLOV');

    $this->dispatch('close-modal-lov');
  }

  public function render()
  {
    $appmenucat = Appmenucat::latest()
      ->search($this->search)
      ->paginate($this->perPage);
    return view('livewire.appmenucat-component', [
      'appmenucats' => $appmenucat
    ]);
  }
}
```

## 7. Fitur yang Sudah Jadi vs Belum

Sudah berfungsi:
- Login/logout dengan captcha.
- Profil & ganti password.
- Dashboard (view kosong).
- Manajemen menu dinamis: kategori menu, item menu, paket menu, assign menu ke user/paket.
- CRUD user (dengan hash password).
- Dokumentasi (kategori + dokumen, upload PDF, tampilan).
- Activity log viewer + hapus per bulan.
- Export Excel/PDF pada komponen CRUD.

Belum/setengah jadi:
- Tidak ada modul sekolah (siswa, kelas, guru, mapel, absensi, nilai, dll.).
- `ModelHasRolesComponent` memakai `Spatie\Permission\Models\Role` dan `syncRoles()` padahal package Spatie Permission tidak terpasang → error saat digunakan.
- Tabel `roles/permissions/role_has_permissions/model_has_roles` tidak ada di database → komponen terkait tidak bisa dipakai.
- `RakSeeder` merujuk model `Rak` yang tidak ada.
- `DocsCreateComponent` tidak mengisi `docscat_iddocscat` dan `urut` yang NOT NULL → insert gagal.
- `DocsEditComponent` mengasumsikan `$this->content` adalah file upload padahal di `mount` diisi string nama file → `updateDocs` error saat memanggil `extension()`.
- `HomedocsComponent`, `DashboardComponent`, view `frontend/home-component` kosong.
- Route dinamis bergantung data `usermenu`; jika tabel kosong, menu tidak muncul. Juga route dibuat dari semua usermenu (bukan per user).
- `AppServiceProvider` meng-import `App\Models\Appmenu` yang tidak ada (tidak dipakai, tapi berpotensi membingungkan).
- Export Excel/PDF mengekspor hash password user (risiko keamanan).

## 8. Catatan

- Kredensial/token pada `templatedb.sql` (personal_access_tokens) sebaiknya tidak dipakai ulang; ganti token dan password default.
- `users.level` dipakai sebagai role sederhana, sedangkan komponen Spatie Permission (Roles/Permissions) tidak terpasang—pilih salah satu mekanisme otorisasi agar konsisten.
- Sidebar masih menyisipkan menu tetap untuk superadmin/admin di Blade, tidak sepenuhnya dinamis.
- `MenuAccessMiddleware` membandingkan `route name` dengan `appmenunew.url`; pastikan keduanya sinkron.
- `CheckUserLevel` abort 404 (bukan 403) untuk akses tidak berhak—pertimbangkan 403 agar jelas.
- Banyak komponen hasil generator memiliki validasi longgar (`'required '` dengan spasi) dan kolom `search()` yang mencari semua kolom termasuk password.
- `ActivitylogComponent` cache filter dropdown 1 jam → data baru tidak langsung muncul pada dropdown.
- Vite dikonfigurasi tetapi asset frontend utama offline; pertimbangkan menyatukan pipeline build.
- Sebelum dipakai untuk sistem sekolah, perlu modul domain (siswa, kelas, guru, mapel, jadwal, nilai, absensi) dan pengujian menyeluruh.
- Jangan sertakan `.env` atau kredensial dalam laporan/commit.
```

, "path": "C:\\project-reyhan\\template web\\LAPORAN_TEMPLATE.md"}