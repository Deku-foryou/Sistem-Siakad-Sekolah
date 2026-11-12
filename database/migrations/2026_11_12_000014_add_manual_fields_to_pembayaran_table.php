<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            if (! Schema::hasColumn('pembayaran', 'bukti_path')) {
                $table->string('bukti_path', 255)->nullable()->after('alasan_gagal');
            }
            if (! Schema::hasColumn('pembayaran', 'bukti_nama_asli')) {
                $table->string('bukti_nama_asli', 255)->nullable()->after('bukti_path');
            }
            if (! Schema::hasColumn('pembayaran', 'bukti_mime')) {
                $table->string('bukti_mime', 100)->nullable()->after('bukti_nama_asli');
            }
            if (! Schema::hasColumn('pembayaran', 'bukti_ukuran')) {
                $table->unsignedBigInteger('bukti_ukuran')->nullable()->after('bukti_mime');
            }
            if (! Schema::hasColumn('pembayaran', 'referensi')) {
                $table->string('referensi', 150)->nullable()->after('provider_ref');
            }
            if (! Schema::hasColumn('pembayaran', 'catatan')) {
                $table->text('catatan')->nullable()->after('alasan_gagal');
            }
            if (! Schema::hasColumn('pembayaran', 'alasan_penolakan')) {
                $table->text('alasan_penolakan')->nullable()->after('catatan');
            }
            if (! Schema::hasColumn('pembayaran', 'nama_pembayar')) {
                $table->string('nama_pembayar', 150)->nullable()->after('payer_nama');
            }
            if (! Schema::hasColumn('pembayaran', 'tanggal_pembayaran')) {
                $table->date('tanggal_pembayaran')->nullable()->after('dibayar_pada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            foreach (['bukti_path', 'bukti_nama_asli', 'bukti_mime', 'bukti_ukuran', 'referensi', 'catatan', 'alasan_penolakan', 'nama_pembayar', 'tanggal_pembayaran'] as $col) {
                if (Schema::hasColumn('pembayaran', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
