<?php

if (!defined('ABSPATH')) {
    exit;
}

class Mai_Esteticas_Mai_Suite_Shortcodes
{
    public function init(): void
    {
        add_shortcode('mai_client_portal', [$this, 'render_client_portal']);
        add_shortcode('mai_staff_portal', [$this, 'render_staff_portal']);
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
    }

    public function register_assets(): void
    {
        wp_register_style('mai-esteticas-mai-suite', MAI_ESTETICAS_MAI_SUITE_URL . 'assets/css/mai-esteticas-mai-suite.css', [], MAI_ESTETICAS_MAI_SUITE_VERSION);
        wp_register_script('mai-client-portal', MAI_ESTETICAS_MAI_SUITE_URL . 'assets/js/client-portal.js', [], MAI_ESTETICAS_MAI_SUITE_VERSION, true);
        wp_register_script('mai-staff-portal', MAI_ESTETICAS_MAI_SUITE_URL . 'assets/js/staff-portal.js', [], MAI_ESTETICAS_MAI_SUITE_VERSION, true);
    }

    public function render_client_portal(): string
    {
        wp_enqueue_style('mai-esteticas-mai-suite');
        wp_enqueue_script('mai-client-portal');
        wp_localize_script('mai-client-portal', 'MaiEsteticasMaiConfig', [
            'restUrl' => esc_url_raw(rest_url('mai/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        return '<div id="mai-client-portal" class="mai-portal"><h3>Portal Cliente</h3><div class="mai-app"></div></div>';
    }

    public function render_staff_portal(): string
    {
        wp_enqueue_style('mai-esteticas-mai-suite');
        wp_enqueue_script('mai-staff-portal');
        wp_localize_script('mai-staff-portal', 'MaiEsteticasMaiConfig', [
            'restUrl' => esc_url_raw(rest_url('mai/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        return '<div id="mai-staff-portal" class="mai-portal"><h3>Portal Esteticista</h3><div class="mai-app"></div></div>';
    }
}
