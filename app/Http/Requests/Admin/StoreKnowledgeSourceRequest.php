<?php

namespace App\Http\Requests\Admin;

use App\Models\KnowledgeSource;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKnowledgeSourceRequest extends FormRequest
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
        $maxUploadKb = (($this->route('client')?->plan?->max_file_upload_mb ?? 10) * 1024);

        return [
            'title' => ['required', 'string', 'max:255'],
            'source_type' => ['required', Rule::in(KnowledgeSource::SOURCE_TYPES)],
            'content' => [
                Rule::requiredIf($this->input('source_type') === 'manual'),
                'nullable',
                'string',
                'max:50000',
            ],
            'source_url' => [
                Rule::requiredIf($this->input('source_type') === 'url'),
                'nullable',
                'url:http,https',
                'max:1000',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (!$value) return;
                    $host = parse_url($value, PHP_URL_HOST);
                    if (!$host) return;
                    $ip = gethostbyname($host);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                        $fail("The {$attribute} resolves to a restricted internal network address.");
                    }
                },
            ],
            'source_file' => [
                Rule::requiredIf($this->input('source_type') === 'file'),
                'nullable',
                'file',
                'mimes:pdf,txt,doc,docx',
                'max:'.$maxUploadKb,
            ],
        ];
    }
}
