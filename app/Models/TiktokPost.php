<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiktokPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'image_url',
    ];

    protected $casts = [
        'image_url' => 'string',
    ];
}