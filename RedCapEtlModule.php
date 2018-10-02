<?php

namespace IU\RedCapEtlModule;

include_once __DIR__.'/UserList.php';
include_once __DIR__.'/ServerConfig.php';
include_once __DIR__.'/Servers.php';

/**
 * Main REDCap-ETL module class.
 */
class RedCapEtlModule extends \ExternalModules\AbstractExternalModule {

    const ADMIN_CONFIG_KEY         = 'admin-config';
    const SERVER_CONFIG_KEY_PREFIX = 'server-config:';
    const SERVERS_KEY              = 'servers';
    const USER_LIST_KEY            = 'user-list';

    const CONFIG_SESSION_KEY = 'redcap-etl-config';
    
    public function cron()
    {
    }

    public function getVersionNumber()
    {
        $versionNumber = '';
        $dirName = $this->getModuleDirectoryName();
        if (preg_match('/.*_v(.*)/', $dirName, $matches) === 1) {
            $versionNumber = $matches[1];
        }
        return $versionNumber;
    }

    public function getUsers()
    {
        $userList = new UserList();
        $json = $this->getSystemSetting(self::USER_LIST_KEY, true);
        $userList->fromJson($json);
        $users = $userList->getUsers();
        return $users;
    }

    public function addUser($username)
    {
        $userList = new UserList();
        $json = $this->getSystemSetting(self::USER_LIST_KEY, true);
        $userList->fromJson($json);
        $userList->addUser($username);
        $json = $userList->toJson();
        $this->setSystemSetting(self::USER_LIST_KEY, $json);
    }

    public function getServers()
    {
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers = $servers->getServers();
        return $servers;
    }

    public function addServer($serverName)
    {
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->addServer($serverName);
        $json = $servers->toJson();
        $this->setSystemSetting(self::SERVERS_KEY, $json);
    }

    public function copyServer($fromServerName, $toServerName)
    {
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->addServer($toServerName);
        $json = $servers->toJson();
        $this->setSystemSetting(self::SERVERS_KEY, $json);
        
        $this->copyServerConfig($fromServerName, $toServerName);
    }
    
    public function renameServer($serverName, $newServerName)
    {
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->addServer($newServerName);
        $servers->removeServer($serverName);
        $json = $servers->toJson();
        $this->setSystemSetting(self::SERVERS_KEY, $json);
        
        $this->renameServerConfig($serverName, $newServerName);
    }
    
    public function removeServer($serverName)
    {
        $this->removeServerConfig($serverName);
        
        $servers = new Servers();
        $json = $this->getSystemSetting(self::SERVERS_KEY, true);
        $servers->fromJson($json);
        $servers->removeServer($serverName);
        $json = $servers->toJson();
        error_log('JSON: '.$json);
        $this->setSystemSetting(self::SERVERS_KEY, $json);
    }


    private function getUserInfo()
    {
        $key = $this->getUserKey();
        $userInfo = json_decode($this->getSystemSetting($key), true);
        if (empty($userInfo)) {
            $userInfo = array();
        }
        return $userInfo;
    }

    private function setUserInfo($userInfo)
    {
        $key = $this->getUserKey();
        $userInfo = json_encode($userInfo);
        $this->setSystemSetting($key, $userInfo);
    }

    private function getUserInfos()
    {
    }


    public function getUserConfigurationNames()
    {
        $userInfo = $this->getUserInfo();
        $names = array_keys($userInfo);
        sort($names);
        return $names;
    }


    /**
     * Gets the specified configuration for the current user.
     * 
     * @param string $name the name of the configuration to get.
     * @return Configuration the specified configuration.
     */
    public function getConfiguration($name)
    {
        $configuraion = null;
        $key = $this->getConfigurationKey($name);

        $setting = $this->getSystemSetting($key);
        $configValues = json_decode($setting, true);
        if (isset($configValues) && is_array($configValues)) {
            $configuration = new Configuration($configValues['name']);
            $configuration->set($configValues['properties']);
        }
        return $configuration;
    }


    public function setConfiguration($configuration)
    {
        $key = $this->getConfigurationKey($configuration->getName());

        $json = json_encode($configuration);
        $setting = $this->setSystemSetting($key, $json);
    }

    public function addConfiguration($name)
    {
        # Add configuration entry for user
        $userInfo = $this->getUserInfo();
        if (!isset($userInfo)) {
            $userInfo = array();
        }

        if (!array_key_exists($name, $userInfo)) {
            $userInfo[$name] = 1;
            $userKey = $this->getUserKey();
            $this->setSystemSetting($userKey, json_encode($userInfo));
        }
        
        # Add the actual configuration
        $key = $this->getConfigurationKey($name);
        $configuration = $this->getSystemSetting($key);
        if (!isset($configuration)) {
            $configuration = new Configuration($name);
            $jsonConfiguration = json_encode($configuration);
            $this->setSystemSetting($key, $jsonConfiguration);
        }

        \REDCap::logEvent('Added REDCap-ETL configuration '.$name.'.');
    }

    public function removeConfiguration($configName)
    {
        
    }


    public function getAdminConfig()
    {
        $adminConfig = new AdminConfig();
        $setting = $this->getSystemSetting(self::ADMIN_CONFIG_KEY);
        $adminConfig->fromJson($setting);
        return $adminConfig;
    }
    
    public function setAdminConfig($adminConfig)
    {
        $json = $adminConfig->toJson();
        $this->setSystemSetting(self::ADMIN_CONFIG_KEY, $json);
    }



    public function getServerConfig($serverName)
    {
        $serverConfig = new ServerConfig($serverName);
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverName;
        $setting = $this->getSystemSetting($key);
        $serverConfig->fromJson($setting);
        return $serverConfig;
    }
    
    public function setServerConfig($serverConfig)
    {
        $json = $serverConfig->toJson();
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverConfig->getName();
        $this->setSystemSetting($key, $json);
    }
    
    public function copyServerConfig($fromServerName, $toServerName)
    {
        $toServerConfig = $this->getServerConfig($fromServerName);
        $toServerConfig->setName($toServerName);
        $json = $toServerConfig->toJson();
        $key = self::SERVER_CONFIG_KEY_PREFIX . $toServerName;
        $this->setSystemSetting($key, $json);
    }
    
    public function renameServerConfig($serverName, $newServerName)
    {
        $this->copyServerConfig($serverName, $newServerName);
        $this->removeServerConfig($serverName);    
    }
    
    
    public function removeServerConfig($serverName)
    {
        $key = self::SERVER_CONFIG_KEY_PREFIX . $serverName;
        $result = $this->removeSystemSetting($key);
        return $result;
    }

    public function getUserKey()
    {
        $key = 'user:'.USERID;
        return $key;
    }

    public function getConfigurationKey($name)
    {
        $key = 'user:'.USERID.';configuration:'.$name;
        return $key;
    }

    public function getEtlProjects()
    {
        $userInfo = $this->getUserInfo();
        echo "select";
        // ...

    }


    public function renderAdminTabs($activeUrl = '')
    {
        $adminUrl = $this->getUrl('admin.php');
        $adminLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' Configure';

        $manageUsersUrl = $this->getUrl('users.php');
        #$manageUsersLabel = '<span>Manage Users</span>';
        #$manageUsersLabel = '<span><img aria-hidden="true" src="/redcap/redcap_v8.5.11/Resources/images/users3.png">'
        $manageUsersLabel = '<span class="glyphicon glyphicon-user" aria-hidden="true"></span>'
           .' Manage Users</span>';

        $serversUrl = $this->getUrl('servers.php');
        $serversLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' ETL Servers';

        $serverConfigUrl = $this->getUrl('server_config.php');
        $serverConfigLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' ETL Server Config';

        $tabs = array($adminUrl => $adminLabel, $manageUsersUrl => $manageUsersLabel
            , $serversUrl => $serversLabel
            , $serverConfigUrl => $serverConfigLabel
        );
        $this->renderTabs($tabs, $activeUrl);
    }

    public function renderUserTabs($activeUrl = '')
    {
        $listUrl = $this->getUrl('index.php');
        $listLabel = '<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>'
           .' My Configurations';

        $addUrl = $this->getUrl('add.php');
        $addLabel = '<span style="color: #008000;" class="glyphicon glyphicon-plus" aria-hidden="true"></span>'
           .' New Configuration';

        $configUrl = $this->getUrl('configure.php');
        $configLabel = '<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>'
           .' Configure';

        $runUrl = $this->getUrl('run.php');
        $runLabel = '<span style="color: #008000;" class="glyphicon glyphicon-play-circle" aria-hidden="true"></span>'
           .' Run';

        $tabs = array($listUrl => $listLabel, $addUrl => $addLabel, $configUrl => $configLabel, $runUrl => $runLabel);
        $this->renderTabs($tabs, $activeUrl);
    }


	/**
	 * RENDER TABS FROM ARRAY WITH 'PAGE' AS KEY AND LABEL AS VALUE
	 */
	public function renderTabs($tabs=array(), $activeUrl = '')
	{
		echo '<div id="sub-nav" style="margin:5px 0 20px;">'."\n";
		echo '<ul>'."\n";
	    foreach ($tabs as $this_url => $this_label) {
	        // Check for Active tab
		    $isActive = false;
            $class = '';
			if (strcasecmp($this_url, $activeUrl) === 0) {
                $class = ' class="active"';
	            $isActive = true;
			}
		    echo '<li '.$class.'>'."\n";
            echo '<a href="'.$this_url.'" style="font-size:13px;color:#393733;padding:6px 9px 5px 10px;">';
            echo $this_label.'</a>'."\n";
		}
		echo '</li>'."\n";
        echo '</ul>'."\n";
		echo '</div>'."\n";
		echo '<div class="clear"></div>'."\n";
	}

}
