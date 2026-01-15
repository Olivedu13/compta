import axios from 'axios';

/**
 * Service d'appel API
 * Point de communication avec le backend PHP
 * Support JWT Token Authentication
 */

const API_BASE_URL = '/api';

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json'
  },
  timeout: 300000 // 5 minutes pour les imports lourds
});

// Intercepteur pour ajouter le token JWT
api.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  error => Promise.reject(error)
);

// Intercepteur pour gestion des erreurs
api.interceptors.response.use(
  response => response,
  error => {
    // Si 401 Unauthorized, token expiré - rediriger vers login
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
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
    return api.get(`/v1/balance/simple.php?exercice=${exercice}&page=${page}&limit=${limit}`);
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
    return api.get(`/v1/sig/simple.php?exercice=${exercice}`);
  },

  getSIGDetail(exercice) {
    return api.get(`/v1/sig/simple.php?exercice=${exercice}`);
  },

  getKPIs(exercice) {
    return api.get(`/v1/kpis/simple.php?exercice=${exercice}`);
  },

  getKPIsDetailed(exercice) {
    return api.get(`/v1/kpis/detailed.php?exercice=${exercice}`);
  },

  getAnalyse(exercice) {
    return api.get(`/v1/analytics/simple.php?exercice=${exercice}`);
  },

  getAnalyticsAdvanced(exercice) {
    return api.get(`/v1/analytics/advanced.php?exercice=${exercice}`);
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

    // Utilise simple-import.php (sans /api/ car baseURL=='/api')
    return api.post('/simple-import.php', formData, {
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
    return api.get('/v1/years/list.php');
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
