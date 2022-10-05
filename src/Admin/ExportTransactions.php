<?php

namespace Yoder\YIPS\Admin;

use Yoder\YIPS\PayTrace\PayTrace;
use Yoder\YIPS\TemplateLoader;
use Yoder\YIPS\WPLogger;
use Yoder\YIPS\Singleton;

use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use League\Csv\Reader;
use Yoder\YIPS\CardDetector;


defined('ABSPATH') || exit;

/**
 * Class PayTrace
 * @package Yoder\YIPS
 */

class ExportTransactions extends Singleton
{

    /**
     * The template loader.
     *
     * @var $loader
     */
    private $loader = null;

    /**
     * Contains transactions response
     *
     * @var $result
     */
    private $result = array();

    /**
     * Log information to file.
     *
     * @var $logger
     */
    private $logger = null;

    /**
     * Holds file path.
     *
     * @var $path
     */
    private $path = null;

    /**
     * Holds settings for csv export
     *
     * @var EXPORT_SETTINGS
     */
    public const EXPORT_SETTINGS = 'yoder_export_settings';

    /**
     * Construct the plugin.
     */
    public function __construct()
    {
        $this->loader = TemplateLoader::instance();
        $this->logger = WPLogger::instance();

        add_action('admin_menu', array($this, 'add_export_menu'));
        add_action('admin_init', array($this, 'handle_export_transactions_form'));
    }

    /**
     * Add admin menu.
     *
     **/
    public function add_export_menu()
    {
        add_menu_page(
            __('Export PayTrace Transactions', 'yips-customization'),
            __('Export PayTrace Transactions', 'yips-customization'),
            'manage_options',
            'export-paytrace-transactions',
            array($this, 'display_export_page'),
            'dashicons-media-spreadsheet'

        );

        add_submenu_page(
            'export-paytrace-transactions',
            __('Settings', 'yips-customization'),
            __('Settings', 'yips-customization'),
            'manage_options',
            'export-paytrace-transactions-settings',
            array($this, 'display_export_settings')
        );
    }

    /**
     * Display the export settings in admin dashboard.
     *  
     **/
    public function display_export_settings()
    {
        $data = array();
        echo $html = $this->loader->get_template(
            'export-transactions-settings.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/admin/',
            false
        );
    }


    /**
     * Display the export page in admin dashboard.
     *  
     **/
    public function display_export_page()
    {
        $data = array(
            'export_obj' => $this, // ExportTransactions object.
            'result' => $this->result
        );

        echo $html = $this->loader->get_template(
            'export-transactions.php',
            $data,
            YIPS_CUST_PLUGIN_DIR_PATH . '/templates/admin/',
            false
        );
    }

    /**
     *  Handle the export form submission.
     * 
     * */
    public function handle_export_transactions_form()
    {

        if (isset($_POST['submit_transactions_export']) && $_POST['submit_transactions_export'] === 'Download') {
            if (!wp_verify_nonce($_POST['_wpnonce'], 'export-nonce')) {
                wp_die(__('Are you cheating?', 'yips-customization'));
            }

            $start_date = sanitize_text_field($_POST['start_date']);
            $end_date = sanitize_text_field($_POST['end_date']);
            $this->init_export($start_date, $end_date);
        }

        if (isset($_POST['submit_transactions_export']) && $_POST['submit_transactions_export'] === 'Save Settings') {
            if (!wp_verify_nonce($_POST['_wpnonce'], 'export-nonce')) {
                wp_die(__('Are you cheating?', 'yips-customization'));
            }

            $settings = array(
                'export-email' => sanitize_email(trim($_POST['export-email']))
            );

            update_option(self::EXPORT_SETTINGS, $settings);
        }
    }

    /**
     * Initiate the export process by getting transactions data.
     *
     * @var string $start_date
     * @var string $end_date
     * @var boolean $download_mode Should file download after export or not
     **/
    public function init_export($start_date, $end_date, $download_mode = true)
    {
        $result = (PayTrace::instance())->get_transactions_by_date_range($start_date, $end_date);
        $this->result = $result;

        if ($this->has_error($result)) {
            $this->logger->log('Error: init_export()');
            $this->logger->log($result);
            return;
        } else {
            $this->export($download_mode);
        }
    }


    /**
     * Set file path to export.
     *
     * @var string $path
     *
     **/
    public function set_file_path($path = null)
    {
        if (!empty($path)) {
            $this->path = $path;
        } else {
            $this->path =  wp_get_upload_dir()['basedir'] . '/transactions-export.csv';
        }
    }

    /**
     * Get the file path.
     *
     **/
    public function get_file_path()
    {
        if (empty($this->path)) {
            $this->set_file_path();
        }

        return $this->path;
    }

    /**
     * Export all transactions data into a CSV file and downloads it.
     *
     * @var boolean $download Whether to download the file right after export.
     *
     **/
    public function export($download = true)
    {
        $transactions = $this->result['exported_transactions'];
        $csv_data_rows = array();

        if ($transactions) {
            $i = 0;
            foreach ($transactions as $key => $transc) {

                $sage_customer_id = $transc['discretionary_data']['Sage Customer ID'];
                $transc_date = $transc['created']['at'];

                $amount =  $transc['amount'];
                $invoice_nums = $transc['discretionary_data']['Invoice Numbers'];

                $card_num = explode('*', $transc['credit_card']['masked_number']);
                $card_num = (int) $card_num[0];
                $card_type = (CardDetector::instance())->detect($card_num);

                $csv_data_rows[$i][] = $sage_customer_id;
                $csv_data_rows[$i][] = $transc_date;
                $csv_data_rows[$i][] = $amount;
                $csv_data_rows[$i][] = $invoice_nums;
                $csv_data_rows[$i][] = $card_type;
                $i++;
            }
        }

        if (empty($csv_data_rows)) {
            $this->logger->log('export()');
            $this->logger->log($this->result);
            $this->logger->log('No transaction data found.');
            return;
        }

        // Create a path to wp-content/upload/ directory.
        $csv_path = $this->get_file_path();
        try {
            $writer = Writer::createFromPath($csv_path, 'w');
            $writer->insertOne($this->get_csv_header());
            $writer->insertAll(new \ArrayIterator($csv_data_rows));
        } catch (CannotInsertRecord $e) {
            $this->logger->log('CannotInsertRecord');
            $this->logger->log($e->getRecords());
            wp_die('Cannot Insert Record');
        } catch (Exception | \RuntimeException $e) {
            $this->logger->log('CannotInsertRecord');
            $this->logger->log($e->getMessage());
            wp_die($e->getMessage());
        }

        // Download CSV.
        if ($download === true) {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Description: File Transfer');
            header('Content-Disposition: attachment; filename="' . basename($csv_path) . '"');
            try {
                $reader = Reader::createFromPath($csv_path);
                $reader->output();
                exit();
            } catch (Exception $e) {
                $this->logger->log('CannotInsertRecord');
                $this->logger->log($e->getMessage());
                wp_die($e->getMessage());
            }
        }
    }


    /**
     * Get CSV header row
     * @return []
     */
    public function get_csv_header()
    {
        return [
            'Customer Number',
            'Transaction Date',
            'Payment Amount',
            'Invoice #(s)',
            'Card Type'
        ];
    }

    /**
     * Return a boolean true if error_message found.
     *
     * @param  array  $arr
     * @return boolean
     */
    public function has_error($arr)
    {
        if (isset($arr['error_message']) && !empty($arr['error_message'])) {
            return true;
        } else {
            return false;
        }
    }
}
