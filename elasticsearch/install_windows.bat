@echo off
REM ============================================
REM Instalación de Elasticsearch con Docker
REM Para Windows - BestBigData
REM ============================================

echo.
echo =============================================
echo  Instalando Elasticsearch para BestBigData
echo =============================================
echo.

REM Verificar Docker
docker --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Docker no esta instalado.
    echo.
    echo Instala Docker Desktop desde:
    echo https://www.docker.com/products/docker-desktop
    echo.
    pause
    exit /b 1
)

echo Docker encontrado!
echo.

REM Crear red si no existe
docker network create elastic 2>nul

REM Detener contenedor existente si existe
docker stop elasticsearch 2>nul
docker rm elasticsearch 2>nul

echo Iniciando Elasticsearch...
echo.

REM Ejecutar Elasticsearch (modo desarrollo sin seguridad)
docker run -d ^
    --name elasticsearch ^
    --net elastic ^
    -p 9200:9200 ^
    -p 9300:9300 ^
    -e "discovery.type=single-node" ^
    -e "xpack.security.enabled=false" ^
    -e "ES_JAVA_OPTS=-Xms512m -Xmx512m" ^
    -v elasticsearch-data:/usr/share/elasticsearch/data ^
    docker.elastic.co/elasticsearch/elasticsearch:8.11.0

if errorlevel 1 (
    echo ERROR: No se pudo iniciar Elasticsearch
    pause
    exit /b 1
)

echo.
echo Esperando que Elasticsearch inicie (30 segundos)...
timeout /t 30 /nobreak >nul

REM Verificar
curl -s http://localhost:9200 >nul 2>&1
if errorlevel 1 (
    echo.
    echo ADVERTENCIA: Elasticsearch no responde aun.
    echo Espera unos segundos mas y verifica en: http://localhost:9200
) else (
    echo.
    echo =============================================
    echo  Elasticsearch instalado correctamente!
    echo =============================================
    echo.
    echo URL: http://localhost:9200
    echo.
    echo Proximos pasos:
    echo 1. Abre: http://localhost/BestBigData/elasticsearch/setup_index.php
    echo 2. Crea el indice
    echo 3. Ejecuta la sincronizacion
    echo.
)

echo.
echo Comandos utiles:
echo   docker logs elasticsearch      - Ver logs
echo   docker stop elasticsearch      - Detener
echo   docker start elasticsearch     - Iniciar
echo   docker rm -f elasticsearch     - Eliminar
echo.

pause
