<?php

namespace App\Http\Requests;

use App\Enums\OfferStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
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
            'deal_id' => ['required', 'exists:deals,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(OfferStatus::class)],
            'issue_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'tax_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
