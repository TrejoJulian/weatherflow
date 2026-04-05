<?php

declare(strict_types=1);

use App\Application\User\GetUser\GetUserHandler;
use App\Application\User\GetUser\GetUserQuery;
use App\Application\User\UserResponse;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use Tests\Unit\Domain\User\FakeUserRepository;

test('returns a user response when user exists', function () {
    $id = UserId::generate();
    $repo = new FakeUserRepository();
    $repo->seed(User::create($id, new Email('john@example.com'), 'John', 'Doe'));

    $response = (new GetUserHandler($repo))->handle(new GetUserQuery($id->value()));

    expect($response)->toBeInstanceOf(UserResponse::class)
        ->and($response->id)->toBe($id->value())
        ->and($response->email)->toBe('john@example.com')
        ->and($response->firstName)->toBe('John')
        ->and($response->lastName)->toBe('Doe');
});

test('throws when user does not exist', function () {
    $repo = new FakeUserRepository();

    (new GetUserHandler($repo))->handle(new GetUserQuery(UserId::generate()->value()));
})->throws(UserNotFoundException::class);
