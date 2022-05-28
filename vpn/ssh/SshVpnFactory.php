<?php

namespace app\services\admin\vpn\ssh;

class SshVpnFactory
{
    const ACTION_PROVIDE_VPN = 'provide';
    const ACTION_BLOCK_VPN = 'block';
    const ACTION_CREATE_CERTIFICATE_VPN = 'create_certificate';
    const ACTION_TWO_FACTOR_VPN = 'two_factor_vpn';

    private $user_id;
    private $action;

    /**
     * @param $user_id
     * @param string $action
     */
    public function __construct($user_id, string $action)
    {
        $this->user_id = $user_id;
        $this->action = $action;
    }

    /**
     * @return SshVpn
     */
    public function getSshVpn() : SshVpn
    {
        switch ($this->action) {
            case self::ACTION_PROVIDE_VPN :
                return new ProvideVpn($this->user_id, $this->action);
            case self::ACTION_BLOCK_VPN :
                return new BlockVpn($this->user_id, $this->action);
            case self::ACTION_CREATE_CERTIFICATE_VPN :
                return new CreateCertificateVpn($this->user_id, $this->action);
            case self::ACTION_TWO_FACTOR_VPN :
                return new TwoFactorVpn($this->user_id, $this->action);
        }
    }
}