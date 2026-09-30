<img width="1600" height="900" alt="Imagen de ChatGPT 30 sept 2026, 08_16_36" src="https://github.com/user-attachments/assets/ec7dc2c1-65da-464a-a6aa-af0cfe621644" />



# Monoverse — MVP

Marketplace académico de NFTs construido con Laravel 12. Versión completada el 30 de septiembre de 2026; integra los aportes de pujas y administración del equipo, favoritos, verificación de la cadena y acceso con cuentas individuales.

**Sitio desplegado:** [Monoverse en Google Cloud](https://monoverse-414457182299.us-central1.run.app). Crea una cuenta para explorar con saldo virtual. La administración y los costos se documentan en [docs/GCP.md](docs/GCP.md).

## Alcance implementado

- Mercado público de NFTs con precio de venta y precio sugerido por escasez/demanda.
- Registro, inicio y cierre de sesión. Cada cuenta nueva recibe 25 MONO virtuales.
- Acuñación de 1 a 25 copias por colección, respetando el suministro, con hash único y una imagen opcional JPG/PNG/WebP de hasta 1 MB.
- Compra directa con transferencia de propiedad y saldo virtual.
- Inventario personal y reventa de una pieza, en venta directa o como subasta.
- Ofertas y pujas: cualquier usuario oferta por un NFT en subasta, se conserva siempre la mejor puja (con reembolso automático al superado) y se adjudica al cierre.
- Panel de administración: un rol admin consulta reportes del mercado, oculta o restaura colecciones y suspende o reactiva cuentas.
- Registro encadenado por hash de acuñaciones, publicaciones, pujas, ventas y acciones de moderación.
- Verificación pública de hashes, enlaces y secuencia en `/cadena`.
- Favoritos privados de NFTs y colecciones en `/favoritos`.
- Datos de demostración reproducibles con seeders.
- Interfaz responsive inspirada en la identidad visual de las láminas del proyecto.

> MONO es saldo ficticio: no hay pagos reales, billeteras criptográficas ni blockchain externa. La verificación detecta inconsistencias internas, no una reescritura completa o truncamiento final por quien controla la base de datos.

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

Crea el archivo vacío `database/database.sqlite` si no existe y ejecuta:

```bash
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

Abre `http://127.0.0.1:8000`. Para desarrollo del frontend se puede usar `npm run dev` en otra terminal. Las migraciones conservan los datos existentes; el seeder solo inicializa una base vacía.

En local puedes crear tu cuenta o ingresar con `jose@monoverse.test`, `juan@monoverse.test`, `will@monoverse.test` o `admin@monoverse.test`, contraseña `demo1234`. Para demostraciones locales sin login se admite `MONOVERSE_DEMO=true`; en producción el selector está deshabilitado incluso si esa variable se activa. Las cuentas sembradas en producción reciben contraseñas aleatorias, no la contraseña local.

Para mantener el cierre de subastas sin visitas, ejecuta `php artisan schedule:work`. En Google Cloud un job de respaldo ejecuta `auctions:close` cada cinco minutos; las visitas al mercado también procesan cierres vencidos inmediatamente.

## Arquitectura

- **Presentación:** Blade, Tailwind CSS 4 y CSS personalizado.
- **Aplicación:** controladores `MarketplaceController` y `AdminController`; la lógica de negocio vive en servicios (`AuctionService`, `AdminService`, `AdminReportService`, `ChainService`, `DemoSessionService`) y los middlewares `EnsureIsAdmin` / `EnsureUserIsActive` aplican las reglas de acceso.
- **Dominio/datos:** Eloquent con usuarios, colecciones, NFTs, publicaciones, transacciones y bloques.
- **Persistencia:** SQLite en local y Cloud SQL MySQL en la nube. Sesiones e imágenes se guardan en la base de datos para sobrevivir a los reinicios de Cloud Run.

El precio sugerido aplica un incremento básico por proporción acuñada (escasez) y por ventas registradas (demanda). El precio efectivo de una publicación queda fijado por el vendedor.

## Pruebas

```bash
php artisan test
```

Las pruebas usan SQLite en memoria (no borran tu base local) y cubren mercado, autenticación, permisos, transferencia de saldo/propiedad, subida de imágenes, acuñación, favoritos, integridad de la cadena, subastas y moderación. El build se verifica con `npm run build`.

## Equipo y despliegue

Las cuatro tareas están completadas en [docs/PENDIENTES.md](docs/PENDIENTES.md) y en `/pendientes`, que conserva la fecha objetivo original del 23 de septiembre. La guía de despliegue y operación está en [docs/GCP.md](docs/GCP.md).
