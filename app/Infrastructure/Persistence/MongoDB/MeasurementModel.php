<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MongoDB;

final class MeasurementModel extends BaseMongoModel
{
    protected $table = 'measurements';
    protected $primaryKey = '_id';

    protected $fillable = [
        '_id',
        'station_id',
        'temperature',
        'humidity',
        'atmospheric_pressure',
        'reported_at',
        'alert_status',
        'alert_types',
    ];

    protected $casts = [
        'temperature'          => 'float',
        'humidity'             => 'float',
        'atmospheric_pressure' => 'float',
        'alert_status'         => 'boolean',
    ];
}