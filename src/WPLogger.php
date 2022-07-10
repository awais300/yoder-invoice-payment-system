<?php

namespace Yoder\YIPS;


defined('ABSPATH') || exit;

/**
 * Class WPLogger
 * @package Yoder\YIPS
 */

class WPLogger extends Singleton
{
    /**
     * Default directory name. 
     * Try to use the same name for log dir as plguin's slug name
     *
     * @var LOG_DIRECTORY_NAME
     */
    private const LOG_DIRECTORY_NAME = 'yoder-invoice-payment-system';

    /**
     * Default file name prefix.
     *
     * @var DEFAULT_LOG_FILE_NAME_PREFIX
     */
    private const DEFAULT_LOG_FILE_NAME_PREFIX = 'api';

    /**
     * Default logging mode.
     *
     * @var $debug
     */
    private $debug = true;

    /**
     * Holds custom file name.
     *
     * @var $debug_file_name
     */
    private $debug_file_name = null;

    /**
     * Construct the plugin.
     **/
    public function on_construct()
    {
        $this->set_log_file_name();
    }

    /**
     * Set debug file name.
     *
     * @param string $debug_file_name.
     */
    public function set_log_file_name($debug_file_name = null)
    {
        if ($debug_file_name) {
            $this->debug_file_name = $debug_file_name;
        } else {
            $this->debug_file_name = self::DEFAULT_LOG_FILE_NAME_PREFIX;
        }
    }

    /**
     * Return debug file name.
     *
     * @return string
     */
    public function get_log_file_name()
    {
        return $this->debug_file_name;
    }

    /**
     * Disable logging if pass true as parameter. Pass false to enable logging.
     *
     * @param bool $debug.
     */
    public function disable_logging(bool $debug = false)
    {
        if ($debug === true) {
            $this->debug = false;
        } else {
            $this->debug = true;
        }
    }

    /**
     * Creates a directory.
     *
     * @param string $upload_base_path.
     * @return string path to directory 
     */
    public function create_log_directory($upload_base_path)
    {
        $dir = $upload_base_path . '/' . self::LOG_DIRECTORY_NAME;
        if (!file_exists($dir)) {
            mkdir($dir, 0755);
        }

        return $dir;
    }

    /**
     * Get directory where log files need to put.
     *
     * @return string path to directory 
     */
    public function get_log_dirctory()
    {
        $path_info = wp_get_upload_dir();
        $dir = untrailingslashit($this->create_log_directory($path_info['basedir']));
        $dir = $dir . '/';
        return $dir;
    }

    /**
     * Write log to a file.
     *
     * @param mix $mix
     */
    public function log($mix)
    {
        if ($this->debug === false) {
            return;
        }

        $daily_log = date('Y-m-d');
        $file_name = $this->get_log_file_name() . '_' . $daily_log . '.log';


        $data  = '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
        $data .= print_r($mix, true);
        file_put_contents($this->get_log_dirctory() . $file_name, $data . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
