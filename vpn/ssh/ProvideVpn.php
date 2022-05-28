<?php

namespace app\services\admin\vpn\ssh;

class ProvideVpn extends SshVpn
{
    private $command;

    /**
     *   Data conditioning + connecting and executing the script
     *
     * @return mixed|void
     */
    public function runCommand()
    {
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
        $user_chat_id = $this->getUser()->chat_id;

        if (empty($user_chat_id)) {
            $this->command = "sudo /data/scripts/openvpncrm.sh on " . $this->getFileName();
        } else {
            $this->command = "sudo /data/scripts/openvpncrm.sh on " . $this->getFileName() . ' ' . $user_chat_id;
        }
    }

    /**
     * @return mixed
     */
    private function getCommand()
    {
        $this->setCommand();

        return $this->command;
    }


}