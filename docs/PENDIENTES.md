# Pendientes del próximo incremento

**Fecha objetivo:** miércoles 23 de septiembre de 2026
**Estado inicial:** pendiente

| # | Función | Responsable | Criterio mínimo de entrega | Estado |
|---|---|---|---|---|
| 1 | Ofertas y pujas | Will | Un usuario oferta por un NFT publicado, se conserva la mejor puja y se adjudica al cierre. | ✅ Completado |
| 2 | Panel de administración | Juan José | Un rol admin modera colecciones, suspende usuarios y consulta reportes. | Pendiente |
| 3 | Verificación de la cadena | José Luis | Una vista pública recalcula los hashes e informa si la cadena fue alterada. | Pendiente |
| 4 | Favoritos | José Luis | Un usuario marca y desmarca NFTs o colecciones y consulta su lista personal. | Pendiente |

## Fuera del alcance del MVP actual

Las funciones 2, 3 y 4 no están implementadas todavía. La aplicación sí guarda una cadena interna para que la tarea 3 pueda construir la validación pública sobre los bloques existentes.

## Tarea 1 — Ofertas y pujas (implementada)

- Al publicar un NFT, el vendedor elige entre **venta directa** (comportamiento original) o **subasta**, con una duración de 1, 6, 24 o 72 horas.
- En una subasta, "Comprar ahora" desaparece: los demás usuarios solo pueden ofertar (`POST /publicaciones/{listing}/pujar`), siempre por encima de la mejor puja vigente (o del precio inicial si aún no hay pujas).
- El saldo del pujador se **reserva** al ofertar y se **libera automáticamente** si otro usuario lo supera, de modo que en todo momento solo hay una puja con fondos retenidos por subasta.
- Al cumplirse `closes_at`, la subasta se adjudica a la mejor puja vigente: se transfiere el NFT, se acredita el saldo al vendedor y se registra la venta en la cadena de bloques. Si nadie ofertó, la publicación expira y el NFT vuelve al inventario del dueño.
- El cierre ocurre de forma perezosa (al cargar `/` o `/perfil`) y también existe el comando `php artisan auctions:close`, programado cada minuto en `routes/console.php` para quien use `schedule:work`.
- Nuevas piezas: migración `create_bids_table`, modelo `Bid`, `App\Services\AuctionService` (lógica de negocio) y `App\Services\ChainService` (registro en la cadena, extraído del controlador). Pruebas en `tests/Feature/AuctionTest.php`.
