<?php
/**
 * Created by PhpStorm.
 * User: mix
 * Date: 16.05.16
 * Time: 15:10
 */

namespace LdapApi;

class LdapService
{
    const ALL_USERS = "(uid=*)";
    
    private $ldap;

    public function __construct(LdapConnect $connect) {
        $this->ldap = $connect;
    }

    /**
     * @throws Exception
     * @return array
     */
    public function getContacts() {
        $info = $this->ldap->search(self::ALL_USERS);
        array_shift($info);
        $out = array();
        foreach ($info as $user) {
            if (!isset($user['givenname'][0])) {
                continue;
            }
            if (!isset($user['mail'][0])) {
                continue;
            }
            $login = $user['uid'][0];
            $mail = $user['mail'][0];
            $r = array(
                "id" => $user['uidnumber'][0],
                "login" => $login,
                "mail" => $mail,
                "firstname" => $user['givenname'][0],
                "lastname" => $user['sn'][0],


            );
            $out[$login] = $r;
        }
        return $out;
    }

    public function checkLogin($login, $password) {
        $name = $this->getNameByLogin($login);
        return $this->ldap->loginUser($name, $password);

    }


    public function getNameByLogin($login) {
        $users = $this->getContacts();
        if (isset($users[$login])) {
            return $users[$login]["firstname"] . " " . $users[$login]["lastname"];
        }

        return null;
    }

}