<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shifts extends Model
{
    protected $fillable = [
        'shift_name',
        'start_time',
        'end_time',
        'description',
    ];
}
