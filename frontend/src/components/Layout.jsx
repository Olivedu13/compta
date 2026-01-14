import React, { useState, useEffect } from 'react';
import {
  AppBar,
  Toolbar,
  Drawer,
  Box,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  Divider,
  Typography,
  Container,
  useTheme,
  useMediaQuery
} from '@mui/material';
import {
  Dashboard as DashboardIcon,
  CloudUpload as CloudUploadIcon,
  BarChart as BarChartIcon,
  AccountBalance as AccountBalanceIcon,
  Settings as SettingsIcon,
  Menu as MenuIcon
} from '@mui/icons-material';

import Dashboard from '../pages/Dashboard';
import ImportPage from '../pages/ImportPage';
import BalancePage from '../pages/BalancePage';
import SIGPage from '../pages/SIGPage';

/**
 * Layout principal avec navigation
 */

const DRAWER_WIDTH = 280;

export default function Layout() {
  const [currentPage, setCurrentPage] = useState('dashboard');
  const [mobileOpen, setMobileOpen] = useState(false);
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));

  const navigationItems = [
    { id: 'dashboard', label: 'Dashboard', icon: <DashboardIcon /> },
    { id: 'import', label: 'Import FEC/Excel', icon: <CloudUploadIcon /> },
    { id: 'sig', label: 'Rapports SIG', icon: <BarChartIcon /> },
    { id: 'balance', label: 'Balance', icon: <AccountBalanceIcon /> },
    { id: 'settings', label: 'Configuration', icon: <SettingsIcon /> }
  ];

  const renderPage = () => {
    switch (currentPage) {
      case 'dashboard':
        return <Dashboard />;
      case 'import':
        return <ImportPage />;
      case 'balance':
        return <BalancePage />;
      case 'sig':
        return <SIGPage />;
      case 'settings':
        return <Typography>Configuration (À faire)</Typography>;
      default:
        return <Dashboard />;
    }
  };

  const drawer = (
    <Box sx={{ overflow: 'auto' }}>
      <Divider />
      <List>
        {navigationItems.map((item) => (
          <ListItem
            key={item.id}
            button
            selected={currentPage === item.id}
            onClick={() => {
              setCurrentPage(item.id);
              setMobileOpen(false);
            }}
            sx={{
              bgcolor: currentPage === item.id ? 'action.selected' : 'transparent',
              '&:hover': {
                bgcolor: 'action.hover'
              }
            }}
          >
            <ListItemIcon sx={{ color: currentPage === item.id ? 'primary.main' : 'inherit' }}>
              {item.icon}
            </ListItemIcon>
            <ListItemText primary={item.label} />
          </ListItem>
        ))}
      </List>
    </Box>
  );

  return (
    <Box sx={{ display: 'flex' }}>
      {/* Drawer - Sidebar Navigation */}
      {isMobile ? (
        <Drawer
          variant="temporary"
          open={mobileOpen}
          onClose={() => setMobileOpen(false)}
          sx={{
            width: DRAWER_WIDTH,
            flexShrink: 0,
            '& .MuiDrawer-paper': {
              width: DRAWER_WIDTH,
              boxSizing: 'border-box'
            }
          }}
        >
          {drawer}
        </Drawer>
      ) : (
        <Drawer
          variant="permanent"
          sx={{
            width: DRAWER_WIDTH,
            flexShrink: 0,
            '& .MuiDrawer-paper': {
              width: DRAWER_WIDTH,
              boxSizing: 'border-box'
            }
          }}
        >
          {drawer}
        </Drawer>
      )}

      {/* Main Content */}
      <Box
        component="main"
        sx={{
          flexGrow: 1,
          p: 3,
          bgcolor: '#f8fafc',
          minHeight: '100vh'
        }}
      >
        <Container maxWidth="lg">
          {renderPage()}
        </Container>
      </Box>
    </Box>
  );
}
