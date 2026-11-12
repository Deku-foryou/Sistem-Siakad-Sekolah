<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen (Docs) — 2B.15.
 * Struktur mengikuti model `App\Models\Docs` aktual.
 * `content` berisi nama file PDF yang disimpan di storage (tutorial-website).
 * Kategori yang masih memiliki dokumen tidak dapat dihapus (RESTRICT),
 * konsisten dengan penangkapan error di DocscatComponent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('docs')) {
            return;
        }

        Schema::create('docs', function (Blueprint $table) {
            $table->increments('iddocs');
            $table->string('title', 255);
            $table->string('content', 255);
            $table->unsignedSmallInteger('urut')->default(1);
            $table->unsignedInteger('docscat_iddocscat');
            $table->timestamps();

            $table->foreign('docscat_iddocscat')
                ->references('iddocscat')->on('docscat')
                ->restrictOnDelete();

            $table->unique(['docscat_iddocscat', 'urut'], 'docs_kategori_urut_unique');
            $table->index('docscat_iddocscat', 'docs_docscat_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docs');
    }
};
