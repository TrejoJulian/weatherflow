# WeatherFlow

API REST para una plataforma de servicios meteorológicos distribuida. Permite gestionar estaciones de medición, registrar datos climáticos, evaluar alertas automáticas y sincronizar información entre servicios mediante mensajería asíncrona.

Desarrollado como Trabajo Práctico para la materia **Arquitectura de Software II — UNQ**.

---



## Requisitos previos

Solo necesitás tener instalado:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (incluye Docker Compose)

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

Los valores por defecto del `.env.example` ya están alineados con las credenciales del `compose.yaml` y funcionan sin modificaciones para el entorno de desarrollo.

### 4. Levantar los contenedores

```bash
docker compose up -d
```

Esto levanta todos los servicios: Core, Monitor, ambas instancias de MongoDB, RabbitMQ, el worker de eventos y el frontend.

### 5. Generar las claves de aplicación

```bash
docker exec weatherflow-core php artisan key:generate
docker exec weatherflow-monitor php artisan key:generate
```

---

## Servicios disponibles

| Servicio | URL | Descripción |
|---|---|---|
| Core (Gestión) | http://localhost:8080 | API de usuarios, estaciones y suscripciones |
| Monitor (Monitoreo) | http://localhost:8081 | API de mediciones, alertas e historial |
| Frontend | http://localhost:3000 | Interfaz web para demostración |
| RabbitMQ Management | http://localhost:15672 | Panel de administración de colas |
| MongoDB Core | localhost:27017 | Base de datos del servicio Core |
| MongoDB Monitor | localhost:27018 | Base de datos del servicio Monitor |

**Credenciales de RabbitMQ:** usuario `weatherflow` / contraseña `secret`

---

## Solución de problemas en Windows

### Error de credenciales de Docker (Windows con Git Bash o WSL2)

Si `docker compose up -d` falla con un error relacionado a credenciales al intentar descargar `nginx:alpine`, ejecutar esto **una sola vez** (no afecta al proyecto):

```bash
grep -v '"credsStore"' ~/.docker/config.json > /tmp/dc.json && mv /tmp/dc.json ~/.docker/config.json
```

Luego repetir `docker compose up -d`. Este problema es específico de Windows con `credsStore: "desktop"` en la configuración local de Docker.

| Entorno | Pasos |
|---|---|
| Linux / Mac | `docker compose up -d` |
| Windows (WSL2 o Git Bash) | Si falla con error de credenciales, correr el comando `grep` de arriba y luego `docker compose up -d` |

### Unable to get image

Las causas mas común si estás en WSL2:

- Agregar el usuario al grupo docker (solo una vez)
sudo usermod -aG docker $USER

- Aplicar el cambio sin reiniciar
newgrp docker

### Reintentar
docker compose up -d

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
| `GET` | `/api/stations` | Listar estaciones |
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
  "sensor_model": "Davis Vantage Pro2"
}
```

---

## API — weatherflow-monitor (puerto 8081)

### Mediciones

| Método | Endpoint | Descripción |
|---|---|---|
| `GET` | `/api/measurements` | Listar mediciones (con filtros opcionales) |
| `POST` | `/api/measurements` | Registrar medición |
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

Las alertas se evalúan automáticamente al registrar una medición. Los umbrales son estrictos (el valor exacto **no** dispara alerta):

| Condición | Umbral | Tipo de alerta |
|---|---|---|
| Temperatura | > 40 °C | `extreme_heat` |
| Temperatura | < 0 °C | `frost` |
| Presión atmosférica | < 980 hPa | `storm` |
| Humedad | > 90 % | `critical_humidity` |

Una medición puede tener múltiples alertas simultáneas.

---

## Verificar eventos en RabbitMQ

El panel de administración está disponible en **http://localhost:15672** (usuario: `weatherflow` / contraseña: `secret`).

Para verificar eventos:
- `Queues → alert-events → Get Messages`: eventos `AlertDetected` publicados al registrar mediciones con alerta
- `Queues → station-events → Get Messages`: eventos `StationRenamed` publicados al renombrar una estación

---

## Ejecutar los tests

### Unit y Feature tests:

Estando en el root del proyecto:

```bash
docker exec weatherflow-core php artisan test
docker exec weatherflow-monitor php artisan test
```

### Tests de integración

Los tests de integración verifican la comunicación real entre servicios (HTTP cross-service y eventos en RabbitMQ). Requieren levantar el entorno con configuración de test aislada:

```bash
# 1. Bajar el entorno de desarrollo (si está corriendo)
docker compose down

# 2. Levantar el entorno de integración
docker compose -f compose.yaml -f compose.test.yml up -d

# 3. Correr los tests de integración
cd services/monitor && php artisan test --testsuite=Integration

# 4. Al terminar, volver al entorno de desarrollo normal
docker compose -f compose.yaml -f compose.test.yml down
docker compose up -d
```

El compose override apunta los servicios a bases de datos y colas separadas (`weatherflow_core_test`, `weatherflow_monitor_test`, `alert-events-test`, `station-events-test`) para no contaminar los datos de desarrollo.

---

## Comandos útiles

```bash
# Ver logs de un servicio
docker logs weatherflow-core -f
docker logs weatherflow-monitor -f
docker logs weatherflow-monitor-worker -f

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
