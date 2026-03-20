<?php

namespace App\Http\Requests\Admin;

use App\Models\KnowledgeSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKnowledgeSourceRequest extends FormRequest
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
