<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMeasurementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'station_id'           => ['required', 'string', 'uuid'],
            'temperature'          => ['required', 'numeric'],
            'humidity'             => ['required', 'numeric', 'between:0,100'],
            'atmospheric_pressure' => ['required', 'numeric'],
            'reported_at'          => ['required', 'date'],
        ];
    }
}