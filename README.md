# Monoverse — MVP

Marketplace académico de NFTs construido con Laravel. El MVP permite simular un mercado entre tres usuarios, acuñar colecciones, comprar piezas, volver a publicarlas y registrar los movimientos en una cadena de bloques interna.

## Alcance implementado

- Mercado público de NFTs con precio de venta y precio sugerido por escasez/demanda.
- Selector de usuario de demostración para probar compras entre José Luis, Juan José y Will.
- Acuñación del primer NFT de una colección.
- Compra directa con transferencia de propiedad y saldo virtual.
- Inventario personal y reventa de una pieza, en venta directa o como subasta.
- Ofertas y pujas: cualquier usuario oferta por un NFT en subasta, se conserva siempre la mejor puja (con reembolso automático al superado) y se adjudica al cierre.
- Registro encadenado por hash de acuñaciones, publicaciones, pujas y ventas.
- Datos de demostración reproducibles con seeders.
- Interfaz responsive inspirada en la identidad visual de las láminas del proyecto.

> Este MVP usa una sesión de usuario simulada y saldo virtual. No incluye autenticación de producción, pagos reales ni una blockchain externa.

## Requisitos

- PHP 8.2 o superior
- Composer 2
- Node.js 20 o superior
- Extensión `pdo_sqlite` de PHP

## Puesta en marcha

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Crea el archivo `database/database.sqlite` si no existe y ejecuta:

```bash
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Abre `http://127.0.0.1:8000`. Para desarrollo del frontend se puede usar `npm run dev` en otra terminal.

## Arquitectura

- **Presentación:** Blade, Tailwind CSS 4 y CSS personalizado.
- **Aplicación:** controlador `MarketplaceController` para los casos de uso del MVP.
- **Dominio/datos:** Eloquent con usuarios, colecciones, NFTs, publicaciones, transacciones y bloques.
- **Persistencia:** SQLite por defecto para facilitar la demostración; la configuración puede migrarse a MySQL mediante `.env`.

El precio sugerido aplica un incremento básico por proporción acuñada (escasez) y por ventas registradas (demanda). El precio efectivo de una publicación queda fijado por el vendedor.

## Pruebas

```bash
php artisan test
```

Las pruebas cubren carga del mercado, cambio de usuario, compra con transferencia de saldo/propiedad y acuñación con registro de bloque.

## Próximo incremento

Los pendientes, responsables y fecha objetivo están en [docs/PENDIENTES.md](docs/PENDIENTES.md) y en la vista `/pendientes` de la aplicación. La tarea 1 (Ofertas y pujas) ya está implementada; el detalle de su diseño está en ese mismo documento.
