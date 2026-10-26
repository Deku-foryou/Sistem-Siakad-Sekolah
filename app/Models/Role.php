<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
   use HasFactory;

   protected $table = 'role';
   protected $primaryKey = 'idrole';
   protected $fillable = [
      'idrole',
      'rolecat_idrolecat',
      'appmenunew_idappmenunew',
   ];
   // public $incrementing = false;
// public $timestamps = false;

   public function scopeSearch($query, $value)
   {
      $query->where('idrole', 'like', "%{$value}%")
         ->orWhere('rolecat_idrolecat', 'like', "%{$value}%")
         ->orWhere('appmenunew_idappmenunew', 'like', "%{$value}%")
      ;
   }


   /*
   public function scopeFilterrole($query, $value){
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

   public function rolecat()
   {
      return $this->belongsTo(Rolecat::class, 'rolecat_idrolecat', 'idrolecat');
   }

   public function appmenunew()
   {
      return $this->belongsTo(Appmenunew::class, 'appmenunew_idappmenunew', 'idappmenunew');
   }


}