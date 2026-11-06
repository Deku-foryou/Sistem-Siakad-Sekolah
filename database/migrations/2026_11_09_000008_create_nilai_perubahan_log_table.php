<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'nilai_perubahan_log';

    private const CONSTRAINT_SATU_TARGET = 'nilai_perubahan_log_satu_target_check';

    /**
     * Log perubahan nilai (khususnya koreksi setelah finalisasi).
     *
     * Setiap baris harus menunjuk tepat satu target: nilai_siswa atau
     * nilai_akhir. Aturan ini ditegakkan di database melalui CHECK constraint
     * (MySQL 8.0.16+) dan diulang pada model NilaiPerubahanLog untuk pesan
     * kesalahan yang ramah.
     */
    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->bigIncrements('idnilai_perubahan_log');
                $table->unsignedBigInteger('nilai_siswa_idnilai_siswa')->nullable();
                $table->unsignedBigInteger('nilai_akhir_idnilai_akhir')->nullable();
                $table->decimal('nilai_lama', 5, 2)->nullable();
                $table->decimal('nilai_baru', 5, 2)->nullable();
                $table->text('alasan');
                $table->unsignedBigInteger('diubah_oleh');
                $table->timestamps();

                $table->foreign('nilai_siswa_idnilai_siswa')
                    ->references('idnilai_siswa')->on('nilai_siswa')
                    ->onDelete('restrict');
                $table->foreign('nilai_akhir_idnilai_akhir')
                    ->references('idnilai_akhir')->on('nilai_akhir')
                    ->onDelete('restrict');
                $table->foreign('diubah_oleh')
                    ->references('id')->on('users')
                    ->onDelete('restrict');
            });
        }

        $this->tambahkanConstraintSatuTarget();
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }

    private function tambahkanConstraintSatuTarget(): void
    {
        $sudahAda = DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', self::TABLE)
            ->where('constraint_name', self::CONSTRAINT_SATU_TARGET)
            ->exists();

        if ($sudahAda) {
            return;
        }

        DB::statement(
            'ALTER TABLE `'.self::TABLE.'` ADD CONSTRAINT `'.self::CONSTRAINT_SATU_TARGET.'` '
            .'CHECK ((`nilai_siswa_idnilai_siswa` IS NULL) <> (`nilai_akhir_idnilai_akhir` IS NULL))'
        );
    }
};
