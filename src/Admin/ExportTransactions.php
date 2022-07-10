<?php

namespace Yoder\YIPS\Admin;

use Yoder\YIPS\PayTrace\PayTrace;
use Yoder\YIPS\TemplateLoader;
use Yoder\YIPS\WPLogger;

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

class ExportTransactions
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
     * Construct the plugin.
     */
    public function __construct()
    {
        $this->loader = TemplateLoader::instance();
        $this->logger = WPLogger::instance();
        $this->logger->set_log_file_name('export');

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
        if (!isset($_POST['submit_transactions_export']) && $_POST['submit_transactions_export'] != 'Download') {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'export-nonce')) {
            wp_die(__('Are you cheating?', 'yips-customization'));
        }

        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);

        $result = (PayTrace::instance())->get_transactions_by_date_range($start_date, $end_date);
        $this->result = $result;

        if ($this->has_error($result)) {
            return;
        } else {
            $this->export();
        }
    }

    /**
     * Export all transactions data into a CSV file and downloads it. 
     *
     **/
    public function export()
    {
        $transactions = $this->result['exported_transactions'];

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

        // Create a path to wp-content/upload/ directory .
        $csv_path = wp_get_upload_dir()['basedir'] . '/transactions-export.csv';
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

        // Download CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="transactions-export.csv"');
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
