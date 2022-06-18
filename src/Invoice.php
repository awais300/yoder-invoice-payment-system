<?php

namespace Yoder\YIPS;
use Omnipay\Omnipay;

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
        $data = array();
        $html = $this->loader->get_template(
            'invoice.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/',
            false
        );

        return trim( $html );
    }
}