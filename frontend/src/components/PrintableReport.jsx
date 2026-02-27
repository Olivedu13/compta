import React, { useRef } from 'react';

/**
 * PrintableReport — Module d'impression complet
 * Génère un rapport PDF-ready avec KPIs, SIG, ratios, bilan, analyse IA
 */

const fmt = (v) =>
  new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(Number(v) || 0);
const pct = (v) => `${(Number(v) || 0).toFixed(1)}%`;
const dec = (v, d = 1) => (Number(v) || 0).toFixed(d);
const fmtK = (v) => {
  const abs = Math.abs(v);
  if (abs >= 1000000) return (v / 1000000).toFixed(1) + 'M€';
  if (abs >= 1000) return (v / 1000).toFixed(0) + 'k€';
  return v.toFixed(0) + '€';
};

// ─── Sous-composants impression ───

const PrintHeader = ({ year }) => (
  <div className="print-header" style={{ borderBottom: '3px solid #1e293b', paddingBottom: 16, marginBottom: 24, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
    <div>
      <div style={{ fontSize: 24, fontWeight: 900, color: '#1e293b', letterSpacing: -1 }}>ATCO BI — AuditCompta</div>
      <div style={{ fontSize: 11, color: '#64748b', fontWeight: 700, textTransform: 'uppercase', letterSpacing: 3, marginTop: 4 }}>
        Rapport d'Audit Financier
      </div>
    </div>
    <div style={{ textAlign: 'right' }}>
      <div style={{ fontSize: 28, fontWeight: 900, color: '#1e293b' }}>Exercice {year}</div>
      <div style={{ fontSize: 10, color: '#94a3b8', marginTop: 4 }}>
        Édité le {new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
      </div>
    </div>
  </div>
);

const SectionTitle = ({ icon, title }) => (
  <div style={{ fontSize: 14, fontWeight: 900, color: '#1e293b', textTransform: 'uppercase', letterSpacing: 1, marginTop: 28, marginBottom: 12, paddingBottom: 6, borderBottom: '2px solid #e2e8f0', display: 'flex', alignItems: 'center', gap: 8, pageBreakAfter: 'avoid' }}>
    <i className={`fa-solid ${icon}`} style={{ color: '#3b82f6' }}></i>
    {title}
  </div>
);

const KpiRow = ({ label, value, detail, highlight }) => (
  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '8px 12px', backgroundColor: highlight ? '#f0f9ff' : 'transparent', borderRadius: 8, marginBottom: 2 }}>
    <div>
      <span style={{ fontSize: 12, fontWeight: 700, color: '#334155' }}>{label}</span>
      {detail && <span style={{ fontSize: 9, color: '#94a3b8', marginLeft: 8 }}>{detail}</span>}
    </div>
    <span style={{ fontSize: 14, fontWeight: 900, color: highlight ? '#2563eb' : '#0f172a', fontVariantNumeric: 'tabular-nums' }}>{value}</span>
  </div>
);

const SigTableRow = ({ label, value, bold, color }) => (
  <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
    <td style={{ padding: '6px 8px', fontSize: 11, fontWeight: bold ? 800 : 500, color: color || '#334155' }}>{label}</td>
    <td style={{ padding: '6px 8px', fontSize: 12, fontWeight: 800, color: color || (value < 0 ? '#ef4444' : '#0f172a'), textAlign: 'right', fontVariantNumeric: 'tabular-nums' }}>{fmt(value)}</td>
  </tr>
);

const RatioBlock = ({ title, items }) => (
  <div style={{ border: '1px solid #e2e8f0', borderRadius: 12, padding: 12, marginBottom: 8, breakInside: 'avoid' }}>
    <div style={{ fontSize: 10, fontWeight: 900, textTransform: 'uppercase', letterSpacing: 2, color: '#64748b', marginBottom: 8 }}>{title}</div>
    {items.map((item, i) => (
      <div key={i} style={{ display: 'flex', justifyContent: 'space-between', padding: '3px 0', fontSize: 11 }}>
        <span style={{ color: '#64748b' }}>{item.label}</span>
        <span style={{ fontWeight: 800, color: '#0f172a' }}>{item.val}</span>
      </div>
    ))}
  </div>
);

const TrafficDot = ({ status }) => {
  const colors = { green: '#22c55e', yellow: '#f59e0b', red: '#ef4444' };
  return <span style={{ display: 'inline-block', width: 10, height: 10, borderRadius: '50%', backgroundColor: colors[status] || '#94a3b8', marginRight: 6 }}></span>;
};

const VerdictBanner = ({ verdict, score, desc }) => {
  const bg = score >= 70 ? '#22c55e' : score >= 50 ? '#f59e0b' : score >= 30 ? '#f97316' : '#ef4444';
  return (
    <div style={{ backgroundColor: bg, color: 'white', borderRadius: 12, padding: '16px 20px', display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
      <div>
        <div style={{ fontSize: 18, fontWeight: 900 }}>{verdict}</div>
        <div style={{ fontSize: 11, opacity: 0.85 }}>{desc}</div>
      </div>
      <div style={{ textAlign: 'right' }}>
        <div style={{ fontSize: 32, fontWeight: 900 }}>{Math.round(score)}</div>
        <div style={{ fontSize: 9, opacity: 0.7, textTransform: 'uppercase', fontWeight: 700 }}>Score / 100</div>
      </div>
    </div>
  );
};

// ─── Composant principal ───
const PrintableReport = ({ data, previousData, aiResult, onClose }) => {
  const printRef = useRef(null);

  if (!data) return null;

  const ca = data.revenue || 0;
  const ebe = data.ebitda || 0;
  const rn = data.netIncome || 0;
  const caf = data.caf || 0;
  const tn = data.tn || 0;
  const bfr = data.bfr || 0;
  const frng = data.frng || 0;
  const healthScore = data.healthScore || 50;
  const ratios = data.ratios || {};
  const sig = data.sig || {};
  const expenses = data.expenseBreakdown || [];

  // Marges
  const margeNette = ca > 0 ? (rn / ca) * 100 : 0;
  const margeEBE = ca > 0 ? (ebe / ca) * 100 : 0;
  const cafSurCA = ca > 0 ? (caf / ca) * 100 : 0;

  // Verdict
  let verdict, verdictDesc;
  if (healthScore >= 70 && margeNette > 3 && tn > 0) {
    verdict = 'Entreprise saine'; verdictDesc = 'Les fondamentaux financiers sont solides.';
  } else if (healthScore >= 50 && margeNette > 1) {
    verdict = 'À surveiller'; verdictDesc = 'Quelques indicateurs méritent attention.';
  } else if (healthScore >= 30) {
    verdict = 'Situation tendue'; verdictDesc = 'Plusieurs signaux d\'alerte présents.';
  } else {
    verdict = 'Situation critique'; verdictDesc = 'Action immédiate recommandée.';
  }

  // Feux tricolores
  const feux = [
    { label: 'Rentabilité', status: margeNette > 5 ? 'green' : margeNette > 2 ? 'yellow' : 'red', detail: `Marge nette ${margeNette.toFixed(1)}%` },
    { label: 'Trésorerie', status: tn > 0 && tn > bfr * 0.1 ? 'green' : tn > 0 ? 'yellow' : 'red', detail: `TN ${fmtK(tn)}` },
    { label: 'Autonomie financière', status: (ratios.financialAutonomy || 0) > 0.3 ? 'green' : (ratios.financialAutonomy || 0) > 0.15 ? 'yellow' : 'red', detail: `${((ratios.financialAutonomy || 0) * 100).toFixed(0)}%` },
    { label: 'DSO Clients', status: (data.dso || 0) < 45 ? 'green' : (data.dso || 0) < 90 ? 'yellow' : 'red', detail: `${Math.round(data.dso || 0)} jours` },
    { label: 'Seuil de rentabilité', status: ca > (data.breakEvenPoint || 0) * 1.1 ? 'green' : ca > (data.breakEvenPoint || 0) ? 'yellow' : 'red', detail: ca >= (data.breakEvenPoint || 0) ? 'Dépassé' : 'Non atteint' },
    { label: 'Endettement', status: (ratios.gearing || 0) < 1 ? 'green' : (ratios.gearing || 0) < 2 ? 'yellow' : 'red', detail: `Gearing ${(ratios.gearing || 0).toFixed(1)}x` },
  ];

  // Évolutions
  const evolCA = previousData?.revenue ? ((ca - previousData.revenue) / Math.abs(previousData.revenue)) * 100 : null;
  const evolRN = previousData?.netIncome ? ((rn - previousData.netIncome) / Math.abs(previousData.netIncome)) * 100 : null;

  const handlePrint = () => {
    const content = printRef.current;
    if (!content) return;

    const printWindow = window.open('', '_blank', 'width=900,height=700');
    printWindow.document.write(`<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport Financier — Exercice ${data.year}</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Inter', sans-serif; color: #0f172a; background: white; }
  @page { size: A4; margin: 15mm 18mm; }
  @media print {
    body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .no-print { display: none !important; }
    .page-break { page-break-before: always; }
  }
</style>
</head>
<body>
<div class="no-print" style="background:#1e293b; padding:12px 24px; display:flex; justify-content:space-between; align-items:center; position:sticky; top:0; z-index:100;">
  <span style="color:white; font-weight:900; font-size:14px;">ATCO BI — Aperçu avant impression</span>
  <div style="display:flex; gap:8px;">
    <button onclick="window.print()" style="background:#3b82f6; color:white; border:none; padding:8px 20px; border-radius:8px; font-weight:800; font-size:12px; cursor:pointer;">
      <i class="fa-solid fa-print"></i> Imprimer / PDF
    </button>
    <button onclick="window.close()" style="background:#475569; color:white; border:none; padding:8px 16px; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer;">Fermer</button>
  </div>
</div>
<div style="max-width:780px; margin:0 auto; padding:24px;">
${content.innerHTML}
</div>
</body>
</html>`);
    printWindow.document.close();
  };

  return (
    <>
      {/* Bouton flottant impression */}
      <button
        onClick={handlePrint}
        className="fixed bottom-6 right-6 z-40 bg-slate-900 text-white w-14 h-14 rounded-2xl shadow-2xl flex items-center justify-center hover:bg-blue-600 transition-all hover:scale-110 no-print"
        title="Imprimer le rapport"
      >
        <i className="fa-solid fa-print text-lg"></i>
      </button>

      {/* Contenu d'impression (hidden, sert de source) */}
      <div ref={printRef} style={{ position: 'absolute', left: '-9999px', top: 0, width: 780 }}>
        {/* ═══ PAGE 1 : SYNTHÈSE DIRIGEANT ═══ */}
        <PrintHeader year={data.year} />

        <VerdictBanner verdict={verdict} score={healthScore} desc={verdictDesc} />

        {/* KPIs Principaux */}
        <SectionTitle icon="fa-chart-bar" title="Indicateurs Clés" />
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 4 }}>
          <KpiRow label="Chiffre d'Affaires" value={fmt(ca)} highlight detail={evolCA ? `${evolCA > 0 ? '+' : ''}${evolCA.toFixed(1)}% vs N-1` : ''} />
          <KpiRow label="Résultat Net" value={fmt(rn)} highlight detail={evolRN ? `${evolRN > 0 ? '+' : ''}${evolRN.toFixed(1)}% vs N-1` : ''} />
          <KpiRow label="Excédent Brut (EBE)" value={fmt(ebe)} detail={`Marge EBE ${margeEBE.toFixed(1)}%`} />
          <KpiRow label="Capacité d'Autofinancement" value={fmt(caf)} detail={`${cafSurCA.toFixed(1)}% du CA`} />
          <KpiRow label="Total Charges" value={fmt(data.totalCharges)} />
          <KpiRow label="Point Mort" value={fmt(data.breakEvenPoint)} />
          <KpiRow label="Trésorerie Nette" value={fmt(tn)} highlight />
          <KpiRow label="Marge Nette" value={pct(margeNette)} highlight />
        </div>

        {/* Diagnostic rapide */}
        <SectionTitle icon="fa-traffic-light" title="Diagnostic Rapide" />
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '4px 16px' }}>
          {feux.map((f, i) => (
            <div key={i} style={{ display: 'flex', alignItems: 'center', padding: '5px 0', fontSize: 11 }}>
              <TrafficDot status={f.status} />
              <span style={{ fontWeight: 700, marginRight: 8 }}>{f.label}</span>
              <span style={{ color: '#64748b', fontSize: 10 }}>{f.detail}</span>
            </div>
          ))}
        </div>

        {/* Répartition charges */}
        <SectionTitle icon="fa-chart-pie" title="Répartition des Charges" />
        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 11 }}>
          <thead>
            <tr style={{ borderBottom: '2px solid #e2e8f0' }}>
              <th style={{ textAlign: 'left', padding: '6px 8px', fontSize: 9, fontWeight: 800, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: 2 }}>Catégorie</th>
              <th style={{ textAlign: 'right', padding: '6px 8px', fontSize: 9, fontWeight: 800, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: 2 }}>Montant</th>
              <th style={{ textAlign: 'right', padding: '6px 8px', fontSize: 9, fontWeight: 800, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: 2 }}>% CA</th>
              <th style={{ textAlign: 'left', padding: '6px 8px', width: 120 }}></th>
            </tr>
          </thead>
          <tbody>
            {expenses.map((e, i) => {
              const maxVal = Math.max(...expenses.map(ex => ex.value));
              const barPct = maxVal > 0 ? (e.value / maxVal) * 100 : 0;
              const pctCA = ca > 0 ? (e.value / ca) * 100 : 0;
              return (
                <tr key={i} style={{ borderBottom: '1px solid #f1f5f9' }}>
                  <td style={{ padding: '6px 8px', fontWeight: 600 }}>{e.label}</td>
                  <td style={{ padding: '6px 8px', fontWeight: 800, textAlign: 'right', fontVariantNumeric: 'tabular-nums' }}>{fmt(e.value)}</td>
                  <td style={{ padding: '6px 8px', textAlign: 'right', color: '#64748b' }}>{pctCA.toFixed(1)}%</td>
                  <td style={{ padding: '6px 8px' }}>
                    <div style={{ height: 8, backgroundColor: '#f1f5f9', borderRadius: 4, overflow: 'hidden' }}>
                      <div style={{ height: '100%', width: `${barPct}%`, backgroundColor: e.color, borderRadius: 4 }}></div>
                    </div>
                  </td>
                </tr>
              );
            })}
            <tr style={{ borderTop: '2px solid #1e293b' }}>
              <td style={{ padding: '8px 8px', fontWeight: 900 }}>TOTAL</td>
              <td style={{ padding: '8px 8px', fontWeight: 900, textAlign: 'right' }}>{fmt(data.totalCharges)}</td>
              <td style={{ padding: '8px 8px', textAlign: 'right', fontWeight: 700, color: '#64748b' }}>{ca > 0 ? ((data.totalCharges / ca) * 100).toFixed(1) : 0}%</td>
              <td></td>
            </tr>
          </tbody>
        </table>

        {/* ═══ PAGE 2 : SIG + RATIOS ═══ */}
        <div className="page-break"></div>
        <PrintHeader year={data.year} />

        {/* Soldes Intermédiaires de Gestion */}
        <SectionTitle icon="fa-layer-group" title="Soldes Intermédiaires de Gestion (SIG)" />
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <tbody>
            <SigTableRow label="Production de l'exercice" value={sig.productionExercice} />
            <SigTableRow label="Marge de Production" value={sig.margeProduction} bold color="#1e293b" />
            <SigTableRow label="Valeur Ajoutée (VA)" value={sig.valeurAjoutee} bold />
            <SigTableRow label="EBITDA/Excédent Brut (EBE)" value={sig.ebe} bold color="#2563eb" />
            <SigTableRow label="Résultat d'Exploitation (REX)" value={sig.resultatExploitation} />
            <SigTableRow label="Résultat Financier" value={sig.resultatFinancier} color={sig.resultatFinancier >= 0 ? '#16a34a' : '#ef4444'} />
            <SigTableRow label="RCAI" value={sig.resultatCourant} />
            <SigTableRow label="Capacité d'Autofinancement (CAF)" value={caf} color="#d97706" />
            <SigTableRow label="Résultat Net Comptable" value={sig.resultatNet} bold color={sig.resultatNet >= 0 ? '#16a34a' : '#ef4444'} />
          </tbody>
        </table>

        {/* Bilan Simplifié */}
        <SectionTitle icon="fa-scale-balanced" title="Bilan Simplifié & Équilibre Financier" />
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
          <div>
            <div style={{ fontSize: 10, fontWeight: 800, color: '#3b82f6', textTransform: 'uppercase', letterSpacing: 2, marginBottom: 8 }}>Actif</div>
            <KpiRow label="Immobilisations" value={fmt(data.fixedAssets)} />
            <KpiRow label="Stocks" value={fmt(data.stocks)} />
            <KpiRow label="Créances clients" value={fmt(data.receivables)} />
            <KpiRow label="Trésorerie active" value={fmt(data.cashPositive)} />
            <KpiRow label="TOTAL ACTIF" value={fmt(data.totalAssets)} highlight />
          </div>
          <div>
            <div style={{ fontSize: 10, fontWeight: 800, color: '#16a34a', textTransform: 'uppercase', letterSpacing: 2, marginBottom: 8 }}>Passif</div>
            <KpiRow label="Capitaux propres" value={fmt(data.equity)} />
            <KpiRow label="Dettes financières" value={fmt(data.debt)} />
            <KpiRow label="Dettes fournisseurs" value={fmt(data.payables)} />
            <KpiRow label="Concours bancaires" value={fmt(data.bankOverdraft)} />
            <KpiRow label="TOTAL PASSIF" value={fmt(data.totalAssets)} highlight />
          </div>
        </div>

        <div style={{ marginTop: 12, display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 8 }}>
          <div style={{ backgroundColor: '#eff6ff', borderRadius: 10, padding: 12, textAlign: 'center', breakInside: 'avoid' }}>
            <div style={{ fontSize: 9, fontWeight: 800, color: '#3b82f6', textTransform: 'uppercase', letterSpacing: 2 }}>FRNG</div>
            <div style={{ fontSize: 18, fontWeight: 900, color: '#1e293b' }}>{fmtK(frng)}</div>
          </div>
          <div style={{ backgroundColor: '#fffbeb', borderRadius: 10, padding: 12, textAlign: 'center', breakInside: 'avoid' }}>
            <div style={{ fontSize: 9, fontWeight: 800, color: '#d97706', textTransform: 'uppercase', letterSpacing: 2 }}>BFR</div>
            <div style={{ fontSize: 18, fontWeight: 900, color: '#1e293b' }}>{fmtK(bfr)}</div>
          </div>
          <div style={{ backgroundColor: tn >= 0 ? '#f0fdf4' : '#fef2f2', borderRadius: 10, padding: 12, textAlign: 'center', breakInside: 'avoid' }}>
            <div style={{ fontSize: 9, fontWeight: 800, color: tn >= 0 ? '#16a34a' : '#ef4444', textTransform: 'uppercase', letterSpacing: 2 }}>Trésorerie Nette</div>
            <div style={{ fontSize: 18, fontWeight: 900, color: '#1e293b' }}>{fmtK(tn)}</div>
          </div>
        </div>

        {/* Ratios */}
        <SectionTitle icon="fa-gauge" title="Ratios Financiers" />
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 8 }}>
          <RatioBlock title="🧱 Structure" items={[
            { label: 'Autonomie Fin.', val: pct(ratios.financialAutonomy ? ratios.financialAutonomy * 100 : 0) },
            { label: 'Endettement', val: dec(ratios.debtRatio, 2) },
            { label: 'Capacité Remb.', val: `${dec(ratios.repaymentCapacity, 1)} ans` },
          ]} />
          <RatioBlock title="💧 Liquidité" items={[
            { label: 'FRNG', val: fmt(frng) },
            { label: 'BFR', val: fmt(bfr) },
            { label: 'Ratio Liquidité', val: dec(ratios.liquidityGeneral, 2) },
          ]} />
          <RatioBlock title="⚙️ Activité" items={[
            { label: 'Rotation Stocks', val: `${Math.round(360 / (data.inventoryTurnover || 1))} j` },
            { label: 'DSO (Clients)', val: `${Math.round(data.dso || 0)} j` },
            { label: 'DPO (Fourn.)', val: `${Math.round(data.dpo || 0)} j` },
          ]} />
          <RatioBlock title="💰 Rentabilité" items={[
            { label: 'ROE', val: pct(ratios.roe) },
            { label: 'ROA', val: pct(ratios.roa) },
            { label: 'Marge Exploit.', val: pct(ratios.operatingMargin) },
          ]} />
          <RatioBlock title="🏗️ Patrimoine" items={[
            { label: 'Immo / Total', val: pct(ratios.fixedAssetsWeight ? ratios.fixedAssetsWeight * 100 : 0) },
            { label: 'Fonds Propres', val: fmt(data.equity) },
            { label: 'Gearing', val: dec(ratios.gearing, 2) },
          ]} />
          <RatioBlock title="🛡️ Solvabilité" items={[
            { label: 'Solvabilité', val: dec(ratios.solvency, 2) },
            { label: 'CAF / CA', val: pct(ratios.cafOnRevenue) },
            { label: 'Couv. Intérêts', val: `${dec(ratios.interestCoverage, 1)}x` },
          ]} />
        </div>

        {/* ═══ PAGE 3 : ANALYSE IA ═══ */}
        {aiResult?.text && (
          <>
            <div className="page-break"></div>
            <PrintHeader year={data.year} />
            <SectionTitle icon="fa-wand-magic-sparkles" title={`Analyse IA — ${aiResult.modelUsed || 'Gemini'}`} />
            <div style={{ fontSize: 11, lineHeight: 1.7, color: '#334155', whiteSpace: 'pre-wrap' }}>
              {aiResult.text.split('\n').map((line, idx) => {
                if (line.startsWith('# '))
                  return <h1 key={idx} style={{ fontSize: 16, fontWeight: 900, color: '#0f172a', marginTop: 20, marginBottom: 8, borderBottom: '1px solid #e2e8f0', paddingBottom: 4, textTransform: 'uppercase' }}>{line.substring(2)}</h1>;
                if (line.startsWith('## '))
                  return <h2 key={idx} style={{ fontSize: 13, fontWeight: 800, color: '#2563eb', marginTop: 16, marginBottom: 6, textTransform: 'uppercase' }}>{line.substring(3)}</h2>;
                if (line.startsWith('### '))
                  return <h3 key={idx} style={{ fontSize: 12, fontWeight: 700, color: '#475569', marginTop: 12, marginBottom: 4 }}>{line.substring(4)}</h3>;
                if (line.match(/^\d\./))
                  return <div key={idx} style={{ fontWeight: 700, marginTop: 8, marginBottom: 4, color: '#0f172a' }}>{line}</div>;
                if (line.trim().startsWith('-'))
                  return <div key={idx} style={{ marginLeft: 16, marginBottom: 4, display: 'flex', gap: 6 }}><span style={{ color: '#94a3b8' }}>•</span><span>{line.substring(1).trim()}</span></div>;
                if (!line.trim()) return <div key={idx} style={{ height: 6 }}></div>;
                return <p key={idx} style={{ marginBottom: 6 }}>{line}</p>;
              })}
            </div>
          </>
        )}

        {/* Footer */}
        <div style={{ marginTop: 40, paddingTop: 12, borderTop: '2px solid #e2e8f0', display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: 9, color: '#94a3b8' }}>
          <span>ATCO BI — AuditCompta — Document confidentiel</span>
          <span>Généré automatiquement le {new Date().toLocaleDateString('fr-FR')} à {new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</span>
        </div>
      </div>
    </>
  );
};

export default PrintableReport;
