<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('patients.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'phone' => ['nullable', 'string', 'max:20'],
            'cnic' => ['nullable', 'string', 'max:20'],
            'dob' => ['nullable', 'date', 'before_or_equal:today'],
            'age_years' => ['nullable', 'integer', 'min:0', 'max:150'],
        ];
    }
}
