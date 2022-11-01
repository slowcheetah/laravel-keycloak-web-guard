<?php

namespace Vizir\KeycloakWebGuard\Contracts;

interface CreateUserInterface
{
    public static function createUser(array $data);
}