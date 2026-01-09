#!/bin/bash
# ============================================
# Instalación de Elasticsearch para BestBigData
# Ejecutar como root: sudo bash install.sh
# ============================================

echo "🔍 Instalando Elasticsearch para BestBigData..."

# Detectar sistema operativo
if [ -f /etc/debian_version ]; then
    OS="debian"
elif [ -f /etc/redhat-release ]; then
    OS="redhat"
else
    echo "❌ Sistema operativo no soportado"
    exit 1
fi

echo "📦 Sistema detectado: $OS"

# Instalar dependencias necesarias
echo "📦 Instalando dependencias..."
if [ "$OS" = "debian" ]; then
    apt-get update
    apt-get install -y curl wget gnupg apt-transport-https
fi

# Instalar Java (requerido por Elasticsearch)
echo "☕ Instalando Java..."
if [ "$OS" = "debian" ]; then
    # Intentar Java 21 primero (Debian Trixie), luego 17
    apt-get install -y openjdk-21-jdk 2>/dev/null || apt-get install -y openjdk-17-jdk 2>/dev/null || apt-get install -y default-jdk
else
    yum install -y java-17-openjdk || yum install -y java-21-openjdk
fi

# Verificar Java
java -version
if [ $? -ne 0 ]; then
    echo "❌ Error: Java no se instaló correctamente"
    exit 1
fi

# Agregar repositorio de Elasticsearch
echo "📥 Agregando repositorio de Elasticsearch..."
if [ "$OS" = "debian" ]; then
    # Descargar e importar la key GPG correctamente
    wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | gpg --dearmor -o /usr/share/keyrings/elasticsearch-keyring.gpg 2>/dev/null
    
    # Si gpg --dearmor falla, intentar método alternativo
    if [ ! -f /usr/share/keyrings/elasticsearch-keyring.gpg ]; then
        wget -qO /usr/share/keyrings/elasticsearch-keyring.gpg https://artifacts.elastic.co/GPG-KEY-elasticsearch
    fi
    
    echo "deb [signed-by=/usr/share/keyrings/elasticsearch-keyring.gpg] https://artifacts.elastic.co/packages/8.x/apt stable main" | tee /etc/apt/sources.list.d/elastic-8.x.list
    apt-get update
    apt-get install -y elasticsearch
else
    rpm --import https://artifacts.elastic.co/GPG-KEY-elasticsearch
    cat > /etc/yum.repos.d/elasticsearch.repo << EOF
[elasticsearch]
name=Elasticsearch repository for 8.x packages
baseurl=https://artifacts.elastic.co/packages/8.x/yum
gpgcheck=1
gpgkey=https://artifacts.elastic.co/GPG-KEY-elasticsearch
enabled=1
autorefresh=1
type=rpm-md
EOF
    yum install -y elasticsearch
fi

# Configurar Elasticsearch (modo desarrollo, sin seguridad para facilitar)
echo "⚙️ Configurando Elasticsearch..."
cat > /etc/elasticsearch/elasticsearch.yml << EOF
# BestBigData - Configuración Elasticsearch
cluster.name: bestbigdata-cluster
node.name: node-1
path.data: /var/lib/elasticsearch
path.logs: /var/log/elasticsearch
network.host: 127.0.0.1
http.port: 9200

# Desactivar seguridad para desarrollo (activar en producción)
xpack.security.enabled: false
xpack.security.enrollment.enabled: false
xpack.security.http.ssl.enabled: false
xpack.security.transport.ssl.enabled: false

# Memoria
indices.memory.index_buffer_size: 30%
EOF

# Configurar memoria JVM (ajustar según RAM del servidor)
RAM_GB=$(free -g | awk '/^Mem:/{print $2}')
HEAP_SIZE=$((RAM_GB / 2))
if [ $HEAP_SIZE -lt 1 ]; then HEAP_SIZE=1; fi
if [ $HEAP_SIZE -gt 31 ]; then HEAP_SIZE=31; fi

cat > /etc/elasticsearch/jvm.options.d/heap.options << EOF
-Xms${HEAP_SIZE}g
-Xmx${HEAP_SIZE}g
EOF

echo "💾 Heap configurado: ${HEAP_SIZE}GB"

# Iniciar servicio
echo "🚀 Iniciando Elasticsearch..."
systemctl daemon-reload
systemctl enable elasticsearch
systemctl start elasticsearch

# Esperar a que inicie
echo "⏳ Esperando que Elasticsearch inicie..."
sleep 30

# Verificar
if curl -s http://localhost:9200 > /dev/null; then
    echo "✅ Elasticsearch instalado correctamente!"
    curl -s http://localhost:9200
else
    echo "❌ Error: Elasticsearch no responde"
    systemctl status elasticsearch
    exit 1
fi

echo ""
echo "============================================"
echo "✅ Instalación completada!"
echo "============================================"
echo "URL: http://localhost:9200"
echo ""
echo "Próximos pasos:"
echo "1. Ejecutar: php elasticsearch/setup_index.php"
echo "2. Ejecutar: php elasticsearch/sync_cdr.php"
echo "============================================"
