<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memperluas ledger alokasi untuk mendukung reversal (kebalikan alokasi) —
 * refund & koreksi keuangan. Tidak mengubah baris lama; hanya menambah
 * himpunan nilai enum `jenis` dan kolom `referensi`.
 *
 * jenis baru:
 * - reversal-refund : menghilangkan alokasi karena refund berhasil
 * - reversal-koreksi: menghilangkan alokasi karena koreksi yang disetujui
 *
 * Baris baru jenis reversal MEMILIKI SIGN-NEGATIVE secara konsep (mengurangi
 * terpakai/sisa) — lihat PaymentService::terpakaiTagihan()/sisaTagihan()/
 * hitungSaldoTersedia() yang diperluas untuk menganggap reversal sebagai pengurang.
 *
 * {referensi_tipe, referensi_id} menunjuk refund_pengajuan.id atau koreksi_pengajuan.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pembayaran_alokasi')) {
            return;
        }

        $definition = DB::selectOne("SHOW COLUMNS FROM pembayaran_alokasi WHERE Field = 'jenis'")->Type ?? '';

        if (! str_contains($definition, "'reversal-refund'")) {
            DB::statement("ALTER TABLE pembayaran_alokasi MODIFY jenis ENUM('tagihan','saldo','reversal-refund','reversal-koreksi') NOT NULL");
        }

        Schema::table('pembayaran_alokasi', function ($table) {
            if (! Schema::hasColumn('pembayaran_alokasi', 'referensi_tipe')) {
                $table->string('referensi_tipe', 30)->nullable()->after('jenis');
            }
            if (! Schema::hasColumn('pembayaran_alokasi', 'referensi_id')) {
                $table->unsignedBigInteger('referensi_id')->nullable()->after('referensi_tipe');
            }
            if (! Schema::hasColumn('pembayaran_alokasi', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('referensi_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembayaran_alokasi')) {
            return;
        }

        $baru = DB::table('pembayaran_alokasi')->whereIn('jenis', ['reversal-refund', 'reversal-koreksi'])->count();
        if ($baru > 0) {
            return; // data ada — jangan menghapus jejak reversal
        }

        Schema::table('pembayaran_alokasi', function ($table) {
            if (Schema::hasColumn('pembayaran_alokasi', 'referensi_tipe')) {
                $table->dropColumn('referensi_tipe');
            }
            if (Schema::hasColumn('pembayaran_alokasi', 'referensi_id')) {
                $table->dropColumn('referensi_id');
            }
            if (Schema::hasColumn('pembayaran_alokasi', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });

        DB::statement("ALTER TABLE pembayaran_alokasi MODIFY jenis ENUM('tagihan','saldo') NOT NULL");
    }
};
