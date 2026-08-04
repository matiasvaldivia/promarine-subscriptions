<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MockPayment extends Model { protected $guarded=[]; protected $casts=['payload_json'=>'array','is_mock'=>'boolean']; }
