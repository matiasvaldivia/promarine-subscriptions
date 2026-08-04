<?php
namespace Tests\Feature;
use App\Models\{InterviewQuestion,InterviewSection,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class InterviewTest extends TestCase { use RefreshDatabase; protected function setUp():void{parent::setUp();$this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);} public function test_answer_is_persisted_with_history():void{$u=User::create(['name'=>'Tamara','username'=>'tamara','password'=>'secret']);$s=InterviewSection::create(['name'=>'Pagos','slug'=>'pagos']);$q=InterviewQuestion::create(['interview_section_id'=>$s->id,'question'=>'¿Cuándo cobrar?','impact'=>'critical']);$this->actingAs($u)->post('/admin/interview/'.$q->id,['answer'=>'Cada 30 días','comment'=>'Revisar','status'=>'needs_review'])->assertRedirect();$this->assertDatabaseHas('interview_answers',['interview_question_id'=>$q->id,'answer'=>'Cada 30 días']);}
}
