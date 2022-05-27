<?php
/**
 * Plugin Name: Yoder Invoice Payment System
 * Description: Plugin description
 * Author: Muhammad Awais / EffectWebAgency
 * Author URI: https://www.effectwebagency.com/
 * Version: 1.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Yoder\YIPS;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'YIPS_CUST_PLUGIN_FILE' ) ) {
    define( 'YIPS_CUST_PLUGIN_FILE', __FILE__ );
}

require_once 'vendor/autoload.php';

Customization::instance();

/**
 * Activate the plugin.
 */
function yoder_on_activate() {
    UserRoles::add_role();
}
register_activation_hook( __FILE__, __NAMESPACE__ .'\\yoder_on_activate' );


/**
 * Deactivation hook.
 */
function yoder_on_deactivate() {
    UserRoles::remove_role();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\yoder_on_deactivate' );