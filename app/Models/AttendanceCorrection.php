<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 勤怠の修正申請情報を管理するモデル（attendance_corrections テーブル）。
 *
 * ユーザーから提出された修正後の出退勤時刻、備考、ステータス（承認待ち/承認済み等）を保持し、
 * ユーザーや元の勤怠レコード、修正用休憩情報とのリレーションを定義する。
 */
class AttendanceCorrection extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_record_id',
        'user_id',
        'clock_in',
        'clock_out',
        'comment',
        'status',
    ];

    /**
     * この修正申請を提出したユーザーを取得する（多対1のリレーション）。
     *
     * @return BelongsTo ユーザーモデルへのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この修正申請の対象となる元の勤怠レコードを取得する（多対1のリレーション）。
     *
     * @return BelongsTo 勤怠レコードモデルへのリレーション
     */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /**
     * この修正申請に紐づく修正用休憩時間のリストを取得する（1対多のリレーション）。
     *
     * @return HasMany 修正休憩モデルへのリレーション
     */
    public function correctionBreaks(): HasMany
    {
        return $this->hasMany(CorrectionBreak::class, 'attendance_correction_id');
    }

    /**
     * 提案された休憩時間のリストを取得する（correctionBreaks のエイリアス）。
     *
     * @return HasMany 修正休憩モデルへのリレーション
     */
    public function proposalBreaks(): HasMany
    {
        return $this->correctionBreaks();
    }
}
