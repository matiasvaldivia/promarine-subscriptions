<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class FirecrawlService { public function health():array{return Http::timeout(10)->get(rtrim(config('services.firecrawl.url'),'/').'/')->throw()->json();} public function scrape(string $url):array{return Http::timeout(90)->post(rtrim(config('services.firecrawl.url'),'/').'/v1/scrape',['url'=>$url,'formats'=>['markdown','links']])->throw()->json();} }
