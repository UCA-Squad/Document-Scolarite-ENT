<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private $username;
    private $roles;
    private $email;
    private $numero;

    private $casAttributes = [];

    public function __construct(string $username, array $roles, string $email = "", string $numero = "")
    {
        $this->username = $username;
        $this->roles = $roles;
        $this->email = $email;
        $this->numero = $numero;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function __call($name, $arguments)
    {
        return $this->username;
    }

    public function getCasAttributes(): array
    {
        return $this->casAttributes;
    }

    public function setCasAttributes(array $casAttributes): void
    {
        $this->casAttributes = $casAttributes;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

}
