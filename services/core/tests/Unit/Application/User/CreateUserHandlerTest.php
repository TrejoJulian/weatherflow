<?php

declare(strict_types=1);

use App\Application\User\CreateUser\CreateUserCommand;
use App\Application\User\CreateUser\CreateUserHandler;
use App\Application\User\UserResponse;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\DuplicateEmailException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use Tests\Unit\Domain\User\FakeUserRepository;

test('creates a user and returns a response', function () {
    $repo = new FakeUserRepository();
    $handler = new CreateUserHandler($repo);

    $response = $handler->handle(new CreateUserCommand('john@example.com', 'John', 'Doe'));

    expect($response)->toBeInstanceOf(UserResponse::class)
        ->and($response->email)->toBe('john@example.com')
        ->and($response->firstName)->toBe('John')
        ->and($response->lastName)->toBe('Doe')
        ->and($response->id)->not->toBeEmpty();
});

test('persists the user via the repository', function () {
    $repo = new FakeUserRepository();
    $handler = new CreateUserHandler($repo);

    $response = $handler->handle(new CreateUserCommand('john@example.com', 'John', 'Doe'));

    expect($repo->findById(UserId::fromString($response->id)))->toBeInstanceOf(User::class);
});

test('throws when email is already in use', function () {
    $repo = new FakeUserRepository();
    $repo->seed(User::create(UserId::generate(), new Email('john@example.com'), 'John', 'Doe'));

    $handler = new CreateUserHandler($repo);

    $handler->handle(new CreateUserCommand('john@example.com', 'Jane', 'Smith'));
})->throws(DuplicateEmailException::class);
