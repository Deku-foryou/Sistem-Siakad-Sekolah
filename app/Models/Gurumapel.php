<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gurumapel extends Model
{
    protected $table = 'gurumapel';
    protected $primaryKey = 'idgurumapel';
    protected $fillable = ['guru_idguru', 'mapel_idmapel'];
}
