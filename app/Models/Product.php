<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model { protected $guarded=[]; protected $casts=['enabled'=>'boolean','featured'=>'boolean','is_imported'=>'boolean','is_mock'=>'boolean']; public function variants(){ return $this->hasMany(ProductVariant::class); } }
