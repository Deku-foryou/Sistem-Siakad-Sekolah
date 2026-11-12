<?php

namespace App\Providers;

use App\Models\Docs;
use App\Models\Docscat;

use App\Models\Appmenu;
use App\Models\Usermenu;
use App\Models\Appmenucat;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        // hanya bisa di akses pada sidebar view
        view()->composer(['assets.sidebar'], function ($view) {

            $appMenuCat = Appmenucat::orderBy('urut', 'asc')->get();

            // Mengambil semua data dari Appmenu
            $appMenu = Usermenu::with(['appmenunew', 'user'])
                ->leftJoin('appmenunew', 'usermenu.appmenunew_idappmenunew', '=', 'appmenunew.idappmenunew')
                ->select('usermenu.*', 'appmenunew.urut')
                ->where('user_iduser', Auth::user()->id ?? null)
                ->orderBy('appmenunew.urut', 'asc')
                ->get();

            $view->with('appMenu', $appMenu);
            $view->with('appMenuCat', $appMenuCat);

        });

        // hanya bisa di akses pada sidebar view
        // Tahan kegagalan agar halaman non-Docs tidak pernah 500
        // akibat data/tabel Docs yang belum siap.
        view()->composer(['assets.docs_sidebar'], function ($view) {
            try {
                $docsCat = \Illuminate\Support\Facades\Schema::hasTable('docscat')
                    ? Docscat::orderBy('urut', 'asc')->get()
                    : collect();
                $sidebarDocs = \Illuminate\Support\Facades\Schema::hasTable('docs')
                    ? Docs::orderBy('urut', 'asc')->get()
                    : collect();
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::warning('docs_sidebar: gagal memuat kategori/dokumen', [
                    'exception' => get_class($exception),
                ]);
                $docsCat = collect();
                $sidebarDocs = collect();
            }

            $view->with('docsCat', $docsCat);
            $view->with('sidebarDocs', $sidebarDocs);
        });


    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

    }
}