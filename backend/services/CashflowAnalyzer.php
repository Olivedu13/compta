<?php
/**
 * CashflowAnalyzer Service - Phase 2
 * Calcule DSO, DPO, BFR, âges créances/dettes, créances douteuses
 */

namespace App\Services;

use App\Config\Database;
use App\Config\Logger;

class CashflowAnalyzer {
    private $db;
    private $exercice;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->exercice = (int) date('Y');
    }
    
    /**
     * DSO = (Créances clients / CA) × 365
     */
    public function calculateDSO($exercice = null) {
        $exercice = $exercice ?? $this->exercice;
        
        $creances = $this->db->query(
            "SELECT COALESCE(SUM(debit - credit), 0) as montant
             FROM fin_ecritures_fec WHERE exercice = ? AND compte_num LIKE '411%'",
            [$exercice]
        )[0]['montant'] ?? 0;
        
        $ca = $this->db->query(
            "SELECT COALESCE(SUM(credit - debit), 0) as montant
             FROM fin_ecritures_fec WHERE exercice = ? AND compte_num LIKE '70%'",
            [$exercice]
        )[0]['montant'] ?? 0;
        
        return $ca > 0 ? round(($creances / $ca) * 365, 1) : 0;
    }
    
    /**
     * DPO = (Dettes fournisseurs / Achats) × 365
     */
    public function calculateDPO($exercice = null) {
        $exercice = $exercice ?? $this->exercice;
        
        $dettes = $this->db->query(
            "SELECT COALESCE(SUM(credit - debit), 0) as montant
             FROM fin_ecritures_fec WHERE exercice = ? AND compte_num LIKE '401%'",
            [$exercice]
        )[0]['montant'] ?? 0;
        
        $achats = $this->db->query(
            "SELECT COALESCE(SUM(debit - credit), 0) as montant
             FROM fin_ecritures_fec WHERE exercice = ? 
             AND (compte_num LIKE '601%' OR compte_num LIKE '602%' OR compte_num LIKE '604%')",
            [$exercice]
        )[0]['montant'] ?? 0;
        
        return $achats > 0 ? round(($dettes / $achats) * 365, 1) : 0;
    }
    
    /**
     * BFR = DSO + Jours Stock - DPO
     */
    public function calculateBFR($exercice = null) {
        $exercice = $exercice ?? $this->exercice;
        
        $dso = $this->calculateDSO($exercice);
        $dpo = $this->calculateDPO($exercice);
        $joursStock = $this->calculateJoursStock($exercice);
        
        $bfr = $dso + $joursStock - $dpo;
        
        return [
            'valeur' => round($bfr, 1),
            'dso' => $dso,
            'jours_stock' => round($joursStock, 1),
            'dpo' => $dpo
        ];
    }
}
