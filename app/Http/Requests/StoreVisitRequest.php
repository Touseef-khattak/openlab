<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('visits.manage');
    }

    public function rules(): array
    {
        return [
            'patient.id' => ['nullable', 'integer', Rule::exists('patients', 'id')->whereNull('deleted_at')],
            'patient.name' => ['required_without:patient.id', 'nullable', 'string', 'max:255'],
            'patient.gender' => ['required_without:patient.id', 'nullable', Rule::enum(Gender::class)],
            'patient.phone' => ['nullable', 'string', 'max:20'],
            'patient.cnic' => ['nullable', 'string', 'max:20'],
            'patient.dob' => ['nullable', 'date', 'before_or_equal:today'],
            'patient.age_years' => ['nullable', 'integer', 'min:0', 'max:150'],
            'doctor_id' => ['nullable', 'integer', Rule::exists('doctors', 'id')->whereNull('deleted_at')],
            'test_ids' => ['required', 'array', 'min:1'],
            'test_ids.*' => ['integer', Rule::exists('tests', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
