<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubscribeUserToStationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'station_id' => ['required', 'string', 'uuid'],
        ];
    }
}