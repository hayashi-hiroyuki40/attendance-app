<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'clock_in' => ['required'],
            'clock_out' => ['required', 'after:clock_in'],
            'break_in' => ['nullable', 'array'],
            'break_in.*' => [
                'nullable',
                'required_with:break_out.*',
                'after_or_equal:clock_in',
                'before_or_equal:clock_out',
            ],
            'break_out' => ['nullable', 'array'],
            'break_out.*' => [
                'nullable',
                'required_with:break_in.*',
                'after:break_in.*',
                'before_or_equal:clock_out',
            ],
            'comment' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.required' => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',
            'clock_out.after' => '出勤時間が不適切な値です',

            'break_in.*.required_with' => '休憩時間を入力してください',
            'break_in.*.after_or_equal' => '休憩時間が不適切な値です',
            'break_in.*.before_or_equal' => '休憩時間が不適切な値です',

            'break_out.*.required_with' => '休憩時間を入力してください',
            'break_out.*.after' => '休憩時間が不適切な値です',
            'break_out.*.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',

            'comment.required' => '備考を記入してください',
        ];
    }
}
