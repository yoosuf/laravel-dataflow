<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MappingPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disk' => ['required', 'string'],
            'path' => ['required', 'string'],
            'format' => ['required', 'in:csv,xlsx,json,ndjson'],
            'sample_size' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
