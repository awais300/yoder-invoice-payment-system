<?php

namespace Yoder\YIPS\User;

use Yoder\YIPS\Helper;
use Yoder\YIPS\Singleton;
use Yoder\YIPS\TemplateLoader;

defined('ABSPATH') || exit;

/**
 * Class UserMeta
 * @package Yoder\YIPS
 */

class UserMeta extends Singleton
{
    /**
     * The template loader.
     *
     * @var $loader
     */
    private $loader = null;

    /**
     * Customer Sage ID.
     *
     * @var META_CUSTOMER_SAGE_ID
     */
    public const META_CUSTOMER_SAGE_ID = 'yoder_customer_sage_id';

    /**
     * PayTrace customer ID.
     *
     * @var META_IS_PAYTRACE_CUSTOMER_CREATED
     */
    public const META_IS_PAYTRACE_CUSTOMER_CREATED = 'yoder_is_paytrace_customer_created';

    /**
     * The from email.
     *
     * @var FROM_EMAIL
     */
    public const FROM_EMAIL = 'no-reply@yoderoil.com';

    /**
     * Contains customer invoice information.
     *
     * @var META_USER_INVOICE_DATA
     */
    public const META_USER_INVOICE_DATA = 'yoder_customer_invoice_data';

    /**
     * Construct the plugin.
     */
    public function __construct()
    {
        $this->loader = TemplateLoader::instance();
        add_action('user_new_form', array($this, 'display_user_meta_fields'));
        add_action('show_user_profile', array($this, 'display_user_meta_fields'));
        add_action('edit_user_profile', array($this, 'display_user_meta_fields'));

        add_action('user_register', array($this, 'save_user_meta_fields'));
        add_action('personal_options_update', array($this, 'save_user_meta_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_meta_fields'));

        add_filter('wp_new_user_notification_email', array($this, 'yoder_wp_new_user_notification_email'), 10, 3);
    }

    /**
     * Save customer invoice information.
     *
     * @param int $user_id
     * @param Array $user_data
     */
    public function save_user_invoice_data($user_id, $user_data)
    {
        if (empty($user_id)) {
            return false;
        }

        if (is_user_logged_in()) {
            $saved_data = $this->get_user_invoice_data($user_id);
            if (is_array($saved_data)) {
                $saved_invoices = $saved_data['invoice'];
            } else {
                $saved_invoices = array();
            }

            if (empty($saved_data) || empty($saved_invoices)) {
                update_user_meta($user_id, self::META_USER_INVOICE_DATA, $user_data);
            } else {
                $user_data['invoice'] = array_merge($saved_invoices, $user_data['invoice']);
                update_user_meta($user_id, self::META_USER_INVOICE_DATA, $user_data);
            }
        }
    }

    /**
     * Get customer invoice information.
     *
     * @param int $user_id
     * @return Array
     */
    public function get_user_invoice_data($user_id)
    {
        if (empty($user_id)) {
            return false;
        }

        if (is_user_logged_in()) {
            return get_user_meta($user_id, self::META_USER_INVOICE_DATA, true);
        }
    }

    /**
     * Display user meta field.
     *
     * @param WP_User $user
     */
    public function display_user_meta_fields($user)
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $data = array(
            'user' => $user,
        );

        $this->loader->get_template(
            'user-profile.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/admin/',
            true
        );
    }

    /**
     * Save user meta field.
     *
     * @param int $user_id
     */
    public function save_user_meta_fields($user_id)
    {

        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permission', 'Access Error');
            exit();
        }

        update_user_meta($user_id, self::META_CUSTOMER_SAGE_ID, sanitize_text_field($_POST[self::META_CUSTOMER_SAGE_ID]));
    }

    /**
     * Get customer Sage ID.
     *
     * @param int $user_id
     * @return string
     */
    public function get_sage_id($user_id)
    {
        if (empty($user_id)) {
            return false;
        }

        return get_user_meta($user_id, self::META_CUSTOMER_SAGE_ID, true);
    }

    /**
     * Change from name and from email address to send email notification.
     *
     * @param Array $new_user_email
     * @param WP_User $user
     * @param string $blogname
     * @return Array
     */
    public function yoder_wp_new_user_notification_email($new_user_email, $user, $blogname)
    {
        if (is_a($user, 'WP_User') && $user->exists()) {
            if (!in_array(UserRoles::ROLE_YODER_INVOICE_CUSTOMER, (array) $user->roles)) {
                return $new_user_email;
            }
        }

        // Generate a new key
        $key = get_password_reset_key($user);
        $message  = sprintf(__('Username: %s'), $user->user_email) . "<br/><br/>";
        $message .= __('To set your password, visit the following address:') . "<br/><br/>";
        $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . "<br/><br/>";

        $new_user_email['subject'] = sprintf('Welcome to %s', $blogname);
        $new_user_email['message'] = $message;
        $new_user_email['headers'] = (Helper::instance())->get_headers_for_email(self::FROM_EMAIL);
        return $new_user_email;
    }
}
