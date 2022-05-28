<?php

namespace app\services\admin\vpn\ssh;

class TwoFactorVpn extends SshVpn
{
    private $command;

    /**
     *   Data conditioning + connecting and executing the script
     *
     * @return mixed|void
     */
    public function runCommand()
    {
        $this->setCommand();

        $ssh_command = $this->getCommand();

        return $this->stream($ssh_command);
    }

    /**
     *  Command setting
     *
     * @return void
     */
    private function setCommand()
    {
        $this->command = "sudo /data/scripts/no2fa.sh " . $this->getFileName() . ' ' . $this->getUser()->two_factor_flag;
    }

    /**
     * @return mixed
     */
    private function getCommand()
    {
        return $this->command;
    }


}