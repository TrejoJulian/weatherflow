<?php

declare(strict_types=1);

use App\Domain\User\Exceptions\InvalidEmailException;
use App\Domain\User\ValueObjects\Email;

test('accepts a valid email', function () {
    $email = new Email('john.doe@example.com');

    expect($email->value())->toBe('john.doe@example.com');
});

test('normalizes email to lowercase', function () {
    $email = new Email('John.Doe@Example.COM');

    expect($email->value())->toBe('john.doe@example.com');
});

test('throws on invalid email format', function (string $invalid) {
    new Email($invalid);
})->with([
    'missing @' => 'notanemail',
    'missing domain' => 'user@',
    'missing user' => '@domain.com',
    'empty string' => '',
])->throws(InvalidEmailException::class);

test('two emails with the same address are equal', function () {
    expect((new Email('a@b.com'))->equals(new Email('a@b.com')))->toBeTrue();
});

test('two emails with different addresses are not equal', function () {
    expect((new Email('a@b.com'))->equals(new Email('c@d.com')))->toBeFalse();
});