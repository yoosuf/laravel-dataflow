<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'runId' => ['required', 'string', 'min:8'],
        ];
    }
}
