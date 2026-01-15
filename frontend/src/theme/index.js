/**
 * Theme Index - Centralize all design system exports
 * Phase 4: Design System & Polish
 */

export { designTokens, useDesignTokens, getColorByStatus, getSpacingValue } from './designTokens';
export {
  animations,
  animationPresets,
  transitions,
  hoverEffects,
} from './animations';
export {
  breakpoints,
  media,
  layoutResponsive,
  displayResponsive,
  textResponsive,
  spacingResponsive,
} from './responsive';

// Thème Material-UI centralisé
export { default as muiTheme } from './theme';
