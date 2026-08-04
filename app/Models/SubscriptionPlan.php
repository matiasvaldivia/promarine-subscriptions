<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SubscriptionPlan extends Model {
    protected $guarded=[];
    protected $casts=['amount'=>'decimal:2','discount_value'=>'decimal:2','shipping_included'=>'boolean','can_pause'=>'boolean','can_cancel'=>'boolean','enabled'=>'boolean'];
    public function variant(){ return $this->belongsTo(ProductVariant::class,'product_variant_id'); }
}
