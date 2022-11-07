<?php

namespace SlowCheetah\KeycloakWebGuard\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface SyncUserInterface
{
    public static function sync(Authenticatable $user, array $data): Authenticatable;
}