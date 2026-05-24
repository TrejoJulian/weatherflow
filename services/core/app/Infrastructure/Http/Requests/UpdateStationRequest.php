<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateStationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'owner_id'     => ['required', 'string', 'uuid'],
            'station_name' => ['required', 'string', 'max:255'],
            'latitude'     => ['required', 'numeric', 'between:-90,90'],
            'longitude'    => ['required', 'numeric', 'between:-180,180'],
            'sensor_model' => ['required', 'string', 'max:255'],
            'status'       => ['required', 'string', 'in:active,inactive'],
        ];
    }
}