<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori Dokumentasi (Docs) — 2B.15.
 * Struktur mengikuti model `App\Models\Docscat` aktual.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('docscat')) {
            return;
        }

        Schema::create('docscat', function (Blueprint $table) {
            $table->increments('iddocscat');
            $table->string('docscat', 255);
            $table->unsignedSmallInteger('urut')->default(1);
            $table->timestamps();

            $table->unique('docscat', 'docscat_unique');
            $table->unique('urut', 'docscat_urut_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docscat');
    }
};
