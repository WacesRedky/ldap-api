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
    const ALL_USERS = "uid=*";

    const ALL_MEMBERS = 'memberUid=*';

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
            $id = $user['uidnumber'][0];
            $r = array(
                "id" => $id,
                "login" => $login,
                "mail" => $mail,
                "firstname" => $user['givenname'][0],
                "lastname" => $user['sn'][0],

            );
            $out[$id] = $r;
        }

        return $out;
    }

    public function checkLogin($login, $password) {
        $name = $this->getNameByLogin($login);

        return $this->ldap->loginUser($name, $password);
    }

    public function getNameByLogin($login) {
        $users = $this->getContacts();
        foreach ($users as $user) {
            if ($user['login'] == $login) {
                return $user["firstname"] . " " . $user["lastname"];
            }
        }

        return null;
    }

    public function checkGroup($login, $group) {
        $info = $this->ldap->search(self::ALL_MEMBERS, "cn={$group},ou=groups");

        return in_array($login, $info[0]['memberuid']);
    }

    public function getUserGroups($login) {
        $info = $this->ldap->search("memberUid={$login}");
        $out = [];
        for ($i = 0; $i < $info['count']; $i++) {
            $out[] = $info[$i]['cn'][0];
        }
        return $out;
    }
}