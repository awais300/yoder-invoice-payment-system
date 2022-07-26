<?php

namespace Yoder\YIPS\Cron;

use Yoder\YIPS\Helper;
use Yoder\YIPS\Singleton;
use Yoder\YIPS\Admin\ExportTransactions;

defined('ABSPATH') || exit;

/**
 * Class Cron
 * @package Yoder\YIPS
 */

class Cron extends Singleton
{
    /**
     * The default email address.
     * @var DEFAULT_EMAIL
     */
    public const DEFAULT_EMAIL = 'lnelson@yoderoil.com';

    /**
     * Construct the plugin.
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'yoder_add_csv_endpoint'));
    }

    /**
     *  Set endpoint for CSV export.
     *  
     * */
    public function yoder_add_csv_endpoint()
    {
        $route_namespace = 'yoderoil/v1';

        // wp-json/yoderoil/v1/export_transactions_csv
        register_rest_route($route_namespace, '/export_transactions_csv', array(
            'methods' => 'GET',
            'callback' => array($this, 'init_export_transactions_csv'),
        ));
    }

    /**
     *  Init the csv export process.
     **/
    public function init_export_transactions_csv()
    {
        // Same day date so $date would be start date and end date as well.
        $date = date('m/d/Y');
        $date = '7/12/2022'; // For testing.
        $file_date = date('m-d-Y', strtotime($date));
        $file_path = wp_get_upload_dir()['basedir'] . "/transactions-export-{$file_date}.csv";
        $download_csv = false;

        (ExportTransactions::instance())->set_file_path($file_path);
        (ExportTransactions::instance())->init_export($date, $date, $download_csv);
        $this->send_email($file_path);
        @unlink($file_path);
    }

    public function send_email($file_path)
    {
        $to = self::DEFAULT_EMAIL;
        $settings = get_option((ExportTransactions::instance())::EXPORT_SETTINGS);
        if (isset($settings['export-email']) && !empty($settings['export-email'])) {
            $to = $settings['export-email'];
        }

        $subject = __('YoderOil - Transactions CSV', 'yips-customization');
        $message = __('Transactions CSV is attached.', 'yips-customization');
        $headers = (Helper::instance())->get_headers_for_email('no-reply@yoderoil.com');

        if (file_exists($file_path)) {
            $attachments = $file_path;
        } else {
            $attachments = '';
        }

        wp_mail($to, $subject, $message, $headers, $attachments);
    }
}
