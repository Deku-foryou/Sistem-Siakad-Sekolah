<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status finalisasi rapor per semester dan kelas.
     *
     * Wali kelas disimpan sebagai snapshot agar rapor yang dicetak ulang tetap
     * menampilkan wali yang memfinalisasi saat itu.
     */
    public function up(): void
    {
        if (! Schema::hasTable('finalisasi_rapor')) {
            Schema::create('finalisasi_rapor', function (Blueprint $table) {
                $table->increments('idfinalisasi_rapor');
                $table->unsignedInteger('semester_idsemester');
                $table->unsignedInteger('kelas_idkelas');
                $table->enum('status', ['draf', 'final'])->default('draf');
                $table->unsignedInteger('wali_kelas_idguru')->nullable();
                $table->unsignedBigInteger('difinalisasi_oleh')->nullable();
                $table->timestamp('difinalisasi_pada')->nullable();
                $table->timestamps();

                $table->unique(
                    ['semester_idsemester', 'kelas_idkelas'],
                    'finalisasi_rapor_semester_kelas_unique'
                );

                $table->foreign('semester_idsemester')
                    ->references('idsemester')->on('semester')
                    ->onDelete('restrict');
                $table->foreign('kelas_idkelas')
                    ->references('idkelas')->on('kelas')
                    ->onDelete('restrict');
                $table->foreign('wali_kelas_idguru')
                    ->references('idguru')->on('guru')
                    ->onDelete('restrict');
                $table->foreign('difinalisasi_oleh')
                    ->references('id')->on('users')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finalisasi_rapor');
    }
};
