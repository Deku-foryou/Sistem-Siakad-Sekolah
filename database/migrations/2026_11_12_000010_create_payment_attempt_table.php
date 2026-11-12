<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment attempt — percobaan/instruksi transaksi (belum tentu uang masuk).
 *
 * Satu baris = satu Snap token / satu order Midtrans untuk satu tagihan.
 * Status lifecycle: created -> pending -> success | failed | expired | cancelled.
 * TIDAK mengurangi saldo tagihan; alokasi dan pengurangan sisa terjadi hanya
 * setelah webhook/API mengonfirmasi success (PaymentService, dalam transaksi + lock).
 *
 * `order_id` unik per percobaan. `public_token` adalah capability URL untuk
 * pihak ketiga (dibuat, tidak wajib dipakai; tidak memberi akses data akademik).
 * Biaya gateway jujur: NULL bila tidak diketahui — tidak pernah dikarang/di-hardcode.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_attempt')) {
            Schema::create('payment_attempt', function (Blueprint $table) {
                $table->bigIncrements('idpayment_attempt');
                $table->unsignedInteger('siswa_idsiswa');
                $table->unsignedBigInteger('tagihan_idtagihan');
                $table->string('order_id', 150)->unique();
                $table->string('public_token', 64)->unique()->nullable();
                $table->string('provider', 40)->default('midtrans');
                $table->string('metode', 40)->nullable();
                $table->bigInteger('nominal')->unsigned();                     // snapshot sisa tagihan saat attempt dibuat
                $table->bigInteger('biaya_gateway')->unsigned()->nullable();   // NULL = belum diketahui (tidak dikarang)
                $table->enum('status', ['created', 'pending', 'success', 'failed', 'expired', 'cancelled'])->default('created');
                $table->unsignedBigInteger('payer_user_id')->nullable();
                $table->enum('payer_jenis', ['siswa', 'wali', 'ketiga'])->default('siswa');
                $table->string('payer_nama', 150)->nullable();
                $table->string('snap_token', 255)->nullable();
                $table->string('snap_redirect_url', 500)->nullable();
                $table->json('payload_request')->nullable();                    // snapshot request (order_id + gross_amount saja)
                $table->json('payload_response')->nullable();                   // whitelist field dari notifikasi/status (sanitized)
                $table->timestamp('expired_pada')->nullable();
                $table->timestamps();

                $table->index(['siswa_idsiswa', 'status'], 'payment_attempt_siswa_status_idx');
                $table->index(['tagihan_idtagihan', 'status'], 'payment_attempt_tagihan_status_idx');
                $table->index(['status', 'created_at'], 'payment_attempt_status_created_idx');

                $table->foreign('siswa_idsiswa')->references('idsiswa')->on('siswa')->onDelete('restrict');
                $table->foreign('tagihan_idtagihan')->references('idtagihan')->on('tagihan')->onDelete('restrict');
                $table->foreign('payer_user_id')->references('id')->on('users')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempt');
    }
};
