#!/bin/bash
# ============================================
# Instalación MANUAL de Elasticsearch
# Para Debian Trixie u otros sistemas problemáticos
# ============================================

echo "🔍 Instalación manual de Elasticsearch..."

# Crear directorio
ES_VERSION="8.11.4"
ES_DIR="/opt/elasticsearch"

# Instalar Java si no existe
echo "☕ Verificando Java..."
if ! command -v java &> /dev/null; then
    apt-get update
    apt-get install -y default-jdk
fi

java -version

# Descargar Elasticsearch directamente
echo "📥 Descargando Elasticsearch ${ES_VERSION}..."
cd /tmp
wget -q --show-progress https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-${ES_VERSION}-linux-x86_64.tar.gz

if [ ! -f elasticsearch-${ES_VERSION}-linux-x86_64.tar.gz ]; then
    echo "❌ Error descargando Elasticsearch"
    exit 1
fi

# Extraer
echo "📦 Extrayendo..."
tar -xzf elasticsearch-${ES_VERSION}-linux-x86_64.tar.gz
rm -rf ${ES_DIR}
mv elasticsearch-${ES_VERSION} ${ES_DIR}

# Crear usuario elasticsearch si no existe
if ! id "elasticsearch" &>/dev/null; then
    useradd -r -s /bin/false elasticsearch
fi

chown -R elasticsearch:elasticsearch ${ES_DIR}

# Configurar
echo "⚙️ Configurando..."
cat > ${ES_DIR}/config/elasticsearch.yml << EOF
# BestBigData - Configuración Elasticsearch
cluster.name: bestbigdata-cluster
node.name: node-1
path.data: ${ES_DIR}/data
path.logs: ${ES_DIR}/logs
network.host: 127.0.0.1
http.port: 9200

# Desactivar seguridad para desarrollo
xpack.security.enabled: false
xpack.security.enrollment.enabled: false
xpack.security.http.ssl.enabled: false
xpack.security.transport.ssl.enabled: false
EOF

# Crear directorios necesarios
mkdir -p ${ES_DIR}/data ${ES_DIR}/logs
chown -R elasticsearch:elasticsearch ${ES_DIR}

# Configurar memoria (50% de RAM, max 31GB)
RAM_GB=$(free -g | awk '/^Mem:/{print $2}')
HEAP_SIZE=$((RAM_GB / 2))
if [ $HEAP_SIZE -lt 1 ]; then HEAP_SIZE=1; fi
if [ $HEAP_SIZE -gt 31 ]; then HEAP_SIZE=31; fi

sed -i "s/-Xms1g/-Xms${HEAP_SIZE}g/" ${ES_DIR}/config/jvm.options
sed -i "s/-Xmx1g/-Xmx${HEAP_SIZE}g/" ${ES_DIR}/config/jvm.options

echo "💾 Heap configurado: ${HEAP_SIZE}GB"

# Crear servicio systemd
cat > /etc/systemd/system/elasticsearch.service << EOF
[Unit]
Description=Elasticsearch
Documentation=https://www.elastic.co
Wants=network-online.target
After=network-online.target

[Service]
Type=simple
User=elasticsearch
Group=elasticsearch
ExecStart=${ES_DIR}/bin/elasticsearch
Restart=on-failure
RestartSec=10
LimitNOFILE=65535
LimitNPROC=4096
LimitMEMLOCK=infinity

[Install]
WantedBy=multi-user.target
EOF

# Configurar límites del sistema
echo "elasticsearch soft nofile 65535" >> /etc/security/limits.conf
echo "elasticsearch hard nofile 65535" >> /etc/security/limits.conf
echo "vm.max_map_count=262144" >> /etc/sysctl.conf
sysctl -p

# Iniciar
echo "🚀 Iniciando Elasticsearch..."
systemctl daemon-reload
systemctl enable elasticsearch
systemctl start elasticsearch

# Esperar
echo "⏳ Esperando que Elasticsearch inicie (60 segundos)..."
sleep 60

# Verificar
if curl -s http://localhost:9200 > /dev/null; then
    echo ""
    echo "============================================"
    echo "✅ Elasticsearch instalado correctamente!"
    echo "============================================"
    curl -s http://localhost:9200 | head -20
    echo ""
    echo "Próximos pasos:"
    echo "1. Ejecutar: php /var/www/bestbigdata/elasticsearch/setup_index.php"
    echo "============================================"
else
    echo "❌ Error: Elasticsearch no responde"
    echo "Revisa los logs: ${ES_DIR}/logs/"
    systemctl status elasticsearch
    exit 1
fi

# Limpiar
rm -f /tmp/elasticsearch-${ES_VERSION}-linux-x86_64.tar.gz

echo ""
echo "Comandos útiles:"
echo "  systemctl status elasticsearch  - Ver estado"
echo "  systemctl restart elasticsearch - Reiniciar"
echo "  tail -f ${ES_DIR}/logs/*.log   - Ver logs"
