<?php
/**
 * Configurar índice de Elasticsearch
 * Ejecutar UNA VEZ después de instalar Elasticsearch
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/app/config/config.php';
require_once APP_ROOT . '/app/services/ElasticSearch.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup Elasticsearch - BestBigData</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h1>🔧 Configuración de Elasticsearch</h1>
    
    <?php
    $es = ElasticSearch::getInstance();
    
    // Verificar conexión
    echo "<h4>1. Verificando conexión...</h4>";
    if (!$es->isAvailable()) {
        echo "<div class='alert alert-danger'>
            ❌ No se puede conectar a Elasticsearch<br>
            <small>Asegúrate de que está corriendo en localhost:9200</small>
        </div>";
        exit;
    }
    
    $info = $es->getInfo();
    echo "<div class='alert alert-success'>
        ✅ Conectado a Elasticsearch<br>
        <strong>Cluster:</strong> {$info['cluster_name']}<br>
        <strong>Versión:</strong> {$info['version']['number']}
    </div>";
    
    // Crear o recrear índice
    $action = $_GET['action'] ?? '';
    
    if ($action === 'create') {
        echo "<h4>2. Creando índice...</h4>";
        
        // Eliminar si existe
        if ($es->indexExists()) {
            echo "<p>⚠️ Eliminando índice existente...</p>";
            $es->deleteIndex();
            sleep(1);
        }
        
        // Crear nuevo
        $result = $es->createIndex();
        
        if (isset($result['acknowledged']) && $result['acknowledged']) {
            echo "<div class='alert alert-success'>
                ✅ Índice <strong>" . ElasticSearch::INDEX_CDR . "</strong> creado correctamente
            </div>";
            
            echo "<div class='alert alert-info'>
                <strong>Próximo paso:</strong> Ejecuta la sincronización para importar los CDR<br>
                <a href='sync_cdr.php' class='btn btn-primary mt-2'>Ir a Sincronización</a>
            </div>";
        } else {
            echo "<div class='alert alert-danger'>
                ❌ Error creando índice<br>
                <pre>" . print_r($result, true) . "</pre>
            </div>";
        }
    } else {
        echo "<h4>2. Estado del índice</h4>";
        
        if ($es->indexExists()) {
            $stats = $es->getIndexStats();
            $docCount = $es->count();
            
            $indexStats = $stats['indices'][ElasticSearch::INDEX_CDR]['primaries'] ?? [];
            $sizeBytes = $indexStats['store']['size_in_bytes'] ?? 0;
            $sizeMB = round($sizeBytes / 1024 / 1024, 2);
            
            echo "<div class='alert alert-info'>
                <strong>Índice:</strong> " . ElasticSearch::INDEX_CDR . "<br>
                <strong>Documentos:</strong> " . number_format($docCount) . "<br>
                <strong>Tamaño:</strong> {$sizeMB} MB
            </div>";
            
            echo "<div class='mt-3'>
                <a href='?action=create' class='btn btn-danger' onclick='return confirm(\"¿Eliminar y recrear índice? Se perderán todos los datos.\")'>
                    🔄 Recrear índice
                </a>
                <a href='sync_cdr.php' class='btn btn-primary'>
                    📥 Ir a Sincronización
                </a>
                <a href='test_search.php' class='btn btn-success'>
                    🔍 Probar búsqueda
                </a>
            </div>";
        } else {
            echo "<div class='alert alert-warning'>
                ⚠️ El índice no existe aún
            </div>";
            
            echo "<a href='?action=create' class='btn btn-success btn-lg'>
                ✨ Crear índice
            </a>";
        }
    }
    ?>
    
    <hr class="mt-4">
    
    <h4>📋 Información del Mapping</h4>
    <p>El índice está optimizado para búsqueda rápida de números telefónicos:</p>
    <ul>
        <li><strong>caller / callee:</strong> Número completo (keyword)</li>
        <li><strong>caller_base / callee_base:</strong> Número sin prefijos internacionales</li>
        <li><strong>caller_suffixes / callee_suffixes:</strong> Sufijos para búsqueda parcial</li>
        <li><strong>start_time:</strong> Fecha/hora en milisegundos</li>
        <li><strong>duration:</strong> Duración en segundos</li>
        <li><strong>source_server / source_table:</strong> Origen del registro</li>
    </ul>
    
</div>
</body>
</html>
