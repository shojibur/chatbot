<?php

namespace App\Http\Requests\Admin;

use App\Models\Client;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->user_type === User::TYPE_ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', Rule::exists(Plan::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email:rfc', 'max:255'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'business_description' => ['nullable', 'string', 'max:4000'],
            'system_prompt' => ['nullable', 'string', 'max:4000'],
            'chat_model' => ['required', Rule::in(Client::CHAT_MODELS)],
            'embedding_model' => ['required', Rule::in(Client::EMBEDDING_MODELS)],
            'retrieval_chunk_count' => ['required', 'integer', 'min:1', 'max:8'],
            'cache_ttl_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'prompt_caching_enabled' => ['required', 'boolean'],
            'semantic_cache_enabled' => ['required', 'boolean'],
            'allowed_domains' => ['nullable', 'string', 'max:2000'],
            'monthly_token_limit' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'status' => ['required', Rule::in(Client::STATUSES)],
            'widget_style' => ['required', Rule::in(Client::WIDGET_STYLES)],
            'primary_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'welcome_message' => ['required', 'string', 'max:500'],
            'toggle_text' => ['required', 'string', 'max:50'],
            'position' => ['required', Rule::in(Client::WIDGET_POSITIONS)],
            'theme_mode' => ['required', Rule::in(Client::WIDGET_THEME_MODES)],
            'show_branding' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
