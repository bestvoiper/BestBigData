<?php
/**
 * ElasticSearch Client para BestBigData
 * Maneja conexión e interacción con Elasticsearch
 */

class ElasticSearch
{
    private static $instance = null;
    private $host;
    private $port;
    private $index;
    private $timeout;
    
    // Índice para CDR
    const INDEX_CDR = 'bestbigdata_cdr';
    
    private function __construct()
    {
        // Configuración - ajustar según tu servidor
        $this->host = defined('ELASTICSEARCH_HOST') ? ELASTICSEARCH_HOST : 'localhost';
        $this->port = defined('ELASTICSEARCH_PORT') ? ELASTICSEARCH_PORT : 9200;
        $this->index = self::INDEX_CDR;
        $this->timeout = 30;
    }
    
    /**
     * Obtener instancia singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Hacer petición HTTP a Elasticsearch
     */
    private function request(string $method, string $endpoint, array $body = null): array
    {
        $url = "http://{$this->host}:{$this->port}/{$endpoint}";
        
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ];
        
        // Para HEAD requests, configuración especial
        if ($method === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
            $options[CURLOPT_HEADER] = true;
        }
        
        curl_setopt_array($ch, $options);
        
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Elasticsearch error: $error");
        }
        
        // Para HEAD, solo retornar el código HTTP
        if ($method === 'HEAD') {
            return ['_http_code' => $httpCode];
        }
        
        $data = json_decode($response, true) ?? [];
        $data['_http_code'] = $httpCode;
        
        return $data;
    }
    
    /**
     * Verificar si Elasticsearch está disponible
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->request('GET', '');
            return isset($response['cluster_name']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener información del cluster
     */
    public function getInfo(): array
    {
        return $this->request('GET', '');
    }
    
    /**
     * Crear índice con mapping optimizado para CDR
     */
    public function createIndex(): array
    {
        $mapping = [
            'settings' => [
                'number_of_shards' => 3,
                'number_of_replicas' => 1,
                'refresh_interval' => '5s',
                'analysis' => [
                    'analyzer' => [
                        'phone_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'keyword',
                            'filter' => ['lowercase']
                        ]
                    ],
                    'normalizer' => [
                        'phone_normalizer' => [
                            'type' => 'custom',
                            'filter' => ['lowercase']
                        ]
                    ]
                ]
            ],
            'mappings' => [
                'properties' => [
                    // Números de teléfono - indexados para búsqueda rápida
                    'caller' => [
                        'type' => 'keyword',
                        'normalizer' => 'phone_normalizer'
                    ],
                    'callee' => [
                        'type' => 'keyword', 
                        'normalizer' => 'phone_normalizer'
                    ],
                    // Número base (sin prefijos) para búsqueda flexible
                    'caller_base' => [
                        'type' => 'keyword'
                    ],
                    'callee_base' => [
                        'type' => 'keyword'
                    ],
                    // Sufijos para búsqueda parcial (edge ngrams inversos)
                    'caller_suffixes' => [
                        'type' => 'keyword'
                    ],
                    'callee_suffixes' => [
                        'type' => 'keyword'
                    ],
                    // Fecha/hora
                    'start_time' => [
                        'type' => 'date',
                        'format' => 'epoch_millis||yyyy-MM-dd HH:mm:ss'
                    ],
                    'stop_time' => [
                        'type' => 'date',
                        'format' => 'epoch_millis||yyyy-MM-dd HH:mm:ss'
                    ],
                    // Metadatos
                    'duration' => ['type' => 'integer'],
                    'end_reason' => ['type' => 'keyword'],
                    'caller_ip' => ['type' => 'ip'],
                    'callee_ip' => ['type' => 'ip'],
                    // Origen
                    'source_server' => ['type' => 'keyword'],
                    'source_table' => ['type' => 'keyword'],
                    // ID único compuesto
                    'record_id' => ['type' => 'keyword']
                ]
            ]
        ];
        
        return $this->request('PUT', $this->index, $mapping);
    }
    
    /**
     * Eliminar índice
     */
    public function deleteIndex(): array
    {
        return $this->request('DELETE', $this->index);
    }
    
    /**
     * Verificar si índice existe
     */
    public function indexExists(): bool
    {
        try {
            // Usar GET en lugar de HEAD para evitar problemas de timeout
            $response = $this->request('GET', "{$this->index}/_settings?flat_settings=true");
            return $response['_http_code'] === 200;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtener estadísticas del índice
     */
    public function getIndexStats(): array
    {
        return $this->request('GET', "{$this->index}/_stats");
    }
    
    /**
     * Indexar un documento CDR
     */
    public function indexDocument(array $doc, string $id = null): array
    {
        $endpoint = $id ? "{$this->index}/_doc/{$id}" : "{$this->index}/_doc";
        $method = $id ? 'PUT' : 'POST';
        
        return $this->request($method, $endpoint, $doc);
    }
    
    /**
     * Indexar múltiples documentos (bulk)
     */
    public function bulkIndex(array $documents): array
    {
        $body = '';
        foreach ($documents as $doc) {
            $id = $doc['record_id'] ?? null;
            $meta = $id 
                ? ['index' => ['_index' => $this->index, '_id' => $id]]
                : ['index' => ['_index' => $this->index]];
            
            $body .= json_encode($meta) . "\n";
            $body .= json_encode($doc) . "\n";
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "http://{$this->host}:{$this->port}/_bulk",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-ndjson'],
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?? [];
    }
    
    /**
     * Generar sufijos de un número para búsqueda parcial
     * Ejemplo: "573124560009" -> ["9", "09", "009", "0009", "60009", ...]
     */
    public static function generateSuffixes(string $number, int $minLength = 4): array
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        $suffixes = [];
        $len = strlen($number);
        
        for ($i = $len - $minLength; $i >= 0; $i--) {
            $suffixes[] = substr($number, $i);
        }
        
        return $suffixes;
    }
    
    /**
     * Extraer número base (sin prefijos internacionales)
     */
    public static function extractBaseNumber(string $number): string
    {
        $clean = preg_replace('/[^0-9]/', '', $number);
        
        // Remover prefijo 011 (código de salida US)
        if (strlen($clean) > 10 && substr($clean, 0, 3) === '011') {
            $clean = substr($clean, 3);
        }
        
        // Remover prefijo 57 (Colombia)
        if (strlen($clean) > 10 && substr($clean, 0, 2) === '57') {
            $clean = substr($clean, 2);
        }
        
        return $clean;
    }
    
    /**
     * Preparar documento CDR para indexación
     */
    public static function prepareCDRDocument(array $row, string $server, string $table): array
    {
        $caller = $row['callere164'] ?? '';
        $callee = $row['calleee164'] ?? '';
        
        // Crear ID único
        $startTime = $row['starttime'] ?? 0;
        $recordId = md5("{$server}_{$table}_{$caller}_{$callee}_{$startTime}");
        
        // Validar IPs (Elasticsearch requiere formato válido)
        $callerIp = self::validateIP($row['callerip'] ?? null);
        $calleeIp = self::validateIP($row['calleeip'] ?? null);
        
        return [
            'record_id' => $recordId,
            'caller' => $caller,
            'callee' => $callee,
            'caller_base' => self::extractBaseNumber($caller),
            'callee_base' => self::extractBaseNumber($callee),
            'caller_suffixes' => self::generateSuffixes($caller),
            'callee_suffixes' => self::generateSuffixes($callee),
            'start_time' => is_numeric($startTime) ? (int)$startTime : strtotime($startTime) * 1000,
            'stop_time' => isset($row['stoptime']) ? (is_numeric($row['stoptime']) ? (int)$row['stoptime'] : strtotime($row['stoptime']) * 1000) : null,
            'duration' => (int)($row['holdtime'] ?? 0),
            'end_reason' => $row['endreason'] ?? '',
            'caller_ip' => $callerIp,
            'callee_ip' => $calleeIp,
            'source_server' => $server,
            'source_table' => $table
        ];
    }
    
    /**
     * Validar y limpiar dirección IP
     */
    private static function validateIP($ip): ?string
    {
        if (empty($ip)) {
            return null;
        }
        
        // Limpiar espacios
        $ip = trim($ip);
        
        // Verificar si es una IP válida (IPv4 o IPv6)
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        // Si no es válida, retornar null
        return null;
    }
    
    /**
     * Buscar números de teléfono - BÚSQUEDA RÁPIDA
     */
    public function searchPhone(string $number, string $startDate = null, string $endDate = null, int $limit = 500): array
    {
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);
        $baseNumber = self::extractBaseNumber($number);
        
        // Construir query
        $must = [];
        
        // Buscar por número base O por sufijo (para búsqueda flexible)
        $should = [
            // Búsqueda exacta por número base
            ['term' => ['caller_base' => $baseNumber]],
            ['term' => ['callee_base' => $baseNumber]],
            // Búsqueda por sufijo (últimos dígitos)
            ['term' => ['caller_suffixes' => $baseNumber]],
            ['term' => ['callee_suffixes' => $baseNumber]],
            // Búsqueda exacta completa
            ['term' => ['caller' => $cleanNumber]],
            ['term' => ['callee' => $cleanNumber]],
        ];
        
        // Filtro por fecha
        $filter = [];
        if ($startDate || $endDate) {
            $range = ['start_time' => []];
            if ($startDate) {
                $range['start_time']['gte'] = strtotime($startDate . ' 00:00:00') * 1000;
            }
            if ($endDate) {
                $range['start_time']['lte'] = strtotime($endDate . ' 23:59:59') * 1000;
            }
            $filter[] = ['range' => $range];
        }
        
        $query = [
            'size' => $limit,
            'query' => [
                'bool' => [
                    'should' => $should,
                    'minimum_should_match' => 1,
                    'filter' => $filter
                ]
            ],
            'sort' => [
                ['start_time' => ['order' => 'desc']]
            ],
            '_source' => true
        ];
        
        $startTime = microtime(true);
        $response = $this->request('POST', "{$this->index}/_search", $query);
        $searchTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $results = [];
        if (isset($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $source = $hit['_source'];
                $results[] = [
                    'callere164' => $source['caller'],
                    'calleee164' => $source['callee'],
                    'starttime' => $source['start_time'],
                    'stoptime' => $source['stop_time'],
                    'holdtime' => $source['duration'],
                    'endreason' => $source['end_reason'],
                    'callerip' => $source['caller_ip'],
                    'calleeip' => $source['callee_ip'],
                    'source_db' => $source['source_server'],
                    'source_table' => $source['source_table'],
                    '_score' => $hit['_score']
                ];
            }
        }
        
        return [
            'results' => $results,
            'total' => $response['hits']['total']['value'] ?? count($results),
            'search_time_ms' => $searchTime,
            'es_took_ms' => $response['took'] ?? 0
        ];
    }
    
    /**
     * Búsqueda avanzada con wildcards (más lenta pero flexible)
     */
    public function searchWildcard(string $pattern, string $startDate = null, string $endDate = null, int $limit = 500): array
    {
        $should = [
            ['wildcard' => ['caller' => ['value' => "*{$pattern}*"]]],
            ['wildcard' => ['callee' => ['value' => "*{$pattern}*"]]],
        ];
        
        $filter = [];
        if ($startDate || $endDate) {
            $range = ['start_time' => []];
            if ($startDate) $range['start_time']['gte'] = strtotime($startDate . ' 00:00:00') * 1000;
            if ($endDate) $range['start_time']['lte'] = strtotime($endDate . ' 23:59:59') * 1000;
            $filter[] = ['range' => $range];
        }
        
        $query = [
            'size' => $limit,
            'query' => [
                'bool' => [
                    'should' => $should,
                    'minimum_should_match' => 1,
                    'filter' => $filter
                ]
            ],
            'sort' => [['start_time' => ['order' => 'desc']]]
        ];
        
        $startTime = microtime(true);
        $response = $this->request('POST', "{$this->index}/_search", $query);
        $searchTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $results = [];
        if (isset($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $source = $hit['_source'];
                $results[] = [
                    'callere164' => $source['caller'],
                    'calleee164' => $source['callee'],
                    'starttime' => $source['start_time'],
                    'stoptime' => $source['stop_time'],
                    'holdtime' => $source['duration'],
                    'endreason' => $source['end_reason'],
                    'callerip' => $source['caller_ip'],
                    'calleeip' => $source['callee_ip'],
                    'source_db' => $source['source_server'],
                    'source_table' => $source['source_table']
                ];
            }
        }
        
        return [
            'results' => $results,
            'total' => $response['hits']['total']['value'] ?? count($results),
            'search_time_ms' => $searchTime
        ];
    }
    
    /**
     * Contar documentos en el índice
     */
    public function count(): int
    {
        $response = $this->request('GET', "{$this->index}/_count");
        return $response['count'] ?? 0;
    }
    
    /**
     * Refrescar índice (hacer visibles los documentos recién indexados)
     */
    public function refresh(): array
    {
        return $this->request('POST', "{$this->index}/_refresh");
    }
}
