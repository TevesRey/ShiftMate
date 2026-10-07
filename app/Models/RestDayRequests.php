<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestDayRequests extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'rest_day_request';

    protected $fillable = [
        'employee_id',
        'current_rest_day',
        'requested_rest_day',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    public function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employees::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
