<?php

namespace Yoder\YIPS;

use Yoder\YIPS\Singleton;

defined('ABSPATH') || exit;

/**
 * Class Schema
 * @package Yoder\YIPS
 */

class Schema extends Singleton
{
    /**
     * The table name
     *
     * @var PAYTRACE_RESPONSE_TABLE
     */
    public const PAYTRACE_RESPONSE_TABLE = 'paytrace_response';


    /**
     * Create DB table
     */
    public function create_table()
    {
        global $wpdb;
        $table = $wpdb->prefix . self::PAYTRACE_RESPONSE_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
         `id` INT(11) NOT NULL AUTO_INCREMENT, 
         `user_id` INT(11) NOT NULL , 
         `response` TEXT NOT NULL , 
         `status` VARCHAR(10) NOT NULL , 
         `date_created` DATETIME NOT NULL , 
         PRIMARY KEY (`id`)) 
        $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
