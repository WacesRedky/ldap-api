<?php
namespace LdapApi;

/**
 * @author: mix
 * @date: 09.05.13
 */
class Ldap implements UserFinder
{
    private $host;

    /**
     * @var string
     */
    private $dn;

    private $userGroup;

    private $filter;

    private $debug = false;

    /**
     * @param string $host
     * @param string $dn
     * @param string $userGroup
     * @param string $filter
     * @throws Exception
     */
    public function __construct($host, $dn, $userGroup = "users", $filter = "(uid=*)") {
        $this->host = $host;
        $this->dn = $dn;
        $this->userGroup = $userGroup;
        $this->filter = $filter;
    }

    public function enambleDebug(){
        $this->debug = true;
    }

    /**
     * @throws Exception
     * @return array
     */
    public function getContacts() {
        $connect = $this->connect();
        $bind = $this->bind($connect);
        if (!$bind) {
            throw new Exception("Could not bind");
        }
        $read = \ldap_search($connect, $this->dn, $this->filter);
        if (!$read) {
            throw new Exception("Unable to search ldap server");
        }
        $info = \ldap_get_entries($connect, $read);
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
                "login" => $login,
                "mail" => $mail,
                "firstname" => $user['givenname'][0],
                "lastname" => $user['sn'][0],

            );
            $out[$login] = $r;
        }
        $this->close($connect);

        return $out;
    }

    public function checkLogin($login, $password, UserFinder $finder) {
        $name = $finder->getNameByLogin($login);
        $ldaprdn = 'cn=' . $name . ",ou={$this->userGroup},{$this->dn}";
        $connect = $this->connect();
        $bind = $this->bind($connect, $ldaprdn, $password);
        $this->close($connect);
        if ($bind) {
            return true;
        }

        return false;
    }

    public function getNameByLogin($login) {
        $users = $this->getContacts();
        if (isset($users[$login])) {
            return $users[$login]["firstname"] . " " . $users[$login]["lastname"];
        }

        return null;
    }

    private function connect() {
        return $this->time(
            function () {
                return \ldap_connect("ldap://{$this->host}/");
            }
        );
    }

    private function close($connect) {
        return $this->time(
            function () use ($connect) {
                \ldap_close($connect);
            }
        );
    }

    private function bind($connect, $ldaprdn = null, $password = null) {
        return $this->time(
            function () use ($connect, $ldaprdn, $password) {
                if ($ldaprdn === null) {
                    return \ldap_bind($connect);
                }

                return @\ldap_bind($connect, $ldaprdn, $password);
            }
        );
    }

    private function time(callable $f) {
        $time1 = microtime(1);
        $result = $f();
        $time2 = microtime(1);
        if ($this->debug) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            echo $trace[1]["function"] . ": " . round($time2 - $time1, 2) . "s\n";
            foreach ($trace as $row) {
                echo $row["file"] . ":" . $row["line"] . " ". $row["class"] . "::" . $row["function"] . "\n";
            }
        }

        return $result;
    }
}