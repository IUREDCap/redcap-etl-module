<?php

namespace IU\RedCapEtlModule;

class UserList implements \JsonSerializable
{
    // Map from usernames to array map of projects IDs
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
        $this->userList[$username] = array();
    }

    public function deleteUser($username)
    {
        unset($this->userList[$username]);
    }
    
    public function getProjects($username)
    {
        return $this->userList[$username];
    }
    
    public function addProject($username, $projectId)
    {
        if (array_key_exists($username, $this->userList)) {
            $this->userList[$username][$projectId] = 1;
        }
    }

    public function removeProject($username, $projectId)
    {
        if (array_key_exists($username, $this->userList)) {
            unset($this->userList[$username][$projectId]);
        }
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
