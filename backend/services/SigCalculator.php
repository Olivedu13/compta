<?php
/**
 * SigCalculator - Calcule les SIG (Soldes Intermédiaires de Gestion)
 * Expertise comptable PCG 2025 - Bijouterie
 * 
 * Cascade des SIG :
 * 1. Marge de Production = (70+71+72) - (601+602+/- Variation 603)
 * 2. Valeur Ajoutée = Marge - (61+62)
 * 3. EBE (EBITDA) = VA + 74 - 64 - 63
 * 4. Résultat d'Exploitation = EBE - 681 (Amortissements)
 * 5. Résultat Financier = +/- 69 (Intérêts) +/- 74 (Produits financiers)
 * 6. Résultat Net = Résultat Exploitation + Résultat Financier
 * 
 * IMPORTANT : Gestion des signes comptables
 * - Dans la source : Solde Négatif = Crédit, Solde Positif = Débit
 * - Pour l'affichage : Convertis en valeurs absolues
 * - Pour les calculs : Respecte la logique comptable
 */

namespace App\Services;

use App\Config\Database;
use App\Config\Logger;

class SigCalculator {
    private $db;
    private $exercice;
    private $balances = []; // Cache des soldes par compte
    
    public function __construct($exercice = null) {
        $this->db = Database::getInstance();
        $this->exercice = $exercice ?? (int) date('Y');
    }
    
    /**
     * Charge les soldes de la balance en mémoire
     * Minimise les requêtes DB
     */
    private function loadBalances() {
        if (!empty($this->balances)) return;
        
        $rows = $this->db->fetchAll(
            "SELECT compte_num, debit, credit, solde FROM fin_balance WHERE exercice = ?",
            [$this->exercice]
        );
        
        foreach ($rows as $row) {
            // Stocke les valeurs avec signature comptable
            // Débit = positif, Crédit = négatif
            $solde = $row['debit'] - $row['credit'];
            $this->balances[$row['compte_num']] = [
                'debit' => (float) $row['debit'],
                'credit' => (float) $row['credit'],
                'solde' => (float) $solde
            ];
        }
    }
    
    /**
     * Récupère le solde d'un compte (valeur algébrique)
     */
    private function getSolde($compteNum) {
        $this->loadBalances();
        return $this->balances[$compteNum]['solde'] ?? 0;
    }
    
    /**
     * Agrège les soldes d'une classe de comptes
     * Ex : compte 6 = somme de 60, 61, 62, 63, 64, 65, 66, 67, 68, 69
     */
    private function sumSoldesClass($classPrefix) {
        $this->loadBalances();
        $sum = 0;
        
        foreach ($this->balances as $compte => $data) {
            if (strpos($compte, $classPrefix) === 0) {
                $sum += $data['solde'];
            }
        }
        
        return $sum;
    }
    
    /**
     * Agrège les soldes de comptes spécifiques
     * Ex : [601, 602, 603] (matières, fournitures, variation stock)
     */
    private function sumSoldes($comptes) {
        $sum = 0;
        foreach ((array) $comptes as $compte) {
            $sum += $this->getSolde($compte);
        }
        return $sum;
    }
    
    /**
     * MARGE DE PRODUCTION (première SIG)
     * Formule :
     * = (Compte 70 + 71 + 72) - (601 + 602 +/- 603 Variation Stock)
     * 
     * Classe 70-72 : Ventes de bijoux, matières, services
     * 601 : Matières premières (Or, Diamants) - CRITIQUE pour bijouterie
     * 602 : Fournitures consommables
     * 603 : Variation de stock (ajustement)
     */
    public function calculMargeProduction() {
        $produits = $this->sumSoldes(['701', '702', '703']); // Ventes + services
        
        $charges = $this->sumSoldes(['601', '602']); // Matières + fournitures
        $variationStock = $this->getSolde('603'); // Peut être + ou -
        
        $marge = $produits - ($charges + $variationStock);
        
        Logger::debug("Marge Production", [
            'produits' => $produits,
            'charges_matières' => $charges,
            'variation_stock' => $variationStock,
            'total' => $marge
        ]);
        
        return $marge;
    }
    
    /**
     * VALEUR AJOUTÉE (VA)
     * = Marge de Production - (Compte 61 + 62)
     * 
     * 61 : Sous-traitance, locations, entretien
     * 62 : Honoraires, rémunérations, publicité, transports, télécoms, etc.
     */
    public function calculValeurAjoutee() {
        $marge = $this->calculMargeProduction();
        
        $charges61 = $this->sumSoldes(['611', '612', '613', '614', '615']); // Services extérieurs
        $charges62 = $this->sumSoldes(['621', '622', '623', '624', '625', '626', '627']); // Services variés
        
        $va = $marge - ($charges61 + $charges62);
        
        Logger::debug("Valeur Ajoutée", [
            'marge' => $marge,
            'charges_61' => $charges61,
            'charges_62' => $charges62,
            'total' => $va
        ]);
        
        return $va;
    }
    
    /**
     * EBE / EBITDA (Excédent Brut d'Exploitation)
     * = VA + Compte 74 - 64 - 63
     * 
     * + 74 : Produits divers, reversements
     * - 64 : Charges de personnel (salaires + cotisations)
     * - 63 : Impôts et taxes
     */
    public function calculEBE() {
        $va = $this->calculValeurAjoutee();
        
        $autres74 = $this->getSolde('74'); // Produits divers (peut être 0)
        
        $charges64 = $this->sumSoldes(['641', '645']); // Salaires + cotisations sociales
        $charges63 = $this->sumSoldes(['631', '637']); // Impôts et taxes
        
        $ebe = $va + $autres74 - $charges64 - $charges63;
        
        Logger::debug("EBE/EBITDA", [
            'va' => $va,
            'autres_74' => $autres74,
            'charges_64' => $charges64,
            'charges_63' => $charges63,
            'total' => $ebe
        ]);
        
        return $ebe;
    }
    
    /**
     * RÉSULTAT D'EXPLOITATION
     * = EBE - 681 (Amortissements et provisions)
     */
    public function calculResultatExploitation() {
        $ebe = $this->calculEBE();
        
        $amortissements = $this->sumSoldes(['681', '685']); // Amortissements + provisions
        
        $resultat = $ebe - $amortissements;
        
        Logger::debug("Résultat Exploitation", [
            'ebe' => $ebe,
            'amortissements' => $amortissements,
            'total' => $resultat
        ]);
        
        return $resultat;
    }
    
    /**
     * RÉSULTAT FINANCIER
     * = +/- 69 (Intérêts, charges financières) +/- 74/75 (Produits financiers)
     */
    public function calculResultatFinancier() {
        $charges69 = $this->getSolde('69'); // Intérêts
        $produits74 = $this->getSolde('741'); // Produits financiers (revenus)
        $produits75 = $this->getSolde('75'); // Produits exceptionnels
        
        $resultat = $produits74 + $produits75 - $charges69;
        
        Logger::debug("Résultat Financier", [
            'charges_69' => $charges69,
            'produits_74' => $produits74,
            'produits_75' => $produits75,
            'total' => $resultat
        ]);
        
        return $resultat;
    }
    
    /**
     * RÉSULTAT NET
     * = Résultat Exploitation + Résultat Financier
     */
    public function calculResultatNet() {
        $resultatExp = $this->calculResultatExploitation();
        $resultatFin = $this->calculResultatFinancier();
        
        $resultat = $resultatExp + $resultatFin;
        
        Logger::info("Résultat Net", [
            'exploitation' => $resultatExp,
            'financier' => $resultatFin,
            'total' => $resultat
        ]);
        
        return $resultat;
    }
    
    /**
     * Générer le tableau complet de la CASCADE des SIG
     * Utile pour affichage Waterfall Chart
     */
    public function calculCascadeSIG() {
        $margeProduction = $this->calculMargeProduction();
        $va = $this->calculValeurAjoutee();
        $ebe = $this->calculEBE();
        $resultatExp = $this->calculResultatExploitation();
        $resultatFin = $this->calculResultatFinancier();
        $resultatNet = $this->calculResultatNet();
        
        return [
            'marge_production' => [
                'libelle' => 'Marge de Production',
                'valeur' => $margeProduction,
                'description' => 'Chiffre d\'affaires - Matières & Fournitures'
            ],
            'valeur_ajoutee' => [
                'libelle' => 'Valeur Ajoutée',
                'valeur' => $va,
                'description' => 'Marge - Services extérieurs & variés'
            ],
            'ebe' => [
                'libelle' => 'EBE (EBITDA)',
                'valeur' => $ebe,
                'description' => 'VA - Personnel - Impôts'
            ],
            'resultat_exploitation' => [
                'libelle' => 'Résultat d\'Exploitation',
                'valeur' => $resultatExp,
                'description' => 'EBE - Amortissements'
            ],
            'resultat_financier' => [
                'libelle' => 'Résultat Financier',
                'valeur' => $resultatFin,
                'description' => 'Produits - Charges Financières'
            ],
            'resultat_net' => [
                'libelle' => 'Résultat Net',
                'valeur' => $resultatNet,
                'description' => 'Exploitation + Financier'
            ]
        ];
    }
    
    /**
     * Retourne les KPI clés pour le Dashboard bijouterie
     * 
     * Focus spécial sur les données métier importantes :
     * - Stock Or (compte 311)
     * - Stock Diamants (compte 312)
     * - Dettes bancaires (164)
     * - Clients (411)
     * - Fournisseurs (401)
     */
    public function calculKPIs() {
        $this->loadBalances();
        
        // Stock (Actifs)
        $stockOr = abs($this->getSolde('311'));
        $stockDiamants = abs($this->getSolde('312'));
        $stockBijoux = abs($this->getSolde('313'));
        $stockTotal = $stockOr + $stockDiamants + $stockBijoux;
        
        // Trésorerie
        $banque = abs($this->getSolde('512'));
        $caisse = abs($this->getSolde('530'));
        $tresorerieTotal = $banque + $caisse;
        
        // Tiers
        $clients = abs($this->getSolde('411'));
        $fournisseurs = abs($this->getSolde('401'));
        
        // Dettes
        $dettesChortTerme = abs($this->getSolde('164'));
        
        // Ratios (simple)
        $margeProduction = $this->calculMargeProduction();
        $chiffreAffaires = $this->sumSoldes(['701', '702', '703']);
        $tauxMargeProduction = $chiffreAffaires != 0 ? ($margeProduction / $chiffreAffaires) * 100 : 0;
        
        return [
            'stock' => [
                'or' => round($stockOr, 2),
                'diamants' => round($stockDiamants, 2),
                'bijoux' => round($stockBijoux, 2),
                'total' => round($stockTotal, 2)
            ],
            'tresorerie' => [
                'banque' => round($banque, 2),
                'caisse' => round($caisse, 2),
                'total' => round($tresorerieTotal, 2)
            ],
            'tiers' => [
                'clients' => round($clients, 2),
                'fournisseurs' => round($fournisseurs, 2)
            ],
            'dettes' => [
                'court_terme' => round($dettesChortTerme, 2)
            ],
            'ratios' => [
                'taux_marge_production' => round($tauxMargeProduction, 2),
                'chiffre_affaires' => round($chiffreAffaires, 2)
            ]
        ];
    }
    
    /**
     * Formule Waterfall pour React Recharts
     * Convertit la cascade SIG en structure pour visualisation
     */
    public function getWaterfallData() {
        $cascade = $this->calculCascadeSIG();
        
        $data = [];
        $cumulatif = 0;
        
        // Ajoute le CA de base
        $ca = $this->sumSoldes(['701', '702', '703']);
        $data[] = [
            'name' => 'Chiffre d\'Affaires',
            'value' => $ca,
            'range' => [0, $ca]
        ];
        $cumulatif = $ca;
        
        // Ajoute chaque étape de la cascade
        foreach ($cascade as $sig) {
            $change = $sig['valeur'] - $cumulatif;
            
            if ($change >= 0) {
                $data[] = [
                    'name' => $sig['libelle'],
                    'value' => $change,
                    'range' => [$cumulatif, $sig['valeur']]
                ];
            } else {
                $data[] = [
                    'name' => $sig['libelle'],
                    'value' => $change,
                    'range' => [$sig['valeur'], $cumulatif]
                ];
            }
            
            $cumulatif = $sig['valeur'];
        }
        
        return $data;
    }
    
    /**
     * Format de présentation avec signes et couleurs pour UI
     */
    public function formatSIG($valeur, $seuil = 0) {
        return [
            'valeur_brute' => round($valeur, 2),
            'valeur_affichee' => number_format(abs(round($valeur, 2)), 2, ',', ' '),
            'est_positif' => $valeur >= $seuil,
            'couleur' => $valeur >= $seuil ? '#4caf50' : '#f44336', // Vert ou Rouge
            'symbole' => $valeur >= $seuil ? '+' : '-'
        ];
    }
}
