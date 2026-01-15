/**
 * Example Test Suite - Composants Common
 * Phase 5: Tests & Finalisation
 * 
 * À copier et adapter pour les autres composants
 */

import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { LoadingOverlay, ErrorBoundary } from '../components/common';

// ============================================
// Tests: LoadingOverlay
// ============================================

describe('LoadingOverlay', () => {
  it('should render loading overlay when open is true', () => {
    render(
      <LoadingOverlay open={true} message="Chargement..." />
    );
    
    expect(screen.getByText('Chargement...')).toBeInTheDocument();
  });

  it('should not render when open is false', () => {
    const { container } = render(
      <LoadingOverlay open={false} message="Chargement..." />
    );
    
    expect(container.firstChild).toBeEmptyDOMElement();
  });

  it('should render CircularProgress component', () => {
    const { container } = render(
      <LoadingOverlay open={true} message="Chargement..." />
    );
    
    expect(container.querySelector('svg')).toBeInTheDocument();
  });

  it('should have backdrop styling', () => {
    const { container } = render(
      <LoadingOverlay open={true} message="Chargement..." />
    );
    
    const backdrop = container.querySelector('[class*="MuiBackdrop"]');
    expect(backdrop).toBeInTheDocument();
  });
});

// ============================================
// Tests: ErrorBoundary
// ============================================

describe('ErrorBoundary', () => {
  // Composant test qui lance une erreur
  const ErrorComponent = () => {
    throw new Error('Test error');
  };

  const ValidComponent = () => (
    <div>Valid component</div>
  );

  it('should render children when there is no error', () => {
    render(
      <ErrorBoundary>
        <ValidComponent />
      </ErrorBoundary>
    );
    
    expect(screen.getByText('Valid component')).toBeInTheDocument();
  });

  it('should catch error and display fallback UI', () => {
    // Supprimer les warnings console pour ce test
    jest.spyOn(console, 'error').mockImplementation(() => {});
    
    render(
      <ErrorBoundary>
        <ErrorComponent />
      </ErrorBoundary>
    );
    
    expect(screen.getByText(/une erreur s'est produite/i)).toBeInTheDocument();
    console.error.mockRestore();
  });

  it('should display error details in development mode', () => {
    jest.spyOn(console, 'error').mockImplementation(() => {});
    
    render(
      <ErrorBoundary>
        <ErrorComponent />
      </ErrorBoundary>
    );
    
    expect(screen.getByText(/test error/i)).toBeInTheDocument();
    console.error.mockRestore();
  });
});

// ============================================
// Tests: Integration
// ============================================

describe('Common Components Integration', () => {
  it('should work together LoadingOverlay + ErrorBoundary', () => {
    render(
      <ErrorBoundary>
        <LoadingOverlay open={true} message="Intégration test" />
      </ErrorBoundary>
    );
    
    expect(screen.getByText('Intégration test')).toBeInTheDocument();
  });
});

// ============================================
// Tests Snapshot (à utiliser avec modération)
// ============================================

describe('Component Snapshots', () => {
  it('LoadingOverlay snapshot', () => {
    const { container } = render(
      <LoadingOverlay open={true} message="Snapshot test" />
    );
    
    expect(container.firstChild).toMatchSnapshot();
  });
});
