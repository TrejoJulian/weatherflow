<?php

declare(strict_types=1);

use App\Application\User\DeleteUser\DeleteUserCommand;
use App\Application\User\DeleteUser\DeleteUserHandler;
use App\Domain\User\Entities\User;
use App\Domain\User\Exceptions\UserNotFoundException;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use Tests\Unit\Domain\User\FakeUserRepository;

test('deletes an existing user', function () {
    $id = UserId::generate();
    $repo = new FakeUserRepository();
    $repo->seed(User::create($id, new Email('john@example.com'), 'John', 'Doe'));

    (new DeleteUserHandler($repo))->handle(new DeleteUserCommand($id->value()));

    expect($repo->findById($id))->toBeNull();
});

test('throws when user does not exist', function () {
    $repo = new FakeUserRepository();

    (new DeleteUserHandler($repo))->handle(new DeleteUserCommand(UserId::generate()->value()));
})->throws(UserNotFoundException::class);