<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class JobPostingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // A link alone is enough (the page is read for the rest), and so is
        // pasted text alone (the title and company are read from it).
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048', 'required_without:description'],
            'description' => ['nullable', 'string', 'max:50000', 'required_without:source_url'],
        ];
    }
}
