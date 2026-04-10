<?php

namespace App\Http\Requests;

use App\Models\MisoAccomplishment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MisoAccomplishmentRequest extends FormRequest
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
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'category' => [
                'required',
                'string',
                Rule::in([
                    MisoAccomplishment::CATEGORY_DATA_MANAGEMENT,
                    MisoAccomplishment::CATEGORY_NETWORK,
                    MisoAccomplishment::CATEGORY_SYSTEMS_DEVELOPMENT,
                ]),
            ],
            'record_no' => ['nullable', 'string', 'max:50'],
            'project_title' => ['required', 'string', 'max:2000'],
            'project_lead' => ['nullable', 'string', 'max:1000'],
            'project_members' => ['nullable', 'string'],
            'budget_cost' => ['nullable', 'string', 'max:255'],
            'implementing_unit' => ['nullable', 'string', 'max:1000'],
            'target_activities' => ['nullable', 'string'],
            'intended_duration' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'string', 'max:255'],
            'target_end_date' => ['nullable', 'string', 'max:255'],
            'reporting_period' => ['nullable', 'string', 'max:255'],
            'completion_percentage' => ['nullable', 'string', 'max:255'],
            'overall_status' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
