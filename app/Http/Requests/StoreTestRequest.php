<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\ResultType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalog.manage');
    }

    public function rules(): array
    {
        $testId = $this->route('test')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable', 'string', 'max:30',
                Rule::unique('tests', 'code')->ignore($testId)->whereNull('deleted_at'),
            ],
            'test_category_id' => ['nullable', 'integer', Rule::exists('test_categories', 'id')],
            'specimen_type' => ['nullable', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_panel' => ['required', 'boolean'],

            'parameters' => ['required', 'array', 'min:1'],
            'parameters.*.id' => ['nullable', 'integer'],
            'parameters.*.name' => ['required', 'string', 'max:255'],
            'parameters.*.unit' => ['nullable', 'string', 'max:30'],
            'parameters.*.result_type' => ['required', Rule::enum(ResultType::class)],
            'parameters.*.decimals' => ['required', 'integer', 'min:0', 'max:4'],
            'parameters.*.options' => ['nullable', 'array'],
            'parameters.*.options.*' => ['nullable', 'string', 'max:100'],

            'parameters.*.ranges' => ['nullable', 'array'],
            'parameters.*.ranges.*.gender' => ['nullable', Rule::in(['all', ...array_column(Gender::cases(), 'value')])],
            'parameters.*.ranges.*.age_min_years' => ['nullable', 'integer', 'min:0', 'max:150'],
            'parameters.*.ranges.*.age_max_years' => ['nullable', 'integer', 'min:0', 'max:150'],
            'parameters.*.ranges.*.low' => ['nullable', 'numeric'],
            'parameters.*.ranges.*.high' => ['nullable', 'numeric'],
            'parameters.*.ranges.*.critical_low' => ['nullable', 'numeric'],
            'parameters.*.ranges.*.critical_high' => ['nullable', 'numeric'],
            'parameters.*.ranges.*.normal_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $parameters = $this->input('parameters', []);

            // A single-value test is exactly one parameter; a panel needs a name per row.
            if (! $this->boolean('is_panel') && count($parameters) !== 1) {
                $validator->errors()->add('parameters', 'A single-value test must have exactly one parameter.');
            }

            foreach ($parameters as $i => $parameter) {
                if (($parameter['result_type'] ?? null) === ResultType::Select->value) {
                    $options = array_filter($parameter['options'] ?? [], fn ($o) => trim((string) $o) !== '');
                    if (count($options) < 2) {
                        $validator->errors()->add("parameters.{$i}.options", 'A select parameter needs at least two options.');
                    }
                }

                foreach ($parameter['ranges'] ?? [] as $j => $range) {
                    $low = $range['low'] ?? null;
                    $high = $range['high'] ?? null;
                    if ($low !== null && $low !== '' && $high !== null && $high !== '' && (float) $low > (float) $high) {
                        $validator->errors()->add("parameters.{$i}.ranges.{$j}.high", 'Max must be greater than or equal to min.');
                    }
                }
            }
        });
    }
}
