<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiktokPost extends Model
{
   protected $fillable = [
        'title',
        'url',
        'image_url'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

}