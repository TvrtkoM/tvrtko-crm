<?php

namespace App\Http\Requests;

use App\Enums\DealStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealRequest extends FormRequest
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
            'company_id' => ['required', 'exists:companies,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'expected_close_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(DealStage::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
