<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hir extends Model
{
    protected $table = 'hirek';
    protected $fillable = [
        'title',
        'image',
        'link',
    ];
}
