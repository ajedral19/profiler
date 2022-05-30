<?php
declare(strict_types=1);

function register(object $data, ?object $conn = null): ?User
{
    $fields = [
        'firstname',
        'lastname',
        'email',
        'username',
        'password',
        'repassword',
    ];

    // match password - todo
    // if (is_null(validate_fields($data, $fields))) {
    return User::new_account($data, $conn);
    // }

    return null;
}
