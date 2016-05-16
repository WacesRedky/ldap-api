<?php
/**
 * Created by PhpStorm.
 * User: mix
 * Date: 16.05.16
 * Time: 16:56
 */

namespace LdapApi;

class ConnectTest extends \PHPUnit_Framework_TestCase
{
    const HOST = "192.168.2.2";

    const DC = "dc=mamba-co,dc=ru";

    const LOGIN = "";
    const PASSWORD = "";
    const NAME = "";

    public function getLdap(){
        return  new LdapConnect(self::HOST, self::DC);
    }


    public function testDebug(){
        $logger = new TestLogger();

        $ldap = $this->getLdap();
        $ldap->enableDebug($logger);

        $ldap->loginUser(self::NAME, self::PASSWORD);

        $this->assertTrue($logger->loggerCalled());
    }

}