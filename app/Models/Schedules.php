<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedules extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'work_date',
        'start_time',
        'end_time',
        'status',
        'locked_at',
    ];

    /** Time in / time out come from the shift template and are exposed under those names. */
    protected $appends = ['is_locked', 'time_in', 'time_out'];

    protected function casts(): array
    {
        return ['locked_at' => 'datetime'];
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->locked_at !== null;
    }

    public function getTimeInAttribute(): ?string
    {
        return $this->start_time;
    }

    public function getTimeOutAttribute(): ?string
    {
        return $this->end_time;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function shifts()
    {
        // The column is shift_id; without this Laravel guesses "shifts_id" from the method name and every rest-day query fails.
        return $this->belongsTo(Shifts::class, 'shift_id');
    }
}
