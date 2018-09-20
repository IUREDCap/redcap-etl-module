<?php

namespace IU\RedCapEtlModule;

class RedCapEtlModule extends \ExternalModules\AbstractExternalModule {

    const ADMIN_CONFIG_KEY = 'admin-config';


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

    public function getConfiguration($name)
    {
        $configuraion = null;
        $key = $this->getConfigurationKey($name);

        $setting = $this->getSystemSetting($key);
        $configValues = json_decode($setting, true);
        if (isset($configValues) && is_array($configValues)) {
            $configuration = new Configuration($configValues['name']);
            try {
                $configuration->set($configValues['properties']);
            } catch(\Exception $exception) {
                ; // should not happen here
            }
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
            print_r($jsonConfiguration);
            $this->setSystemSetting($key, $jsonConfiguration);
        }

        \REDCap::logEvent('Added REDCap-ETL configuration '.$name.'.');
    }

    public function getAdminConfig()
    {
        $adminConfig = new AdminConfig();
        $setting = $this->getSystemSetting(self::ADMIN_CONFIG_KEY);
        $adminConfig->fromJson($setting);
        return $adminConfig;
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


        $tabs = array($adminUrl => $adminLabel);
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
