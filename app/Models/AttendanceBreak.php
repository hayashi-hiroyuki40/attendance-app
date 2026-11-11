<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 休憩時間情報を管理するモデル（attendance_breaks テーブル）。
 *
 * 各勤怠レコードに紐づく休憩の開始時刻（break_in）および終了時刻（break_out）を保持する。
 */
class AttendanceBreak extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_record_id',
        'break_in',
        'break_out',
    ];

    /**
     * この休憩が属する勤怠レコードを取得する（多対1のリレーション）。
     *
     * @return BelongsTo 勤怠レコードモデルへのリレーション
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
