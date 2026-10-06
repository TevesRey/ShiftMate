<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestDayRequests extends Model
{
    protected $fillable = [
        'employee_id',
        'current_rest_day',
        'requested_rest_day',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    public function employee()
    {
        return $belongsTo(Employees::class);
    }
    public function user()
    {
        return $belongsTo(User::class);
    }
}
