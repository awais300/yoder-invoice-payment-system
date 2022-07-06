<?php

namespace Yoder\YIPS\Invoice;

use Yoder\YIPS\PayTrace\PayTrace;
use Yoder\YIPS\Rosetta\Rosetta;
use Yoder\YIPS\Singleton;
use Yoder\YIPS\TemplateLoader;
use Yoder\YIPS\User\UserRoles;

defined('ABSPATH') || exit;

/**
 * Class Invoice
 * @package Yoder\YIPS
 */

class Invoice extends Singleton
{
    /**
     * The template loader.
     *
     * @var $loader
     */
    private $loader = null;

    /**
     * The page slug.
     *
     * @var CUSTOMER_LOGIN_PAGE
     */
    public const CUSTOMER_LOGIN_PAGE = 'login';

    /**
     * The page slug.
     *
     * @var CUSTOMER_INVOICE_PAGE
     */
    public const CUSTOMER_INVOICE_PAGE = 'pay-online';


    /**
     * The Page slug
     *
     * @var THANK_YOU_PAGE
     */
    public const THANK_YOU_PAGE = 'thank-you';

    /**
     * Construct the plugin.
     */
    public function __construct()
    {
        $this->loader = TemplateLoader::instance();
        add_action('template_redirect', array($this, 'check_user_permission'));
        add_action('init', array($this, 'yoder_register_shortcode'));
    }

    /**
     * Check user permission.
     */
    public function check_user_permission()
    {
        global $post;
        $page_slug = $post->post_name;

        $slugs = array(
            self::CUSTOMER_INVOICE_PAGE,
            self::THANK_YOU_PAGE,
        );

        if (!is_user_logged_in()) {
            if (in_array($page_slug, $slugs)) {
                wp_redirect('/' . self::CUSTOMER_LOGIN_PAGE);
                exit;
            }
        }

        if (in_array($page_slug, $slugs)) {
            if (!(UserRoles::instance()->user_has_access())) {
                wp_redirect('/');
                exit;
            }
        }
    }

    /**
     * Register shortcode.
     */
    public function yoder_register_shortcode()
    {
        add_shortcode('invoice-form', array($this, 'display_invoice_form'));
        add_shortcode('invoice-thankyou', array($this, 'display_thankyou_page'));
    }

    /**
     * Display invoice form.
     * 
     * @return string
     */
    public function display_invoice_form()
    {
        // PayTrace data.
        $client_key = (PayTrace::instance())->get_client_key();
        $payment = (PayTrace::instance())->process_payment();

        // Rosetta/PDI data.
        $invoices = (Rosetta::instance())->get_due_invoices();
        $customer = (Rosetta::instance())->get_customer();

        $data = array(
            'invoice_obj' => $this, // Invoice Object.
            'client_key' => $client_key['client_key'],
            'payment' => $payment,
            'thankyou_page' => self::THANK_YOU_PAGE,
            'invoices' => $invoices,
            'customer' => $customer,
        );

        $html = $this->loader->get_template(
            'invoice.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/',
            false
        );

        return trim($html);
    }

    /**
     * Display thank you page.
     * 
     * @return string
     */
    public function display_thankyou_page()
    {

        $data = array(
            'main_screen_url' => '/' . self::CUSTOMER_INVOICE_PAGE,
            'logout_url' => wp_login_url('/' . self::CUSTOMER_LOGIN_PAGE),
        );
        $html = $this->loader->get_template(
            'thank-you.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/',
            false
        );

        return trim($html);
    }

    /**
     * Return a boolean if error_message found.
     *
     * @param  array  $arr
     * @return boolean
     */
    public function has_error($arr)
    {
        if (empty($arr['error_message'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check whether an invoice data is present e.g. not empty.
     * @param  array  $invoices
     * @return boolean
     */
    public function has_invoice($invoices)
    {
        $filtered = array_filter($invoices['invoices']);

        if (empty($filtered)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check whether a customer data is present e.g. not empty
     * @param  array  $customer
     * @return boolean
     */
    public function has_customer($customer)
    {
        $filtered = array_filter($customer['customer']);

        if (empty($filtered)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get customer addresss separately or formatted.
     * 
     * @param  array  $customer
     * @param  boolean $concat
     * @return string
     */
    public function get_customer_address($customer, $concat = false)
    {
        $address = '';
        $customer = $customer['customer'];
        $address = $customer['AddressLine1'];

        if ($concat === false) {
            if (empty($address)) {
                $address = $customer['AddressLine2'];
            } else {
                return $address;
            }

            if (empty($address)) {
                $address = $customer['AddressLine3'];
            } else {
                return $address;
            }

            return $address;
        } else {
            $customer['AddressLine1'] . '<br/>' . $customer['AddressLine2'] . '<br/>' . $customer['AddressLine3'];
        }
    }
}
