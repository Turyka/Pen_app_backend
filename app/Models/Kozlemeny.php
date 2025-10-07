<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kozlemeny extends Model
{
    protected $table = 'kozlemeny';
    protected $fillable = [
        'title',
        'description',
        'ertesites',
        'type',
        'created',
        'user_id', 
    ];
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
