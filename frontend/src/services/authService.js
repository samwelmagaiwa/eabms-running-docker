import axios from 'axios'

const API_BASE_URL = process.env.VUE_APP_API_URL || 'http://localhost:8000/api'

// Create axios instance with default config
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: parseInt(process.env.VUE_APP_API_TIMEOUT) || 10000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

// Add auth token to requests
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Handle response errors
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status
    const method = (error.config?.method || '').toString().toLowerCase()
    const url = (error.config?.url || '').toString()

    if (status === 401) {
      // IMPORTANT: Do NOT auto-redirect on login failures, so the login
      // page can display "Invalid email or password" in the shared error banner.
      const isLoginRequest = method === 'post' && url.includes('/login')

      if (!isLoginRequest) {
        // Token expired or invalid on authenticated routes
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_data')
        localStorage.removeItem('session_data')
        window.location.href = '/login'
      }
    }

    return Promise.reject(error)
  }
)

export const authService = {
  /**
   * Login user with email and password
   * @param {Object} credentials - { email, password }
   * @returns {Promise<Object>} - Login response
   */
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
        message: error.response?.data?.message || 'Login failed',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Logout current user
   * @returns {Promise<Object>} - Logout response
   */
  async logout() {
    try {
      const response = await apiClient.post('/logout')
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      // Even if logout API fails, we should clear local state
      return {
        success: true,
        message: 'Logged out locally (API call failed)'
      }
    }
  },

  /**
   * Logout from all sessions
   * @returns {Promise<Object>} - Logout all response
   */
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
        message: error.response?.data?.message || 'Logout all failed'
      }
    }
  },

  /**
   * Get current user data
   * @returns {Promise<Object>} - User data response
   */
  async getCurrentUser() {
    try {
      const response = await apiClient.get('/current-user')
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to get user data',
        status: error.response?.status
      }
    }
  },

  /**
   * Get role-based redirect URL
   * @returns {Promise<Object>} - Redirect URL response
   */
  async getRoleBasedRedirect() {
    try {
      const response = await apiClient.get('/role-redirect')
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to get redirect URL',
        status: error.response?.status
      }
    }
  },

  /**
   * Get active sessions
   * @returns {Promise<Object>} - Active sessions response
   */
  async getActiveSessions() {
    try {
      const response = await apiClient.get('/active-sessions')
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to get active sessions'
      }
    }
  },

  /**
   * Revoke a specific session
   * @param {number} tokenId - Token ID to revoke
   * @returns {Promise<Object>} - Revoke session response
   */
  async revokeSession(tokenId) {
    try {
      const response = await apiClient.post('/revoke-session', {
        token_id: tokenId
      })
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to revoke session'
      }
    }
  },

  /**
   * Change password for the currently authenticated user
   * @param {{ current: string, new: string, confirm: string }} payload
   * @returns {Promise<Object>} - Change password response
   */
  async changePassword(payload) {
    try {
      const response = await apiClient.post('/change-password', {
        current_password: payload.current,
        password: payload.new,
        password_confirmation: payload.confirm
      })
      return {
        success: true,
        data: response.data,
        message: response.data?.message || 'Password changed successfully.'
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to change password.',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Request password reset OTP by phone (public, no auth)
   * @param {string} phone
   * @returns {Promise<Object>}
   */
  async requestPasswordResetByPhone(phone) {
    try {
      const response = await apiClient.post('/password-reset/request-by-phone', { phone })
      return {
        success: true,
        data: response.data,
        message: response.data?.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to send OTP.',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Verify password reset OTP (phone + otp)
   * @param {{ phone: string, otp: string }} payload
   * @returns {Promise<Object>}
   */
  async verifyPasswordResetOtp(payload) {
    try {
      const response = await apiClient.post('/password-reset/verify-otp', {
        phone: payload.phone,
        otp: payload.otp
      })
      return {
        success: true,
        data: response.data,
        message: response.data?.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to verify OTP.',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Reset password using phone + OTP, returns { user, token }
   * @param {{ phone: string, otp: string, password: string, password_confirmation: string }} payload
   * @returns {Promise<Object>}
   */
  async resetPasswordWithOtp(payload) {
    try {
      const response = await apiClient.post('/password-reset/reset-with-otp', {
        phone: payload.phone,
        otp: payload.otp,
        password: payload.password,
        password_confirmation: payload.password_confirmation
      })
      return {
        success: true,
        data: response.data?.data,
        message: response.data?.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to reset password.',
        errors: error.response?.data?.errors || {}
      }
    }
  }
}

export default authService
