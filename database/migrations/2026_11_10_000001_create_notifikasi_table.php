<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notifikasi sistem (peristiwa server-side) untuk guru dan siswa.
     *
     * Keputusan (lihat LAPORAN_TAHAP_2B6_2.md): jenis adalah string bebas
     * dengan validasi di service (bukan enum) agar nilai dapat bertambah
     * tanpa ALTER tabel; prioritas rendah/sedang/tinggi. Channel database
     * saja. Tidak ada soft delete / arsip pada versi pertama.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifikasi')) {
            Schema::create('notifikasi', function (Blueprint $table) {
                $table->bigIncrements('idnotifikasi');
                $table->string('judul', 255);
                $table->text('isi');
                $table->string('jenis', 30);
                $table->string('prioritas', 10)->default('sedang');
                $table->string('sumber_tabel', 50)->nullable();
                $table->unsignedBigInteger('sumber_id')->nullable();
                $table->string('link', 255)->nullable();
                $table->unsignedBigInteger('dibuat_oleh')->nullable();
                $table->timestamp('waktu_dibuat')->useCurrent();
                $table->timestamps();

                $table->foreign('dibuat_oleh')
                    ->references('id')->on('users')
                    ->onDelete('restrict');

                $table->index(['jenis', 'waktu_dibuat'], 'notifikasi_jenis_waktu_index');
                $table->index(['sumber_tabel', 'sumber_id'], 'notifikasi_sumber_index');
                $table->index('waktu_dibuat', 'notifikasi_waktu_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
