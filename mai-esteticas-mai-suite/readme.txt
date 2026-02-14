=== Esteticas Mai Suite ===
Contributors: mai
Tags: esteticas-mai, crm, erp, appointments
Requires at least: 6.2
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later

Sistema híbrido CRM + ERP ligero para clínicas estéticas en WordPress sin WooCommerce.

== Descripción ==

Incluye MVP técnico con:
- Roles RBAC (cliente, esteticista, coordinador, admin sistema).
- Tablas custom `wp_mai_*`.
- Endpoints REST `/wp-json/mai/v1/...`.
- Portales por shortcode `[mai_client_portal]` y `[mai_staff_portal]`.
- Estados de cita y auditoría.
- Portal administrativo WP para crear sedes, crear esteticistas y asignar administradora por sede.
- Precarga de sedes y catálogo de servicios desde interfaz admin.
- Campos de sede listos para editar luego: nombre, dirección y teléfono.

== Instalación ==

1. Copiar carpeta `mai-esteticas-mai-suite` en `wp-content/plugins/`.
2. Activar plugin desde WordPress.
3. Ir a **Estéticas Mai** en el menú de admin.
4. (Opcional) Usar **Cargar datos iniciales** para precargar sedes y servicios base.
5. Crear/ajustar sedes con nombre, dirección y teléfono, y asignar administradora por sede.
6. Crear esteticistas y asignarlas a una sede.
7. Crear páginas con shortcodes:
   - `[mai_client_portal]`
   - `[mai_staff_portal]`

== Notas ==

Este entregable es base MVP lista para extender con pasarela real (Wompi/MercadoPago/PayU), PDF robusto y reglas avanzadas de agenda.
