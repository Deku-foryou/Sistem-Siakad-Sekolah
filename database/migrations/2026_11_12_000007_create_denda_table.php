<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Denda keterlambatan per tagihan. Satu denda per tagihan (unique).
     *
     * Metode: 'nominal' (rupiah tetap) atau 'persen' (persen dari sisa pokok; 1-100).
     */
    public function up(): void
    {
        if (! Schema::hasTable('denda')) {
            Schema::create('denda', function (Blueprint $table) {
                $table->bigIncrements('iddenda');
                $table->unsignedBigInteger('tagihan_idtagihan')->unique();
                $table->enum('metode', ['nominal', 'persen']);
                $table->bigInteger('nilai')->unsigned();
                $table->bigInteger('nominal_denda')->unsigned();
                $table->timestamp('diberikan_pada')->useCurrent();
                $table->enum('status', ['berlaku', 'dibebaskan'])->default('berlaku');
                $table->timestamp('dibebaskan_pada')->nullable();
                $table->unsignedBigInteger('dibebaskan_oleh')->nullable();
                $table->text('alasan_dibebaskan')->nullable();

                $table->index('status');

                $table->foreign('tagihan_idtagihan')
                    ->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('dibebaskan_oleh')
                    ->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('denda');
    }
};
