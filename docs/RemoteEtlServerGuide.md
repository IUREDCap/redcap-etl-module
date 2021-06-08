Remote ETL Server Guide
===========================================================

This guide has information on setting up a remote ETL server
for the REDCap-ETL external module.

In general, to run a remote ETL server you will need a server that:

* can be accessed by REDCap
* supports SSH and SFTP
* has a directory where files can be SFTP'd from REDCap

You will need to create an ETL server configuration in the REDCap-ETL external module for
all remote ETL servers that you want to use. The ETL configuration will specify properties that include:

* how to authenticate with the remote system where the ETL server is located
* a directory on the ETL server's system where the external module can copy users' ETL configuration files
* the command to run on the ETL server's system to activate the ETL server

For remote system authentication, and there are three options:

1. **Username/Password.** You can use the username and password of a user who has access to the
    system where the ETL server is running.
2. **SSH Key**. You can use an SSH key (without a key password).
3. **SSH Key with Key Password.**. You can use an SSH key with a key password.

When a user runs one of their ETL configurations on an ETL server, the external module will:

1. use SFTP to copy the user's ETL configuration file to the directory on the ETL server's system
   specified in the ETL server's configuration
2. use SSH to run the ETL server activation command on the ETL server's system that is
   specified in the ETL server's configuration with the full
   path of the user's copied configuration file as an argument for the command, for example:

    /opt/redcap-etl/bin/redcap_etl.php /opt/redcap-etl/config/user_etl_config/etl_config_609ed1c7e74c29.10651986.json


Using the REDCap-ETL Application as a Remote ETL Server
------------------------------------------------------------------

REDCap-ETL was originally implemented as a standalone application that runs outside of REDCap, and this
application can be used as an ETL server.

**The example commands used in this section are for Ubuntu 20.**

You first need to get REDCap-ETL on the system you want to use, for example:

    cd /opt
    sudo git clone https://github.com/IUREDCap/redcap-etl

After this step, the REDCap-ETL application can be run on the command line by executing the following script:

    /opt/redcap-etl/bin/redcap_etl.php

SSH needs to be enabled for the REDCap-ETL external module to be able to access the server.
You can check the status of SSH with the following command:

    sudo systemctl status ssh

If SSH has not been enabled, use the following commands to enable it:

    sudo apt update
    sudo apt install openssh-server

For the SSH key authentication option with the remote system,
you need to specify the private key file in the ETL server configuration
and the file must be readable by the user that the web server runs under (e.g., "www-data").
You need to create the SSH key on the remote system where the ETL server runs, for example:

    ssh-keygen -m PEM -t rsa
    ssh-copy-id abitc@127.0.0.1

Set up a configuration directory that the the user (used to SSH to the REDCap-ETL application)
is able to write to.  The **config** directory under the REDCap-ETL installation can be used. Files in this directory, except
for the example configuration file are set to be ignored by Git. The permission of this directory
needs to be set so that user specified for authentication has access, for example, for user "abitc":

    sudo chown abitc:abitc /opt/redcap-etl/config/


Creating a Custom ETL Server
---------------------------------------------------------

It is possible to create a custom ETL server, because the command you configure in REDCap to be executed
to activate the remote ETL server can be set to run any application. The path of the user's ETL
configuration file will be passed as the first argument for the command for the remote ETL server,
so the application specified can use this to get access to the user's ETL configuration file.

