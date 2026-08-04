<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('promarine:import', function () {
    $base=rtrim(env('PROMARINE_URL','https://promarineantioxidants.com'),'/');
    $dir=storage_path('app/promarine-imports'); $public=public_path('assets/promarine/products');
    if(!is_dir($dir))mkdir($dir,0775,true); if(!is_dir($public))mkdir($public,0775,true);
    $assets=[];
    foreach(['marine-epic','marine-fusion','echa-marine','marine-pulse'] as $slug){
        $source="$base/products/$slug.js"; $data=Http::timeout(30)->get($source)->throw()->json();
        file_put_contents("$dir/$slug.json",json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $url=str_starts_with($data['featured_image'],'//')?'https:'.$data['featured_image']:$data['featured_image'];
        $bytes=Http::timeout(60)->get($url)->throw()->body(); $ext=pathinfo(parse_url($url,PHP_URL_PATH),PATHINFO_EXTENSION)?:'jpg'; $local="$public/$slug-packshot-square.$ext";file_put_contents($local,$bytes);
        $assets[]=['original_url'=>$url,'local_path'=>'public/assets/promarine/products/'.$slug.'-packshot-square.'.$ext,'type'=>'product_image','sha256'=>hash('sha256',$bytes),'source_page'=>"$base/products/$slug",'status'=>'downloaded'];
    }
    file_put_contents("$dir/manifest.json",json_encode(['source'=>$base,'imported_at'=>now()->toIso8601String(),'assets'=>$assets],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    $this->info('Importación pública completada: '.count($assets).' recursos.');
})->purpose('Importar catálogo y recursos públicos de Promarine sin autenticación');
