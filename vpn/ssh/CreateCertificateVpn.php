<?php

namespace app\services\admin\vpn\ssh;

class CreateCertificateVpn extends SshVpn
{
    private $command;
    private $value;

    /**
     * Data conditioning + connecting and executing the script
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
            $this->command = "sudo /data/scripts/openvpncrm.sh new " . $this->getCommandValue() . ' ' . $this->getUser()->department_id;
        } else {
            $this->command = "sudo /data/scripts/openvpncrm.sh new " . $this->getCommandValue() . ' ' . $this->getUser()->department_id . ' ' . $user_chat_id;
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

    /**
     * @return void
     */
    private function setCommandValue()
    {
        $this->value = $this->getUser()->vpn_user_name;
    }

    /**
     *  'Certificate name' value for bash-script in command
     *
     * @return mixed
     */
    private function getCommandValue()
    {
        $this->setCommandValue();

        return $this->value;
    }

}