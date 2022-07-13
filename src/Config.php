<?php

namespace Yoder\YIPS;

use Yoder\YIPS\Singleton;
use Yoder\YIPS\Cryptor;

defined('ABSPATH') || exit;

/**
 * Class Config
 * @package Yoder\YIPS
 */

class Config extends Singleton
{
    public function __construct()
    {
        // Debug
        if (@isset($_GET['yoder_api_debug'])) {
            //echo Cryptor::encrypt('text_goes_here'); echo "<br/>";
            //exit;
        }
    }

    /**
     * Get configuration for plugin.
     *
     * @param string $key To get specific config (One step deep).
     * @return Array
     */
    public function get_config($key = false)
    {
        $config = [
            'rosetta' => [
                'security_key' => Cryptor::decrypt('XD7czNG2nLtby9emBe+FMDhnlq0h01vsrF17pJ/UcQkrXHFcY12pQJ/kjFJY0wCWiin7dnr8XXBzSTQDyyns229JttyE'),
                'ip' => '50.240.141.89',
                'port' => 55555
            ],
            'paytrace' => [
                'username' => Cryptor::decrypt('Hb0Yd9Db58dGmRal5SnvsARUsdMT0QPOcKfkGQMQr2Sy2dW2KyiLMq2oMWvQ0IvJreCxHcpuka/Xodz9XWEhATw='),
                'password' => Cryptor::decrypt('7W4pZ98mtUJVOwu5EZkcTbjaG+sm3AuaNAWgBV6C6zXw1CHMSiebpobVnvByQ3XYtyo='),
                'integrator_id' => '967174xd2CvC'
            ]
        ];

        if ($key !== false) {
            if (isset($config[$key])) {
                return $config[$key];
            } else {
                return false;
            }
        }

        return $config;
    }
}
