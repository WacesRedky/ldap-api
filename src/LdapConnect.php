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

    public function enableDebug(LoggerInterface $logger) {
        $this->debugLogger = $logger;
    }

    public function search($filter, $extendedDn = null) {
        $connect = $this->connect();
        $bind = $this->bindLocal($connect);
        if (!$bind) {
            throw new Exception("cant bind");
        }

        $dn = $this->dn;

        if ($extendedDn) {
            $dn = $extendedDn . "," . $this->dn;
        }
        $read = $this->time(
            function () use ($connect, $dn, $filter) {
                return \ldap_search($connect, $dn, $filter);
            },
            'search ' . $dn . " / " . $filter
        );

        if (!$read) {
            throw new Exception("Unable to search ldap server");
        }

        $info = $this->time(
            function () use ($connect, $read) {
                return \ldap_get_entries($connect, $read);
            },
            'get_entries'
        );
        $this->close($connect);
        return $info;
    }

    public function loginUser($name, $password) {
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
            },
            'connect to ' . $this->host
        );
    }

    private function close($connect) {
        return $this->time(
            function () use ($connect) {
                \ldap_close($connect);
            },
            'close'
        );
    }

    private function bind($connect, $ldaprdn, $password) {
        return $this->time(
            function () use ($connect, $ldaprdn, $password) {
                return @\ldap_bind($connect, $ldaprdn, $password);
            },
            'bind ' . $ldaprdn
        );
    }

    private function bindLocal($connect) {
        return $this->time(
            function () use ($connect) {
                return \ldap_bind($connect);
            },
            'bind local'
        );
    }

    private function time(callable $f, string $name) {

        if ($this->debugLogger) {
            $msg = "$name\n";
            $this->debugLogger->notice($msg);
        }
        $time1 = microtime(1);
        $result = $f();
        $time2 = microtime(1);
        if ($this->debugLogger) {
            $msg = "$name done in " . round(($time2 - $time1) * 1000, 2) . "ms\n";
            $this->debugLogger->debug($msg);
        }

        return $result;
    }
}