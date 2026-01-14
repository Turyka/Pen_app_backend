<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookPost extends Model
{
    protected $fillable = [
        'title', 'url', 'image_url'
    ];

}