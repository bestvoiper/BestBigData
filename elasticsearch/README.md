# Elasticsearch para BestBigData

## 🚀 Instalación Rápida

### Opción 1: Docker (Recomendado para Windows/Mac)

```bash
# Windows
elasticsearch\install_windows.bat

# Linux/Mac
docker run -d --name elasticsearch \
    -p 9200:9200 \
    -e "discovery.type=single-node" \
    -e "xpack.security.enabled=false" \
    docker.elastic.co/elasticsearch/elasticsearch:8.11.0
```

### Opción 2: Instalación directa (Linux)

```bash
sudo bash elasticsearch/install.sh
```

---

## 📋 Configuración

Una vez instalado Elasticsearch:

### 1. Crear el índice
Visita: `http://localhost/BestBigData/elasticsearch/setup_index.php`

Esto crea el índice con el mapping optimizado para búsquedas de teléfono.

### 2. Sincronizar datos
Visita: `http://localhost/BestBigData/elasticsearch/sync_cdr.php`

O por CLI:
```bash
# Sincronizar últimos 7 días
php elasticsearch/sync_cli.php --days=7

# Sincronizar rango específico
php elasticsearch/sync_cli.php --start=2025-01-01 --end=2025-01-09
```

### 3. Probar búsqueda
Visita: `http://localhost/BestBigData/elasticsearch/test_search.php`

---

## ⚙️ Configuración en config.php

```php
// Configuración de Elasticsearch
define('ELASTICSEARCH_HOST', 'localhost');
define('ELASTICSEARCH_PORT', 9200);
define('USE_ELASTICSEARCH', true); // false = usa MySQL
```

---

## 🔄 Sincronización Automática (Cron)

Agrega estas tareas al crontab para mantener ES actualizado:

```bash
# Cada hora - últimos 2 días
0 * * * * php /path/to/BestBigData/elasticsearch/sync_cli.php --days=2

# Cada noche a las 3am - última semana
0 3 * * * php /path/to/BestBigData/elasticsearch/sync_cli.php --days=7
```

---

## 📊 Comparación de Rendimiento

| Método | Tiempo típico | Notas |
|--------|---------------|-------|
| MySQL LIKE '%num%' | 120-300 segundos | Full table scan |
| MySQL LIKE '%num' | 60-120 segundos | Full table scan |
| **Elasticsearch** | **10-100 ms** | ⚡ Usa índices |

**Elasticsearch es ~1000x más rápido**

---

## 🔍 Cómo funciona

1. Los CDR se sincronizan desde MySQL a Elasticsearch
2. Cada número se indexa con:
   - Número completo: `573124560009`
   - Número base (sin prefijos): `3124560009`
   - Sufijos para búsqueda parcial: `["0009", "60009", "560009", ...]`
3. Las búsquedas usan term queries (O(1)) en vez de LIKE (O(n))

---

## 🛠️ Comandos útiles

```bash
# Ver estado de Elasticsearch
curl http://localhost:9200

# Ver documentos indexados
curl http://localhost:9200/bestbigdata_cdr/_count

# Ver estadísticas del índice
curl http://localhost:9200/bestbigdata_cdr/_stats

# Buscar directamente en ES (ejemplo)
curl -X POST "http://localhost:9200/bestbigdata_cdr/_search" \
  -H "Content-Type: application/json" \
  -d '{"query":{"term":{"caller_base":"3124560009"}}}'
```

---

## 🚨 Troubleshooting

### Elasticsearch no inicia
- Verifica que tienes suficiente RAM (mínimo 2GB)
- En Docker: `docker logs elasticsearch`

### Búsqueda no encuentra resultados
- Verifica que se sincronizaron datos: `curl localhost:9200/bestbigdata_cdr/_count`
- Ejecuta sincronización: `php elasticsearch/sync_cli.php --days=30`

### La aplicación sigue usando MySQL
- Verifica `USE_ELASTICSEARCH` en config.php
- Verifica que ES responde: `curl localhost:9200`
