<?php
/**
 * bootstrap.php - Proxy pour API
 * 
 * Point d'entrée pour tous les scripts API
 * Inclut le vrai bootstrap du backend
 */

// Inclure le bootstrap du backend (compatible local + Ionos)
$_bsRoot = __DIR__;
if (!file_exists($_bsRoot . '/backend/bootstrap.php')) {
    $_bsRoot = dirname($_bsRoot);
}
require_once $_bsRoot . '/backend/bootstrap.php';
