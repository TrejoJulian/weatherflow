# Tests de carga (K6)

Tests de carga sobre los endpoints de reporte, corridos con [K6](https://k6.io/) vía Docker — no hace falta instalar nada en el host. El contenedor de K6 se conecta a la red del compose y le pega a los servicios por hostname interno (`core`, `monitor`), evitando el overhead del port-mapping.

> Las corridas oficiales (las que validan los thresholds) se hacen con el proyecto clonado en el **filesystem nativo de Linux** (`~/...`, nunca `/mnt/c` o `/mnt/d`). En un entorno con el proyecto montado desde Windows vía WSL2, los tests sirven para validar funcionamiento pero la latencia medida refleja el montaje, no la arquitectura.

## Tests

| Script | Endpoint | Servicio |
|---|---|---|
| `current-temp.js` | `GET /api/reports/current-temp/{stationId}` | Core |
| `avg-day.js` | `GET /api/reports/avg/day?station_id=` | Monitor |
| `avg-week.js` | `GET /api/reports/avg/week?station_id=` | Monitor |

## Perfiles

Cada script corre con uno de tres perfiles, seleccionado con `-e PROFILE=`:

- **`smoke`** (default): 3 VUs constantes, 30 s — verifica que el endpoint responde bien bajo carga mínima.
- **`load`**: rampa 0 → 10 → 20 VUs, ~3 min — régimen normal.
- **`stress`**: rampa 0 → 20 → 50 VUs sostenidos, ~4 min — dónde y cómo se degrada.

Todos los perfiles validan los mismos thresholds (definidos en `lib/profiles.js`): `http_req_duration p95 < 500ms` y `http_req_failed < 1%`. Si no se cumplen, K6 termina con exit code ≠ 0.

## Cómo correr

Con el stack levantado (`docker compose up -d`), desde la raíz del repo.

Linux / Ubuntu:

```bash
docker run --rm --network weatherflow_weatherflow \
  -v "$(pwd)/tests/load:/scripts" \
  grafana/k6 run -e PROFILE=smoke /scripts/current-temp.js
```

Windows (PowerShell):

```powershell
docker run --rm --network weatherflow_weatherflow `
  -v "${PWD}\tests\load:/scripts" `
  grafana/k6 run -e PROFILE=smoke /scripts/current-temp.js
```

## Parámetros

Todos opcionales, se pasan con `-e NOMBRE=valor`:

| Variable | Default | Descripción |
|---|---|---|
| `PROFILE` | `smoke` | Perfil de carga: `smoke`, `load` o `stress` |
| `BASE_URL_CORE` | `http://core` | URL base de Core (hostname interno del compose) |
| `BASE_URL_MONITOR` | `http://monitor` | URL base de Monitor |
| `STATION_ID` | `...0101` (UNQ) | Estación a consultar; las default son `00000000-0000-4000-8000-0000000001{01,02,03}` (UNQ, Bariloche, Ushuaia) |
