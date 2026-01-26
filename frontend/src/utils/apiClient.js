/**
 * API Client Configuration for Laravel Backend
 * Handles authentication, token management, and API requests
 */

import axios from 'axios'
import { API_CONFIG, APP_CONFIG } from './config'

// Log configuration in development
if (typeof window !== 'undefined' && APP_CONFIG.DEBUG) {
  console.log('🔧 API Client Configuration:', {
    baseURL: API_CONFIG.BASE_URL,
    timeout: API_CONFIG.TIMEOUT,
    debug: APP_CONFIG.DEBUG
  })
}

// Create axios instance with base configuration
const apiClient = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  timeout: API_CONFIG.TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
})

if (typeof window !== 'undefined' && APP_CONFIG.DEBUG) {
  console.log('🚀 Axios Instance Created with BaseURL:', API_CONFIG.BASE_URL)
}

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    // Log request in debug mode
    if (typeof window !== 'undefined' && APP_CONFIG.DEBUG) {
      console.log(`🚀 API Request: ${config.method?.toUpperCase()} ${config.url}`)
    }

    return config
  },
  (error) => {
    if (typeof window !== 'undefined' && APP_CONFIG.DEBUG) {
      console.error('❌ API Request Error:', error)
    }
    return Promise.reject(error)
  }
)

// Response interceptor to handle errors and token expiration
apiClient.interceptors.response.use(
  (response) => {
    // Log response in debug mode
    if (typeof window !== 'undefined' && APP_CONFIG.DEBUG) {
      console.log(
        `✅ API Response: ${response.config.method?.toUpperCase()} ${response.config.url} (${response.status})`
      )
    }

    return response
  },
  (error) => {
    // Gracefully suppress logging for aborted/timeouts to avoid noisy console on refresh
    const isAborted =
      !error.response &&
      (error.code === 'ECONNABORTED' || /aborted|canceled/i.test(error.message || ''))
    if (isAborted) {
      return Promise.reject(new Error('Request aborted'))
    }

    // Log error in debug mode (non-aborted only)
    if (typeof window !== 'undefined' && APP_CONFIG.DEBUG) {
      console.error(`❌ API Error: ${error.config?.method?.toUpperCase()} ${error.config?.url}`, {
        status: error.response?.status,
        message: error.response?.data?.message || error.message,
        data: error.response?.data
      })
    }

    // Handle 401 Unauthorized (token expired or invalid)
    if (error.response?.status === 401) {
      console.warn('🔐 Authentication failed - clearing tokens and redirecting to login')

      // Clear invalid token
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      localStorage.removeItem('session_data')

      // Redirect to login if not already there
      if (window.location.pathname !== '/' && window.location.pathname !== '/login') {
        window.location.href = '/'
      }
    }

    // Optional small retry once for transient 5xx
    if (error.response?.status >= 500 && !error.config.__retriedOnce) {
      error.config.__retriedOnce = true
      return new Promise((resolve) => setTimeout(resolve, 400)).then(() =>
        apiClient.request(error.config)
      )
    }

    return Promise.reject(error)
  }
)

// API methods
export const authAPI = {
  // Login user
  async login(credentials) {
    try {
      const response = await apiClient.post('/login', credentials)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Login failed'
      }
    }
  },

  // Logout user (current session only)
  async logout() {
    try {
      const response = await apiClient.post('/logout')
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      // Even if logout fails on server, we should clear local data
      console.warn('Logout request failed:', error.message)
      return { success: true }
    }
  },

  // Logout from all sessions
  async logoutAll() {
    try {
      const response = await apiClient.post('/logout-all')
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  // Get active sessions
  async getActiveSessions() {
    try {
      const response = await apiClient.get('/sessions')
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  // Revoke a specific session
  async revokeSession(tokenId) {
    try {
      const response = await apiClient.post('/sessions/revoke', {
        token_id: tokenId
      })
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  // Get current user info
  async getCurrentUser() {
    try {
      const response = await apiClient.get('/user')
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  // Onboarding API methods
  async getOnboardingStatus() {
    try {
      const response = await apiClient.get('/onboarding/status')
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async acceptTerms() {
    try {
      const response = await apiClient.post('/onboarding/accept-terms')
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async acceptIctPolicy() {
    try {
      const response = await apiClient.post('/onboarding/accept-ict-policy')
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async submitDeclaration(declarationData) {
    try {
      // Handle both FormData (with files) and regular object data
      let requestData
      let headers = {}

      if (declarationData instanceof FormData) {
        // For file uploads, send FormData directly
        requestData = declarationData
        headers['Content-Type'] = 'multipart/form-data'
      } else {
        // For regular data, wrap in declaration_data object
        requestData = {
          declaration_data: declarationData
        }
      }

      const response = await apiClient.post('/onboarding/submit-declaration', requestData, {
        headers: headers
      })

      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message,
        errors: error.response?.data?.errors
      }
    }
  },

  async completeOnboarding() {
    try {
      const response = await apiClient.post('/onboarding/complete')
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async updateOnboardingStep(step) {
    try {
      const response = await apiClient.post('/onboarding/update-step', {
        step
      })
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async resetOnboarding(userId, type = 'all') {
    try {
      const response = await apiClient.post('/onboarding/reset', {
        user_id: userId,
        type: type
      })
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  // Admin API methods
  async getUsers(params = {}) {
    try {
      const response = await apiClient.get('/admin/users', { params })
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async getUserDetails(userId) {
    try {
      const response = await apiClient.get(`/admin/users/${userId}`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async resetUserOnboarding(userId, resetType = 'all') {
    try {
      const response = await apiClient.post('/admin/users/reset-onboarding', {
        user_id: userId,
        reset_type: resetType
      })
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async bulkResetOnboarding(userIds, resetType = 'all') {
    try {
      const response = await apiClient.post('/admin/users/bulk-reset-onboarding', {
        user_ids: userIds,
        reset_type: resetType
      })
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  },

  async getOnboardingStats() {
    try {
      const response = await apiClient.get('/admin/onboarding/stats')
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || error.message
      }
    }
  }
}

// Export the configured axios instance for other API calls
export default apiClient
