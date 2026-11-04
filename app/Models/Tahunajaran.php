<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Tahunajaran extends Model
{
    use HasFactory;

    protected $table = 'tahunajaran';

    protected $primaryKey = 'idtahunajaran';

    protected $fillable = [
        'idtahunajaran',
        'nama',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('idtahunajaran', 'like', "%{$value}%")
            ->orWhere('nama', 'like', "%{$value}%");
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class, 'tahunajaran_idtahunajaran', 'idtahunajaran');
    }

    public static function aktif()
    {
        return static::where('aktif', true)->first();
    }

    public static function aktifkan($id)
    {
        // Aktivasi tahun ajaran hanya boleh lewat aktivasi semester (KonteksAkademikService::aktifkanSemester).
        // Ini mencegah konteks tidak konsisten (semester aktif di tahun ajaran lain tetap aktif).
        throw new DomainException('Aktivasi tahun ajaran harus dilakukan melalui pengaktifan semester pada tahun ajaran tersebut.');
    }

    public static function nonaktifkan($id)
    {
        return DB::transaction(function () use ($id) {
            $tahun = static::query()->lockForUpdate()->findOrFail($id);

            // Cegah kondisi "semester aktif tanpa tahun ajaran aktif".
            $semesterAktif = $tahun->semesters()
                ->where('aktif', true)
                ->lockForUpdate()
                ->exists();

            if ($semesterAktif) {
                throw new DomainException('Tahun ajaran memiliki semester aktif. Nonaktifkan semester terlebih dahulu.');
            }

            $tahun->aktif = false;
            $tahun->save();

            return $tahun;
        });
    }
}
