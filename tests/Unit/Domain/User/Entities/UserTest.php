<?php

declare(strict_types=1);

use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;

test('creates a user with correct data', function () {
    $user = User::create(
        UserId::generate(),
        new Email('john@example.com'),
        'John',
        'Doe',
    );

    expect($user->email()->value())->toBe('john@example.com')
        ->and($user->firstName())->toBe('John')
        ->and($user->lastName())->toBe('Doe')
        ->and($user->id())->toBeInstanceOf(UserId::class);
});

test('updates user data', function () {
    $user = User::create(
        UserId::generate(),
        new Email('john@example.com'),
        'John',
        'Doe',
    );

    $user->update(new Email('jane@example.com'), 'Jane', 'Smith');

    expect($user->email()->value())->toBe('jane@example.com')
        ->and($user->firstName())->toBe('Jane')
        ->and($user->lastName())->toBe('Smith');
});

test('id does not change after update', function () {
    $id = UserId::generate();
    $user = User::create($id, new Email('john@example.com'), 'John', 'Doe');

    $user->update(new Email('jane@example.com'), 'Jane', 'Smith');

    expect($user->id()->equals($id))->toBeTrue();
});