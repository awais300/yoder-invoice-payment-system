<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class UserLogin
 * @package Yoder\YIPS
 */

class UserLogin
{

    private const CUSTOMER_INVOICE_PAGE = 'pay-invoice';

    public function __construct()
    {
        $this->loader = TemplateLoader::get_instance();
        add_action('wp_head', array($this, 'yoder_hide_rememeberme'));
        add_action('template_redirect', array($this, 'yoder_template_redirect'));
        add_filter('login_redirect', array($this, 'yoder_login_redirect'), 10, 3);
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

    public function login_redirect($redirect_to, $request, $user)
    {
        if (is_a($user, 'WP_User') && $user->exists()) {
            if (in_array(UserRoles::ROLE_YODER_INVOICE_CUSTOMER, (array) $user->roles)) {
                $redirect_to = get_site_url() . '/' . self::CUSTOMER_INVOICE_PAGE;
            }
        }

        return $redirect_to;
    }
}
