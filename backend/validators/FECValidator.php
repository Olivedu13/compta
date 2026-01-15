/**
 * FECValidator - Detecte et corrige les anomalies structurelles
 * Rend l'import robuste aux problèmes récurrents du FEC
 */

class FECValidator {
    /**
     * Valide et normalise un FEC avant import
     * Gère les anomalies structurelles courantes
     */
    public static function validateAndFixFECStructure(&$lines) {
        if (empty($lines)) {
            throw new \Exception("Fichier FEC vide");
        }
        
        $headerLine = trim($lines[0]);
        
        // Détecte le séparateur
        $separator = static::detectSeparator($headerLine);
        $headers = str_getcsv($headerLine, $separator);
        $headers = array_map(fn($h) => trim(strtolower($h)), $headers);
        
        $expectedCount = 18; // FEC standard
        $actualCount = count($headers);
        
        $issues = [];
        
        // ANOMALIE 1: Colonnes manquantes
        if ($actualCount < $expectedCount) {
            $issues['missing_columns'] = [
                'count' => $expectedCount - $actualCount,
                'expected' => $expectedCount,
                'actual' => $actualCount
            ];
            
            // Ajoute les colonnes manquantes à la fin
            $standardHeaders = [
                'journalcode', 'journallib', 'ecriturenum', 'ecrituredate',
                'comptenum', 'comptelib', 'compauxnum', 'compauxlib',
                'pieceref', 'piecedate', 'ecriturelib', 'debit', 'credit',
                'ecriturelet', 'datelet', 'validdate', 'montantdevise', 'idevise'
            ];
            
            while (count($headers) < $expectedCount) {
                $idx = count($headers);
                $headers[] = $standardHeaders[$idx] ?? 'unknown_' . $idx;
            }
        }
        
        // ANOMALIE 2: Colonnes extra
        if ($actualCount > $expectedCount) {
            $issues['extra_columns'] = [
                'count' => $actualCount - $expectedCount,
                'trimmed' => true
            ];
            $headers = array_slice($headers, 0, $expectedCount);
        }
        
        // ANOMALIE 3: Normalise chaque ligne de données
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) {
                unset($lines[$i]);
                continue;
            }
            
            $fields = str_getcsv($line, $separator);
            
            // Padding: ajoute des colonnes vides manquantes
            while (count($fields) < $expectedCount) {
                $fields[] = '';
            }
            
            // Trimming: enlève les colonnes extra
            $fields = array_slice($fields, 0, $expectedCount);
            
            // Reconstitue la ligne normalisée
            $lines[$i] = implode($separator, $fields);
        }
        
        $lines = array_values($lines); // Ré-indexe
        
        return [
            'separator' => $separator,
            'headers' => $headers,
            'expected_columns' => $expectedCount,
            'actual_columns' => $actualCount,
            'issues' => $issues,
            'line_count' => count($lines) - 1 // Exclut l'en-tête
        ];
    }
    
    private static function detectSeparator($line) {
        $countTab = count(str_getcsv($line, "\t"));
        $countPipe = count(str_getcsv($line, "|"));
        return $countTab > $countPipe ? "\t" : "|";
    }
}
