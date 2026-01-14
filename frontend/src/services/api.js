import axios from 'axios';

/**
 * Service d'appel API
 * Point de communication avec le backend PHP
 */

const API_BASE_URL = '/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json'
  },
  timeout: 300000 // 5 minutes pour les imports lourds
});

// Intercepteur pour gestion des erreurs
api.interceptors.response.use(
  response => response,
  error => {
    console.error('API Error:', error);
    return Promise.reject(error);
  }
);

export const apiService = {
  // ========================================
  // Santé de l'API
  // ========================================
  
  getHealth() {
    return api.get('/health');
  },

  // ========================================
  // Balance & Données
  // ========================================

  getBalance(exercice, page = 1, limit = 100) {
    return api.get(`/balance/${exercice}`, {
      params: { page, limit }
    });
  },

  getEcritures(exercice, { page = 1, limit = 50, compte = null, journal = null, dateDebut = null, dateFin = null } = {}) {
    return api.get(`/ecritures/${exercice}`, {
      params: {
        page,
        limit,
        compte,
        journal,
        date_debut: dateDebut,
        date_fin: dateFin
      }
    });
  },

  // ========================================
  // SIG & Indicateurs
  // ========================================

  getSIG(exercice) {
    return api.get(`/sig/${exercice}`);
  },

  getSIGDetail(exercice) {
    return api.get(`/sig/${exercice}/detail`);
  },

  getKPIs(exercice) {
    return api.get(`/kpis/${exercice}`);
  },

  // ========================================
  // Références
  // ========================================

  getPlanComptable() {
    return api.get('/plan-comptable');
  },

  getJournaux() {
    return api.get('/journaux');
  },

  // ========================================
  // Imports
  // ========================================

  importFEC(file, onUploadProgress) {
    const formData = new FormData();
    formData.append('file', file);

    return api.post('/import/fec', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress
    });
  },

  importExcel(file, sheetName = null, onUploadProgress) {
    const formData = new FormData();
    formData.append('file', file);
    if (sheetName) {
      formData.append('sheet_name', sheetName);
    }

    return api.post('/import/excel', formData, {
      onUploadProgress
    });
  },

  importArchive(file, onUploadProgress) {
    const formData = new FormData();
    formData.append('file', file);

    return api.post('/import/archive', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress
    });
  },

  // ========================================
  // Gestion des années
  // ========================================

  getAnnees() {
    return api.get('/annees');
  },

  getAnneeExists(annee) {
    return api.get(`/annee/${annee}/exists`);
  },

  clearAnnee(annee) {
    return api.post(`/annee/${annee}/clear`);
  },

  getComparaison(annees) {
    const anneesParam = annees.join(',');
    return api.get(`/comparaison/annees?annees=${anneesParam}`);
  },

  // ========================================
  // Actions
  // ========================================

  recalculBalance(exercice) {
    return api.post('/recalcul-balance', {
      exercice
    });
  }
};

export default apiService;
