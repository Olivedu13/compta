<?php
/**
 * GET /api/v1/ai/analysis.php
 * Module d'Analyse IA — Prompt System Expert-Comptable
 * Génère la note de synthèse structurée à partir des données FEC
 * Self-contained
 * 
 * Params:
 * - exercice (required): Année comptable
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $exercice = isset($_GET['exercice']) ? (int)$_GET['exercice'] : null;
    
    if (!$exercice || $exercice < 1900 || $exercice > 2100) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required and must be a valid year');
    }
    
    // Find project root (works locally with public_html/ and on Ionos flat webroot)
    $projectRoot = dirname(dirname(dirname(__DIR__)));
    if (!file_exists($projectRoot . '/compta.db')) {
        $projectRoot = dirname($projectRoot);
    }
    $dbPath = $projectRoot . '/compta.db';
    
    if (!file_exists($dbPath)) {
        throw new Exception("Database not found");
    }
    
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // =============================================
    // SYSTEM PROMPT (XML Structure)
    // =============================================
    $systemPrompt = <<<'XML'
<financial_expert_prompt>
  <persona>
    Expert-comptable associé (Big Four, 20 ans XP) spécialisé en audit stratégique,
    commissaire aux comptes et conseil en performance financière PME/TPE.
    Secteur : Commerce de détail / Bijouterie-Joaillerie artisanale.
  </persona>
  <objective>
    Produire une note de synthèse professionnelle de niveau cabinet Big Four pour le dirigeant,
    combinant rigueur normative (PCG 2025, référentiel NEP), vision business actionnable
    et scoring de santé financière sur 100 points.
  </objective>
  <analysis_framework>
    <section id="scoring" priority="1">
      Score de Santé Financière /100 : Rentabilité (30pts), Liquidité (25pts),
      Structure (20pts), Exploitation (15pts), Qualité données (10pts).
      Feu tricolore global : vert (≥70), orange (40-69), rouge (&lt;40).
    </section>
    <section id="executive_summary" priority="2">
      Synthèse en 5 bullets max. Alertes rouges immédiates.
      Opportunités d'optimisation chiffrées. Verdict en une phrase.
    </section>
    <section id="profitability" priority="3">
      Cascade SIG complète PCG 2025 (Production → Marge → VA → EBE → RE → RN).
      Décomposition charges fixes/variables. Seuil de rentabilité et marge de sécurité.
      Benchmark secteur bijouterie (marge brute 55-65%, VA/CA 40-50%, EBE/CA 15-25%).
    </section>
    <section id="cash_flow" priority="4">
      Cycle de conversion de trésorerie (DSO + DIO - DPO).
      Analyse BFR normatif vs réel. Trésorerie nette et autonomie financière.
      Projections : nombre de jours de trésorerie restants au rythme actuel.
    </section>
    <section id="risk_matrix" priority="5">
      Matrice des risques (Probabilité × Impact) : fiscal, opérationnel, liquidité, concentration.
      Anomalies comptables détectées : écritures sans pièce, lettrage incomplet, montants aberrants.
    </section>
    <section id="monthly_dynamics" priority="6">
      Analyse de saisonnalité. Identification du meilleur / pire mois.
      Tendance : croissance, stagnation ou déclin ? Coefficient de variation du CA.
    </section>
    <section id="strategic_recommendations" priority="7">
      Plan d'actions structuré : Court terme (0-3 mois), Moyen terme (3-12 mois), Long terme (1-3 ans).
      Chaque action chiffrée avec impact estimé en €.
      Quick wins identifiés.
    </section>
  </analysis_framework>
  <sector_benchmarks>
    <benchmark sector="bijouterie_artisanale">
      <kpi name="marge_brute" min="55" target="62" unit="%" />
      <kpi name="va_ca" min="40" target="48" unit="%" />
      <kpi name="ebe_ca" min="12" target="20" unit="%" />
      <kpi name="rn_ca" min="5" target="12" unit="%" />
      <kpi name="dso" max="45" target="30" unit="jours" />
      <kpi name="dpo" min="30" target="50" unit="jours" />
      <kpi name="part_personnel_va" max="65" target="55" unit="%" />
      <kpi name="ratio_endettement" max="70" target="50" unit="%" />
    </benchmark>
  </sector_benchmarks>
  <tone_and_style>
    Professionnel, direct, pédagogique. Vocabulaire de conseil stratégique :
    "leviers d'optimisation", "tension de trésorerie", "structure de coûts",
    "résilience opérationnelle", "capacité d'autofinancement", "cash burn rate".
    Chaque constat est accompagné d'un chiffre et d'une recommandation.
    Pas de langue de bois : si c'est critique, le dire clairement.
  </tone_and_style>
  <output_format>
    Markdown structuré avec : emojis de statut (✅🟡🔴⚡),
    tableaux comparatifs (valeur vs benchmark), bullet points d'action numérotés,
    cascade SIG en bloc code, scoring en tableau, et verdict final encadré.
  </output_format>
</financial_expert_prompt>
XML;
    
    // =============================================
    // COLLECTE DES DONNÉES POUR L'ANALYSE
    // =============================================
    
    // Soldes par racine de compte
    $stmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 2) as racine2,
            SUBSTR(compte_num, 1, 3) as racine3,
            SUM(CAST(debit AS REAL)) as td,
            SUM(CAST(credit AS REAL)) as tc
        FROM ecritures WHERE exercice = ?
        GROUP BY SUBSTR(compte_num, 1, 2), SUBSTR(compte_num, 1, 3)
    ");
    $stmt->execute([$exercice]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $s2 = []; $s3 = [];
    foreach ($rows as $r) {
        $sol = (float)$r['td'] - (float)$r['tc'];
        if (!isset($s2[$r['racine2']])) $s2[$r['racine2']] = 0;
        $s2[$r['racine2']] += $sol;
        if (!isset($s3[$r['racine3']])) $s3[$r['racine3']] = 0;
        $s3[$r['racine3']] += $sol;
    }
    
    // EXCLUSION DES TOTAUX BANCAIRES (doublons)
    $exclStmt = $db->prepare("
        SELECT 
            SUBSTR(compte_num, 1, 2) as racine2,
            SUBSTR(compte_num, 1, 3) as racine3,
            SUM(CAST(debit AS REAL)) as total_debit,
            SUM(CAST(credit AS REAL)) as total_credit
        FROM ecritures
        WHERE exercice = ?
          AND (compte_num LIKE '627%' OR compte_num LIKE '661%')
          AND (UPPER(libelle_ecriture) LIKE '%ARRET%' 
               OR UPPER(libelle_ecriture) LIKE '%RESULTAT ARRET%'
               OR UPPER(libelle_ecriture) LIKE 'INTERETS/FRAIS%'
               OR UPPER(libelle_ecriture) LIKE 'INTERETS FRAIS%'
               OR UPPER(libelle_ecriture) LIKE 'INT ARRET%'
               OR libelle_ecriture LIKE 'INT_R_TS%FRAIS%'
               OR libelle_ecriture LIKE '%NTÉR%TS%FRAIS%')
        GROUP BY SUBSTR(compte_num, 1, 2), SUBSTR(compte_num, 1, 3)
    ");
    $exclStmt->execute([$exercice]);
    while ($row = $exclStmt->fetch(PDO::FETCH_ASSOC)) {
        $solde = (float)$row['total_debit'] - (float)$row['total_credit'];
        if (isset($s2[$row['racine2']])) $s2[$row['racine2']] -= $solde;
        if (isset($s3[$row['racine3']])) $s3[$row['racine3']] -= $solde;
    }

    // Calculs SIG
    $ca = -($s2['70'] ?? 0);
    $production = -(($s2['70'] ?? 0) + ($s2['71'] ?? 0) + ($s2['72'] ?? 0));
    $achats = ($s3['601'] ?? 0) + ($s3['602'] ?? 0) + ($s3['603'] ?? 0);
    $marge_prod = $production - $achats;
    $services = ($s2['61'] ?? 0) + ($s2['62'] ?? 0);
    $va = $marge_prod - $services;
    $personnel = $s2['64'] ?? 0;
    $impots = $s2['63'] ?? 0;
    $subventions = -($s2['74'] ?? 0);
    $ebe = $va + $subventions - $impots - $personnel;
    $dotations = ($s2['68'] ?? 0) + ($s2['65'] ?? 0);
    $re = $ebe - $dotations + (-(($s2['75'] ?? 0) + ($s2['78'] ?? 0) + ($s2['79'] ?? 0)));
    $rf = -($s2['76'] ?? 0) - ($s2['66'] ?? 0);
    $rn = $re + $rf + (-($s2['77'] ?? 0) - ($s2['67'] ?? 0)) - ($s3['695'] ?? 0);
    
    // Bilan simplifié
    $creances = $s3['411'] ?? 0;
    $dettes_fourn = -($s3['401'] ?? 0);
    $stocks = ($s2['31'] ?? 0) + ($s2['32'] ?? 0) + ($s2['37'] ?? 0);
    $tresorerie = ($s2['51'] ?? 0) + ($s2['53'] ?? 0);
    
    // Cycles
    $dso = $ca > 0 ? round(($creances / $ca) * 365, 1) : 0;
    $achats_tot = $achats + $services;
    $dpo = $achats_tot > 0 ? round(($dettes_fourn / $achats_tot) * 365, 1) : 0;
    $jours_stock = $achats > 0 ? round(($stocks / $achats) * 365, 1) : 0;
    $bfr = ($stocks + $creances) - $dettes_fourn;
    
    // Charges fixes vs variables
    $ch_fixes = $personnel + $impots + ($s2['66'] ?? 0) + ($s3['681'] ?? 0);
    $ch_variables = $achats + $services;
    $mscv = $ca > 0 ? ($ca - $ch_variables) / $ca : 0;
    $seuil = $mscv > 0 ? $ch_fixes / $mscv : 0;
    
    // Evolution mensuelle
    $stmt = $db->prepare("
        SELECT SUBSTR(ecriture_date, 1, 7) as mois,
               SUM(CASE WHEN SUBSTR(compte_num,1,1)='7' THEN CAST(credit AS REAL) - CAST(debit AS REAL) ELSE 0 END) as ca,
               SUM(CASE WHEN SUBSTR(compte_num,1,1)='6' THEN CAST(debit AS REAL) - CAST(credit AS REAL) ELSE 0 END) as charges
        FROM ecritures WHERE exercice = ?
        GROUP BY SUBSTR(ecriture_date, 1, 7) ORDER BY mois
    ");
    $stmt->execute([$exercice]);
    $mensuel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top fournisseurs
    $stmt = $db->prepare("
        SELECT COALESCE(lib_tiers, 'N/A') as nom, 
               SUM(CAST(debit AS REAL) - CAST(credit AS REAL)) as montant
        FROM ecritures 
        WHERE exercice = ? AND SUBSTR(compte_num,1,1) = '6' AND lib_tiers IS NOT NULL AND lib_tiers != ''
        GROUP BY lib_tiers ORDER BY montant DESC LIMIT 10
    ");
    $stmt->execute([$exercice]);
    $topFourn = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top clients
    $stmt = $db->prepare("
        SELECT COALESCE(lib_tiers, 'N/A') as nom,
               SUM(CAST(credit AS REAL) - CAST(debit AS REAL)) as montant
        FROM ecritures 
        WHERE exercice = ? AND SUBSTR(compte_num,1,3) = '411' AND lib_tiers IS NOT NULL AND lib_tiers != ''
        GROUP BY lib_tiers ORDER BY montant DESC LIMIT 10
    ");
    $stmt->execute([$exercice]);
    $topClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Qualité données
    $stmt = $db->prepare("SELECT COUNT(*) as total,
        SUM(CASE WHEN lettrage_flag=1 THEN 1 ELSE 0 END) as lettrees,
        SUM(CASE WHEN piece_ref IS NOT NULL AND piece_ref != '' THEN 1 ELSE 0 END) as avec_piece
        FROM ecritures WHERE exercice = ?");
    $stmt->execute([$exercice]);
    $qualite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // =============================================
    // GÉNÉRATION DE LA NOTE DE SYNTHÈSE
    // =============================================
    
    $fmt = function($v) { return number_format(abs($v), 0, ',', ' '); };
    $pct = function($v, $base) { return $base > 0 ? round(($v / $base) * 100, 1) : 0; };
    
    // Helper closures (must be closures for heredoc interpolation)
    $signeAppr = function($val, $low, $high) {
        if ($val >= $high) return '✅ Excellent';
        if ($val >= $low) return '🟡 Correct';
        return '🔴 Insuffisant';
    };
    $signeRN = function($val) {
        return $val >= 0 ? '✅ Bénéficiaire' : '🔴 Déficitaire';
    };
    $signeTN = function($val) {
        return $val >= 0 ? '✅ Positive' : '🔴 Tension';
    };
    $statusSeuil = function($ca, $seuil) {
        if ($seuil <= 0) return '⚪ Non calculable';
        return $ca >= $seuil 
            ? '✅ **Atteint** — Marge de sécurité de ' . number_format(abs($ca - $seuil), 0, ',', ' ') . ' €' 
            : '🔴 **Non atteint** — Déficit de ' . number_format(abs($ca - $seuil), 0, ',', ' ') . ' €';
    };
    $statutLettrage = function($taux) {
        if ($taux >= 80) return '✅';
        if ($taux >= 50) return '🟡 À améliorer';
        return '🔴 Insuffisant';
    };
    
    // Alertes
    $alertes = [];
    if ($rn < 0) $alertes[] = "🔴 **Résultat Net déficitaire** : perte de {$fmt(abs($rn))} €";
    if ($ebe < 0) $alertes[] = "🔴 **EBE négatif** : l'activité ne génère pas de cash opérationnel";
    if ($tresorerie < 0) $alertes[] = "🔴 **Trésorerie négative** : tension de trésorerie immédiate";
    if ($dso > 60) $alertes[] = "🟠 **DSO élevé** ({$dso} jours) : risque d'impayés";
    if ($ca > 0 && $ca < $seuil) $alertes[] = "🔴 **Sous le seuil de rentabilité** : CA < point mort";
    if ($bfr > $tresorerie && $bfr > 0) $alertes[] = "🟠 **BFR ({$fmt($bfr)} €) > Trésorerie** : risque de cash";
    
    $alertesTxt = count($alertes) > 0 
        ? implode("\n", array_map(function($a) { return "- $a"; }, $alertes))
        : "- ✅ Aucune alerte critique détectée";
    
    // Tableau mensuel
    $tableauMensuel = "| Mois | CA | Charges | Résultat |\n|------|---:|--------:|---------:|\n";
    foreach ($mensuel as $m) {
        $res = (float)$m['ca'] - (float)$m['charges'];
        $tableauMensuel .= "| {$m['mois']} | {$fmt($m['ca'])} € | {$fmt($m['charges'])} € | " . ($res >= 0 ? '+' : '-') . "{$fmt(abs($res))} € |\n";
    }
    
    // Tableau fournisseurs
    $tableauFourn = "| Rang | Fournisseur | Montant | % Charges |\n|------|------------|--------:|----------:|\n";
    foreach ($topFourn as $i => $f) {
        $p = $pct(abs((float)$f['montant']), $ch_variables + $ch_fixes);
        $tableauFourn .= "| " . ($i+1) . " | {$f['nom']} | {$fmt($f['montant'])} € | {$p}% |\n";
    }
    
    // Tableau Clients
    $tableauClients = "| Rang | Client | CA | % CA Total |\n|------|--------|---:|-----------:|\n";
    foreach ($topClients as $i => $c) {
        $p = $pct(abs((float)$c['montant']), $ca);
        $tableauClients .= "| " . ($i+1) . " | {$c['nom']} | {$fmt($c['montant'])} € | {$p}% |\n";
    }
    
    // Taux lettrage
    $txLettrage = (int)$qualite['total'] > 0 ? round((int)$qualite['lettrees'] / (int)$qualite['total'] * 100, 1) : 0;
    $txPiece = (int)$qualite['total'] > 0 ? round((int)$qualite['avec_piece'] / (int)$qualite['total'] * 100, 1) : 0;
    
    $marge_brute_pct = $pct($marge_prod, $ca);
    $va_pct = $pct($va, $ca);
    $ebe_pct = $pct($ebe, $ca);
    $rn_pct = $pct($rn, $ca);
    $part_pers_va = $va > 0 ? $pct($personnel, $va) : 0;
    
    // =============================================
    // SCORING DE SANTÉ FINANCIÈRE / 100
    // =============================================
    
    // Rentabilité /30
    $scoreRenta = 0;
    if ($rn > 0) $scoreRenta += 10;
    if ($ebe > 0) $scoreRenta += 5;
    if ($rn_pct >= 5) $scoreRenta += 5; elseif ($rn_pct >= 2) $scoreRenta += 3;
    if ($ebe_pct >= 15) $scoreRenta += 5; elseif ($ebe_pct >= 10) $scoreRenta += 3;
    if ($marge_brute_pct >= 50) $scoreRenta += 5; elseif ($marge_brute_pct >= 35) $scoreRenta += 3;
    
    // Liquidité /25
    $scoreLiquid = 0;
    if ($tresorerie > 0) $scoreLiquid += 10; elseif ($tresorerie > -10000) $scoreLiquid += 3;
    $joursTreso = $ca > 0 ? round(abs($tresorerie) / ($ca / 365), 0) : 0;
    if ($tresorerie >= 0) {
        if ($joursTreso >= 90) $scoreLiquid += 8; elseif ($joursTreso >= 30) $scoreLiquid += 5; elseif ($joursTreso >= 15) $scoreLiquid += 2;
    }
    if ($bfr <= $tresorerie) $scoreLiquid += 7; elseif ($bfr > 0 && $tresorerie > 0) $scoreLiquid += 3;
    
    // Structure /20
    $scoreStruct = 0;
    $cycleCash = $dso + $jours_stock - $dpo;
    if ($dso <= 45) $scoreStruct += 5; elseif ($dso <= 60) $scoreStruct += 3;
    if ($dpo >= 30) $scoreStruct += 5; elseif ($dpo >= 15) $scoreStruct += 3;
    if ($cycleCash <= 30) $scoreStruct += 5; elseif ($cycleCash <= 60) $scoreStruct += 3;
    if ($part_pers_va <= 65) $scoreStruct += 5; elseif ($part_pers_va <= 75) $scoreStruct += 3;
    
    // Exploitation /15
    $scoreExploit = 0;
    if ($ca > 0 && $ca >= $seuil) $scoreExploit += 8; elseif ($seuil > 0 && $ca >= $seuil * 0.8) $scoreExploit += 4;
    $topClientPct = count($topClients) > 0 ? round(abs((float)$topClients[0]['montant']) / max($ca, 1) * 100, 1) : 0;
    if ($topClientPct <= 30) $scoreExploit += 4; elseif ($topClientPct <= 50) $scoreExploit += 2;
    if (count($mensuel) >= 10) $scoreExploit += 3; elseif (count($mensuel) >= 6) $scoreExploit += 1;
    
    // Qualité données /10
    $scoreQualite = 0;
    if ($txLettrage >= 80) $scoreQualite += 4; elseif ($txLettrage >= 50) $scoreQualite += 2;
    if ($txPiece >= 90) $scoreQualite += 4; elseif ($txPiece >= 70) $scoreQualite += 2;
    if ((int)$qualite['total'] >= 100) $scoreQualite += 2; elseif ((int)$qualite['total'] >= 50) $scoreQualite += 1;
    
    $scoreTotal = $scoreRenta + $scoreLiquid + $scoreStruct + $scoreExploit + $scoreQualite;
    
    $feuGlobal = $scoreTotal >= 70 ? '🟢 SAIN' : ($scoreTotal >= 40 ? '🟡 VIGILANCE' : '🔴 CRITIQUE');
    $gradeFin = $scoreTotal >= 85 ? 'A+' : ($scoreTotal >= 70 ? 'A' : ($scoreTotal >= 55 ? 'B' : ($scoreTotal >= 40 ? 'C' : 'D')));
    
    // Benchmark bijouterie
    $bmMarge = function($val) { if ($val >= 62) return '✅ Au-dessus'; if ($val >= 55) return '🟡 Dans la norme'; return '🔴 Sous le seuil'; };
    $bmVA = function($val) { if ($val >= 48) return '✅'; if ($val >= 40) return '🟡'; return '🔴'; };
    $bmEBE = function($val) { if ($val >= 20) return '✅'; if ($val >= 12) return '🟡'; return '🔴'; };
    $bmRN = function($val) { if ($val >= 12) return '✅'; if ($val >= 5) return '🟡'; return '🔴'; };
    $bmDSO = function($val) { if ($val <= 30) return '✅'; if ($val <= 45) return '🟡'; return '🔴'; };
    $bmPers = function($val) { if ($val <= 55) return '✅'; if ($val <= 65) return '🟡'; return '🔴'; };
    
    // Saisonnalité et dynamique mensuelle
    $caValues = array_map(function($m) { return (float)$m['ca']; }, $mensuel);
    $chargesValues = array_map(function($m) { return (float)$m['charges']; }, $mensuel);
    $moisLabels = array_map(function($m) { return $m['mois']; }, $mensuel);
    $avgCA = count($caValues) > 0 ? array_sum($caValues) / count($caValues) : 0;
    $maxCA = count($caValues) > 0 ? max($caValues) : 0;
    $minCA = count($caValues) > 0 ? min($caValues) : 0;
    $idxMax = $maxCA > 0 ? array_search($maxCA, $caValues) : 0;
    $idxMin = array_search($minCA, $caValues);
    $moisMax = isset($moisLabels[$idxMax]) ? $moisLabels[$idxMax] : 'N/A';
    $moisMin = isset($moisLabels[$idxMin]) ? $moisLabels[$idxMin] : 'N/A';
    // Coefficient de variation
    $varianceCA = 0;
    if (count($caValues) > 1 && $avgCA > 0) {
        foreach ($caValues as $v) $varianceCA += pow($v - $avgCA, 2);
        $varianceCA = sqrt($varianceCA / count($caValues));
    }
    $cvCA = $avgCA > 0 ? round($varianceCA / $avgCA * 100, 1) : 0;
    
    // Tendance (pente linéaire simplifiée)
    $tendanceTxt = 'Stable';
    if (count($caValues) >= 3) {
        $n = count($caValues);
        $sumXY = 0; $sumX = 0; $sumY = 0; $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $i * $caValues[$i];
            $sumX += $i;
            $sumY += $caValues[$i];
            $sumX2 += $i * $i;
        }
        $pente = ($n * $sumXY - $sumX * $sumY) / max(1, ($n * $sumX2 - $sumX * $sumX));
        if ($pente > $avgCA * 0.02) $tendanceTxt = '📈 Croissance';
        elseif ($pente < -$avgCA * 0.02) $tendanceTxt = '📉 Déclin';
        else $tendanceTxt = '➡️ Stable';
    }
    
    // Risques
    $risques = [];
    if ($rn < 0) $risques[] = ['🔴', 'Résultat déficitaire', 'Élevée', 'Fort', 'Restructurer les charges immédiatement'];
    if ($tresorerie < 0) $risques[] = ['🔴', 'Trésorerie négative', 'Actuel', 'Critique', 'Plan de trésorerie d\'urgence'];
    if ($dso > 60) $risques[] = ['🟠', 'Délai clients excessif', 'Élevée', 'Moyen', 'Programme de relance automatisé'];
    if ($topClientPct > 40) $risques[] = ['🟠', 'Concentration client (' . $topClientPct . '%)', 'Moyenne', 'Fort', 'Diversifier le portefeuille'];
    if ($txLettrage < 50) $risques[] = ['🟡', 'Lettrage insuffisant (' . $txLettrage . '%)', 'Certaine', 'Moyen', 'Campagne de rapprochement'];
    if ($txPiece < 70) $risques[] = ['🟡', 'Pièces manquantes (' . $txPiece . '%)', 'Certaine', 'Fiscal', 'Numérisation systématique'];
    if ($ca > 0 && $seuil > 0 && $ca < $seuil) $risques[] = ['🔴', 'Sous seuil de rentabilité', 'Actuel', 'Critique', 'Plan de croissance CA ou réduction coûts'];
    if ($bfr > $tresorerie && $bfr > 0) $risques[] = ['🟠', 'BFR > Trésorerie', 'Élevée', 'Fort', 'Négocier délais fournisseurs'];
    if (count($risques) === 0) $risques[] = ['✅', 'Aucun risque critique identifié', '-', '-', 'Maintenir la trajectoire'];
    
    $tableauRisques = "| Statut | Risque | Probabilité | Impact | Action |\n|:------:|--------|:-----------:|:------:|--------|\n";
    foreach ($risques as $r) {
        $tableauRisques .= "| {$r[0]} | {$r[1]} | {$r[2]} | {$r[3]} | {$r[4]} |\n";
    }
    
    // CAF
    $caf = $rn + ($s2['68'] ?? 0) - (($s2['78'] ?? 0) + ($s2['79'] ?? 0));
    
    // Impact estimé des quick wins
    $impactRelance = $dso > 30 && $creances > 0 ? round($creances * 0.15, 0) : 0;
    $impactDPO = $dpo < 45 && $dettes_fourn > 0 ? round($dettes_fourn * 0.1, 0) : 0;
    $impactNego = round($ch_variables * 0.03, 0);
    
    $note = <<<MD
# 📊 Note de Synthèse Financière — Exercice {$exercice}

> *Analyse produite selon le référentiel d'audit Big Four — PCG 2025.*
> *Persona : Expert-comptable associé, 20 ans XP, spécialiste bijouterie-joaillerie.*

---

## 🏆 Score de Santé Financière : {$scoreTotal}/100 — {$feuGlobal} (Grade {$gradeFin})

| Dimension | Score | Détail |
|-----------|------:|:-------|
| **Rentabilité** | {$scoreRenta}/30 | RN {$fmt($rn)} €, EBE {$fmt($ebe)} € |
| **Liquidité** | {$scoreLiquid}/25 | Trésorerie {$fmt($tresorerie)} €, {$joursTreso}j d'autonomie |
| **Structure** | {$scoreStruct}/20 | Cycle cash {$cycleCash}j, personnel {$part_pers_va}% VA |
| **Exploitation** | {$scoreExploit}/15 | CA vs seuil, concentration, régularité |
| **Qualité données** | {$scoreQualite}/10 | Lettrage {$txLettrage}%, pièces {$txPiece}% |

---

## 🎯 Executive Summary

### Indicateurs Clés vs Benchmark Bijouterie

| Indicateur | Valeur | % CA | Benchmark Secteur | Statut |
|-----------|-------:|-----:|:-----------------:|:------:|
| **Chiffre d'Affaires** | {$fmt($ca)} € | 100% | Référence | — |
| **Marge de Production** | {$fmt($marge_prod)} € | {$marge_brute_pct}% | 55-65% | {$bmMarge($marge_brute_pct)} |
| **Valeur Ajoutée** | {$fmt($va)} € | {$va_pct}% | 40-50% | {$bmVA($va_pct)} |
| **EBE (EBITDA)** | {$fmt($ebe)} € | {$ebe_pct}% | 12-25% | {$bmEBE($ebe_pct)} |
| **Résultat Net** | {$fmt($rn)} € | {$rn_pct}% | 5-12% | {$bmRN($rn_pct)} |
| **CAF** | {$fmt($caf)} € | — | > 0 | {$signeRN($caf)} |
| **BFR** | {$fmt($bfr)} € | — | — | {$fmt(abs($bfr / max($ca, 1) * 365))}j de CA |
| **Trésorerie Nette** | {$fmt($tresorerie)} € | — | > 0 | {$signeTN($tresorerie)} |

### Alertes et Points de Vigilance

{$alertesTxt}

---

## 💰 Analyse de Profitabilité — Cascade SIG PCG 2025

```
 Chiffre d'Affaires Net ........... {$fmt($ca)} €   (100%)
   Production totale .............. {$fmt($production)} €
   - Consommation matières ........ {$fmt($achats)} €
 ═══════════════════════════════════════════════════════
 = Marge de Production ............ {$fmt($marge_prod)} €   ({$marge_brute_pct}%)
   - Services extérieurs .......... {$fmt($services)} €
 ═══════════════════════════════════════════════════════
 = VALEUR AJOUTÉE ................. {$fmt($va)} €   ({$va_pct}%)
   + Subventions d'exploitation ... {$fmt($subventions)} €
   - Impôts & Taxes ............... {$fmt($impots)} €
   - Personnel .................... {$fmt($personnel)} €   ({$part_pers_va}% de la VA)
 ═══════════════════════════════════════════════════════
 = EBE (EBITDA) ................... {$fmt($ebe)} €   ({$ebe_pct}%)
   - Dotations & autres charges ... {$fmt($dotations)} €
 ═══════════════════════════════════════════════════════
 = Résultat d'Exploitation ........ {$fmt($re)} €
   +/- Résultat Financier ......... {$fmt($rf)} €
 ═══════════════════════════════════════════════════════
 = RÉSULTAT NET ................... {$fmt($rn)} €   ({$rn_pct}%)
```

### Décomposition Charges Fixes vs Variables

| Type | Montant | % du CA | Observation |
|------|--------:|--------:|:------------|
| **Charges Variables** (achats, services ext.) | {$fmt($ch_variables)} € | {$pct($ch_variables, $ca)}% | Levier marge directe |
| **Charges Fixes** (personnel, impôts, amort.) | {$fmt($ch_fixes)} € | {$pct($ch_fixes, $ca)}% | Incompressibles court terme |
| **Total Charges** | {$fmt($ch_variables + $ch_fixes)} € | {$pct($ch_variables + $ch_fixes, $ca)}% | — |

### Seuil de Rentabilité

- **Point mort** : {$fmt($seuil)} € de CA minimum nécessaire
- **Marge sur coûts variables** : {$pct($ca - $ch_variables, $ca)}%
- **Statut** : {$statusSeuil($ca, $seuil)}

---

## 🔄 Cycle d'Exploitation & Trésorerie

### Cycles de Paiement

| Indicateur | Jours | Benchmark | Écart | Statut |
|-----------|------:|:---------:|------:|:------:|
| **DSO** (délai clients) | {$dso}j | ≤ 30j | {$fmt($dso - 30)}j | {$bmDSO($dso)} |
| **DPO** (délai fournisseurs) | {$dpo}j | ≥ 45j | {$fmt($dpo - 45)}j | — |
| **Jours de Stock** | {$jours_stock}j | Secteur | — | — |
| **Cycle de Conversion** | {$cycleCash}j | ≤ 30j | {$fmt($cycleCash - 30)}j | — |

### BFR et Trésorerie

- **BFR** = Stocks ({$fmt($stocks)} €) + Créances ({$fmt($creances)} €) - Dettes fourn. ({$fmt($dettes_fourn)} €) = **{$fmt($bfr)} €**
- **Trésorerie nette** : **{$fmt($tresorerie)} €** soit **{$joursTreso} jours** d'autonomie au rythme actuel
- **CAF** : {$fmt($caf)} € disponibles pour l'autofinancement

---

## ⚡ Matrice des Risques

{$tableauRisques}

### Qualité des Données FEC
- Écritures totales : **{$qualite['total']}**
- Taux de lettrage : **{$txLettrage}%** {$statutLettrage($txLettrage)}
- Pièces justificatives : **{$txPiece}%** des écritures

---

## 📈 Dynamique Mensuelle

### Tendance : {$tendanceTxt} | Volatilité (CV) : {$cvCA}%

- **Meilleur mois** : {$moisMax} ({$fmt($maxCA)} €)
- **Mois le plus faible** : {$moisMin} ({$fmt($minCA)} €)
- **CA mensuel moyen** : {$fmt($avgCA)} €
- **Amplitude** : {$fmt($maxCA - $minCA)} € (×{$fmt($minCA > 0 ? $maxCA / $minCA : 0)})

{$tableauMensuel}

---

## 👥 Analyse Concentration

### Top Clients
{$tableauClients}

### Top Fournisseurs
{$tableauFourn}

---

## 🎯 Plan d'Actions Stratégique

### ⚡ Quick Wins (impact immédiat)
1. **Relance clients** : cibler le DSO → gains potentiels de **{$fmt($impactRelance)} €** de trésorerie
2. **Négociation fournisseurs** : allonger le DPO → gain BFR estimé **{$fmt($impactDPO)} €**
3. **Renégociation achats** : -3% sur charges variables → économie **{$fmt($impactNego)} €/an**

### 📋 Court terme (0-3 mois)
1. Sécuriser la trésorerie : relance systématique des impayés > 30 jours
2. Mettre en place un tableau de bord hebdomadaire (CA, encaissements, solde banque)
3. Compléter le lettrage des écritures comptables (objectif > 90%)

### 📊 Moyen terme (3-12 mois)
1. Optimiser la structure de coûts : renégocier les contrats fournisseurs principaux
2. Diversifier le portefeuille client pour réduire la concentration
3. Mettre en place un prévisionnel de trésorerie glissant à 13 semaines

### 🚀 Long terme (1-3 ans)
1. Développer les canaux de vente (e-commerce, marketplace bijouterie)
2. Investir dans la fidélisation client (programme VIP, SAV premium)
3. Structurer la comptabilité analytique par activité (création, réparation, négoce)

---

> **Verdict** : Score {$scoreTotal}/100 ({$gradeFin}) — {$feuGlobal}.
> *Cette analyse a été générée automatiquement à partir des données FEC importées. Elle ne se substitue pas à l'avis d'un expert-comptable diplômé.*
MD;
    
    // =============================================
    // RÉSULTAT
    // =============================================
    
    $result = [
        'exercice' => $exercice,
        'system_prompt' => $systemPrompt,
        'analysis' => $note,
        'score' => [
            'total' => $scoreTotal,
            'grade' => $gradeFin,
            'status' => $feuGlobal,
            'detail' => [
                'rentabilite' => ['score' => $scoreRenta, 'max' => 30],
                'liquidite' => ['score' => $scoreLiquid, 'max' => 25],
                'structure' => ['score' => $scoreStruct, 'max' => 20],
                'exploitation' => ['score' => $scoreExploit, 'max' => 15],
                'qualite_donnees' => ['score' => $scoreQualite, 'max' => 10],
            ],
        ],
        'data_context' => [
            'ca' => round($ca, 2),
            'marge_production' => round($marge_prod, 2),
            'valeur_ajoutee' => round($va, 2),
            'ebe' => round($ebe, 2),
            'resultat_net' => round($rn, 2),
            'caf' => round($caf, 2),
            'bfr' => round($bfr, 2),
            'tresorerie' => round($tresorerie, 2),
            'dso' => $dso,
            'dpo' => $dpo,
            'jours_stock' => $jours_stock,
            'cycle_cash' => $cycleCash,
            'seuil_rentabilite' => round($seuil, 2),
            'charges_fixes' => round($ch_fixes, 2),
            'charges_variables' => round($ch_variables, 2),
            'jours_tresorerie' => $joursTreso,
            'tendance' => $tendanceTxt,
            'volatilite_cv' => $cvCA,
        ],
        'alertes' => $alertes,
        'risques' => $risques,
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
