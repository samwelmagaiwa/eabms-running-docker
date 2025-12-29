import axios from 'axios'
import { devLog } from '../utils/devLogger'

const API_BASE_URL = process.env.VUE_APP_API_URL || 'http://127.0.0.1:8000/api'

// Create axios instance with default config
const axiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: 0, // No timeout - let backend take as long as needed
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
})

// Add auth token to requests and append cache-busting param for GETs
axiosInstance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    if (config.method && config.method.toLowerCase() === 'get') {
      try {
        const url = new URL(config.url, config.baseURL)
        url.searchParams.set('_cb', String(Date.now()))
        config.url = url.pathname + url.search
      } catch (e) {
        const sep = (config.url || '').includes('?') ? '&' : '?'
        config.url = `${config.url}${sep}_cb=${Date.now()}`
      }
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Centralized response handling so locked / unauthenticated users are
// sent back to login instead of leaving the ICT dashboard in a broken state.
axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status

    // Only force-logout on 401 (invalid/expired token).
    // 403 is a normal "role doesn't have access" case in this app and should not clear auth.
    if (status === 401) {
      // Clear any stale auth data
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      localStorage.removeItem('session_data')

      // Redirect to login/root if not already there
      if (typeof window !== 'undefined') {
        const path = window.location.pathname || ''
        if (path !== '/' && path !== '/login') {
          window.location.href = '/'
        }
      }
    }

    return Promise.reject(error)
  }
)

const ictOfficerService = {
  /**
   * Get all access requests approved by Head of IT and available for ICT Officer implementation
   */
  async getAccessRequests() {
    try {
      devLog.debug('🔄 IctOfficerService: Fetching access requests...')
      const response = await axiosInstance.get('/ict-officer/access-requests')

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Access requests loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        devLog.error('❌ IctOfficerService: Failed to load access requests:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load access requests'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error fetching access requests:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading access requests'
      }
    }
  },

  /**
   * Get a specific access request by ID
   */
  async getAccessRequestById(requestId) {
    try {
      devLog.debug('🔄 IctOfficerService: Fetching access request by ID:', requestId)
      const response = await axiosInstance.get(`/ict-officer/access-requests/${requestId}`)

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Access request loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        devLog.error('❌ IctOfficerService: Failed to load access request:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load access request'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error fetching access request:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading access request'
      }
    }
  },

  /**
   * Assign a task to self (ICT Officer takes ownership of implementation)
   */
  async assignTaskToSelf(requestId, notes = '') {
    try {
      devLog.debug('🔄 IctOfficerService: Assigning task to self:', requestId)

      const response = await axiosInstance.post(
        `/ict-officer/access-requests/${requestId}/assign`,
        {
          notes: notes
        }
      )

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Task assigned successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        devLog.error('❌ IctOfficerService: Failed to assign task:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to assign task'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error assigning task:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while assigning task'
      }
    }
  },

  /**
   * Update progress on an assigned task
   */
  async updateProgress(requestId, status, notes = '') {
    try {
      devLog.debug('🔄 IctOfficerService: Updating progress:', requestId, status)

      const response = await axiosInstance.put(
        `/ict-officer/access-requests/${requestId}/progress`,
        {
          status: status,
          notes: notes
        }
      )

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Progress updated successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        devLog.error('❌ IctOfficerService: Failed to update progress:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to update progress'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error updating progress:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while updating progress'
      }
    }
  },

  /**
   * Update implementation progress with detailed comments and status
   */
  async updateImplementationProgress(requestId, status, comments = '') {
    try {
      devLog.debug('🔄 IctOfficerService: Updating implementation progress:', requestId, status)

      const payload = {
        implementation_status: status,
        implementation_comments: comments
      }

      // Add timestamp based on status
      if (status === 'implementation_in_progress') {
        payload.ict_officer_started_at = new Date().toISOString()
      } else if (status === 'implemented') {
        payload.ict_officer_implemented_at = new Date().toISOString()
      }

      const response = await axiosInstance.put(
        `/ict-officer/access-requests/${requestId}/implementation`,
        payload
      )

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Implementation progress updated successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message || 'Implementation progress updated successfully'
        }
      } else {
        devLog.error(
          '❌ IctOfficerService: Failed to update implementation progress:',
          response.data.message
        )
        return {
          success: false,
          message: response.data.message || 'Failed to update implementation progress'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error updating implementation progress:', error)
      return {
        success: false,
        message:
          error.response?.data?.message || 'Network error while updating implementation progress'
      }
    }
  },

  /**
   * Cancel an assigned task (unassign from self)
   */
  async cancelTask(requestId, reason) {
    try {
      devLog.debug('🔄 IctOfficerService: Canceling task:', requestId)

      const response = await axiosInstance.post(
        `/ict-officer/access-requests/${requestId}/cancel`,
        {
          reason: reason
        }
      )

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Task canceled successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        devLog.error('❌ IctOfficerService: Failed to cancel task:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to cancel task'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error canceling task:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while canceling task'
      }
    }
  },

  /**
   * Complete implementation of access request
   */
  async completeImplementation(requestId, notes = '', signatureFile = null) {
    try {
      devLog.debug('🔄 IctOfficerService: Completing implementation:', requestId)

      const formData = new FormData()
      formData.append('notes', notes)
      if (signatureFile) {
        formData.append('signature', signatureFile)
      }

      const response = await axiosInstance.post(
        `/ict-officer/access-requests/${requestId}/complete`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }
      )

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Implementation completed successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        devLog.error(
          '❌ IctOfficerService: Failed to complete implementation:',
          response.data.message
        )
        return {
          success: false,
          message: response.data.message || 'Failed to complete implementation'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error completing implementation:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while completing implementation'
      }
    }
  },

  /**
   * Get detailed timeline for a specific access request
   */
  async getRequestTimeline(requestId) {
    try {
      devLog.debug('🔄 IctOfficerService: Fetching request timeline:', requestId)
      const response = await axiosInstance.get(`/ict-officer/access-requests/${requestId}/timeline`)

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Request timeline loaded successfully')
        return response.data.data
      } else {
        devLog.error(
          '❌ IctOfficerService: Failed to load request timeline:',
          response.data.message
        )
        throw new Error(response.data.message || 'Failed to load request timeline')
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error fetching request timeline:', error)
      if (error.response) {
        throw new Error(error.response.data?.message || 'Failed to load timeline data')
      } else {
        throw new Error('Network error while loading timeline')
      }
    }
  },

  /**
   * Get access request timeline (alias for getRequestTimeline)
   */
  async getAccessRequestTimeline(requestId) {
    try {
      devLog.debug('🔄 IctOfficerService: Fetching access request timeline:', requestId)
      const response = await axiosInstance.get(`/ict-officer/access-requests/${requestId}/timeline`)

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Access request timeline loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        devLog.error(
          '❌ IctOfficerService: Failed to load access request timeline:',
          response.data.message
        )
        return {
          success: false,
          message: response.data.message || 'Failed to load access request timeline'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error fetching access request timeline:', error)
      return {
        success: false,
        message:
          error.response?.data?.message || 'Network error while loading access request timeline'
      }
    }
  },

  /**
   * Grant access to a request (final step with timeline logging)
   */
  async grantAccess(requestId, data) {
    try {
      devLog.debug('🔄 IctOfficerService: Granting access for request:', requestId)
      devLog.debug('Grant access data:', data)

      const response = await axiosInstance.post(
        `/ict-officer/access-requests/${requestId}/grant-access`,
        {
          comments: data.comments || '',
          implementation_status: 'implemented',
          implementation_completed_at: new Date().toISOString()
        }
      )

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Access granted successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message || 'Access granted successfully'
        }
      } else {
        devLog.error('❌ IctOfficerService: Failed to grant access:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to grant access'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error granting access:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while granting access'
      }
    }
  },

  /**
   * Get statistics for ICT Officer dashboard
   */
  async getStatistics() {
    try {
      devLog.debug('🔄 IctOfficerService: Fetching statistics...')
      const response = await axiosInstance.get('/ict-officer/statistics')

      if (response.data.success) {
        devLog.debug('✅ IctOfficerService: Statistics loaded successfully')
        return {
          success: true,
          data: response.data.data,
          message: response.data.message
        }
      } else {
        devLog.error('❌ IctOfficerService: Failed to load statistics:', response.data.message)
        return {
          success: false,
          message: response.data.message || 'Failed to load statistics'
        }
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error fetching statistics:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while loading statistics'
      }
    }
  },

  /**
   * Get count of pending access requests for notification badge
   */
  async getPendingRequestsCount() {
    try {
      devLog.debug('🔄 IctOfficerService: Fetching pending requests count...')
      const response = await axiosInstance.get('/ict-officer/pending-count')

      if (response.data.success) {
        // Success logged only in development mode
        if (process.env.NODE_ENV === 'development') {
          devLog.debug('✅ IctOfficerService: Pending count loaded successfully')
        }
        return response.data.data
      } else {
        devLog.error('❌ IctOfficerService: Failed to load pending count:', response.data.message)
        throw new Error(response.data.message || 'Failed to load pending requests count')
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error fetching pending count:', error)
      if (error.response) {
        throw new Error(error.response.data?.message || 'Failed to load pending count')
      } else {
        throw new Error('Network error while loading pending count')
      }
    }
  },

  /**
   * Retry or (re)send SMS notification to the requester for a given access request
   * Backend-agnostic: sends multiple compatible phone keys and hint flags.
   */
  async resendRequesterSms(requestId, requesterPhone = null) {
    try {
      devLog.debug('🔄 IctOfficerService: Retrying requester SMS for:', {
        requestId,
        requesterPhone
      })

      const normalizePhone = (p) => {
        if (!p) return null
        const s = String(p).trim()
        const keepPlus = s.startsWith('+')
        const digits = s.replace(/[^\d]/g, '')
        return keepPlus ? `+${digits}` : digits
      }

      const rawPhone = requesterPhone || null
      const normalized = normalizePhone(rawPhone)

      const payload = {
        request_id: requestId,
        role: 'ict_officer',
        notification_type: 'requester_access_granted',
        resend_only: true,
        force: true,
        force_resend: true,
        resend_sms: true,
        send_sms: true,
        send_sms_to_requester: true,
        notification_channel: 'sms',
        // Phone compatibility keys (ignored if not recognized)
        requester_phone: rawPhone || undefined,
        requester_phone_number: rawPhone || undefined,
        requester_msisdn: normalized || undefined,
        staff_phone: rawPhone || undefined,
        staff_phone_number: rawPhone || undefined,
        recipient_phone: normalized || undefined,
        destination_phone: normalized || undefined,
        phone_number: normalized || undefined,
        msisdn: normalized || undefined,
        contact_phone: normalized || undefined
      }

      // Try multiple endpoints in order of preference
      let response
      const endpoints = [
        `/ict-officer/access-requests/${requestId}/notify`,
        `/ict-officer/access-requests/${requestId}/resend-sms`,
        `/ict-officer/access-requests/${requestId}/send-notification`,
        `/ict-officer/access-requests/${requestId}/grant-access`,
        `/ict-officer/notify`,
        `/ict-officer/resend-sms`
      ]

      let lastError
      for (let i = 0; i < endpoints.length; i++) {
        try {
          devLog.debug(`🔄 Trying endpoint ${i + 1}/${endpoints.length}: ${endpoints[i]}`)
          response = await axiosInstance.post(endpoints[i], payload)
          devLog.debug(`✅ Success with endpoint: ${endpoints[i]}`)
          break
        } catch (e) {
          console.warn(
            `❌ Endpoint ${endpoints[i]} failed:`,
            e.response?.status,
            e.response?.data?.message || e.message
          )
          lastError = e
          if (i === endpoints.length - 1) {
            devLog.error('🚫 All endpoints failed for SMS retry')
            throw lastError
          }
        }
      }

      if (response.data?.success) {
        devLog.debug('✅ IctOfficerService: Requester SMS retry triggered successfully')
        return { success: true, data: response.data.data, message: response.data.message }
      }

      return {
        success: false,
        message: response.data?.message || 'Failed to trigger SMS retry'
      }
    } catch (error) {
      devLog.error('❌ IctOfficerService: Error retrying requester SMS:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Network error while retrying SMS'
      }
    }
  }
}

export default ictOfficerService
