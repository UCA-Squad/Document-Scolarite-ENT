<?php

namespace App\Security;

use App\Logic\LDAP;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private $ldap;
    private $params;

    public function __construct(LDAP $ldap, ParameterBagInterface $params)
    {
        $this->ldap = $ldap;
        $this->params = $params;
    }

    public function loadUserByIdentifier($identifier): UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }

    public function loadUserByUsername($username): UserInterface
    {
        $admins = $this->params->get("admin_users");
        $code = $this->params->get("ldap_code");
        $affi = $this->params->get("ldap_affiliation");
        $affi_student = $this->params->get("ldap_affiliation_student");

        $users = $this->ldap->search("(uid=$username)", "ou=people,", [$affi, "memberOf", $code, "mail"]);
        $user = current($users);

        // Si l'utilisateur est admin
        if (in_array($username, $admins)) {
            $mail = current($user->getAttribute('mail'));
            return new User($username, ["ROLE_ADMIN"], $mail);
        }

        // Si l'utilisateur est blacklisté
        if ($user->hasAttribute("memberOf") && in_array($this->params->get("ldap")["bl_group"], $user->getAttribute("memberOf"))) {
            return new User($username, ['ROLE_ANONYMOUS']);
        }

        // Si l'utilisateur est un étudiant
        if (in_array($affi_student, $user->getAttribute($affi))) {
            $numero = current($user->getAttribute($code));
            return new User($username, ["ROLE_ETUDIANT"], "", $numero);
        }

        // Si l'utilisateur fait parti du groupe LDAP gestionnaire
        if ($user->hasAttribute("memberOf") && in_array($this->params->get("ldap")["admin_group"], $user->getAttribute("memberOf"))) {
            $mail = current($user->getAttribute('mail'));
            return new User($username, ["ROLE_SCOLA"], $mail);
        }

        return new User($username, ['ROLE_ANONYMOUS']);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }
}