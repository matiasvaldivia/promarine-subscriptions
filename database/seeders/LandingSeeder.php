<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LandingSeeder extends Seeder { public function run():void{foreach(['hero'=>['title'=>'Tu Promarine, todos los meses','subtitle'=>'Recibí tu producto automáticamente y mantené la continuidad de tu rutina.'],'mode'=>['payments'=>'mock','shopify'=>'mock','igs'=>'mock']] as $key=>$value)DB::table('landing_settings')->updateOrInsert(['key'=>$key],['value_json'=>json_encode($value),'created_at'=>now(),'updated_at'=>now()]);} }
