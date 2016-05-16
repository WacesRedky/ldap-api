<?php
/**
 * Created by PhpStorm.
 * User: mix
 * Date: 16.05.16
 * Time: 15:15
 */

namespace LdapApi;

use PHPUnit_Framework_TestCase;

class ServiceTest extends PHPUnit_Framework_TestCase
{
    
    public function getLdap(){
        $connect = new LdapConnect(ConnectTest::HOST, ConnectTest::DC);

        return new LdapService($connect);
    }


    public function __construct($name = null, array $data = [], $dataName='') {
        parent::__construct($name, $data, $dataName);

    }

    public function testLogin(){
        $this->assertFalse($this->getLdap()->checkLogin(ConnectTest::LOGIN, ConnectTest::PASSWORD . "s"));
        $this->assertTrue($this->getLdap()->checkLogin(ConnectTest::LOGIN, ConnectTest::PASSWORD));
    }

    public function testNameByLogin(){
        $this->assertEquals($this->getLdap()->getNameByLogin(ConnectTest::LOGIN), ConnectTest::NAME);
        $this->assertEquals($this->getLdap()->getNameByLogin(ConnectTest::LOGIN . "a"), null);
    }

    public function testContacts(){
        $this->assertArrayHasKey(ConnectTest::LOGIN, $this->getLdap()->getContacts());
    }
    
}