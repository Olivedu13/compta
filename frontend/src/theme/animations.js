/**
 * Animations Système - Keyframes et Transitions réutilisables
 * Phase 4: Design System & Polish
 */

import { keyframes } from '@emotion/react';

// ============================================
// KEYFRAMES
// ============================================

export const animations = {
  // Fade
  fadeIn: keyframes`
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  `,

  fadeOut: keyframes`
    from {
      opacity: 1;
    }
    to {
      opacity: 0;
    }
  `,

  // Slide
  slideInUp: keyframes`
    from {
      transform: translateY(20px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  `,

  slideInDown: keyframes`
    from {
      transform: translateY(-20px);
      opacity: 0;
    }
    to {
      transform: translateY(0);
      opacity: 1;
    }
  `,

  slideInLeft: keyframes`
    from {
      transform: translateX(-20px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  `,

  slideInRight: keyframes`
    from {
      transform: translateX(20px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  `,

  slideOutUp: keyframes`
    from {
      transform: translateY(0);
      opacity: 1;
    }
    to {
      transform: translateY(-20px);
      opacity: 0;
    }
  `,

  slideOutDown: keyframes`
    from {
      transform: translateY(0);
      opacity: 1;
    }
    to {
      transform: translateY(20px);
      opacity: 0;
    }
  `,

  // Scale
  scaleIn: keyframes`
    from {
      transform: scale(0.95);
      opacity: 0;
    }
    to {
      transform: scale(1);
      opacity: 1;
    }
  `,

  scaleOut: keyframes`
    from {
      transform: scale(1);
      opacity: 1;
    }
    to {
      transform: scale(0.95);
      opacity: 0;
    }
  `,

  // Pulse
  pulse: keyframes`
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.5;
    }
  `,

  // Bounce
  bounce: keyframes`
    0%, 100% {
      transform: translateY(0);
    }
    50% {
      transform: translateY(-10px);
    }
  `,

  // Spin
  spin: keyframes`
    from {
      transform: rotate(0deg);
    }
    to {
      transform: rotate(360deg);
    }
  `,

  // Shake
  shake: keyframes`
    0%, 100% {
      transform: translateX(0);
    }
    10%, 30%, 50%, 70%, 90% {
      transform: translateX(-5px);
    }
    20%, 40%, 60%, 80% {
      transform: translateX(5px);
    }
  `,

  // Gradient Shift (pour backgrounds animés)
  gradientShift: keyframes`
    0% {
      background-position: 0% 50%;
    }
    50% {
      background-position: 100% 50%;
    }
    100% {
      background-position: 0% 50%;
    }
  `,

  // Glow
  glow: keyframes`
    0%, 100% {
      box-shadow: 0 0 5px rgba(33, 150, 243, 0.5);
    }
    50% {
      box-shadow: 0 0 20px rgba(33, 150, 243, 0.8);
    }
  `,

  // Skeleton Loading
  skeletonLoading: keyframes`
    0% {
      background-position: -1000px 0;
    }
    100% {
      background-position: 1000px 0;
    }
  `,

  // Success checkmark
  checkmark: keyframes`
    0% {
      stroke-dashoffset: 50;
      opacity: 0;
    }
    50% {
      opacity: 1;
    }
    100% {
      stroke-dashoffset: 0;
      opacity: 1;
    }
  `,
};

// ============================================
// ANIMATION PRESETS (prêts à l'emploi)
// ============================================

export const animationPresets = {
  // Entrance Animations
  fadeInSlow: {
    animation: `${animations.fadeIn} 500ms ease-in-out`,
  },
  fadeInFast: {
    animation: `${animations.fadeIn} 250ms ease-in-out`,
  },

  slideInUpSlow: {
    animation: `${animations.slideInUp} 500ms ease-out`,
  },
  slideInUpFast: {
    animation: `${animations.slideInUp} 250ms ease-out`,
  },

  slideInLeftSlow: {
    animation: `${animations.slideInLeft} 500ms ease-out`,
  },

  slideInRightSlow: {
    animation: `${animations.slideInRight} 500ms ease-out`,
  },

  scaleInSlow: {
    animation: `${animations.scaleIn} 500ms cubic-bezier(0.34, 1.56, 0.64, 1)`,
  },

  // Continuous Animations
  pulse: {
    animation: `${animations.pulse} 2s cubic-bezier(0.4, 0, 0.6, 1) infinite`,
  },

  bounce: {
    animation: `${animations.bounce} 1s ease-in-out infinite`,
  },

  spin: {
    animation: `${animations.spin} 1s linear infinite`,
  },

  glow: {
    animation: `${animations.glow} 2s ease-in-out infinite`,
  },

  // Exit Animations
  fadeOutFast: {
    animation: `${animations.fadeOut} 250ms ease-out`,
  },

  slideOutDownFast: {
    animation: `${animations.slideOutDown} 250ms ease-in`,
  },
};

// ============================================
// TRANSITION UTILITIES
// ============================================

export const transitions = {
  // Propriétés communes
  colorTransition: {
    transition: 'color 150ms ease-in-out, background-color 150ms ease-in-out',
  },

  shadowTransition: {
    transition: 'box-shadow 250ms ease-in-out',
  },

  scaleTransition: {
    transition: 'transform 250ms ease-in-out',
  },

  allTransition: {
    transition: 'all 250ms ease-in-out',
  },

  smoothTransition: {
    transition: 'all 350ms cubic-bezier(0.4, 0, 0.2, 1)',
  },
};

// ============================================
// HOVER EFFECTS
// ============================================

export const hoverEffects = {
  // Élévation
  elevate: {
    transition: 'box-shadow 250ms ease-in-out, transform 250ms ease-in-out',
    '&:hover': {
      boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1)',
      transform: 'translateY(-2px)',
    },
  },

  // Grossissement
  scale: {
    transition: 'transform 250ms ease-in-out',
    '&:hover': {
      transform: 'scale(1.05)',
    },
  },

  // Luminosité
  brighten: {
    transition: 'opacity 250ms ease-in-out',
    '&:hover': {
      opacity: 0.8,
    },
  },

  // Underline Effect
  underline: {
    position: 'relative',
    '&::after': {
      content: '""',
      position: 'absolute',
      bottom: 0,
      left: 0,
      width: '0%',
      height: '2px',
      backgroundColor: 'currentColor',
      transition: 'width 250ms ease-in-out',
    },
    '&:hover::after': {
      width: '100%',
    },
  },

  // Highlight
  highlight: {
    transition: 'background-color 250ms ease-in-out',
    '&:hover': {
      backgroundColor: 'rgba(33, 150, 243, 0.1)',
    },
  },
};

export default {
  animations,
  animationPresets,
  transitions,
  hoverEffects,
};
