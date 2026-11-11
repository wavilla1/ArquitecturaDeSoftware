# Diagrama de arquitectura

Refleja el código. Los diagramas usan Mermaid.

Monoverse es un monolito Laravel 12 organizado por capas: rutas y middlewares, controladores, servicios de negocio y modelos Eloquent. El detalle de clases está en [DIAGRAMA_CLASES.md](DIAGRAMA_CLASES.md).

## 1. Arquitectura lógica (capas)

```mermaid
flowchart TB
    navegador["Navegador<br/>(usuarios y administrador)"]
    consumidor["Otro equipo o sistema<br/>(consume la API JSON)"]
    coingecko["API de CoinGecko<br/>(tercero, cotizaciones)"]

    subgraph app["Aplicación Laravel 12 (monolito)"]
        direction TB

        subgraph entrada["Entrada"]
            web["routes/web.php<br/>páginas y formularios"]
            api["routes/api.php<br/>GET /api/v1/nfts"]
            consola["routes/console.php<br/>auctions:close cada minuto"]
        end

        subgraph mw["Middlewares"]
            mwlist["SetLocale · EnsureMarketplaceUser<br/>EnsureUserIsActive · EnsureIsAdmin<br/>throttle · CORS"]
        end

        subgraph ctrl["Controladores"]
            ctrlweb["Marketplace · Nft · Collection<br/>Admin · Auth · Locale"]
            ctrlapi["Api/NftCatalogController"]
            cmd["Comandos Artisan<br/>CloseExpiredAuctions · InitializeCloud"]
        end

        subgraph vistas["Presentación"]
            blade["Vistas Blade + CSS/JS (Vite)<br/>textos en lang/es y lang/en"]
        end

        subgraph svc["Servicios de negocio"]
            servicios["AuctionService · ChainService<br/>AdminService · AdminReportService<br/>NftCatalogService · DemoSessionService"]
            contrato["«interface» CryptoMarket"]
            impl["CoinGeckoMarket / NullCryptoMarket"]
        end

        subgraph datos["Datos"]
            modelos["Modelos Eloquent<br/>User · NftCollection · Nft · Listing<br/>Bid · MarketplaceTransaction · Block"]
            cache["Caché de Laravel<br/>(cotizaciones, 5 min)"]
        end
    end

    bd[("Base de datos<br/>SQLite en local · MySQL en producción")]

    navegador -->|HTTPS| web
    consumidor -->|HTTPS, JSON| api
    web --> mwlist
    api --> mwlist
    mwlist --> ctrlweb
    mwlist --> ctrlapi
    consola --> cmd
    ctrlweb --> blade
    ctrlweb --> servicios
    ctrlweb --> contrato
    ctrlapi --> servicios
    cmd --> servicios
    contrato -.->|implementada por| impl
    impl --> cache
    impl -->|HTTPS, sin API key| coingecko
    servicios --> modelos
    ctrlweb --> modelos
    modelos --> bd
```

Puntos clave:

- **Dos formas de entrar:** las páginas web (sesión, formularios, vistas Blade) y la API pública de solo lectura `GET /api/v1/nfts`, sin sesión ni autenticación.
- **Middlewares:** `EnsureMarketplaceUser` exige sesión, `EnsureUserIsActive` bloquea a las cuentas suspendidas, `EnsureIsAdmin` protege el panel y `SetLocale` aplica el idioma elegido (español o inglés).
- **Lógica de negocio en servicios:** subastas, cadena de bloques, moderación, reportes y catálogo. Algunas operaciones (acuñar, comprar, vender) siguen en `MarketplaceController`, que también consulta modelos directamente.
- **Integración con un tercero:** el mercado muestra cotizaciones reales de CoinGecko. El controlador solo conoce la interfaz `CryptoMarket`; si CoinGecko falla, la sección se oculta y el resto de la página carga normal.
- **Cadena de auditoría:** `ChainService` agrega un bloque con hash SHA-256 por cada evento relevante (acuñación, venta, moderación) y `/cadena` la verifica públicamente.

## 2. Despliegue en Google Cloud


```mermaid
flowchart LR
    usuario["Navegador / consumidor de la API"]
    coingecko["API de CoinGecko"]

    subgraph gcp["Google Cloud · proyecto monoverse-mvp-20260930 · us-central1"]
        direction TB
        run["Cloud Run: monoverse<br/>PHP 8.3 + Apache · 0 a 3 instancias"]
        sql[("Cloud SQL: monoverse-db<br/>MySQL 8<br/>datos, sesiones y caché")]
        secretos["Secret Manager<br/>clave de app y contraseñas"]
        registro["Artifact Registry<br/>imagen Docker"]
        build["Cloud Build<br/>compila el Dockerfile"]
        jobinit["Job: monoverse-initialize<br/>migraciones y datos iniciales"]
        jobsub["Job: monoverse-auctions<br/>cierra subastas"]
        scheduler["Cloud Scheduler<br/>cada 5 minutos"]
    end

    usuario -->|HTTPS| run
    run -->|conector Cloud SQL| sql
    run --> secretos
    run -->|HTTPS| coingecko
    build --> registro
    registro --> run
    registro --> jobinit
    registro --> jobsub
    scheduler --> jobsub
    jobinit --> sql
    jobsub --> sql
```

