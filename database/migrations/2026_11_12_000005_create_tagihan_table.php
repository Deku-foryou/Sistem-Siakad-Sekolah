<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tagihan siswa dengan snapshot lengkap.
     *
     * Snapshot tarif/kelas/periode saat diterbitkan; perubahan tarif tidak
     * mengubah tagihan yang sudah ada. Nominal dalam rupiah.
     *
     * Status:
     * - belum_dibayar  : belum ada pembayaran masuk
     * - dibayar_sebagian : sebagian terbayar
     * - lunas           : sudah lunas (termasuk Rp 0 jika pembebasan penuh -- lihat dibebaskan)
     * - dibebaskan      : pembebasan penuh (Rp 0, tanpa transaksi)
     * - dibatalkan      : tagihan dibatalkan sebelum lunas
     */
    public function up(): void
    {
        if (! Schema::hasTable('tagihan')) {
            Schema::create('tagihan', function (Blueprint $table) {
                $table->bigIncrements('idtagihan');
                $table->unsignedInteger('siswa_idsiswa');
                $table->enum('jenis', ['spp', 'ujian', 'seragam', 'buku', 'pendaftaran', 'insidental', 'lainnya']);
                $table->string('nama', 150)->nullable();       // nama khusus tagihan insidental
                $table->string('periode', 30);                 // format bebas-terkontrol per jenis, mis. "2026-11" untuk SPP
                $table->bigInteger('nominal')->unsigned();
                $table->bigInteger('diskon_nominal')->unsigned()->default(0);
                $table->smallInteger('diskon_persen')->unsigned()->default(0)->comment('0-100');
                $table->bigInteger('total_tagihan')->unsigned();

                // Snapshot tarif & konteks saat terbit
                $table->unsignedInteger('tarif_idtarif')->nullable();
                $table->string('tarif_nama', 150)->nullable();
                $table->enum('tarif_jenis', ['global', 'kelas', 'individu'])->nullable();
                $table->bigInteger('tarif_nominal')->unsigned()->nullable();
                $table->unsignedInteger('kelas_idkelas')->nullable();
                $table->string('kelas_nama', 120)->nullable();
                $table->unsignedInteger('tahunajaran_idtahunajaran')->nullable();
                $table->unsignedInteger('semester_idsemester')->nullable();

                $table->enum('status', ['belum_dibayar', 'dibayar_sebagian', 'lunas', 'dibebaskan', 'dibatalkan'])
                    ->default('belum_dibayar');
                $table->unsignedBigInteger('denda_id')->nullable();

                $table->date('tanggal_terbit');
                $table->date('tanggal_jatuh_tempo');
                $table->text('catatan')->nullable();
                $table->text('alasan_batal')->nullable();
                $table->unsignedBigInteger('dibuat_oleh')->nullable();
                $table->unsignedBigInteger('dibatalkan_oleh')->nullable();
                $table->timestamp('dibatalkan_pada')->nullable();
                $table->timestamps();

                $table->unique(['siswa_idsiswa', 'jenis', 'periode'], 'tagihan_siswa_jenis_periode_unique');
                $table->index(['siswa_idsiswa', 'status'], 'tagihan_siswa_status_idx');
                $table->index(['status', 'tanggal_jatuh_tempo'], 'tagihan_status_jt_idx');
                $table->index(['tanggal_jatuh_tempo'], 'tagihan_jatuh_tempo_idx');

                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('kelas_idkelas')
                    ->references('idkelas')->on('kelas')->onDelete('restrict');
                $table->foreign('tahunajaran_idtahunajaran')
                    ->references('idtahunajaran')->on('tahunajaran')->onDelete('restrict');
                $table->foreign('semester_idsemester')
                    ->references('idsemester')->on('semester')->onDelete('restrict');
                $table->foreign('tarif_idtarif')
                    ->references('idtarif')->on('tarif')->onDelete('restrict');
                $table->foreign('dibuat_oleh')
                    ->references('id')->on('users')->onDelete('restrict');
                $table->foreign('dibatalkan_oleh')
                    ->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
