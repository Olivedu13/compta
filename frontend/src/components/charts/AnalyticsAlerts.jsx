/**
 * AnalyticsAlerts - Alertes et Recommandations
 */

import React from 'react';
import { Paper, Box, Typography } from '@mui/material';

const AnalyticsAlerts = ({ ratios = {}, solvabilite = {}, cycles = {}, topClients = [], ca = {} }) => {
  const alerts = [];

  // Frais Bancaires élevés
  if (parseFloat(ratios.ratio_frais_banc) > 2.5) {
    alerts.push({
      level: 'critical',
      icon: '🔴',
      title: 'Frais Bancaires élevés',
      message: `${ratios.ratio_frais_banc}% du CA - Audit des conditions bancaires recommandé`
    });
  }

  // Endettement élevé
  if (solvabilite.endettement > 2) {
    alerts.push({
      level: 'critical',
      icon: '🔴',
      title: 'Endettement élevé',
      message: `${solvabilite.endettement}x - Attention sur la capacité de remboursement`
    });
  }

  // Cycle de conversion long
  if (cycles.cycle_conversion > 60) {
    alerts.push({
      level: 'warning',
      icon: '🟠',
      title: 'Cycle de conversion long',
      message: `${cycles.cycle_conversion?.toFixed(1)} jours - Risque de trésorerie en période creuse`
    });
  }

  // Concentration client
  if (topClients.length > 0) {
    const topClientPercent = (topClients[0].ca / Math.abs(ca.total)) * 100;
    if (topClientPercent > 15) {
      alerts.push({
        level: 'warning',
        icon: '🟠',
        title: 'Concentration client critique',
        message: `Premier client: ${topClientPercent.toFixed(1)}% du CA`
      });
    }
  }

  // Coût matière élevé
  if (parseFloat(ratios.ratio_achats) > 65) {
    alerts.push({
      level: 'warning',
      icon: '🟠',
      title: 'Coût matière élevé',
      message: `${ratios.ratio_achats}% - Marge brute comprimée`
    });
  }

  // Liquidité faible
  if (solvabilite.ratio_liquidite < 1) {
    alerts.push({
      level: 'critical',
      icon: '🔴',
      title: 'Liquidité insuffisante',
      message: `${solvabilite.ratio_liquidite?.toFixed(2)}x - Risque de défaut de paiement`
    });
  }

  // Autonomie faible
  if (solvabilite.ratio_autonomie < 30) {
    alerts.push({
      level: 'warning',
      icon: '🟠',
      title: 'Autonomie financière faible',
      message: `${solvabilite.ratio_autonomie?.toFixed(1)}% - Dépendance élevée aux tiers`
    });
  }

  // DSO élevé
  if (cycles.dso_clients > 60) {
    alerts.push({
      level: 'info',
      icon: '🔵',
      title: 'DSO clients élevé',
      message: `${cycles.dso_clients?.toFixed(1)} jours - Suivi des impayés recommandé`
    });
  }

  // DPO faible
  if (cycles.dpo_fournisseurs < 30) {
    alerts.push({
      level: 'info',
      icon: '🔵',
      title: 'DPO fournisseurs bas',
      message: `${cycles.dpo_fournisseurs?.toFixed(1)} jours - Paiements rapides requis`
    });
  }

  const getAlertColor = (level) => {
    switch (level) {
      case 'critical':
        return '#ffebee';
      case 'warning':
        return '#fff8e1';
      case 'info':
        return '#e3f2fd';
      default:
        return '#f5f5f5';
    }
  };

  const getBorderColor = (level) => {
    switch (level) {
      case 'critical':
        return '#f44336';
      case 'warning':
        return '#ff9800';
      case 'info':
        return '#2196f3';
      default:
        return '#bdbdbd';
    }
  };

  if (alerts.length === 0) {
    return (
      <Paper sx={{ p: 3, backgroundColor: '#e8f5e9', borderLeft: '4px solid #4caf50' }}>
        <Typography variant="body1" sx={{ color: '#4caf50', fontWeight: 'bold' }}>
          ✅ Aucune alerte majeure détectée
        </Typography>
        <Typography variant="body2" color="textSecondary" sx={{ mt: 1 }}>
          La santé financière de l'entreprise est globalement stable.
        </Typography>
      </Paper>
    );
  }

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
      {(alerts || []).map((alert, idx) => (
        <Paper
          key={idx}
          sx={{
            p: 2.5,
            backgroundColor: getAlertColor(alert.level),
            borderLeft: `4px solid ${getBorderColor(alert.level)}`
          }}
        >
          <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 1.5 }}>
            <Typography sx={{ fontSize: 20, mt: 0.2 }}>
              {alert.icon}
            </Typography>
            <Box>
              <Typography variant="subtitle2" sx={{ fontWeight: 'bold' }}>
                {alert.title}
              </Typography>
              <Typography variant="body2" color="textSecondary" sx={{ mt: 0.5 }}>
                {alert.message}
              </Typography>
            </Box>
          </Box>
        </Paper>
      ))}
    </Box>
  );
};

export default AnalyticsAlerts;
