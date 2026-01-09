<?php
/**
 * Clase Conexion
 * Centraliza todas las conexiones a bases de datos
 * Implementa patrón Singleton para evitar conexiones duplicadas
 */

class Conexion
{
    private static $instance = null;
    private static $mainConnection = null;
    private static $cdrConnections = [];
    
    /**
     * Configuración de la base de datos principal
     * Los parámetros se definen en app/config/config.php según el entorno
     */
    private static function getMainConfig()
    {
        return [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'charset' => DB_CHARSET
        ];
    }
    
    /**
     * Configuración de las bases de datos CDR
     */
    private static function getCdrConfig()
    {
        return [
            'sw1' => [
                'host' => 'sw1.bestvoiper.com',
                'name' => 'vos3000',
                'user' => 'developers',
                'pass' => 'Luisda0806*++',
                'prefix' => 'e_cdr_'
            ],
            'sw2' => [
                'host' => 'sw2.bestvoiper.com',
                'name' => 'vos3000',
                'user' => 'developers',
                'pass' => 'Luisda0806*++',
                'prefix' => 'e_cdr_'
            ],
            'sw3' => [
                'host' => 'sw3.bestvoiper.com',
                'name' => 'vos3000',
                'user' => 'developers',
                'pass' => 'Luisda0806*++',
                'prefix' => 'e_cdr_'
            ],
            'sw4' => [
                'host' => 'sw4.bestvoiper.com',
                'name' => 'vos3000',
                'user' => 'developers',
                'pass' => 'Luisda0806*++',
                'prefix' => 'e_cdr_'
            ]

        ];
    }
    
    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {}
    
    /**
     * Evitar clonación (Singleton)
     */
    private function __clone() {}
    
    /**
     * Obtener instancia única
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener conexión a la base de datos principal
     * @return PDO
     */
    public static function getMain()
    {
        if (self::$mainConnection === null) {
            $config = self::getMainConfig();

            try {
                $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}";
                self::$mainConnection = new PDO($dsn, $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                error_log("Error de conexión principal: " . $e->getMessage());
                die("Error de conexión a la base de datos principal.");
            }
        }
        return self::$mainConnection;
    }
    
    /**
     * Obtener conexión a una base de datos CDR específica
     * @param string $key Clave del servidor (sw1, sw2, sw3)
     * @return array|null ['connection' => PDO, 'prefix' => string]
     */
    public static function getCDR($key)
    {
        if (!isset(self::$cdrConnections[$key])) {
            $config = self::getCdrConfig();
            
            if (!isset($config[$key])) {
                error_log("Configuración CDR no encontrada: {$key}");
                return null;
            }
            
            $db = $config[$key];
            
            try {
                $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => true,  // Conexión persistente
                    PDO::ATTR_TIMEOUT => 5,        // Timeout reducido a 5 segundos
                    PDO::ATTR_EMULATE_PREPARES => true,  // Mejora rendimiento para consultas simples
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    PDO::MYSQL_ATTR_COMPRESS => true,  // Compresión de datos
                ]);
                self::$cdrConnections[$key] = [
                    'connection' => $pdo,
                    'prefix' => $db['prefix']
                ];
            } catch (PDOException $e) {
                error_log("Error conectando a CDR {$key}: " . $e->getMessage());
                return null;
            }
        }
        
        return self::$cdrConnections[$key];
    }
    
    /**
     * Obtener todas las conexiones CDR disponibles
     * @return array
     */
    public static function getAllCDR()
    {
        $config = self::getCdrConfig();
        $connections = [];
        
        foreach (array_keys($config) as $key) {
            $conn = self::getCDR($key);
            if ($conn !== null) {
                $connections[$key] = $conn;
            }
        }
        
        return $connections;
    }
    
    /**
     * Obtener lista de servidores CDR configurados
     * @return array
     */
    public static function getCDRServers()
    {
        return array_keys(self::getCdrConfig());
    }
    
    /**
     * Verificar si un servidor CDR está disponible
     * @param string $key
     * @return bool
     */
    public static function isCDRAvailable($key)
    {
        $conn = self::getCDR($key);
        return $conn !== null;
    }
    
    /**
     * Cerrar todas las conexiones
     */
    public static function closeAll()
    {
        self::$mainConnection = null;
        self::$cdrConnections = [];
    }
    
    /**
     * Cerrar conexión CDR específica
     * @param string $key
     */
    public static function closeCDR($key)
    {
        if (isset(self::$cdrConnections[$key])) {
            unset(self::$cdrConnections[$key]);
        }
    }
}
