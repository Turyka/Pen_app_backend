<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kozlemeny extends Model
{
    protected $table = 'Kozlemeny';
    protected $fillable = [
        'title',
        'description',
        'ertesites',
        'created',
    ];
}
