<?php

namespace Vizir\KeycloakWebGuard\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface CreateUserInterface
{
    public static function createUser(array $data): Authenticatable;
}