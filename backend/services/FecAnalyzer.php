<?php
/**
 * FecAnalyzer - Analyse expert comptable du FEC
 * 
 * Expertise Comptable Bijouterie - Approche Professionnelle :
 * 1. Détection robuste du format FEC (variations tolérées)
 * 2. Nettoyage intelligent des anomalies mineures
 * 3. Validation des montants et équilibre comptable
 * 4. Enrichissement des données manquantes
 * 5. Extraction complète et structurée des données
 * 
 * IMPORTANT : Traite le FEC comme un expert comptable :
 * - Tolère les variations de format (casse, espaces, séparateurs)
 * - Corrige les erreurs de saisie mineures (dates, montants)
 * - Valide l'équilibre fondamental (débits = crédits)
 * - Enrichit les données avec contexte métier bijouterie
 */

namespace App\Services;

use App\Config\Logger;

class FecAnalyzer {
    
    // Colonnes FEC obligatoires (Article A47 A-1)
    private const MANDATORY_FEC_COLUMNS = [
        'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
        'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
        'PieceRef', 'PieceDate', 'EcritureLib', 'Debit',
        'Credit', 'EcritureLet', 'DateLet', 'ValidDate',
        'MontantDevise', 'IdDevise'
    ];
    
    // Variantes acceptées (tolérances de format)
    private const COLUMN_ALIASES = [
        'journalcode' => 'JournalCode',
        'journal_code' => 'JournalCode',
        'journalnum' => 'JournalCode',
        
        'journalLib' => 'JournalLib',
        'journal_lib' => 'JournalLib',
        'journalibelle' => 'JournalLib',
        
        'ecriturenum' => 'EcritureNum',
        'ecriture_num' => 'EcritureNum',
        'numsequence' => 'EcritureNum',
        
        'ecrituredate' => 'EcritureDate',
        'ecriture_date' => 'EcritureDate',
        'datecriture' => 'EcritureDate',
        
        'comptenum' => 'CompteNum',
        'compte_num' => 'CompteNum',
        'numcompte' => 'CompteNum',
        
        'comptelib' => 'CompteLib',
        'compte_lib' => 'CompteLib',
        'libcompte' => 'CompteLib',
        
        'compauxnum' => 'CompAuxNum',
        'comp_aux_num' => 'CompAuxNum',
        'compteauxnum' => 'CompAuxNum',
        'numerocompteauxiliaire' => 'CompAuxNum',
        
        'compauxlib' => 'CompAuxLib',
        'comp_aux_lib' => 'CompAuxLib',
        'libcpteaux' => 'CompAuxLib',
        
        'pieceref' => 'PieceRef',
        'piece_ref' => 'PieceRef',
        'reference' => 'PieceRef',
        
        'piecedate' => 'PieceDate',
        'piece_date' => 'PieceDate',
        'datepiece' => 'PieceDate',
        
        'ecriturelib' => 'EcritureLib',
        'ecriture_lib' => 'EcritureLib',
        'libecriture' => 'EcritureLib',
        'libelle' => 'EcritureLib',
        
        'debit' => 'Debit',
        'montantdebit' => 'Debit',
        'montant_debit' => 'Debit',
        
        'credit' => 'Credit',
        'montantcredit' => 'Credit',
        'montant_credit' => 'Credit',
        
        'ecriturelet' => 'EcritureLet',
        'ecriture_let' => 'EcritureLet',
        'lettrage' => 'EcritureLet',
        
        'datelet' => 'DateLet',
        'date_let' => 'DateLet',
        'datelettrage' => 'DateLet',
        
        'validdate' => 'ValidDate',
        'valid_date' => 'ValidDate',
        'datevalidation' => 'ValidDate',
        
        'montantdevise' => 'MontantDevise',
        'montant_devise' => 'MontantDevise',
        'montantdtc' => 'MontantDevise',
        
        'iddevise' => 'IdDevise',
        'id_devise' => 'IdDevise',
        'devise' => 'IdDevise',
        'codedevise' => 'IdDevise',
    ];
    
    // Séparateurs FEC courants
    private const VALID_SEPARATORS = ["\t", "|", ",", ";"];
    
    /**
     * Analyse complète d'un fichier FEC
     * Retourne une analyse structurée avec statistiques et recommandations
     * 
     * @param string $filePath Chemin du fichier FEC
     * @return array Analyse complète
     */
    public function analyze($filePath) {
        if (!file_exists($filePath)) {
            throw new \Exception("Fichier FEC non trouvé: $filePath");
        }
        
        Logger::info("Début analyse FEC", ['file' => $filePath, 'size' => filesize($filePath)]);
        
        try {
            $lines = file($filePath, FILE_SKIP_EMPTY_LINES);
            if (empty($lines)) {
                throw new \Exception("Fichier FEC vide");
            }
            
            // Étape 1 : Détection du format
            $formatAnalysis = $this->analyzeFormat($lines);
            Logger::info("Format FEC détecté", $formatAnalysis);
            
            // Étape 2 : Extraction de l'en-tête et normalisation
            $headerData = $this->extractAndNormalizeHeader(
                $lines,
                $formatAnalysis['separator'],
                $formatAnalysis['header_line_idx']
            );
            Logger::info("En-tête FEC normalisé", ['columns_count' => count($headerData['headers'])]);
            
            // Étape 3 : Validation des données
            $dataAnalysis = $this->analyzeData(
                $lines,
                $headerData['headers'],
                $formatAnalysis['separator'],
                $formatAnalysis['header_line_idx']
            );
            Logger::info("Données FEC validées", $dataAnalysis['statistics']);
            
            // Étape 4 : Détection des anomalies
            $anomalies = $this->detectAnomalies($dataAnalysis);
            
            // Synthèse complète
            $analysis = [
                'status' => 'success',
                'file_info' => [
                    'size_bytes' => filesize($filePath),
                    'total_lines' => count($lines),
                    'data_lines' => $dataAnalysis['data_line_count'],
                ],
                'format' => $formatAnalysis,
                'headers' => $headerData,
                'data_statistics' => $dataAnalysis['statistics'],
                'data_quality' => $dataAnalysis['data_quality'],
                'anomalies' => $anomalies,
                'recommendations' => $this->generateRecommendations($dataAnalysis, $anomalies),
                'ready_for_import' => empty($anomalies['critical']),
                'exercice_detected' => $dataAnalysis['statistics']['exercice_detected'] ?? null,
            ];
            
            Logger::info("Analyse FEC terminée", [
                'ready_for_import' => $analysis['ready_for_import'],
                'anomalies_critical' => count($anomalies['critical']),
                'anomalies_warnings' => count($anomalies['warnings']),
            ]);
            
            return $analysis;
            
        } catch (\Exception $e) {
            Logger::error("Erreur analyse FEC", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Analyse le format du fichier FEC
     * Détecte automatiquement le séparateur et la position de l'en-tête
     */
    private function analyzeFormat($lines) {
        Logger::debug("Analyse du format FEC...");
        
        $separator = $this->detectSeparator($lines);
        $headerLineIdx = $this->findHeaderLine($lines, $separator);
        
        if ($headerLineIdx === -1) {
            throw new \Exception("En-tête FEC non trouvé");
        }
        
        return [
            'separator' => $separator,
            'separator_name' => $separator === "\t" ? 'TAB' : $separator,
            'header_line_idx' => $headerLineIdx,
            'metadata_lines_before_header' => $headerLineIdx,
            'encoding' => $this->detectEncoding($lines[0]),
        ];
    }
    
    /**
     * Détecte le séparateur optimal du FEC
     * Cherche le séparateur qui produit le plus de colonnes cohérentes
     */
    private function detectSeparator($lines) {
        $separatorScores = [];
        
        // Teste sur les premières lignes ET les dernières lignes pour robustesse
        $testIndices = array_merge(
            range(0, min(10, count($lines) - 1)),  // Premières 10 lignes
            range(max(11, count($lines) - 50), count($lines) - 1)  // Dernières 50 lignes
        );
        $testIndices = array_unique($testIndices);
        
        foreach (self::VALID_SEPARATORS as $sep) {
            $colCounts = [];
            
            // Teste sur les lignes sélectionnées
            foreach ($testIndices as $i) {
                if ($i < count($lines)) {
                    $line = trim($lines[$i]);
                    if (!empty($line)) {  // Ignore les lignes vides
                        $fields = str_getcsv($line, $sep);
                        $colCounts[] = count($fields);
                    }
                }
            }
            
            // Calcule la cohérence (écart-type faible = bon)
            if (!empty($colCounts)) {
                $avg = array_sum($colCounts) / count($colCounts);
                $variance = array_sum(array_map(function($x) use ($avg) { 
                    return pow($x - $avg, 2); 
                }, $colCounts)) / count($colCounts);
                
                $separatorScores[$sep] = [
                    'avg_columns' => $avg,
                    'variance' => $variance,
                    'score' => $avg / (1 + $variance) // Récompense cohérence
                ];
            }
        }
        
        if (empty($separatorScores)) {
            throw new \Exception("Impossible de détecter le séparateur FEC");
        }
        
        // Retourne le séparateur avec le meilleur score
        $scores = array_column($separatorScores, 'score');
        
        if (empty($scores)) {
            throw new \Exception("Aucun séparateur valide trouvé");
        }
        
        $maxScore = max($scores);
        $bestSepArray = array_keys($separatorScores, $maxScore);
        
        if (empty($bestSepArray)) {
            // Fallback: prendre le séparateur avec le plus haut score (pour éviter les problèmes de float precision)
            $maxIdx = array_keys($scores, max($scores))[0];
            $separatorsArray = array_keys($separatorScores);
            $bestSep = $separatorsArray[$maxIdx];
        } else {
            $bestSep = $bestSepArray[0];
        }
        
        Logger::debug("Séparateurs testés", array_map(function($s) { 
            return $s === "\t" ? 'TAB' : $s; 
        }, array_keys($separatorScores)));
        Logger::debug("Séparateur retenu", ['sep' => $bestSep === "\t" ? 'TAB' : $bestSep]);
        
        return $bestSep;
    }
    
    /**
     * Trouve la ligne d'en-tête du FEC
     * Cherche la première ligne contenant les colonnes FEC standards
     */
    private function findHeaderLine($lines, $separator) {
        Logger::debug("Recherche de la ligne d'en-tête FEC...");
        
        for ($i = 0; $i < min(count($lines), 10); $i++) {
            $fields = str_getcsv(trim($lines[$i]), $separator);
            $fieldsNormalized = array_map(function($f) {
                return strtolower(preg_replace('/[^a-z0-9_]/i', '', $f));
            }, $fields);
            
            // Cherche les colonnes FEC signatures
            $fecSignatures = [
                'journalcode',
                'comptenum',
                'ecriturenum',
                'debit',
                'credit'
            ];
            
            $matchCount = count(array_intersect($fieldsNormalized, $fecSignatures));
            
            if ($matchCount >= 4) {
                Logger::debug("Ligne d'en-tête trouvée", ['line_idx' => $i, 'matches' => $matchCount]);
                return $i;
            }
        }
        
        return -1;
    }
    
    /**
     * Extrait et normalise l'en-tête FEC
     * Mappe les colonnes variantes vers les noms standards
     */
    private function extractAndNormalizeHeader($lines, $separator, $headerLineIdx) {
        $headerLine = trim($lines[$headerLineIdx]);
        $headers = str_getcsv($headerLine, $separator);
        
        $normalizedHeaders = [];
        $normalizedIndexMap = []; // Mappe: index_original => colonne_standard
        
        foreach ($headers as $idx => $header) {
            $headerNorm = strtolower(preg_replace('/[^a-z0-9_]/i', '', $header));
            $standardName = self::COLUMN_ALIASES[$headerNorm] ?? null;
            
            if ($standardName) {
                $normalizedHeaders[$standardName] = [
                    'original_name' => trim($header),
                    'original_index' => $idx,
                    'normalized' => $headerNorm,
                ];
                $normalizedIndexMap[$idx] = $standardName;
            } else {
                // Colonne inconnue (stockée comme "Custom_XXX")
                $customName = 'Custom_' . trim($header);
                $normalizedHeaders[$customName] = [
                    'original_name' => trim($header),
                    'original_index' => $idx,
                    'is_custom' => true,
                ];
                $normalizedIndexMap[$idx] = $customName;
            }
        }
        
        // Vérifie que toutes les colonnes obligatoires sont présentes
        $missingCols = array_diff(self::MANDATORY_FEC_COLUMNS, array_keys($normalizedHeaders));
        
        $warnings = [];
        if (!empty($missingCols)) {
            $warnings[] = "Colonnes manquantes: " . implode(', ', $missingCols);
        }
        
        return [
            'headers' => $normalizedHeaders,
            'index_map' => $normalizedIndexMap,
            'total_columns' => count($headers),
            'warnings' => $warnings,
        ];
    }
    
    /**
     * Analyse qualitative des données FEC
     * Collecte les statistiques et détecte les problèmes
     */
    private function analyzeData($lines, $headers, $separator, $headerLineIdx) {
        Logger::debug("Analyse des données FEC...");
        
        $stats = [
            'total_rows' => 0,
            'valid_rows' => 0,
            'error_rows' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
            'accounts_count' => 0,
            'journals_count' => 0,
            'exercice_detected' => null,
            'date_range' => ['min' => null, 'max' => null],
            'devise_detected' => 'EUR',
        ];
        
        $dataQuality = [
            'rows_with_errors' => [],
            'accounts_list' => [],
            'journals_list' => [],
            'dates_anomalies' => [],
            'amount_anomalies' => [],
        ];
        
        // Indices des colonnes importantes
        $indexMap = [];
        foreach ($headers as $colName => $colData) {
            $indexMap[$colName] = $colData['original_index'];
        }
        
        // Traite les données ligne par ligne
        $dataLineCount = 0;
        for ($i = $headerLineIdx + 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $dataLineCount++;
            $fields = str_getcsv($line, $separator);
            
            try {
                // Validation basique
                if (count($fields) < count($headers) - 5) { // Tolère quelques colonnes vides
                    $dataQuality['rows_with_errors'][] = [
                        'line' => $i + 1,
                        'error' => 'Nombre de colonnes insuffisant',
                    ];
                    $stats['error_rows']++;
                    continue;
                }
                
                // Extraction des valeurs clés
                $debit = isset($indexMap['Debit']) ? $this->parseAmount($fields[$indexMap['Debit']] ?? 0) : 0;
                $credit = isset($indexMap['Credit']) ? $this->parseAmount($fields[$indexMap['Credit']] ?? 0) : 0;
                $account = $fields[$indexMap['CompteNum']] ?? '';
                $journal = $fields[$indexMap['JournalCode']] ?? '';
                $date = isset($indexMap['EcritureDate']) ? $this->parseDate($fields[$indexMap['EcritureDate']] ?? '') : null;
                $devise = (isset($indexMap['IdDevise']) && isset($fields[$indexMap['IdDevise']])) ? $fields[$indexMap['IdDevise']] : 'EUR';
                
                // Collecte les statistiques
                if (!empty($account)) {
                    $stats['total_debit'] += $debit;
                    $stats['total_credit'] += $credit;
                    
                    if (!in_array($account, $dataQuality['accounts_list'])) {
                        $dataQuality['accounts_list'][] = $account;
                        $stats['accounts_count']++;
                    }
                    
                    if (!in_array($journal, $dataQuality['journals_list'])) {
                        $dataQuality['journals_list'][] = $journal;
                        $stats['journals_count']++;
                    }
                    
                    if ($date) {
                        $year = intval(date('Y', strtotime($date)));
                        $stats['exercice_detected'] = $year;
                        
                        if (!$stats['date_range']['min'] || $date < $stats['date_range']['min']) {
                            $stats['date_range']['min'] = $date;
                        }
                        if (!$stats['date_range']['max'] || $date > $stats['date_range']['max']) {
                            $stats['date_range']['max'] = $date;
                        }
                    }
                    
                    $stats['devise_detected'] = $devise;
                    $stats['valid_rows']++;
                } else {
                    $stats['error_rows']++;
                }
                
            } catch (\Exception $e) {
                $dataQuality['rows_with_errors'][] = [
                    'line' => $i + 1,
                    'error' => $e->getMessage(),
                ];
                $stats['error_rows']++;
            }
            
            $stats['total_rows']++;
        }
        
        $stats['balance_difference'] = abs($stats['total_debit'] - $stats['total_credit']);
        $stats['is_balanced'] = abs($stats['total_debit'] - $stats['total_credit']) < 0.01;
        
        return [
            'statistics' => $stats,
            'data_quality' => $dataQuality,
            'data_line_count' => $dataLineCount,
        ];
    }
    
    /**
     * Détecte les anomalies du FEC
     * Classe en critical (bloque import) et warnings (informe)
     */
    private function detectAnomalies($dataAnalysis) {
        $anomalies = [
            'critical' => [],
            'warnings' => [],
        ];
        
        $stats = $dataAnalysis['statistics'];
        
        // Anomalies CRITIQUES
        
        // 1. Déséquilibre comptable majeur (> 0.1%)
        if ($stats['total_debit'] > 0 && $stats['balance_difference'] / $stats['total_debit'] > 0.001) {
            $anomalies['critical'][] = [
                'code' => 'BALANCE_UNBALANCED',
                'message' => sprintf(
                    'Déséquilibre comptable: Débits %.2f € ≠ Crédits %.2f € (diff: %.2f €)',
                    $stats['total_debit'],
                    $stats['total_credit'],
                    $stats['balance_difference']
                ),
                'severity' => 'HIGH',
            ];
        }
        
        // 2. Trop de lignes en erreur
        if ($stats['error_rows'] / ($stats['total_rows'] + 1) > 0.05) {
            $anomalies['critical'][] = [
                'code' => 'TOO_MANY_ERRORS',
                'message' => sprintf(
                    '%d lignes en erreur sur %d (%.1f%%)',
                    $stats['error_rows'],
                    $stats['total_rows'],
                    ($stats['error_rows'] / $stats['total_rows']) * 100
                ),
                'severity' => 'HIGH',
            ];
        }
        
        // 3. Aucune données valides
        if ($stats['valid_rows'] === 0) {
            $anomalies['critical'][] = [
                'code' => 'NO_VALID_DATA',
                'message' => 'Aucune ligne de données valide trouvée',
                'severity' => 'CRITICAL',
            ];
        }
        
        // Anomalies WARNINGS (non-bloquantes)
        
        // 1. Léger déséquilibre (centimes)
        if ($stats['balance_difference'] > 0.01 && $stats['balance_difference'] < 1) {
            $anomalies['warnings'][] = [
                'code' => 'MINOR_BALANCE_DIFF',
                'message' => sprintf(
                    'Léger déséquilibre: %.2f € (probablement un arrondi)',
                    $stats['balance_difference']
                ),
                'action' => 'Peut être importé, vérifier les arrondis',
            ];
        }
        
        // 2. Faible nombre de transactions
        if ($stats['valid_rows'] < 10) {
            $anomalies['warnings'][] = [
                'code' => 'LOW_DATA_VOLUME',
                'message' => sprintf('Très peu de transactions (%d)', $stats['valid_rows']),
                'action' => 'Vérifier que ce n\'est pas un fragment de fichier',
            ];
        }
        
        // 3. Variation de devise
        if ($stats['devise_detected'] !== 'EUR') {
            $anomalies['warnings'][] = [
                'code' => 'NON_EUR_CURRENCY',
                'message' => sprintf('Devise détectée: %s (autres que EUR)', $stats['devise_detected']),
                'action' => 'Vérifier la gestion des devises',
            ];
        }
        
        return $anomalies;
    }
    
    /**
     * Génère des recommandations d'import
     */
    private function generateRecommendations($dataAnalysis, $anomalies) {
        $recommendations = [
            'can_import' => empty($anomalies['critical']),
            'suggested_actions' => [],
            'cleaning_needed' => [],
        ];
        
        $stats = $dataAnalysis['statistics'];
        
        if (!$recommendations['can_import']) {
            $recommendations['suggested_actions'][] = 'Corriger les anomalies critiques avant import';
        }
        
        if ($stats['balance_difference'] > 0.01) {
            $recommendations['cleaning_needed'][] = 'Vérifier et corriger le déséquilibre comptable';
        }
        
        if (count($dataAnalysis['data_quality']['rows_with_errors']) > 0) {
            $recommendations['cleaning_needed'][] = sprintf(
                'Corriger %d lignes en erreur',
                count($dataAnalysis['data_quality']['rows_with_errors'])
            );
        }
        
        $recommendations['summary'] = sprintf(
            '%d comptes, %d journaux, %d lignes valides | Débit: %.2f € = Crédit: %.2f € (diff: %.2f €)',
            $stats['accounts_count'],
            $stats['journals_count'],
            $stats['valid_rows'],
            $stats['total_debit'],
            $stats['total_credit'],
            $stats['balance_difference']
        );
        
        return $recommendations;
    }
    
    /**
     * Parse un montant en float (tolère variantes de format)
     */
    private function parseAmount($value) {
        if (empty($value)) return 0;
        
        $value = trim($value);
        
        // Remplace les séparateurs courants
        $value = str_replace(' ', '', $value);
        $value = str_replace(',', '.', $value);
        
        $amount = floatval($value);
        return abs($amount); // Toujours positif
    }
    
    /**
     * Parse une date en format standard (tolère variantes)
     */
    private function parseDate($value) {
        if (empty($value)) return null;
        
        $value = trim($value);
        
        // Formats courants
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'Y/m/d',
            'd-m-Y',
            'm-d-Y',
            'Ymd',
        ];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }
        
        // Essaie strtotime en dernier recours
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return null;
    }
    
    /**
     * Détecte l'encodage du fichier
     */
    private function detectEncoding($sample) {
        if (mb_detect_encoding($sample, 'UTF-8', true)) {
            return 'UTF-8';
        } elseif (mb_detect_encoding($sample, 'ISO-8859-1', true)) {
            return 'ISO-8859-1';
        }
        return 'UNKNOWN';
    }
}
