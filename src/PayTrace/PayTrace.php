<?php

namespace Yoder\YIPS\PayTrace;

use Yoder\YIPS\Singleton;
use Yoder\YIPS\WPLogger;
use Yoder\YIPS\Helper;
use Yoder\YIPS\Invoice\Invoice;
use Yoder\YIPS\User\UserMeta;
use Yoder\YIPS\Rosetta\Rosetta;

defined('ABSPATH') || exit;

/**
 * Class PayTrace
 * @package Yoder\YIPS
 */

class PayTrace extends Singleton
{
    /**
     * Log information to file.
     *
     * @var $logger
     */
    private $logger = null;

    /**
     * Contains utility functions for PayTrace.
     *
     * @var $logger
     */
    private $util = null;

    /**
     * Construct the plugin.
     */
    public function __construct()
    {
        $this->logger = WPLogger::instance();
        $this->util = Utilities::instance();
    }

    /**
     * Get client key via PayTrace API.
     * @return array
     */
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
            $result['error_message'] = __("Couldn't create a PayTrace connection to process payment. Please contact administrator", 'yips-customization');
            $result['client_key'] = '';
            return $result;
        }

        $json = (Helper::instance())->jsonDecode($oauth_result['temp_json_response']);
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

        $json = (Helper::instance())->jsonDecode($api_result['temp_json_response']);
        $result['client_key'] = $json['clientKey'];
        $result['error_message'] = '';
        return $result;
    }

    /**
     * Process payment when invoice form is submit.
     * on success redirect to thank you page on failure return error.
     *
     * @return Array contains error info.
     */
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
            $result['error_message'] = __("Couldn't create a PayTrace connection to process payment. Please contact administrator", 'yips-customization');
            $result['transaction_success'] = false;
            return $result;
        }

        $json = (Helper::instance())->jsonDecode($oauth_result['temp_json_response']);
        $oauth_token = sprintf("Bearer %s", $json['access_token']);
        $transaction_result = $this->buildTransaction($oauth_token);


        if (isset($transaction_result['transaction_success']) && $transaction_result['transaction_success'] === true) {
            // Transaction success.
            $invoices['invoice'] = array_keys($_POST['invoice']);
            $invoices['user_id'] = get_current_user_id();
            (UserMeta::instance())->save_user_invoice_data(get_current_user_id(), $invoices);
            wp_redirect(('/' . Invoice::THANK_YOU_PAGE));
            exit;
        } else {
            return $transaction_result;
        }
    }

    /**
     * Build transaction and process it via API.
     *
     * @param string $oauth_token.
     * @return Array
     */
    public function buildTransaction($oauth_token)
    {
        $request_data = $this->buildRequestData();
        $result = $this->util->processTransaction($oauth_token, $request_data, URL_PROTECT_SALE);
        return $this->verifyTransactionResult($result);
    }

    /**
     * Build request data to be sent via PayTrace API.
     * @return Array
     */
    public function buildRequestData()
    {
        $customer = (Rosetta::instance())->get_customer();
        $customer = $customer['customer'];

        $hpf_token = $_POST['HPF_Token'];
        $enc_key = $_POST['enc_key'];
        $amount = $_POST['amount'];
        $request_data = array(
            "amount" => $amount,
            "hpf_token" => $hpf_token,
            "enc_key" => $enc_key,
            "integrator_id" => "967174xd2CvC",
            "billing_address" => array(
                "name" => $customer['CustomerName'],
                "street_address" => $customer['AddressLine1'],
                "street_address2" => $customer['AddressLine2'],
                "city" => $customer['City'],
                "state" => $customer['State'],
                "zip" => $customer['ZipCode']
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

    /**
     * Verify the transaction and return the response.
     *
     * @param Array $trans_result.
     * @return Array
     */
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

        $json = (Helper::instance())->jsonDecode($trans_result['temp_json_response']);

        if ($trans_result['http_status_code'] != 200) {
            if ($json['success'] === false) {
                $this->logger->log('verifyTransactionResult(): $json===false');
                $this->logger->log($trans_result['response']);
                $result['error_message'] = __('Transaction Error occurred. Please try again or contact administrator.', 'yips-customization');
                $result['transaction_success'] = false;
                return $result;
            } else {
                $this->logger->log('verifyTransactionResult(): $json===else');
                $this->logger->log($trans_result['response']);
                $result['error_message'] = __('Request Error occurred. Please try again or contact administrator.', 'yips-customization');
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
                $result['transation_response'] = $trans_result;
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
