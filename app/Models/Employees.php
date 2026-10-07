<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employees extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'position',
        'department',
        'contact_number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
