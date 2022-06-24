<?php

namespace Yoder\YIPS\PayTrace;

use Yoder\YIPS\Singleton;
use Yoder\YIPS\WPLogger;
use Yoder\YIPS\Helper;

defined('ABSPATH') || exit;

/**
 * Class PayTrace
 * @package Yoder\YIPS
 */

class PayTrace extends Singleton
{
    private $logger = null;
    private $util = null;

    public function __construct()
    {
        $this->logger = WPLogger::instance();
        $this->util = Utilities::instance();
    }

    public function get_client_key()
    {
        $result = array(
            'error_message' => '',
            'client_key' => ''
        );

        $oauth_result = $this->util->oAuthTokenGenerator();
        $is_error = $this->util->isFoundOAuthTokenError($oauth_result);

        // Error found.
        if ($is_error === true && 0) {
            $this->logger->log('get_client_key(): $is_error');
            $this->logger->log($this->util->getFoundOAuthTokenError($oauth_result));
            $this->logger->log($oauth_result['response']);
            $result['error_message'] = "Couldn't create a PayTrace connection to process payment. Please contact administrator";
            $result['client_key'] = '';
            return $result;
        }

        $json = Helper::jsonDecode($oauth_result['temp_json_response']);
        $oauth_token = sprintf("Bearer %s", $json['access_token']);

        // Protect oauth to genreate key
        $api_result = $this->util->ProtectAuthTokenGenerator($oauth_token);
        $response = $api_result['response'];

        if ($api_result['success'] === false) {
            $this->logger->log('get_client_key(): $api_result===false');
            $this->logger->log($api_result['response']);
            $result['error_message'] = $api_result['curl_error'];
            $result['client_key'] = '';
            return $result;
        }

        if ($api_result['success'] === true) {
            if ($api_result['http_status_code'] != 200) {
                $this->logger->log('get_client_key(): $api_result!=200');
                $this->logger->log($api_result['response']);
                $result['error_message'] = wp_remote_retrieve_response_message($response);
                $result['client_key'] = '';
                return $result;
            }
        }

        $json = Helper::jsonDecode($api_result['temp_json_response']);
        $result['client_key'] = $json['clientKey'];
        $result['error_message'] = '';
        return $result;
    }

    public function process_payment()
    {

        if (!isset($_POST['invoice_submit']) || $_POST['invoice_submit'] !== 'Submit') {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'invoice-nonce')) {
            die('Are you cheating?');
        }

        if (empty($_POST['amount'])) {
            return;
        }

        $result = array(
            'error_message' => '',
            'transaction_success' => false
        );

        $oauth_result = $this->util->oAuthTokenGenerator();
        $is_error = $this->util->isFoundOAuthTokenError($oauth_result);

        // Error found.
        if ($is_error === true) {
            $this->logger->log('process_payment(): $is_error');
            $this->logger->log($this->util->getFoundOAuthTokenError($oauth_result));
            $this->logger->log($oauth_result['response']);
            $result['error_message'] = "Couldn't create a PayTrace connection to process payment. Please contact administrator";
            $result['transaction_success'] = false;
            return $result;
        }

        $json = Helper::jsonDecode($oauth_result['temp_json_response']);
        $oauth_token = sprintf("Bearer %s", $json['access_token']);
        return $this->buildTransaction($oauth_token);
    }

    public function buildTransaction($oauth_token)
    {
        $request_data = $this->buildRequestData();
        $result = $this->util->processTransaction($oauth_token, $request_data, URL_PROTECT_SALE);
        return $this->verifyTransactionResult($result);
    }


    public function buildRequestData()
    {
        $hpf_token = $_POST['HPF_Token'];
        $enc_key = $_POST['enc_key'];
        $amount = $_POST['amount'];
        $request_data = array(
            "amount" => $amount,
            "hpf_token" => $hpf_token,
            "enc_key" => $enc_key,
            "integrator_id" => "967174xd2CvC",
            "billing_address" => array(
                "name" => "Test-Protect-Sale",
                "street_address" => "8320 E. West St.",
                "city" => "Spokane",
                "state" => "WA",
                "zip" => "85284"
            )
        );

         // log requested data. remove senstive info.
        $temp_request_data = $request_data;       
        unset($temp_request_data['enc_key']);
        unset($temp_request_data['HPF_Token']);
        $this->logger->log('buildRequestData()');
        $this->logger->log($temp_request_data);
        unset($temp_request_data);

        $request_data = json_encode($request_data);
        return $request_data;
    }

    //This function is to verify the Transaction result
    public function verifyTransactionResult($trans_result)
    {
        $result = array(
            'error_message' => '',
            'transaction_success' => false
        );

        //Handle curl level error, ExitOnCurlError
        if ($trans_result['curl_error']) {
            $result['error_message'] = $trans_result['curl_error'];
            $result['transaction_success'] = false;
            return $result;
        }

        //If we reach here, we have been able to communicate with the service,
        //next is decode the json response and then review Http Status code, response_code and success of the response

        $json = Helper::jsonDecode($trans_result['temp_json_response']);

        if ($trans_result['http_status_code'] != 200) {
            if ($json['success'] === false) {
                $this->logger->log('verifyTransactionResult(): $json===false');
                $this->logger->log($trans_result['response']);
                $result['error_message'] = 'Transaction Error occurred. Please try again or contact administrator.';
                $result['transaction_success'] = false;
                return $result;
            } else {
                $this->logger->log('verifyTransactionResult(): $json===else');
                $this->logger->log($trans_result['response']);
                $result['error_message'] = 'Request Error occurred. Please try again or contact administrator.';
                $result['transaction_success'] = false;
                return $result;
            }
        } else {
            // Do your code when Response is available and based on the response_code.
            // Please refer PayTrace-Error page for possible errors and Response Codes
            // For transation successfully approved
            if ($json['success'] == true && $json['response_code'] == 101) {
                $result['error_message'] = '';
                $result['transaction_success'] = true;
                return $result;
            } else {
                $this->logger->log('verifyTransactionResult(): else');
                $this->logger->log($trans_result['response']);
                //Do you code here for any additional verification such as - Avs-response and CSC_response as needed.
                //Please refer PayTrace-Error page for possible errors and Response Codes
                //success = true and response_code == 103 approved but voided because of CSC did not match.
            }
        }

        return $result;
    }
}
