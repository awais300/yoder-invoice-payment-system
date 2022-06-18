<?php

namespace Yoder\YIPS;
use Omnipay\Omnipay;

defined('ABSPATH') || exit;

/**
 * Class UserLogin
 * @package Yoder\YIPS
 */

class UserLogin
{

    private const CUSTOMER_INVOICE_PAGE = 'pay-online';
    public const FROM_EMAIL = 'no-reply@yoderoil.com';
    private $logger = null;

    public function __construct()
    {

        $this->logger = WPLogger::get_instance();
        add_action('wp_head', array($this, 'yoder_hide_rememeberme'));
        //add_action('template_redirect', array($this, 'yoder_template_redirect'));
        //add_action('template_redirect', array($this, 'test'));
        add_filter('login_redirect', array($this, 'yoder_login_redirect'), 10, 3);
        add_filter('retrieve_password_message', array($this, 'yoder_retrieve_password_message'), 10, 4);
        add_filter('retrieve_password_notification_email', array($this, 'yoder_retrieve_password_notification_email'), 10, 4);
    }

    public function yoder_hide_rememeberme()
    {
        echo '<style>
            .tml-rememberme-wrap {
                display: none !important;
            }
        </style>';
    }

    public function yoder_template_redirect()
    {
        global $post;
        $page_slug = $post->post_name;

        $slugs = array(
            'dashboard',
            'register',
            'lostpassword',
            'resetpass',
        );

        if (!current_user_can('manage_options')) {
            if (in_array($page_slug, $slugs)) {
                wp_redirect(get_site_url());
                exit;
            }
        }
    }

    public function yoder_login_redirect($redirect_to, $request, $user)
    {

        if (is_a($user, 'WP_User') && $user->exists()) {
            if (in_array(UserRoles::ROLE_YODER_INVOICE_CUSTOMER, (array) $user->roles)) {
                $redirect_to = get_site_url() . '/' . self::CUSTOMER_INVOICE_PAGE;
            }
        }

        return $redirect_to;
    }

    public function yoder_retrieve_password_message($message, $key, $user_login, $user)
    {
        if (is_a($user, 'WP_User') && $user->exists()) {
            if (!in_array(UserRoles::ROLE_YODER_INVOICE_CUSTOMER, (array) $user->roles)) {
                return $message;
            }
        }

        $reset_page = $this->get_pass_reset_page();

        // Start with the default content.
        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $message = __('Someone has requested a password reset for the following account:') . "<br/>";
        /* translators: %s: site name */
        $message .= sprintf(__('Site Name: %s'), $site_name) . "<br/>";
        /* translators: %s: user login */
        $message .= sprintf(__('Username: %s'), $user->user_email) . "<br/>";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "<br/>";
        $message .= __('To reset your password, visit the following address:') . "<br/>";
        //$message .= '<' . network_site_url("{$reset_page}?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";
        $message .= network_site_url("{$reset_page}?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . "<br/><br/>";

        return $message;
    }

    public function yoder_retrieve_password_notification_email($defaults, $key, $user_login, $user)
    {
        if (is_a($user, 'WP_User') && $user->exists()) {
            if (!in_array(UserRoles::ROLE_YODER_INVOICE_CUSTOMER, (array) $user->roles)) {
                return $defaults;
            }
        }

        $defaults['headers'] = Helper::get_headers_for_email(self::FROM_EMAIL);
        return $defaults;
    }

    public function get_pass_reset_page()
    {
        $page = 'wp-login.php';
        if (class_exists('Theme_My_Login')) {
            $page = 'resetpass';
        }
        return $page;
    }

    public function test()
    {
        $ccGateway = Omnipay::create('Paytrace_CreditCard');
        $ccGateway->setUserName('awais@effectwebagency.com')
            ->setPassword('flummox@123')
            ->setTestMode(true);

        //$settings = $ccGateway->getDefaultParameters();

        $creditCardData = ['number' => '4242424242424242', 'expiryMonth' => '6', 'expiryYear' => '2023', 'cvv' => '123', 'customer_id'=> 'p444', 'firstName' => 'Shilpa'];
        $response = $ccGateway->purchase(['amount' => '2100.00', 'currency' => 'USD', 'card' => $creditCardData])->send();

        if ($response->isSuccessful()) {
            $this->logger->log('success');
            $this->logger->log($response);
            echo $response->getMessage();
        } else {
            $this->logger->log('fail');
            $this->logger->log($response);
            echo $response->getMessage();
        }
        exit('end');
    }
}
