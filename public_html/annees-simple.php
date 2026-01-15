<?php
/**
 * ⚠️ DÉPRÉCIÉ - Endpoint migré vers /api/v1/
 * 
 * Ce fichier redirige automatiquement vers le nouvel endpoint
 * pour maintenir la compatibilité rétroactive.
 * 
 * Nouvel endpoint: GET /api/v1/years/list.php
 */

$queryString = http_build_query($_GET);
$newUrl = '/api/v1/years/list.php' . ($queryString ? '?' . $queryString : '');

http_response_code(301); // Moved Permanently
header('Location: ' . $newUrl);
header('X-Deprecated: true');
header('X-Migration: Endpoint moved to /api/v1/years/list.php');

exit;

