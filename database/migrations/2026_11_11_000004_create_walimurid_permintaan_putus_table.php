<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FONDASI PORTAL WALI MURID — Tahap 2B.7.3B
 *
 * Desain final §5.3: tabel `walimurid_permintaan_putus`
 * (permintaan pemutusan hubungan oleh wali; keputusan oleh pengelola berwenang).
 * - Satu permintaan `menunggu` per relasi (unique generated column).
 * - Seluruh FK ON DELETE RESTRICT ON UPDATE RESTRICT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('walimurid_permintaan_putus')) {
            return;
        }

        Schema::create('walimurid_permintaan_putus', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->bigIncrements('idpermintaan_putus');
            $table->unsignedBigInteger('walimurid_id');
            $table->unsignedBigInteger('diminta_oleh');                 // wali pemilik relasi
            $table->string('alasan', 500);
            $table->enum('status_permintaan', ['menunggu', 'disetujui', 'ditolak', 'dibatalkan'])->default('menunggu');
            $table->tinyInteger('status_menunggu_flag')
                ->virtualAs("CASE WHEN `status_permintaan` = 'menunggu' THEN 1 ELSE NULL END")
                ->nullable();
            $table->unsignedBigInteger('diputuskan_oleh')->nullable()->default(null); // admin
            $table->timestamp('diputuskan_pada')->nullable()->default(null);
            $table->string('catatan_keputusan', 500)->nullable();
            $table->timestamp('created_at')->nullable()->default(null);
            $table->timestamp('updated_at')->nullable()->default(null);

            $table->unique(['walimurid_id', 'status_menunggu_flag'], 'uk_permintaan_menunggu');
            $table->index(['status_permintaan', 'created_at'], 'idx_permintaan_antrian');

            $table->foreign('walimurid_id', 'permintaan_walimurid_fk')
                ->references('idwalimurid')->on('walimurid')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('diminta_oleh', 'permintaan_peminta_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('diputuskan_oleh', 'permintaan_pemutus_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walimurid_permintaan_putus');
    }
};
