<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedules extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'work_date',
        'status',
    ];

    public function user()
    {
        return $belongsTo(User::class);
    }
    public function shifts()
    {
        return $belongsTo(Shifts::class);
    }
}
