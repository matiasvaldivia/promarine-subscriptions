<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InterviewQuestion extends Model { protected $guarded=[]; public function answer(){ return $this->hasOne(InterviewAnswer::class)->latestOfMany(); } public function answers(){ return $this->hasMany(InterviewAnswer::class)->latest('answered_at'); } public function section(){ return $this->belongsTo(InterviewSection::class); } }
