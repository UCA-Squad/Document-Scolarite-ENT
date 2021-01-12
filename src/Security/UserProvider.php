<?php

namespace App\Security;

use App\Logic\LDAP;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;

class UserProvider implements UserProviderInterface
{
	private $ldap;
	private $params;

	public function __construct(LDAP $ldap, ParameterBagInterface $params)
	{
		$this->ldap = $ldap;
		$this->params = $params;
	}

	public function loadUserByUsername($username)
	{
		$admins = $this->params->get("admin_users");
		$code = $this->params->get("ldap_code");
		$affi = $this->params->get("ldap_affiliation");
		$affi_student = $this->params->get("ldap_affiliation_student");

		if (in_array($username, $admins))
			return new User($username, "xxx", ["ROLE_ADMIN"], true, true, true, true, ["numero" => "1234"]);

		$users = $this->ldap->search("(uid=$username)", "ou=people,", [$affi, "memberOf", $code]);
		$user = current($users);

		if ($user->getAttribute($affi)[0] == $affi_student)
			return new User($username, "xxx", ["ROLE_ETUDIANT"], true, true, true, true, ["numero" => $user->getAttribute($code)[0]]);

		if (in_array($this->params->get("ldap")["admin_group"], $user->getAttribute("memberOf")))
			return new User($username, "xxx", ["ROLE_SCOLA"], true, true, true, true, ["numero" => "1234"]);

		return new User($username, "xxx", ['ROLE_ANONYMOUS']);
	}

	public function refreshUser(UserInterface $user)
	{
		return $this->loadUserByUsername($user->getUsername());
	}

	public function supportsClass($class)
	{
		return $class === 'Symfony\Component\Security\Core\User\User';
	}
}