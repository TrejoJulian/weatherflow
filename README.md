# WeatherFlow

API REST para una plataforma de servicios meteorológicos distribuida. Gestiona estaciones de medición, ingesta datos climáticos reales desde **OpenWeatherMap** de forma periódica, registra mediciones manuales, evalúa alertas automáticas, expone reportes y sincroniza información entre servicios mediante mensajería asíncrona. Todo el sistema se **observa** desde un stack unificado de Grafana (métricas, logs y tracing distribuido) y se **mide bajo carga** con K6.

Desarrollado como Trabajo Práctico para la materia **Arquitectura de Software II — UNQ**.

---

## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (incluye Docker Compose)
- Una **API key de OpenWeatherMap** (el [plan free](https://openweathermap.org/price) alcanza) — necesaria para la ingesta periódica y el reporte de temperatura actual

No se necesita PHP, Composer ni MongoDB instalados localmente.

---

## Instalación y puesta en marcha

### 1. Clonar el repositorio

```bash
git clone https://github.com/TrejoJulian/weatherflow.git
cd weatherflow
```

### 2. Instalar dependencias PHP

Las dependencias deben instalarse dentro de cada servicio usando la imagen oficial de Composer, ya que no se requiere PHP local.

**En Linux / Mac:**

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd)/services/core:/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd)/services/monitor:/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

**En Windows (PowerShell):**

```powershell
docker run --rm `
    -u 1000:1000 `
    -v "${PWD}/services/core:/var/www/html" `
    -w /var/www/html `
    laravelsail/php84-composer:latest `
    composer install --ignore-platform-reqs

docker run --rm `
    -u 1000:1000 `
    -v "${PWD}/services/monitor:/var/www/html" `
    -w /var/www/html `
    laravelsail/php84-composer:latest `
    composer install --ignore-platform-reqs
```

### 3. Configurar los archivos de entorno

```bash
cp services/core/.env.example services/core/.env
cp services/monitor/.env.example services/monitor/.env
```

Los valores por defecto ya están alineados con las credenciales del `compose.yaml` y funcionan sin modificaciones, **con una excepción obligatoria**: la API key de OpenWeatherMap en `services/core/.env`:

```env
OPENWEATHER_API_KEY=tu-api-key-aqui
```

Sin la key, el stack levanta igual pero la ingesta periódica y el reporte de temperatura actual fallan contra OWM (se ve como `owm_requests_total{outcome="error"}` en las métricas y errores 401 en los logs).

Otras variables que se pueden ajustar en `services/core/.env` (los defaults sirven):

| Variable | Default | Qué controla |
|---|---|---|
| `INGESTION_CRON` | `*/10 * * * *` | Frecuencia de la ingesta desde OWM (cada 10 min) |
| `OWM_CONNECT_TIMEOUT` / `OWM_TIMEOUT` | `3` / `8` | Timeouts (s) de conexión y respuesta contra OWM |
| `OWM_RETRIES` | `3` | Reintentos con backoff ante fallos transitorios |
| `OWM_BREAKER_THRESHOLD` / `OWM_BREAKER_RESET` | `5` / `30` | Circuit breaker: fallos para abrir / segundos para reintentar |
| `OWM_FRESH_TTL` / `OWM_CACHE_TTL` | `60` / `600` | TTL (s) de la request cache de lecturas |

### 4. Levantar los contenedores

```bash
docker compose up -d
```

La primera vez construye la imagen PHP compartida (`weatherflow/php:8.4`, definida en `docker/php/`) y descarga el resto — tarda unos minutos. Levanta todo el sistema:

- **Aplicación:** Core (web + scheduler de ingesta), Monitor (web), dos workers de Monitor, frontend, ambas instancias de MongoDB, Redis y RabbitMQ.
- **Observabilidad:** Prometheus, Grafana, Loki, Promtail, Tempo, OTel Collector, cAdvisor, node-exporter y docker-stats-exporter.

### 5. Generar las claves de aplicación

```bash
docker exec weatherflow-core php artisan key:generate
docker exec weatherflow-monitor php artisan key:generate
```

### 6. Sembrar las estaciones default

```bash
docker exec weatherflow-core php artisan db:seed
```

Crea un usuario `system` y **tres estaciones con UUIDs determinísticos** (el seeder es idempotente — re-correrlo no duplica nada):

| Estación | UUID |
|---|---|
| Universidad Nacional de Quilmes | `00000000-0000-4000-8000-000000000101` |
| Bariloche | `00000000-0000-4000-8000-000000000102` |
| Ushuaia | `00000000-0000-4000-8000-000000000103` |

A partir de acá el scheduler ingesta lecturas reales de OWM para esas estaciones en cada tick (default cada 10 minutos). Para no esperar al primer tick, se puede disparar una ingesta manual:

```bash
docker exec weatherflow-core php artisan core:ingest-measurements
```

### 7. Verificar que todo funciona

```bash
# Las mediciones ingestadas aparecen en Monitor
curl http://localhost:8081/api/measurements

# Reporte de temperatura actual (en vivo desde OWM, con cache)
curl http://localhost:8080/api/reports/current-temp/00000000-0000-4000-8000-000000000101

# Métricas Prometheus de cada servicio
curl http://localhost:8080/metrics
curl http://localhost:8081/metrics
```

Y en **Grafana** (http://localhost:3001, `admin` / `admin`) el dashboard **WeatherFlow** ya viene provisionado con las métricas de hardware, por endpoint y de negocio.

---

## Servicios disponibles

| Servicio | URL | Descripción |
|---|---|---|
| Core (Gestión) | http://localhost:8080 | API de usuarios, estaciones, suscripciones y reporte de temperatura actual |
| Monitor (Monitoreo) | http://localhost:8081 | API de mediciones, alertas, historial y reportes de promedios |
| Frontend | http://localhost:3000 | Interfaz web para demostración |
| Grafana | http://localhost:3001 | Dashboard, logs (Loki), traces (Tempo) — `admin` / `admin` |
| Prometheus | http://localhost:9090 | Métricas crudas y estado de los targets de scrape |
| RabbitMQ Management | http://localhost:15672 | Panel de administración de colas — `weatherflow` / `secret` |
| MongoDB Core | localhost:27017 | Base de datos del servicio Core |
| MongoDB Monitor | localhost:27018 | Base de datos del servicio Monitor |
| Redis | localhost:6379 | Cache de lecturas OWM, estado del circuit breaker y registry de métricas |

Loki, Tempo, el OTel Collector, cAdvisor, node-exporter y el endpoint Prometheus de RabbitMQ (`:15692`) solo se exponen dentro de la red de Docker; se consultan a través de Grafana o Prometheus.

### Topología de procesos PHP

Cada servicio es un codebase, pero corre en varios contenedores con roles distintos (misma imagen, distinto comando):

| Contenedor | Servicio | Rol |
|---|---|---|
| `weatherflow-core` | Core | Web + **scheduler de ingesta** (`schedule:work` supervisado dentro del contenedor) |
| `weatherflow-monitor` | Monitor | Web |
| `weatherflow-monitor-worker` | Monitor | Consumer de `station-events` (sincroniza renombres de estación) |
| `weatherflow-monitor-worker-raw` | Monitor | Consumer de `raw-measurements` (procesa la ingesta de OWM) |

> Los workers pueden quedar en restart-loop unos ~20 s al arrancar hasta que RabbitMQ termina de levantar; es normal.

---

## Frontend

El frontend es una interfaz sencilla pensada para la demostración del proyecto. Permite interactuar con ambas APIs sin necesidad de hacer pruebas manuales con curl o herramientas externas. Está disponible en **http://localhost:3000** una vez que los contenedores están corriendo.

---

## API — weatherflow-core (puerto 8080)

### Usuarios

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/users` | Listar usuarios |
| `POST` | `/api/users` | Crear usuario |
| `GET` | `/api/users/{id}` | Obtener usuario por ID |
| `PUT` | `/api/users/{id}` | Actualizar usuario |
| `DELETE` | `/api/users/{id}` | Eliminar usuario |
| `POST` | `/api/users/{id}/subscriptions` | Suscribir usuario a una estación |
| `DELETE` | `/api/users/{id}/subscriptions/{stationId}` | Desuscribir usuario de una estación |

**Ejemplo — crear usuario:**
```json
POST http://localhost:8080/api/users
{
  "email": "ana@example.com",
  "first_name": "Ana",
  "last_name": "García"
}
```

### Estaciones

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/stations` | Listar estaciones (filtros opcionales por nombre y fecha de creación) |
| `POST` | `/api/stations` | Crear estación |
| `GET` | `/api/stations/{id}` | Obtener estación por ID |
| `PUT` | `/api/stations/{id}` | Actualizar estación (renombrar publica `StationRenamed` a RabbitMQ) |
| `DELETE` | `/api/stations/{id}` | Eliminar estación |

**Ejemplo — crear estación:**
```json
POST http://localhost:8080/api/stations
{
  "owner_id": "{user_id}",
  "station_name": "Estación Central BA",
  "latitude": -34.6037,
  "longitude": -58.3816,
  "sensor_model": "Davis Vantage Pro2",
  "climate_provider": "openweather"
}
```

`climate_provider` es opcional (default `openweather`) y determina de qué proveedor externo se ingestan las lecturas de esa estación.

### Reportes

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/reports/current-temp/{stationId}` | Temperatura **actual** de la estación — llamada en vivo a OWM |

La respuesta incluye `source` (`cache` / `live` / `fallback-cache`) y `stale`: dentro del TTL se sirve desde la request cache de Redis; si OWM está caído (o el circuit breaker abierto) se responde la última lectura conocida con `stale: true` en lugar de un error 500.

---

## API — weatherflow-monitor (puerto 8081)

### Mediciones

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/measurements` | Listar mediciones (con filtros opcionales) |
| `POST` | `/api/measurements` | Registrar medición manual |
| `GET` | `/api/measurements/{id}` | Obtener medición por ID |
| `DELETE` | `/api/measurements/{id}` | Eliminar medición |

**Ejemplo — registrar medición (con alerta de calor extremo):**
```json
POST http://localhost:8081/api/measurements
{
  "station_id": "{station_id}",
  "temperature": 42.0,
  "humidity": 55.0,
  "atmospheric_pressure": 1010.0,
  "reported_at": "2026-05-24T14:00:00Z"
}
```

Al detectar una alerta, el Monitor publica automáticamente un evento `AlertDetected` a la cola `alert-events` de RabbitMQ.

### Reportes

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/reports/avg/day?station_id={id}` | Promedio de temperatura de las últimas 24 h |
| `GET` | `/api/reports/avg/week?station_id={id}` | Promedio de temperatura de los últimos 7 días |

### Filtros disponibles en `GET /api/measurements`

Todos los parámetros son opcionales y combinables:

| Parámetro | Tipo | Descripción |
|---|---|---|
| `station_name` | string | Nombre de estación (búsqueda parcial) |
| `date_from` | ISO 8601 | Límite inferior del rango de fechas |
| `date_to` | ISO 8601 | Límite superior del rango de fechas |
| `temp_min` | float | Temperatura mínima (°C) |
| `temp_max` | float | Temperatura máxima (°C) |
| `humidity_min` | float | Humedad mínima (%) |
| `humidity_max` | float | Humedad máxima (%) |
| `pressure_min` | float | Presión mínima (hPa) |
| `pressure_max` | float | Presión máxima (hPa) |
| `alert_only` | bool | Solo mediciones con alerta activa |
| `alert_type` | string | Tipo específico: `extreme_heat`, `frost`, `storm`, `critical_humidity` |

---

## Reglas de alerta

Las alertas se evalúan automáticamente al registrar una medición (manual o ingestada). Los umbrales son estrictos (el valor exacto **no** dispara alerta):

| Condición | Umbral | Tipo de alerta |
|---|---|---|
| Temperatura | > 40 °C | `extreme_heat` |
| Temperatura | < 0 °C | `frost` |
| Presión atmosférica | < 980 hPa | `storm` |
| Humedad | > 90 % | `critical_humidity` |

Una medición puede tener múltiples alertas simultáneas.

---

## Observabilidad

Las tres señales (métricas, logs y traces) se consultan desde el mismo Grafana (http://localhost:3001, `admin` / `admin`). Datasources y dashboard se provisionan solos al levantar el stack.

- **Dashboard `WeatherFlow`** — métricas de hardware por contenedor (cAdvisor / node-exporter), por endpoint (rate, latencia p95, status codes por ruta) y de negocio (mediciones ingestadas, alertas disparadas, tasa de error contra OWM, estado del circuit breaker, fallbacks servidos, profundidad de colas).
- **Logs (Explore → Loki)** — los cuatro procesos PHP loguean JSON estructurado; Promtail lo envía a Loki. Filtrar un flujo completo entre ambos servicios:
  ```logql
  {service=~"weatherflow-.*"} | trace_id="<id>"
  ```
  Cada línea con `trace_id` tiene un botón para saltar directo a su trace en Tempo.
- **Traces (Explore → Tempo)** — tracing distribuido con OpenTelemetry: un solo trace por medición ingestada, desde la llamada a OWM en Core hasta la publicación de `AlertDetected` en Monitor, cruzando RabbitMQ vía header W3C `traceparent`.
- **Métricas crudas** — `GET /metrics` en cada servicio (fuera del prefijo `/api`), scrapeado por Prometheus (http://localhost:9090 → Status → Targets para verificar que todo está UP).

---

## Verificar eventos en RabbitMQ

El panel de administración está disponible en **http://localhost:15672** (usuario: `weatherflow` / contraseña: `secret`).

| Cola | Evento | Cuándo se publica |
|---|---|---|
| `raw-measurements` | `RawMeasurementIngested` | En cada tick de ingesta, una lectura por estación (Core → Monitor) |
| `alert-events` | `AlertDetected` | Al registrar una medición que supera algún umbral |
| `station-events` | `StationRenamed` | Al renombrar una estación |

Para inspeccionar: `Queues → <cola> → Get Messages`. Las colas de consumo constante (`raw-measurements`, `station-events`) suelen verse vacías — los workers las drenan al instante; su actividad se ve en los logs o en Grafana.

---

## Ejecutar los tests

### Unit y Feature tests

Estando en el root del proyecto:

```bash
docker exec weatherflow-core php artisan config:clear
docker exec weatherflow-core php artisan test

docker exec weatherflow-monitor php artisan config:clear
docker exec weatherflow-monitor php artisan test
```

> **Siempre `config:clear` antes de correr tests.** Si la configuración quedó cacheada (`php artisan optimize`), los overrides de `phpunit.xml` que apuntan los tests a las bases `*_test` no se aplican y la suite podría correr contra la base real. Como red de seguridad, el trait `RefreshMongoCollections` se niega a dropear colecciones si el nombre de la base no termina en `_test`.

Los tests de integración contra la API real de OpenWeatherMap requieren `OPENWEATHER_API_KEY` válida en el `.env` de Core.

### Tests de integración entre servicios

Verifican la comunicación real entre servicios (HTTP cross-service y eventos en RabbitMQ). Requieren levantar el entorno con configuración de test aislada:

```bash
# 1. Bajar el entorno de desarrollo (si está corriendo)
docker compose down

# 2. Levantar el entorno de integración
docker compose -f compose.yaml -f compose.test.yml up -d

# 3. Correr los tests de integración de monitor
cd services/monitor && php artisan test --testsuite=Integration

# 4. Al terminar, volver al entorno de desarrollo normal
docker compose -f compose.yaml -f compose.test.yml down
docker compose up -d
```

El compose override apunta los servicios a bases de datos y colas separadas (`weatherflow_core_test`, `weatherflow_monitor_test`, `alert-events-test`, `station-events-test`) para no contaminar los datos de desarrollo.

---

## Tests de carga (K6)

Tests de carga sobre los tres endpoints de reporte, corridos con K6 vía Docker — sin instalar nada en el host. Con el stack levantado, desde la raíz del repo:

**En Linux / Mac:**

```bash
docker run --rm --network weatherflow_weatherflow \
  -v "$(pwd)/tests/load:/scripts" \
  grafana/k6 run -e PROFILE=smoke /scripts/current-temp.js
```

**En Windows (PowerShell):**

```powershell
docker run --rm --network weatherflow_weatherflow `
  -v "${PWD}\tests\load:/scripts" `
  grafana/k6 run -e PROFILE=smoke /scripts/current-temp.js
```

Scripts disponibles: `current-temp.js`, `avg-day.js`, `avg-week.js`. Perfiles: `smoke` (default), `load`, `stress`. Todos validan thresholds pass/fail (`p95 < 500ms`, fallos < 1%); si no se cumplen, K6 sale con exit code ≠ 0. Detalle de perfiles y parámetros: [`tests/load/README.md`](tests/load/README.md).

---

## Solución de problemas en Windows

### Error de credenciales de Docker (Windows con Git Bash o WSL2)

Si `docker compose up -d` falla con un error relacionado a credenciales al descargar imágenes, ejecutar esto **una sola vez** (no afecta al proyecto):

```bash
grep -v '"credsStore"' ~/.docker/config.json > /tmp/dc.json && mv /tmp/dc.json ~/.docker/config.json
```

Luego repetir `docker compose up -d`. Este problema es específico de Windows con `credsStore: "desktop"` en la configuración local de Docker.

### Unable to get image

La causa más común si estás en WSL2:

```bash
# Agregar el usuario al grupo docker (solo una vez)
sudo usermod -aG docker $USER

# Aplicar el cambio sin reiniciar
newgrp docker

# Reintentar
docker compose up -d
```

### cAdvisor / node-exporter en Docker Desktop

En Docker Desktop (Windows/WSL2 y Mac), cAdvisor y node-exporter miden la **VM de Docker**, no el hardware real del host. Las métricas por contenedor son correctas; las "del host" corresponden a la VM. Para el alcance del TP es suficiente.

---

## Comandos útiles

```bash
# Ver logs de un servicio (JSON estructurado)
docker logs weatherflow-core -f              # web + scheduler de ingesta
docker logs weatherflow-monitor -f
docker logs weatherflow-monitor-worker -f    # consumer de station-events
docker logs weatherflow-monitor-worker-raw -f # consumer de raw-measurements

# Disparar un tick de ingesta manualmente (sin esperar al cron)
docker exec weatherflow-core php artisan core:ingest-measurements

# Re-sembrar las estaciones default (idempotente)
docker exec weatherflow-core php artisan db:seed

# Abrir shell dentro de un contenedor
docker exec -it weatherflow-core bash
docker exec -it weatherflow-monitor bash

# Limpiar caché de configuración
docker exec weatherflow-core php artisan optimize:clear
docker exec weatherflow-monitor php artisan optimize:clear

# Detener todos los contenedores
docker compose down
```

---
