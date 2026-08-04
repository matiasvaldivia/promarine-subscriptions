<?php
namespace App\Http\Controllers;
use App\Models\MockSubscription;
use App\Services\MockSubscriptionFlow;
use Illuminate\Http\Request;
class SimulatorController extends Controller { public function index(){return view('admin.simulator',['subscriptions'=>MockSubscription::latest()->get()]);} public function run(Request $r,MockSubscriptionFlow $flow){$d=$r->validate(['subscription_id'=>'required|exists:mock_subscriptions,id','outcome'=>'required|in:approved,rejected']);$sub=MockSubscription::findOrFail($d['subscription_id']);$result=$flow->processPayment($sub,$d['outcome'],'sim-'.$sub->uuid.'-'.$d['outcome'].'-'.$r->input('cycle','1'));return back()->with('simulation',collect($result)->except(['payment','order'])->merge(['payment_id'=>$result['payment']->provider_payment_id,'order_id'=>$result['order']?->shopify_order_id])->toJson(JSON_PRETTY_PRINT));} }
