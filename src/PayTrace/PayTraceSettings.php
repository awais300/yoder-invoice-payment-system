<?php

namespace Yoder\YIPS\PayTrace;

use Yoder\YIPS\Config;

defined('ABSPATH') || exit;

// This file holds all the settings related to API.
// Define variables that holds API settings and urls.
// Get the user credential for the account and change the user credentials

/**
 * Class PayTraceSettings
 * @package Yoder\YIPS
 */

class PayTraceSettings
{
    /**
     * Contains plugin configuration.
     *
     * @var $logger
     */
    private $config = null;

    /**
     * Construct the plugin.
     **/
    public function __construct()
    {
        $this->config = (Config::instance())->get_config('paytrace');
        $this->init_settings();
    }

    /**
     * Defint constants and setting to be used for PayTrace API.
     *
     **/
    public function init_settings()
    {
        define("PAYTRACE_USERNAME", $this->config['username']);
        define("PAYTRACE_PASSWORD", $this->config['password']); //pvc
        define("PAYTRACE_GRANT_TYPE", "password");


        define("BASE_URL", "https://api.paytrace.com"); //Production

        //API version
        define("API_VERSION", "/v1");

        // Url for OAuth Token
        define("URL_OAUTH", BASE_URL . "/oauth/token");

        // Url for OAuth Token
        define("URL_PROTECTAUTH", BASE_URL . API_VERSION . "/payment_fields/token/create");

        // URL for Keyed Sale
        define("URL_PROTECT_SALE", BASE_URL . API_VERSION . "/transactions/sale/pt_protect");

        // URL for Keyed Authorization
        define("URL_PROTECT_AUTHORIZATION", BASE_URL . API_VERSION . "/transactions/authorization/pt_protect");

        // URL for Capture Transaction
        define("URL_CAPTURE", BASE_URL . API_VERSION . "/transactions/authorization/capture");

        // URL for Create Customer(PayTrace Vault) Method
        define("URL_PROTECT_CREATE_CUSTOMER", BASE_URL . API_VERSION . "/customer/pt_protect_create");

        // URL for Create Customer(PayTrace Vault) Method
        define("URL_PROTECT_UPDATE_CUSTOMER", BASE_URL . API_VERSION . "/customer/pt_protect_update");

        // URL for Create Customer(PayTrace Vault) Method
        define("URL_PROTECT_SALE_CREATE_CUSTOMER", BASE_URL . API_VERSION . "/transactions/sale/pt_protect_customer");

        // URL to get transactions by date range.
        define("URL_EXPORT_BY_DATE_RANGE", BASE_URL . API_VERSION . "/transactions/export/by_date_range");

        // URL to get customer(s)
        define("URL_EXPORT_CUSTOMER", BASE_URL . API_VERSION . "/customer/export");
    }
}
