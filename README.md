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
29. Ítems de cotización estructurados (nombre, descripción, cantidad y valor unitario) con recálculo automático de subtotal/total e IVA al guardar.
30. Mejor visibilidad de cotizaciones en portal cliente mostrando cantidad de ítems, subtotal, IVA y total.
31. Flujo básico de estado de cotización (borrador, enviada, aprobada, rechazada) con detección de vencimiento en portal cliente según vigencia.
32. Shortcode de cotizaciones para operario (`[ec_operator_quotations]`) y filtros básicos en portal cliente por estado (`status`) y vencidas (`show_expired`).
33. Acción de cliente sobre cotizaciones enviadas (aprobar/rechazar) con nonce y validación de transición de estado.
34. Notificación por correo y auditoría cuando cliente cambia estado de cotización (aprobada/rechazada).
35. Historial de cambios de estado de cotización (`ec_quotation_status_history`) visible en admin con último cambio por cliente.
36. Trazabilidad de cambios de estado también desde edición en wp-admin (no solo desde portal cliente).
37. Captura de comentario opcional del cliente al aprobar/rechazar cotización y envío en notificación por correo.
38. Visualización de notas de cliente en historial de estados (admin) y en listado de cotizaciones del operario.
39. Filtros por estado y vencimiento también en shortcode de cotizaciones de operario (`[ec_operator_quotations status="..."]`, `show_expired`).
40. Parámetro `limit` en shortcodes de cotizaciones de cliente y operario para controlar cantidad de resultados (1-200).
41. Parámetro `page` en shortcodes de cotizaciones de cliente y operario para paginación simple por offset.
42. Navegación básica Anterior/Siguiente por query args (`ecq_client_page`, `ecq_operator_page`) en listados de cotizaciones.
43. Paginación cliente/operario ajustada para detectar correctamente página siguiente (`limit + 1`).
44. Navegación de paginación conserva filtros activos (`status`, `show_expired`, `limit`) en cliente y operario.
45. Paginación básica (limit/page + Anterior/Siguiente) añadida también a shortcodes de órdenes de cliente y operario.
46. Enlaces de paginación de órdenes ahora conservan `limit` al navegar entre páginas (cliente/operario).
47. Filtro opcional `status` añadido a shortcodes de órdenes (`[ec_client_orders]`, `[ec_operator_orders]`) y preservado en paginación.
48. Shortcodes de citas de cliente/operario ahora incluyen `status`, `limit` y `page` con paginación simple y preservación de filtros en navegación (`eca_client_page`, `eca_operator_page`).
49. Shortcodes de citas de cliente/operario ahora soportan `show_past` (`yes/no`) para ocultar citas pasadas y conservar el filtro en la paginación.
50. Los filtros/paginación de citas también pueden leerse desde query args (`eca_status`, `eca_limit`, `eca_show_past`) para compartir enlaces con el estado del listado.
51. Filtro por rango de fechas en citas (`eca_from`, `eca_to`) para cliente/operario, preservado durante la paginación.
52. Normalización de rango de fechas en citas: si `eca_from` > `eca_to`, el sistema invierte automáticamente el rango para evitar filtros inválidos.
53. Atajo de rango en citas con `eca_range` (`next_7_days`/`next_30_days`) para construir filtros rápidos en cliente y operario.
54. Preset adicional `next_90_days` en `eca_range` para planificación trimestral de citas en cliente/operario.
55. Recordatorios automáticos de citas con WP-Cron (24h antes), reprogramables al cambiar fecha/hora y con auditoría de correo.

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

- Base técnica del plugin (bootstrap, CPT, taxonomía, roles): **97%**
- Módulo de órdenes de servicio (metabox + trazabilidad + filtros frontend): **75%**
- Módulo de cotizaciones (ítems + estados + aprobación + historial/auditoría): **99%**
- Módulo de inventario (metabox + persistencia): **40%**
- Módulo de agenda/citas (metabox + anti-solapamiento + reprogramación frontend básica): **100%**
- Portal cliente/frontend operativo (shortcodes + acciones + filtros + paginación): **99%**
- PDF y correo automático: **10%**
- Conversación/comentarios y notificaciones: **82%**
