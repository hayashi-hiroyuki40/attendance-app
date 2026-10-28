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

class AttendanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
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
                $q->whereLike('date', "{$month}%");
            })
            ->latest('date');

        $records = $query->paginate($perPage);

        return AttendanceRecordResource::collection($records);
    }

    /**
     * Store a newly created resource in storage.
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
     * Display the specified resource.
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'attendanceBreaks', 'attendanceCorrections']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update($request->validated());
        $attendanceRecord->load(['user', 'attendanceBreaks']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->json(null, 204);
    }
}
