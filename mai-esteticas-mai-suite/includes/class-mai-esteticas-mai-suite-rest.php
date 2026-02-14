<?php

if (!defined('ABSPATH')) {
    exit;
}

class Mai_Esteticas_Mai_Suite_REST
{
    private string $namespace = 'mai/v1';

    public function init(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/sedes', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sedes'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/services', [
            'methods' => 'GET',
            'callback' => [$this, 'get_services'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/availability', [
            'methods' => 'GET',
            'callback' => [$this, 'get_availability'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/appointments', [
            'methods' => 'POST',
            'callback' => [$this, 'create_appointment'],
            'permission_callback' => [$this, 'is_logged_in'],
        ]);

        register_rest_route($this->namespace, '/appointments/(?P<id>\d+)', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update_appointment'],
            'permission_callback' => [$this, 'can_update_appointment'],
        ]);

        register_rest_route($this->namespace, '/payments/(?P<appointment_id>\d+)/create', [
            'methods' => 'POST',
            'callback' => [$this, 'create_payment_checkout'],
            'permission_callback' => [$this, 'is_logged_in'],
        ]);

        register_rest_route($this->namespace, '/payments/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'payments_webhook'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($this->namespace, '/client/me/appointments', [
            'methods' => 'GET',
            'callback' => [$this, 'get_my_appointments'],
            'permission_callback' => [$this, 'is_logged_in'],
        ]);

        register_rest_route($this->namespace, '/medical-record/save', [
            'methods' => 'POST',
            'callback' => [$this, 'save_medical_record'],
            'permission_callback' => [$this, 'is_logged_in'],
        ]);

        register_rest_route($this->namespace, '/consents/(?P<appointment_id>\d+)/sign', [
            'methods' => 'POST',
            'callback' => [$this, 'sign_consent'],
            'permission_callback' => [$this, 'is_logged_in'],
        ]);

        register_rest_route($this->namespace, '/reports/quincena', [
            'methods' => 'GET',
            'callback' => [$this, 'get_quincena_report'],
            'permission_callback' => [$this, 'can_view_reports'],
        ]);
    }

    public function is_logged_in(): bool
    {
        return is_user_logged_in();
    }

    public function can_update_appointment(): bool
    {
        return current_user_can('mai_update_appointment_status') || current_user_can('mai_manage_all');
    }

    public function can_view_reports(): bool
    {
        return current_user_can('mai_view_reports') || current_user_can('mai_manage_all');
    }

    public function get_sedes(): WP_REST_Response
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT id, name, timezone FROM {$wpdb->prefix}mai_sedes WHERE is_active = 1", ARRAY_A);
        return new WP_REST_Response($rows, 200);
    }

    public function get_services(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $sede_id = absint($request->get_param('sede_id'));
        $sql = "SELECT id, name, duration_minutes, price, payment_rule, consent_required FROM {$wpdb->prefix}mai_services WHERE is_active = 1";
        if ($sede_id > 0) {
            $sql .= $wpdb->prepare(' AND sede_id = %d', $sede_id);
        }
        $rows = $wpdb->get_results($sql, ARRAY_A);
        return new WP_REST_Response($rows, 200);
    }

    public function get_availability(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $sede_id = absint($request->get_param('sede_id'));
        $service_id = absint($request->get_param('service_id'));
        $date = sanitize_text_field((string) $request->get_param('date'));

        if (!$sede_id || !$service_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return new WP_REST_Response(['message' => 'Parámetros inválidos'], 422);
        }

        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT duration_minutes FROM {$wpdb->prefix}mai_services WHERE id=%d AND sede_id=%d",
            $service_id,
            $sede_id
        ), ARRAY_A);

        if (!$service) {
            return new WP_REST_Response(['slots' => []], 200);
        }

        $duration = (int) $service['duration_minutes'];
        $slots = [];
        $start = new DateTime("{$date} 08:00:00", new DateTimeZone('America/Bogota'));
        $end = new DateTime("{$date} 18:00:00", new DateTimeZone('America/Bogota'));

        while ($start < $end) {
            $slot_end = (clone $start)->modify("+{$duration} minutes");
            $conflicts = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mai_appointments
                 WHERE sede_id=%d
                 AND status IN ('pending_approval','processing_payment','scheduled')
                 AND ((start_datetime < %s AND end_datetime > %s) OR (start_datetime >= %s AND start_datetime < %s))",
                $sede_id,
                $slot_end->format('Y-m-d H:i:s'),
                $start->format('Y-m-d H:i:s'),
                $start->format('Y-m-d H:i:s'),
                $slot_end->format('Y-m-d H:i:s')
            ));

            if ($conflicts === 0) {
                $slots[] = [
                    'start' => $start->format(DateTime::ATOM),
                    'end' => $slot_end->format(DateTime::ATOM),
                ];
            }

            $start->modify('+30 minutes');
        }

        return new WP_REST_Response(['slots' => $slots], 200);
    }

    public function create_appointment(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $user_id = get_current_user_id();
        $payload = $request->get_json_params();

        $client_id = $this->resolve_client_id($user_id);
        if (!$client_id) {
            return new WP_REST_Response(['message' => 'Cliente no encontrado'], 404);
        }

        $sede_id = absint($payload['sede_id'] ?? 0);
        $service_id = absint($payload['service_id'] ?? 0);
        $specialist_id = isset($payload['specialist_id']) ? absint($payload['specialist_id']) : null;
        $start = sanitize_text_field((string) ($payload['start_datetime'] ?? ''));

        $service = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}mai_services WHERE id=%d", $service_id), ARRAY_A);
        if (!$sede_id || !$service_id || !$start || !$service) {
            return new WP_REST_Response(['message' => 'Datos incompletos'], 422);
        }

        $start_dt = new DateTime($start, new DateTimeZone('America/Bogota'));
        $end_dt = (clone $start_dt)->modify('+' . ((int) $service['duration_minutes']) . ' minutes');

        if ($specialist_id) {
            $overlap = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mai_appointments WHERE specialist_id=%d
                 AND status IN ('pending_approval','processing_payment','scheduled')
                 AND ((start_datetime < %s AND end_datetime > %s) OR (start_datetime >= %s AND start_datetime < %s))",
                $specialist_id,
                $end_dt->format('Y-m-d H:i:s'),
                $start_dt->format('Y-m-d H:i:s'),
                $start_dt->format('Y-m-d H:i:s'),
                $end_dt->format('Y-m-d H:i:s')
            ));
            if ($overlap > 0) {
                return new WP_REST_Response(['message' => 'Especialista no disponible en ese horario'], 409);
            }
        }

        $result = $wpdb->insert("{$wpdb->prefix}mai_appointments", [
            'client_id' => $client_id,
            'sede_id' => $sede_id,
            'specialist_id' => $specialist_id,
            'service_id' => $service_id,
            'start_datetime' => $start_dt->format('Y-m-d H:i:s'),
            'end_datetime' => $end_dt->format('Y-m-d H:i:s'),
            'status' => 'pending_approval',
            'price_subtotal' => $service['price'],
            'total' => $service['price'],
            'payment_required' => 1,
            'payment_status' => 'pending',
        ]);

        if (!$result) {
            return new WP_REST_Response(['message' => 'No se pudo crear la cita'], 500);
        }

        $appointment_id = (int) $wpdb->insert_id;
        $this->audit('appointment_created', 'appointment', $appointment_id, ['status' => 'pending_approval']);

        return new WP_REST_Response(['appointment_id' => $appointment_id, 'status' => 'pending_approval'], 201);
    }

    public function update_appointment(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $appointment_id = absint($request['id']);
        $status = sanitize_text_field((string) ($request->get_json_params()['status'] ?? ''));
        $allowed = ['pending_approval', 'processing_payment', 'scheduled', 'finished', 'cancelled', 'no_show', 'reschedule_requested'];

        if (!in_array($status, $allowed, true)) {
            return new WP_REST_Response(['message' => 'Estado inválido'], 422);
        }

        $updated = $wpdb->update("{$wpdb->prefix}mai_appointments", ['status' => $status], ['id' => $appointment_id]);
        if ($updated === false) {
            return new WP_REST_Response(['message' => 'No se pudo actualizar'], 500);
        }

        $this->audit('appointment_status_changed', 'appointment', $appointment_id, ['status' => $status]);
        return new WP_REST_Response(['appointment_id' => $appointment_id, 'status' => $status], 200);
    }

    public function create_payment_checkout(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $appointment_id = absint($request['appointment_id']);
        $appointment = $wpdb->get_row($wpdb->prepare("SELECT id,total FROM {$wpdb->prefix}mai_appointments WHERE id=%d", $appointment_id), ARRAY_A);

        if (!$appointment) {
            return new WP_REST_Response(['message' => 'Cita no encontrada'], 404);
        }

        $checkout_url = home_url('/?mai_payment=' . $appointment_id . '&token=' . wp_generate_password(20, false));
        $wpdb->insert("{$wpdb->prefix}mai_payments", [
            'appointment_id' => $appointment_id,
            'gateway' => 'custom',
            'amount' => $appointment['total'],
            'currency' => 'COP',
            'status' => 'pending',
            'checkout_url' => $checkout_url,
        ]);

        $wpdb->update("{$wpdb->prefix}mai_appointments", ['status' => 'processing_payment', 'payment_status' => 'pending'], ['id' => $appointment_id]);
        $this->audit('payment_checkout_created', 'appointment', $appointment_id, ['checkout_url' => $checkout_url]);

        return new WP_REST_Response(['checkout_url' => $checkout_url], 200);
    }

    public function payments_webhook(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $payload = $request->get_json_params();
        $appointment_id = absint($payload['appointment_id'] ?? 0);
        $status = sanitize_text_field((string) ($payload['status'] ?? 'failed'));

        if (!$appointment_id) {
            return new WP_REST_Response(['message' => 'appointment_id requerido'], 422);
        }

        $signature_valid = true; // TODO: validar firma real de pasarela.

        $payment_status = $status === 'paid' ? 'paid' : 'failed';
        $appointment_status = $status === 'paid' ? 'scheduled' : 'processing_payment';

        $wpdb->update(
            "{$wpdb->prefix}mai_payments",
            [
                'status' => $payment_status,
                'webhook_payload' => wp_json_encode($payload),
                'signature_valid' => $signature_valid ? 1 : 0,
                'paid_at' => $status === 'paid' ? current_time('mysql') : null,
            ],
            ['appointment_id' => $appointment_id],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );

        $wpdb->update(
            "{$wpdb->prefix}mai_appointments",
            ['status' => $appointment_status, 'payment_status' => $payment_status],
            ['id' => $appointment_id]
        );

        $this->audit('payment_webhook_received', 'appointment', $appointment_id, $payload);
        return new WP_REST_Response(['ok' => true], 200);
    }

    public function get_my_appointments(): WP_REST_Response
    {
        global $wpdb;
        $client_id = $this->resolve_client_id(get_current_user_id());
        if (!$client_id) {
            return new WP_REST_Response([], 200);
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, sede_id, specialist_id, service_id, start_datetime, end_datetime, status, total, payment_status
             FROM {$wpdb->prefix}mai_appointments WHERE client_id=%d ORDER BY start_datetime DESC",
            $client_id
        ), ARRAY_A);

        return new WP_REST_Response($rows, 200);
    }

    public function save_medical_record(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $client_id = $this->resolve_client_id(get_current_user_id());
        if (!$client_id) {
            return new WP_REST_Response(['message' => 'Cliente no encontrado'], 404);
        }

        $payload = $request->get_json_params();
        $safe_payload = wp_json_encode($payload);
        $table_record = "{$wpdb->prefix}mai_medical_records";
        $table_versions = "{$wpdb->prefix}mai_medical_record_versions";

        $record = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table_record} WHERE client_id=%d", $client_id), ARRAY_A);

        if (!$record) {
            $wpdb->insert($table_record, ['client_id' => $client_id]);
            $record_id = (int) $wpdb->insert_id;
            $version = 1;
        } else {
            $record_id = (int) $record['id'];
            $version = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(MAX(version_number),0)+1 FROM {$table_versions} WHERE medical_record_id=%d", $record_id));
        }

        $wpdb->insert($table_versions, [
            'medical_record_id' => $record_id,
            'version_number' => $version,
            'payload' => $safe_payload,
            'created_by' => get_current_user_id(),
        ]);

        $version_id = (int) $wpdb->insert_id;
        $wpdb->update($table_record, ['current_version_id' => $version_id], ['id' => $record_id]);
        $this->audit('medical_record_saved', 'medical_record', $record_id, ['version' => $version]);

        return new WP_REST_Response(['record_id' => $record_id, 'version' => $version], 200);
    }

    public function sign_consent(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $appointment_id = absint($request['appointment_id']);
        $payload = $request->get_json_params();

        $signed_name = sanitize_text_field((string) ($payload['signed_name'] ?? ''));
        $signed_id = sanitize_text_field((string) ($payload['signed_id'] ?? ''));
        $signature_data_url = (string) ($payload['signature_data_url'] ?? '');

        if (!$appointment_id || !$signed_name || !$signed_id || strpos($signature_data_url, 'data:image/png;base64,') !== 0) {
            return new WP_REST_Response(['message' => 'Datos inválidos'], 422);
        }

        $appointment = $wpdb->get_row($wpdb->prepare("SELECT id, service_id, client_id FROM {$wpdb->prefix}mai_appointments WHERE id=%d", $appointment_id), ARRAY_A);
        if (!$appointment) {
            return new WP_REST_Response(['message' => 'Cita no encontrada'], 404);
        }

        $raw = base64_decode(substr($signature_data_url, strlen('data:image/png;base64,')));
        $upload_dir = wp_upload_dir();
        $safe_dir = trailingslashit($upload_dir['basedir']) . 'mai-secure';
        if (!file_exists($safe_dir)) {
            wp_mkdir_p($safe_dir);
        }

        $filename = 'consent-sign-' . $appointment_id . '-' . time() . '.png';
        $signature_path = trailingslashit($safe_dir) . $filename;
        file_put_contents($signature_path, $raw);

        $hash = hash('sha256', $signed_name . '|' . $signed_id . '|' . $appointment_id . '|' . time());
        $wpdb->insert("{$wpdb->prefix}mai_consents", [
            'appointment_id' => $appointment_id,
            'service_id' => (int) $appointment['service_id'],
            'client_id' => (int) $appointment['client_id'],
            'consent_text_version' => 'v1',
            'signed_name' => $signed_name,
            'signed_id' => $signed_id,
            'signature_image_path' => $signature_path,
            'pdf_path' => null,
            'signed_at' => current_time('mysql'),
            'ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'hash' => $hash,
        ]);

        $this->audit('consent_signed', 'appointment', $appointment_id, ['hash' => $hash]);

        return new WP_REST_Response(['message' => 'Consentimiento firmado', 'hash' => $hash], 200);
    }

    public function get_quincena_report(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $from = sanitize_text_field((string) $request->get_param('from'));
        $to = sanitize_text_field((string) $request->get_param('to'));
        $sede_id = absint($request->get_param('sede_id'));

        if (!$from || !$to) {
            return new WP_REST_Response(['message' => 'Rango requerido'], 422);
        }

        $query = "SELECT sede_id, COUNT(*) as total_citas, SUM(total) as ingreso_total
                  FROM {$wpdb->prefix}mai_appointments
                  WHERE start_datetime BETWEEN %s AND %s";
        $params = ["{$from} 00:00:00", "{$to} 23:59:59"];

        if ($sede_id) {
            $query .= ' AND sede_id = %d';
            $params[] = $sede_id;
        }

        $query .= ' GROUP BY sede_id';

        $rows = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        return new WP_REST_Response(['data' => $rows], 200);
    }

    private function resolve_client_id(int $wp_user_id): int
    {
        global $wpdb;
        $table = "{$wpdb->prefix}mai_clients";
        $client_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE wp_user_id=%d", $wp_user_id));

        if (!$client_id) {
            $wpdb->insert($table, [
                'wp_user_id' => $wp_user_id,
                'document_id' => (string) $wp_user_id,
            ]);
            $client_id = (int) $wpdb->insert_id;
        }

        return (int) $client_id;
    }

    private function audit(string $event_type, string $entity_type, int $entity_id, array $payload = []): void
    {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}mai_audit_logs", [
            'actor_user_id' => get_current_user_id(),
            'event_type' => $event_type,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'payload' => wp_json_encode($payload),
            'ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);
    }
}
