<?php

namespace Yoder\YIPS\Rosetta;

use Yoder\YIPS\Singleton;
use Yoder\YIPS\Helper;
use Yoder\YIPS\User\UserMeta;
use Yoder\YIPS\WPLogger;
use Yoder\YIPS\Config;

defined('ABSPATH') || exit;

/**
 * Class Rosetta
 * @package Yoder\YIPS
 */

class Rosetta extends Singleton
{
    /**
     * Log information.
     *
     * @var $logger
     */
    private $logger = null;

    /**
     * Contains plugin configuration.
     *
     * @var $logger
     */
    private $config = null;

    /**
     * Construct the plugin.
     */
    public function on_construct()
    {
        $this->config = (Config::instance())->get_config('rosetta');
        $this->logger = WPLogger::instance();

        // Debug
        if (isset($_GET['yoder_api_debug'])) {
            $test = $this->test();
            dd($test);
            exit();
        }
    }

    /**
     * Test function
     **/
    public function test()
    {

        $xml = "<rosetta>
          <securitykey>{$this->config['security_key']}</securitykey>
          <function>GetCustomerData</function>
          <xml>
            <ExtSourceID>YODERWEB</ExtSourceID>
            <ARDivisionNo>00</ARDivisionNo>
            <CustomerNo>0001067</CustomerNo>
          </xml>
        </rosetta>";

        $response = $this->yoder_remote_get($xml);
        return $response;
    }

    /**
     * Get customer invoices.
     */
    public function get_invoices()
    {
        $security_key = $this->config['security_key'];
        $result = array();
        $customer_id = (UserMeta::instance())->get_sage_id(get_current_user_id());
        $request_xml = "<rosetta>
                          <securitykey>{$security_key}</securitykey>
                          <function>GetOpenInvoiceData</function>
                          <xml>
                            <ExtSourceID>YODERWEB</ExtSourceID>
                            <ARDivisionNo>00</ARDivisionNo>
                            <CustomerNo>{$customer_id}</CustomerNo>
                          </xml>
                        </rosetta>";

        $response = $this->yoder_remote_get($request_xml);

        if (!empty($response['error_message'])) {
            $result['error_message'] = $response['error_message'];
            $result['invoices'] = array();
            return $result;
        }

        $xml = $response['response'];
        $xml = @simplexml_load_string($xml);
        if ($xml === false) {
            throw new \Exception('Invalid XML response');
        }
        $xml_array = (Helper::instance())->xmlToArray($xml);

        if ($xml_array['rosetta']['ErrorStatus'] != 0) {
            $result['error_message'] = $xml_array['rosetta']['ErrorMessage'];
            $result['invoices'] = array();
        } else {
            $result['error_message'] = '';
            $result['invoices'] = $xml_array['rosetta']['invoice'];
        }

        return $result;
    }

    /**
     * Get due invoices.
     */
    public function get_due_invoices()
    {
        $invoices = $this->get_invoices();

        if (!empty($invoices['error_message'])) {
            return $invoices;
        } else {
            foreach ($invoices['invoices'] as $key => $invoice) {
                if ($invoice['Balance'] <= 0) {
                    unset($invoices['invoices'][$key]);
                }
            }
        }

        $invoices = $this->filter_invoices($invoices);
        return $invoices;
    }


    /**
     * Filter invoices that are already paid.
     *
     * @param Array $invoices
     * @return Array
     */
    public function filter_invoices($invoices)
    {
        $user_data = (UserMeta::instance())->get_user_invoice_data(get_current_user_id());
        $user_saved_invoices = $user_data['invoice'];

        if (empty($user_saved_invoices)) {
            return $invoices;
        } else {
            foreach ($invoices['invoices'] as $key => $invoice) {
                if (in_array($invoice['InvoiceNo'], $user_saved_invoices)) {
                    unset($invoices['invoices'][$key]);
                }
            }
        }

        return $invoices;
    }

    /**
     * Get customer information.
     */
    public function get_customer()
    {
        $security_key = $this->config['security_key'];
        $result = array();
        $customer_id = (UserMeta::instance())->get_sage_id(get_current_user_id());
        $request_xml = "<rosetta>
                    <securitykey>{$security_key}</securitykey>
                      <function>GetCustomerData</function>
                      <xml>
                        <ExtSourceID>YODERWEB</ExtSourceID>
                        <ARDivisionNo>00</ARDivisionNo>
                        <CustomerNo>{$customer_id}</CustomerNo>
                      </xml>
                </rosetta>";

        $response = $this->yoder_remote_get($request_xml);

        if (!empty($response['error_message'])) {
            $result['error_message'] = $response['error_message'];
            $result['customer'] = array();
            return $result;
        }


        $xml = $response['response'];
        $xml = @simplexml_load_string($xml);
        if ($xml === false) {
            throw new \Exception('Invalid XML response');
        }
        $xml_array = (Helper::instance())->xmlToArray($xml);

        if ($xml_array['rosetta']['ErrorStatus'] != 0) {
            $result['error_message'] = $xml_array['rosetta']['ErrorMessage'];
            $result['customer'] = array();
        } else {
            $result['error_message'] = '';
            $result['customer'] = $xml_array['rosetta']['cust'];
        }

        return $result;
    }

    /**
     * Call Rosetta/PDI API via Socket to get the response.
     *
     * @param string $request_message
     * @param Array
     */
    public function yoder_remote_get($request_message)
    {
        $host = $this->config['ip'];
        $port = $this->config['port'];
        $bytes = 1024;
        $response_data = '';

        // Usually an XML to send to rosetta server.
        $message = trim($request_message);

        $response = array(
            'error_message' => '',
            'response' => ''
        );

        // Create socket.
        $socket = @socket_create(AF_INET, SOCK_STREAM, 0);
        if ($socket === false) {
            $error_code = socket_last_error();
            $error_msg = socket_strerror($error_code);
            $error_message = "Clouldn't create socket: [{$error_code}] {$error_msg}";

            $this->logger->log($error_message);

            $response['error_message'] = $error_message;
            $response['response'] = '';
            return $response;
        }

        // Connect socket.
        $socket_connection = @socket_connect($socket, $host, $port);
        if ($socket_connection === false) {
            $error_code = socket_last_error();
            $error_msg = socket_strerror($error_code);
            $error_message = "Clouldn't create socket connection: [{$error_code}] {$error_msg}";

            $this->logger->log($error_message);

            $response['error_message'] = $error_message;
            $response['response'] = '';
            return $response;
        }

        // Send data to rosetta server via socket.
        $socket_write = @socket_write($socket, $message, strlen($message));
        if ($socket_write === false) {
            $error_code = socket_last_error();
            $error_msg = socket_strerror($error_code);
            $error_message = "Clouldn't write to socket connection: [{$error_code}] {$error_msg}";

            $this->logger->log($error_message);

            $response['error_message'] = $error_message;
            $response['response'] = '';
            return $response;
        }

        // Read response from rosetta server via socket.
        while (($buffer = socket_read($socket, $bytes))) {
            if ($buffer === false) {
                $error_code = socket_last_error();
                $error_msg = socket_strerror($error_code);
                $error_message = "Error while reading data from socket: [{$error_code}] {$error_msg}";

                $this->logger->log($error_message);

                $response['error_message'] = $error_message;
                $response['response'] = '';
                return $response;
            }

            $response_data .= $buffer;
        }

        socket_close($socket);

        $response['error_message'] = '';
        $response['response'] = $response_data;
        return $response;
    }
}
