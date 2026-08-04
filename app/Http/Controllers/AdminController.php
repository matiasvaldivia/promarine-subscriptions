<?php
namespace App\Http\Controllers;
use App\Models\{IntegrationEvent,InterviewQuestion,MembershipSubscription,MockSubscription,Policy,Product};
class AdminController extends Controller { public function index(){ $total=InterviewQuestion::count();$answered=InterviewQuestion::whereHas('answer')->count();return view('admin.dashboard',compact('total','answered')+['approved'=>Policy::where('status','approved')->count(),'products'=>Product::count(),'subscriptions'=>MockSubscription::count(),'memberships'=>MembershipSubscription::count(),'events'=>IntegrationEvent::latest()->limit(8)->get()]); } }
