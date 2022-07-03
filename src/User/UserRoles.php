<?php

namespace Yoder\YIPS\User;

use Yoder\YIPS\Singleton;

defined('ABSPATH') || exit;

/**
 * Class UserRoles
 * @package Yoder\YIPS
 */

class UserRoles extends Singleton
{
    /**
     * User custom role.
     *
     * @var ROLE_YODER_INVOICE_CUSTOMER
     */
    public const ROLE_YODER_INVOICE_CUSTOMER = 'yoder-invoice-customer';

    /**
     * Add user to custom role.
     *
     */
    public function add_role()
    {
        remove_role(self::ROLE_YODER_INVOICE_CUSTOMER);
        add_role(
            self::ROLE_YODER_INVOICE_CUSTOMER,
            'Yoder Invoice Customer',
            get_role('subscriber')->capabilities
        );
    }

    /**
     * Remove custom role.
     *
     */
    public function remove_role()
    {
        remove_role(self::ROLE_YODER_INVOICE_CUSTOMER);
    }

    /**
     * Check whether user has required access.
     * 
     * @return boolean
     */
    public function user_has_access()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $allowed_roles = array('administrator', self::ROLE_YODER_INVOICE_CUSTOMER);
        $user_roles = wp_get_current_user()->roles;

        if (empty($user_roles)) {
            return false;
        }

        foreach ($user_roles as $role) {
            if (in_array($role, $allowed_roles)) {
                return true;
            }
        }

        return false;
    }
}
