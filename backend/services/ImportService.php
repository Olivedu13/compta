<?php
/**
 * Service d'import de fichiers comptables (Excel, FEC, Archives)
 * Utilise le streaming pour minimiser l'empreinte mémoire
 */

namespace App\Services;

use App\Config\Database;
use App\Config\Logger;

class ImportService {
    private $db;
    private $maxRows = 50000; // Traite par batch pour éviter les timeouts
    private $exercice;
    private $fecAnalyzer;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->exercice = (int) date('Y');
        $this->fecAnalyzer = new FecAnalyzer();
    }
    
    /**
     * Analyse un fichier FEC AVANT import
     * Retourne les informations structurées et détecte les anomalies
     * 
     * @param string $filePath Chemin du fichier FEC
     * @return array Analyse complète du FEC
     */
    public function analyzeFEC($filePath) {
        return $this->fecAnalyzer->analyze($filePath);
    }
    
    /**
     * Import Excel (.xlsx) - Version Simplifiée
     * Utilise une méthode basée sur ZIP et XML sans dépendances externes
     * 
     * @param string $filePath Chemin du fichier Excel
     * @param string $sheetName Nom de la feuille à importer
     * @return array ['success' => bool, 'count' => int, 'message' => string]
     */
    public function importExcel($filePath, $sheetName = null) {
        if (!file_exists($filePath)) {
            throw new \Exception("Fichier non trouvé: $filePath");
        }
        
        Logger::info("Début import Excel", ['file' => $filePath, 'size' => filesize($filePath)]);
        
        try {
            // Ouvre le fichier XLSX comme une archive ZIP
            $zip = new \ZipArchive();
            $openResult = $zip->open($filePath);
            
            if ($openResult !== true) {
                $errorMsg = "Erreur ZIP: " . $this->getZipErrorMessage($openResult) . " (Fichier: " . $filePath . ")";
                Logger::error($errorMsg);
                throw new \Exception($errorMsg);
            }
            
            // Lit le fichier de workbook.xml
            $xmlFile = $zip->getFromName('xl/workbook.xml');
            if (!$xmlFile) {
                $zip->close();
                throw new \Exception("Fichier workbook.xml non trouvé - Fichier Excel invalide");
            }
            
            $xml = simplexml_load_string($xmlFile);
            $sheetId = 1; // Par défaut première feuille
            
            // Lit les données de la première feuille
            $sheetFile = $zip->getFromName('xl/worksheets/sheet' . $sheetId . '.xml');
            if (!$sheetFile) {
                $zip->close();
                throw new \Exception("Fichier feuille sheet$sheetId.xml non trouvé");
            }
            
            // Lit la table de shared strings pour les valeurs texte
            $sharedStrings = [];
            $stringFile = $zip->getFromName('xl/sharedStrings.xml');
            if ($stringFile) {
                $stringXml = simplexml_load_string($stringFile);
                foreach ($stringXml->si as $si) {
                    $sharedStrings[] = (string) $si->t;
                }
            }
            
            // Parse les lignes du fichier feuille
            $sheetXml = simplexml_load_string($sheetFile);
            if (!$sheetXml) {
                $zip->close();
                throw new \Exception("Impossible de parser le fichier feuille XML");
            }
            
            $rowCount = 0;
            $batch = [];
            $isHeaderRow = true;
            $headers = [];
            
            foreach ($sheetXml->sheetData->row as $row) {
                $rowData = [];
                
                foreach ($row->c as $cell) {
                    $cellValue = null;
                    
                    // Récupère la valeur de la cellule
                    if ((string) $cell['t'] === 's') {
                        // Cellule texte (indexe dans sharedStrings)
                        $idx = (int) $cell->v;
                        $cellValue = $sharedStrings[$idx] ?? '';
                    } else {
                        // Cellule numérique ou autre
                        $cellValue = (string) $cell->v;
                    }
                    
                    $rowData[] = $cellValue;
                }
                
                if ($isHeaderRow) {
                    $headers = $rowData;
                    $isHeaderRow = false;
                    continue;
                }
                
                if (!empty(array_filter($rowData))) {
                    try {
                        $mappedRow = $this->mapExcelRow($rowData, $headers);
                        if ($mappedRow) {
                            $batch[] = $mappedRow;
                            
                            if (count($batch) >= 100) {
                                $this->insertBatchEcritures($batch);
                                $batch = [];
                            }
                            $rowCount++;
                        }
                    } catch (\Exception $e) {
                        Logger::warning("Erreur import ligne", ['error' => $e->getMessage()]);
                    }
                }
            }
            
            // Insère les dernières lignes
            if (!empty($batch)) {
                $this->insertBatchEcritures($batch);
            }
            
            $zip->close();
            
            Logger::info("Import Excel réussi", ['count' => $rowCount]);
            
            return [
                'success' => true,
                'count' => $rowCount,
                'message' => "$rowCount lignes importées avec succès"
            ];
            
        } catch (\Exception $e) {
            Logger::error("Erreur import Excel", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Retourne le message d'erreur ZipArchive
     */
    private function getZipErrorMessage($code) {
        switch ($code) {
            case \ZipArchive::ER_OK:
                return 'No error';
            case \ZipArchive::ER_MULTIDISK:
                return 'Multi-disk zip archives not supported';
            case \ZipArchive::ER_RENAME:
                return 'Renaming temporary file failed';
            case \ZipArchive::ER_CLOSE:
                return 'Closing zip archive failed';
            case \ZipArchive::ER_SEEK:
                return 'Seeking zip file failed';
            case \ZipArchive::ER_READ:
                return 'Reading zip file failed';
            case \ZipArchive::ER_WRITE:
                return 'Writing zip file failed';
            case \ZipArchive::ER_CRC:
                return 'CRC error';
            case \ZipArchive::ER_ZIPCLOSED:
                return 'Containing zip archive was closed';
            case \ZipArchive::ER_NOENT:
                return 'No such file or directory';
            case \ZipArchive::ER_EXISTS:
                return 'File already exists';
            case \ZipArchive::ER_OPEN:
                return 'Can\'t open file';
            case \ZipArchive::ER_TMPOPEN:
                return 'Failure to create temporary file';
            case \ZipArchive::ER_ZLIB:
                return 'Zlib error';
            case \ZipArchive::ER_MEMORY:
                return 'Malloc failure';
            case \ZipArchive::ER_CHANGED:
                return 'Entry has been changed';
            case \ZipArchive::ER_COMPNOTSUPP:
                return 'Compression method not supported';
            case \ZipArchive::ER_EOF:
                return 'Premature EOF';
            case \ZipArchive::ER_INVAL:
                return 'Invalid argument';
            case \ZipArchive::ER_NOZIP:
                return 'Not a zip archive';
            case \ZipArchive::ER_INTERNAL:
                return 'Internal error';
            case \ZipArchive::ER_INCONS:
                return 'Zip archive inconsistent';
            case \ZipArchive::ER_REMOVE:
                return 'Can\'t remove file';
            case \ZipArchive::ER_DELETED:
                return 'Entry has been deleted';
            default:
                return 'Unknown error (' . $code . ')';
        }
    }
    
    /**
     * Import FEC (.txt/.csv) - Format obligatoire audit
     * Détecte automatiquement le séparateur (Tab vs Pipe)
     * Ignore les lignes de metadata (Raison sociale, SIREN, etc.)
     * 
     * @param string $filePath Chemin du fichier FEC
     * @return array ['success' => bool, 'count' => int, 'errors' => array]
     */
    public function importFEC($filePath) {
        if (!file_exists($filePath)) {
            throw new \Exception("Fichier FEC non trouvé: $filePath");
        }
        
        Logger::info("Début import FEC", ['file' => $filePath]);
        
        try {
            // Lit le fichier en mémoire pour détecter la structure
            $lines = file($filePath, FILE_SKIP_EMPTY_LINES);
            if (empty($lines)) {
                throw new \Exception("Fichier FEC vide");
            }
            
            // Détecte le séparateur sur les lignes avec le plus de champs
            $separator = $this->detectFecSeparator($lines);
            Logger::debug("Séparateur FEC détecté", ['separator' => $separator === "\t" ? 'TAB' : '|']);
            
            // Trouve la ligne d'en-tête (contient "JournalCode" ou équivalent)
            $headerLineIdx = $this->findFecHeaderLine($lines, $separator);
            if ($headerLineIdx === -1) {
                throw new \Exception("En-tête FEC non trouvé (cherche 'JournalCode' ou équivalent)");
            }
            
            Logger::debug("En-tête FEC trouvé", ['line_number' => $headerLineIdx + 1]);
            
            // Parse l'en-tête
            $headerLine = trim($lines[$headerLineIdx]);
            $headers = str_getcsv($headerLine, $separator);
            $headers = array_map(function($h) { return trim(strtolower($h)); }, $headers);
            
            Logger::debug("Colonnes détectées", ['count' => count($headers), 'headers' => implode(',', array_slice($headers, 0, 5))]);
            
            // ÉTAPE 1 : Scanne le FEC pour récupérer tous les comptes uniques
            Logger::info("Étape 1/2 : Scan des comptes du FEC...");
            $comptesUniques = $this->scanFecAccounts($lines, $headerLineIdx, $separator, $headers);
            Logger::info("Comptes uniques trouvés", ['count' => count($comptesUniques), 'comptes' => implode(', ', array_slice(array_keys($comptesUniques), 0, 10))]);
            
            // ÉTAPE 2 : Crée les comptes racine manquants
            Logger::info("Étape 2/2 : Création des comptes racine manquants...");
            $this->createMissingRootAccounts($comptesUniques);
            
            // ÉTAPE 3 : Import normal des écritures
            $rowCount = 0;
            $errorCount = 0;
            $batch = [];
            $exerciceDetecte = null; // Sera défini à partir du premier FEC
            $exerciceDeleted = false; // Flag pour supprimer une seule fois par exercice
            
            // Traite les lignes après l'en-tête
            for ($i = $headerLineIdx + 1; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (empty($line)) continue;
                
                // Parse la ligne avec le séparateur détecté
                $fields = str_getcsv($line, $separator);
                
                try {
                    // Valide et mappe les 18 champs obligatoires du FEC
                    $rowData = $this->mapFecRow($fields, $headers);
                    
                    if ($rowData) {
                        // Détecte l'exercice réel depuis la première ligne (très important !)
                        if ($exerciceDetecte === null && isset($rowData['exercice'])) {
                            $exerciceDetecte = $rowData['exercice'];
                            Logger::info("Exercice détecté depuis FEC", ['exercice' => $exerciceDetecte]);
                            
                            // SUPPRIME LES ÉCRITURES FEC EXISTANTES POUR CET EXERCICE AVANT D'IMPORTER LES NOUVELLES
                            if (!$exerciceDeleted) {
                                Logger::info("Suppression des écritures existantes", ['exercice' => $exerciceDetecte]);
                                $this->db->query(
                                    "DELETE FROM ecritures WHERE exercice = ?",
                                    [$exerciceDetecte]
                                );
                                Logger::info("Écritures supprimées", ['exercice' => $exerciceDetecte]);
                                $exerciceDeleted = true;
                            }
                        }
                        
                        $batch[] = $rowData;
                        
                        // Batch insert toutes les 500 lignes
                        if (count($batch) >= 500) {
                            $this->insertBatchEcritures($batch);
                            $batch = [];
                        }
                        $rowCount++;
                    }
                } catch (\Exception $e) {
                    Logger::warning("Erreur ligne FEC " . ($i + 1), ['error' => $e->getMessage(), 'line_content' => substr($line, 0, 100)]);
                    $errorCount++;
                }
            }
            
            // Traite les dernières lignes
            if (!empty($batch)) {
                $this->insertBatchEcritures($batch);
            }
            
            Logger::info("Import FEC terminé", ['rows' => $rowCount, 'errors' => $errorCount]);
            
            // Utilise l'exercice détecté du FEC (ou default si non trouvé)
            $exerciceImport = $exerciceDetecte ?? $this->exercice;
            
            // Note: La table fin_balance n'existe que dans le nouveau schéma
            // Pour l'ancien schéma (ecritures), pas d'agrégation nécessaire
            
            return [
                'success' => true,
                'count' => $rowCount,
                'errors' => $errorCount,
                'accounts_created' => count($comptesUniques),
                'message' => "$rowCount écritures FEC importées (" . count($comptesUniques) . " comptes créés)"
            ];
            
        } catch (\Exception $e) {
            Logger::error("Erreur import FEC", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Agrège les écritures du FEC en balance
     * Groupe par compte et calcule débit/crédit/solde
     * 
     * @param int $exercice Année comptable
     * @return array Nombre de lignes agrégées
     */
    public function aggregateBalance($exercice) {
        Logger::info("Début agrégation balance", ['exercice' => $exercice]);
        
        try {
            // Supprime l'ancienne balance pour cet exercice
            $this->db->query(
                "DELETE FROM fin_balance WHERE exercice = ?",
                [$exercice]
            );
            Logger::debug("Ancienne balance supprimée", ['exercice' => $exercice]);
            
            // Agrège les écritures par compte et insère dans la balance
            $sql = "
                INSERT INTO fin_balance (exercice, compte_num, debit, credit, solde, date_import)
                SELECT 
                    exercice,
                    compte_num,
                    SUM(debit) as debit,
                    SUM(credit) as credit,
                    SUM(debit) - SUM(credit) as solde,
                    NOW() as date_import
                FROM fin_ecritures_fec
                WHERE exercice = ?
                GROUP BY exercice, compte_num
                ON DUPLICATE KEY UPDATE
                    debit = VALUES(debit),
                    credit = VALUES(credit),
                    solde = VALUES(solde),
                    date_import = NOW()
            ";
            
            $this->db->query($sql, [$exercice]);
            
            // Compte les lignes créées/mises à jour
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM fin_balance WHERE exercice = ?",
                [$exercice]
            );
            
            $count = $result['count'] ?? 0;
            Logger::info("Agrégation balance terminée", ['exercice' => $exercice, 'lignes' => $count]);
            
            return [
                'success' => true,
                'aggregated_lines' => $count,
                'exercice' => $exercice
            ];
            
        } catch (\Exception $e) {
            Logger::error("Erreur agrégation balance", ['error' => $e->getMessage(), 'exercice' => $exercice]);
            throw $e;
        }
    }
    
    /**
     * Import d'archives (.tar, .tar.gz)
     * Extrait temporairement et traite les fichiers
     * 
     * @param string $filePath Chemin de l'archive
     * @return array Résultat de l'import
     */
    public function importArchive($filePath) {
        if (!file_exists($filePath)) {
            throw new \Exception("Archive non trouvée: $filePath");
        }
        
        Logger::info("Début import archive", ['file' => $filePath]);
        
        try {
            // Utilise PharData pour extraire (pas de dépendance système)
            $phar = new \PharData($filePath);
            $tempDir = sys_get_temp_dir() . '/compta_import_' . uniqid();
            $phar->extractTo($tempDir);
            
            $results = [];
            
            // Récursive : cherche les fichiers .xlsx, .txt, .csv
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $file) {
                if ($file->isDir()) continue;
                
                $ext = strtolower($file->getExtension());
                
                try {
                    if ($ext === 'xlsx') {
                        $results[] = $this->importExcel($file->getRealPath());
                    } elseif (in_array($ext, ['txt', 'csv'])) {
                        $results[] = $this->importFEC($file->getRealPath());
                    }
                } catch (\Exception $e) {
                    Logger::error("Erreur traitement fichier archive", ['file' => $file->getFilename(), 'error' => $e->getMessage()]);
                }
            }
            
            // Nettoie le répertoire temporaire
            $this->recursiveRmdir($tempDir);
            
            Logger::info("Import archive terminé", ['files_processed' => count($results)]);
            
            return [
                'success' => true,
                'files_processed' => count($results),
                'results' => $results,
                'message' => count($results) . ' fichiers traités'
            ];
            
        } catch (\Exception $e) {
            Logger::error("Erreur import archive", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Détecte le séparateur du FEC (Tabulation vs Pipe)
     * Scanne plusieurs lignes pour trouver le séparateur le plus cohérent
     */
    private function detectFecSeparator($lines) {
        if (is_string($lines)) {
            $lines = [$lines];
        }
        
        $tabTotal = 0;
        $pipeTotal = 0;
        
        // Scanne les 20 premières lignes non-vides
        $count = 0;
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $tabTotal += substr_count($line, "\t");
            $pipeTotal += substr_count($line, "|");
            $count++;
            if ($count >= 20) break;
        }
        
        // Le FEC contient généralement 18 colonnes (17 séparateurs)
        // Donc, le bon séparateur aura plus d'occurrences
        return $tabTotal > $pipeTotal ? "\t" : "|";
    }
    
    /**
     * Trouve la ligne d'en-tête du FEC
     * Cherche la première ligne qui commence par "JournalCode"
     */
    private function findFecHeaderLine($lines, $separator) {
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Normalise la ligne
            $normalized = preg_replace('/[^a-z0-9\t|]/', '', strtolower($line));
            
            // Si la ligne commence par "journalcode" (ou variantes), c'est l'en-tête
            if (strpos($normalized, 'journalcode') === 0) {
                // Vérifie qu'il y a 18 colonnes
                $fields = str_getcsv($line, $separator);
                if (count($fields) === 18) {
                    return $i;
                }
            }
        }
        
        return -1;
    }
    
    /**
     * Mappe une ligne Excel aux champs FEC
     */
    private function mapExcelRow($cells, $headers) {
        $row = [];
        
        foreach ($headers as $idx => $header) {
            $headerName = trim((string) $header);
            if (isset($cells[$idx])) {
                $row[$headerName] = (string) $cells[$idx];
            }
        }
        
        // Valide et structure selon les 18 champs obligatoires
        return $this->validateFecFields($row);
    }
    
    /**
     * Mappe une ligne FEC aux champs de la BD
     * Flexible sur les noms de colonnes
     */
    private function mapFecRow($fields, $headers) {
        $row = [];
        
        // Mappe les colonnes avec flexibilité sur les noms
        foreach ($headers as $idx => $header) {
            $headerNorm = trim(strtolower(preg_replace('/[^a-z0-9_]/', '', $header)));
            if (isset($fields[$idx])) {
                $row[$headerNorm] = trim($fields[$idx]);
            }
        }
        
        return $this->validateFecFields($row);
    }
    
    /**
     * Valide et transforme les champs FEC en structure DB
     * Les 18 champs obligatoires :
     * JournalCode, JournalLib, EcritureNum, EcritureDate, CompteNum, CompteLib,
     * CompAuxNum, CompAuxLib, PieceRef, PieceDate, EcritureLib,
     * Debit, Credit, EcritureLet, DateLet, ValidDate, MontantDevise, IdDevise
     */
    private function validateFecFields($row) {
        // Normalise les clés - supprime underscores et espaces
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalized = preg_replace('/[_\s]/', '', strtolower($key));
            $normalizedRow[$normalized] = $value;
        }
        $row = $normalizedRow;
        
        // Champs obligatoires - avec variations de noms acceptées
        $required = [
            'journalcode' => ['journalcode'],
            'ecriturenum' => ['ecriturenum', 'ecriturenumber'],
            'ecrituredate' => ['ecrituredate', 'ecriture_date'],
            'comptenum' => ['comptenum', 'accountnumber'],
            'debit' => ['debit'],
            'credit' => ['credit']
        ];
        
        foreach ($required as $field => $variants) {
            $found = false;
            foreach ($variants as $variant) {
                if (isset($row[$variant]) && $row[$variant] !== '') {
                    $row[$field] = $row[$variant];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new \Exception("Champ obligatoire manquant: $field (cherché: " . implode(', ', $variants) . ")");
            }
        }
        
        // Valide les dates - FEC format: AAAAMMJJ (ex: 20240101)
        $dateStr = $row['ecrituredate'];
        $eDate = $this->parseFecDate($dateStr);
        if (!$eDate) {
            throw new \Exception("Format date invalide (attendu AAAAMMJJ): " . $dateStr);
        }
        
        // Valide les montants (convertit , en . si nécessaire)
        $debit = (float) str_replace(',', '.', $row['debit'] ?? '0');
        $credit = (float) str_replace(',', '.', $row['credit'] ?? '0');
        
        // Parse les autres dates optionnelles au format AAAAMMJJ
        $pieceDate = isset($row['piecedate']) && !empty($row['piecedate']) 
            ? $this->parseFecDate($row['piecedate'])?->format('Y-m-d')
            : null;
        $dateLet = isset($row['datelet']) && !empty($row['datelet'])
            ? $this->parseFecDate($row['datelet'])?->format('Y-m-d')
            : null;
        $validDate = isset($row['validdate']) && !empty($row['validdate'])
            ? $this->parseFecDate($row['validdate'])?->format('Y-m-d')
            : null;
        
        // Structure pour insertion DB
        return [
            'journal_code' => $row['journalcode'] ?? '',
            'journal_lib' => $row['journalllib'] ?? '',
            'ecriture_num' => $row['ecriturenum'],
            'ecriture_date' => $eDate->format('Y-m-d'),
            'compte_num' => $row['comptenum'],
            'compte_lib' => $row['comptelib'] ?? '',
            'comp_aux_num' => $row['compauxnum'] ?? null,
            'comp_aux_lib' => $row['compauxlib'] ?? null,
            'piece_ref' => $row['pieceref'] ?? null,
            'piece_date' => $pieceDate,
            'ecriture_lib' => $row['ecriturelib'] ?? '',
            'debit' => $debit,
            'credit' => $credit,
            'ecriture_let' => $row['ecriturelet'] ?? null,
            'date_let' => $dateLet,
            'valid_date' => $validDate,
            'montant_devise' => str_replace(',', '.', $row['montantdevise'] ?? '0'),
            'id_devise' => $row['idevise'] ?? 'EUR',
            'exercice' => (int) substr($eDate->format('Y-m-d'), 0, 4)
        ];
    }
    
    /**
     * Parse une date FEC au format AAAAMMJJ en DateTime
     * Exemple: "20240101" -> DateTime(2024-01-01)
     */
    private function parseFecDate($dateStr) {
        $dateStr = trim((string) $dateStr);
        if (empty($dateStr)) {
            return null;
        }
        
        // Format FEC standard: AAAAMMJJ
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $dateStr, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            
            $date = \DateTime::createFromFormat('Y-m-d', "$year-$month-$day");
            if (!$date) {
                return null;
            }
            return $date;
        }
        
        return null;
    }

    
    /**
     * Insère un batch d'écritures en une seule requête
     * Utilise INSERT IGNORE pour éviter les doublons
     */
    private function insertBatchEcritures($batch) {
        if (empty($batch)) return;
        
        $columns = array_keys($batch[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        
        // Utilise INSERT au lieu de INSERT IGNORE pour attraper les vrais erreurs
        $sql = "INSERT INTO ecritures (" . implode(', ', $columns) . ") VALUES ";
        $sql .= implode(', ', array_fill(0, count($batch), $placeholders));
        
        $values = [];
        foreach ($batch as $row) {
            $values = array_merge($values, array_values($row));
        }
        
        try {
            Logger::debug("Insertion batch écritures", ['batch_size' => count($batch), 'columns' => implode(',', array_slice($columns, 0, 5))]);
            $this->db->query($sql, $values);
            Logger::debug("Batch inséré avec succès", ['batch_size' => count($batch)]);
        } catch (\Exception $e) {
            Logger::error("Erreur insertion batch écritures", [
                'error' => $e->getMessage(),
                'batch_size' => count($batch),
                'sql_preview' => substr($sql, 0, 200)
            ]);
            throw $e;
        }
    }
    
    /**
     * Supprime récursivement un répertoire
     */
    private function recursiveRmdir($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $path = $dir . "/" . $file;
                    if (is_dir($path)) {
                        $this->recursiveRmdir($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    /**
     * Scanne le FEC pour récupérer tous les compte_num uniques avec leurs libellés
     */
    private function scanFecAccounts($lines, $headerLineIdx, $separator, $headers) {
        $comptes = [];
        
        // Trouve les index des colonnes compte_num et compte_lib
        $compteNumIdx = -1;
        $compteLibIdx = -1;
        foreach ($headers as $idx => $header) {
            $norm = preg_replace('/[_\s]/', '', strtolower($header));
            if ($norm === 'comptenum') $compteNumIdx = $idx;
            if ($norm === 'comptelib') $compteLibIdx = $idx;
        }
        
        if ($compteNumIdx === -1) {
            Logger::warning("Colonne compte_num non trouvée");
            return $comptes;
        }
        
        // Scanne toutes les lignes
        for ($i = $headerLineIdx + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $fields = str_getcsv($line, $separator);
            if (isset($fields[$compteNumIdx])) {
                $compteNum = trim($fields[$compteNumIdx]);
                if (!empty($compteNum) && !isset($comptes[$compteNum])) {
                    $compteLib = isset($fields[$compteLibIdx]) ? trim($fields[$compteLibIdx]) : '';
                    $comptes[$compteNum] = $compteLib;
                }
            }
        }
        
        return $comptes;
    }
    
    /**
     * Crée les comptes racine (3 premiers chiffres) manquants
     * Classifie automatiquement selon la classe racine
     */
    private function createMissingRootAccounts($comptesUniques) {
        $comptesRacine = [];
        
        // Extrait les comptes racine uniques (3 premiers chiffres)
        foreach (array_keys($comptesUniques) as $compteNum) {
            $racine = substr($compteNum, 0, 3);
            if (!empty($racine) && is_numeric($racine)) {
                if (!isset($comptesRacine[$racine])) {
                    $comptesRacine[$racine] = $comptesUniques[$compteNum];
                }
            }
        }
        
        Logger::debug("Comptes racine à créer", ['count' => count($comptesRacine), 'comptes' => implode(', ', array_keys($comptesRacine))]);
        
        // Vérifie quels comptes existent déjà
        try {
            $racineKeys = array_keys($comptesRacine);
            $placeholders = implode(',', array_fill(0, count($racineKeys), '?'));
            $existing = $this->db->fetchAll(
                "SELECT compte_num FROM sys_plan_comptable WHERE compte_num IN ($placeholders)",
                $racineKeys
            );
            $existingComptes = array_column($existing, 'compte_num');
            
            // Crée les comptes manquants
            foreach ($comptesRacine as $racine => $libelle) {
                if (!in_array($racine, $existingComptes)) {
                    $classeRacine = substr($racine, 0, 1);
                    $typeCompte = $this->getCompteType($classeRacine);
                    
                    try {
                        $this->db->query(
                            "INSERT INTO sys_plan_comptable (compte_num, libelle, classe_racine, type_compte, is_actif) 
                             VALUES (?, ?, ?, ?, TRUE)",
                            [$racine, $libelle, $classeRacine, $typeCompte]
                        );
                        Logger::debug("Compte créé", ['compte' => $racine, 'classe' => $classeRacine, 'libelle' => $libelle]);
                    } catch (\Exception $e) {
                        Logger::warning("Impossible de créer compte", ['compte' => $racine, 'error' => $e->getMessage()]);
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::warning("Erreur création comptes racine", ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Détermine le type de compte selon la classe racine (premier chiffre)
     */
    private function getCompteType($classeRacine) {
        $types = [
            '1' => 'Actif',
            '2' => 'Actif',
            '3' => 'Actif',
            '4' => 'Passif',
            '5' => 'Trésorerie',
            '6' => 'Charge',
            '7' => 'Produit',
            '8' => 'Compte spécial',
            '9' => 'Compte analytique'
        ];
        
        return $types[$classeRacine] ?? 'Autre';
    }
}
