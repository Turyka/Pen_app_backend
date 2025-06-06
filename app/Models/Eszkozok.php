<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Eszkozok extends Model
{
    protected $table = 'adat_eszkozok';
    protected $fillable = ['device_id', 'device', 'os', 'app_version'];
}
