# Servicio público de catálogo de Monoverse

El servicio entrega NFTs disponibles para compra directa. Otro equipo puede mostrar las piezas y enlazar a su página en Monoverse, sin acceder a información privada de las cuentas.

## Endpoint

```http
GET https://monoverse-414457182299.us-central1.run.app/api/v1/nfts?page=1
Accept: application/json
```

No requiere autenticación, API key, cookies ni cuerpo en la petición. Solo admite lectura (`GET` y `HEAD`); no permite comprar, pujar o modificar datos. CORS permite consultas desde cualquier origen, sin credenciales. El límite es de 60 consultas por minuto por IP.

`page` es opcional, por defecto `1`, y debe ser un entero entre 1 y 1000000. Cada página contiene hasta 20 elementos, ordenados por ID ascendente. El tamaño de página es fijo: no existe un parámetro `per_page` configurable.

Solo se incluyen publicaciones activas de venta directa, con la pieza marcada en venta, colección visible, propietario no suspendido y vendedor que coincide con el propietario. Se excluyen las subastas, ventas completadas, publicaciones canceladas y piezas sin publicar. Consultar esta API no cierra subastas ni registra transacciones.

## Ejemplo completo

Respuesta `200 OK` con `Content-Type: application/json`. Los valores siguientes son ilustrativos; los resultados reales dependen del inventario disponible.

```json
{
  "data": [
    {
      "id": 1,
      "name": "Nebula Echoes #1",
      "description": "Fragmentos de color nacidos en una nebulosa digital.",
      "collection": "Nebula Echoes",
      "price": "3.20",
      "currency": "MONO",
      "seller": "juanjose",
      "image_url": null,
      "detail_url": "https://monoverse-414457182299.us-central1.run.app/nfts/1"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

## Campos

| Campo | Tipo | Significado |
|---|---|---|
| `data` | array | Lista de piezas de esta página. Es `[]` si no hay resultados. |
| `id` | integer | Identificador del NFT, no de la publicación de venta. |
| `name` | string | Nombre de colección seguido del número de copia. |
| `description` | string | Descripción de la colección. |
| `collection` | string | Nombre de la colección. |
| `price` | string | Precio de venta con dos decimales, conservando la precisión decimal. |
| `currency` | string | Siempre `MONO`, saldo ficticio sin valor monetario. |
| `seller` | string | Alias público del vendedor, sin el prefijo `@`. |
| `image_url` | string o null | URL absoluta de la imagen; `null` si la colección no tiene imagen subida. |
| `detail_url` | string | URL absoluta de la página pública de la pieza. |
| `meta.current_page` | integer | Página consultada. |
| `meta.per_page` | integer | Máximo de elementos por página: 20. |
| `meta.total` | integer | Total de piezas que cumplen los filtros, no solo las de esta página. |
| `meta.last_page` | integer | Última página; vale 1 incluso cuando el catálogo está vacío. |

No se exponen correos, contraseñas, saldos, identificadores de sesión ni imágenes codificadas en base64. Los nombres y descripciones son contenido de los usuarios: el consumidor debe tratarlos como texto, no insertarlos como HTML.

## Página individual

`GET /nfts/{id}` muestra la pieza y su precio, vendedor, imagen o arte de respaldo y hash. Un visitante puede iniciar sesión para comprar. El procesamiento de la compra conserva los controles de saldo, propiedad, moderación y disponibilidad existentes.

Si la pieza se vende, se retira, se oculta, se cambia a subasta o el vendedor es suspendido, deja de aparecer en el catálogo y su página devuelve `404`. Una consulta no reserva inventario: el consumidor debe tolerar cambios entre consultar y abrir un enlace. Las respuestas del catálogo llevan `Cache-Control: no-store, private` para evitar servir inventario antiguo desde cachés HTTP.

## Estados HTTP

- `200`: consulta correcta, incluso si `data` está vacío o la página supera la última disponible.
- `422`: parámetro `page` inválido. Devuelve JSON con `message` y `errors.page`.
- `429`: límite de consultas alcanzado. Respetar el encabezado `Retry-After` antes de reintentar.
- `405`: método no permitido, por ejemplo `POST`.
- `404`: ruta inexistente o detalle de una pieza no disponible.
- `500` o `503`: fallo temporal del servicio o infraestructura. El consumidor debe mostrar un estado de error, no interpretar la respuesta como catálogo vacío.

Las rutas `/api/*` responden con errores JSON incluso cuando no se envía el encabezado `Accept`. Los textos de error pueden cambiar; el consumidor debe basarse en el estado HTTP y los nombres de los campos.

## Ejemplo para el equipo consumidor

```javascript
const response = await fetch(
  'https://monoverse-414457182299.us-central1.run.app/api/v1/nfts?page=1',
  { headers: { Accept: 'application/json' }, credentials: 'omit' }
);
if (!response.ok) throw new Error(`Monoverse respondió ${response.status}`);
const { data, meta } = await response.json();
// Renderizar con textContent o el escape de plantillas del framework.
// Mostrar una imagen de respaldo cuando image_url sea null.
// Pedir page + 1 mientras current_page sea menor que last_page.
console.log(data, meta);
```

Para verificar el contrato sin conectarse a producción: `php artisan test --filter=NftCatalogTest`.
