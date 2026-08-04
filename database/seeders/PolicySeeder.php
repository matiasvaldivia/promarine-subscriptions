<?php
namespace Database\Seeders;
use App\Models\{Policy,PolicyVersion};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PolicySeeder extends Seeder { public function run():void{$titles=['Condiciones de suscripción','Política de cancelación','Política de precios','Política de envíos','Política de reembolsos','Privacidad','Consentimiento de débito recurrente'];foreach($titles as $title){$slug=Str::slug($title);$categoryId=DB::table('policy_categories')->updateOrInsert(['slug'=>$slug],['name'=>$title,'created_at'=>now(),'updated_at'=>now()]);$cat=DB::table('policy_categories')->where('slug',$slug)->value('id');$policy=Policy::updateOrCreate(['slug'=>$slug],['policy_category_id'=>$cat,'title'=>$title,'status'=>'draft','current_version'=>1]);PolicyVersion::firstOrCreate(['policy_id'=>$policy->id,'version'=>1],['content'=>'Documento preliminar para revisión interna. La respuesta definitiva depende de las decisiones de la entrevista y revisión profesional cuando corresponda.','status'=>'draft']);}} }
