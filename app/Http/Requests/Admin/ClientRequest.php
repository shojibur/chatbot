<?php

namespace App\Http\Requests\Admin;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'monthly_token_limit' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'status' => ['required', Rule::in(Client::STATUSES)],
            'widget_style' => ['required', Rule::in(Client::WIDGET_STYLES)],
            'primary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'welcome_message' => ['required', 'string', 'max:500'],
            'position' => ['required', Rule::in(Client::WIDGET_POSITIONS)],
            'show_branding' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
