<?php

namespace Yoder\YIPS;

use Yoder\YIPS\User\UserMeta;

defined('ABSPATH') || exit;

/**
 * Class Test
 * @package Yoder\YIPS
 */

class Test
{
    /**
     * Construct the plugin.
     */
    public function __construct()
    {
        if (isset($_GET['reset_invoice']) && $_GET['reset_invoice'] === 'yes') {
            update_user_meta(34, UserMeta::META_USER_INVOICE_DATA, '');
            update_user_meta(33, UserMeta::META_USER_INVOICE_DATA, '');
            update_user_meta(1, UserMeta::META_USER_INVOICE_DATA, '');
        }
    }
}
