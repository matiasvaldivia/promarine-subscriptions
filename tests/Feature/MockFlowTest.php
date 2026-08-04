<?php
namespace Tests\Feature;
use App\Models\{MockSubscription,Product,ProductVariant,SubscriptionPlan};
use App\Services\MockSubscriptionFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
class MockFlowTest extends TestCase { use RefreshDatabase;
 private function subscription():MockSubscription{$p=Product::create(['name'=>'Demo','slug'=>'demo','reference_price'=>100,'subscription_price'=>90]);$v=ProductVariant::create(['product_id'=>$p->id,'name'=>'Botella']);$plan=SubscriptionPlan::create(['product_variant_id'=>$v->id,'name'=>'30 días','amount'=>90]);$customer=DB::table('mock_customers')->insertGetId(['uuid'=>(string)Str::uuid(),'name'=>'Mock','email'=>'mock@invalid.local','is_mock'=>true,'environment'=>'local','created_at'=>now(),'updated_at'=>now()]);return MockSubscription::create(['uuid'=>(string)Str::uuid(),'customer_id'=>$customer,'subscription_plan_id'=>$plan->id,'provider_subscription_id'=>'mock_sub_test','status'=>'active','amount'=>90,'currency'=>'ARS','frequency'=>30,'frequency_type'=>'days','is_mock'=>true,'environment'=>'local']);}
 public function test_rejected_payment_never_creates_order():void{$s=$this->subscription();$result=app(MockSubscriptionFlow::class)->processPayment($s,'rejected','reject-1');$this->assertNull($result['order']);$this->assertDatabaseCount('mock_orders',0);}
 public function test_approved_payment_creates_order_and_igs_event():void{$s=$this->subscription();app(MockSubscriptionFlow::class)->processPayment($s,'approved','approve-1');$this->assertDatabaseCount('mock_orders',1);$this->assertDatabaseCount('mock_igs_events',1);}
 public function test_duplicate_event_is_idempotent():void{$s=$this->subscription();$flow=app(MockSubscriptionFlow::class);$flow->processPayment($s,'approved','same-key');$second=$flow->processPayment($s,'approved','same-key');$this->assertTrue($second['duplicate']);$this->assertDatabaseCount('mock_orders',1);}
 public function test_mock_real_separation_is_explicit():void{$s=$this->subscription();$this->assertTrue($s->is_mock);$this->assertSame('local',$s->environment);}
}
