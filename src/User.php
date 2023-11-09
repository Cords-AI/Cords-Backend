<?php

namespace App;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    public $firstName;
    public $lastName;
    public $email;

    private $decodedToken;

    public static function create($token): ?User
    {
        $key = file_get_contents(__DIR__ . "/../keycloak.pub");
        try {
            $user = new User();
            $user->decodedToken = JWT::decode($token, new Key($key, 'RS256'));
            $user->firstName = $user->decodedToken->given_name;
            $user->lastName = $user->decodedToken->family_name;
            $user->email = $user->decodedToken->email;
            return $user;
        } catch(\Exception $e) {
            return null;
        }
    }

    public function getRoles(): array
    {
        $roles = [];
        if ($this->decodedToken) {
            $roles[] = 'ROLE_ADMIN';
        }
        return $roles;
    }

    public function eraseCredentials()
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->decodedToken->sub;
    }
}
