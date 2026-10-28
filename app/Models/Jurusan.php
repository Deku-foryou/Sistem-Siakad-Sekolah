<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Jurusan extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'jurusan';
    protected $primaryKey = 'idjurusan';
    public $timestamps = true;
    protected $fillable = [
        'idjurusan',
        'kode',
        'nama',
        'deskripsi',
        'aktif',
    ];

    public static function buat(array $data): array
    {
        $result = ['success' => false, 'jurusan' => null, 'errors' => []];

        // Validate required fields
        if (empty($data['kode'])) {
            $result['errors']['kode'] = 'Kode jurusan wajib diisi';
        }
        if (empty($data['nama'])) {
            $result['errors']['nama'] = 'Nama jurusan wajib diisi';
        }

        if (! empty($result['errors'])) {
            return $result;
        }

        // Check for duplicate kode (case-insensitive after trim)
        $kode = trim((string) ($data['kode'] ?? ''));
        if (Jurusan::whereRaw('LOWER(kode) = ?', [strtolower($kode)])->exists()) {
            $result['errors']['kode'] = 'Kode jurusan sudah terdaftar';

            return $result;
        }

        // Check for duplicate nama (case-insensitive after trim)
        $nama = trim((string) ($data['nama'] ?? ''));
        if (Jurusan::whereRaw('LOWER(nama) = ?', [strtolower($nama)])->exists()) {
            $result['errors']['nama'] = 'Nama jurusan sudah terdaftar';

            return $result;
        }

        DB::transaction(function () use ($kode, $nama, $deskripsi, $aktif, &$result) {
            $jurusan = new Jurusan;
            $jurusan->kode = $kode;
            $jurusan->nama = $nama;
            $jurusan->deskripsi = $deskripsi ?? '';
            $jurusan->aktif = $aktif ?? true;
            $jurusan->save();

            $result['jurusan'] = $jurusan;
            $result['success'] = true;
        });

        return $result;
    }

    public function scopeSearch($query, $value)
    {
        $query->where('idjurusan', 'like', "%{$value}%")
            ->orWhere('kode', 'like', "%{$value}%")
            ->orWhere('nama', 'like', "%{$value}%")
            ->orWhere('deskripsi', 'like', "%{$value}%")
        ;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // log semua kolom
            ->logOnlyDirty() // hanya jika ada perubahan
            ->dontSubmitEmptyLogs();
    }

    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'jurusan_id', 'idjurusan');
    }
}