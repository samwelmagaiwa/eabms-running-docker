import axios from 'axios'

const API_BASE_URL = process.env.VUE_APP_API_URL || '/api'

// Create axios instance with default config
const axiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

// Add auth token to requests
axiosInstance.interceptors.request.use(
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

const headOfItService = {
  /**
   * Get all requests that have reached Head of IT stage (pending, approved, rejected)
   */
  async getPendingRequests() {
    try {
      console.log('🔄 HeadOfItService: Fetching all requests...')
      const response = await axiosInstance.get('/head-of-it/all-requests')

      if (response.data.success) {
        console.log('✅ HeadOfItService: All requests loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to load pending requests:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load pending requests'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error fetching pending requests:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading requests'
      }
    }
  },

  /**
   * Get a specific request by ID
   */
  async getRequestById(requestId) {
    try {
      console.log('🔄 HeadOfItService: Fetching request by ID:', requestId)
      const response = await axiosInstance.get(`/head-of-it/requests/${requestId}`)

      if (response.data.success) {
        console.log('✅ HeadOfItService: Request loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to load request:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load request'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error fetching request:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading request'
      }
    }
  },

  /**
   * Approve a request
   */
  async approveRequest(requestId, signatureFile) {
    try {
      console.log('🔄 HeadOfItService: Approving request:', requestId)

      const formData = new FormData()
      formData.append('signature', signatureFile)
      formData.append('action', 'approve')

      const response = await axiosInstance.post(
        `/head-of-it/requests/${requestId}/approve`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }
      )

      if (response.data.success) {
        console.log('✅ HeadOfItService: Request approved successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to approve request:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to approve request'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error approving request:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while approving request'
      }
    }
  },

  /**
   * Reject a request
   */
  async rejectRequest(requestId, signatureFile, reason) {
    try {
      console.log('🔄 HeadOfItService: Rejecting request:', requestId)

      const formData = new FormData()
      formData.append('signature', signatureFile)
      formData.append('action', 'reject')
      formData.append('reason', reason)

      const response = await axiosInstance.post(
        `/head-of-it/requests/${requestId}/reject`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }
      )

      if (response.data.success) {
        console.log('✅ HeadOfItService: Request rejected successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to reject request:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to reject request'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error rejecting request:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while rejecting request'
      }
    }
  },

  /**
   * Get list of available ICT Officers
   * @param {number} requestId - Optional request ID to get SMS status for assigned officers
   */
  async getIctOfficers(requestId = null) {
    try {
      console.log('🔄 HeadOfItService: Fetching ICT officers...', { requestId })
      const url = requestId
        ? `/head-of-it/ict-officers?request_id=${requestId}`
        : '/head-of-it/ict-officers'
      const response = await axiosInstance.get(url)

      if (response.data.success) {
        console.log('✅ HeadOfItService: ICT officers loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to load ICT officers:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load ICT officers'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error fetching ICT officers:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading ICT officers'
      }
    }
  },

  /**
   * Assign a task to an ICT Officer
   */
  async assignTaskToIctOfficer(requestId, ictOfficerId, notifyPhone = null, forceReassign = false) {
    try {
      console.log('🔄 HeadOfItService: Assigning task to ICT officer:', {
        requestId,
        ictOfficerId,
        notifyPhone,
        forceReassign
      })

      // Prefer new endpoint (UserAccess system). Fallback to legacy if unavailable
      const tryNewEndpoint = async () => {
        const payloadNew = {
          user_access_id: requestId,
          ict_officer_user_id: ictOfficerId,
          // Optional flags; backend ignores if not used
          force_reassign: !!forceReassign
        }
        return axiosInstance.post('/head-of-it/assign-ict-task', payloadNew, { timeout: 20000 })
      }

      const tryLegacyEndpoint = async () => {
        // Normalize phone for compatibility (optional; backend ignores unknown keys)
        const normalizePhone = (p) => {
          if (!p) return null
          const s = String(p).trim()
          const keepPlus = s.startsWith('+')
          const digits = s.replace(/[^\d]/g, '')
          return keepPlus ? `+${digits}` : digits
        }
        const rawPhone = notifyPhone || null
        const normalizedPhone = normalizePhone(rawPhone)

        const payloadLegacy = {
          request_id: requestId,
          ict_officer_id: ictOfficerId,
          notify_phone: rawPhone || undefined,
          ict_officer_phone: normalizedPhone || undefined,
          send_sms: true,
          send_sms_to_ict_officer: true,
          notify_ict_officer: true,
          notification_channel: 'sms',
          force_reassign: !!forceReassign
        }
        return axiosInstance.post('/head-of-it/assign-task', payloadLegacy, { timeout: 20000 })
      }

      let response
      try {
        response = await tryNewEndpoint()
      } catch (err) {
        const status = err?.response?.status
        // Fallback on 404/405/501 style errors (route not found or not allowed)
        if (status === 404 || status === 405 || status === 501) {
          console.warn(
            'HeadOfItService: New assignment endpoint unavailable, falling back to legacy'
          )
          response = await tryLegacyEndpoint()
        } else {
          throw err
        }
      }

      if (response.data.success) {
        console.log('✅ HeadOfItService: Task assigned successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to assign task:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to assign task'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error assigning task:', error)
      // Surface validation errors if present
      const validationErrors = error.response?.data?.errors
      const message =
        (validationErrors && Object.values(validationErrors).flat().join(' ')) ||
        error.response?.data?.message ||
        error.message ||
        'Network error while assigning task'
      return {
        success: false,
        message
      }
    }
  },

  /**
   * Get task assignment history
   */
  async getTaskHistory(requestId) {
    try {
      console.log('🔄 HeadOfItService: Fetching task history for request:', requestId)
      const response = await axiosInstance.get(`/head-of-it/tasks/${requestId}/history`)

      if (response.data.success) {
        console.log('✅ HeadOfItService: Task history loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to load task history:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load task history'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error fetching task history:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading task history'
      }
    }
  },

  /**
   * Cancel task assignment
   */
  async cancelTaskAssignment(requestId) {
    try {
      console.log('🔄 HeadOfItService: Canceling task assignment for request:', requestId)

      // Try new endpoint first (UserAccess system), fallback to legacy
      const tryNewEndpoint = () =>
        axiosInstance.post(`/head-of-it/ict-tasks/${requestId}/cancel`, null, { timeout: 15000 })
      const tryLegacyEndpoint = () =>
        axiosInstance.post(`/head-of-it/tasks/${requestId}/cancel`, null, { timeout: 15000 })

      let response
      try {
        response = await tryNewEndpoint()
      } catch (err) {
        const status = err?.response?.status
        if (status === 404 || status === 405 || status === 501) {
          console.warn('HeadOfItService: New cancel endpoint unavailable, falling back to legacy')
          response = await tryLegacyEndpoint()
        } else {
          throw err
        }
      }

      if (response.data.success) {
        console.log('✅ HeadOfItService: Task assignment canceled successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error(
          '❌ HeadOfItService: Failed to cancel task assignment:',
          response.data.message
        )
        return {
          success: false,
          message: response.data.message || 'Failed to cancel task assignment'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error canceling task assignment:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while canceling task assignment'
      }
    }
  },

  /**
   * Get ICT Director recommendations for Head of IT
   */
  async getDictRecommendations(params = {}) {
    try {
      console.log('🔄 HeadOfItService: Fetching DICT recommendations...', params)

      const response = await axiosInstance.get('/head-of-it/dict-recommendations', {
        params
      })

      if (response.data.success) {
        console.log('✅ HeadOfItService: DICT recommendations loaded successfully')
        return {
          success: true,
          data: response.data,
          message: response.data.message
        }
      } else {
        console.error(
          '❌ HeadOfItService: Failed to load DICT recommendations:',
          response.data.message
        )
        return {
          success: false,
          message: response.data.message || 'Failed to load DICT recommendations'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error fetching DICT recommendations:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading DICT recommendations'
      }
    }
  },

  /**
   * Get DICT recommendation statistics for Head of IT
   */
  async getDictRecommendationStats() {
    try {
      console.log('🔄 HeadOfItService: Fetching DICT recommendation statistics...')
      const response = await axiosInstance.get('/head-of-it/dict-recommendations/stats')

      if (response.data.success) {
        console.log('✅ HeadOfItService: DICT recommendation statistics loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to load statistics:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load statistics'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error fetching statistics:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading statistics'
      }
    }
  },

  /**
   * Get detailed DICT recommendation for a specific request
   */
  async getDictRecommendationDetails(userAccessId) {
    try {
      console.log('🔄 HeadOfItService: Fetching DICT recommendation details for:', userAccessId)
      const response = await axiosInstance.get(
        `/head-of-it/dict-recommendations/${userAccessId}/details`
      )

      if (response.data.success) {
        console.log('✅ HeadOfItService: DICT recommendation details loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error(
          '❌ HeadOfItService: Failed to load recommendation details:',
          response.data.message
        )
        return {
          success: false,
          message: response.data.message || 'Failed to load recommendation details'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error fetching recommendation details:', error)
      return {
        success: false,
        message:
          error.response?.data?.message || 'Network error while loading recommendation details'
      }
    }
  },

  /**
   * Get detailed timeline for a specific access request (Head of IT view)
   */
  async getRequestTimeline(requestId) {
    try {
      console.log('🔄 HeadOfItService: Fetching request timeline:', requestId)
      const response = await axiosInstance.get(`/head-of-it/requests/${requestId}/timeline`)

      if (response.data.success) {
        console.log('✅ HeadOfItService: Request timeline loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        console.error('❌ HeadOfItService: Failed to load request timeline:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load request timeline'
        }
      }
    } catch (error) {
      console.error('❌ HeadOfItService: Error fetching request timeline:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading request timeline'
      }
    }
  }
}

export default headOfItService
