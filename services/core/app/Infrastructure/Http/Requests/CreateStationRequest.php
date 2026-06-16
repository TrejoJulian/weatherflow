<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use App\Domain\WeatherStation\Enums\ClimateProviderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateStationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing(['climate_provider' => ClimateProviderType::OpenWeather->value]);
    }

    public function rules(): array
    {
        return [
            'owner_id'         => ['required', 'string', 'uuid'],
            'station_name'     => ['required', 'string', 'max:255'],
            'latitude'         => ['required', 'numeric', 'between:-90,90'],
            'longitude'        => ['required', 'numeric', 'between:-180,180'],
            'sensor_model'     => ['required', 'string', 'max:255'],
            'status'           => ['nullable', 'string', 'in:active,inactive'],
            'climate_provider' => ['required', 'string', Rule::in(ClimateProviderType::values())],
        ];
    }
}