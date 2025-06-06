<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postok extends Model
{
    protected $table = 'postok';
    protected $fillable = [
        'title',
        'image',
        'link',
    ];
}
