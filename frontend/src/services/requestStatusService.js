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
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

const requestStatusService = {
  /**
   * Get all requests for the authenticated user
   * @param {Object} params - Query parameters
   * @returns {Promise<Object>} API response
   */
  async getRequests(params = {}) {
    try {
      const response = await apiClient.get('/request-status', { params })
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error fetching requests:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to fetch requests',
        data: null
      }
    }
  },

  /**
   * Get specific request details
   * @param {string} id - Request ID
   * @param {string} type - Request type (combined_access, booking_service)
   * @returns {Promise<Object>} API response
   */
  async getRequestDetails(id, type) {
    try {
      console.log('🔍 Fetching request details:', { id, type })

      const response = await apiClient.get('/request-status/details', {
        params: { id, type }
      })

      console.log('✅ Request details response:', response.data)

      // Ensure approval workflow data is properly structured
      let requestData = response.data.data
      if (requestData && type === 'combined_access') {
        // Structure HOD approval data for better access
        if (requestData.hod_approved_at || requestData.hod_approval_comment) {
          requestData.hodApproval = {
            status: requestData.hod_approval_status || 'pending',
            approved_at: requestData.hod_approved_at,
            approved_by: requestData.hod_approved_by,
            approved_by_name: requestData.hod_approved_by_name,
            comment: requestData.hod_approval_comment,
            rejection_reason: requestData.hod_rejection_reason || requestData.hod_approval_comment
          }
        }

        // Structure other approval steps similarly
        ;['divisional_director', 'ict_director', 'head_of_it', 'ict_officer'].forEach((role) => {
          const statusField = `${role}_approval_status`
          const dateField = `${role}_approved_at`
          const byField = `${role}_approved_by`
          const commentField = `${role}_approval_comment`

          if (requestData[statusField] || requestData[dateField] || requestData[commentField]) {
            requestData[`${role.replace('_', '')}Approval`] = {
              status: requestData[statusField] || 'pending',
              approved_at: requestData[dateField],
              approved_by: requestData[byField],
              comment: requestData[commentField],
              rejection_reason: requestData[commentField]
            }
          }
        })
      }

      return {
        success: true,
        data: requestData,
        message: response.data.message
      }
    } catch (error) {
      console.error('❌ Error fetching request details:', {
        error: error.message,
        response: error.response?.data,
        status: error.response?.status,
        statusText: error.response?.statusText,
        headers: error.response?.headers,
        config: {
          url: error.config?.url,
          method: error.config?.method,
          params: error.config?.params
        },
        params: { id, type }
      })

      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to fetch request details',
        data: null
      }
    }
  },

  /**
   * Get request statistics for the authenticated user
   * @returns {Promise<Object>} API response
   */
  async getStatistics() {
    try {
      const response = await apiClient.get('/request-status/statistics')
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error fetching statistics:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to fetch statistics',
        data: null
      }
    }
  },

  /**
   * Search requests with filters
   * @param {Object} filters - Search filters
   * @returns {Promise<Object>} API response
   */
  async searchRequests(filters = {}) {
    try {
      const params = {
        search: filters.search || '',
        status: filters.status || '',
        type: filters.type || '',
        sort_by: filters.sortBy || 'created_at',
        sort_order: filters.sortOrder || 'desc',
        per_page: filters.perPage || 15,
        page: filters.page || 1
      }

      // Remove empty parameters
      Object.keys(params).forEach((key) => {
        if (params[key] === '' || params[key] === null || params[key] === undefined) {
          delete params[key]
        }
      })

      const response = await apiClient.get('/request-status', { params })
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error searching requests:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to search requests',
        data: null
      }
    }
  },

  /**
   * Get requests with pagination
   * @param {number} page - Page number
   * @param {number} perPage - Items per page
   * @param {Object} filters - Additional filters
   * @returns {Promise<Object>} API response
   */
  async getRequestsPaginated(page = 1, perPage = 15, filters = {}) {
    try {
      const params = {
        page,
        per_page: perPage,
        ...filters
      }

      const response = await apiClient.get('/request-status', { params })
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error fetching paginated requests:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to fetch requests',
        data: null
      }
    }
  },

  /**
   * Refresh requests data
   * @param {Object} currentFilters - Current applied filters
   * @returns {Promise<Object>} API response
   */
  async refreshRequests(currentFilters = {}) {
    return this.getRequests(currentFilters)
  },

  /**
   * Get request type display name
   * @param {string} type - Request type
   * @returns {string} Display name
   */
  getRequestTypeDisplayName(type) {
    const typeNames = {
      combined_access: 'Combined Access',
      booking_service: 'Booking Service',
      jeeva_access: 'Jeeva Access',
      wellsoft_access: 'Wellsoft Access',
      internet_access: 'Internet Access'
    }
    return typeNames[type] || type
  },

  /**
   * Get status display name
   * @param {string} status - Status code
   * @returns {string} Display name
   */
  getStatusDisplayName(status) {
    const statusNames = {
      pending: 'Pending',
      pending_hod: 'Pending HOD Approval',
      pending_divisional: 'Pending Divisional Approval',
      pending_ict_director: 'Pending ICT Director Approval',
      pending_head_it: 'Pending Head IT Approval',
      pending_ict_officer: 'Pending ICT Officer Approval',
      approved: 'Approved',
      hod_approved: 'HOD Approved',
      divisional_approved: 'Divisional Approved',
      ict_director_approved: 'ICT Director Approved',
      head_it_approved: 'Head IT Approved',
      ict_officer_approved: 'ICT Officer Approved',
      rejected: 'Rejected',
      hod_rejected: 'HOD Rejected',
      divisional_rejected: 'Divisional Rejected',
      ict_director_rejected: 'ICT Director Rejected',
      head_it_rejected: 'Head IT Rejected',
      ict_officer_rejected: 'ICT Officer Rejected',
      implemented: 'Implemented',
      cancelled: 'Cancelled',
      in_review: 'In Review',
      in_use: 'In Use',
      returned: 'Returned',
      overdue: 'Overdue'
    }
    return statusNames[status] || status
  },

  /**
   * Get status color class
   * @param {string} status - Status code
   * @returns {string} CSS class
   */
  getStatusColorClass(status) {
    const colorClasses = {
      pending: 'bg-yellow-400 animate-pulse',
      pending_hod: 'bg-yellow-400 animate-pulse',
      pending_divisional: 'bg-yellow-400 animate-pulse',
      pending_ict_director: 'bg-yellow-400 animate-pulse',
      pending_head_it: 'bg-yellow-400 animate-pulse',
      pending_ict_officer: 'bg-yellow-400 animate-pulse',
      approved: 'bg-green-400',
      hod_approved: 'bg-green-400',
      divisional_approved: 'bg-green-400',
      ict_director_approved: 'bg-green-400',
      head_it_approved: 'bg-green-400',
      ict_officer_approved: 'bg-green-400',
      rejected: 'bg-red-400',
      hod_rejected: 'bg-red-400',
      divisional_rejected: 'bg-red-400',
      ict_director_rejected: 'bg-red-400',
      head_it_rejected: 'bg-red-400',
      ict_officer_rejected: 'bg-red-400',
      implemented: 'bg-green-400',
      cancelled: 'bg-gray-400',
      in_review: 'bg-blue-400 animate-pulse',
      in_use: 'bg-purple-400',
      returned: 'bg-gray-400',
      overdue: 'bg-red-500 animate-pulse'
    }
    return colorClasses[status] || 'bg-gray-400'
  },

  /**
   * Get status text color class
   * @param {string} status - Status code
   * @returns {string} CSS class
   */
  getStatusTextColorClass(status) {
    const textColorClasses = {
      pending: 'text-yellow-400',
      pending_hod: 'text-yellow-400',
      pending_divisional: 'text-yellow-400',
      pending_ict_director: 'text-yellow-400',
      pending_head_it: 'text-yellow-400',
      pending_ict_officer: 'text-yellow-400',
      approved: 'text-green-400',
      hod_approved: 'text-green-400',
      divisional_approved: 'text-green-400',
      ict_director_approved: 'text-green-400',
      head_it_approved: 'text-green-400',
      ict_officer_approved: 'text-green-400',
      rejected: 'text-red-400',
      hod_rejected: 'text-red-400',
      divisional_rejected: 'text-red-400',
      ict_director_rejected: 'text-red-400',
      head_it_rejected: 'text-red-400',
      ict_officer_rejected: 'text-red-400',
      implemented: 'text-green-400',
      cancelled: 'text-gray-400',
      in_review: 'text-blue-400',
      in_use: 'text-purple-400',
      returned: 'text-gray-400',
      overdue: 'text-red-500'
    }
    return textColorClasses[status] || 'text-gray-400'
  },

  /**
   * Format date for display
   * @param {string} dateString - ISO date string
   * @returns {string} Formatted date
   */
  formatDate(dateString) {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  },

  /**
   * Format time for display
   * @param {string} dateString - ISO date string
   * @returns {string} Formatted time
   */
  formatTime(dateString) {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    })
  },

  /**
   * Get request type icon
   * @param {string} type - Request type
   * @returns {string} Font Awesome icon class
   */
  getRequestTypeIcon(type) {
    const icons = {
      combined_access: 'fas fa-layer-group',
      booking_service: 'fas fa-calendar-check',
      jeeva_access: 'fas fa-file-medical',
      wellsoft_access: 'fas fa-laptop-medical',
      internet_access: 'fas fa-wifi'
    }
    return icons[type] || 'fas fa-file-alt'
  },

  /**
   * Debug endpoint to check database records
   * @returns {Promise<Object>} API response
   */
  async debug() {
    try {
      const response = await apiClient.get('/request-status/debug')
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error in debug endpoint:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Debug failed',
        data: null
      }
    }
  }
}

export default requestStatusService
