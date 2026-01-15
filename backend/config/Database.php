<?php
/**
 * Classe de gestion de la connexion MySQL
 * Utilise le pattern Singleton pour économiser les connexions
 * 
 * Les credentials viennent des variables d'environnement (.env)
 * Jamais hardcodés en dur pour des raisons de sécurité
 */

namespace App\Config;

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // Lit les credentials depuis .env via putenv()
        $dbType = getenv('DB_TYPE') ?: 'mysql'; // mysql ou sqlite
        
        try {
            if ($dbType === 'sqlite') {
                // Configuration SQLite
                $dbPath = getenv('DB_PATH') ?: dirname(dirname(dirname(__FILE__))) . '/compta.db';
                $dsn = "sqlite:" . $dbPath;
                
                $this->connection = new \PDO(
                    $dsn,
                    null,
                    null,
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_PERSISTENT => false,
                    ]
                );
            } else {
                // Configuration MySQL
                $host = getenv('DB_HOST') ?: 'localhost';
                $db = getenv('DB_NAME') ?: 'compta_atc';
                $user = getenv('DB_USER') ?: 'compta_user';
                $password = getenv('DB_PASS') ?: 'password123';
                $charset = getenv('DB_CHARSET') ?: 'utf8mb4';
                
                $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
                
                $this->connection = new \PDO(
                    $dsn,
                    $user,
                    $password,
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_PERSISTENT => false,
                    ]
                );
            }
            
            // Désactive l'émulation de requêtes préparées (sécurité)
            $this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch (\PDOException $e) {
            Logger::error("Database connection error", [
                'type' => $dbType,
                'error' => $e->getMessage()
            ]);
            
            // NE PAS exposer le message d'erreur au client
            if (getenv('APP_ENV') === 'production') {
                die(json_encode(['error' => 'Database connection failed']));
            } else {
                die(json_encode(['error' => 'DB Error: ' . $e->getMessage()]));
            }
        }
    }

    
    /**
     * Retourne l'instance unique de la classe
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retourne la connexion PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Exécute une requête préparée
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres liés
     * @return \PDOStatement
     */
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Retourne une seule ligne
     */
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Retourne toutes les lignes
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Alias pour compatibilité: queryRow = fetchOne
     */
    public function queryRow($sql, $params = []) {
        return $this->fetchOne($sql, $params);
    }
    
    /**
     * Retourne les résultats directement pour un SELECT
     * Alias pour faciliter l'utilisation
     */
    public function queryResults($sql, $params = []) {
        return $this->fetchAll($sql, $params);
    }
    
    /**
     * Insère et retourne l'ID généré
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, array_values($data));
        return $this->connection->lastInsertId();
    }
    
    /**
     * Désactiver les clones et la sérialisation
     */
    private function __clone() {}
    public function __sleep() { return []; }
    public function __wakeup() {}
}
