<?php

namespace Yoder\YIPS\Rosetta;

use Yoder\YIPS\Singleton;

defined('ABSPATH') || exit;

/**
 * Class Rosetta
 * @package Yoder\YIPS
 */

class Rosetta extends Singleton
{
    public function __construct()
    {
        $aa = $this->setting();
        dd($aa);
        exit();
    }

    public function setting()
    {
        $result = array();
        $xml = '<dm2RosettaSettings>
                  <setting>
                    <securitykey>32B32BCE9E61CD42AB89622D495B2</securitykey>
                    <user>ros</user>
                    <password>ros1234</password>
                    <maspath>C:\SAGE100A\MAS90\HOME</maspath>
                    <companycode>TST</companycode>
                  </setting>
                </dm2RosettaSettings>';
                
        $xml = '<rosetta>
  <securitykey>32B32BCE9E61CD42AB89622D495B2</securitykey>
  <function>GetOpenInvoiceData</function>
  <xml>
    <ARDivisionNo>00</ARDivisionNo>
    <CustomerNo>0000004</CustomerNo>
  </xml>
</rosetta>';

        $url = 'http://10.0.0.9:55555';
        $request_data = $xml;
        $response = wp_remote_post(
            $url,
            array(
                'method'      => 'GET',
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
        }

        $result['response'] = $response;
        return $result;
    }
}
