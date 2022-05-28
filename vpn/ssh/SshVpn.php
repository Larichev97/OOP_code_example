<?php

namespace app\services\admin\vpn\ssh;

use app\models\CrmUsers;
use Yii;

abstract class SshVpn implements SshVpnInterface
{
    // SSH SETTINGS 
    protected $ssh_host; // server address
    protected $ssh_port; // port for ssh
    protected $ssh_login; // user login
    protected $ssh_pass; // user password

    protected $file_name; // file name, where will be run command
    protected $user_id; // id employee
    protected $action; // command action (provide or block vpn)

    /**
     * @param $user_id
     * @param string $action
     */
    public function __construct($user_id, string $action = '')
    {
        $this->user_id = $user_id;
        $this->action = $action;
    }

    /**
     * @return mixed
     */
    abstract public function runCommand();

    /**
     *  Connecting and executing the script
     *
     * @param string $ssh_command
     * @return bool
     */
    protected function stream(string $ssh_command) {
        if (!empty($ssh_command)) {

            $this->setSshParams();

            if ($ssh = \ssh2_connect($this->ssh_host, $this->ssh_port)) {
                if(\ssh2_auth_password($ssh, $this->ssh_login, $this->ssh_pass)) {
                    $stream = \ssh2_exec($ssh, $ssh_command);

                    \stream_set_blocking($stream, true);

                    $stream_out = \stream_get_contents($stream);

                    \fclose($stream);

                    \ssh2_exec($ssh, 'exit');

                    return $stream_out;
                }
            }
        }

        return false;
    }

    /**
     * @return void
     */
    protected function setSshParams()
    {
        $this->ssh_host = Yii::$app->params['ssh_vpn_admin_host'];
        $this->ssh_port = Yii::$app->params['ssh_vpn_admin_port'];
        $this->ssh_login = Yii::$app->params['ssh_vpn_admin_login'];
        $this->ssh_pass = Yii::$app->params['ssh_vpn_admin_pass'];
    }

    /**
     *  Obtaining data about an employee
     *
     * @return CrmUsers
     */
    protected function getUser() : CrmUsers
    {
        $crmUsers = CrmUsers::findOne($this->user_id);

        return $crmUsers;
    }

    /**
     *  Setting the file name for command execution on the server
     *
     * @return void
     */
    protected function setFileName()
    {
        $this->file_name = $this->getUser()->vpn_user_name;
    }

    /**
     * @return mixed
     */
    protected function getFileName()
    {
        $this->setFileName();

        return $this->file_name;
    }

}