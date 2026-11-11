<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 修正申請における休憩時間情報を管理するモデル（correction_breaks テーブル）。
 *
 * ユーザーから提出された勤怠修正申請に紐づく、修正後の休憩開始時刻（break_in）および終了時刻（break_out）を保持する。
 */
class CorrectionBreak extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_correction_id',
        'break_in',
        'break_out',
    ];

    /**
     * この修正休憩が属する勤怠修正申請を取得する（多対1のリレーション）。
     *
     * @return BelongsTo 勤怠修正申請モデルへのリレーション
     */
    public function attendanceCorrection(): BelongsTo
    {
        return $this->belongsTo(AttendanceCorrection::class);
    }
}
