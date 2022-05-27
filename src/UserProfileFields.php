<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class UserRoles
 * @package Yoder\YIPS
 */

class UserProfileFields
{
    private $loader = null;
    public const META_CUSTOMER_SAGE_FIELD = 'customer_sage_id';
    public const FROM_EMAIL = 'no-reply@yoderoil.com';

    public function __construct()
    {
        $this->loader = TemplateLoader::get_instance();
        add_action('user_new_form', array($this, 'yoder_register_profile_fields'));
        add_action('show_user_profile', array($this, 'yoder_register_profile_fields'));
        add_action('edit_user_profile', array($this, 'yoder_register_profile_fields'));

        add_action('user_register', array($this, 'yoder_save_profile_fields'));
        add_action('personal_options_update', array($this, 'yoder_save_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'yoder_save_profile_fields'));

        add_filter('wp_new_user_notification_email', array($this, 'yoder_wp_new_user_notification_email'), 10, 3);
    }

    public function yoder_register_profile_fields($user)
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

    public function yoder_save_profile_fields($user_id)
    {

        if (!current_user_can('manage_options')) {
            return false;
        }

        update_user_meta($user_id, self::META_CUSTOMER_SAGE_FIELD, $_POST[self::META_CUSTOMER_SAGE_FIELD]);
    }

    public function yoder_wp_new_user_notification_email($new_user_email, $user, $blogname)
    {
        $message  = sprintf( __( 'Username: %s' ), $user->user_email ) . "<br/><br/>";
        $message .= __( 'To set your password, visit the following address:' ) . "<br/><br/>";
        $message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) . "<br/><br/>";

        $new_user_email['subject'] = sprintf('Welcome to %s', $blogname);
        $new_user_email['message'] = $message;
        $new_user_email['headers'] = Helper::get_headers_for_email(self::FROM_EMAIL);
        return $new_user_email;
    }
}
