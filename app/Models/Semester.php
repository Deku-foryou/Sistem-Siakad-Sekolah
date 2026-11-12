<?php

namespace App\Models;

use App\Services\KonteksAkademikService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Semester extends Model
{
    use HasFactory;

    protected $table = 'semester';

    protected $primaryKey = 'idsemester';

    protected $fillable = [
        'idsemester',
        'tahunajaran_idtahunajaran',
        'nama',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('idsemester', 'like', "%{$value}%")
            ->orWhere('nama', 'like', "%{$value}%");
    }

    public function tahunajaran()
    {
        return $this->belongsTo(Tahunajaran::class, 'tahunajaran_idtahunajaran', 'idtahunajaran');
    }

    public static function aktif()
    {
        return static::where('aktif', true)->first();
    }

    public static function aktifkan($id)
    {
        // Delegasikan ke KonteksAkademikService agar aktivasi semester
        // dan tahun ajaran induknya terjadi atomik dalam satu transaksi.
        return KonteksAkademikService::aktifkanSemester((int) $id)->semester;
    }

    public static function nonaktifkan($id)
    {
        return DB::transaction(function () use ($id) {
            $semester = static::query()->lockForUpdate()->findOrFail($id);
            $semester->aktif = false;
            $semester->save();

            return $semester;
        });
    }

    public function jadwalkelas()
    {
        return $this->hasMany(JadwalKelas::class, 'semester_idsemester', 'idsemester');
    }

    public function penugasanMengajar()
    {
        return $this->hasMany(PenugasanMengajar::class, 'semester_idsemester', 'idsemester');
    }

    public function komponenNilai()
    {
        return $this->hasMany(KomponenNilai::class, 'semester_idsemester', 'idsemester');
    }

    public function kktpMapel()
    {
        return $this->hasMany(KktpMapel::class, 'semester_idsemester', 'idsemester');
    }

    public function nilaiSiswa()
    {
        return $this->hasMany(NilaiSiswa::class, 'semester_idsemester', 'idsemester');
    }

    public function nilaiAkhir()
    {
        return $this->hasMany(NilaiAkhir::class, 'semester_idsemester', 'idsemester');
    }

    public function finalisasiRapor()
    {
        return $this->hasMany(FinalisasiRapor::class, 'semester_idsemester', 'idsemester');
    }

    public function catatanRapor()
    {
        return $this->hasMany(CatatanRapor::class, 'semester_idsemester', 'idsemester');
    }
}
