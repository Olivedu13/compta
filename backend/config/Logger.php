<?php
/**
 * Logger simple pour journaliser les événements et erreurs
 */

namespace App\Config;

class Logger {
    private static $logDir = __DIR__ . '/../logs';
    
    public static function init() {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    /**
     * Enregistre un message INFO
     */
    public static function info($message, $data = []) {
        self::log('INFO', $message, $data);
    }
    
    /**
     * Enregistre un message ERROR
     */
    public static function error($message, $data = []) {
        self::log('ERROR', $message, $data);
    }
    
    /**
     * Enregistre un message WARNING
     */
    public static function warning($message, $data = []) {
        self::log('WARNING', $message, $data);
    }
    
    /**
     * Enregistre un message DEBUG (seulement en développement)
     */
    public static function debug($message, $data = []) {
        if (getenv('ENV') === 'development') {
            self::log('DEBUG', $message, $data);
        }
    }
    
    /**
     * Fonction générique de logging
     */
    private static function log($level, $message, $data = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logDir . '/' . date('Y-m-d') . '.log';
        
        $logEntry = "[$timestamp] [$level] $message";
        if (!empty($data)) {
            $logEntry .= " | " . json_encode($data);
        }
        $logEntry .= "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        error_log($logEntry);
    }
}

// Initialise le logger au chargement
Logger::init();
