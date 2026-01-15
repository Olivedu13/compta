/**
 * Design Tokens - Système de Design Centralisé
 * Toutes les variables de style (colors, spacing, typography, breakpoints)
 * Phase 4: Design System & Polish
 */

export const designTokens = {
  // ============================================
  // COULEURS (Color Palette)
  // ============================================
  colors: {
    // Primaires
    primary: {
      50: '#e3f2fd',
      100: '#bbdefb',
      200: '#90caf9',
      300: '#64b5f6',
      400: '#42a5f5',
      500: '#2196f3', // Primary
      600: '#1e88e5',
      700: '#1976d2',
      800: '#1565c0',
      900: '#0d47a1',
    },
    
    // Secondaires
    secondary: {
      50: '#f3e5f5',
      100: '#e1bee7',
      200: '#ce93d8',
      300: '#ba68c8',
      400: '#ab47bc',
      500: '#9c27b0', // Secondary
      600: '#8e24aa',
      700: '#7b1fa2',
      800: '#6a1b9a',
      900: '#4a148c',
    },

    // Succès
    success: {
      50: '#e8f5e9',
      100: '#c8e6c9',
      200: '#a5d6a7',
      300: '#81c784',
      400: '#66bb6a',
      500: '#4caf50', // Success
      600: '#43a047',
      700: '#388e3c',
      800: '#2e7d32',
      900: '#1b5e20',
    },

    // Erreur
    error: {
      50: '#ffebee',
      100: '#ffcdd2',
      200: '#ef9a9a',
      300: '#e57373',
      400: '#ef5350',
      500: '#f44336', // Error
      600: '#e53935',
      700: '#d32f2f',
      800: '#c62828',
      900: '#b71c1c',
    },

    // Attention
    warning: {
      50: '#fff3e0',
      100: '#ffe0b2',
      200: '#ffcc80',
      300: '#ffb74d',
      400: '#ffa726',
      500: '#ff9800', // Warning
      600: '#fb8c00',
      700: '#f57c00',
      800: '#e65100',
      900: '#bf360c',
    },

    // Info
    info: {
      50: '#e0f2f1',
      100: '#b2dfdb',
      200: '#80cbc4',
      300: '#4db6ac',
      400: '#26a69a',
      500: '#00bcd4', // Info
      600: '#00acc1',
      700: '#0097a7',
      800: '#00838f',
      900: '#006064',
    },

    // Neutres
    neutral: {
      0: '#ffffff',
      50: '#fafafa',
      100: '#f5f5f5',
      200: '#eeeeee',
      300: '#e0e0e0',
      400: '#bdbdbd',
      500: '#9e9e9e',
      600: '#757575',
      700: '#616161',
      800: '#424242',
      900: '#212121',
      1000: '#000000',
    },

    // Sémantiques
    semantic: {
      positive: '#4caf50',   // ✓ succès
      negative: '#f44336',   // ✗ erreur
      warning: '#ff9800',    // ⚠️ attention
      info: '#2196f3',       // ℹ️ info
      focus: '#2196f3',      // Focalisé (keyboard)
    },

    // Bijouterie spécifiques
    bijouterie: {
      or: '#ffc107',         // Or (#FFC107)
      argent: '#c0c0c0',     // Argent
      platine: '#e5e4e2',    // Platine
      cuivre: '#b87333',     // Cuivre
      gemstone: '#9c27b0',   // Pierre précieuse
    },
  },

  // ============================================
  // TYPOGRAPHIE (Typography)
  // ============================================
  typography: {
    // Font Family
    fontFamily: {
      base: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
      mono: '"Roboto Mono", "Courier New", monospace',
      heading: '"Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
    },

    // Font Sizes
    fontSize: {
      xs: '0.75rem',      // 12px
      sm: '0.875rem',     // 14px
      base: '1rem',       // 16px
      lg: '1.125rem',     // 18px
      xl: '1.25rem',      // 20px
      '2xl': '1.5rem',    // 24px
      '3xl': '1.875rem',  // 30px
      '4xl': '2.25rem',   // 36px
      '5xl': '3rem',      // 48px
    },

    // Font Weights
    fontWeight: {
      thin: 100,
      extralight: 200,
      light: 300,
      normal: 400,
      medium: 500,
      semibold: 600,
      bold: 700,
      extrabold: 800,
      black: 900,
    },

    // Line Heights
    lineHeight: {
      tight: 1.2,
      normal: 1.5,
      relaxed: 1.75,
      loose: 2,
    },

    // Letter Spacing
    letterSpacing: {
      tight: '-0.05em',
      normal: '0',
      wide: '0.05em',
      wider: '0.1em',
      widest: '0.2em',
    },
  },

  // ============================================
  // ESPACEMENT (Spacing/Size Scale)
  // ============================================
  spacing: {
    0: '0',
    1: '0.25rem',    // 4px
    2: '0.5rem',     // 8px
    3: '0.75rem',    // 12px
    4: '1rem',       // 16px
    5: '1.25rem',    // 20px
    6: '1.5rem',     // 24px
    7: '1.75rem',    // 28px
    8: '2rem',       // 32px
    9: '2.25rem',    // 36px
    10: '2.5rem',    // 40px
    12: '3rem',      // 48px
    14: '3.5rem',    // 56px
    16: '4rem',      // 64px
    20: '5rem',      // 80px
    24: '6rem',      // 96px
    28: '7rem',      // 112px
    32: '8rem',      // 128px
    36: '9rem',      // 144px
    40: '10rem',     // 160px
    44: '11rem',     // 176px
    48: '12rem',     // 192px
    52: '13rem',     // 208px
    56: '14rem',     // 224px
    60: '15rem',     // 240px
    64: '16rem',     // 256px
    72: '18rem',     // 288px
    80: '20rem',     // 320px
    96: '24rem',     // 384px
  },

  // ============================================
  // BORDER RADIUS (Arrondi)
  // ============================================
  borderRadius: {
    none: '0',
    sm: '0.125rem',    // 2px
    base: '0.25rem',   // 4px
    md: '0.375rem',    // 6px
    lg: '0.5rem',      // 8px
    xl: '0.75rem',     // 12px
    '2xl': '1rem',     // 16px
    '3xl': '1.5rem',   // 24px
    full: '9999px',    // Rond complet
  },

  // ============================================
  // OMBRES (Shadows)
  // ============================================
  shadow: {
    none: 'none',
    sm: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
    base: '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
    md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
    lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
    xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
    '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
    inner: 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.06)',
  },

  // ============================================
  // TRANSITIONS/ANIMATIONS
  // ============================================
  transition: {
    fast: '150ms ease-in-out',
    base: '250ms ease-in-out',
    slow: '350ms ease-in-out',
    slower: '500ms ease-in-out',

    properties: {
      colors: 'color, background-color, border-color, fill, stroke',
      all: 'all',
      none: 'none',
    },

    timing: {
      linear: 'linear',
      easeIn: 'cubic-bezier(0.4, 0, 1, 1)',
      easeOut: 'cubic-bezier(0, 0, 0.2, 1)',
      easeInOut: 'cubic-bezier(0.4, 0, 0.2, 1)',
    },
  },

  // ============================================
  // BREAKPOINTS (Responsive Design)
  // ============================================
  breakpoint: {
    xs: '0px',       // Phone
    sm: '600px',     // Tablet
    md: '960px',     // Small Desktop
    lg: '1264px',    // Desktop
    xl: '1904px',    // Large Desktop
  },

  // ============================================
  // Z-INDEX (Stacking Order)
  // ============================================
  zIndex: {
    hide: -1,
    base: 0,
    dropdown: 1000,
    sticky: 1020,
    fixed: 1030,
    backdrop: 1040,
    offcanvas: 1050,
    modal: 1060,
    popover: 1070,
    tooltip: 1080,
    notification: 1090,
  },

  // ============================================
  // OPACITÉ (Opacity)
  // ============================================
  opacity: {
    0: '0',
    5: '0.05',
    10: '0.1',
    20: '0.2',
    25: '0.25',
    30: '0.3',
    40: '0.4',
    50: '0.5',
    60: '0.6',
    70: '0.7',
    75: '0.75',
    80: '0.8',
    90: '0.9',
    95: '0.95',
    100: '1',
  },

  // ============================================
  // COMPOSANT DEFAULTS
  // ============================================
  components: {
    // Boutons
    button: {
      borderRadius: '0.5rem',
      padding: {
        sm: '0.5rem 1rem',
        md: '0.75rem 1.5rem',
        lg: '1rem 2rem',
      },
      fontSize: {
        sm: '0.875rem',
        md: '1rem',
        lg: '1.125rem',
      },
      fontWeight: 600,
      transition: '150ms ease-in-out',
    },

    // Input/Form
    input: {
      borderRadius: '0.5rem',
      padding: '0.75rem 1rem',
      fontSize: '1rem',
      lineHeight: 1.5,
      borderWidth: '1px',
      transition: '150ms ease-in-out',
    },

    // Card
    card: {
      borderRadius: '0.75rem',
      padding: '1.5rem',
      boxShadow: '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
      transition: '250ms ease-in-out',
    },

    // Badge
    badge: {
      borderRadius: '9999px',
      padding: '0.25rem 0.75rem',
      fontSize: '0.875rem',
      fontWeight: 600,
    },
  },
};

/**
 * Hooks et Utilities pour utiliser les tokens
 */
export const useDesignTokens = () => designTokens;

export const getColorByStatus = (status) => {
  const statusMap = {
    success: designTokens.colors.success[500],
    error: designTokens.colors.error[500],
    warning: designTokens.colors.warning[500],
    info: designTokens.colors.info[500],
    pending: designTokens.colors.neutral[500],
  };
  return statusMap[status] || designTokens.colors.neutral[500];
};

export const getSpacingValue = (key) => designTokens.spacing[key] || '0';

export default designTokens;
