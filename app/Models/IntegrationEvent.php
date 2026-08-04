<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IntegrationEvent extends Model { protected $guarded=[]; protected $casts=['payload_json'=>'array','is_mock'=>'boolean']; }
