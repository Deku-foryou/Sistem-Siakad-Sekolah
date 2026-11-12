<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat perubahan tarif (immutable).
     *
     * Setiap pembuatan/perubahan/penghapusan tarif dicatat sebagai baris baru.
     * Dipakai untuk menelusuri tarif yang pernah berlaku dan untuk audit.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tarif_riwayat')) {
            Schema::create('tarif_riwayat', function (Blueprint $table) {
                $table->bigIncrements('idtarif_riwayat');
                $table->unsignedInteger('tarif_idtarif');
                $table->enum('aksi', ['dibuat', 'diubah', 'dinonaktifkan']);
                $table->bigInteger('nominal_lama')->nullable();
                $table->bigInteger('nominal_baru')->nullable();
                $table->string('nama_lama', 150)->nullable();
                $table->string('nama_baru', 150)->nullable();
                $table->date('berlaku_dari_lama')->nullable();
                $table->date('berlaku_dari_baru')->nullable();
                $table->date('berlaku_sampai_lama')->nullable();
                $table->date('berlaku_sampai_baru')->nullable();
                $table->enum('status_lama', ['aktif', 'nonaktif'])->nullable();
                $table->enum('status_baru', ['aktif', 'nonaktif'])->nullable();
                $table->text('alasan')->nullable();
                $table->unsignedBigInteger('oleh')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['tarif_idtarif', 'created_at'], 'tarif_riwayat_tarif_waktu_idx');
                $table->index('oleh');

                $table->foreign('tarif_idtarif')
                    ->references('idtarif')->on('tarif')->onDelete('restrict');
                $table->foreign('oleh')
                    ->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_riwayat');
    }
};
