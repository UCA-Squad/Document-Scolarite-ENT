<?php

namespace App\Logic;


use Symfony\Component\Ldap\Entry;

/**
 * Classe permetttant d'intéragir avec le LDAP configuré.
 */
class LDAP
{
	private $ldapParams;

	public function __construct(array $ldapParams)
	{
		$this->ldapParams = $ldapParams;
	}

	/**
	 * Récupère un tableau d'étudiant du LDAP.
	 * @param string $query
	 * @param string $dn
	 * @param array $fields Permet de filtrer le résultat
	 * @return Entry[]
	 */
	public function search(string $query, string $dn, array $fields = [])
	{
		$ldap = \Symfony\Component\Ldap\Ldap::create('ext_ldap', ['connection_string' => $this->ldapParams['connection_string']]);
		$ldap->bind($this->ldapParams['bind_dn'], $this->ldapParams['bind_password']);
		$query = $ldap->query($dn . $this->ldapParams['base_dn'], $query, ["filter" => $fields]);
		$users = $query->execute();
		return $users->toArray();
	}
}