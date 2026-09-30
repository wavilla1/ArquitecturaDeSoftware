# Estado de las tareas del MVP

**Fecha objetivo:** miércoles 23 de septiembre de 2026
**Revisión:** 30 de septiembre de 2026. La fecha original se conserva como referencia; no se afirma entrega retroactiva.

| # | Función | Responsable | Criterio mínimo de entrega | Estado |
|---|---|---|---|---|
| 1 | Ofertas y pujas | Will | Un usuario oferta por un NFT publicado, se conserva la mejor puja y se adjudica al cierre. | ✅ Completado |
| 2 | Panel de administración | Juan José | Un rol admin modera colecciones, suspende usuarios y consulta reportes. | ✅ Completado |
| 3 | Verificación de la cadena | José Luis | Una vista pública recalcula los hashes e informa si la cadena fue alterada. | ✅ Completado |
| 4 | Favoritos | José Luis | Un usuario marca y desmarca NFTs o colecciones y consulta su lista personal. | ✅ Completado |

## Funciones completadas al 30 de septiembre

La tarea 3 tiene una vista pública `/cadena`, recálculo SHA-256 y detección de enlaces, secuencias o contenido alterados. Un bloqueo común serializa la escritura de bloques. Es una cadena interna de auditoría, sin consenso externo; no detecta una reescritura total coherente o eliminación de los últimos bloques sin una copia de referencia.

La tarea 4 ofrece guardado y eliminación de favoritos para NFTs y colecciones, con una lista por usuario en `/favoritos`. Los endpoints son idempotentes y exigen sesión; las colecciones ocultas no aparecen en esa lista.

También se completaron registro/login/logout, imágenes persistentes, copias numeradas, protección de operaciones sobre colecciones ocultas y preparación para Google Cloud.

## Tarea 1 — Ofertas y pujas (implementada)

- Al publicar un NFT, el vendedor elige entre **venta directa** (comportamiento original) o **subasta**, con una duración de 1, 6, 24 o 72 horas.
- En una subasta, "Comprar ahora" desaparece: los demás usuarios solo pueden ofertar (`POST /publicaciones/{listing}/pujar`), siempre por encima de la mejor puja vigente (o del precio inicial si aún no hay pujas).
- El saldo del pujador se **reserva** al ofertar y se **libera automáticamente** si otro usuario lo supera, de modo que en todo momento solo hay una puja con fondos retenidos por subasta.
- Al cumplirse `closes_at`, la subasta se adjudica a la mejor puja vigente: se transfiere el NFT, se acredita el saldo al vendedor y se registra la venta en la cadena de bloques. Si nadie ofertó, la publicación expira y el NFT vuelve al inventario del dueño.
- El cierre ocurre de forma perezosa (al cargar `/` o `/perfil`) y también existe el comando `php artisan auctions:close`, programado cada minuto en `routes/console.php` para quien use `schedule:work`.
- Nuevas piezas: migración `create_bids_table`, modelo `Bid`, `App\Services\AuctionService` (lógica de negocio) y `App\Services\ChainService` (registro en la cadena, extraído del controlador). Pruebas en `tests/Feature/AuctionTest.php`.

## Tarea 2 — Panel de administración (implementada)

### Rol y acceso

- La tabla `users` gana `is_admin` y `suspended_at`. El seeder crea una cuarta cuenta de demostración, **Monoverse Admin** (`@admin`, sin saldo), que es la única con `is_admin = true`.
- El panel usa autenticación real. La cuenta admin inicia sesión con correo y contraseña; las cuentas normales reciben 403. El selector anterior se conserva únicamente como modo demo local opcional y está bloqueado en producción.
- `App\Services\DemoSessionService` centraliza la resolución del usuario activo, de modo que controladores y middlewares leen siempre la misma sesión.
- Middlewares: `EnsureIsAdmin` (alias `admin`) protege el panel; `EnsureUserIsActive` (alias `active`) bloquea las acciones de escritura del mercado para cuentas suspendidas.

### Reportes

`GET /admin` muestra métricas calculadas sobre las tablas existentes: usuarios y suspendidos, colecciones y ocultas, NFTs acuñados, publicaciones activas, subastas abiertas, ventas cerradas con ticket medio, volumen transado, bloques en cadena y **MONO en circulación** (saldo libre + saldo reservado por las pujas vigentes, útil para comprobar que las subastas no crean ni destruyen fondos). Debajo, el ranking de colecciones por volumen y los últimos bloques de la cadena.

### Moderación de colecciones

- `POST /admin/colecciones/{collection}/visibilidad` alterna entre `visible` y `hidden` (nueva columna `status` en `collections`).
- Una colección oculta desaparece del mercado público (`scopeVisible`) y deja de admitir pujas nuevas.
- Al ocultar se **retiran sus publicaciones de venta directa** (pasan a `cancelled` y el NFT vuelve al inventario del dueño). Las subastas con puja vigente se dejan cerrar con normalidad para no tener que reembolsar en caliente.

### Suspensión de usuarios

- `POST /admin/usuarios/{user}/suspension` alterna `suspended_at`.
- Una cuenta suspendida conserva NFTs y saldo, pero no puede acuñar, publicar, comprar ni pujar; ve un aviso permanente en el encabezado y sigue pudiendo navegar.
- Al suspender se retiran también sus publicaciones de venta directa activas.
- Un administrador no puede suspenderse a sí mismo ni a otro administrador (422).

### Auditoría

Cada acción de moderación agrega un bloque encadenado mediante `ChainService`, con el formato `Moderación: … por @admin`. Esto deja material listo para la tarea 3 (verificación de la cadena).

### Piezas nuevas

Migraciones `add_admin_role_to_users` y `add_status_to_collections`; `AdminController`; servicios `AdminService`, `AdminReportService` y `DemoSessionService`; middlewares `EnsureIsAdmin` y `EnsureUserIsActive`; vista `resources/views/admin/dashboard.blade.php`. Pruebas en `tests/Feature/AdminPanelTest.php`.
