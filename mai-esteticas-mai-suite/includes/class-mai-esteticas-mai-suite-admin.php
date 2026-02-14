<?php

if (!defined('ABSPATH')) {
    exit;
}

class Mai_Esteticas_Mai_Suite_Admin
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_mai_create_sede', [$this, 'handle_create_sede']);
        add_action('admin_post_mai_create_esteticista', [$this, 'handle_create_esteticista']);
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

        ?>
        <div class="wrap">
            <h1>Portal Administrativo Estéticas Mai</h1>
            <p>Desde aquí puedes crear sedes, crear esteticistas y asignar administradora de sede.</p>

            <?php if ($success) : ?>
                <div class="notice notice-success"><p><?php echo esc_html($success); ?></p></div>
            <?php endif; ?>

            <?php if ($error) : ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <hr/>
            <h2>Crear sede</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mai_create_sede_nonce'); ?>
                <input type="hidden" name="action" value="mai_create_sede"/>
                <table class="form-table">
                    <tr>
                        <th><label for="mai_sede_name">Nombre de sede</label></th>
                        <td><input type="text" class="regular-text" id="mai_sede_name" name="name" required/></td>
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
                                <?php foreach ($this->get_manager_candidates() as $candidate) : ?>
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
                        <th><label for="mai_est_sede">Sede</label></th>
                        <td>
                            <select id="mai_est_sede" name="sede_id" required>
                                <option value="">Selecciona una sede</option>
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
            <h2>Sedes registradas</h2>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Timezone</th>
                    <th>Administradora</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->get_sedes_with_manager() as $row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) $row['id']); ?></td>
                        <td><?php echo esc_html($row['name']); ?></td>
                        <td><?php echo esc_html($row['timezone']); ?></td>
                        <td><?php echo esc_html($row['manager_name'] ?: 'Sin asignar'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_create_sede(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos.');
        }

        check_admin_referer('mai_create_sede_nonce');

        global $wpdb;
        $name = sanitize_text_field((string) ($_POST['name'] ?? ''));
        $timezone = sanitize_text_field((string) ($_POST['timezone'] ?? 'America/Bogota'));
        $manager_user_id = absint($_POST['manager_user_id'] ?? 0);

        if ($name === '') {
            $this->redirect_with('mai_error', 'Debes ingresar el nombre de la sede.');
        }

        $inserted = $wpdb->insert("{$wpdb->prefix}mai_sedes", [
            'name' => $name,
            'timezone' => $timezone,
            'manager_user_id' => $manager_user_id > 0 ? $manager_user_id : null,
            'is_active' => 1,
        ]);

        if (!$inserted) {
            $this->redirect_with('mai_error', 'No se pudo crear la sede.');
        }

        $this->redirect_with('mai_success', 'Sede creada correctamente.');
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

        if (!$display_name || !$email || !$password || !$sede_id) {
            $this->redirect_with('mai_error', 'Completa todos los campos obligatorios para crear la esteticista.');
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
            'sede_id' => $sede_id,
            'specialties' => $specialties,
            'is_active' => 1,
        ]);

        if (!$inserted) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user((int) $user_id);
            $this->redirect_with('mai_error', 'No se pudo crear el registro de esteticista en la base de datos.');
        }

        $this->redirect_with('mai_success', 'Esteticista creada y asignada a la sede correctamente.');
    }

    /** @return array<int, array{id:int,name:string,timezone:string}> */
    private function get_sedes(): array
    {
        global $wpdb;

        return $wpdb->get_results("SELECT id, name, timezone FROM {$wpdb->prefix}mai_sedes ORDER BY name ASC", ARRAY_A) ?: [];
    }

    /** @return array<int, array{id:int,name:string,timezone:string,manager_name:?string}> */
    private function get_sedes_with_manager(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT s.id, s.name, s.timezone, u.display_name AS manager_name
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
