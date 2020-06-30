<?php
/**
 * Created by PhpStorm.
 * User: Igor
 * Date: 01.02.2019
 * Time: 15:20
 */

namespace Roadinfo;


class User
{
    public $keyuser;
    public $login;
    public $name;
    public $email;

    public function __construct($keyuser, $login, $name, $email){
        $this->keyuser = $keyuser;
        $this->login = $login;
        $this->name = $name;
        $this->email = $email;
    }
}