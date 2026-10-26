<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rolecat extends Model
{
   use HasFactory;

   protected $table = 'rolecat';
   protected $primaryKey = 'idrolecat';
   protected $fillable = [
      'rolecat',
   ];
   // public $incrementing = false;
// public $timestamps = false;

   public function scopeSearch($query, $value)
   {
      $query->where('idrolecat', 'like', "%{$value}%")
         ->orWhere('rolecat', 'like', "%{$value}%")
      ;
   }


   /*
   public function scopeFilterrolecat($query, $value){
     if (!empty($value)) {$query->where('NAMA_FILTER_RELASI', $value);}
   }

   */
   /*
   public function scopeRangeTanggal($query, $value1, $value2)
   {
      if ($value1 && $value2) {
          $query->whereBetween('tanggal', [$value1, $value2]);
      }
   }
   */


   //contoh relasi untu menampilkan tabel child atau detail 
/*
   public function nama_table_detail()
   {
   return $this->hasMany(nama_model_child::class, 'nama_kolom_relasi_tabel_child', 'nama_kolom_primary_key_table_parent'); 
   }
*/

   //contoh relasi untu mengambil data parent
/*
   public function nama_table_parent()
   {
   return $this->belongsTo(nama_model_parent::class, 'nama_kolom_relasi_tabel_child', 'nama_kolom_primary_key_table_parent'); 
   }
*/

}