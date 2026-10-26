<?php

namespace App\Models;

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
        return DB::transaction(function () use ($id) {
            static::query()->update(['aktif' => false]);
            $tahun = static::findOrFail($id);
            $tahun->aktif = true;
            $tahun->save();

            return $tahun;
        });
    }
}
