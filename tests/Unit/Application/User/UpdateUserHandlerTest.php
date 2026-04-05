<?php

declare(strict_types=1);

use App\Application\User\UpdateUser\UpdateUserCommand;
use App\Application\User\UpdateUser\UpdateUserHandler;
use App\Application\User\UserResponse;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\DuplicateEmailException;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use Tests\Unit\Domain\User\FakeUserRepository;

test('updates a user and returns a response', function () {
    $id = UserId::generate();
    $repo = new FakeUserRepository();
    $repo->seed(User::create($id, new Email('john@example.com'), 'John', 'Doe'));

    $response = (new UpdateUserHandler($repo))->handle(
        new UpdateUserCommand($id->value(), 'updated@example.com', 'Johnny', 'Updated')
    );

    expect($response)->toBeInstanceOf(UserResponse::class)
        ->and($response->email)->toBe('updated@example.com')
        ->and($response->firstName)->toBe('Johnny')
        ->and($response->lastName)->toBe('Updated');
});

test('allows keeping the same email on update', function () {
    $id = UserId::generate();
    $repo = new FakeUserRepository();
    $repo->seed(User::create($id, new Email('john@example.com'), 'John', 'Doe'));

    $response = (new UpdateUserHandler($repo))->handle(
        new UpdateUserCommand($id->value(), 'john@example.com', 'John', 'Updated')
    );

    expect($response->email)->toBe('john@example.com');
});

test('throws when user does not exist', function () {
    $repo = new FakeUserRepository();

    (new UpdateUserHandler($repo))->handle(
        new UpdateUserCommand(UserId::generate()->value(), 'john@example.com', 'John', 'Doe')
    );
})->throws(UserNotFoundException::class);

test('throws when email belongs to another user', function () {
    $repo = new FakeUserRepository();
    $repo->seed(
        User::create(UserId::generate(), new Email('taken@example.com'), 'Jane', 'Smith'),
        User::create($id = UserId::generate(), new Email('john@example.com'), 'John', 'Doe'),
    );

    (new UpdateUserHandler($repo))->handle(
        new UpdateUserCommand($id->value(), 'taken@example.com', 'John', 'Doe')
    );
})->throws(DuplicateEmailException::class);