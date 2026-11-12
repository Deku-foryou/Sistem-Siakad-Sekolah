<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memperlebar enum `pembayaran.metode` dengan nilai 'saldo' (penggunaan saldo siswa),
 * agar ledger penggunaan saldo tercatat apa adanya (analog dengan penambahan
 * metode credit_card/cstore/echannel/lainnya di Sprint 2).
 *
 * Aditif; tidak mengubah baris data yang sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembayaran')) {
            return;
        }

        $definition = DB::selectOne("SHOW COLUMNS FROM pembayaran WHERE Field = 'metode'")->Type ?? '';

        if (str_contains($definition, "'saldo'")) {
            return; // sudah ada
        }

        DB::statement("ALTER TABLE pembayaran MODIFY metode ENUM('tunai','transfer','va','qris','ewallet','credit_card','cstore','echannel','saldo','lainnya') NOT NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembayaran')) {
            return;
        }

        $saldoRows = DB::table('pembayaran')->where('metode', 'saldo')->count();
        if ($saldoRows > 0) {
            return; // no-op: data sudah bernilai 'saldo'
        }

        DB::statement("ALTER TABLE pembayaran MODIFY metode ENUM('tunai','transfer','va','qris','ewallet','credit_card','cstore','echannel','lainnya') NOT NULL");
    }
};
