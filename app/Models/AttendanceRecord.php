<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceBreaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
