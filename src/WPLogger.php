<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class WPLogger
 * @package Yoder\YIPS
 */

class WPLogger
{
    // Try to use the same name for log dir as plguin's slug name
    private const LOG_DIRECTORY_NAME = 'yoder-invoice-payment-system';
    private const DEFAULT_LOG_FILE_NAME_PREFIX = 'api';
    protected static $instance = null;
    private $debug = true;


    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function disable_logging(bool $debug = false)
    {
        if($debug === true) {
             $this->debug = false;
        } else {
            $this->debug = true;
        }
    }

    public function create_log_directory($upload_base_path)
    {
        $dir = $upload_base_path . '/' . self::LOG_DIRECTORY_NAME;
        if (!file_exists($dir)) {
            mkdir($dir, 0755);
        }

        return $dir;
    }

    public function get_log_dirctory()
    {
        $path_info = wp_get_upload_dir();
        $dir = untrailingslashit($this->create_log_directory($path_info['basedir']));
        $dir = $dir . '/';
        return $dir;
    }

    public function log($mix, $log_file = null)
    {
        if ($this->debug === false) {
            return;
        }

        $daily_log = date('Y-m-d');
        if ($log_file == null) {
            $file_name = self::DEFAULT_LOG_FILE_NAME_PREFIX . '_' . $daily_log . '.log';
        } else {
            $file_name = $log_file . '_' . $daily_log . '.log';
        }

        $data  = '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
        $data .= print_r($mix, true);
        file_put_contents($this->get_log_dirctory() . $file_name, $data . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
