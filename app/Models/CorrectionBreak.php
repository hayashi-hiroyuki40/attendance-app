<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correction_id',
        'break_in',
        'break_out',
    ];

    public function attendanceCorrection()
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}
