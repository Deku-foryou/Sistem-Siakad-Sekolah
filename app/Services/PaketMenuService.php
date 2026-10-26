<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Usermenu;

class PaketMenuService
{
    public static function menuDariRolecat(int $rolecatId): array
    {
        return Role::where('rolecat_idrolecat', $rolecatId)
            ->pluck('appmenunew_idappmenunew')
            ->unique()
            ->values()
            ->all();
    }

    public static function terapkanPaket(User $user, int $rolecatId): int
    {
        $menuIds = self::menuDariRolecat($rolecatId);
        $added = 0;
        foreach ($menuIds as $mid) {
            $um = Usermenu::firstOrCreate([
                'user_iduser' => $user->id,
                'appmenunew_idappmenunew' => $mid,
            ]);
            if ($um->wasRecentlyCreated) {
                $added++;
            }
        }

        return $added;
    }

    public static function terapkanDefault(User $user): string
    {
        $level = $user->level;
        $namaPaket = config('siakad.paket_default.'.$level);
        if (! $namaPaket) {
            return 'paket_tidak_ada';
        }

        $rolecat = \App\Models\Rolecat::where('rolecat', $namaPaket)->first();
        if (! $rolecat) {
            return 'paket_tidak_ada';
        }

        self::terapkanPaket($user, $rolecat->idrolecat);

        return 'ok';
    }

    public static function sinkronkanSemua(string $level, int $chunk = 200): array
    {
        $processed = 0;
        $added = 0;
        $namaPaket = config('siakad.paket_default.'.$level);
        $rolecat = $namaPaket ? \App\Models\Rolecat::where('rolecat', $namaPaket)->first() : null;

        if (! $rolecat) {
            $count = User::where('level', $level)->count();

            return ['processed' => $count, 'added' => 0, 'paket_tidak_ada' => true];
        }

        User::where('level', $level)->chunk($chunk, function ($users) use (&$processed, &$added, $rolecat) {
            foreach ($users as $user) {
                $processed++;
                $added += self::terapkanPaket($user, $rolecat->idrolecat);
            }
        });

        return ['processed' => $processed, 'added' => $added, 'paket_tidak_ada' => false];
    }
}
