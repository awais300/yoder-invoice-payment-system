<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class UserRoles
 * @package Yoder\YIPS
 */

class Helper
{
    public static function get_headers_for_email($from_email, $from_name = '')
    {
        if(empty($from_email)){
             throw new \Exception('From email is missing');
        }
       
        if(empty($from_name)) {
            $from_name = get_bloginfo('name');
        }

        $headers  = "From: {$from_name} <{$from_email}>\n";
        //$headers .= "Cc: testsite <mail@testsite.com>\n";
        //$headers .= "X-Sender: testsite <mail@testsite.com>\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();
        $headers .= "X-Priority: 1\n"; // Urgent message!
        //$headers .= "Return-Path: mail@testsite.com\n"; // Return path for errors
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=iso-8859-1\n";

        return $headers;
    }
}
