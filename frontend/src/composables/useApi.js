import { ref } from 'vue'
import axios from 'axios'

// API configuration
const API_BASE_URL = process.env.VUE_APP_API_URL || '/api'

// Create axios instance with default config
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json'
  }
})

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized access
      console.warn('Unauthorized access - token may be expired')
      // You might want to redirect to login here
      // router.push('/login')
    }
    return Promise.reject(error)
  }
)

export function useApi() {
  const loading = ref(false)
  const error = ref('')

  // Generic API call function
  const apiCall = async (method, url, data = null, config = {}) => {
    loading.value = true
    error.value = ''

    try {
      const response = await apiClient({
        method,
        url,
        data,
        ...config
      })

      return response.data
    } catch (err) {
      console.error(`API Error [${method.toUpperCase()} ${url}]:`, err)

      if (err.response) {
        // Server responded with error status
        const status = err.response.status
        const message = err.response.data?.message || 'An error occurred'

        if (status === 401) {
          error.value = 'Authentication failed. Please log in again.'
        } else if (status === 403) {
          error.value = 'Access denied. You do not have permission for this action.'
        } else if (status >= 500) {
          error.value = 'Server error. Please try again later.'
        } else {
          error.value = message
        }
      } else if (err.request) {
        // Network error
        error.value = 'Network error. Please check your connection and try again.'
      } else {
        // Other error
        error.value = err.message || 'An unexpected error occurred'
      }

      throw err
    } finally {
      loading.value = false
    }
  }

  // Specific API methods
  const get = (url, config = {}) => apiCall('get', url, null, config)
  const post = (url, data, config = {}) => apiCall('post', url, data, config)
  const put = (url, data, config = {}) => apiCall('put', url, data, config)
  const patch = (url, data, config = {}) => apiCall('patch', url, data, config)
  const del = (url, config = {}) => apiCall('delete', url, null, config)

  return {
    loading,
    error,
    apiCall,
    get,
    post,
    put,
    patch,
    delete: del,
    apiClient
  }
}

// Specific composable for departments
export function useDepartments() {
  const departments = ref([])
  const loading = ref(false)
  const error = ref('')

  const { get } = useApi()

  const fetchDepartments = async () => {
    loading.value = true
    error.value = ''

    try {
      const response = await get('/v1/departments')

      if (response.success) {
        departments.value = response.data || []
        console.log('Departments loaded successfully:', departments.value.length, 'departments')
      } else {
        throw new Error(response.message || 'Failed to fetch departments')
      }
    } catch (err) {
      console.error('Error fetching departments:', err)
      error.value = err.message || 'Failed to load departments'

      // Fallback to hardcoded departments if API fails
      departments.value = [
        { id: 1, name: 'Administration', code: 'ADMIN' },
        { id: 2, name: 'Anesthesiology', code: 'ANESTH' },
        { id: 3, name: 'Cardiology', code: 'CARDIO' },
        { id: 4, name: 'Dermatology', code: 'DERMA' },
        { id: 5, name: 'Emergency Medicine', code: 'EMERG' },
        { id: 6, name: 'ICT Department', code: 'ICT' },
        { id: 7, name: 'Internal Medicine', code: 'INTMED' },
        { id: 8, name: 'Laboratory', code: 'LAB' },
        { id: 9, name: 'Nursing', code: 'NURS' },
        { id: 10, name: 'Pharmacy', code: 'PHARM' },
        { id: 11, name: 'Radiology', code: 'RADIO' },
        { id: 12, name: 'Other', code: 'OTHER' }
      ]
      console.log('Using fallback departments due to API error')
    } finally {
      loading.value = false
    }
  }

  return {
    departments,
    loading,
    error,
    fetchDepartments
  }
}

// Specific composable for user access requests
export function useUserAccess() {
  const { post } = useApi()

  const submitAccessRequest = async (formData) => {
    const response = await post('/v1/user-access', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response
  }

  const checkSignature = async (pfNumber) => {
    const response = await post('/v1/check-signature', { pf_number: pfNumber })
    return response
  }

  return {
    submitAccessRequest,
    checkSignature
  }
}
