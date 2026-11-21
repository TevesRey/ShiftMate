<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestDayAssignment extends Model
{
    protected $fillable = ['user_id', 'rest_day', 'assigned_by'];

    protected function casts(): array
    {
        return ['rest_day' => 'date:Y-m-d'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
