<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InterviewSection extends Model { protected $guarded=[]; public function questions(){ return $this->hasMany(InterviewQuestion::class)->orderBy('position'); } }
