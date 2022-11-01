<?php

namespace Vizir\KeycloakWebGuard\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Vizir\KeycloakWebGuard\Contracts\CreateUserInterface;

class KeycloakWebUserProvider implements UserProvider
{
    /**
     * The user model path
     * @var string
     */
    protected string $model;

    /**
     * The user model primary key
     * @var string
     */
    protected string $primaryKey;

    /**
     * The user creator class path
     * @var string
     */
    protected string $userCreator;

    public function __construct(string $model, string $primaryKey, string $userCreator)
    {
        $this->model = $model;
        $this->primaryKey = $primaryKey;
        $this->userCreator = $userCreator;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (!$this->validateCredentialsData($credentials)) {
            return null;
        }

        $class = '\\' . ltrim($this->model, '\\');

        return $class::where($this->primaryKey, $credentials[$this->primaryKey])
            ->firstOr(function () use ($credentials) {
                return $this->createUser($credentials);
            });
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        throw new \BadMethodCallException('Unexpected method [retrieveById] call');
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        throw new \BadMethodCallException('Unexpected method [retrieveByToken] call');
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param Authenticatable $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        throw new \BadMethodCallException('Unexpected method [updateRememberToken] call');
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param Authenticatable $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        throw new \BadMethodCallException('Unexpected method [validateCredentials] call');
    }

    private function validateCredentialsData(array $credentials): bool
    {
        return isset($credentials[$this->primaryKey]);
    }

    private function createUser(array $credentials): ?Authenticatable
    {
        if (!$this->userCreator) {
            return null;
        }

        /** @var Object $userCreatorClass */
        $userCreatorClass = '\\' . ltrim($this->userCreator, '\\');
        $userCreator = new $userCreatorClass();
        if (!($userCreator instanceof CreateUserInterface)) {
            return null;
        }

        return $userCreator::createUser($credentials);
    }
}
