<?php
/**
 * InputValidator - Validation stricte des entrées
 * 
 * Évite injections SQL, XSS, négation de service
 */

namespace App\Config;

class InputValidator {
    /**
     * Valide et casterne un integer
     * 
     * @param mixed $value Valeur à valider
     * @param int $min Minimum (inclusif)
     * @param int $max Maximum (inclusif)
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function asInt($value, $min = PHP_INT_MIN, $max = PHP_INT_MAX) {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        
        if ($value === false) {
            throw new \InvalidArgumentException("Invalid integer value");
        }
        
        if ($value < $min || $value > $max) {
            throw new \InvalidArgumentException("Integer out of range [$min, $max]");
        }
        
        return $value;
    }
    
    /**
     * Valide et returnune année (4 chiffres)
     * 
     * @param mixed $value
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function asYear($value) {
        $value = self::asInt($value, 1900, 2100);
        return $value;
    }
    
    /**
     * Valide un numéro de compte (format PCG)
     * Format: 1-12 chiffres, pas de caractères spéciaux
     * 
     * @param mixed $value
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function asAccountNumber($value) {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Account number must be string");
        }
        
        // Trim et vérifie format
        $value = trim($value);
        if (!preg_match('/^[0-9]{1,12}$/', $value)) {
            throw new \InvalidArgumentException("Invalid account number format");
        }
        
        return $value;
    }
    
    /**
     * Valide un code journal (max 10 caractères alphanumériques)
     * 
     * @param mixed $value
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function asJournalCode($value) {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Journal code must be string");
        }
        
        $value = trim($value);
        if (!preg_match('/^[A-Z0-9]{1,10}$/i', $value)) {
            throw new \InvalidArgumentException("Invalid journal code format");
        }
        
        return strtoupper($value);
    }
    
    /**
     * Valide une date au format YYYY-MM-DD
     * 
     * @param mixed $value
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function asDate($value) {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Date must be string");
        }
        
        $value = trim($value);
        
        // Valide format ISO 8601
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new \InvalidArgumentException("Date must be format YYYY-MM-DD");
        }
        
        // Vérifie que c'est une date valide
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException("Invalid date value");
        }
        
        return $value;
    }
    
    /**
     * Valide une limite de résultats (pagination)
     * 
     * @param mixed $value
     * @param int $max Maximum autorisé
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function asLimit($value, $max = 1000) {
        $value = self::asInt($value, 1, $max);
        return $value;
    }
    
    /**
     * Valide un numéro de page (pagination)
     * 
     * @param mixed $value
     * @return int
     * @throws \InvalidArgumentException
     */
    public static function asPage($value) {
        $value = self::asInt($value, 1);
        return $value;
    }
    
    /**
     * Valide un email
     * 
     * @param mixed $value
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function asEmail($value) {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Email must be string");
        }
        
        $value = trim($value);
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format");
        }
        
        return $value;
    }
    
    /**
     * Valide un montant décimal (devise)
     * 
     * @param mixed $value
     * @param float $min Minimum
     * @param float $max Maximum
     * @return float
     * @throws \InvalidArgumentException
     */
    public static function asDecimal($value, $min = 0, $max = PHP_FLOAT_MAX) {
        // Accepte formats: 100.50, 100,50 (français)
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        
        if ($value === false) {
            throw new \InvalidArgumentException("Invalid decimal value");
        }
        
        if ($value < $min || $value > $max) {
            throw new \InvalidArgumentException("Decimal out of range [$min, $max]");
        }
        
        return $value;
    }
    
    /**
     * Valide un type MIME
     * 
     * @param string $actualMime Type MIME réel (finfo)
     * @param array $allowedMimes Types MIME autorisés
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function validateMimeType($actualMime, array $allowedMimes) {
        if (!in_array($actualMime, $allowedMimes)) {
            throw new \InvalidArgumentException("MIME type '$actualMime' not allowed");
        }
        return true;
    }
    
    /**
     * Valide la taille d'un fichier
     * 
     * @param int $fileSize Taille en octets
     * @param int $maxBytes Maximum autorisé
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function validateFileSize($fileSize, $maxBytes = 67108864) {  // 64MB default
        if ($fileSize <= 0 || $fileSize > $maxBytes) {
            throw new \InvalidArgumentException("File size exceeds limit");
        }
        return true;
    }
}
?>
