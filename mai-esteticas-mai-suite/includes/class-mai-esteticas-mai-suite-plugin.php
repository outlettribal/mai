<?php

if (!defined('ABSPATH')) {
    exit;
}

class Mai_Esteticas_Mai_Suite_Plugin
{
    public function init(): void
    {
        Mai_Esteticas_Mai_Suite_Roles::register_roles();
        (new Mai_Esteticas_Mai_Suite_Shortcodes())->init();
        (new Mai_Esteticas_Mai_Suite_REST())->init();
        (new Mai_Esteticas_Mai_Suite_Admin())->init();
    }
}
