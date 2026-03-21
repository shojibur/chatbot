<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_monthly' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'monthly_token_limit' => ['required', 'integer', 'min:0'],
            'monthly_message_limit' => ['required', 'integer', 'min:0'],
            'max_knowledge_sources' => ['required', 'integer', 'min:1'],
            'max_file_upload_mb' => ['required', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:200'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
