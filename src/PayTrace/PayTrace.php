<?php

namespace Yoder\YIPS\PayTrace;

use Yoder\YIPS\Singleton;
use Yoder\YIPS\WPLogger;
use Yoder\YIPS\Helper;
use Yoder\YIPS\Invoice\Invoice;
use Yoder\YIPS\User\UserMeta;
use Yoder\YIPS\Rosetta\Rosetta;
use Yoder\YIPS\Config;
use Yoder\YIPS\Schema;
use Yoder\YIPS\TemplateLoader;

defined('ABSPATH') || exit;

/**
 * Class PayTrace
 * @package Yoder\YIPS
 */

class PayTrace extends Singleton
{
    /**
     * Contains plugin configuration.
     *
     * @var $config
     */
    private $config = null;

    /**
     * The template loader.
     *
     * @var $loader
     */
    private $loader = null;

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
     * Holds HTTP request data .
     *
     * @var $request_data
     */
    private $request_data = array();

    /**
     * Holds customer status/info.
     *
     * @mix $customer_exist
     */
    private $customer_exist = '';

    /**
     * Holds customer status/info.
     *
     * @mix $customer_exist
     */
    private const TO_EMAIL = 'breschly@yoderoil.com';


    /**
     * Construct the plugin.
     */
    public function __construct()
    {
        $this->loader = TemplateLoader::instance();
        $this->config = (Config::instance())->get_config('paytrace');
        $this->logger = WPLogger::instance();
        $this->util = Utilities::instance();
    }

    /**
     * Get transactions details by date range.
     * @param  string $start_date
     * @param  string $end_date
     * @return []
     */
    public function get_transactions_by_date_range($start_date, $end_date)
    {
        if (empty($start_date) || empty($end_date)) {
            $this->logger->log('get_transactions_by_date_range()');
            $this->logger->log('start_date or end_date is missing');
            return;
        }

        // Convert date format that API accepts.
        $start_date = date('m/d/Y', strtotime($start_date));
        $end_date = date('m/d/Y', strtotime($end_date));

        $result = array();

        $oauth_result = $this->util->oAuthTokenGenerator();
        $is_error = $this->util->isFoundOAuthTokenError($oauth_result);

        // Error found.
        if ($is_error === true) {
            $this->logger->log('get_transactions_by_date_range(): $is_error');
            $this->logger->log($this->util->getFoundOAuthTokenError($oauth_result));
            $this->logger->log($oauth_result['response']);
            $result['error_message'] = __("Couldn't create a PayTrace connection. Please contact administrator", 'yips-customization');
            return $result;
        }

        $json = (Helper::instance())->jsonDecode($oauth_result['temp_json_response']);
        $oauth_token = sprintf("Bearer %s", $json['access_token']);

        $headers = array(
            'Content-type' => 'application/json',
            'Authorization' => $oauth_token
        );

        $request_data = array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'include_bin' => true,
            'integrator_id' => $this->config['integrator_id']
        );

        $request_data = json_encode($request_data);

        $response = wp_remote_post(
            URL_EXPORT_BY_DATE_RANGE,
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
            $result['error_message'] = $response->get_error_message();
            $result['response'] = $response;
            return $result;
        } else {
            $json = wp_remote_retrieve_body($response);
            $api_result = (Helper::instance())->jsonDecode($json);

            if ($api_result['success'] != 1) {
                $result['error_message'] = $api_result['status_message'];
                return $result;
            } else {
                $result['error_message'] = '';
                $result['exported_transactions'] = $api_result['transactions'];
                $result['response'] = $response;
            }
        }

        return $result;
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

        // Protect oauth to generate key
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
        $result = array(
            'error_message' => '',
            'transaction_success' => false
        );

        if (!isset($_POST['invoice_submit']) || $_POST['invoice_submit'] !== 'Submit') {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'invoice-nonce')) {
            die('Are you cheating?');
        }

        if (!isset($_POST['invoice'])) {
            $result['error_message'] = __('Please select invoice(s)', 'yips-customization');
            $result['transaction_success'] = false;
            return $result;
        }


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

        /*$paying_customer = $this->is_customer_exist_in_paytrace($oauth_token);
        if (isset($paying_customer['error_message']) && !empty($paying_customer['error_message'])) {
            return $paying_customer;
        } else {
            $this->customer_exist = $paying_customer['customer_exist'];
        }*/

        $transaction_result = $this->buildTransaction($oauth_token);
        if (isset($transaction_result['transaction_success']) && $transaction_result['transaction_success'] === true) {
            $this->on_transaction_success($transaction_result);
        } else {
            $this->on_transaction_fail($transaction_result);
            return $transaction_result;
        }
    }

    /**
     * Save info on successful transaction.
     *
     * @param array $transaction_result
     */
    public function on_transaction_success($transaction_result)
    {
        $t_response = $transaction_result['transaction_response'];
        $json_response = $t_response['temp_json_response'];
        $transaction_data = json_decode($json_response);

        // Save invoice info.
        $invoices['invoice'] = array_keys($_POST['invoice']);
        $invoices['user_id'] = get_current_user_id();

        // Save transaction info.
        global $wpdb;
        $table = $wpdb->prefix . Schema::PAYTRACE_RESPONSE_TABLE;
        $data = array(
            'user_id' => get_current_user_id(),
            'response' => $json_response,
            'transaction_id' => $transaction_data->transaction_id,
            'invoices' => implode(',', $invoices['invoice']),
            'status' => 'success',
            'date_created' => date('Y-m-d H:i:s')
        );

        $format = array('%d', '%s', '%s', '%s', '%s', '%s');
        $wpdb->insert($table, $data, $format);

        
        (UserMeta::instance())->save_user_invoice_data(get_current_user_id(), $invoices);

        // Send email to customer.
        $this->send_invoice_to_customer_email($invoices, $json_response);

        // Redirect to thank you page.
        wp_redirect(('/' . Invoice::THANK_YOU_PAGE));
        exit;
    }

    /**
     * Save info on failed transaction.
     *
     * @param array $transaction_result
     */
    public function on_transaction_fail($transaction_result)
    {
        $t_response = $transaction_result['transaction_response'];
        $json_response = $t_response['temp_json_response'];

        // Save transaction info.
        global $wpdb;
        $table = $wpdb->prefix . Schema::PAYTRACE_RESPONSE_TABLE;
        $data = array(
            'user_id' => get_current_user_id(),
            'response' => $json_response,
            'transaction_id' => '',
            'invoices' => '',
            'status' => 'fail',
            'date_created' => date('Y-m-d H:i:s')
        );

        $format = array('%d', '%s', '%s', '%s', '%s', '%s');
        $wpdb->insert($table, $data, $format);
    }

    /**
     * Send email to customer with invoice details.
     **/
    public function send_invoice_to_customer_email($invoices, $json_response)
    {
        if (empty($invoices['invoice'])) {
            return;
        }

        $user = get_userdata(get_current_user_id());
        if (empty($user)) {
            return;
        }

        if (empty($this->request_data['billing_address']['name'])) {
            $hi_message = '';
        } else {
            $hi_message = $this->request_data['billing_address']['name'];
        }

        $to = $user->user_email;
        $subject = 'Receipt - Yoder Oil Invoice(s) Paid';
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: YoderOil <no-reply@yoderoil.com>',
        );

        $data = array(
            'user' => $user,
            'customer_name' => $hi_message,
            'invoices' => $invoices['invoice'],
            'request_data' => $this->request_data,
            'transaction' => json_decode($json_response),
            'date' => date('M d, Y'),
        );

        $body = $this->loader->get_template(
            'customer-invoice-details.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/email/',
            false
        );

        wp_mail($to, $subject, $body, $headers);
        wp_mail(self::TO_EMAIL, $subject, $body, $headers);
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
        if (!empty($customer['error_message'])) {
            throw new \Exception('Customer data not found');
        }

        $customer = array_filter($customer['customer']);

        // Find customer category/class.
        $customer_cat = '';
        if (isset($customer['UDF_CUSTCAT'])) {
            $customer_cat = trim($customer['UDF_CUSTCAT']);
        }


        $invoice_amount = 0;
        $convenience_fee = 0;

        $invoices = $_POST['invoice'];
        $invoice_numbers = array();
        foreach ($invoices as $inv_num => $invoice) {
            $invoice_amount = $invoice_amount + $invoice;
            $invoice_numbers[] = $inv_num;
        }

        $invoice_numbers = implode(',', $invoice_numbers);

        $invoice_amount = number_format($invoice_amount, 2, '.', '');
        $convenience_fee = 0.03 * $invoice_amount;

        $amount_with_convenience_fee = $invoice_amount + $convenience_fee;
        $amount_with_convenience_fee = number_format($amount_with_convenience_fee, 2, '.', '');

        if ($customer_cat === 'D') {
            $final_amount = $amount_with_convenience_fee;
        } else {
            $final_amount = $invoice_amount;
        }


        $hpf_token = $_POST['HPF_Token'];
        $enc_key = $_POST['enc_key'];
        //$amount = $_POST['amount'];
        $request_data = array(
            "amount" => $final_amount,
            "hpf_token" => $hpf_token,
            "invoice_id" => substr($invoice_numbers, 0, 50),
            "enc_key" => $enc_key,
            "integrator_id" => $this->config['integrator_id'],
            'discretionary_data' => array(
                //'Invoice Numbers' => $invoice_numbers,
                'Customer #' => $customer['CustomerNo'],
                'WP User ID' => get_current_user_id(),
            ),
            "billing_address" => array(
                "name" => ($customer['CustomerName']) ?: '',
                "street_address" => ($customer['AddressLine1']) ?: '',
                "street_address2" => ($customer['AddressLine2']) ?: '',
                "city" => ($customer['City']) ?: '',
                "state" => ($customer['State']) ?: '',
                "zip" => ($customer['ZipCode']) ?: ''
            )
        );

        // log requested data. remove sensitive info.
        $temp_request_data = $request_data;
        unset($temp_request_data['enc_key']);
        unset($temp_request_data['HPF_Token']);
        $this->logger->log('buildRequestData()');
        $this->logger->log($temp_request_data);
        unset($temp_request_data);

        // Customer exist.
        /*if ($this->customer_exist !== false) {
            unset($request_data['customer_id']);
        }*/

        $this->request_data = $request_data;

        $request_data = json_encode($this->request_data);
        return $request_data;
    }

    /**
     * Check if customer exist in paytrace system.
     * 
     * @param  string $customer_id
     * @param  string $end_date
     * @return array | false
     */
    public function is_customer_exist_in_paytrace($oauth_token)
    {

        $customer = (Rosetta::instance())->get_customer();
        if (!empty($customer['error_message'])) {
            throw new \Exception('Customer data not found');
        }

        $customer = array_filter($customer['customer']);
        $customer_id =  $customer['CustomerNo'];

        $result = array();

        $headers = array(
            'Content-type' => 'application/json',
            'Authorization' => $oauth_token
        );

        $request_data = array(
            'customer_id' => $customer_id,
            'integrator_id' => $this->config['integrator_id']
        );

        $request_data = json_encode($request_data);

        $response = wp_remote_post(
            URL_EXPORT_CUSTOMER,
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
            $result['error_message'] = $response->get_error_message();
            $result['response'] = $response;
            $this->logger->log('is_customer_exist_in_paytrace()');
            $this->logger->log($result['response']);
            return $result;
        } else {
            $json = wp_remote_retrieve_body($response);
            $api_result = (Helper::instance())->jsonDecode($json);

            if (empty($api_result['customers'])) {
                $result['customer_exist'] = false;
            } else {
                $result['customer_exist'] = $api_result['customers'][0];
            }
        }

        return $result;
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
            $this->logger->log('verifyTransactionResult()');
            $this->logger->log($trans_result['curl_error']);
            $result['transaction_success'] = false;
            $result['transaction_response'] = $trans_result;
            return $result;
        }

        //If we reach here, we have been able to communicate with the service,
        //next is decode the JSON response and then review HTTP Status code, response_code and success of the response

        $json = (Helper::instance())->jsonDecode($trans_result['temp_json_response']);

        if ($trans_result['http_status_code'] != 200) {
            if ($json['success'] === false) {
                $this->logger->log('verifyTransactionResult(): $json===false');
                $this->logger->log($trans_result['response']);
                $result['error_message'] = $json['status_message'];
                $this->logger->log($result['error_message']);
                $result['transaction_success'] = false;
                $result['transaction_response'] = $trans_result;
                return $result;
            } else {
                $this->logger->log('verifyTransactionResult(): $json===else');
                $this->logger->log($trans_result['response']);
                $result['error_message'] = __('Request Error occurred. Please try again or contact administrator.', 'yips-customization');
                $result['transaction_success'] = false;
                $result['transaction_response'] = $trans_result;
                return $result;
            }
        } else {
            // Do your code when Response is available and based on the response_code.
            // Please refer PayTrace-Error page for possible errors and Response Codes
            // For transaction successfully approved
            //if ($json['success'] == true && ($json['response_code'] == 101 || $json['response_code'] == 165 || $json['response_code'] == 167)) {
            if ($json['success'] == true && ($json['response_code'] == 101)) {
                $result['error_message'] = '';
                $result['transaction_success'] = true;
                $result['transaction_response'] = $trans_result;
                return $result;
            } else {
                $this->logger->log('verifyTransactionResult(): else');
                $this->logger->log($trans_result['response']);
                $result['error_message'] = __('The API returned response other than 101. Please try again or contact administrator.', 'yips-customization');
                $result['transaction_success'] = false;
                $result['transaction_response'] = $trans_result;
                //Do you code here for any additional verification such as - Avs-response and CSC_response as needed.
                //Please refer PayTrace-Error page for possible errors and Response Codes
                //success = true and response_code == 103 approved but voided because of CSC did not match.
            }
        }

        return $result;
    }
}
