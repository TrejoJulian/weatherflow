<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetStationsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['nullable', 'string', 'max:255'],
            'created_from' => ['nullable', 'date'],
            'created_to'   => ['nullable', 'date', 'after_or_equal:created_from'],
        ];
    }
}
