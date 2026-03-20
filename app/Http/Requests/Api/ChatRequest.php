<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_code' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:4000'],
            'session_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
