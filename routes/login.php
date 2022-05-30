<?php
function login(object $data, object $db): ?User
{
    return User::login($data, $db);
}
