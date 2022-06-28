<?php

namespace Yoder\YIPS\PayTrace;
use Yoder\YIPS\Cryptor;

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

    public function __construct()
    {
        $this->init_settings();
    }

    public function init_settings()
    {
        /*echo Cryptor::encrypt('awais@effectwebagency.com');
        echo "<br/>";
        echo Cryptor::encrypt('flummox@123');
        exit;*/

        define("PAYTRACE_USERNAME", Cryptor::decrypt('c3O2OsqL078mWtnJJG9fCaP1JHOwPCRkH4B3XA0f2k+M9wAjBtIWKwjb2/GcxmAuIFJuTVJ6jkj2OlWhiIHpl8M='));
        define("PAYTRACE_PASSWORD", Cryptor::decrypt('0mI+TNKkbu+KmP30GX4mh7UJRAQ1bml6hWp9w52uLPeylqxS97SVQG9YwFuKXcSdgs6v')); //pvc
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
    }
}
