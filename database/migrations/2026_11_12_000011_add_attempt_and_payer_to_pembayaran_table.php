<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembayaran (ledger uang masuk) dihubungkan 1:1 ke payment_attempt.
 *
 * - `payment_attempt_id` NULL untuk pembayaran manual/konvensional; unik untuk online.
 * - `payer_jenis`: siswa | wali | ketiga (pihak ketiga) | admin (input manual oleh admin).
 * - Identifier pembayar di-copy dari attempt saat settling (snapshot audit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            if (! Schema::hasColumn('pembayaran', 'payment_attempt_id')) {
                $table->unsignedBigInteger('payment_attempt_id')->nullable()->unique()->after('idpembayaran');
                $table->foreign('payment_attempt_id')->references('idpayment_attempt')->on('payment_attempt')->onDelete('restrict');
            }

            if (! Schema::hasColumn('pembayaran', 'payer_jenis')) {
                $table->enum('payer_jenis', ['siswa', 'wali', 'ketiga', 'admin'])->nullable()->after('payment_attempt_id');
            }

            if (! Schema::hasColumn('pembayaran', 'payer_nama')) {
                $table->string('payer_nama', 150)->nullable()->after('payer_jenis');
            }

            if (! Schema::hasColumn('pembayaran', 'payer_user_id')) {
                $table->unsignedBigInteger('payer_user_id')->nullable()->after('payer_nama');
                $table->foreign('payer_user_id')->references('id')->on('users')->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            if (Schema::hasColumn('pembayaran', 'payment_attempt_id')) {
                $table->dropForeign(['payment_attempt_id']);
                $table->dropUnique(['payment_attempt_id']);
                $table->dropColumn('payment_attempt_id');
            }

            if (Schema::hasColumn('pembayaran', 'payer_user_id')) {
                $table->dropForeign(['payer_user_id']);
                $table->dropColumn('payer_user_id');
            }

            foreach (['payer_jenis', 'payer_nama'] as $column) {
                if (Schema::hasColumn('pembayaran', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
