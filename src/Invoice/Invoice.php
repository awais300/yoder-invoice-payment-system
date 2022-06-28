<?php

namespace Yoder\YIPS\Invoice;

use Yoder\YIPS\PayTrace\PayTrace;
use Yoder\YIPS\Rosetta\Rosetta;
use Yoder\YIPS\TemplateLoader;
use Yoder\YIPS\User\UserRoles;

defined('ABSPATH') || exit;

/**
 * Class Invoice
 * @package Yoder\YIPS
 */

class Invoice
{
    private $loader = null;
    private const THANK_YOU_PAGE = '/thank-you';

    public function __construct()
    {
        $this->loader = TemplateLoader::get_instance();
        add_action('init', array($this, 'yoder_register_shortcode'));
    }

    public function yoder_register_shortcode()
    {
        add_shortcode('invoice-form', array($this, 'display_invoice_form'));
        add_shortcode('invoice-thankyou', array($this, 'display_thankyou_page'));
    }

    public function display_invoice_form()
    {
        if (!$this->allowed_access()) {
            wp_redirect('/');
            exit;
        }

        $client_key = (PayTrace::instance())->get_client_key();
        $payment = (PayTrace::instance())->process_payment();

        $invoices = (Rosetta::instance())->get_non_paid_invoices();

        $data = array(
            'invoice_obj' => $this,
            'client_key' => $client_key['client_key'],
            'payment' => $payment,
            'thankyou_page' => self::THANK_YOU_PAGE,
            'invoices' => $invoices
        );

        $html = $this->loader->get_template(
            'invoice.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/',
            false
        );

        return trim($html);
    }

    public function has_error($invoices)
    {
        if (empty($invoices['error_message']) && empty($invoices['error_message'][0])) {
            return false;
        } else {
            return true;
        }
    }

    public function has_invoice($invoices)
    {
        if (!empty($invoices['invoices']) && !empty($invoices['invoices'][0])) {
            return true;
        } else {
            return false;
        }
    }

    public function display_thankyou_page()
    {
        if (!$this->allowed_access()) {
            wp_redirect('/');
            exit;
        }

        $data = array();
        $html = $this->loader->get_template(
            'thank-you.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/',
            false
        );

        return trim($html);
    }

    public function allowed_access()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $allowed_roles = array('administrator', UserRoles::ROLE_YODER_INVOICE_CUSTOMER);
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
