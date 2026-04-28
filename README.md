# WeatherFlow

API REST para una plataforma de servicios meteorológicos. Permite gestionar estaciones de medición, registrar datos climáticos y evaluar alertas automáticas en base a umbrales predefinidos.

Desarrollado como Trabajo Práctico para la materia **Arquitectura de Software II — UNQ**.

---

## Tecnologías

- **PHP 8.4** — Laravel 13
- **MongoDB 7** — base de datos principal
- **Laravel Sail** — entorno de desarrollo en Docker (no requiere PHP ni MongoDB instalados localmente)
- **Pest** — framework de testing

---

## Requisitos previos

Solo necesitás tener instalado:

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (incluye Docker Compose)

Nada más. PHP, Composer y MongoDB corren dentro de los contenedores.

---

## Instalación y puesta en marcha

### 1. Clonar el repositorio

```bash
git clone https://github.com/TrejoJulian/weatherflow.git
cd weatherflow
```

### 2. Copiar el archivo de entorno

```bash
cp .env.example .env
```

### 3. Agregar las variables de MongoDB al `.env`

Abrír el `.env` y agregar al final:

```env
MONGODB_URI=mongodb://weatherflow:secret@mongodb:27017/weatherflow?authSource=admin
MONGODB_DATABASE=weatherflow
```

### 4. Instalar dependencias PHP desde Docker

La primera vez no se tiene Composer instalado localmente, así que usamos la imagen oficial de Composer para instalar las dependencias:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

> En Windows (PowerShell) reemplazar `-u "$(id -u):$(id -g)"` por `-u 1000:1000`.

### 5. Levantar los contenedores

```bash
./vendor/bin/sail up -d
```

Esto levanta dos contenedores: la aplicación Laravel (puerto 80) y MongoDB (puerto 27017).

### 6. Generar la clave de aplicación

```bash
./vendor/bin/sail artisan key:generate
```

> **Error de permisos:** Si aparece _Permission denied_ sobre `storage/logs/laravel.log` o `.env`, corré esto primero y luego repetí el comando:
> ```bash
> docker exec -u root weatherflow-app chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache
> docker exec -u root weatherflow-app chmod 666 /var/www/html/.env
> ```

### 7. Verificar que todo funciona

```bash
./vendor/bin/sail artisan about
```

---

## Documentación de la API

Con los contenedores corriendo, la documentación interactiva (Swagger UI) está disponible en:

```
http://localhost/docs/
```

---

## Endpoints disponibles

| Método   | Endpoint                                          | Descripción                        |
|----------|---------------------------------------------------|------------------------------------|
| `GET`    | `/api/users`                                      | Listar usuarios                    |
| `POST`   | `/api/users`                                      | Crear usuario                      |
| `GET`    | `/api/users/{id}`                                 | Obtener usuario                    |
| `PUT`    | `/api/users/{id}`                                 | Actualizar usuario                 |
| `DELETE` | `/api/users/{id}`                                 | Eliminar usuario                   |
| `POST`   | `/api/users/{id}/subscriptions`                   | Suscribir usuario a una estación   |
| `DELETE` | `/api/users/{id}/subscriptions/{stationId}`       | Desuscribir usuario de una estación|
| `GET`    | `/api/stations`                                   | Listar estaciones                  |
| `POST`   | `/api/stations`                                   | Crear estación                     |
| `GET`    | `/api/stations/{id}`                              | Obtener estación                   |
| `PUT`    | `/api/stations/{id}`                              | Actualizar estación                |
| `DELETE` | `/api/stations/{id}`                              | Eliminar estación                  |
| `GET`    | `/api/measurements`                               | Listar mediciones (con filtros)    |
| `POST`   | `/api/measurements`                               | Registrar medición                 |
| `GET`    | `/api/measurements/{id}`                          | Obtener medición                   |
| `PUT`    | `/api/measurements/{id}`                          | Actualizar medición                |
| `DELETE` | `/api/measurements/{id}`                          | Eliminar medición                  |

### Filtros disponibles en `GET /api/measurements`

| Parámetro    | Tipo    | Descripción                                                      |
|--------------|---------|------------------------------------------------------------------|
| `station`    | string  | Filtrar por nombre de estación (búsqueda parcial)                |
| `temp_min`   | number  | Temperatura mínima                                               |
| `temp_max`   | number  | Temperatura máxima                                               |
| `alert`      | boolean | `true` para traer solo mediciones con alerta activa              |
| `alert_type` | string  | `extreme_heat`, `frost`, `storm` o `critical_humidity`           |

---

## Reglas de alerta

Las alertas se evalúan automáticamente al crear o actualizar una medición. Los umbrales son estrictos (el valor exacto no dispara alerta):

| Condición                     | Alerta             |
|-------------------------------|--------------------|
| Temperatura **> 40 °C**       | Extreme Heat       |
| Temperatura **< 0 °C**        | Frost              |
| Presión atmosférica **< 980 hPa** | Storm          |
| Humedad **> 90 %**            | Critical Humidity  |

Una medición puede tener múltiples alertas activas simultáneamente.

---

## Ejecutar los tests

```bash
./vendor/bin/sail test
```

Para correr solo los tests unitarios (no requieren base de datos):

```bash
./vendor/bin/sail test --testsuite=Unit
```

Para correr solo los tests de integración (requieren Sail corriendo):

```bash
./vendor/bin/sail test --testsuite=Feature
```

---

## Comandos útiles de Sail

```bash
# Detener los contenedores
./vendor/bin/sail down

# Ver logs de la aplicación
./vendor/bin/sail logs

# Abrir una shell dentro del contenedor
./vendor/bin/sail shell

# Limpiar caché de configuración
./vendor/bin/sail artisan optimize:clear
```
