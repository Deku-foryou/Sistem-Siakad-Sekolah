<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger transaksi pembayaran (struktur fondasi; implementasi penuh di Sprint 2).
     *
     * Satu baris = satu transaksi pembayaran (manual atau online) untuk satu tagihan.
     * Tidak ada penghapusan; pembatalan melalui adjustment/reversal.
     */
    public function up(): void
    {
        if (! Schema::hasTable('pembayaran')) {
            Schema::create('pembayaran', function (Blueprint $table) {
                $table->bigIncrements('idpembayaran');
                $table->unsignedBigInteger('tagihan_idtagihan');
                $table->unsignedInteger('siswa_idsiswa');
                $table->bigInteger('nominal_dibayar')->unsigned();
                $table->bigInteger('biaya_gateway')->unsigned()->default(0);
                $table->enum('metode', ['tunai', 'transfer', 'va', 'qris', 'ewallet']);
                $table->string('provider', 40)->nullable();
                $table->string('order_id', 120)->unique()->nullable();
                $table->string('provider_ref', 150)->nullable()->unique();
                $table->enum('status', ['pending', 'success', 'failed', 'expired', 'cancelled', 'review', 'refunded'])
                    ->default('pending');
                $table->timestamp('dibayar_pada')->nullable();
                $table->text('alasan_gagal')->nullable();
                $table->unsignedBigInteger('diinput_oleh')->nullable();
                $table->unsignedBigInteger('diverifikasi_oleh')->nullable();
                $table->timestamp('diverifikasi_pada')->nullable();
                $table->timestamps();

                $table->index(['tagihan_idtagihan', 'status'], 'pembayaran_tagihan_status_idx');
                $table->index(['siswa_idsiswa', 'status'], 'pembayaran_siswa_status_idx');
                $table->index(['status', 'created_at'], 'pembayaran_status_created_idx');

                $table->foreign('tagihan_idtagihan')
                    ->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('diinput_oleh')
                    ->references('id')->on('users')->onDelete('restrict');
                $table->foreign('diverifikasi_oleh')
                    ->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
