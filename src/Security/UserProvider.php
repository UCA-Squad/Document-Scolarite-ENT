<?php

namespace App\Security;

use App\Logic\LDAP;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;

class UserProvider implements UserProviderInterface
{
	private $ldap;
	private $container;

	public function __construct(LDAP $ldap, ContainerInterface $container)
	{
		$this->ldap = $ldap;
		$this->container = $container;
	}

	public function loadUserByUsername($username)
	{
		if (in_array($username, $this->container->getParameter("admin_users")))
			return new User($username, "xxx", ["ROLE_ADMIN"], true, true, true, true, ["numero" => "1234"]);

		$users = $this->ldap->search("(uid=$username)", "ou=people,", ["CLFDstatus", "memberOf", "CLFDcodeEtu"]);
		$user = current($users);

		if ($user->getAttribute("CLFDstatus")[0] == "0")
			return new User($username, "xxx", ["ROLE_ETUDIANT"], true, true, true, true, ["numero" => $user->getAttribute("CLFDcodeEtu")[0]]);

		if (in_array($this->container->getParameter("ldap")["admin_group"], $user->getAttribute("memberOf")))
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