<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Hash};
use RuntimeException;
class AdminUserSeeder extends Seeder { public function run():void{$username=env('TAMARA_USERNAME');$password=env('TAMARA_PASSWORD');if(blank($username)||blank($password))throw new RuntimeException('TAMARA_USERNAME y TAMARA_PASSWORD son obligatorios para crear el acceso privado.');$user=User::updateOrCreate(['username'=>$username],['name'=>'Tamara','password'=>Hash::make($password),'failed_login_attempts'=>0,'locked_until'=>null]);$role=DB::table('roles')->updateOrInsert(['name'=>'decision_owner'],['updated_at'=>now(),'created_at'=>now()]);$roleId=DB::table('roles')->where('name','decision_owner')->value('id');DB::table('role_user')->updateOrInsert(['role_id'=>$roleId,'user_id'=>$user->id]);} }
