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

    public function __construct()
    {
        $this->loader = TemplateLoader::get_instance();
        add_action('user_new_form', array($this, 'yoder_register_profile_fields'));
        add_action('show_user_profile', array($this, 'yoder_register_profile_fields'));
        add_action('edit_user_profile', array($this, 'yoder_register_profile_fields'));

        add_action('user_register', array($this, 'yoder_save_profile_fields'));
        add_action('personal_options_update', array($this, 'yoder_save_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'yoder_save_profile_fields'));
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
}
