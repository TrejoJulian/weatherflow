<?php

declare(strict_types=1);

use App\Domain\WeatherStation\ValueObjects\Location;
use App\Infrastructure\Http\Clients\OpenWeatherProvider;
use Illuminate\Http\Client\ConnectionException;
use Tests\Unit\Infrastructure\Resilience\GaneshaTestDoubles;

const SOME_LOCATION = [-34.9205, -58.3838];

beforeEach(function () {
    config(['services.openweather.key' => 'test-key']);
});

test('connect timeout aborts fast when the host is unreachable, without waiting the response timeout', function () {
    // 192.0.2.1 is RFC 5737 TEST-NET-1: routable-looking but black-holed, so the TCP
    // handshake never completes and the connect timeout is what fires.
    config([
        'services.openweather.base_url'           => 'http://192.0.2.1',
        'services.resilience.owm_connect_timeout' => 1,
        'services.resilience.owm_timeout'         => 20,
    ]);

    $start = microtime(true);

    expect(fn () => (new OpenWeatherProvider(GaneshaTestDoubles::alwaysAvailable()))->fetchCurrentReading(new Location(...SOME_LOCATION)))
        ->toThrow(ConnectionException::class);

    $elapsed = microtime(true) - $start;

    // Governed by the 1s connect timeout, nowhere near the 20s response timeout.
    expect($elapsed)->toBeLessThan(6.0);
});

test('response timeout aborts a slow server even though the connection opened fine', function () {
    // Local server that accepts the connection (connect succeeds instantly) but never
    // answers, so the only thing that can cut the call is the response timeout.
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    expect($server)->not->toBeFalse();
    $port = (int) explode(':', stream_socket_get_name($server, false))[1];

    $childPid = pcntl_fork();
    if ($childPid === 0) {
        $connection = stream_socket_accept($server, 30);
        sleep(15); // hang well past the response timeout, never reply
        exit(0);
    }

    fclose($server); // parent keeps no handle on the listening socket

    config([
        'services.openweather.base_url'           => "http://127.0.0.1:{$port}",
        'services.resilience.owm_connect_timeout' => 5,
        'services.resilience.owm_timeout'         => 1,
    ]);

    try {
        $start = microtime(true);

        expect(fn () => (new OpenWeatherProvider(GaneshaTestDoubles::alwaysAvailable()))->fetchCurrentReading(new Location(...SOME_LOCATION)))
            ->toThrow(ConnectionException::class);

        $elapsed = microtime(true) - $start;

        // Governed by the 1s response timeout: well under the 5s connect timeout,
        // which proves the connection opened and it was the response that timed out.
        expect($elapsed)->toBeLessThan(4.0);
    } finally {
        posix_kill($childPid, SIGKILL);
        pcntl_wait($status);
    }
})->skip(
    fn () => ! function_exists('pcntl_fork') || ! function_exists('posix_kill'),
    'pcntl/posix extensions required for the slow-server test',
);
