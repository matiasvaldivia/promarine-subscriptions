<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductVariant extends Model {
    protected $guarded=[];
    protected $casts=['units_per_package'=>'decimal:2','recommended_daily_dose'=>'decimal:2','price'=>'decimal:2','enabled'=>'boolean'];
    public function product(){ return $this->belongsTo(Product::class); }
    public function plans(){ return $this->hasMany(SubscriptionPlan::class); }
}
