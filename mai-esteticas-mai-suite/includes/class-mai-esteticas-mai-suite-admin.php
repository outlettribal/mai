<?php

if (!defined('ABSPATH')) {
    exit;
}

class Mai_Esteticas_Mai_Suite_Admin
{
    /** @var array<int, string> */
    private array $default_sedes = [
        'Ikevana',
        'Calle 170',
        'Cedritos',
        'Rosales',
        'Barce',
        'Carrera 30',
        'Calle 26',
    ];

    /** @var array<string, array<int, string>> */
    private array $default_services = [
        'MEDICINA ESTÉTICA NO INVASIVA' => [
            'HIFU',
            'Expert Lab',
            'Esperma de Salmón',
            'Hidragloss (dermapen con ácido hialurónico)',
            'Facial de Rejuvenecimiento con Radiofrecuencia',
        ],
        'REJUVENECIMIENTO & GLOW FACIAL' => [
            'Limpieza Facial Profunda',
            'Microdermoabrasión',
            'Facial Elixir Real',
            'Vitamina C',
            'Aox System',
            'Hidraluronic',
        ],
        'TRANSFORMACIÓN CORPORAL & MOLDEO' => [
            'Programas de Reducción y Moldeo',
            'Corrientes Rusas',
            'Gimnasia Pasiva',
            'Presoterapia',
            'Detox Iónico',
        ],
        'BIENESTAR & RITUALES TERAPÉUTICOS' => [
            'Masaje Relajante Localizado',
            'Masaje Relajante Completo',
            'Drenaje Linfático',
            'Ritual Ankaly Espalda',
            'Ritual Ankaly Completo',
            'Ritual Lavanda',
            'Programa Wellness (Dulces Sueños)',
        ],
        'ARMONIZACIÓN DE LA MIRADA' => [
            'Diseño de Cejas en Henna',
            'Lifting de Pestañas',
            'Extensión de Pestañas',
        ],
        'DEPILACIÓN PROFESIONAL' => [
            'Depilación de Cejas con Hilo Hindú',
            'Depilación en Cera (Facial y Corporal)',
            'Bigote',
        ],
    ];

    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_mai_create_sede', [$this, 'handle_create_sede']);
        add_action('admin_post_mai_update_sede', [$this, 'handle_update_sede']);
        add_action('admin_post_mai_create_esteticista', [$this, 'handle_create_esteticista']);
        add_action('admin_post_mai_seed_initial_data', [$this, 'handle_seed_initial_data']);
    }

    public function register_menu(): void
    {
        add_menu_page(
            'Estéticas Mai Admin',
            'Estéticas Mai',
            'manage_options',
            'mai-esteticas-mai-admin',
            [$this, 'render_page'],
            'dashicons-calendar-alt',
            56
        );
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para acceder a esta página.');
        }

        $success = sanitize_text_field((string) ($_GET['mai_success'] ?? ''));
        $error = sanitize_text_field((string) ($_GET['mai_error'] ?? ''));
        $manager_candidates = $this->get_manager_candidates();

        ?>
        <div class="wrap">
            <h1>Portal Administrativo Estéticas Mai</h1>
            <p>Flujo recomendado: <strong>1) Crear esteticistas</strong> → <strong>2) Crear/editar sedes</strong> → <strong>3) Asignar administradora por sede</strong>.</p>

            <?php if ($success) : ?>
                <div class="notice notice-success"><p><?php echo esc_html($success); ?></p></div>
            <?php endif; ?>

            <?php if ($error) : ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <hr/>
            <h2>Cargar datos iniciales recomendados</h2>
            <p>Precarga sedes (Ikevana, Calle 170, Cedritos, Rosales, Barce, Carrera 30, Calle 26) y el catálogo base de servicios.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mai_seed_initial_data_nonce'); ?>
                <input type="hidden" name="action" value="mai_seed_initial_data"/>
                <?php submit_button('Cargar datos iniciales', 'secondary'); ?>
            </form>

            <hr/>
            <h2>Crear esteticista</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mai_create_esteticista_nonce'); ?>
                <input type="hidden" name="action" value="mai_create_esteticista"/>
                <table class="form-table">
                    <tr>
                        <th><label for="mai_est_name">Nombre completo</label></th>
                        <td><input type="text" class="regular-text" id="mai_est_name" name="display_name" required/></td>
                    </tr>
                    <tr>
                        <th><label for="mai_est_email">Email</label></th>
                        <td><input type="email" class="regular-text" id="mai_est_email" name="email" required/></td>
                    </tr>
                    <tr>
                        <th><label for="mai_est_password">Contraseña</label></th>
                        <td><input type="text" class="regular-text" id="mai_est_password" name="password" value="<?php echo esc_attr(wp_generate_password(12, false)); ?>" required/></td>
                    </tr>
                    <tr>
                        <th><label for="mai_est_sede">Sede (opcional al crear)</label></th>
                        <td>
                            <select id="mai_est_sede" name="sede_id">
                                <option value="">Sin sede por ahora</option>
                                <?php foreach ($this->get_sedes() as $sede) : ?>
                                    <option value="<?php echo esc_attr((string) $sede['id']); ?>"><?php echo esc_html($sede['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mai_est_specialties">Especialidades</label></th>
                        <td><input type="text" class="regular-text" id="mai_est_specialties" name="specialties" placeholder="Limpieza facial, depilación láser"/></td>
                    </tr>
                </table>
                <?php submit_button('Crear esteticista'); ?>
            </form>

            <hr/>
            <h2>Crear sede</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mai_create_sede_nonce'); ?>
                <input type="hidden" name="action" value="mai_create_sede"/>
                <table class="form-table">
                    <tr>
                        <th><label for="mai_sede_name">Nombre de sede</label></th>
                        <td>
                            <input type="text" class="regular-text" id="mai_sede_name" name="name" list="mai-sedes-default" required/>
                            <datalist id="mai-sedes-default">
                                <?php foreach ($this->default_sedes as $default_sede) : ?>
                                    <option value="<?php echo esc_attr($default_sede); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <p class="description">Puedes elegir un nombre sugerido y luego corregirlo si lo necesitas.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mai_sede_address">Dirección</label></th>
                        <td><input type="text" class="regular-text" id="mai_sede_address" name="address" placeholder="Pendiente por definir"/></td>
                    </tr>
                    <tr>
                        <th><label for="mai_sede_phone">Teléfono</label></th>
                        <td><input type="text" class="regular-text" id="mai_sede_phone" name="phone" placeholder="Pendiente por definir"/></td>
                    </tr>
                    <tr>
                        <th><label for="mai_sede_timezone">Timezone</label></th>
                        <td><input type="text" class="regular-text" id="mai_sede_timezone" name="timezone" value="America/Bogota" required/></td>
                    </tr>
                    <tr>
                        <th><label for="mai_sede_manager">Administradora de sede</label></th>
                        <td>
                            <select id="mai_sede_manager" name="manager_user_id">
                                <option value="">Sin asignar</option>
                                <?php foreach ($manager_candidates as $candidate) : ?>
                                    <option value="<?php echo esc_attr((string) $candidate->ID); ?>">
                                        <?php echo esc_html($candidate->display_name . ' (' . $candidate->user_email . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Puedes asignar una usuaria con rol coordinador o administrator.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Crear sede'); ?>
            </form>

            <hr/>
            <h2>Sedes registradas (editable)</h2>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Timezone</th>
                    <th>Administradora</th>
                    <th>Acción</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->get_sedes_with_manager() as $row) : ?>
                    <tr>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('mai_update_sede_nonce'); ?>
                            <input type="hidden" name="action" value="mai_update_sede"/>
                            <input type="hidden" name="sede_id" value="<?php echo esc_attr((string) $row['id']); ?>"/>
                            <td><?php echo esc_html((string) $row['id']); ?></td>
                            <td><input type="text" name="name" value="<?php echo esc_attr($row['name']); ?>"/></td>
                            <td><input type="text" name="address" value="<?php echo esc_attr($row['address'] ?? ''); ?>"/></td>
                            <td><input type="text" name="phone" value="<?php echo esc_attr($row['phone'] ?? ''); ?>"/></td>
                            <td><input type="text" name="timezone" value="<?php echo esc_attr($row['timezone']); ?>"/></td>
                            <td>
                                <select name="manager_user_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($manager_candidates as $candidate) : ?>
                                        <option value="<?php echo esc_attr((string) $candidate->ID); ?>" <?php selected((int) $row['manager_user_id'], (int) $candidate->ID); ?>>
                                            <?php echo esc_html($candidate->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <?php submit_button('Guardar', 'secondary', 'submit', false); ?>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_seed_initial_data(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos.');
        }

        check_admin_referer('mai_seed_initial_data_nonce');

        global $wpdb;

        $created_sedes = 0;
        foreach ($this->default_sedes as $sede_name) {
            $exists = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mai_sedes WHERE name = %s",
                $sede_name
            ));

            if ($exists > 0) {
                continue;
            }

            $ok = $wpdb->insert("{$wpdb->prefix}mai_sedes", [
                'name' => $sede_name,
                'address' => '',
                'phone' => '',
                'timezone' => 'America/Bogota',
                'is_active' => 1,
            ]);

            if ($ok) {
                $created_sedes++;
            }
        }

        $all_sede_ids = array_map('intval', $wpdb->get_col("SELECT id FROM {$wpdb->prefix}mai_sedes"));
        $created_services = 0;

        foreach ($this->default_services as $category => $services) {
            foreach ($services as $service_name) {
                foreach ($all_sede_ids as $sede_id) {
                    $exists = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}mai_services WHERE sede_id = %d AND category = %s AND name = %s",
                        $sede_id,
                        $category,
                        $service_name
                    ));

                    if ($exists > 0) {
                        continue;
                    }

                    $ok = $wpdb->insert("{$wpdb->prefix}mai_services", [
                        'sede_id' => $sede_id > 0 ? $sede_id : 0,
                        'category' => $category,
                        'name' => $service_name,
                        'duration_minutes' => 60,
                        'buffer_before' => 0,
                        'buffer_after' => 0,
                        'price' => 0,
                        'payment_rule' => 'full',
                        'consent_required' => 0,
                        'is_active' => 1,
                    ]);

                    if ($ok) {
                        $created_services++;
                    }
                }
            }
        }

        if ($created_sedes === 0 && $created_services === 0) {
            $this->redirect_with('mai_success', 'Los datos base ya estaban cargados. No fue necesario crear nuevos registros.');
        }

        $this->redirect_with('mai_success', sprintf('Datos base cargados. Sedes nuevas: %d. Servicios nuevos: %d.', $created_sedes, $created_services));
    }

    public function handle_create_sede(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos.');
        }

        check_admin_referer('mai_create_sede_nonce');

        global $wpdb;
        $name = sanitize_text_field((string) ($_POST['name'] ?? ''));
        $address = sanitize_text_field((string) ($_POST['address'] ?? ''));
        $phone = sanitize_text_field((string) ($_POST['phone'] ?? ''));
        $timezone = sanitize_text_field((string) ($_POST['timezone'] ?? 'America/Bogota'));
        $manager_user_id = absint($_POST['manager_user_id'] ?? 0);

        if ($name === '') {
            $this->redirect_with('mai_error', 'Debes ingresar el nombre de la sede.');
        }

        if ($manager_user_id > 0 && !$this->is_valid_manager_user($manager_user_id)) {
            $this->redirect_with('mai_error', 'La administradora seleccionada no tiene rol válido.');
        }

        $inserted = $wpdb->insert("{$wpdb->prefix}mai_sedes", [
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'timezone' => $timezone,
            'manager_user_id' => $manager_user_id > 0 ? $manager_user_id : null,
            'is_active' => 1,
        ]);

        if (!$inserted) {
            $this->redirect_with('mai_error', 'No se pudo crear la sede.');
        }

        $this->redirect_with('mai_success', 'Sede creada correctamente.');
    }

    public function handle_update_sede(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos.');
        }

        check_admin_referer('mai_update_sede_nonce');

        global $wpdb;
        $sede_id = absint($_POST['sede_id'] ?? 0);
        $name = sanitize_text_field((string) ($_POST['name'] ?? ''));
        $address = sanitize_text_field((string) ($_POST['address'] ?? ''));
        $phone = sanitize_text_field((string) ($_POST['phone'] ?? ''));
        $timezone = sanitize_text_field((string) ($_POST['timezone'] ?? 'America/Bogota'));
        $manager_user_id = absint($_POST['manager_user_id'] ?? 0);

        if ($sede_id <= 0 || $name === '') {
            $this->redirect_with('mai_error', 'Datos inválidos para actualizar sede.');
        }

        if ($manager_user_id > 0 && !$this->is_valid_manager_user($manager_user_id)) {
            $this->redirect_with('mai_error', 'La administradora seleccionada no tiene rol válido.');
        }

        $exists = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mai_sedes WHERE id = %d",
            $sede_id
        ));

        if ($exists === 0) {
            $this->redirect_with('mai_error', 'La sede no existe.');
        }

        $updated = $wpdb->update(
            "{$wpdb->prefix}mai_sedes",
            [
                'name' => $name,
                'address' => $address,
                'phone' => $phone,
                'timezone' => $timezone,
                'manager_user_id' => $manager_user_id > 0 ? $manager_user_id : null,
            ],
            ['id' => $sede_id]
        );

        if ($updated === false) {
            $this->redirect_with('mai_error', 'No se pudo actualizar la sede.');
        }

        $this->redirect_with('mai_success', 'Sede actualizada correctamente.');
    }

    public function handle_create_esteticista(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos.');
        }

        check_admin_referer('mai_create_esteticista_nonce');

        global $wpdb;
        $display_name = sanitize_text_field((string) ($_POST['display_name'] ?? ''));
        $email = sanitize_email((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $sede_id = absint($_POST['sede_id'] ?? 0);
        $specialties = sanitize_text_field((string) ($_POST['specialties'] ?? ''));

        if (!$display_name || !$email || !$password) {
            $this->redirect_with('mai_error', 'Completa nombre, email y contraseña para crear la esteticista.');
        }

        if ($sede_id > 0) {
            $sede_exists = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mai_sedes WHERE id = %d",
                $sede_id
            ));

            if ($sede_exists === 0) {
                $this->redirect_with('mai_error', 'La sede seleccionada no existe.');
            }
        }

        if (email_exists($email)) {
            $this->redirect_with('mai_error', 'Ese email ya existe en WordPress.');
        }

        $user_id = wp_insert_user([
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => $display_name,
            'role' => 'mai_esteticista',
        ]);

        if (is_wp_error($user_id)) {
            $this->redirect_with('mai_error', 'No se pudo crear el usuario esteticista.');
        }

        $inserted = $wpdb->insert("{$wpdb->prefix}mai_specialists", [
            'wp_user_id' => $user_id,
            'sede_id' => $sede_id > 0 ? $sede_id : 0,
            'specialties' => $specialties,
            'is_active' => 1,
        ]);

        if (!$inserted) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user((int) $user_id);
            $this->redirect_with('mai_error', 'No se pudo crear el registro de esteticista en la base de datos.');
        }

        $msg = $sede_id > 0
            ? 'Esteticista creada y asignada a la sede correctamente.'
            : 'Esteticista creada sin sede. Puedes asignarla después.';

        $this->redirect_with('mai_success', $msg);
    }

    /** @return array<int, array{id:int,name:string,timezone:string}> */
    private function get_sedes(): array
    {
        global $wpdb;

        return $wpdb->get_results("SELECT id, name, timezone FROM {$wpdb->prefix}mai_sedes ORDER BY name ASC", ARRAY_A) ?: [];
    }

    /** @return array<int, array{id:int,name:string,address:string,phone:string,timezone:string,manager_user_id:int,manager_name:?string}> */
    private function get_sedes_with_manager(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT s.id, s.name, s.address, s.phone, s.timezone, s.manager_user_id, u.display_name AS manager_name
             FROM {$wpdb->prefix}mai_sedes s
             LEFT JOIN {$wpdb->users} u ON u.ID = s.manager_user_id
             ORDER BY s.name ASC",
            ARRAY_A
        );

        return $rows ?: [];
    }

    /** @return array<int, WP_User> */
    private function get_manager_candidates(): array
    {
        $users = get_users([
            'role__in' => ['mai_coordinador', 'administrator'],
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => ['ID', 'display_name', 'user_email'],
        ]);

        return is_array($users) ? $users : [];
    }

    private function is_valid_manager_user(int $user_id): bool
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $roles = is_array($user->roles) ? $user->roles : [];
        return in_array('mai_coordinador', $roles, true) || in_array('administrator', $roles, true);
    }

    private function redirect_with(string $key, string $message): void
    {
        $url = add_query_arg(
            [
                'page' => 'mai-esteticas-mai-admin',
                $key => $message,
            ],
            admin_url('admin.php')
        );

        wp_safe_redirect($url);
        exit;
    }
}
