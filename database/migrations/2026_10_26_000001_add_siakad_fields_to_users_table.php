<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 30)->nullable()->unique()->after('id');
            });
        }

        if (! Schema::hasColumn('users', 'wajib_ganti_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('wajib_ganti_password')->default(false)->after('level');
            });
        }

        // email jadi nullable (aditif, aman di DB existing)
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');

        // konversi level lama
        DB::table('users')->where('level', 'admin')->update(['level' => 'superadmin']);
        DB::table('users')->where('level', 'user')->update(['level' => 'siswa']);

        // backfill username dari bagian sebelum "@" pada email
        DB::table('users')->whereNull('username')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $u) {
                $base = $u->email ? Str::before($u->email, '@') : ('user' . $u->id);
                $base = Str::limit($base, 30, '');
                if ($base === '') {
                    $base = 'user' . $u->id;
                }
                $username = $base;
                $i = 1;
                while (DB::table('users')->where('username', $username)->where('id', '!=', $u->id)->exists()) {
                    $username = Str::limit($base, 27, '') . $i;
                    $i++;
                }
                DB::table('users')->where('id', $u->id)->update(['username' => $username]);
            }
        });
    }

    public function down(): void
    {
        // tidak menghapus kolom username agar aman; hanya kembalikan email not null bila diinginkan
    }
};
