<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiktokPost extends Model
{
    protected $fillable = [
     'url', 'image_url'
    ];

}