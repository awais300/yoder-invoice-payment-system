<?php

namespace Yoder\YIPS\Rosetta;

use Yoder\YIPS\Singleton;
use Yoder\YIPS\Helper;

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
        //$aa = $this->get_customer();
        
        if(isset($_GET['yoder_api_debug'])) {
             dd($aa);
            exit();
        }
       
    }

    public function setting()
    {
        $result = array();
       /* $xml = '<dm2RosettaSettings>
                  <setting>
                    <securitykey>32B32BCE9E61CD42AB89622D495B2</securitykey>
                    <user>ros</user>
                    <password>ros1234</password>
                    <maspath>C:\SAGE100A\MAS90\HOME</maspath>
                    <companycode>TST</companycode>
                  </setting>
                </dm2RosettaSettings>';*/

        $xml = '<rosetta>
  <securitykey>32B32BCE9E61CD42AB89622D495B2</securitykey>
  <function>GetOpenInvoiceData</function>
  <xml>
    <ARDivisionNo>00</ARDivisionNo>
    <CustomerNo>0000004</CustomerNo>
  </xml>
</rosetta>';
        
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode( '' . ':' . '' )
        );

        $headers = array();

        $url = 'https://50.240.141.89';
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
                'headers'     => $headers,
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

    public function get_invoices()
    {

        $xml = '<rosetta>
  <ErrorStatus>0</ErrorStatus>
  <ErrorMessage />
  <invoice>
    <InvoiceNo>12345</InvoiceNo>
    <InvoiceType>IN</InvoiceType>
    <InvoiceTypeDesc>Invoice</InvoiceTypeDesc>
    <InvoiceDate>20170202</InvoiceDate>
    <InvoiceDueDate>20170302</InvoiceDueDate>
    <Amount>1000</Amount>
    <Balance>123.22</Balance>
  </invoice>
  <invoice>
    <InvoiceNo>12346</InvoiceNo>
    <InvoiceType>PP</InvoiceType>
    <InvoiceTypeDesc>Prepay</InvoiceTypeDesc>
    <InvoiceDate>20170205</InvoiceDate>
    <InvoiceDueDate>20170205</InvoiceDueDate>
    <Amount>-500</Amount>
    <Balance>-500</Balance>
  </invoice>
  <invoice>
    <InvoiceNo>12444</InvoiceNo>
    <InvoiceType>IN</InvoiceType>
    <InvoiceTypeDesc>Invoice</InvoiceTypeDesc>
    <InvoiceDate>20170202</InvoiceDate>
    <InvoiceDueDate>20170302</InvoiceDueDate>
    <Amount>250</Amount>
    <Balance>22.00</Balance>
  </invoice>
</rosetta>';

        $result = array();
        $xml = @simplexml_load_string($xml);
        if ($xml === false) {
            throw new \Exception('Invalid XML response');
        }
        $xml_array = Helper::xmlToArray($xml);

        if ($xml_array['rosetta']['ErrorStatus'] != 0) {
            $result['error_message'] = $xml_array['rosetta']['ErrorMessage'];
            $result['invoices'] = array();
        } else {
            $result['error_message'] = array();
            $result['invoices'] = $xml_array['rosetta']['invoice'];
        }

        return $result;
    }

    public function get_paid_inovices() {
        
    }

    public function get_non_paid_invoices()
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

            return $invoices;
        }
    }

    public function get_customer()
    {
        $xml = '<rosetta>
  <ErrorStatus>0</ErrorStatus>
  <ErrorMessage />
  <cust>
    <ARDivisionNo>00</ARDivisionNo>
    <CustomerNo>0000003</CustomerNo>
    <CustomerName>Kalispell 3rd Ave.</CustomerName>
    <AddressLine1>***credit card clearing***</AddressLine1>
    <AddressLine2/>
    <AddressLine3/>
    <City>Kalispell</City>
    <State>MT</State>
    <ZipCode>59901</ZipCode>
    <CountryCode>USA</CountryCode>
    <TelephoneNo/>
    <TelephoneExt/>
    <FaxNo/>
    <EmailAddress/>
    <CurrentBalance>1310.39</CurrentBalance>
    <PastDueBalance>0</PastDueBalance>
  </cust>
</rosetta>';

        $result = array();
        $xml = @simplexml_load_string($xml);
        if ($xml === false) {
            throw new \Exception('Invalid XML response');
        }
        $xml_array = Helper::xmlToArray($xml);

        if ($xml_array['rosetta']['ErrorStatus'] != 0) {
            $result['error_message'] = $xml_array['rosetta']['ErrorMessage'];
            $result['customer'] = array();
        } else {
            $result['error_message'] = array();
            $result['customer'] = $xml_array['rosetta']['cust'];
        }

        return $result;
    }
}
