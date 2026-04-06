<?php

declare(strict_types=1);

use App\Application\User\GetAllUsers\GetAllUsersHandler;
use App\Application\User\GetAllUsers\GetAllUsersQuery;
use App\Application\User\UserResponse;
use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use Tests\Unit\Domain\User\FakeUserRepository;

test('returns empty array when no users exist', function () {
    $repo = new FakeUserRepository();

    $result = (new GetAllUsersHandler($repo))->handle(new GetAllUsersQuery());

    expect($result)->toBeEmpty();
});

test('returns all users as responses', function () {
    $repo = new FakeUserRepository();
    $repo->seed(
        User::create(UserId::generate(), new Email('john@example.com'), 'John', 'Doe'),
        User::create(UserId::generate(), new Email('jane@example.com'), 'Jane', 'Smith'),
    );

    $result = (new GetAllUsersHandler($repo))->handle(new GetAllUsersQuery());

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(UserResponse::class)
        ->and($result[1])->toBeInstanceOf(UserResponse::class);
});
