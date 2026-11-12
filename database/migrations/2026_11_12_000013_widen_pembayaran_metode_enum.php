<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memperluas enum `pembayaran.metode` agar hasil mapping payment_type Midtrans
 * dapat disimpan apa adanya tanpa mengarang nilai:
 *
 *   bank_transfer -> va   (bank diketahui terpisah di payload)
 *   gopay/shopeepay/dll   -> ewallet
 *   qris          -> qris
 *   credit_card   -> credit_card  (BARU)
 *   cstore        -> cstore       (BARU)
 *   echannel      -> echannel     (BARU)
 *   lainnya       -> lainnya      (BARU — payment_type yang belum dikenali)
 *
 * Aditif (memperlebar himpunan nilai enum yang valid); tidak ada data yang diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembayaran')) {
            return;
        }

        $definition = DB::selectOne("SHOW COLUMNS FROM pembayaran WHERE Field = 'metode'")->Type ?? '';

        if (str_contains($definition, "'lainnya'")) {
            return; // sudah ditambah sebelumnya
        }

        DB::statement("ALTER TABLE pembayaran MODIFY metode ENUM('tunai','transfer','va','qris','ewallet','credit_card','cstore','echannel','lainnya') NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembayaran')) {
            return;
        }

        // Nilai baru tidak boleh dipaksa hilang bila ada baris bernilai baru.
        $baru = DB::table('pembayaran')->whereIn('metode', ['credit_card', 'cstore', 'echannel', 'lainnya'])->count();
        if ($baru > 0) {
            return; // no-op: sudah ada data — jangan kehilangan informasi
        }

        DB::statement("ALTER TABLE pembayaran MODIFY metode ENUM('tunai','transfer','va','qris','ewallet') NOT NULL");
    }
};
