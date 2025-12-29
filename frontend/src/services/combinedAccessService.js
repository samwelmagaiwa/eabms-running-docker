import apiClient from '../utils/apiClient'
import { devLog } from '../utils/devLogger'

/**
 * Combined Access Service for HOD request management
 * Handles all operations related to combined access requests (Jeeva, Wellsoft, Internet)
 */
class CombinedAccessService {
  /**
   * Get all combined access requests for HOD approval
   * @param {Object} filters - Search and filter parameters
   * @returns {Promise<Object>} Response with requests data
   */
  async getHodRequests(filters = {}) {
    try {
      devLog.debug('🔄 Fetching combined access requests for HOD approval...', filters)

      const params = new URLSearchParams()

      if (filters.search) params.append('search', filters.search)
      if (filters.status) params.append('status', filters.status)
      if (filters.department) params.append('department', filters.department)
      if (filters.per_page) params.append('per_page', filters.per_page)
      if (filters.page) params.append('page', filters.page)

      const response = await apiClient.get(`/hod/combined-access-requests?${params.toString()}`)

      if (response.data && response.data.success) {
        devLog.debug(
          '✅ HOD requests retrieved successfully:',
          response.data.data?.length || 0,
          'requests'
        )
        return {
          success: true,
          data: response.data
        }
      } else {
        throw new Error(response.data?.message || 'Failed to retrieve HOD requests')
      }
    } catch (error) {
      // Silently ignore abort errors
      if (
        error.message === 'Request aborted' ||
        error.message?.includes('aborted') ||
        error.message?.includes('canceled')
      ) {
        return {
          success: false,
          error: 'Request aborted',
          data: null
        }
      }

      devLog.error('❌ Error fetching HOD requests:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to retrieve HOD requests',
        data: null
      }
    }
  }

  /**
   * Get a specific combined access request by ID (general form endpoint)
   * NOTE: May be unauthorized for HOD; prefer getHodRequestById() when in HOD context.
   * @param {number} requestId - The request ID
   * @returns {Promise<Object>} Response with request data
   */
  async getRequestById(requestId) {
    try {
      devLog.debug('🔄 Fetching combined access request (general):', requestId)

      const response = await apiClient.get(`/both-service-form/${requestId}`)

      if (response.data && response.data.success) {
        devLog.debug('✅ Request retrieved successfully (general):', requestId)
        return {
          success: true,
          data: response.data.data
        }
      } else {
        throw new Error(response.data?.message || 'Request not found')
      }
    } catch (error) {
      devLog.error('❌ Error fetching request (general):', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to retrieve request',
        data: null
      }
    }
  }

  /**
   * Get a specific combined access request by ID for HOD view (authorized to HOD departments)
   * @param {number} requestId - The request ID
   * @returns {Promise<Object>} Response with request data
   */
  async getHodRequestById(requestId) {
    try {
      devLog.debug('🔄 Fetching HOD combined access request:', requestId)

      const response = await apiClient.get(`/hod/combined-access-requests/${requestId}`)

      if (response.data && response.data.success) {
        devLog.debug('✅ HOD request retrieved successfully:', requestId)
        return {
          success: true,
          data: response.data.data
        }
      } else {
        throw new Error(response.data?.message || 'HOD request not found')
      }
    } catch (error) {
      devLog.error('❌ Error fetching HOD request:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to retrieve HOD request',
        data: null
      }
    }
  }

  /**
   * Update HOD approval status for a combined access request
   * @param {number} requestId - The request ID
   * @param {Object} approvalData - Approval data (status, comments, etc.)
   * @returns {Promise<Object>} Response with update result
   */
  async updateHodApproval(requestId, approvalData) {
    try {
      devLog.debug('🔄 Updating HOD approval for request:', requestId, approvalData)

      const formData = new FormData()

      // Add approval data
      formData.append('hod_status', approvalData.status) // 'approved' or 'rejected'
      formData.append('hod_comments', approvalData.comments || '')
      formData.append('hod_name', approvalData.hodName || '')
      formData.append('hod_approved_at', new Date().toISOString())

      // Add HOD signature if provided
      if (approvalData.hodSignature) {
        formData.append('hod_signature', approvalData.hodSignature)
      }

      // Add module-specific data if provided
      if (approvalData.moduleData) {
        formData.append('module_data', JSON.stringify(approvalData.moduleData))
      }

      // Add access rights if provided
      if (approvalData.accessRights) {
        formData.append('access_rights', JSON.stringify(approvalData.accessRights))
      }

      const response = await apiClient.post(
        `/hod/combined-access-requests/${requestId}/approve`,
        formData
      )

      if (response.data && response.data.success) {
        devLog.debug('✅ HOD approval updated successfully:', requestId)
        return {
          success: true,
          data: response.data.data,
          message: 'Request approval updated successfully'
        }
      } else {
        throw new Error(response.data?.message || 'Failed to update approval')
      }
    } catch (error) {
      devLog.error('❌ Error updating HOD approval:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to update approval',
        errors: error.response?.data?.errors || null
      }
    }
  }

  /**
   * Cancel a combined access request
   * @param {number} requestId - The request ID
   * @param {string} reason - Cancellation reason
   * @returns {Promise<Object>} Response with cancellation result
   */
  async cancelRequest(requestId, reason) {
    try {
      devLog.debug('🔄 Cancelling combined access request:', requestId)

      const response = await apiClient.post(`/hod/combined-access-requests/${requestId}/cancel`, {
        reason: reason || 'Cancelled by HOD',
        cancelled_at: new Date().toISOString()
      })

      if (response.data && response.data.success) {
        devLog.debug('✅ Request cancelled successfully:', requestId)
        return {
          success: true,
          data: response.data.data,
          message: 'Request cancelled successfully'
        }
      } else {
        throw new Error(response.data?.message || 'Failed to cancel request')
      }
    } catch (error) {
      devLog.error('❌ Error cancelling request:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to cancel request'
      }
    }
  }

  /**
   * Get statistics for HOD dashboard
   * @returns {Promise<Object>} Response with statistics
   */
  async getHodStatistics() {
    try {
      devLog.debug('🔄 Fetching HOD statistics...')

      const response = await apiClient.get('/hod/combined-access-requests/statistics')

      if (response.data && response.data.success) {
        devLog.debug('✅ HOD statistics retrieved successfully')
        return {
          success: true,
          data: response.data.data
        }
      } else {
        throw new Error(response.data?.message || 'Failed to retrieve statistics')
      }
    } catch (error) {
      // Downgrade to warning to avoid noisy console on refresh/timeouts
      console.warn('⚠️ Statistics fetch fallback (using computed stats):', error?.message || error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to retrieve statistics',
        data: {
          pendingHod: 0,
          hodApproved: 0,
          hodRejected: 0,
          total: 0
        }
      }
    }
  }

  /**
   * Export HOD requests to Excel/CSV
   * @param {Object} filters - Export filters
   * @param {string} format - Export format ('excel' or 'csv')
   * @returns {Promise<Object>} Response with export result
   */
  async exportHodRequests(filters = {}, format = 'excel') {
    try {
      devLog.debug('🔄 Exporting HOD requests...', { filters, format })

      const params = new URLSearchParams()

      if (filters.search) params.append('search', filters.search)
      if (filters.status) params.append('status', filters.status)
      if (filters.department) params.append('department', filters.department)
      if (filters.date_from) params.append('date_from', filters.date_from)
      if (filters.date_to) params.append('date_to', filters.date_to)

      params.append('format', format)

      const response = await apiClient.get(
        `/hod/combined-access-requests/export?${params.toString()}`,
        {
          responseType: 'blob'
        }
      )

      devLog.debug('✅ Export completed successfully')
      return {
        success: true,
        data: response.data,
        filename:
          this.getFilenameFromHeaders(response.headers) ||
          `hod_requests_${Date.now()}.${format === 'excel' ? 'xlsx' : 'csv'}`
      }
    } catch (error) {
      devLog.error('❌ Error exporting HOD requests:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to export requests'
      }
    }
  }

  /**
   * Search requests by multiple criteria
   * @param {Object} criteria - Search criteria
   * @returns {Promise<Object>} Response with search results
   */
  async searchRequests(criteria) {
    try {
      devLog.debug('🔄 Searching requests with criteria:', criteria)

      const response = await apiClient.post('/hod/combined-access-requests/search', criteria)

      if (response.data && response.data.success) {
        devLog.debug(
          '✅ Search completed successfully:',
          response.data.data?.length || 0,
          'results'
        )
        return {
          success: true,
          data: response.data
        }
      } else {
        throw new Error(response.data?.message || 'Search failed')
      }
    } catch (error) {
      devLog.error('❌ Error searching requests:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Search failed',
        data: null
      }
    }
  }

  /**
   * Bulk approve multiple requests
   * @param {Array} requestIds - Array of request IDs
   * @param {Object} approvalData - Bulk approval data
   * @returns {Promise<Object>} Response with bulk approval result
   */
  async bulkApprove(requestIds, approvalData) {
    try {
      devLog.debug('🔄 Bulk approving requests:', requestIds.length)

      const response = await apiClient.post('/hod/combined-access-requests/bulk-approve', {
        request_ids: requestIds,
        hod_comments: approvalData.comments || '',
        hod_name: approvalData.hodName || '',
        approved_at: new Date().toISOString()
      })

      if (response.data && response.data.success) {
        devLog.debug('✅ Bulk approval completed successfully')
        return {
          success: true,
          data: response.data.data,
          message: `${response.data.data.approved_count || 0} requests approved successfully`
        }
      } else {
        throw new Error(response.data?.message || 'Bulk approval failed')
      }
    } catch (error) {
      devLog.error('❌ Error in bulk approval:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Bulk approval failed',
        data: null
      }
    }
  }

  /**
   * Get request history/audit trail
   * @param {number} requestId - The request ID
   * @returns {Promise<Object>} Response with request history
   */
  async getRequestHistory(requestId) {
    try {
      devLog.debug('🔄 Fetching request history:', requestId)

      const response = await apiClient.get(`/hod/combined-access-requests/${requestId}/history`)

      if (response.data && response.data.success) {
        devLog.debug('✅ Request history retrieved successfully')
        return {
          success: true,
          data: response.data.data
        }
      } else {
        throw new Error(response.data?.message || 'Failed to retrieve request history')
      }
    } catch (error) {
      devLog.error('❌ Error fetching request history:', error)
      return {
        success: false,
        error:
          error.response?.data?.message || error.message || 'Failed to retrieve request history',
        data: []
      }
    }
  }

  /**
   * Helper method to extract filename from response headers
   * @param {Object} headers - Response headers
   * @returns {string|null} Extracted filename
   */
  getFilenameFromHeaders(headers) {
    try {
      const contentDisposition = headers['content-disposition'] || headers['Content-Disposition']
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
        if (filenameMatch && filenameMatch[1]) {
          return filenameMatch[1].replace(/['"]/g, '')
        }
      }
      return null
    } catch (error) {
      console.warn('Could not extract filename from headers:', error)
      return null
    }
  }

  /**
   * Validate request data before submission
   * @param {Object} requestData - Request data to validate
   * @returns {Object} Validation result
   */
  validateRequestData(requestData) {
    const errors = []

    if (!requestData.staff_name || requestData.staff_name.trim() === '') {
      errors.push('Staff name is required')
    }

    if (!requestData.pf_number || requestData.pf_number.trim() === '') {
      errors.push('PF Number is required')
    }

    if (!requestData.department || requestData.department.trim() === '') {
      errors.push('Department is required')
    }

    if (!requestData.phone || requestData.phone.trim() === '') {
      errors.push('Phone number is required')
    }

    if (!requestData.services || requestData.services.length === 0) {
      errors.push('At least one service must be selected')
    }

    return {
      isValid: errors.length === 0,
      errors: errors
    }
  }

  /**
   * Format request data for display
   * @param {Object} request - Raw request data
   * @returns {Object} Formatted request data
   */
  formatRequestForDisplay(request) {
    return {
      ...request,
      formattedDate: this.formatDate(request.created_at || request.submission_date),
      formattedTime: this.formatTime(request.created_at || request.submission_date),
      statusText: this.getStatusText(request.hod_status || request.status),
      servicesText: this.getServicesText(request.services || request.request_types),
      requestIdDisplay: request.request_id || `REQ-${request.id.toString().padStart(6, '0')}`
    }
  }

  /**
   * Format date string
   * @param {string} dateString - Date string to format
   * @returns {string} Formatted date
   */
  formatDate(dateString) {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  }

  /**
   * Format time string
   * @param {string} dateString - Date string to format
   * @returns {string} Formatted time
   */
  formatTime(dateString) {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  /**
   * Get status text
   * @param {string} status - Status code
   * @returns {string} Human-readable status text
   */
  getStatusText(status) {
    const statusMap = {
      pending_hod: 'Pending HOD Approval',
      hod_approved: 'HOD Approved',
      hod_rejected: 'HOD Rejected',
      cancelled: 'Cancelled',
      completed: 'Completed'
    }
    return statusMap[status] || 'Unknown Status'
  }

  /**
   * Get services text
   * @param {Array} services - Services array
   * @returns {string} Comma-separated services text
   */
  getServicesText(services) {
    if (!services || services.length === 0) return 'No services'

    const serviceMap = {
      jeeva: 'Jeeva',
      jeeva_access: 'Jeeva',
      wellsoft: 'Wellsoft',
      internet: 'Internet',
      internet_access_request: 'Internet'
    }

    return services.map((service) => serviceMap[service] || service).join(', ')
  }

  /**
   * Get detailed timeline for a specific access request (Head of Department view)
   * @param {number} requestId - The request ID
   * @returns {Promise<Object>} Response with timeline data
   */
  async getRequestTimeline(requestId) {
    try {
      devLog.debug('🔄 CombinedAccessService: Fetching request timeline:', requestId)

      const response = await apiClient.get(`/hod/combined-access-requests/${requestId}/timeline`)

      if (response.data && response.data.success) {
        devLog.debug('✅ CombinedAccessService: Request timeline loaded successfully')

        // Normalize multiple backend formats into the shape expected by RequestTimeline.vue
        // Expected: { request: {...}, ict_assignments?: [], ... }
        const raw = response.data.data
        let normalized = raw

        // HOD endpoint format: { request_info, approval_stages, implementation, cancellation, ict_tasks }
        if (raw && !raw.request && raw.request_info) {
          const request = {
            ...raw.request_info,

            // Ensure core fields exist
            id: raw.request_info.id,
            staff_name: raw.request_info.staff_name,
            pf_number: raw.request_info.pf_number,
            created_at: raw.request_info.created_at,

            // Cancellation (map to fields used by the timeline component)
            cancelled_at: raw.cancellation?.cancelled_at || null,
            cancelled_by: raw.cancellation?.cancelled_by || null,
            cancellation_reason: raw.cancellation?.cancellation_reason || null,

            // Implementation
            ict_officer_implemented_at: raw.implementation?.implemented_at || null,
            implementation_comments: raw.implementation?.implementation_notes || null
          }

          // Map approval stages back into per-column fields used by the component
          const stages = Array.isArray(raw.approval_stages) ? raw.approval_stages : []
          for (const s of stages) {
            const stageName = (s.stage || '').toLowerCase()

            if (stageName.includes('hod')) {
              request.hod_status = s.status
              request.hod_name = s.approver_name
              request.hod_comments = s.comments
              request.hod_approved_at = s.timestamp
            } else if (stageName.includes('divisional')) {
              request.divisional_status = s.status
              request.divisional_director_name = s.approver_name
              request.divisional_director_comments = s.comments
              request.divisional_approved_at = s.timestamp
            } else if (stageName.includes('ict director')) {
              request.ict_director_status = s.status
              request.ict_director_name = s.approver_name
              request.ict_director_comments = s.comments
              request.ict_director_approved_at = s.timestamp
            } else if (stageName.includes('head of it')) {
              request.head_it_status = s.status
              request.head_it_name = s.approver_name
              request.head_it_comments = s.comments
              request.head_it_approved_at = s.timestamp
            } else if (stageName.includes('ict officer')) {
              request.ict_officer_status = s.status
              request.ict_officer_name = s.approver_name
              request.ict_officer_comments = s.comments
              request.ict_officer_approved_at = s.timestamp
            }
          }

          normalized = {
            ...raw,
            request,
            // keep compatibility naming; component also supports ict_assignments in some views
            ict_assignments: Array.isArray(raw.ict_tasks) ? raw.ict_tasks : []
          }
        }

        return {
          success: true,
          data: normalized,
          message: response.data.message
        }
      } else {
        throw new Error(response.data?.message || 'Failed to load request timeline')
      }
    } catch (error) {
      devLog.error('❌ CombinedAccessService: Error fetching request timeline:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to load request timeline'
      }
    }
  }

  /**
   * Permanently delete a user-cancelled request (HOD cleanup)
   * @param {number} requestId
   */
  async deleteCancelledRequest(requestId) {
    try {
      devLog.debug('🗑️ Deleting user-cancelled request as HOD:', requestId)
      const response = await apiClient.delete(`/hod/combined-access-requests/${requestId}`)
      if (response.data && response.data.success) {
        return { success: true, message: response.data.message }
      } else {
        throw new Error(response.data?.message || 'Failed to delete request')
      }
    } catch (error) {
      devLog.error('❌ Error deleting user-cancelled request:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to delete request'
      }
    }
  }

  /**
   * Get all Wellsoft modules from database
   * @returns {Promise<Object>} Response with Wellsoft modules data
   */
  async getWellsoftModules() {
    try {
      devLog.debug('🔄 CombinedAccessService: Fetching Wellsoft modules...')

      const response = await apiClient.get('/wellsoft-modules')

      if (response.data && response.data.success) {
        devLog.debug(
          '✅ CombinedAccessService: Wellsoft modules loaded successfully:',
          response.data.data?.length || 0
        )
        return {
          success: true,
          data: response.data.data
        }
      } else {
        throw new Error(response.data?.message || 'Failed to load Wellsoft modules')
      }
    } catch (error) {
      devLog.error('❌ CombinedAccessService: Error fetching Wellsoft modules:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to load Wellsoft modules',
        data: []
      }
    }
  }

  /**
   * Get all Jeeva modules from database
   * @returns {Promise<Object>} Response with Jeeva modules data
   */
  async getJeevaModules() {
    try {
      devLog.debug('🔄 CombinedAccessService: Fetching Jeeva modules...')

      const response = await apiClient.get('/jeeva-modules')

      if (response.data && response.data.success) {
        devLog.debug(
          '✅ CombinedAccessService: Jeeva modules loaded successfully:',
          response.data.data?.length || 0
        )
        return {
          success: true,
          data: response.data.data
        }
      } else {
        throw new Error(response.data?.message || 'Failed to load Jeeva modules')
      }
    } catch (error) {
      devLog.error('❌ CombinedAccessService: Error fetching Jeeva modules:', error)
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Failed to load Jeeva modules',
        data: []
      }
    }
  }
}

export default new CombinedAccessService()
