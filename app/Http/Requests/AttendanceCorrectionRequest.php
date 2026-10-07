<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'new_clock_in'    => ['required'],
            'new_clock_out'   => ['required'],

            'new_break_in'    => ['nullable', 'array'],
            'new_break_in.*'  => ['nullable', 'required_with:new_break_out.*'],

            'new_break_out'   => ['nullable', 'array'],
            'new_break_out.*' => ['nullable', 'required_with:new_break_in.*'],

            'comment'         => ['required', 'string'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'new_break_in' => array_map(fn($v) => $v === '' ? null : $v, $this->input('new_break_in', [])),
            'new_break_out' => array_map(fn($v) => $v === '' ? null : $v, $this->input('new_break_out', [])),
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockInStr  = $this->input('new_clock_in');
            $clockOutStr = $this->input('new_clock_out');

            $clockIn  = $clockInStr ? $this->tryParseTime($clockInStr) : null;
            $clockOut = $clockOutStr ? $this->tryParseTime($clockOutStr) : null;

            if ($clockIn && $clockOut && $clockIn->greaterThanOrEqualTo($clockOut)) {
                $validator->errors()->add('new_clock_in', '出勤時間が不適切な値です');
            }

            $breakIns  = $this->input('new_break_in', []);
            $breakOuts = $this->input('new_break_out', []);

            foreach ($breakIns as $index => $breakInStr) {
                $breakOutStr = $breakOuts[$index] ?? null;

                if (empty($breakInStr) && empty($breakOutStr)) {
                    continue;
                }

                $breakIn  = $breakInStr ? $this->tryParseTime($breakInStr) : null;
                $breakOut = $breakOutStr ? $this->tryParseTime($breakOutStr) : null;

                if ($breakIn) {
                    if (($clockIn && $breakIn->lessThan($clockIn)) || ($clockOut && $breakIn->greaterThan($clockOut))) {
                        $validator->errors()->add("new_break_in.{$index}", '休憩時間が不適切な値です');
                    }
                }

                if ($breakOut) {
                    if ($clockOut && $breakOut->greaterThan($clockOut)) {
                        $validator->errors()->add("new_break_out.{$index}", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }

                if ($breakIn && $breakOut) {
                    if ($breakIn->greaterThanOrEqualTo($breakOut)) {
                        $validator->errors()->add("new_break_in.{$index}", '休憩時間が不適切な値です');
                    }
                }
            }
        });
    }

    private function tryParseTime(?string $timeStr): ?Carbon
    {
        if (empty($timeStr)) {
            return null;
        }

        try {
            return Carbon::parse('2000-01-01 ' . trim($timeStr));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function messages(): array
    {
        return [
            'new_clock_in.required'        => '出勤時間を入力してください',
            'new_clock_out.required'       => '退勤時間を入力してください',

            'new_break_in.*.required_with'  => '休憩時間を入力してください',
            'new_break_out.*.required_with' => '休憩時間を入力してください',

            'comment.required'             => '備考を記入してください',
        ];
    }
}
