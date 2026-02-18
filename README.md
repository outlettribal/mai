# Electrocam ERP/CRM Plugin (WordPress)

Base inicial del proyecto **Electrocam** para construir un plugin ERP/CRM en WordPress orientado a:

- Gestión de órdenes de servicio.
- Gestión de cotizaciones.
- Gestión de inventario (bodega).
- Agenda y control de citas.
- Portales para administración, operarios y clientes.

## Estado actual del desarrollo

En esta iteración el plugin ya incluye una **primera capa funcional**:

1. Bootstrap del plugin, constantes y hooks de activación/desactivación.
2. Registro de CPT:
   - `service_order`
   - `quotation`
   - `inventory_item`
   - `appointment`
3. Registro de taxonomía:
   - `inventory_category` para inventario.
4. Registro de roles base en activación:
   - `ec_admin`
   - `ec_operator`
   - `ec_client`
5. Metabox de datos de orden de servicio con guardado seguro (nonce + sanitización).
6. Generación de consecutivo automático de órdenes (`EC-0001`, `EC-0002`, ...).
7. Creación automática de cita de control cuando se seleccionan 30/60/90 días.
8. Metabox de cotizaciones con campos de fechas, subtotal, IVA, total y términos.
9. Metabox de inventario con SKU, stock y precio unitario.
10. Persistencia segura de cotizaciones e inventario con validación y sanitización.
11. Metabox de citas con fecha/hora, modalidad y estado.
12. Validación anti-solapamiento de citas en edición/creación (misma fecha y hora).
13. Capacidades granulares por módulo (órdenes, cotizaciones, inventario y citas) para `ec_admin` y `ec_operator`.
14. Shortcodes iniciales de portal cliente para listar órdenes, cotizaciones y citas filtradas por usuario autenticado (`[ec_client_orders]`, `[ec_client_quotations]`, `[ec_client_appointments]`).
15. Reprogramación básica de citas desde frontend de cliente con nonce y validación anti-solapamiento.
16. Shortcodes iniciales para operarios (`[ec_operator_orders]`, `[ec_operator_appointments]`) con información asignada.
17. Notificación por correo en reprogramaciones y en nuevos comentarios de órdenes de servicio.
18. Historial de reprogramaciones de citas guardado en metadatos y visible en administración.
19. Reprogramación de citas también disponible para operarios desde frontend, con nonce, permisos y trazabilidad.
20. Validación de formato fecha/hora y feedback unificado para reprogramaciones en portales cliente/operario.
21. Flujo de reprogramación refactorizado en un método compartido para cliente y operario.
22. Historial breve visible en frontend (cliente/operario) y auditoría de envío de correo de reprogramación.
23. Correo de soporte configurable (`ec_support_email`) con fallback a `servicioalcliente@electrocam.com` y auditoría de envíos por post.
24. Pantalla de ajustes en wp-admin para editar correo de soporte sin tocar base de datos.
25. Campo opcional de correo secundario (CC) para notificaciones y auditoría de envíos.
26. Validación del correo CC en ajustes con mensajes de error visibles en wp-admin.
27. Validación del correo principal de soporte y visualización de auditoría de correos en metabox de citas.
28. Límite configurable de retención de auditoría (10-500) aplicado a historial de correos y reprogramaciones.

## Estructura

```text
electrocam-crm/
├── electrocam-crm.php
└── includes/
    └── class-ec-core.php
```

## Próximos pasos

### Fase siguiente (inmediata)
- Reprogramación de citas en frontend para clientes/operarios.
- Acciones de cliente sobre citas (solicitud de cambio, comentarios y trazabilidad).
- Notificaciones por correo para recordatorios y cambios de cita.

### Fase PDF + correo
- Integración de TCPDF para orden/cotización.
- Envío por `wp_mail()` con adjunto a cliente y soporte.
- Registro de trazabilidad de envío.

### Fase paneles frontend
- Shortcodes o bloques para panel de operario y panel de cliente.
- Listados filtrados por usuario autenticado.
- Conversación/comentarios por orden con notificaciones.


## Avance estimado del proyecto

- Base técnica del plugin (bootstrap, CPT, taxonomía, roles): **95%**
- Módulo de órdenes de servicio (metabox, guardado, consecutivo, cita de control): **70%**
- Módulo de cotizaciones (metabox + totales básicos): **45%**
- Módulo de inventario (metabox + persistencia): **40%**
- Módulo de agenda/citas (metabox + anti-solapamiento + reprogramación frontend básica): **90%**
- Portal cliente frontend (shortcodes listados + reprogramación inicial): **68%**
- PDF y correo automático: **10%**
- Conversación/comentarios y notificaciones: **70%**
