<?php
namespace App\Services;

use App\Models\{IntegrationEvent,MockOrder,MockPayment,MockSubscription};
use Illuminate\Support\Facades\DB;

class MockSubscriptionFlow {
    public function __construct(private ShopifyGatewayInterface $shopify, private IGSGatewayInterface $igs) {}
    public function processPayment(MockSubscription $subscription, string $outcome, string $idempotencyKey): array {
        return DB::transaction(function () use ($subscription,$outcome,$idempotencyKey) {
            $payment=MockPayment::firstOrCreate(['idempotency_key'=>$idempotencyKey],[
                'mock_subscription_id'=>$subscription->id,'provider_payment_id'=>'mock_payment_'.bin2hex(random_bytes(6)),
                'status'=>$outcome,'amount'=>$subscription->amount,'currency'=>$subscription->currency,
                'is_mock'=>true,'environment'=>'local','payload_json'=>['sanitized'=>true],
            ]);
            if (!$payment->wasRecentlyCreated) return ['payment'=>$payment,'order'=>MockOrder::where('mock_payment_id',$payment->id)->first(),'duplicate'=>true];
            $subscription->update(['status'=>$outcome==='approved'?'payment_approved':'payment_rejected']);
            IntegrationEvent::create(['event_id'=>'mock_evt_'.bin2hex(random_bytes(6)),'event_type'=>'payment.'.$outcome,'integration'=>'mercadopago','status'=>'processed','is_mock'=>true,'environment'=>'local','payload_json'=>['payment_id'=>$payment->provider_payment_id,'amount'=>$payment->amount,'currency'=>$payment->currency]]);
            if ($outcome!=='approved') return ['payment'=>$payment,'order'=>null,'duplicate'=>false];
            $result=$this->shopify->createPaidOrder(['subscription_id'=>$subscription->provider_subscription_id,'amount'=>$subscription->amount]);
            $order=MockOrder::create(['mock_subscription_id'=>$subscription->id,'mock_payment_id'=>$payment->id,'shopify_order_id'=>$result->id,'status'=>'created','total'=>$subscription->amount,'is_mock'=>true,'environment'=>'local']);
            $igs=$this->igs->registerSale(['order_id'=>$order->shopify_order_id,'influencer_code'=>$subscription->influencer_code]);
            DB::table('mock_igs_events')->insert(['mock_order_id'=>$order->id,'event_id'=>$igs->id,'type'=>'igs.sale.created','status'=>'recorded','commission'=>round($subscription->amount*0.10,2),'is_mock'=>true,'environment'=>'local','payload_json'=>json_encode(['sanitized'=>true]),'created_at'=>now(),'updated_at'=>now()]);
            IntegrationEvent::create(['event_id'=>'mock_evt_'.bin2hex(random_bytes(6)),'event_type'=>'shopify.order.created','integration'=>'shopify','status'=>'processed','is_mock'=>true,'environment'=>'local','payload_json'=>['order_id'=>$order->shopify_order_id]]);
            return ['payment'=>$payment,'order'=>$order,'igs_id'=>$igs->id,'duplicate'=>false];
        });
    }
}
