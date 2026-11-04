<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel pengumuman, pengumuman_target, dan pengumuman_baca.
     *
     * Konvensi proyek:
     * - Tabel lowercase tanpa plural, PK id{tabel}.
     * - FK {tabel}_id{pk} dengan tipe sama persis dengan PK tujuan.
     * - ON DELETE RESTRICT untuk seluruh FK.
     */
    public function up(): void
    {
        if (! Schema::hasTable('pengumuman')) {
            Schema::create('pengumuman', function (Blueprint $table) {
                $table->increments('idpengumuman');
                $table->string('judul', 255);
                $table->text('isi');
                $table->enum('kategori', ['akademik', 'acara', 'penting', 'darurat'])->default('akademik');
                $table->enum('prioritas', ['rendah', 'sedang', 'tinggi', 'mendesak'])->default('sedang');
                $table->enum('status', ['draf', 'terbit', 'arsip'])->default('draf');
                $table->timestamp('waktu_terbit')->nullable();
                $table->date('berlaku_dari')->nullable();
                $table->date('berlaku_sampai')->nullable();
                $table->enum('target', ['semua', 'jurusan', 'kelas', 'siswa'])->default('semua');
                $table->unsignedBigInteger('dibuat_oleh');
                $table->unsignedBigInteger('diperbarui_oleh')->nullable();
                $table->timestamps();

                $table->foreign('dibuat_oleh')
                    ->references('id')->on('users')
                    ->onDelete('restrict');
                $table->foreign('diperbarui_oleh')
                    ->references('id')->on('users')
                    ->onDelete('restrict');
            });
        }

        if (! Schema::hasTable('pengumuman_target')) {
            Schema::create('pengumuman_target', function (Blueprint $table) {
                $table->increments('idpengumuman_target');
                $table->unsignedInteger('pengumuman_idpengumuman');
                $table->unsignedInteger('jurusan_idjurusan')->nullable();
                $table->unsignedInteger('kelas_idkelas')->nullable();
                $table->unsignedInteger('siswa_idsiswa')->nullable();
                $table->timestamps();

                // UNIQUE terpisah — tidak bergantung kombinasi kolom nullable.
                $table->unique(['pengumuman_idpengumuman', 'jurusan_idjurusan'], 'pengumuman_target_jurusan_unique');
                $table->unique(['pengumuman_idpengumuman', 'kelas_idkelas'], 'pengumuman_target_kelas_unique');
                $table->unique(['pengumuman_idpengumuman', 'siswa_idsiswa'], 'pengumuman_target_siswa_unique');

                $table->foreign('pengumuman_idpengumuman')
                    ->references('idpengumuman')->on('pengumuman')
                    ->onDelete('restrict');
                $table->foreign('jurusan_idjurusan')
                    ->references('idjurusan')->on('jurusan')
                    ->onDelete('restrict');
                $table->foreign('kelas_idkelas')
                    ->references('idkelas')->on('kelas')
                    ->onDelete('restrict');
                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')
                    ->onDelete('restrict');
            });
        }

        if (! Schema::hasTable('pengumuman_baca')) {
            Schema::create('pengumuman_baca', function (Blueprint $table) {
                $table->bigIncrements('idpengumuman_baca');
                $table->unsignedInteger('pengumuman_idpengumuman');
                $table->unsignedInteger('siswa_idsiswa');
                $table->timestamp('dibaca_pada')->nullable();
                $table->timestamps();

                $table->unique(['pengumuman_idpengumuman', 'siswa_idsiswa'], 'pengumuman_baca_pengumuman_siswa_unique');

                $table->foreign('pengumuman_idpengumuman')
                    ->references('idpengumuman')->on('pengumuman')
                    ->onDelete('restrict');
                $table->foreign('siswa_idsiswa')
                    ->references('idsiswa')->on('siswa')
                    ->onDelete('restrict');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman_baca');
        Schema::dropIfExists('pengumuman_target');
        Schema::dropIfExists('pengumuman');
    }
};
