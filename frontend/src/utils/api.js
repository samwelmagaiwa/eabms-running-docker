// API layer to fetch users for each module using the configured API client
// Now uses the real backend with proper authentication and request_type filtering

import apiClient from './apiClient'

/**
 * Fetch Jeeva system users from user_access table filtered by request_type 'jeeva_access'
 */
export async function fetchJeevaUsers(params = {}) {
  try {
    console.log('🔍 Fetching Jeeva users with params:', params)
    const { data } = await apiClient.get('/jeeva-users', { params })
    console.log('✅ Jeeva users response:', data)

    // Transform response to match expected format
    return {
      items: data.items || [],
      total: data.total || 0,
      success: data.success || false,
      message: data.message || 'Jeeva users retrieved'
    }
  } catch (error) {
    console.error('❌ Failed to fetch Jeeva users:', error)
    console.error('Error details:', {
      status: error.response?.status,
      statusText: error.response?.statusText,
      message: error.response?.data?.message,
      data: error.response?.data
    })

    // Return fallback data to prevent UI breaking
    return {
      items: [],
      total: 0,
      success: false,
      message: error.response?.data?.message || 'Failed to fetch Jeeva users'
    }
  }
}

/**
 * Fetch Wellsoft system users from user_access table filtered by request_type 'wellsoft'
 */
export async function fetchWellsoftUsers(params = {}) {
  try {
    console.log('🔍 Fetching Wellsoft users with params:', params)
    const { data } = await apiClient.get('/wellsoft-users', { params })
    console.log('✅ Wellsoft users response:', data)

    // Transform response to match expected format
    return {
      items: data.items || [],
      total: data.total || 0,
      success: data.success || false,
      message: data.message || 'Wellsoft users retrieved'
    }
  } catch (error) {
    console.error('❌ Failed to fetch Wellsoft users:', error)
    console.error('Error details:', {
      status: error.response?.status,
      statusText: error.response?.statusText,
      message: error.response?.data?.message,
      data: error.response?.data
    })

    // Return fallback data to prevent UI breaking
    return {
      items: [],
      total: 0,
      success: false,
      message: error.response?.data?.message || 'Failed to fetch Wellsoft users'
    }
  }
}

/**
 * Fetch Internet access users from user_access table filtered by request_type 'internet_access_request'
 */
export async function fetchInternetUsers(params = {}) {
  try {
    console.log('🔍 Fetching Internet users with params:', params)
    const { data } = await apiClient.get('/internet-users', { params })
    console.log('✅ Internet users response:', data)

    // Transform response to match expected format
    return {
      items: data.items || [],
      total: data.total || 0,
      success: data.success || false,
      message: data.message || 'Internet users retrieved'
    }
  } catch (error) {
    console.error('❌ Failed to fetch Internet users:', error)
    console.error('Error details:', {
      status: error.response?.status,
      statusText: error.response?.statusText,
      message: error.response?.data?.message,
      data: error.response?.data
    })

    // Return fallback data to prevent UI breaking
    return {
      items: [],
      total: 0,
      success: false,
      message: error.response?.data?.message || 'Failed to fetch Internet users'
    }
  }
}

/**
 * Delete a user access request (Soft delete/Cancel)
 */
export async function deleteUserAccess(requestId) {
  try {
    console.log('🗑️ Deleting user access request:', requestId)
    const { data } = await apiClient.delete(`/v1/user-access/${requestId}`)
    console.log('✅ Delete response:', data)
    return {
      success: data.success ?? false,
      message: data.message || 'Request deleted successfully'
    }
  } catch (error) {
    console.error('❌ Failed to delete user access:', error)
    console.error('Error details:', {
      status: error.response?.status,
      statusText: error.response?.statusText,
      message: error.response?.data?.message,
      data: error.response?.data
    })
    return {
      success: false,
      message: error.response?.data?.message || 'Failed to delete request'
    }
  }
}

/**
 * Download PDF for a user access request (with authentication)
 */
export async function downloadUserAccessPdf(requestId) {
  try {
    const response = await apiClient.get(`/both-service-form/${requestId}/export-pdf`, {
      responseType: 'blob'
    })

    // Create blob URL and trigger download
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `user-access-request-${requestId}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    return {
      success: true,
      message: 'PDF downloaded successfully'
    }
  } catch (error) {
    console.error('❌ Failed to download PDF:', error)
    return {
      success: false,
      message: error.response?.data?.message || 'Failed to download PDF'
    }
  }
}
