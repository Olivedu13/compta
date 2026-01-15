/**
 * Responsive Design Utilities
 * Media Queries et Breakpoints gérés centralisés
 * Phase 4: Design System & Polish
 */

import { css } from '@emotion/react';

// ============================================
// BREAKPOINTS (du fichier designTokens)
// ============================================

export const breakpoints = {
  xs: 0,      // Phone
  sm: 600,    // Tablet
  md: 960,    // Small Desktop
  lg: 1264,   // Desktop
  xl: 1904,   // Large Desktop
};

// ============================================
// MEDIA QUERY FUNCTIONS (Mobile-First)
// ============================================

/**
 * Mobile-First Approach: On part du mobile, on augmente pour les écrans plus grands
 */

export const media = {
  // xs: 0px and up (DEFAULT - no query needed)
  xs: (styles) => css`
    @media (min-width: ${breakpoints.xs}px) {
      ${styles}
    }
  `,

  // sm: 600px and up (Tablet)
  sm: (styles) => css`
    @media (min-width: ${breakpoints.sm}px) {
      ${styles}
    }
  `,

  // md: 960px and up (Small Desktop)
  md: (styles) => css`
    @media (min-width: ${breakpoints.md}px) {
      ${styles}
    }
  `,

  // lg: 1264px and up (Desktop)
  lg: (styles) => css`
    @media (min-width: ${breakpoints.lg}px) {
      ${styles}
    }
  `,

  // xl: 1904px and up (Large Desktop)
  xl: (styles) => css`
    @media (min-width: ${breakpoints.xl}px) {
      ${styles}
    }
  `,

  // Écrans petits (max-width)
  maxSm: (styles) => css`
    @media (max-width: ${breakpoints.sm - 1}px) {
      ${styles}
    }
  `,

  maxMd: (styles) => css`
    @media (max-width: ${breakpoints.md - 1}px) {
      ${styles}
    }
  `,

  // Orientation
  portrait: (styles) => css`
    @media (orientation: portrait) {
      ${styles}
    }
  `,

  landscape: (styles) => css`
    @media (orientation: landscape) {
      ${styles}
    }
  `,

  // High DPI (Retina displays)
  retina: (styles) => css`
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
      ${styles}
    }
  `,

  // Print
  print: (styles) => css`
    @media print {
      ${styles}
    }
  `,

  // Dark mode
  darkMode: (styles) => css`
    @media (prefers-color-scheme: dark) {
      ${styles}
    }
  `,

  // Light mode
  lightMode: (styles) => css`
    @media (prefers-color-scheme: light) {
      ${styles}
    }
  `,

  // Reduced motion (accessibility)
  prefersReducedMotion: (styles) => css`
    @media (prefers-reduced-motion: reduce) {
      ${styles}
    }
  `,

  // Touch device
  touch: (styles) => css`
    @media (hover: none) {
      ${styles}
    }
  `,

  // Mouse/Pointer device
  pointer: (styles) => css`
    @media (hover: hover) {
      ${styles}
    }
  `,
};

// ============================================
// LAYOUT HELPERS
// ============================================

export const layoutResponsive = {
  // Grid responsive 1 → 2 → 3 → 4 colonnes
  containerGrid: css`
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;

    ${media.sm(css`
      grid-template-columns: repeat(2, 1fr);
    `)}

    ${media.md(css`
      grid-template-columns: repeat(3, 1fr);
    `)}

    ${media.lg(css`
      grid-template-columns: repeat(4, 1fr);
    `)}
  `,

  // Container avec padding responsive
  container: css`
    width: 100%;
    padding: 1rem;
    margin: 0 auto;

    ${media.sm(css`
      max-width: 600px;
      padding: 1.5rem;
    `)}

    ${media.md(css`
      max-width: 960px;
      padding: 2rem;
    `)}

    ${media.lg(css`
      max-width: 1264px;
      padding: 2.5rem;
    `)}

    ${media.xl(css`
      max-width: 1904px;
      padding: 3rem;
    `)}
  `,

  // Sidebar layout 1 col mobile → 2 col desktop
  sidebarLayout: css`
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;

    ${media.md(css`
      grid-template-columns: 1fr 300px;
    `)}

    ${media.lg(css`
      grid-template-columns: 1fr 400px;
    `)}
  `,

  // Hero section responsive
  hero: css`
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;

    ${media.md(css`
      min-height: 500px;
    `)}

    ${media.lg(css`
      min-height: 600px;
    `)}
  `,
};

// ============================================
// DISPLAY UTILITIES
// ============================================

export const displayResponsive = {
  // Afficher/Masquer sur breakpoints
  hideMobile: css`
    ${media.maxSm(css`
      display: none;
    `)}
  `,

  hideTablet: css`
    ${media.maxMd(css`
      display: none;
    `)}
  `,

  showMobileOnly: css`
    ${media.sm(css`
      display: none;
    `)}
  `,

  showTabletOnly: css`
    display: none;
    ${media.sm(css`
      display: block;
    `)}
    ${media.md(css`
      display: none;
    `)}
  `,

  showDesktopOnly: css`
    ${media.maxMd(css`
      display: none;
    `)}
  `,
};

// ============================================
// TEXT RESPONSIF
// ============================================

export const textResponsive = {
  // Font size responsive
  h1: css`
    font-size: 1.875rem;
    ${media.sm(css`
      font-size: 2.25rem;
    `)}
    ${media.md(css`
      font-size: 3rem;
    `)}
  `,

  h2: css`
    font-size: 1.5rem;
    ${media.sm(css`
      font-size: 1.875rem;
    `)}
    ${media.md(css`
      font-size: 2.25rem;
    `)}
  `,

  h3: css`
    font-size: 1.25rem;
    ${media.sm(css`
      font-size: 1.5rem;
    `)}
    ${media.md(css`
      font-size: 1.875rem;
    `)}
  `,

  body: css`
    font-size: 0.875rem;
    ${media.sm(css`
      font-size: 1rem;
    `)}
    ${media.md(css`
      font-size: 1.125rem;
    `)}
  `,
};

// ============================================
// SPACING RESPONSIF
// ============================================

export const spacingResponsive = {
  padding: {
    sm: css`
      padding: 1rem;
      ${media.sm(css`
        padding: 1.5rem;
      `)}
      ${media.md(css`
        padding: 2rem;
      `)}
    `,
    md: css`
      padding: 1.5rem;
      ${media.sm(css`
        padding: 2rem;
      `)}
      ${media.md(css`
        padding: 2.5rem;
      `)}
    `,
    lg: css`
      padding: 2rem;
      ${media.sm(css`
        padding: 2.5rem;
      `)}
      ${media.md(css`
        padding: 3rem;
      `)}
    `,
  },
};

export default {
  breakpoints,
  media,
  layoutResponsive,
  displayResponsive,
  textResponsive,
  spacingResponsive,
};
