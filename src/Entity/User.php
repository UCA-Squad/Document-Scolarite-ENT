<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;
    #[ORM\Column(type: 'string', length: 25, unique: true)]
    private string $username;
    private array $roles;
    private string $email;
    private ?string $numero = null;
    private array $casAttributes = [];

    #[ORM\Column(type: 'string', length: 255)]
    private string $prenom;
    #[ORM\Column(type: 'string', length: 255)]
    private string $nom;

    #[ORM\Column(type: 'string', length: 25)]
    private string $composante;

    private string $codeComposante;

    private bool $old;

    public function __construct(string $username, array $roles, string $email = "", string $numero = "")
    {
        $this->username = $username;
        $this->roles = $roles;
        $this->email = $email;
        $this->numero = $numero;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /*public function __call($name, $arguments)
    {
        return $this->username;
    }*/

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

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getComposante(): string
    {
        return $this->composante;
    }

    public function setComposante(string $composante): self
    {
        $this->composante = $composante;

        return $this;
    }

    public function isOld(): bool
    {
        return $this->old;
    }

    public function setOld(bool $old): self
    {
        $this->old = $old;

        return $this;
    }

    public function getCodeComposante(): string
    {
        return $this->codeComposante;
    }

    public function setCodeComposante(string $codeComposante): self
    {
        $this->codeComposante = $codeComposante;

        return $this;
    }

}
