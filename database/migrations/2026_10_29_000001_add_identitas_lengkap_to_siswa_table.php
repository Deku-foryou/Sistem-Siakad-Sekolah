<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->unique()->after('nis');
            $table->string('telepon_siswa', 20)->nullable()->after('alamat');
            $table->string('email')->nullable()->after('telepon_siswa');
            $table->string('asal_sekolah')->nullable()->after('tahun_masuk');
            $table->string('telepon_ayah', 20)->nullable()->after('nama_ayah');
            $table->string('telepon_ibu', 20)->nullable()->after('nama_ibu');
            $table->string('nama_wali')->nullable()->after('telepon_ibu');
            $table->string('telepon_wali', 20)->nullable()->after('nama_wali');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn(['nik','telepon_siswa','email','asal_sekolah','telepon_ayah','telepon_ibu','nama_wali','telepon_wali']);
        });
    }
};
