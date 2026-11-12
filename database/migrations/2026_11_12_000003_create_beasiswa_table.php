<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Beasiswa / keringanan untuk siswa.
     *
     * Jenis:
     * - 'nominal'     : diskon nominal tetap (rupiah)
     * - 'persen'      : diskon persen (0-100; nilai disimpan sebagai persen integer atau desimal)
     * - 'pembebasan'  : pembebasan penuh (nominal Rp 0)
     */
    public function up(): void
    {
        if (! Schema::hasTable('beasiswa')) {
            Schema::create('beasiswa', function (Blueprint $table) {
                $table->increments('idbeasiswa');
                $table->string('nama', 150);
                $table->enum('jenis', ['nominal', 'persen', 'pembebasan']);
                $table->bigInteger('nilai')->unsigned(); // rupiah untuk nominal, 1-100 untuk persen
                $table->date('berlaku_dari');
                $table->date('berlaku_sampai')->nullable();
                $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
                $table->text('alasan')->nullable();
                $table->unsignedBigInteger('dibuat_oleh')->nullable();
                $table->timestamps();

                $table->index(['jenis', 'status', 'berlaku_dari'], 'beasiswa_jenis_status_dari_idx');
                $table->index('berlaku_dari');

                $table->foreign('dibuat_oleh')
                    ->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa');
    }
};
