<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
class SecurityAndAuthTest extends TestCase { use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);}
 public function test_suite_is_isolated_from_local_mysql():void{$this->assertSame('testing',app()->environment());$this->assertSame('sqlite',config('database.default'));$this->assertSame(':memory:',config('database.connections.sqlite.database'));}
 public function test_admin_requires_authentication():void{$this->get('/admin')->assertRedirect('/login');}
 public function test_login_is_private_and_not_linked_from_landing():void{$this->get('/login')->assertOk()->assertSee('Hola, Tamara');$this->get('/')->assertOk()->assertDontSee('/login',false)->assertDontSee('Acceso privado de Tamara');$this->get('/admin/login')->assertRedirect('/login');}
 public function test_login_and_password_hashing():void{$u=User::create(['name'=>'Tamara','username'=>'tamara','password'=>'secret-demo']);$this->assertNotSame('secret-demo',$u->getRawOriginal('password'));$this->assertTrue(Hash::check('secret-demo',$u->password));$this->post('/login',['username'=>'tamara','password'=>'secret-demo'])->assertRedirect('/admin');$this->assertAuthenticatedAs($u);}
 public function test_username_ignores_spaces_and_uppercase():void{$u=User::create(['name'=>'Tamara','username'=>'tamara','password'=>'secret-demo']);$this->post('/login',['username'=>'  TAMARA  ','password'=>'secret-demo'])->assertRedirect('/admin');$this->assertAuthenticatedAs($u);}
 public function test_incorrect_login_is_rejected():void{User::create(['name'=>'Tamara','username'=>'tamara','password'=>'correct']);$this->post('/login',['username'=>'tamara','password'=>'wrong'])->assertSessionHasErrors('username');$this->assertGuest();}
 public function test_tamara_can_open_dashboard_and_questionnaires():void{$u=User::create(['name'=>'Tamara','username'=>'tamara','password'=>'secret']);$this->actingAs($u)->get('/admin')->assertOk()->assertSee('Dashboard de Tamara')->assertSee('Abrir cuestionarios');$this->actingAs($u)->get('/admin/interview')->assertOk()->assertSee('Cuestionarios para Tamara');}
 public function test_sensitive_values_are_not_rendered():void{$secret='top-secret-token';config(['services.shopify.token'=>$secret]);$this->get('/')->assertDontSee($secret);}
}
