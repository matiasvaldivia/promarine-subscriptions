<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MockSubscription extends Model { protected $guarded=[]; protected $casts=['metadata_json'=>'array','is_mock'=>'boolean','next_billing_at'=>'datetime','started_at'=>'datetime']; public function payments(){ return $this->hasMany(MockPayment::class); } public function orders(){ return $this->hasMany(MockOrder::class); } public function customer(){ return $this->belongsTo(MockCustomer::class,'customer_id'); } public function plan(){ return $this->belongsTo(SubscriptionPlan::class,'subscription_plan_id'); } }
