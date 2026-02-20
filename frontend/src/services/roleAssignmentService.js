import axios from 'axios'

const API_BASE_URL = process.env.VUE_APP_API_URL || '/api'

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
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      localStorage.removeItem('session_data')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export const roleAssignmentService = {
  /**
   * Get all roles from database
   * @returns {Promise<Object>} - Roles response
   */
  async getAllRoles() {
    try {
      const response = await apiClient.get('/roles')
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch roles',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Get all users with their roles
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>} - Users response
   */
  async getUsersWithRoles(params = {}) {
    try {
      const response = await apiClient.get('/user-roles', { params })
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch users',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Assign roles to a user
   * @param {number} userId - User ID
   * @param {Array} roleIds - Array of role IDs
   * @returns {Promise<Object>} - Assignment response
   */
  async assignRolesToUser(userId, roleIds) {
    try {
      const response = await apiClient.post(`/user-roles/${userId}/assign`, {
        role_ids: roleIds
      })
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to assign roles',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Remove a specific role from a user
   * @param {number} userId - User ID
   * @param {number} roleId - Role ID
   * @returns {Promise<Object>} - Removal response
   */
  async removeRoleFromUser(userId, roleId) {
    try {
      const response = await apiClient.delete(`/user-roles/${userId}/roles/${roleId}`)
      return {
        success: true,
        message: response.data.message
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to remove role',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Get user role history
   * @param {number} userId - User ID
   * @returns {Promise<Object>} - History response
   */
  async getUserRoleHistory(userId) {
    try {
      const response = await apiClient.get(`/user-roles/${userId}/history`)
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch role history',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Get role assignment statistics
   * @returns {Promise<Object>} - Statistics response
   */
  async getRoleStatistics() {
    try {
      const response = await apiClient.get('/user-roles/statistics')
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch statistics',
        errors: error.response?.data?.errors || {}
      }
    }
  },

  /**
   * Get available permissions
   * @returns {Promise<Object>} - Permissions response
   */
  async getAvailablePermissions() {
    try {
      const response = await apiClient.get('/roles/permissions')
      return {
        success: true,
        data: response.data.data
      }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch permissions',
        errors: error.response?.data?.errors || {}
      }
    }
  }
}

export default roleAssignmentService
