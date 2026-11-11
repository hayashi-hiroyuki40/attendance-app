<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\Api\V1\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * 公開API v1 — 勤怠データの取得・操作を JSON で提供する。
 *
 * 読み取り系（index, show）は認証不要。
 * 書き込み系（store, update, destroy）は Sanctum 認証必須、
 * かつ Policy で本人のみが更新・削除できる。
 */
class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧を取得する。
     *
     * クエリパラメータで user_id / date / month による絞り込み、
     * page / per_page によるページネーションが可能。
     *
     * @param  IndexAttendanceRecordRequest  $request  バリデーション済みのリクエスト
     * @return AnonymousResourceCollection 勤怠リソースのコレクション（ページネーション付き）
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 20);

        $query = AttendanceRecord::with(['user', 'attendanceBreaks'])
            ->when($request->input('user_id'), function ($q, $userId) {
                $q->where('user_id', $userId);
            })
            ->when($request->input('date'), function ($q, $date) {
                $q->where('date', $date);
            })
            ->when($request->input('month'), function ($q, $month) {
                $q->where('date', 'like', "{$month}%");
            })
            ->latest('date');

        $records = $query->paginate($perPage);

        return AttendanceRecordResource::collection($records);
    }

    /**
     * 勤怠を新規登録する。
     *
     * 認証ユーザー（Sanctum）に紐づく勤怠レコードを新規作成する。
     *
     * @param  StoreAttendanceRecordRequest  $request  新規登録用のバリデーション済みリクエスト
     * @return JsonResponse 作成された勤怠リソース（ステータスコード 201）
     */

    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $attendanceRecord = $request->user()->attendanceRecords()->create($request->validated());
        $attendanceRecord->load(['user', 'attendanceBreaks']);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 指定 ID の勤怠詳細を取得する。
     *
     * 関連する user / attendanceBreaks / attendanceCorrections を含めて返却する。
     *
     * @param  AttendanceRecord  $attendanceRecord  対象の勤怠モデル（ルートモデルバインディング）
     * @return AttendanceRecordResource 指定された勤怠リソース
     */

    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'attendanceBreaks', 'attendanceCorrections']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定 ID の勤怠を更新する。
     *
     * Policy により、本人のみが更新できる。
     *
     * @param  UpdateAttendanceRecordRequest  $request  更新用のバリデーション済みリクエスト
     * @param  AttendanceRecord               $attendanceRecord 対象の勤怠モデル
     * @return AttendanceRecordResource 更新された勤怠リソース
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($request->validated());
        $attendanceRecord->load(['user', 'attendanceBreaks']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定 ID の勤怠を削除する。
     *
     * Policy により、本人のみが削除できる。
     *
     * @param  AttendanceRecord  $attendanceRecord  対象の勤怠モデル
     * @return JsonResponse 204 No Content
     */
    public function destroy(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->json(null, 204);
    }
}
