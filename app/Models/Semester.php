<?php

namespace App\Models;

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
        return DB::transaction(function () use ($id) {
            $semester = static::findOrFail($id);
            static::query()->update(['aktif' => false]);
            $semester->aktif = true;
            $semester->save();

            // aktifkan tahun ajaran induk, nonaktifkan yang lain
            Tahunajaran::query()->update(['aktif' => false]);
            $semester->tahunajaran->aktif = true;
            $semester->tahunajaran->save();

            return $semester;
        });
    }
}
