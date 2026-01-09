<?php
/**
 * Modelo de Búsqueda
 * Maneja las búsquedas en las bases de datos CDR
 */

class Search extends Model
{
    protected $table = 'search_history';

    /**
     * Obtener conexiones a bases de datos CDR
     */
    public function getCDRConnections()
    {
        return Conexion::getAllCDR();
    }

    /**
     * Obtener tablas CDR disponibles
     */
    public function getCDRTables($connection, $prefix, $startDate = null, $endDate = null)
    {
        $tables = [];
        
        // Convertir strings vacíos a null
        $startDate = !empty($startDate) ? $startDate : null;
        $endDate = !empty($endDate) ? $endDate : null;

        try {
            $stmt = $connection->query("SHOW TABLES LIKE '{$prefix}%'");
            $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($allTables as $table) {
                if (preg_match('/e_cdr_(\d{8})/', $table, $matches)) {
                    $tableDate = $matches[1];

                    if ($startDate && $endDate) {
                        $start = str_replace('-', '', $startDate);
                        $end = str_replace('-', '', $endDate);

                        if ($tableDate >= $start && $tableDate <= $end) {
                            $tables[] = $table;
                        }
                    } else {
                        $tables[] = $table;
                    }
                }
            }

            rsort($tables);
            
            // Limitar tablas para evitar búsquedas muy largas
            $maxTables = ($startDate && $endDate) ? 60 : 30;
            if (count($tables) > $maxTables) {
                $tables = array_slice($tables, 0, $maxTables);
            }
        } catch (PDOException $e) {
            error_log("Error obteniendo tablas CDR: " . $e->getMessage());
        }

        return $tables;
    }

    /**
     * Buscar número en las bases de datos CDR
     * FLEXIBLE: busca el número sin importar prefijos (57, +57, 011, etc.)
     */
    public function searchPhoneNumber($phoneNumber, $startDate = null, $endDate = null)
    {
        $results = [];
        $cdrConnections = $this->getCDRConnections();
        
        // Limpiar y obtener el número base (sin prefijos)
        $baseNumber = $this->extractBaseNumber($phoneNumber);
        
        // Máximo de resultados total
        $maxTotalResults = 500;

        foreach ($cdrConnections as $dbKey => $dbInfo) {
            if (count($results) >= $maxTotalResults) break;
            
            $connection = $dbInfo['connection'];
            $prefix = $dbInfo['prefix'];
            $tables = $this->getCDRTables($connection, $prefix, $startDate, $endDate);
            
            if (empty($tables)) continue;
            
            // Buscar en lotes de tablas usando UNION ALL
            $batchSize = 5; // Reducido para mejor rendimiento con LIKE
            $tableBatches = array_chunk($tables, $batchSize);
            
            foreach ($tableBatches as $batch) {
                if (count($results) >= $maxTotalResults) break;
                
                $batchResults = $this->searchBatchTablesFlexible($connection, $batch, $baseNumber, $dbKey);
                $results = array_merge($results, $batchResults);
            }
        }

        // Ordenar solo al final
        usort($results, function ($a, $b) {
            return ($b['starttime'] ?? 0) - ($a['starttime'] ?? 0);
        });

        return array_slice($results, 0, $maxTotalResults);
    }
    
    /**
     * Extraer número base quitando prefijos comunes
     * Input: "573124560009", "+573124560009", "3124560009", "0113124560009"
     * Output: "3124560009" (número sin prefijo de país)
     */
    private function extractBaseNumber($number)
    {
        // Limpiar: solo dígitos
        $clean = preg_replace('/[^0-9]/', '', $number);
        
        // Quitar prefijo 011 (código internacional desde USA)
        if (strlen($clean) > 10 && substr($clean, 0, 3) === '011') {
            $clean = substr($clean, 3);
        }
        
        // Quitar prefijo 57 (Colombia) si el número es largo
        if (strlen($clean) > 10 && substr($clean, 0, 2) === '57') {
            $clean = substr($clean, 2);
        }
        
        return $clean;
    }
    
    /**
     * Buscar en múltiples tablas con LIKE flexible
     */
    private function searchBatchTablesFlexible($connection, $tables, $baseNumber, $dbKey)
    {
        $results = [];
        
        // UNION ALL de todas las tablas del batch
        $unions = [];
        foreach ($tables as $table) {
            $unions[] = "(SELECT 
                            callere164, calleee164, starttime, stoptime,
                            callerip, calleeip, holdtime, endreason,
                            '{$table}' as source_table
                         FROM {$table}
                         WHERE callere164 LIKE ? OR calleee164 LIKE ?
                         LIMIT 30)";
        }
        
        if (empty($unions)) return [];
        
        $sql = implode(" UNION ALL ", $unions) . " LIMIT 150";
        
        // Patrón: buscar el número base en cualquier parte
        $searchPattern = '%' . $baseNumber . '%';
        
        // Crear array de parámetros (2 por cada tabla: caller y callee)
        $params = [];
        foreach ($tables as $table) {
            $params[] = $searchPattern;
            $params[] = $searchPattern;
        }
        
        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            
            foreach ($rows as $row) {
                $row['source_db'] = $dbKey;
                $results[] = $row;
            }
        } catch (PDOException $e) {
            // Si falla el UNION, buscar tabla por tabla (fallback)
            foreach ($tables as $table) {
                $results = array_merge($results, 
                    $this->searchSingleTableFlexible($connection, $table, $baseNumber, $dbKey));
            }
        }
        
        return $results;
    }
    
    /**
     * Búsqueda en una sola tabla (fallback)
     */
    private function searchSingleTableFlexible($connection, $table, $baseNumber, $dbKey)
    {
        $results = [];
        $searchPattern = '%' . $baseNumber . '%';
        
        try {
            $sql = "SELECT 
                        callere164, calleee164, starttime, stoptime,
                        callerip, calleeip, holdtime, endreason
                    FROM {$table}
                    WHERE callere164 LIKE ? OR calleee164 LIKE ?
                    LIMIT 30";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute([$searchPattern, $searchPattern]);
            $rows = $stmt->fetchAll();
            
            foreach ($rows as $row) {
                $row['source_db'] = $dbKey;
                $row['source_table'] = $table;
                $results[] = $row;
            }
        } catch (PDOException $e) {
            // Silencioso
        }
        
        return $results;
    }

    /**
     * Registrar búsqueda
     */
    public function logSearch($userId, $phoneNumber, $resultsFound, $cost)
    {
        return $this->create([
            'user_id' => $userId,
            'phone_number' => $phoneNumber,
            'results_found' => $resultsFound,
            'cost' => $cost,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener historial de búsquedas del usuario
     */
    public function getUserHistory($userId, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        return $this->query($sql, [$userId]);
    }

    /**
     * Obtener estadísticas del usuario
     */
    public function getUserStats($userId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_searches,
                    COALESCE(SUM(results_found), 0) as total_results,
                    COALESCE(SUM(cost), 0) as total_spent
                FROM {$this->table} 
                WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Obtener búsquedas recientes (todas)
     */
    public function getRecentSearches($limit = 10)
    {
        $sql = "SELECT sh.*, u.name as user_name 
                FROM {$this->table} sh 
                JOIN users u ON sh.user_id = u.id 
                ORDER BY sh.created_at DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Contar búsquedas de hoy
     */
    public function countToday()
    {
        return $this->count("DATE(created_at) = CURDATE()");
    }

    /**
     * Obtener ingresos de hoy
     */
    public function getRevenueToday()
    {
        $sql = "SELECT COALESCE(SUM(cost), 0) as total FROM {$this->table} WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->db->query($sql);
        return $stmt->fetch()['total'];
    }

    /**
     * Procesar archivo CSV/TXT y extraer números de teléfono
     */
    public function parsePhoneFile($filePath, $fileType)
    {
        $numbers = [];
        
        if (!file_exists($filePath)) {
            return $numbers;
        }

        $content = file_get_contents($filePath);
        
        if ($fileType === 'csv') {
            // Procesar CSV
            $lines = str_getcsv($content, "\n");
            foreach ($lines as $line) {
                $columns = str_getcsv($line);
                foreach ($columns as $col) {
                    $cleaned = $this->extractPhoneNumber($col);
                    if ($cleaned) {
                        $numbers[] = $cleaned;
                    }
                }
            }
        } else {
            // Procesar TXT (un número por línea o separado por comas/espacios)
            $lines = preg_split('/[\r\n,;\s]+/', $content);
            foreach ($lines as $line) {
                $cleaned = $this->extractPhoneNumber(trim($line));
                if ($cleaned) {
                    $numbers[] = $cleaned;
                }
            }
        }

        // Eliminar duplicados y vacíos
        return array_unique(array_filter($numbers));
    }

    /**
     * Extraer número de teléfono válido de un string
     */
    private function extractPhoneNumber($string)
    {
        // Remover todo excepto números
        $number = preg_replace('/[^0-9]/', '', $string);
        
        // Validar longitud mínima (al menos 7 dígitos)
        if (strlen($number) >= 7) {
            return $number;
        }
        
        return null;
    }

    /**
     * Búsqueda masiva de múltiples números
     */
    public function searchMultiplePhoneNumbers($phoneNumbers, $startDate = null, $endDate = null)
    {
        $allResults = [];
        $summary = [];

        foreach ($phoneNumbers as $phone) {
            $results = $this->searchPhoneNumber($phone, $startDate, $endDate);
            
            $summary[$phone] = [
                'phone' => $phone,
                'count' => count($results),
                'results' => $results
            ];
            
            foreach ($results as $result) {
                $result['searched_number'] = $phone;
                $allResults[] = $result;
            }
        }

        return [
            'all_results' => $allResults,
            'summary' => $summary,
            'total_results' => count($allResults),
            'numbers_searched' => count($phoneNumbers)
        ];
    }

    /**
     * Registrar búsqueda masiva
     */
    public function logBulkSearch($userId, $numbersCount, $resultsFound, $cost)
    {
        return $this->create([
            'user_id' => $userId,
            'phone_number' => "BULK_SEARCH ({$numbersCount} números)",
            'results_found' => $resultsFound,
            'cost' => $cost,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
