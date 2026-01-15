/**
 * AnalyticsRevenueCharts - Graphiques du Chiffre d'Affaires
 * Affiche CA mensuel et trimestriel
 */

import React from 'react';
import { Grid, Paper, Typography } from '@mui/material';
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend
} from 'recharts';
import { ChartCard } from '../common';

const AnalyticsRevenueCharts = ({ caMensuel = [], caTrimestriel = [] }) => {
  return (
    <Grid container spacing={2}>
      {/* CA Mensuel */}
      <Grid item xs={12} md={6}>
        <ChartCard
          title="Chiffre d'Affaires Mensuel"
          subtitle="Évolution au cours de l'année"
          height={350}
        >
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={caMensuel}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="mois" />
              <YAxis />
              <Tooltip 
                formatter={(value) => `€${value.toLocaleString('fr-FR')}`}
              />
              <Legend />
              <Line
                type="monotone"
                dataKey="ca"
                stroke="#2196f3"
                strokeWidth={2}
                dot={{ r: 4 }}
              />
            </LineChart>
          </ResponsiveContainer>
        </ChartCard>
      </Grid>

      {/* CA Trimestriel */}
      <Grid item xs={12} md={6}>
        <ChartCard
          title="Chiffre d'Affaires Trimestriel"
          subtitle="Comparaison par trimestre"
          height={350}
        >
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={caTrimestriel}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="trimestre" />
              <YAxis />
              <Tooltip 
                formatter={(value) => `€${value.toLocaleString('fr-FR')}`}
              />
              <Legend />
              <Bar
                dataKey="ca"
                fill="#2196f3"
                radius={[8, 8, 0, 0]}
              />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>
      </Grid>
    </Grid>
  );
};

export default AnalyticsRevenueCharts;
