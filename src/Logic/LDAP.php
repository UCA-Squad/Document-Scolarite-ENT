<?php
namespace App\Logic;


use Symfony\Component\Ldap\Entry;

class LDAP
{
    private $ldapParams;

    public function __construct(array $ldapParams)
    {
        $this->ldapParams = $ldapParams;
    }

    /**
     * @param string $query
     * @param string $dn
     * @param array $fields
     * @return Entry[]
     */
    public function search(string $query,string $dn,array $fields=[])
    {
        $ldap = \Symfony\Component\Ldap\Ldap::create('ext_ldap',['connection_string'=>$this->ldapParams['connection_string']]);
        $ldap->bind($this->ldapParams['bind_dn'],$this->ldapParams['bind_password']);
        $query = $ldap->query($dn.$this->ldapParams['base_dn'], $query,["filter"=>$fields]);
        $users = $query->execute();
        return $users->toArray();
    }
}