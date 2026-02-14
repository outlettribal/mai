<?php
/**
 * Plugin Name: Esteticas Mai Suite
 * Description: CRM + ERP ligero para clínicas estéticas sin WooCommerce.
 * Version: 0.1.0
 * Author: Mai
 * Text Domain: mai-esteticas-mai-suite
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MAI_ESTETICAS_MAI_SUITE_VERSION', '0.1.0');
define('MAI_ESTETICAS_MAI_SUITE_PATH', plugin_dir_path(__FILE__));
define('MAI_ESTETICAS_MAI_SUITE_URL', plugin_dir_url(__FILE__));

require_once MAI_ESTETICAS_MAI_SUITE_PATH . 'includes/class-mai-esteticas-mai-suite-activator.php';
require_once MAI_ESTETICAS_MAI_SUITE_PATH . 'includes/class-mai-esteticas-mai-suite-roles.php';
require_once MAI_ESTETICAS_MAI_SUITE_PATH . 'includes/class-mai-esteticas-mai-suite-shortcodes.php';
require_once MAI_ESTETICAS_MAI_SUITE_PATH . 'includes/class-mai-esteticas-mai-suite-rest.php';
require_once MAI_ESTETICAS_MAI_SUITE_PATH . 'includes/class-mai-esteticas-mai-suite-plugin.php';

register_activation_hook(__FILE__, ['Mai_Esteticas_Mai_Suite_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Mai_Esteticas_Mai_Suite_Roles', 'deactivate_roles']);

add_action('plugins_loaded', static function () {
    (new Mai_Esteticas_Mai_Suite_Plugin())->init();
});
