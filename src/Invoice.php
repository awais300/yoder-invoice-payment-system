<?php
namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class Invoice
 * @package Yoder\YIPS
 */

class Invoice
{
    private $loader = null;

    public function __construct()
    {
        $this->loader = TemplateLoader::get_instance();
        add_action('init', array($this, 'yoder_register_shortcode'));
    }

    public function yoder_register_shortcode() {
        add_shortcode('invoice-form', array($this, 'display_invoice_form'));
    }

    public function display_invoice_form() {
        if(! $this->allowed_access()) {
            wp_redirect('/');
            exit;
        }

        $data = array();
        $html = $this->loader->get_template(
            'invoice.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/',
            false
        );

        return trim( $html );
    }

    public function allowed_access() {
        if(! is_user_logged_in()) {
            return false;
        }

        $allowed_roles = array('administrator', UserRoles::ROLE_YODER_INVOICE_CUSTOMER);
        $user_roles = wp_get_current_user()->roles;

        if(empty($user_roles)) {
            return false;
        }

        foreach($user_roles as $role) {
            if(in_array($role, $allowed_roles)) {
                return true;
            }
        }

        return false;
    }
}