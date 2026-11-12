<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hasil satu kali pemeriksaan rekonsiliasi (bukan ledger keuangan).
 */
class RekonsiliasiHasil extends Model
{
    protected $table = 'rekonsiliasi_hasil';

    protected $fillable = [
        'periode',
        'dijalankan_oleh',
        'dijalankan_pada',
        'jumlah_pemeriksaan',
        'jumlah_temuan',
        'jumlah_kritis',
        'ringkasan',
    ];

    protected $casts = [
        'dijalankan_pada' => 'datetime',
        'ringkasan' => 'array',
        'jumlah_pemeriksaan' => 'integer',
        'jumlah_temuan' => 'integer',
        'jumlah_kritis' => 'integer',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dijalankan_oleh', 'id');
    }

    public function temuan(): HasMany
    {
        return $this->hasMany(RekonsiliasiTemuan::class, 'rekonsiliasi_hasil_id', 'id');
    }
}
