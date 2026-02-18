<?php
/**
 * Plugin Name: Electrocam ERP CRM
 * Plugin URI: https://electrocam.com
 * Description: Base del plugin ERP/CRM para gestionar órdenes de servicio, cotizaciones, inventario y agenda de Electrocam SAS.
 * Version: 0.22.0
 * Author: Electrocam SAS
 * Text Domain: electrocam-crm
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EC_PLUGIN_VERSION', '0.22.0' );
define( 'EC_PLUGIN_FILE', __FILE__ );
define( 'EC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'EC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once EC_PLUGIN_PATH . 'includes/class-ec-core.php';

/**
 * Inicializa el plugin.
 *
 * @return EC_Core
 */
function ec_bootstrap() {
	return EC_Core::instance();
}

/**
 * Activación del plugin.
 *
 * @return void
 */
function ec_activate_plugin() {
	EC_Core::activate();
}
register_activation_hook( __FILE__, 'ec_activate_plugin' );

/**
 * Desactivación del plugin.
 *
 * @return void
 */
function ec_deactivate_plugin() {
	EC_Core::deactivate();
}
register_deactivation_hook( __FILE__, 'ec_deactivate_plugin' );

ec_bootstrap();
