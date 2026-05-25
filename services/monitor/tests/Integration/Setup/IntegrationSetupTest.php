<?php

declare(strict_types=1);

test('integration setup can create user and station in core', function () {
    $userId    = createUserInCore();
    $stationId = createStationInCore($userId, 'Integration Station');

    expect($userId)->not->toBeEmpty()
        ->and($stationId)->not->toBeEmpty();
});
