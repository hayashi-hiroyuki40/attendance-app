<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 勤怠記録情報を管理するモデル（attendance_records テーブル）。
 *
 * ユーザーの出勤日、出退勤時刻、備考、ステータス（出勤中/退勤済など）を保持し、
 * ユーザー、休憩時間、修正申請とのリレーションを定義する。
 */
class AttendanceRecord extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
        'status',
    ];

    /**
     * この勤怠レコードを所有するユーザーを取得する（多対1のリレーション）。
     *
     * @return BelongsTo ユーザーモデルへのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この勤怠レコードに紐づく休憩時間のリストを取得する（1対多のリレーション）。
     *
     * @return HasMany 休憩モデルへのリレーション
     */
    public function attendanceBreaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    /**
     * この勤怠レコードに紐づく修正申請のリストを取得する（1対多のリレーション）。
     *
     * @return HasMany 修正申請モデルへのリレーション
     */
    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
