<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shifts extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'shift_name',
        'start_time',
        'end_time',
        'description',
    ];
}
