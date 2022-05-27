<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class UserRoles
 * @package Yoder\YIPS
 */

class UserRoles
{
    private const ROLE_YODER_INVOICE_CUSTOMER = 'yoder-invoice-customer';
    
    public static function add_role() {
        remove_role(self::ROLE_YODER_INVOICE_CUSTOMER);
        add_role( self::ROLE_YODER_INVOICE_CUSTOMER, 'Yoder Invoice Customer', get_role( 'subscriber' )->capabilities );
    }

    public static function remove_role() {
        remove_role(self::ROLE_YODER_INVOICE_CUSTOMER);
    }
}