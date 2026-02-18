<?php
/**
 * Núcleo del plugin Electrocam ERP/CRM.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase principal.
 */
class EC_Core {
	/**
	 * Instancia singleton.
	 *
	 * @var EC_Core|null
	 */
	private static $instance = null;

	/**
	 * Obtiene la instancia única.
	 *
	 * @return EC_Core
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook de activación.
	 *
	 * @return void
	 */
	public static function activate() {
		self::register_roles();
		self::register_post_types_static();
		self::register_taxonomies_static();
		if ( ! get_option( 'ec_support_email' ) ) {
			update_option( 'ec_support_email', 'servicioalcliente@electrocam.com' );
		}
		if ( false === get_option( 'ec_notification_cc_email', false ) ) {
			update_option( 'ec_notification_cc_email', '' );
		}
		if ( false === get_option( 'ec_audit_retention_limit', false ) ) {
			update_option( 'ec_audit_retention_limit', 50 );
		}
		flush_rewrite_rules();
	}

	/**
	 * Hook de desactivación.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'add_meta_boxes_service_order', array( $this, 'add_service_order_meta_box' ) );
		add_action( 'add_meta_boxes_quotation', array( $this, 'add_quotation_meta_box' ) );
		add_action( 'add_meta_boxes_inventory_item', array( $this, 'add_inventory_item_meta_box' ) );
		add_action( 'add_meta_boxes_appointment', array( $this, 'add_appointment_meta_box' ) );
		add_action( 'save_post_service_order', array( $this, 'save_service_order_meta' ), 10, 2 );
		add_action( 'save_post_quotation', array( $this, 'save_quotation_meta' ), 10, 2 );
		add_action( 'save_post_inventory_item', array( $this, 'save_inventory_item_meta' ), 10, 2 );
		add_action( 'save_post_appointment', array( $this, 'save_appointment_meta' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'render_settings_errors_notice' ) );
		add_shortcode( 'ec_client_orders', array( $this, 'render_client_orders_shortcode' ) );
		add_shortcode( 'ec_client_quotations', array( $this, 'render_client_quotations_shortcode' ) );
		add_shortcode( 'ec_client_appointments', array( $this, 'render_client_appointments_shortcode' ) );
		add_shortcode( 'ec_operator_orders', array( $this, 'render_operator_orders_shortcode' ) );
		add_shortcode( 'ec_operator_appointments', array( $this, 'render_operator_appointments_shortcode' ) );
		add_shortcode( 'ec_operator_quotations', array( $this, 'render_operator_quotations_shortcode' ) );
		add_action( 'admin_post_ec_client_reschedule_appointment', array( $this, 'handle_client_reschedule_appointment' ) );
		add_action( 'admin_post_ec_operator_reschedule_appointment', array( $this, 'handle_operator_reschedule_appointment' ) );
		add_action( 'admin_post_ec_client_update_quotation_status', array( $this, 'handle_client_update_quotation_status' ) );
		add_action( 'comment_post', array( $this, 'notify_service_order_comment' ), 10, 3 );
	}

	/**
	 * Carga traducciones.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'electrocam-crm', false, dirname( plugin_basename( EC_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Registra CPT principales del ERP/CRM.
	 *
	 * @return void
	 */
	public function register_post_types() {
		self::register_post_types_static();
	}

	/**
	 * Registra CPT en contexto estático (activación/init).
	 *
	 * @return void
	 */
	private static function register_post_types_static() {
		register_post_type(
			'service_order',
			array(
				'label'           => __( 'Órdenes de Servicio', 'electrocam-crm' ),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-clipboard',
				'supports'        => array( 'title', 'editor', 'comments' ),
				'show_in_rest'    => true,
				'capability_type' => array( 'ec_service_order', 'ec_service_orders' ),
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'quotation',
			array(
				'label'           => __( 'Cotizaciones', 'electrocam-crm' ),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-media-spreadsheet',
				'supports'        => array( 'title', 'editor' ),
				'show_in_rest'    => true,
				'capability_type' => array( 'ec_quotation', 'ec_quotations' ),
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'inventory_item',
			array(
				'label'           => __( 'Inventario', 'electrocam-crm' ),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-products',
				'supports'        => array( 'title', 'editor', 'thumbnail' ),
				'show_in_rest'    => true,
				'capability_type' => array( 'ec_inventory_item', 'ec_inventory_items' ),
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'appointment',
			array(
				'label'           => __( 'Citas', 'electrocam-crm' ),
				'public'          => false,
				'show_ui'         => true,
				'menu_icon'       => 'dashicons-calendar-alt',
				'supports'        => array( 'title' ),
				'show_in_rest'    => true,
				'capability_type' => array( 'ec_appointment', 'ec_appointments' ),
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Registra taxonomías.
	 *
	 * @return void
	 */
	public function register_taxonomies() {
		self::register_taxonomies_static();
	}

	/**
	 * Registra taxonomías en contexto estático.
	 *
	 * @return void
	 */
	private static function register_taxonomies_static() {
		register_taxonomy(
			'inventory_category',
			'inventory_item',
			array(
				'label'        => __( 'Categorías de Inventario', 'electrocam-crm' ),
				'public'       => false,
				'show_ui'      => true,
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Registra roles base para el plugin.
	 *
	 * @return void
	 */
	private static function register_roles() {
		add_role(
			'ec_admin',
			__( 'Administrador Electrocam', 'electrocam-crm' ),
			array(
				'read'              => true,
				'edit_posts'        => true,
				'edit_others_posts' => true,
				'publish_posts'     => true,
				'upload_files'      => true,
				'manage_categories' => true,
				'moderate_comments' => true,
				'list_users'        => true,
				'create_users'      => true,
				'edit_users'        => true,
				'delete_users'      => true,
				'promote_users'     => true,
				'manage_options'    => true,
			)
		);

		add_role(
			'ec_operator',
			__( 'Operario Electrocam', 'electrocam-crm' ),
			array(
				'read'              => true,
				'upload_files'      => true,
				'moderate_comments' => true,
			)
		);

		add_role(
			'ec_client',
			__( 'Cliente Electrocam', 'electrocam-crm' ),
			array(
				'read' => true,
			)
		);

		$admin_caps = array(
			'edit_ec_service_order',
			'read_ec_service_order',
			'delete_ec_service_order',
			'edit_ec_service_orders',
			'edit_others_ec_service_orders',
			'publish_ec_service_orders',
			'read_private_ec_service_orders',
			'delete_ec_service_orders',
			'delete_private_ec_service_orders',
			'delete_published_ec_service_orders',
			'delete_others_ec_service_orders',
			'edit_private_ec_service_orders',
			'edit_published_ec_service_orders',
			'create_ec_service_orders',
			'edit_ec_quotation',
			'read_ec_quotation',
			'delete_ec_quotation',
			'edit_ec_quotations',
			'edit_others_ec_quotations',
			'publish_ec_quotations',
			'read_private_ec_quotations',
			'delete_ec_quotations',
			'delete_private_ec_quotations',
			'delete_published_ec_quotations',
			'delete_others_ec_quotations',
			'edit_private_ec_quotations',
			'edit_published_ec_quotations',
			'create_ec_quotations',
			'edit_ec_inventory_item',
			'read_ec_inventory_item',
			'delete_ec_inventory_item',
			'edit_ec_inventory_items',
			'edit_others_ec_inventory_items',
			'publish_ec_inventory_items',
			'read_private_ec_inventory_items',
			'delete_ec_inventory_items',
			'delete_private_ec_inventory_items',
			'delete_published_ec_inventory_items',
			'delete_others_ec_inventory_items',
			'edit_private_ec_inventory_items',
			'edit_published_ec_inventory_items',
			'create_ec_inventory_items',
			'edit_ec_appointment',
			'read_ec_appointment',
			'delete_ec_appointment',
			'edit_ec_appointments',
			'edit_others_ec_appointments',
			'publish_ec_appointments',
			'read_private_ec_appointments',
			'delete_ec_appointments',
			'delete_private_ec_appointments',
			'delete_published_ec_appointments',
			'delete_others_ec_appointments',
			'edit_private_ec_appointments',
			'edit_published_ec_appointments',
			'create_ec_appointments',
		);

		$operator_caps = array(
			'edit_ec_service_order',
			'read_ec_service_order',
			'delete_ec_service_order',
			'edit_ec_service_orders',
			'publish_ec_service_orders',
			'edit_published_ec_service_orders',
			'create_ec_service_orders',
			'edit_ec_quotation',
			'read_ec_quotation',
			'delete_ec_quotation',
			'edit_ec_quotations',
			'publish_ec_quotations',
			'edit_published_ec_quotations',
			'create_ec_quotations',
			'edit_ec_appointment',
			'read_ec_appointment',
			'delete_ec_appointment',
			'edit_ec_appointments',
			'publish_ec_appointments',
			'edit_published_ec_appointments',
			'create_ec_appointments',
		);

		self::grant_caps_to_role( 'ec_admin', $admin_caps );
		self::grant_caps_to_role( 'ec_operator', $operator_caps );
	}

	/**
	 * Otorga capacidades a un rol.
	 *
	 * @param string $role_slug Slug del rol.
	 * @param array  $caps      Lista de capacidades.
	 * @return void
	 */
	private static function grant_caps_to_role( $role_slug, $caps ) {
		$role = get_role( $role_slug );

		if ( ! $role ) {
			return;
		}

		foreach ( $caps as $cap ) {
			$role->add_cap( $cap );
		}
	}

	/**
	 * Agrega metabox para datos clave de la orden.
	 *
	 * @return void
	 */
	public function add_service_order_meta_box() {
		add_meta_box(
			'ec_service_order_details',
			__( 'Datos de la Orden de Servicio', 'electrocam-crm' ),
			array( $this, 'render_service_order_meta_box' ),
			'service_order',
			'normal',
			'default'
		);
	}

	/**
	 * Agrega metabox para datos de cotización.
	 *
	 * @return void
	 */
	public function add_quotation_meta_box() {
		add_meta_box(
			'ec_quotation_details',
			__( 'Datos de Cotización', 'electrocam-crm' ),
			array( $this, 'render_quotation_meta_box' ),
			'quotation',
			'normal',
			'default'
		);
	}

	/**
	 * Agrega metabox para inventario.
	 *
	 * @return void
	 */
	public function add_inventory_item_meta_box() {
		add_meta_box(
			'ec_inventory_details',
			__( 'Datos del artículo', 'electrocam-crm' ),
			array( $this, 'render_inventory_item_meta_box' ),
			'inventory_item',
			'normal',
			'default'
		);
	}

	/**
	 * Agrega metabox para citas.
	 *
	 * @return void
	 */
	public function add_appointment_meta_box() {
		add_meta_box(
			'ec_appointment_details',
			__( 'Datos de la cita', 'electrocam-crm' ),
			array( $this, 'render_appointment_meta_box' ),
			'appointment',
			'normal',
			'default'
		);
	}

	/**
	 * Renderiza campos de la orden de servicio.
	 *
	 * @param WP_Post $post Post actual.
	 * @return void
	 */
	public function render_service_order_meta_box( $post ) {
		wp_nonce_field( 'ec_save_service_order', 'ec_service_order_nonce' );

		$fields = array(
			'ec_client_id'           => get_post_meta( $post->ID, 'ec_client_id', true ),
			'ec_operator_id'         => get_post_meta( $post->ID, 'ec_operator_id', true ),
			'ec_service_date'        => get_post_meta( $post->ID, 'ec_service_date', true ),
			'ec_status'              => get_post_meta( $post->ID, 'ec_status', true ),
			'ec_modality'            => get_post_meta( $post->ID, 'ec_modality', true ),
			'ec_next_control_days'   => get_post_meta( $post->ID, 'ec_next_control_days', true ),
			'ec_problem_description' => get_post_meta( $post->ID, 'ec_problem_description', true ),
			'ec_solution_description'=> get_post_meta( $post->ID, 'ec_solution_description', true ),
			'ec_order_number'        => get_post_meta( $post->ID, 'ec_order_number', true ),
		);
		?>
		<p>
			<strong><?php esc_html_e( 'Consecutivo:', 'electrocam-crm' ); ?></strong>
			<?php echo esc_html( $fields['ec_order_number'] ? $fields['ec_order_number'] : __( 'Se asigna al guardar.', 'electrocam-crm' ) ); ?>
		</p>
		<p>
			<label for="ec_client_id"><?php esc_html_e( 'ID Cliente', 'electrocam-crm' ); ?></label><br>
			<input type="number" name="ec_client_id" id="ec_client_id" value="<?php echo esc_attr( $fields['ec_client_id'] ); ?>" min="1" class="widefat">
		</p>
		<p>
			<label for="ec_operator_id"><?php esc_html_e( 'ID Operario', 'electrocam-crm' ); ?></label><br>
			<input type="number" name="ec_operator_id" id="ec_operator_id" value="<?php echo esc_attr( $fields['ec_operator_id'] ); ?>" min="1" class="widefat">
		</p>
		<p>
			<label for="ec_service_date"><?php esc_html_e( 'Fecha de servicio', 'electrocam-crm' ); ?></label><br>
			<input type="date" name="ec_service_date" id="ec_service_date" value="<?php echo esc_attr( $fields['ec_service_date'] ); ?>" class="widefat">
		</p>
		<p>
			<label for="ec_status"><?php esc_html_e( 'Estado', 'electrocam-crm' ); ?></label><br>
			<select name="ec_status" id="ec_status" class="widefat">
				<?php foreach ( array( 'pending' => 'Pendiente', 'in_progress' => 'En curso', 'completed' => 'Finalizado' ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $fields['ec_status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="ec_modality"><?php esc_html_e( 'Modalidad', 'electrocam-crm' ); ?></label><br>
			<select name="ec_modality" id="ec_modality" class="widefat">
				<?php foreach ( array( 'on_site' => 'Presencial', 'virtual' => 'Virtual' ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $fields['ec_modality'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="ec_next_control_days"><?php esc_html_e( 'Próximo control (días)', 'electrocam-crm' ); ?></label><br>
			<select name="ec_next_control_days" id="ec_next_control_days" class="widefat">
				<option value=""><?php esc_html_e( 'Sin programación', 'electrocam-crm' ); ?></option>
				<?php foreach ( array( '30', '60', '90' ) as $days ) : ?>
					<option value="<?php echo esc_attr( $days ); ?>" <?php selected( (string) $fields['ec_next_control_days'], $days ); ?>><?php echo esc_html( $days ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="ec_problem_description"><?php esc_html_e( 'Descripción del problema', 'electrocam-crm' ); ?></label><br>
			<textarea name="ec_problem_description" id="ec_problem_description" class="widefat" rows="4"><?php echo esc_textarea( $fields['ec_problem_description'] ); ?></textarea>
		</p>
		<p>
			<label for="ec_solution_description"><?php esc_html_e( 'Descripción de la solución', 'electrocam-crm' ); ?></label><br>
			<textarea name="ec_solution_description" id="ec_solution_description" class="widefat" rows="4"><?php echo esc_textarea( $fields['ec_solution_description'] ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Renderiza campos de cotización.
	 *
	 * @param WP_Post $post Post actual.
	 * @return void
	 */
	public function render_quotation_meta_box( $post ) {
		wp_nonce_field( 'ec_save_quotation', 'ec_quotation_nonce' );

		$client_id = get_post_meta( $post->ID, 'ec_client_id', true );
		$operator_id = get_post_meta( $post->ID, 'ec_operator_id', true );
		$issue_date = get_post_meta( $post->ID, 'ec_issue_date', true );
		$valid_until = get_post_meta( $post->ID, 'ec_valid_until', true );
		$subtotal = get_post_meta( $post->ID, 'ec_subtotal', true );
		$tax_rate = get_post_meta( $post->ID, 'ec_tax_rate', true );
		$total = get_post_meta( $post->ID, 'ec_total', true );
		$terms = get_post_meta( $post->ID, 'ec_terms_conditions', true );
		$quotation_status = get_post_meta( $post->ID, 'ec_quotation_status', true );
		$quote_items = get_post_meta( $post->ID, 'ec_quote_items', true );
		if ( ! is_array( $quote_items ) ) {
			$quote_items = array();
		}

		$max_rows = max( 5, count( $quote_items ) );
		?>
		<p>
			<label for="ec_client_id"><?php esc_html_e( 'ID Cliente', 'electrocam-crm' ); ?></label><br>
			<input type="number" name="ec_client_id" id="ec_client_id" min="1" class="widefat" value="<?php echo esc_attr( $client_id ); ?>">
		</p>
		<p>
			<label for="ec_operator_id"><?php esc_html_e( 'ID Operario', 'electrocam-crm' ); ?></label><br>
			<input type="number" name="ec_operator_id" id="ec_operator_id" min="1" class="widefat" value="<?php echo esc_attr( $operator_id ); ?>">
		</p>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
			<p>
				<label for="ec_issue_date"><?php esc_html_e( 'Fecha de emisión', 'electrocam-crm' ); ?></label><br>
				<input type="date" name="ec_issue_date" id="ec_issue_date" class="widefat" value="<?php echo esc_attr( $issue_date ); ?>">
			</p>
			<p>
				<label for="ec_valid_until"><?php esc_html_e( 'Vigencia', 'electrocam-crm' ); ?></label><br>
				<input type="date" name="ec_valid_until" id="ec_valid_until" class="widefat" value="<?php echo esc_attr( $valid_until ); ?>">
			</p>
		</div>
		<p><strong><?php esc_html_e( 'Ítems de cotización', 'electrocam-crm' ); ?></strong></p>
		<table class="widefat striped" style="margin-bottom:12px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Nombre', 'electrocam-crm' ); ?></th>
					<th><?php esc_html_e( 'Descripción', 'electrocam-crm' ); ?></th>
					<th><?php esc_html_e( 'Cantidad', 'electrocam-crm' ); ?></th>
					<th><?php esc_html_e( 'Precio unitario', 'electrocam-crm' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php for ( $i = 0; $i < $max_rows; $i++ ) : ?>
					<?php $item = isset( $quote_items[ $i ] ) && is_array( $quote_items[ $i ] ) ? $quote_items[ $i ] : array(); ?>
					<tr>
						<td><input type="text" name="ec_quote_item_name[]" class="widefat" value="<?php echo esc_attr( isset( $item['name'] ) ? $item['name'] : '' ); ?>" maxlength="140"></td>
						<td><input type="text" name="ec_quote_item_description[]" class="widefat" value="<?php echo esc_attr( isset( $item['description'] ) ? $item['description'] : '' ); ?>" maxlength="300"></td>
						<td><input type="number" name="ec_quote_item_qty[]" class="widefat" value="<?php echo esc_attr( isset( $item['qty'] ) ? $item['qty'] : '' ); ?>" min="0" step="1"></td>
						<td><input type="number" name="ec_quote_item_unit_price[]" class="widefat" value="<?php echo esc_attr( isset( $item['unit_price'] ) ? $item['unit_price'] : '' ); ?>" min="0" step="0.01"></td>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Si agregas ítems, el subtotal y total se recalculan automáticamente al guardar.', 'electrocam-crm' ); ?></p>
		<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
			<p>
				<label for="ec_subtotal"><?php esc_html_e( 'Subtotal', 'electrocam-crm' ); ?></label><br>
				<input type="number" name="ec_subtotal" id="ec_subtotal" class="widefat" value="<?php echo esc_attr( $subtotal ); ?>" min="0" step="0.01">
			</p>
			<p>
				<label for="ec_tax_rate"><?php esc_html_e( 'IVA (%)', 'electrocam-crm' ); ?></label><br>
				<input type="number" name="ec_tax_rate" id="ec_tax_rate" class="widefat" value="<?php echo esc_attr( '' !== $tax_rate ? $tax_rate : '19' ); ?>" min="0" max="100" step="0.01">
			</p>
			<p>
				<label for="ec_total"><?php esc_html_e( 'Total', 'electrocam-crm' ); ?></label><br>
				<input type="number" name="ec_total" id="ec_total" class="widefat" value="<?php echo esc_attr( $total ); ?>" min="0" step="0.01">
			</p>
		</div>
		<p>
			<label for="ec_quotation_status"><?php esc_html_e( 'Estado de cotización', 'electrocam-crm' ); ?></label><br>
			<select name="ec_quotation_status" id="ec_quotation_status" class="widefat">
				<?php foreach ( array( 'draft' => 'Borrador', 'sent' => 'Enviada', 'approved' => 'Aprobada', 'rejected' => 'Rechazada' ) as $status_value => $status_label ) : ?>
					<option value="<?php echo esc_attr( $status_value ); ?>" <?php selected( $quotation_status ? $quotation_status : 'draft', $status_value ); ?>><?php echo esc_html( $status_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="ec_terms_conditions"><?php esc_html_e( 'Términos y condiciones', 'electrocam-crm' ); ?></label><br>
			<textarea name="ec_terms_conditions" id="ec_terms_conditions" class="widefat" rows="4"><?php echo esc_textarea( $terms ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Renderiza campos de inventario.
	 *
	 * @param WP_Post $post Post actual.
	 * @return void
	 */
	public function render_inventory_item_meta_box( $post ) {
		wp_nonce_field( 'ec_save_inventory_item', 'ec_inventory_item_nonce' );

		$stock_quantity = get_post_meta( $post->ID, 'ec_stock_quantity', true );
		$unit_price = get_post_meta( $post->ID, 'ec_unit_price', true );
		$sku = get_post_meta( $post->ID, 'ec_sku', true );
		?>
		<p>
			<label for="ec_sku"><?php esc_html_e( 'SKU interno', 'electrocam-crm' ); ?></label><br>
			<input type="text" name="ec_sku" id="ec_sku" class="widefat" value="<?php echo esc_attr( $sku ); ?>" maxlength="100">
		</p>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
			<p>
				<label for="ec_stock_quantity"><?php esc_html_e( 'Cantidad disponible', 'electrocam-crm' ); ?></label><br>
				<input type="number" name="ec_stock_quantity" id="ec_stock_quantity" class="widefat" value="<?php echo esc_attr( $stock_quantity ); ?>" min="0" step="1">
			</p>
			<p>
				<label for="ec_unit_price"><?php esc_html_e( 'Valor unitario', 'electrocam-crm' ); ?></label><br>
				<input type="number" name="ec_unit_price" id="ec_unit_price" class="widefat" value="<?php echo esc_attr( $unit_price ); ?>" min="0" step="0.01">
			</p>
		</div>
		<?php
	}

	/**
	 * Renderiza campos de cita.
	 *
	 * @param WP_Post $post Post actual.
	 * @return void
	 */
	public function render_appointment_meta_box( $post ) {
		wp_nonce_field( 'ec_save_appointment', 'ec_appointment_nonce' );

		$service_order_id = get_post_meta( $post->ID, 'ec_service_order_id', true );
		$client_id = get_post_meta( $post->ID, 'ec_client_id', true );
		$date = get_post_meta( $post->ID, 'ec_appointment_date', true );
		$time = get_post_meta( $post->ID, 'ec_appointment_time', true );
		$modality = get_post_meta( $post->ID, 'ec_modality', true );
		$status = get_post_meta( $post->ID, 'ec_status', true );
		$reschedule_history = get_post_meta( $post->ID, 'ec_reschedule_history', true );
		?>
		<p>
			<label for="ec_service_order_id"><?php esc_html_e( 'ID Orden de servicio', 'electrocam-crm' ); ?></label><br>
			<input type="number" name="ec_service_order_id" id="ec_service_order_id" min="1" class="widefat" value="<?php echo esc_attr( $service_order_id ); ?>">
		</p>
		<p>
			<label for="ec_client_id"><?php esc_html_e( 'ID Cliente', 'electrocam-crm' ); ?></label><br>
			<input type="number" name="ec_client_id" id="ec_client_id" min="1" class="widefat" value="<?php echo esc_attr( $client_id ); ?>">
		</p>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
			<p>
				<label for="ec_appointment_date"><?php esc_html_e( 'Fecha', 'electrocam-crm' ); ?></label><br>
				<input type="date" name="ec_appointment_date" id="ec_appointment_date" class="widefat" value="<?php echo esc_attr( $date ); ?>">
			</p>
			<p>
				<label for="ec_appointment_time"><?php esc_html_e( 'Hora', 'electrocam-crm' ); ?></label><br>
				<input type="time" name="ec_appointment_time" id="ec_appointment_time" class="widefat" value="<?php echo esc_attr( $time ); ?>">
			</p>
		</div>
		<p>
			<label for="ec_modality"><?php esc_html_e( 'Modalidad', 'electrocam-crm' ); ?></label><br>
			<select name="ec_modality" id="ec_modality" class="widefat">
				<?php foreach ( array( 'on_site' => 'Presencial', 'virtual' => 'Virtual' ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $modality, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="ec_status"><?php esc_html_e( 'Estado', 'electrocam-crm' ); ?></label><br>
			<select name="ec_status" id="ec_status" class="widefat">
				<?php foreach ( array( 'scheduled' => 'Programada', 'completed' => 'Completada', 'cancelled' => 'Cancelada' ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<hr>
		<p><strong><?php esc_html_e( 'Historial de reprogramaciones', 'electrocam-crm' ); ?></strong></p>
		<?php if ( ! empty( $reschedule_history ) && is_array( $reschedule_history ) ) : ?>
			<ul>
				<?php foreach ( array_reverse( $reschedule_history ) as $item ) : ?>
					<li>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: old slot, 2: new slot, 3: user id, 4: date */
								__( 'De %1$s a %2$s por usuario #%3$d (%4$s)', 'electrocam-crm' ),
								$item['from'],
								$item['to'],
								$item['user_id'],
								$item['changed_at']
							)
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'Sin reprogramaciones registradas.', 'electrocam-crm' ); ?></p>
		<?php endif; ?>
		<?php
		$email_audit = get_post_meta( $post->ID, 'ec_email_audit', true );
		?>
		<hr>
		<p><strong><?php esc_html_e( 'Auditoría de correos', 'electrocam-crm' ); ?></strong></p>
		<?php if ( ! empty( $email_audit ) && is_array( $email_audit ) ) : ?>
			<ul>
				<?php foreach ( array_reverse( array_slice( $email_audit, -5 ) ) as $entry ) : ?>
					<li><?php echo esc_html( sprintf( __( '%1$s - %2$s (%3$s)', 'electrocam-crm' ), isset( $entry['event'] ) ? $entry['event'] : '', isset( $entry['subject'] ) ? $entry['subject'] : '', isset( $entry['sent_at'] ) ? $entry['sent_at'] : '' ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'Sin envíos registrados.', 'electrocam-crm' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Guarda metadatos de la orden y genera cita de control.
	 *
	 * @param int     $post_id ID del post.
	 * @param WP_Post $post    Post guardado.
	 * @return void
	 */
	public function save_service_order_meta( $post_id, $post ) {
		if ( ! isset( $_POST['ec_service_order_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ec_service_order_nonce'] ) ), 'ec_save_service_order' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_ec_service_order', $post_id ) ) {
			return;
		}

		$client_id = isset( $_POST['ec_client_id'] ) ? absint( wp_unslash( $_POST['ec_client_id'] ) ) : 0;
		$operator_id = isset( $_POST['ec_operator_id'] ) ? absint( wp_unslash( $_POST['ec_operator_id'] ) ) : 0;
		$service_date = isset( $_POST['ec_service_date'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_service_date'] ) ) : '';
		$status = isset( $_POST['ec_status'] ) ? sanitize_key( wp_unslash( $_POST['ec_status'] ) ) : 'pending';
		$modality = isset( $_POST['ec_modality'] ) ? sanitize_key( wp_unslash( $_POST['ec_modality'] ) ) : 'on_site';
		$next_control_days = isset( $_POST['ec_next_control_days'] ) ? absint( wp_unslash( $_POST['ec_next_control_days'] ) ) : 0;
		$problem_description = isset( $_POST['ec_problem_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ec_problem_description'] ) ) : '';
		$solution_description = isset( $_POST['ec_solution_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ec_solution_description'] ) ) : '';

		update_post_meta( $post_id, 'ec_client_id', $client_id );
		update_post_meta( $post_id, 'ec_operator_id', $operator_id );
		update_post_meta( $post_id, 'ec_service_date', $service_date );
		update_post_meta( $post_id, 'ec_status', $status );
		update_post_meta( $post_id, 'ec_modality', $modality );
		update_post_meta( $post_id, 'ec_next_control_days', $next_control_days );
		update_post_meta( $post_id, 'ec_problem_description', $problem_description );
		update_post_meta( $post_id, 'ec_solution_description', $solution_description );

		$this->ensure_service_order_number( $post_id );
		$this->maybe_generate_control_appointment( $post_id, $post, $service_date, $next_control_days, $client_id, $modality );
	}

	/**
	 * Guarda metadatos de cotización.
	 *
	 * @param int     $post_id ID del post.
	 * @param WP_Post $post    Post guardado.
	 * @return void
	 */
	public function save_quotation_meta( $post_id, $post ) {
		if ( ! isset( $_POST['ec_quotation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ec_quotation_nonce'] ) ), 'ec_save_quotation' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'quotation' !== $post->post_type || ! current_user_can( 'edit_ec_quotation', $post_id ) ) {
			return;
		}

		$client_id = isset( $_POST['ec_client_id'] ) ? absint( wp_unslash( $_POST['ec_client_id'] ) ) : 0;
		$operator_id = isset( $_POST['ec_operator_id'] ) ? absint( wp_unslash( $_POST['ec_operator_id'] ) ) : 0;
		$issue_date = isset( $_POST['ec_issue_date'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_issue_date'] ) ) : '';
		$valid_until = isset( $_POST['ec_valid_until'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_valid_until'] ) ) : '';
		$subtotal = isset( $_POST['ec_subtotal'] ) ? (float) wp_unslash( $_POST['ec_subtotal'] ) : 0;
		$tax_rate = isset( $_POST['ec_tax_rate'] ) ? (float) wp_unslash( $_POST['ec_tax_rate'] ) : 19;
		$total = isset( $_POST['ec_total'] ) ? (float) wp_unslash( $_POST['ec_total'] ) : 0;
		$terms = isset( $_POST['ec_terms_conditions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ec_terms_conditions'] ) ) : '';
		$quotation_status = isset( $_POST['ec_quotation_status'] ) ? sanitize_key( wp_unslash( $_POST['ec_quotation_status'] ) ) : 'draft';

		$item_names = isset( $_POST['ec_quote_item_name'] ) && is_array( $_POST['ec_quote_item_name'] ) ? wp_unslash( $_POST['ec_quote_item_name'] ) : array();
		$item_descriptions = isset( $_POST['ec_quote_item_description'] ) && is_array( $_POST['ec_quote_item_description'] ) ? wp_unslash( $_POST['ec_quote_item_description'] ) : array();
		$item_qtys = isset( $_POST['ec_quote_item_qty'] ) && is_array( $_POST['ec_quote_item_qty'] ) ? wp_unslash( $_POST['ec_quote_item_qty'] ) : array();
		$item_unit_prices = isset( $_POST['ec_quote_item_unit_price'] ) && is_array( $_POST['ec_quote_item_unit_price'] ) ? wp_unslash( $_POST['ec_quote_item_unit_price'] ) : array();

		$items = array();
		$items_subtotal = 0;
		$row_count = max( count( $item_names ), count( $item_descriptions ), count( $item_qtys ), count( $item_unit_prices ) );
		for ( $i = 0; $i < $row_count; $i++ ) {
			$name = isset( $item_names[ $i ] ) ? sanitize_text_field( $item_names[ $i ] ) : '';
			$description = isset( $item_descriptions[ $i ] ) ? sanitize_text_field( $item_descriptions[ $i ] ) : '';
			$qty = isset( $item_qtys[ $i ] ) ? max( 0, (float) $item_qtys[ $i ] ) : 0;
			$unit_price = isset( $item_unit_prices[ $i ] ) ? max( 0, (float) $item_unit_prices[ $i ] ) : 0;

			if ( '' === $name && '' === $description && $qty <= 0 && $unit_price <= 0 ) {
				continue;
			}

			$line_total = round( $qty * $unit_price, 2 );
			$items_subtotal += $line_total;
			$items[] = array(
				'name' => $name,
				'description' => $description,
				'qty' => round( $qty, 2 ),
				'unit_price' => round( $unit_price, 2 ),
				'line_total' => $line_total,
			);
		}

		if ( ! empty( $items ) ) {
			$subtotal = $items_subtotal;
		}

		if ( $tax_rate < 0 ) {
			$tax_rate = 0;
		}

		$allowed_statuses = array( 'draft', 'sent', 'approved', 'rejected' );
		if ( ! in_array( $quotation_status, $allowed_statuses, true ) ) {
			$quotation_status = 'draft';
		}

		$total = $subtotal + ( $subtotal * ( $tax_rate / 100 ) );

		update_post_meta( $post_id, 'ec_client_id', $client_id );
		update_post_meta( $post_id, 'ec_operator_id', $operator_id );
		update_post_meta( $post_id, 'ec_issue_date', $issue_date );
		update_post_meta( $post_id, 'ec_valid_until', $valid_until );
		update_post_meta( $post_id, 'ec_subtotal', round( $subtotal, 2 ) );
		update_post_meta( $post_id, 'ec_tax_rate', round( $tax_rate, 2 ) );
		update_post_meta( $post_id, 'ec_total', round( $total, 2 ) );
		update_post_meta( $post_id, 'ec_terms_conditions', $terms );
		update_post_meta( $post_id, 'ec_quotation_status', $quotation_status );
		update_post_meta( $post_id, 'ec_quote_items', $items );
	}

	/**
	 * Guarda metadatos de inventario.
	 *
	 * @param int     $post_id ID del post.
	 * @param WP_Post $post    Post guardado.
	 * @return void
	 */
	public function save_inventory_item_meta( $post_id, $post ) {
		if ( ! isset( $_POST['ec_inventory_item_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ec_inventory_item_nonce'] ) ), 'ec_save_inventory_item' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'inventory_item' !== $post->post_type || ! current_user_can( 'edit_ec_inventory_item', $post_id ) ) {
			return;
		}

		$sku = isset( $_POST['ec_sku'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_sku'] ) ) : '';
		$stock_quantity = isset( $_POST['ec_stock_quantity'] ) ? absint( wp_unslash( $_POST['ec_stock_quantity'] ) ) : 0;
		$unit_price = isset( $_POST['ec_unit_price'] ) ? (float) wp_unslash( $_POST['ec_unit_price'] ) : 0;

		update_post_meta( $post_id, 'ec_sku', $sku );
		update_post_meta( $post_id, 'ec_stock_quantity', $stock_quantity );
		update_post_meta( $post_id, 'ec_unit_price', round( $unit_price, 2 ) );
	}

	/**
	 * Guarda metadatos de cita y evita solapamientos en fecha/hora.
	 *
	 * @param int     $post_id ID del post.
	 * @param WP_Post $post    Post guardado.
	 * @return void
	 */
	public function save_appointment_meta( $post_id, $post ) {
		if ( ! isset( $_POST['ec_appointment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ec_appointment_nonce'] ) ), 'ec_save_appointment' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'appointment' !== $post->post_type || ! current_user_can( 'edit_ec_appointment', $post_id ) ) {
			return;
		}

		$service_order_id = isset( $_POST['ec_service_order_id'] ) ? absint( wp_unslash( $_POST['ec_service_order_id'] ) ) : 0;
		$client_id = isset( $_POST['ec_client_id'] ) ? absint( wp_unslash( $_POST['ec_client_id'] ) ) : 0;
		$date = isset( $_POST['ec_appointment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_appointment_date'] ) ) : '';
		$time = isset( $_POST['ec_appointment_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_appointment_time'] ) ) : '';
		$modality = isset( $_POST['ec_modality'] ) ? sanitize_key( wp_unslash( $_POST['ec_modality'] ) ) : 'on_site';
		$status = isset( $_POST['ec_status'] ) ? sanitize_key( wp_unslash( $_POST['ec_status'] ) ) : 'scheduled';

		if ( ! empty( $date ) && ! empty( $time ) && ! $this->is_appointment_slot_available( $date, $time, $post_id ) ) {
			set_transient( 'ec_admin_notice_appointment_overlap', 1, 30 );
			return;
		}

		update_post_meta( $post_id, 'ec_service_order_id', $service_order_id );
		update_post_meta( $post_id, 'ec_client_id', $client_id );
		update_post_meta( $post_id, 'ec_appointment_date', $date );
		update_post_meta( $post_id, 'ec_appointment_time', $time );
		update_post_meta( $post_id, 'ec_modality', $modality );
		update_post_meta( $post_id, 'ec_status', $status );
	}

	/**
	 * Muestra avisos administrativos del plugin.
	 *
	 * @return void
	 */
	public function render_admin_notices() {
		if ( ! get_transient( 'ec_admin_notice_appointment_overlap' ) ) {
			return;
		}

		delete_transient( 'ec_admin_notice_appointment_overlap' );
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Ya existe una cita programada para esa fecha y hora. Selecciona otro horario.', 'electrocam-crm' ) . '</p></div>';
	}

	/**
	 * Verifica disponibilidad de agenda en un slot fecha/hora.
	 *
	 * @param string $date Fecha de cita.
	 * @param string $time Hora de cita.
	 * @param int    $exclude_post_id Cita actual para excluir en edición.
	 * @return bool
	 */
	private function is_appointment_slot_available( $date, $time, $exclude_post_id = 0 ) {
		$query = array(
			'post_type'      => 'appointment',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => 1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'   => 'ec_appointment_date',
					'value' => $date,
				),
				array(
					'key'   => 'ec_appointment_time',
					'value' => $time,
				),
			),
		);

		if ( $exclude_post_id > 0 ) {
			$query['post__not_in'] = array( $exclude_post_id );
		}

		return empty( get_posts( $query ) );
	}



	/**
	 * Registra página de ajustes del plugin.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'Electrocam CRM Ajustes', 'electrocam-crm' ),
			__( 'Electrocam CRM', 'electrocam-crm' ),
			'manage_options',
			'electrocam-crm-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registra settings del plugin.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ec_settings_group',
			'ec_support_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_support_email_setting' ),
				'default'           => 'servicioalcliente@electrocam.com',
			)
		);

		register_setting(
			'ec_settings_group',
			'ec_notification_cc_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_optional_notification_email' ),
				'default'           => '',
			)
		);

		register_setting(
			'ec_settings_group',
			'ec_audit_retention_limit',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_audit_retention_limit' ),
				'default'           => 50,
			)
		);
	}

	/**
	 * Renderiza página de ajustes.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ajustes Electrocam CRM', 'electrocam-crm' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'ec_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ec_support_email"><?php esc_html_e( 'Correo de soporte', 'electrocam-crm' ); ?></label></th>
						<td>
							<input type="email" id="ec_support_email" name="ec_support_email" value="<?php echo esc_attr( get_option( 'ec_support_email', 'servicioalcliente@electrocam.com' ) ); ?>" class="regular-text" required>
							<p class="description"><?php esc_html_e( 'Se usa para copias y auditoría de notificaciones del plugin.', 'electrocam-crm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ec_notification_cc_email"><?php esc_html_e( 'Correo secundario (CC)', 'electrocam-crm' ); ?></label></th>
						<td>
							<input type="email" id="ec_notification_cc_email" name="ec_notification_cc_email" value="<?php echo esc_attr( get_option( 'ec_notification_cc_email', '' ) ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Opcional. Recibe copia de notificaciones de reprogramación y comentarios.', 'electrocam-crm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ec_audit_retention_limit"><?php esc_html_e( 'Límite de auditoría', 'electrocam-crm' ); ?></label></th>
						<td>
							<input type="number" id="ec_audit_retention_limit" name="ec_audit_retention_limit" value="<?php echo esc_attr( (int) get_option( 'ec_audit_retention_limit', 50 ) ); ?>" min="10" max="500" step="1" class="small-text">
							<p class="description"><?php esc_html_e( 'Cantidad máxima de registros que se conservarán en historiales de auditoría y reprogramación.', 'electrocam-crm' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}



	/**
	 * Sanitiza correo principal de soporte.
	 *
	 * @param string $value Valor recibido.
	 * @return string
	 */
	public function sanitize_support_email_setting( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		$sanitized = sanitize_email( $value );

		if ( empty( $sanitized ) ) {
			add_settings_error(
				'ec_support_email',
				'ec_support_email_invalid',
				__( 'El correo de soporte no es válido. Se restauró el valor por defecto.', 'electrocam-crm' ),
				'error'
			);
			return 'servicioalcliente@electrocam.com';
		}

		return $sanitized;
	}

	/**
	 * Sanitiza correo opcional de notificación.
	 *
	 * @param string $value Valor recibido.
	 * @return string
	 */
	public function sanitize_optional_notification_email( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( '' === $value ) {
			return '';
		}

		$sanitized = sanitize_email( $value );
		if ( empty( $sanitized ) ) {
			add_settings_error(
				'ec_notification_cc_email',
				'ec_notification_cc_email_invalid',
				__( 'El correo secundario (CC) no es válido y no se guardó.', 'electrocam-crm' ),
				'error'
			);
			return '';
		}

		return $sanitized;
	}


	/**
	 * Sanitiza límite de retención de auditoría.
	 *
	 * @param mixed $value Valor recibido.
	 * @return int
	 */
	public function sanitize_audit_retention_limit( $value ) {
		$limit = absint( $value );
		if ( $limit < 10 ) {
			$limit = 10;
		}
		if ( $limit > 500 ) {
			$limit = 500;
		}

		return $limit;
	}

	/**
	 * Renderiza mensajes de settings del plugin.
	 *
	 * @return void
	 */
	public function render_settings_errors_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'settings_page_electrocam-crm-settings' !== $screen->id ) {
			return;
		}

		settings_errors();
	}

	/**
	 * Renderiza listado de órdenes para cliente autenticado.
	 *
	 * @return string
	 */
	public function render_client_orders_shortcode() {
		$current_user_id = get_current_user_id();

		if ( ! $current_user_id ) {
			return '<p>' . esc_html__( 'Debes iniciar sesión para ver tus órdenes.', 'electrocam-crm' ) . '</p>';
		}

		if ( ! current_user_can( 'read' ) ) {
			return '<p>' . esc_html__( 'No tienes permisos para ver esta información.', 'electrocam-crm' ) . '</p>';
		}

		$orders = get_posts(
			array(
				'post_type'      => 'service_order',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'   => 'ec_client_id',
						'value' => $current_user_id,
					),
				),
			)
		);

		if ( empty( $orders ) ) {
			return '<p>' . esc_html__( 'No tienes órdenes registradas.', 'electrocam-crm' ) . '</p>';
		}

		$output = '<ul class="ec-client-list ec-client-orders">';

		foreach ( $orders as $order ) {
			$order_number = get_post_meta( $order->ID, 'ec_order_number', true );
			$status = get_post_meta( $order->ID, 'ec_status', true );
			$service_date = get_post_meta( $order->ID, 'ec_service_date', true );
			$output .= '<li><strong>' . esc_html( $order_number ? $order_number : $order->post_title ) . '</strong> - ' . esc_html( $service_date ) . ' - ' . esc_html( $status ) . '</li>';
		}

		$output .= '</ul>';


		return $output;
	}

	/**
	 * Obtiene etiqueta de estado de cotización para frontend.
	 *
	 * @param string $status Estado almacenado.
	 * @return string
	 */
	private function get_quotation_status_label( $status ) {
		$labels = array(
			'draft' => __( 'Borrador', 'electrocam-crm' ),
			'sent' => __( 'Enviada', 'electrocam-crm' ),
			'approved' => __( 'Aprobada', 'electrocam-crm' ),
			'rejected' => __( 'Rechazada', 'electrocam-crm' ),
			'expired' => __( 'Vencida', 'electrocam-crm' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['draft'];
	}

	/**
	 * Renderiza listado de cotizaciones para cliente autenticado.
	 *
	 * @return string
	 */
	public function render_client_quotations_shortcode( $atts = array() ) {
		$current_user_id = get_current_user_id();
		$atts = shortcode_atts(
			array(
				'status' => '',
				'show_expired' => 'yes',
			),
			$atts,
			'ec_client_quotations'
		);

		if ( ! $current_user_id ) {
			return '<p>' . esc_html__( 'Debes iniciar sesión para ver tus cotizaciones.', 'electrocam-crm' ) . '</p>';
		}

		$message = $this->get_quotation_feedback_message();
		$filter_status = sanitize_key( (string) $atts['status'] );
		$show_expired = 'no' !== strtolower( (string) $atts['show_expired'] );

		$allowed_filter_statuses = array( 'draft', 'sent', 'approved', 'rejected', 'expired' );
		if ( $filter_status && ! in_array( $filter_status, $allowed_filter_statuses, true ) ) {
			$filter_status = '';
		}

		$quotations = get_posts(
			array(
				'post_type'      => 'quotation',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'   => 'ec_client_id',
						'value' => $current_user_id,
					),
				),
			)
		);

		if ( empty( $quotations ) ) {
			return $message . '<p>' . esc_html__( 'No tienes cotizaciones registradas.', 'electrocam-crm' ) . '</p>';
		}

		$output = $message;
		$output .= '<ul class="ec-client-list ec-client-quotations">';
		$has_results = false;

		foreach ( $quotations as $quotation ) {
			$subtotal = (float) get_post_meta( $quotation->ID, 'ec_subtotal', true );
			$tax_rate = (float) get_post_meta( $quotation->ID, 'ec_tax_rate', true );
			$total = (float) get_post_meta( $quotation->ID, 'ec_total', true );
			$valid_until = get_post_meta( $quotation->ID, 'ec_valid_until', true );
			$quote_items = get_post_meta( $quotation->ID, 'ec_quote_items', true );
			$item_count = is_array( $quote_items ) ? count( $quote_items ) : 0;
			$status = get_post_meta( $quotation->ID, 'ec_quotation_status', true );
			$valid_timestamp = $valid_until ? strtotime( $valid_until . ' 23:59:59' ) : false;
			if ( $valid_timestamp && $valid_timestamp < current_time( 'timestamp' ) && ! in_array( $status, array( 'approved', 'rejected' ), true ) ) {
				$status = 'expired';
			}

			if ( ! $show_expired && 'expired' === $status ) {
				continue;
			}

			if ( $filter_status && $filter_status !== $status ) {
				continue;
			}

			$has_results = true;
			$output .= '<li><strong>' . esc_html( $quotation->post_title ) . '</strong><br>';
			$output .= esc_html__( 'Estado:', 'electrocam-crm' ) . ' ' . esc_html( $this->get_quotation_status_label( $status ? $status : 'draft' ) ) . '<br>';
			$output .= esc_html__( 'Ítems:', 'electrocam-crm' ) . ' ' . esc_html( (string) $item_count ) . ' · ';
			$output .= esc_html__( 'Subtotal:', 'electrocam-crm' ) . ' $' . esc_html( number_format_i18n( $subtotal, 2 ) ) . ' · ';
			$output .= esc_html__( 'IVA:', 'electrocam-crm' ) . ' ' . esc_html( number_format_i18n( $tax_rate, 2 ) ) . '% · ';
			$output .= esc_html__( 'Total:', 'electrocam-crm' ) . ' $' . esc_html( number_format_i18n( $total, 2 ) ) . '<br>';
			$output .= esc_html__( 'Vigencia:', 'electrocam-crm' ) . ' ' . esc_html( $valid_until );

			if ( 'sent' === $status ) {
				$output .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ec-quotation-action-form">';
				$output .= '<input type="hidden" name="action" value="ec_client_update_quotation_status">';
				$output .= '<input type="hidden" name="quotation_id" value="' . esc_attr( $quotation->ID ) . '">';
				$output .= wp_nonce_field( 'ec_client_update_quotation_' . $quotation->ID, 'ec_client_update_quotation_nonce', true, false );
				$output .= '<button type="submit" name="new_status" value="approved">' . esc_html__( 'Aprobar', 'electrocam-crm' ) . '</button> ';
				$output .= '<button type="submit" name="new_status" value="rejected">' . esc_html__( 'Rechazar', 'electrocam-crm' ) . '</button>';
				$output .= '</form>';
			}

			$output .= '</li>';
		}

		$output .= '</ul>';

		if ( ! $has_results ) {
			return $message . '<p>' . esc_html__( 'No hay cotizaciones para el filtro seleccionado.', 'electrocam-crm' ) . '</p>';
		}

		return $output;
	}

	/**
	 * Renderiza listado de citas para cliente autenticado.
	 *
	 * @return string
	 */
	public function render_client_appointments_shortcode() {
		$current_user_id = get_current_user_id();

		if ( ! $current_user_id ) {
			return '<p>' . esc_html__( 'Debes iniciar sesión para ver tus citas.', 'electrocam-crm' ) . '</p>';
		}

		$message = $this->get_reschedule_feedback_message();

		$appointments = get_posts(
			array(
				'post_type'      => 'appointment',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'   => 'ec_client_id',
						'value' => $current_user_id,
					),
				),
			)
		);

		if ( empty( $appointments ) ) {
			return $message . '<p>' . esc_html__( 'No tienes citas registradas.', 'electrocam-crm' ) . '</p>';
		}

		$output = $message;
		$output .= '<ul class="ec-client-list ec-client-appointments">';

		foreach ( $appointments as $appointment ) {
			$date = get_post_meta( $appointment->ID, 'ec_appointment_date', true );
			$time = get_post_meta( $appointment->ID, 'ec_appointment_time', true );
			$status = get_post_meta( $appointment->ID, 'ec_status', true );
			$output .= '<li><strong>' . esc_html( $appointment->post_title ) . '</strong> - ' . esc_html( $date ) . ' ' . esc_html( $time ) . ' - ' . esc_html( $status );
			if ( 'completed' !== $status && 'cancelled' !== $status ) {
				$output .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ec-reschedule-form">';
				$output .= '<input type="hidden" name="action" value="ec_client_reschedule_appointment">';
				$output .= '<input type="hidden" name="appointment_id" value="' . esc_attr( $appointment->ID ) . '">';
				$output .= wp_nonce_field( 'ec_client_reschedule_' . $appointment->ID, 'ec_client_reschedule_nonce', true, false );
				$output .= '<label>' . esc_html__( 'Nueva fecha', 'electrocam-crm' ) . ' <input type="date" name="new_date" value="' . esc_attr( $date ) . '" required></label> ';
				$output .= '<label>' . esc_html__( 'Nueva hora', 'electrocam-crm' ) . ' <input type="time" name="new_time" value="' . esc_attr( $time ) . '" required></label> ';
				$output .= '<button type="submit">' . esc_html__( 'Solicitar cambio', 'electrocam-crm' ) . '</button>';
				$output .= '</form>';
			}
			$output .= $this->render_frontend_reschedule_history_html( $appointment->ID, 3 );
			$output .= '</li>';
		}

		$output .= '</ul>';

		return $output;
	}


	/**
	 * Renderiza órdenes asignadas al operario autenticado.
	 *
	 * @return string
	 */
	public function render_operator_orders_shortcode() {
		$current_user_id = get_current_user_id();

		if ( ! $current_user_id ) {
			return '<p>' . esc_html__( 'Debes iniciar sesión para ver tus órdenes asignadas.', 'electrocam-crm' ) . '</p>';
		}

		if ( ! current_user_can( 'edit_ec_service_orders' ) ) {
			return '<p>' . esc_html__( 'No tienes permisos de operario para ver esta información.', 'electrocam-crm' ) . '</p>';
		}

		$orders = get_posts(
			array(
				'post_type'      => 'service_order',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'   => 'ec_operator_id',
						'value' => $current_user_id,
					),
				),
			)
		);

		if ( empty( $orders ) ) {
			return '<p>' . esc_html__( 'No tienes órdenes asignadas.', 'electrocam-crm' ) . '</p>';
		}

		$output = '<ul class="ec-operator-list ec-operator-orders">';
		foreach ( $orders as $order ) {
			$order_number = get_post_meta( $order->ID, 'ec_order_number', true );
			$status = get_post_meta( $order->ID, 'ec_status', true );
			$service_date = get_post_meta( $order->ID, 'ec_service_date', true );
			$output .= '<li><strong>' . esc_html( $order_number ? $order_number : $order->post_title ) . '</strong> - ' . esc_html( $service_date ) . ' - ' . esc_html( $status ) . '</li>';
		}
		$output .= '</ul>';

		return $output;
	}


	/**
	 * Renderiza cotizaciones asignadas al operario autenticado.
	 *
	 * @return string
	 */
	public function render_operator_quotations_shortcode() {
		$current_user_id = get_current_user_id();

		if ( ! $current_user_id ) {
			return '<p>' . esc_html__( 'Debes iniciar sesión para ver tus cotizaciones asignadas.', 'electrocam-crm' ) . '</p>';
		}

		if ( ! current_user_can( 'edit_ec_quotations' ) ) {
			return '<p>' . esc_html__( 'No tienes permisos de operario para ver esta información.', 'electrocam-crm' ) . '</p>';
		}

		$quotations = get_posts(
			array(
				'post_type'      => 'quotation',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'   => 'ec_operator_id',
						'value' => $current_user_id,
					),
				),
			)
		);

		if ( empty( $quotations ) ) {
			return '<p>' . esc_html__( 'No tienes cotizaciones asignadas.', 'electrocam-crm' ) . '</p>';
		}

		$output = '<ul class="ec-operator-list ec-operator-quotations">';
		foreach ( $quotations as $quotation ) {
			$status = get_post_meta( $quotation->ID, 'ec_quotation_status', true );
			$total = (float) get_post_meta( $quotation->ID, 'ec_total', true );
			$valid_until = get_post_meta( $quotation->ID, 'ec_valid_until', true );

			$output .= '<li><strong>' . esc_html( $quotation->post_title ) . '</strong><br>';
			$output .= esc_html__( 'Estado:', 'electrocam-crm' ) . ' ' . esc_html( $this->get_quotation_status_label( $status ? $status : 'draft' ) ) . ' · ';
			$output .= esc_html__( 'Total:', 'electrocam-crm' ) . ' $' . esc_html( number_format_i18n( $total, 2 ) ) . ' · ';
			$output .= esc_html__( 'Vigencia:', 'electrocam-crm' ) . ' ' . esc_html( $valid_until ) . '</li>';
		}
		$output .= '</ul>';

		return $output;
	}

	/**
	 * Renderiza citas vinculadas al operario autenticado.
	 *
	 * @return string
	 */
	public function render_operator_appointments_shortcode() {
		$current_user_id = get_current_user_id();

		if ( ! $current_user_id ) {
			return '<p>' . esc_html__( 'Debes iniciar sesión para ver tus citas asignadas.', 'electrocam-crm' ) . '</p>';
		}

		if ( ! current_user_can( 'edit_ec_appointments' ) ) {
			return '<p>' . esc_html__( 'No tienes permisos de operario para ver esta información.', 'electrocam-crm' ) . '</p>';
		}

		$message = $this->get_reschedule_feedback_message();

		$service_orders = get_posts(
			array(
				'post_type'      => 'service_order',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => 'ec_operator_id',
						'value' => $current_user_id,
					),
				),
			)
		);

		if ( empty( $service_orders ) ) {
			return $message . '<p>' . esc_html__( 'No tienes citas asignadas.', 'electrocam-crm' ) . '</p>';
		}

		$appointments = get_posts(
			array(
				'post_type'      => 'appointment',
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'     => 'ec_service_order_id',
						'value'   => $service_orders,
						'compare' => 'IN',
					),
				),
			)
		);

		if ( empty( $appointments ) ) {
			return $message . '<p>' . esc_html__( 'No tienes citas asignadas.', 'electrocam-crm' ) . '</p>';
		}

		$output = $message;
		$output .= '<ul class="ec-operator-list ec-operator-appointments">';
		foreach ( $appointments as $appointment ) {
			$date = get_post_meta( $appointment->ID, 'ec_appointment_date', true );
			$time = get_post_meta( $appointment->ID, 'ec_appointment_time', true );
			$status = get_post_meta( $appointment->ID, 'ec_status', true );
			$output .= '<li><strong>' . esc_html( $appointment->post_title ) . '</strong> - ' . esc_html( $date ) . ' ' . esc_html( $time ) . ' - ' . esc_html( $status );
			if ( 'completed' !== $status && 'cancelled' !== $status ) {
				$output .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ec-reschedule-form">';
				$output .= '<input type="hidden" name="action" value="ec_operator_reschedule_appointment">';
				$output .= '<input type="hidden" name="appointment_id" value="' . esc_attr( $appointment->ID ) . '">';
				$output .= wp_nonce_field( 'ec_operator_reschedule_' . $appointment->ID, 'ec_operator_reschedule_nonce', true, false );
				$output .= '<label>' . esc_html__( 'Nueva fecha', 'electrocam-crm' ) . ' <input type="date" name="new_date" value="' . esc_attr( $date ) . '" required></label> ';
				$output .= '<label>' . esc_html__( 'Nueva hora', 'electrocam-crm' ) . ' <input type="time" name="new_time" value="' . esc_attr( $time ) . '" required></label> ';
				$output .= '<button type="submit">' . esc_html__( 'Reprogramar', 'electrocam-crm' ) . '</button>';
				$output .= '</form>';
			}
			$output .= $this->render_frontend_reschedule_history_html( $appointment->ID, 3 );
			$output .= '</li>';
		}
		$output .= '</ul>';

		return $output;
	}



	/**
	 * Procesa actualización de estado de cotización desde portal cliente.
	 *
	 * @return void
	 */
	public function handle_client_update_quotation_status() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
			exit;
		}

		$quotation_id = isset( $_POST['quotation_id'] ) ? absint( wp_unslash( $_POST['quotation_id'] ) ) : 0;
		$new_status = isset( $_POST['new_status'] ) ? sanitize_key( wp_unslash( $_POST['new_status'] ) ) : '';
		$nonce = isset( $_POST['ec_client_update_quotation_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_client_update_quotation_nonce'] ) ) : '';
		$redirect_url = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( ! $quotation_id || ! wp_verify_nonce( $nonce, 'ec_client_update_quotation_' . $quotation_id ) ) {
			wp_die( esc_html__( 'Solicitud inválida.', 'electrocam-crm' ) );
		}

		$current_user_id = get_current_user_id();
		$client_id = (int) get_post_meta( $quotation_id, 'ec_client_id', true );
		$current_status = (string) get_post_meta( $quotation_id, 'ec_quotation_status', true );

		if ( $current_user_id !== $client_id ) {
			wp_die( esc_html__( 'No tienes permisos para modificar esta cotización.', 'electrocam-crm' ) );
		}

		if ( 'sent' !== $current_status ) {
			wp_safe_redirect( add_query_arg( 'ec_quotation_error', 'invalid_transition', $redirect_url ) );
			exit;
		}

		if ( ! in_array( $new_status, array( 'approved', 'rejected' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'ec_quotation_error', 'invalid_transition', $redirect_url ) );
			exit;
		}

		update_post_meta( $quotation_id, 'ec_quotation_status', $new_status );
		update_post_meta( $quotation_id, 'ec_client_status_updated_at', gmdate( 'Y-m-d H:i:s' ) );

		wp_safe_redirect( add_query_arg( 'ec_quotation_updated', '1', $redirect_url ) );
		exit;
	}

	/**
	 * Construye mensaje de feedback para acciones de cotización.
	 *
	 * @return string
	 */
	private function get_quotation_feedback_message() {
		if ( isset( $_GET['ec_quotation_updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['ec_quotation_updated'] ) ) ) {
			return '<p class="ec-success-message">' . esc_html__( 'La cotización fue actualizada correctamente.', 'electrocam-crm' ) . '</p>';
		}

		if ( isset( $_GET['ec_quotation_error'] ) ) {
			$error = sanitize_text_field( wp_unslash( $_GET['ec_quotation_error'] ) );
			if ( 'invalid_transition' === $error ) {
				return '<p class="ec-error-message">' . esc_html__( 'No se pudo cambiar el estado de la cotización.', 'electrocam-crm' ) . '</p>';
			}
		}

		return '';
	}

	/**
	 * Procesa solicitud de reprogramación enviada por operario.
	 *
	 * @return void
	 */
	public function handle_operator_reschedule_appointment() {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_ec_appointments' ) ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
			exit;
		}

		$appointment_id = isset( $_POST['appointment_id'] ) ? absint( wp_unslash( $_POST['appointment_id'] ) ) : 0;
		$new_date = isset( $_POST['new_date'] ) ? sanitize_text_field( wp_unslash( $_POST['new_date'] ) ) : '';
		$new_time = isset( $_POST['new_time'] ) ? sanitize_text_field( wp_unslash( $_POST['new_time'] ) ) : '';
		$nonce = isset( $_POST['ec_operator_reschedule_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_operator_reschedule_nonce'] ) ) : '';

		if ( ! $appointment_id || ! wp_verify_nonce( $nonce, 'ec_operator_reschedule_' . $appointment_id ) ) {
			wp_die( esc_html__( 'Solicitud inválida.', 'electrocam-crm' ) );
		}

		$service_order_id = (int) get_post_meta( $appointment_id, 'ec_service_order_id', true );
		$current_user_id = get_current_user_id();
		$operator_id = $service_order_id ? (int) get_post_meta( $service_order_id, 'ec_operator_id', true ) : 0;

		if ( $current_user_id !== $operator_id && ! current_user_can( 'edit_others_ec_appointments' ) ) {
			wp_die( esc_html__( 'No tienes permisos para modificar esta cita.', 'electrocam-crm' ) );
		}

		$this->process_appointment_reschedule( $appointment_id, $new_date, $new_time, $current_user_id );
	}

	/**
	 * Procesa solicitud de reprogramación enviada por cliente.
	 *
	 * @return void
	 */
	public function handle_client_reschedule_appointment() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/' ) );
			exit;
		}

		$appointment_id = isset( $_POST['appointment_id'] ) ? absint( wp_unslash( $_POST['appointment_id'] ) ) : 0;
		$new_date = isset( $_POST['new_date'] ) ? sanitize_text_field( wp_unslash( $_POST['new_date'] ) ) : '';
		$new_time = isset( $_POST['new_time'] ) ? sanitize_text_field( wp_unslash( $_POST['new_time'] ) ) : '';
		$nonce = isset( $_POST['ec_client_reschedule_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ec_client_reschedule_nonce'] ) ) : '';

		if ( ! $appointment_id || ! wp_verify_nonce( $nonce, 'ec_client_reschedule_' . $appointment_id ) ) {
			wp_die( esc_html__( 'Solicitud inválida.', 'electrocam-crm' ) );
		}

		$current_user_id = get_current_user_id();
		$appointment_client_id = (int) get_post_meta( $appointment_id, 'ec_client_id', true );

		if ( $current_user_id !== $appointment_client_id ) {
			wp_die( esc_html__( 'No tienes permisos para modificar esta cita.', 'electrocam-crm' ) );
		}

		$this->process_appointment_reschedule( $appointment_id, $new_date, $new_time, $current_user_id );
	}

	/**
	 * Ejecuta el flujo de reprogramación de cita compartido.
	 *
	 * @param int    $appointment_id ID cita.
	 * @param string $new_date       Nueva fecha.
	 * @param string $new_time       Nueva hora.
	 * @param int    $current_user_id Usuario que ejecuta.
	 * @return void
	 */
	private function process_appointment_reschedule( $appointment_id, $new_date, $new_time, $current_user_id ) {
		$redirect_url = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( ! $this->is_valid_date_time( $new_date, $new_time ) ) {
			wp_safe_redirect( add_query_arg( 'ec_appointment_error', 'invalid_datetime', $redirect_url ) );
			exit;
		}

		if ( ! $this->is_appointment_slot_available( $new_date, $new_time, $appointment_id ) ) {
			wp_safe_redirect( add_query_arg( 'ec_appointment_error', 'slot_unavailable', $redirect_url ) );
			exit;
		}

		$old_date = (string) get_post_meta( $appointment_id, 'ec_appointment_date', true );
		$old_time = (string) get_post_meta( $appointment_id, 'ec_appointment_time', true );

		update_post_meta( $appointment_id, 'ec_appointment_date', $new_date );
		update_post_meta( $appointment_id, 'ec_appointment_time', $new_time );
		update_post_meta( $appointment_id, 'ec_status', 'scheduled' );
		$this->append_appointment_reschedule_history( $appointment_id, $old_date, $old_time, $new_date, $new_time, $current_user_id );
		$this->send_appointment_reschedule_email( $appointment_id, $new_date, $new_time );

		wp_safe_redirect( add_query_arg( 'ec_appointment_updated', '1', $redirect_url ) );
		exit;
	}


	/**
	 * Construye mensaje de feedback para reprogramaciones.
	 *
	 * @return string
	 */
	private function get_reschedule_feedback_message() {
		if ( isset( $_GET['ec_appointment_updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['ec_appointment_updated'] ) ) ) {
			return '<p class="ec-success-message">' . esc_html__( 'La cita fue reprogramada exitosamente.', 'electrocam-crm' ) . '</p>';
		}

		if ( isset( $_GET['ec_appointment_error'] ) ) {
			$error = sanitize_text_field( wp_unslash( $_GET['ec_appointment_error'] ) );
			if ( 'slot_unavailable' === $error ) {
				return '<p class="ec-error-message">' . esc_html__( 'El horario seleccionado ya está ocupado. Intenta otro.', 'electrocam-crm' ) . '</p>';
			}
			if ( 'invalid_datetime' === $error ) {
				return '<p class="ec-error-message">' . esc_html__( 'La fecha u hora enviada no es válida.', 'electrocam-crm' ) . '</p>';
			}
		}

		return '';
	}

	/**
	 * Valida formato de fecha y hora de cita.
	 *
	 * @param string $date Fecha.
	 * @param string $time Hora.
	 * @return bool
	 */
	private function is_valid_date_time( $date, $time ) {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			return false;
		}

		$timestamp = strtotime( $date . ' ' . $time . ':00' );
		return false !== $timestamp;
	}


	/**
	 * Renderiza historial breve para frontend.
	 *
	 * @param int $appointment_id ID de cita.
	 * @param int $limit          Máximo de registros.
	 * @return string
	 */
	private function render_frontend_reschedule_history_html( $appointment_id, $limit = 3 ) {
		$history = get_post_meta( $appointment_id, 'ec_reschedule_history', true );

		if ( ! is_array( $history ) || empty( $history ) ) {
			return '';
		}

		$history = array_slice( array_reverse( $history ), 0, absint( $limit ) );
		$output = '<details class="ec-reschedule-history"><summary>' . esc_html__( 'Ver historial de cambios', 'electrocam-crm' ) . '</summary><ul>';
		foreach ( $history as $item ) {
			$from = isset( $item['from'] ) ? $item['from'] : '';
			$to = isset( $item['to'] ) ? $item['to'] : '';
			$changed_at = isset( $item['changed_at'] ) ? $item['changed_at'] : '';
			$output .= '<li>' . esc_html( sprintf( __( 'De %1$s a %2$s (%3$s)', 'electrocam-crm' ), $from, $to, $changed_at ) ) . '</li>';
		}
		$output .= '</ul></details>';

		return $output;
	}

	/**
	 * Envía correo al cliente y soporte cuando una cita se reprograma.
	 *
	 * @param int    $appointment_id ID de cita.
	 * @param string $new_date       Nueva fecha.
	 * @param string $new_time       Nueva hora.
	 * @return void
	 */
	private function send_appointment_reschedule_email( $appointment_id, $new_date, $new_time ) {
		$client_id = (int) get_post_meta( $appointment_id, 'ec_client_id', true );
		$client = $client_id ? get_user_by( 'id', $client_id ) : false;
		$recipients = array();

		if ( $client && ! empty( $client->user_email ) ) {
			$recipients[] = $client->user_email;
		}

		$support_email = $this->get_support_email();
		if ( ! empty( $support_email ) ) {
			$recipients[] = $support_email;
		}

		$cc_email = $this->get_notification_cc_email();
		if ( ! empty( $cc_email ) ) {
			$recipients[] = $cc_email;
		}

		$recipients = array_unique( array_filter( $recipients ) );

		if ( empty( $recipients ) ) {
			return;
		}

		$subject = __( 'Cita reprogramada - Electrocam', 'electrocam-crm' );
		$message = sprintf(
			/* translators: 1: appointment ID, 2: date, 3: time */
			__( '<p>La cita <strong>#%1$d</strong> fue reprogramada para <strong>%2$s</strong> a las <strong>%3$s</strong>.</p>', 'electrocam-crm' ),
			$appointment_id,
			$new_date,
			$new_time
		);
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $recipients, $subject, $message, $headers );
		if ( $sent ) {
			$sent_at = gmdate( 'Y-m-d H:i:s' );
			update_post_meta( $appointment_id, 'ec_last_reschedule_email_sent_at', $sent_at );
			$this->append_email_audit( $appointment_id, 'appointment_rescheduled', $recipients, $subject, $sent_at );
		}
	}


	/**
	 * Obtiene el correo de soporte configurado.
	 *
	 * @return string
	 */
	private function get_support_email() {
		$support_email = get_option( 'ec_support_email', 'servicioalcliente@electrocam.com' );
		$support_email = is_string( $support_email ) ? sanitize_email( $support_email ) : '';

		if ( empty( $support_email ) ) {
			$support_email = 'servicioalcliente@electrocam.com';
		}

		return $support_email;
	}

	/**
	 * Obtiene correo opcional de copia (CC) para notificaciones.
	 *
	 * @return string
	 */
	private function get_notification_cc_email() {
		$cc_email = get_option( 'ec_notification_cc_email', '' );
		$cc_email = is_string( $cc_email ) ? sanitize_email( $cc_email ) : '';

		return $cc_email;
	}

	/**
	 * Registra auditoría de envío de correo en el post asociado.
	 *
	 * @param int    $post_id     ID del post.
	 * @param string $event       Evento de correo.
	 * @param array  $recipients  Destinatarios.
	 * @param string $subject     Asunto.
	 * @param string $sent_at     Fecha/hora envío.
	 * @return void
	 */
	private function append_email_audit( $post_id, $event, $recipients, $subject, $sent_at ) {
		$audit = get_post_meta( $post_id, 'ec_email_audit', true );
		if ( ! is_array( $audit ) ) {
			$audit = array();
		}

		$audit[] = array(
			'event'      => sanitize_key( $event ),
			'recipients' => array_values( array_map( 'sanitize_email', (array) $recipients ) ),
			'subject'    => sanitize_text_field( $subject ),
			'sent_at'    => sanitize_text_field( $sent_at ),
		);

		$limit = (int) get_option( 'ec_audit_retention_limit', 50 );
		if ( $limit > 0 && count( $audit ) > $limit ) {
			$audit = array_slice( $audit, -$limit );
		}

		update_post_meta( $post_id, 'ec_email_audit', $audit );
	}

	/**
	 * Agrega una entrada al historial de reprogramaciones.
	 *
	 * @param int    $appointment_id ID de cita.
	 * @param string $old_date       Fecha anterior.
	 * @param string $old_time       Hora anterior.
	 * @param string $new_date       Fecha nueva.
	 * @param string $new_time       Hora nueva.
	 * @param int    $user_id        Usuario que reprograma.
	 * @return void
	 */
	private function append_appointment_reschedule_history( $appointment_id, $old_date, $old_time, $new_date, $new_time, $user_id ) {
		$history = get_post_meta( $appointment_id, 'ec_reschedule_history', true );

		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$history[] = array(
			'from'       => trim( $old_date . ' ' . $old_time ),
			'to'         => trim( $new_date . ' ' . $new_time ),
			'user_id'    => absint( $user_id ),
			'changed_at' => gmdate( 'Y-m-d H:i:s' ),
		);

		$limit = (int) get_option( 'ec_audit_retention_limit', 50 );
		if ( $limit > 0 && count( $history ) > $limit ) {
			$history = array_slice( $history, -$limit );
		}

		update_post_meta( $appointment_id, 'ec_reschedule_history', $history );
	}

	/**
	 * Notifica comentarios nuevos en órdenes de servicio.
	 *
	 * @param int        $comment_id       ID del comentario.
	 * @param int|string $comment_approved Estado de aprobación.
	 * @param array      $commentdata      Datos de comentario.
	 * @return void
	 */
	public function notify_service_order_comment( $comment_id, $comment_approved, $commentdata ) {
		if ( 1 !== (int) $comment_approved ) {
			return;
		}

		$post_id = isset( $commentdata['comment_post_ID'] ) ? absint( $commentdata['comment_post_ID'] ) : 0;
		if ( ! $post_id || 'service_order' !== get_post_type( $post_id ) ) {
			return;
		}

		$client_id = (int) get_post_meta( $post_id, 'ec_client_id', true );
		$operator_id = (int) get_post_meta( $post_id, 'ec_operator_id', true );
		$recipients = array();

		$client = $client_id ? get_user_by( 'id', $client_id ) : false;
		$operator = $operator_id ? get_user_by( 'id', $operator_id ) : false;

		if ( $client && ! empty( $client->user_email ) ) {
			$recipients[] = $client->user_email;
		}
		if ( $operator && ! empty( $operator->user_email ) ) {
			$recipients[] = $operator->user_email;
		}

		$support_email = $this->get_support_email();
		if ( ! empty( $support_email ) ) {
			$recipients[] = $support_email;
		}
		$admin_email = get_option( 'admin_email' );
		if ( ! empty( $admin_email ) ) {
			$recipients[] = $admin_email;
		}

		$cc_email = $this->get_notification_cc_email();
		if ( ! empty( $cc_email ) ) {
			$recipients[] = $cc_email;
		}

		$recipients = array_unique( array_filter( $recipients ) );
		if ( empty( $recipients ) ) {
			return;
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		$subject = __( 'Nuevo comentario en orden de servicio', 'electrocam-crm' );
		$message = sprintf(
			/* translators: 1: order title, 2: comment content */
			__( "Se registró un nuevo comentario en la orden '%1$s':

%2$s", 'electrocam-crm' ),
			get_the_title( $post_id ),
			$comment->comment_content
		);

		$sent = wp_mail( $recipients, $subject, $message );
		if ( $sent ) {
			$this->append_email_audit( $post_id, 'service_order_comment', $recipients, $subject, gmdate( 'Y-m-d H:i:s' ) );
		}
	}

	/**
	 * Asigna consecutivo EC-000X si no existe.
	 *
	 * @param int $post_id ID de orden.
	 * @return void
	 */
	private function ensure_service_order_number( $post_id ) {
		$current = get_post_meta( $post_id, 'ec_order_number', true );

		if ( ! empty( $current ) ) {
			return;
		}

		$counter = (int) get_option( 'ec_service_order_counter', 0 );
		$counter++;
		update_option( 'ec_service_order_counter', $counter );

		update_post_meta( $post_id, 'ec_order_number', sprintf( 'EC-%04d', $counter ) );
	}

	/**
	 * Genera una cita automática de control si aplica.
	 *
	 * @param int     $post_id           ID de orden.
	 * @param WP_Post $post              Post.
	 * @param string  $service_date      Fecha base.
	 * @param int     $next_control_days Días al próximo control.
	 * @param int     $client_id         Cliente.
	 * @param string  $modality          Modalidad.
	 * @return void
	 */
	private function maybe_generate_control_appointment( $post_id, $post, $service_date, $next_control_days, $client_id, $modality ) {
		if ( empty( $service_date ) || ! in_array( $next_control_days, array( 30, 60, 90 ), true ) ) {
			return;
		}

		$control_date = gmdate( 'Y-m-d', strtotime( $service_date . ' +' . $next_control_days . ' days' ) );
		$control_time = '09:00';
		update_post_meta( $post_id, 'ec_control_date', $control_date );

		$existing = get_posts(
			array(
				'post_type'      => 'appointment',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'   => 'ec_service_order_id',
						'value' => $post_id,
					),
				),
			)
		);

		if ( ! empty( $existing ) ) {
			return;
		}

		if ( ! $this->is_appointment_slot_available( $control_date, $control_time ) ) {
			return;
		}

		$appointment_id = wp_insert_post(
			array(
				'post_type'   => 'appointment',
				'post_status' => 'publish',
				'post_title'  => sprintf( 'Control %s - %s', $post->post_title, $control_date ),
			)
		);

		if ( is_wp_error( $appointment_id ) ) {
			return;
		}

		update_post_meta( $appointment_id, 'ec_service_order_id', $post_id );
		update_post_meta( $appointment_id, 'ec_client_id', $client_id );
		update_post_meta( $appointment_id, 'ec_appointment_date', $control_date );
		update_post_meta( $appointment_id, 'ec_appointment_time', $control_time );
		update_post_meta( $appointment_id, 'ec_modality', $modality );
		update_post_meta( $appointment_id, 'ec_status', 'scheduled' );
	}
}
