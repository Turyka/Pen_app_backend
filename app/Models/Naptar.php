<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Naptar extends Model
{
    protected $table = 'naptar';
    protected $fillable = [
        'title',
        'date',
        'status',
        'start_time',
        'end_time',
        'event_type',
        'created',
        'edited',
        'description',
    ];
    public function getFormattedStartTimeAttribute()
    {
    return Carbon::createFromFormat('H:i:s', $this->start_time)->format('H:i');
    }

    public function getFormattedEndTimeAttribute()
    {
        return Carbon::createFromFormat('H:i:s', $this->end_time)->format('H:i');
    }
}
