<?php
/**
 * Classe de gestion de la connexion MySQL
 * Utilise le pattern Singleton pour économiser les connexions
 */

namespace App\Config;

class Database {
    private static $instance = null;
    private $connection;
    
    private $host = 'db5019387279.hosting-data.io';
    private $db = 'dbs15168768';
    private $user = 'dbu2705925';
    private $password = 'Atc13001!74529012!';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
            
            $this->connection = new \PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_PERSISTENT => false,
                ]
            );
            
            // Désactive l'émulation de requêtes préparées (sécurité)
            $this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch (\PDOException $e) {
            error_log("Erreur DB: " . $e->getMessage());
            die(json_encode(['error' => 'Connexion DB échouée']));
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
