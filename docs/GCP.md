# Despliegue en Google Cloud

Proyecto dedicado: `monoverse-mvp-20260930` (Monoverse MVP). Región: `us-central1`.

Sitio público: [Monoverse](https://monoverse-414457182299.us-central1.run.app). Primera publicación: 30 de septiembre de 2026.

Actualización del 9 de noviembre de 2026: revisión `monoverse-00002-nov`, imagen `us-central1-docker.pkg.dev/monoverse-mvp-20260930/monoverse/web:api-20261109-1`, con el 100 % del tráfico de producción. Se añadió el [catálogo JSON público](https://monoverse-414457182299.us-central1.run.app/api/v1/nfts?page=1) y las páginas `/nfts/{id}`. Contrato y ejemplos en [API.md](API.md).

## Componentes

- Cloud Run `monoverse`: PHP 8.3 + Apache, HTTPS administrado, 0 instancias mínimas y 3 máximas, 512 MiB por instancia.
- Cloud SQL `monoverse-db`: MySQL 8, `db-f1-micro`, 10 GB SSD, base `monoverse`, backup diario. Sin redes autorizadas para conexión directa; la aplicación usa el conector de Cloud SQL.
- Artifact Registry `monoverse`: imágenes compiladas con Cloud Build.
- Secret Manager: `monoverse-app-key`, `monoverse-db-password` y `monoverse-admin-password`. No guardar sus valores en Git.
- Cuenta de servicio `monoverse-runtime`: acceso a Cloud SQL y a los secretos necesarios.
- Job `monoverse-initialize`: migraciones incrementales y datos iniciales solo si la base está vacía.
- Job `monoverse-auctions`: cierre de subastas; Cloud Scheduler lo invoca cada cinco minutos. El mercado y el inventario también procesan cierres en cada visita.

Las sesiones, NFTs, saldos, imágenes y favoritos viven en MySQL. No dependen del disco temporal de Cloud Run.

## Administración

Crear una cuenta desde `/registro` para probar el mercado. El administrador usa `admin@monoverse.test`; su contraseña está en el secreto `monoverse-admin-password` del proyecto. Solo un operador autorizado del proyecto debe acceder a ese secreto. Las cuentas demo restantes tienen contraseñas aleatorias en producción.

Acceso al secreto desde la [consola de Secret Manager](https://console.cloud.google.com/security/secret-manager/secret/monoverse-admin-password/versions?project=monoverse-mvp-20260930). No publicar la contraseña ni compartirla en el repositorio.

Consola del proyecto: https://console.cloud.google.com/home/dashboard?project=monoverse-mvp-20260930

## Verificación del catálogo del 9 de noviembre

- 35 pruebas automatizadas, 286 aserciones, incluido un conjunto de 10 pruebas nuevas para la API: aprobadas.
- Build de Vite y Cloud Build: aprobados. La nueva revisión se verificó sin tráfico antes de promoverla.
- 94 comprobaciones HTTP aprobadas tanto en la revisión temporal como en la URL de producción: contrato JSON, CORS, ausencia de sesión, precios, enlaces, validación, métodos rechazados, login y cadena íntegra.
- Paginación real comprobada con 31 piezas disponibles durante la revisión: 20 en la primera página y 11 en la segunda, sin IDs duplicados. También se comprobaron todos los enlaces de detalle de la segunda página.
- No hubo migraciones ni reinicialización de datos. La instancia Cloud SQL terminó su operación de mantenimiento y quedó `RUNNABLE`; las cuentas e inventario existentes se conservaron.
- Los jobs de inicialización y subastas apuntan a la misma imagen nueva; el job de inicialización no se ejecutó, al no existir cambios de esquema.

## Verificación de la primera entrega

- 25 pruebas automatizadas de Laravel, 122 aserciones: aprobadas.
- Build de Vite y compilación de la imagen en Cloud Build: aprobados.
- 34 comprobaciones HTTP contra el sitio desplegado: páginas públicas, assets, bloqueo de invitados, registro sin escalada de rol/saldo, favoritos, compra y bloqueo de compra duplicada, reventa, acuñación de dos copias con imagen, persistencia tras volver a ingresar, panel admin y cadena válida.
- Se conservó una cuenta técnica `qa_3a8687be` y su colección `Cloud Test 3a8687be` como evidencia de las operaciones de comprobación. Son datos ficticios y no credenciales de acceso para el equipo.

## Actualizar

```powershell
gcloud builds submit --tag=us-central1-docker.pkg.dev/monoverse-mvp-20260930/monoverse/web:VERSION --project=monoverse-mvp-20260930 --region=us-central1
gcloud run jobs update monoverse-initialize --image=us-central1-docker.pkg.dev/monoverse-mvp-20260930/monoverse/web:VERSION --region=us-central1 --project=monoverse-mvp-20260930
gcloud run jobs execute monoverse-initialize --wait --region=us-central1 --project=monoverse-mvp-20260930
gcloud run deploy monoverse --image=us-central1-docker.pkg.dev/monoverse-mvp-20260930/monoverse/web:VERSION --region=us-central1 --project=monoverse-mvp-20260930
gcloud run jobs update monoverse-auctions --image=us-central1-docker.pkg.dev/monoverse-mvp-20260930/monoverse/web:VERSION --region=us-central1 --project=monoverse-mvp-20260930
```

Estos comandos conservan la configuración existente de conexiones y secretos. No usar `migrate:fresh` en la nube: elimina datos. Antes de un cambio de esquema importante, crear un backup bajo demanda de Cloud SQL.

## Consultar estado

```powershell
gcloud run services describe monoverse --region=us-central1 --project=monoverse-mvp-20260930 --format='value(status.url)'
gcloud run services logs read monoverse --region=us-central1 --project=monoverse-mvp-20260930 --limit=30
gcloud sql instances describe monoverse-db --project=monoverse-mvp-20260930
```

## Costos y pausa

Cloud SQL factura cómputo mientras está encendido, incluso sin visitas. También pueden facturarse almacenamiento, copias, builds, secretos, programación y tráfico. Cloud Run usa escalado a cero; eso no apaga Cloud SQL. Consultar las tarifas vigentes: https://cloud.google.com/sql/pricing y https://cloud.google.com/run/pricing.

Para una pausa que conserva los datos (el almacenamiento y los backups pueden seguir generando cargos):

```powershell
gcloud scheduler jobs pause monoverse-auctions --location=us-central1 --project=monoverse-mvp-20260930
gcloud sql instances patch monoverse-db --activation-policy=NEVER --project=monoverse-mvp-20260930
```

Esto deja el sitio sin acceso a la base de datos hasta reactivar con `--activation-policy=ALWAYS` y reanudar el scheduler con `gcloud scheduler jobs resume`.

## Límites del MVP

Saldo ficticio; sin pagos ni blockchain externa. No incluye correo de recuperación de contraseña, verificación de email, alta disponibilidad de la base ni un historial anclado externamente. Las alertas de presupuesto no son un límite duro de gasto. El rol admin presenta reportes operativos del mercado, no un sistema de denuncias de usuarios.
