<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Naptar extends Model
{
    protected $table = 'naptar';
    protected $fillable = [
        'title',
        'date',
        'start_time',
        'end_time',
        'event_type',
        'description',
    ];
}
