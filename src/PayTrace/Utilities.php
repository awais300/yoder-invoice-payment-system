<?php

namespace Yoder\YIPS\PayTrace;

use Yoder\YIPS\Helper;
use Yoder\YIPS\Singleton;

defined('ABSPATH') || exit;

/*
 * This file contains all the common functions that are accessilbe and used on sample codes for PayTrace.
 */


/**
 * Class Utilities
 * @package Yoder\YIPS
 */

class Utilities extends Singleton
{
    /**
     * This function will make a request to accuire the OAuth token
     * Returns an array with Json response, Curl_error and http status code of the request.
     *
     * @return array
     */
    public function oAuthTokenGenerator()
    {

        // array variable to store the Response value, httpstatus code and curl error.
        $result = array(
            'temp_json_response' => '',
            'curl_error' => '',
            'http_status_code' => ''
        );

        //set up oauth_data request
        $request_data = array(
            'grant_type' => PAYTRACE_GRANT_TYPE,
            'username' => PAYTRACE_USERNAME,
            'password' => PAYTRACE_PASSWORD
        );

        $response = wp_remote_post(
            URL_OAUTH,
            array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking'    => true,
                'sslverify'    => false,
                'headers'     => array(),
                'body'        => $request_data
            )
        );

        if (is_wp_error($response)) {
            $result['success'] = false;
            $result['curl_error'] = $response->get_error_message();
        } else {
            $result['success'] = true;
            $result['temp_json_response'] = wp_remote_retrieve_body($response);
            $result['http_status_code'] =  wp_remote_retrieve_response_code($response);
        }

        $result['response'] = $response;
        return $result;
    }

    /**
     * This function will make a request by sending the OAuth token in header
     * Returns an array with Json response, Curl_error and http status code of the request.
     * Respone also contains the client key.
     * 
     * @param string $oauth_token
     * @return array
     */
    public function ProtectAuthTokenGenerator($oauth_token)
    {

        // array variable to store the Response value, httpstatus code and curl error.
        $result = array(
            'temp_json_response' => '',
            'curl_error' => '',
            'http_status_code' => ''
        );

        $headers = array(
            'Content-type' => 'application/json',
            'Authorization' => $oauth_token
        );

        $response = wp_remote_post(
            URL_PROTECTAUTH,
            array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking'    => true,
                'sslverify'    => false,
                'headers'     => $headers,
                'body'        => array()
            )
        );

        if (is_wp_error($response)) {
            $result['success'] = false;
            $result['curl_error'] = $response->get_error_message();
        } else {
            $result['success'] = true;
            $result['temp_json_response'] = wp_remote_retrieve_body($response);
            $result['http_status_code'] =  wp_remote_retrieve_response_code($response);
        }

        $result['response'] = $response;
        return $result;
    }

    /**
     * This function will actually execute the transaction based on the request data, url and OAuth token
     * Returns an array with Json response, Curl_error and http status code of the request.
     * 
     * @param string $oauth_token
     * @param Array $request_data
     * @param string $url API endpoint.
     * @return array
     */
    public function processTransaction($oauth_token, $request_data, $url)
    {
        // array variable to store the Response value, httpstatus code and curl error.
        $result = array(
            'temp_json_response' => '',
            'curl_error' => '',
            'http_status_code' => ''
        );

        $headers = array(
            'Content-type' => 'application/json',
            'Authorization' => $oauth_token
        );

        $response = wp_remote_post(
            $url,
            array(
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking'    => true,
                'sslverify'    => false,
                'headers'     => $headers,
                'body'        => $request_data
            )
        );

        if (is_wp_error($response)) {
            $result['success'] = false;
            $result['curl_error'] = $response->get_error_message();
        } else {
            $result['success'] = true;
            $result['temp_json_response'] = wp_remote_retrieve_body($response);
            $result['http_status_code'] =  wp_remote_retrieve_response_code($response);
        }

        $result['response'] = $response;
        return $result;
    }

    /**
     * This function will check for the OAuth request error.
     * Returns the boolean flag
     *
     * @param array $oauth_response
     * @return boolean
     */
    public function isFoundOAuthTokenError($oauth_response)
    {
        //set a variable with default 'false' value assuming some error occurred.
        $bool_oauth_error = false;

        //Handle curl level for OAuth error, ExitOnCurlError
        if ($oauth_response['curl_error']) {
            $bool_oauth_error = true;
            return $bool_oauth_error;
        }

        //If we reach here, we have been able to communicate with the service,
        //next is decode the json response and then review Http Status code of the request
        //and move forward with further request.

        if ($oauth_response['http_status_code'] != 200) {
            $bool_oauth_error = true;
        } else {
            // Reaching at this point means OAuth request was successful.
            $bool_oauth_error = false;
        }

        return $bool_oauth_error;
    }

    /**
     * This function will check for the OAuth request error.
     * retirm the error if any.
     * 
     * @param array $oauth_response
     * @return string
     */
    public function getFoundOAuthTokenError($oauth_response)
    {
        $error_message = '';

        //Handle curl level for OAuth error, ExitOnCurlError
        if ($oauth_response['curl_error']) {
            $error_message = $oauth_response['curl_error'];
        }

        //If we reach here, we have been able to communicate with the service,
        //next is decode the json response and then review Http Status code of the request
        //and move forward with further request.

        $json = (Helper::instance())->jsonDecode($oauth_response['temp_json_response']);

        if ($oauth_response['http_status_code'] != 200) {

            if (!empty($oauth_response['temp_json_response'])) {
                //unsuccessful OAuth Json response
                $error_message = $this->getHttpStatus($oauth_response['http_status_code']) . ' ' . $this->getOAuthError($json);
            } else {
                //in case of some other error, utilize the httpstatus code and message.
                $error_message = "OAuth Request Error!" . $this->getHttpStatus($oauth_response['http_status_code']);
            }
        }

        return $error_message;
    }


    /**
     * This function to display individual keys of unsuccessful OAuth Json response 
     * turns into OAuth error response
     *
     * @param string $json_string
     * @return string
     */
    public function getOAuthError($json_string)
    {
        $oauth_error = 'OAuth Error: ' . $json_string['error'];
        $oauth_error .= '<br/>' . $json_string['error_description'];
        return $oauth_error;
    }

    /**
     * This function is used to display the http status
     *
     * @param int $http_status_code
     * @return string
     */
    public function getHttpStatus($http_status_code)
    {
        return $this->httpStatusInfo($http_status_code);
    }

    /**
     * This function will find the associated status message from the file
     * used in Parse_ini_file( path to the file)
     * Returns the status code and message as a string.
     * HttpCodeinfo.ini file contains all the associated message with http status
     * 
     * @param int $http_status_code
     * @return string
     */
    public function httpStatusInfo($http_status_code)
    {
        $http_message = parse_ini_file(untrailingslashit(YIPS_PAYTRACE_DIR_PATH) . '/HttpcodeInfo.ini');
        $http_info =  $http_status_code . " " . $http_message[$http_status_code];
        return $http_info;
    }
}
