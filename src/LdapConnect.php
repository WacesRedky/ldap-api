<?php
namespace LdapApi;

use Psr\Log\LoggerInterface;
/**
 * @author: mix
 * @date: 09.05.13
 */
class LdapConnect
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $dn;

    /**
     * @var string
     */
    private $userGroup;

    /**
     * @var LoggerInterface
     */
    private $debugLogger = null;

    /**
     * @param string $host
     * @param string $dn
     * @param string $userGroup
     * @internal param string $filter
     */
    public function __construct($host, $dn, $userGroup = "users") {
        $this->host = $host;
        $this->dn = $dn;
        $this->userGroup = $userGroup;
    }

    public function enableDebug(LoggerInterface $logger){
        $this->debugLogger = $logger;
    }

    public function search($filter){
        $connect = $this->connect();
        $bind = $this->bind($connect);
        if (!$bind) {
            throw new Exception("cant bind");
        }
        $read = \ldap_search($connect, $this->dn, $filter);
        if (!$read) {
            throw new Exception("Unable to search ldap server");
        }
        $info = \ldap_get_entries($connect, $read);
        $this->close($connect);
        return $info;
    }

    public function loginUser($name, $password){
        $ldaprdn = 'cn=' . $name . ",ou={$this->userGroup},{$this->dn}";
        $connect = $this->connect();
        $bind = $this->bind($connect, $ldaprdn, $password);
        $this->close($connect);
        if ($bind) {
            return true;
        }

        return false;
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
        if ($this->debugLogger) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $msg = $trace[1]["function"] . ": " . round($time2 - $time1, 2) . "s\n";
            foreach ($trace as $row) {
                $msg .= $row["file"] . ":" . $row["line"] . " ". $row["class"] . "::" . $row["function"] . "\n";
            }
            $this->debugLogger->notice($msg);
        }

        return $result;
    }
}