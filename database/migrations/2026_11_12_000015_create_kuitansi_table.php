<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kuitansi')) {
            Schema::create('kuitansi', function (Blueprint $table) {
                $table->bigIncrements('idkuitansi');
                $table->unsignedBigInteger('pembayaran_idpembayaran')->unique();
                $table->unsignedInteger('siswa_idsiswa');
                $table->unsignedBigInteger('tagihan_idtagihan');
                $table->string('nomor', 50)->unique();
                $table->unsignedSmallInteger('tahun');
                $table->unsignedTinyInteger('bulan');
                $table->unsignedInteger('nomor_urut');
                $table->json('snapshot_sekolah')->nullable();
                $table->json('snapshot_kuitansi')->nullable();
                $table->string('file_path', 255);
                $table->string('checksum_sha256', 64);
                $table->unsignedBigInteger('ukuran_file')->nullable();
                $table->unsignedBigInteger('dibuat_oleh')->nullable();
                $table->timestamps();

                $table->index(['tahun', 'bulan'], 'kuitansi_tahun_bulan_idx');
                $table->index(['siswa_idsiswa'], 'kuitansi_siswa_idx');

                $table->foreign('pembayaran_idpembayaran')->references('idpembayaran')->on('pembayaran')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('tagihan_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('dibuat_oleh')->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kuitansi');
    }
};
