<?php

namespace app\services\admin\vpn\ssh;

interface SshVpnInterface
{
    /**
     * @return mixed
     */
    public function runCommand();
}