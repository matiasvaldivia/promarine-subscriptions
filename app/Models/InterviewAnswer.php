<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InterviewAnswer extends Model { protected $guarded=[]; protected $casts=['answered_at'=>'datetime']; }
