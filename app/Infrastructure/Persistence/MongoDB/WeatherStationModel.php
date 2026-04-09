<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MongoDB;

final class WeatherStationModel extends BaseMongoModel
{
    protected $table = 'weather_stations';
    protected $primaryKey = '_id';

    protected $fillable = [
        '_id',
        'owner_id',
        'name',
        'location',
        'sensor_model',
        'status',
    ];

    protected $casts = [
        'location' => 'array',
    ];
}