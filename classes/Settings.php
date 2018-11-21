<?php

namespace IU\RedCapEtlModule;

class Settings
{
    const ADMIN_CONFIG_KEY         = 'admin-config';
    const SERVER_CONFIG_KEY_PREFIX = 'server-config:';
    const SERVERS_KEY              = 'servers';
    const USER_LIST_KEY            = 'user-list';
    const LAST_RUN_TIME_KEY        = 'last-run-time'; // for storing day and time of last run

    const USER_PROJECTS_KEY_PREFIX = 'user-projects:';  // appdend with username to make key
    
    const CONFIG_SESSION_KEY = 'redcap-etl-config';
    
    private $module;
    
    /** @var RedCapDb $db REDCap database object. */
    private $db;
    
    public function __construct($module)
    {
        $this->module = $module;
        $this->db     = new RedCapDb();
    }
    
    #----------------------------------------------------------
    # Users settings methods
    #----------------------------------------------------------
    
    public function getUsers()
    {
        // Note: only 1 database access, so don't need transaction option
        $userList = new UserList();
        $json = $this->module->getSystemSetting(self::USER_LIST_KEY);
        $userList->fromJson($json);
        $users = $userList->getUsers();
        return $users;
    }
    
    public function addUser($username, $transaction = true)
    {
        $commit = true;
        $userList = new UserList();
        
        if ($transaction) {
            $this->db->startTransaction();
        }
        
        $json = $this->module->getSystemSetting(self::USER_LIST_KEY);
        $userList->fromJson($json);
        $userList->addUser($username);
        $json = $userList->toJson();
        $this->module->setSystemSetting(self::USER_LIST_KEY, $json);
        
        if ($transaction) {
            $this->db->endTransaction($commit);
        }
    }
}
