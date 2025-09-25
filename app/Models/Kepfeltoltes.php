<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kepfeltoltes extends Model
{
    use HasFactory;

    protected $table = 'kepfeltoltes';

    protected $fillable = [
        'event_type',
        'event_type_img',
    ];
}