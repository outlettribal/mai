<?php
/**
 * Plugin Name: Mi Plugin Hola
 * Plugin URI:  https://example.com/mi-plugin-hola
 * Description: Plugin base para WordPress con shortcode y ajuste configurable.
 * Version:     1.0.0
 * Author:      Equipo Mai
 * Author URI:  https://example.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mi-plugin-hola
 */

if (!defined('ABSPATH')) {
    exit;
}

class Mi_Plugin_Hola
{
    private const OPTION_NAME = 'mi_plugin_hola_mensaje';
    private const DEFAULT_MESSAGE = '¡Hola desde tu nuevo plugin de WordPress!';

    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('saludo_plugin', [$this, 'render_shortcode']);
    }

    public static function activate(): void
    {
        if (get_option(self::OPTION_NAME) === false) {
            add_option(self::OPTION_NAME, self::DEFAULT_MESSAGE);
        }
    }

    public function register_admin_menu(): void
    {
        add_options_page(
            'Mi Plugin Hola',
            'Mi Plugin Hola',
            'manage_options',
            'mi-plugin-hola',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'mi_plugin_hola_options',
            self::OPTION_NAME,
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => self::DEFAULT_MESSAGE,
            ]
        );

        add_settings_section(
            'mi_plugin_hola_main_section',
            'Configuración principal',
            static function (): void {
                echo '<p>Define el mensaje que mostrará el shortcode <code>[saludo_plugin]</code>.</p>';
            },
            'mi-plugin-hola'
        );

        add_settings_field(
            'mi_plugin_hola_message_field',
            'Mensaje',
            [$this, 'render_message_field'],
            'mi-plugin-hola',
            'mi_plugin_hola_main_section'
        );
    }

    public function render_message_field(): void
    {
        $value = get_option(self::OPTION_NAME, self::DEFAULT_MESSAGE);
        ?>
        <input
            type="text"
            name="<?php echo esc_attr(self::OPTION_NAME); ?>"
            value="<?php echo esc_attr($value); ?>"
            class="regular-text"
        />
        <?php
    }

    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Mi Plugin Hola</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('mi_plugin_hola_options');
                do_settings_sections('mi-plugin-hola');
                submit_button('Guardar cambios');
                ?>
            </form>
        </div>
        <?php
    }

    public function render_shortcode(): string
    {
        $message = get_option(self::OPTION_NAME, self::DEFAULT_MESSAGE);

        return sprintf(
            '<div class="mi-plugin-hola-message">%s</div>',
            esc_html($message)
        );
    }
}

$mi_plugin_hola = new Mi_Plugin_Hola();
$mi_plugin_hola->init();

register_activation_hook(__FILE__, ['Mi_Plugin_Hola', 'activate']);
