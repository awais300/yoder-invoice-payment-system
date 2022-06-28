<?php

namespace Yoder\YIPS\User;
use Yoder\YIPS\Helper;
use Yoder\YIPS\TemplateLoader;

defined('ABSPATH') || exit;

/**
 * Class UserMeta
 * @package Yoder\YIPS
 */

class UserMeta
{
    private $loader = null;
    public const META_CUSTOMER_SAGE_ID = 'yoder_customer_sage_id';
    public const FROM_EMAIL = 'no-reply@yoderoil.com';
    public const META_USER_INVOICE_DATA = 'yoder_customer_invoice_data';

    public function __construct()
    {
        $this->loader = TemplateLoader::get_instance();
        add_action('user_new_form', array($this, 'display_user_meta_fields'));
        add_action('show_user_profile', array($this, 'display_user_meta_fields'));
        add_action('edit_user_profile', array($this, 'display_user_meta_fields'));

        add_action('user_register', array($this, 'save_user_meta_fields'));
        add_action('personal_options_update', array($this, 'save_user_meta_fields'));
        add_action('edit_user_profile_update', array($this, 'save_user_meta_fields'));

        add_filter('wp_new_user_notification_email', array($this, 'yoder_wp_new_user_notification_email'), 10, 3);
    }

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

    public function save_user_meta_fields($user_id)
    {

        if (!current_user_can('manage_options')) {
            return false;
        }

        update_user_meta($user_id, self::META_CUSTOMER_SAGE_ID, sanitize_text_field($_POST[self::META_CUSTOMER_SAGE_ID]));
    }

    public function yoder_wp_new_user_notification_email($new_user_email, $user, $blogname)
    {
        if (is_a($user, 'WP_User') && $user->exists()) {
            if (!in_array(UserRoles::ROLE_YODER_INVOICE_CUSTOMER, (array) $user->roles)) {
                return $new_user_email;
            }
        }

        // Generate a new key
        $key = get_password_reset_key( $user );
        $message  = sprintf(__('Username: %s'), $user->user_email) . "<br/><br/>";
        $message .= __('To set your password, visit the following address:') . "<br/><br/>";
        $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . "<br/><br/>";

        $new_user_email['subject'] = sprintf('Welcome to %s', $blogname);
        $new_user_email['message'] = $message;
        $new_user_email['headers'] = Helper::get_headers_for_email(self::FROM_EMAIL);
        return $new_user_email;
    }
}
