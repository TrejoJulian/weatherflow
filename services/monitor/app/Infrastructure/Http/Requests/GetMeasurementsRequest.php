<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use App\Domain\Measurement\Enums\AlertType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GetMeasurementsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'station_name'  => ['sometimes', 'string'],
            'temp_min'      => ['sometimes', 'numeric'],
            'temp_max'      => ['sometimes', 'numeric'],
            'alert'         => ['sometimes', Rule::in(['true', 'false', '1', '0'])],
            'alert_type'    => ['sometimes', 'string', Rule::in(array_column(AlertType::cases(), 'value'))],
            'date_from'     => ['sometimes', 'date'],
            'date_to'       => ['sometimes', 'date', 'after_or_equal:date_from'],
            'humidity_min'  => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'humidity_max'  => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'pressure_min'  => ['sometimes', 'numeric'],
            'pressure_max'  => ['sometimes', 'numeric'],
        ];
    }
}
