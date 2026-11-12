<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adjustment keuangan immutable. Setiap perubahan atas tagihan yang sudah
     * diterbitkan dicatat sebagai baris adjustment; tidak ada UPDATE langsung.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tagihan_adjustment')) {
            Schema::create('tagihan_adjustment', function (Blueprint $table) {
                $table->bigIncrements('idtagihan_adjustment');
                $table->unsignedBigInteger('tagihan_idtagihan');
                $table->enum('jenis', ['koreksi_nominal', 'pembebasan', 'denda_dibebaskan', 'pembatalan']);
                $table->bigInteger('nominal_lama')->nullable();
                $table->bigInteger('nominal_baru')->nullable();
                $table->text('alasan');
                $table->unsignedBigInteger('oleh');
                $table->timestamp('created_at')->useCurrent();

                $table->index(['tagihan_idtagihan', 'created_at'], 'tagihan_adj_tagihan_waktu_idx');

                $table->foreign('tagihan_idtagihan')
                    ->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('oleh')
                    ->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_adjustment');
    }
};
