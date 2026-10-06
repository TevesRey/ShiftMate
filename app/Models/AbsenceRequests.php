<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceRequests extends Model
{
    protected $fillable = [
        'employee_id',
        'schdule_id',
        'absence_date',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    public function employee()
    {
        return $belongsTo(Employee::class);
    }
    public function reviewed_at()
    {
        return $belongsTo(User::class);
    }
}
