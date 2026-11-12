<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // 2B.27-R1 — Pengamanan Log Viewer (opcodesio/log-viewer v3.17.1).
        // Package mendaftarkan route web (`/log-viewer`) dan API (`/log-viewer/api/*`)
        // dengan middleware `AuthorizeLogViewer` → `LogViewerService::auth()`:
        //   - bila callback/Gate tidak terdefinisi, SEMUA akses diizinkan (celah C1);
        //   - bila Gate `viewLogViewer` terdefinisi, akses diizinkan hanya bila
        //     Gate mengizinkan.
        // Gate ini membatasi seluruh route Log Viewer (web & api) ke superadmin
        // di semua environment. Fail-closed: exception → 403/500 (tidak ada data bocor);
        // role tak dikenal → ditolak (perbandingan strict).
        Gate::define('viewLogViewer', function ($user) {
            return isset($user->level) && $user->level === 'superadmin';
        });
    }
}
