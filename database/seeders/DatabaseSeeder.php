<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder { public function run():void{$this->call([AdminUserSeeder::class,ProductSeeder::class,InterviewSeeder::class,PolicySeeder::class,LandingSeeder::class,MockScenarioSeeder::class]);} }
