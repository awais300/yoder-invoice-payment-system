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
                /*'username' => Cryptor::decrypt('9O5APcc2xkAjFsPRyx8G2Sp0zokWydPaK+kI+e+JlQyLJekCCiFzgycdG8hvbE+4fGhcwzEBmSWD80lql5tMvXs='),
                'password' => Cryptor::decrypt('ImxYHezOmzM/0KTtTPHZAQvvdX3GgPCRp6o6kVUbCoPkDkFPqIExHvNvWWk/611rbTo13/zj'),
                'integrator_id' => '936EffectWeb',*/
                
                'username' => 'awais@effectwebagency.com',
                'password' => 'TWDKJC@fYY0v',
                'integrator_id' => '967174xd2CvC',
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
