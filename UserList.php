<?php

namespace IU\RedCapEtlModule;


class UserList implements \JsonSerializable
{
    private $userList;

    public function __construct()
    {
        $this->userList = array();
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getUsers()
    {
        $users = array_keys($this->userList);
        sort($users);
        return $users;
    }

    public function addUser($username)
    {
        $this->userList[$username] = 1;
    }

    public function deleteUser($username)
    {
    }


    public function fromJson($json)
    {
        if (!empty($json)) {
            $values = json_decode($json, true);
            foreach (get_object_vars($this) as $var => $value) {
                $this->$var = $values[$var];
            }
        }
    }

    public function toJson()
    {
        $json = json_encode($this);
        return $json;
    }
}
