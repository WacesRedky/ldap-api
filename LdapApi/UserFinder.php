<?php
namespace LdapApi;
/**
 * @author: mix
 * @date: 07.05.14
 */
interface UserFinder {
    public function getNameByLogin($login);
}