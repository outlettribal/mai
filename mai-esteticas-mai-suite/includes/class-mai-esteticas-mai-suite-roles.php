<?php

if (!defined('ABSPATH')) {
    exit;
}

class Mai_Esteticas_Mai_Suite_Roles
{
    public static function register_roles(): void
    {
        add_role('mai_cliente', 'Mai Cliente', [
            'read' => true,
            'mai_manage_own_appointments' => true,
            'mai_manage_own_medical_record' => true,
            'mai_sign_consents' => true,
            'mai_make_payments' => true,
        ]);

        add_role('mai_esteticista', 'Mai Esteticista', [
            'read' => true,
            'mai_view_assigned_appointments' => true,
            'mai_update_appointment_status' => true,
            'mai_add_esteticas_mai_notes' => true,
            'mai_view_medical_records' => true,
        ]);

        add_role('mai_coordinador', 'Mai Coordinador (Sede)', [
            'read' => true,
            'mai_manage_branch_schedule' => true,
            'mai_reassign_specialists' => true,
            'mai_view_branch_reports' => true,
            'mai_update_appointment_status' => true,
            'mai_view_medical_records' => true,
        ]);

        add_role('mai_admin', 'Mai Admin (Sistema)', [
            'read' => true,
            'manage_options' => true,
            'mai_manage_all' => true,
            'mai_view_audit_logs' => true,
            'mai_manage_catalogs' => true,
            'mai_view_reports' => true,
            'mai_view_medical_records' => true,
            'mai_update_appointment_status' => true,
        ]);

        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('mai_manage_all');
            $admin->add_cap('mai_view_audit_logs');
            $admin->add_cap('mai_manage_catalogs');
            $admin->add_cap('mai_view_reports');
            $admin->add_cap('mai_view_medical_records');
            $admin->add_cap('mai_update_appointment_status');
        }
    }

    public static function deactivate_roles(): void
    {
        // Se conservan roles/caps para no romper instalaciones existentes.
    }
}
