<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Napilogin extends Model
{
    protected $table = 'napi_login';
    protected $fillable = ['device_id', 'datetime','fcm_token'];
}