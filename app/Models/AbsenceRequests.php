<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceRequests extends Model
{
    protected $fillable = [
        'employee_id',
        'schedule_id',
        'absence_date',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    public function employee()
    {
        return $this->belongsTo(Employees::class);
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class);
    }
}
