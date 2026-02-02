import axios from 'axios'

const API_BASE_URL = process.env.VUE_APP_API_URL || '/api'

class DepartmentService {
  constructor() {
    this.api = axios.create({
      baseURL: API_BASE_URL,
      timeout: 10000,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
      }
    })

    // Add auth token to requests
    this.api.interceptors.request.use(
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
    this.api.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('auth_token')
          localStorage.removeItem('user_data')
          window.location.href = '/login'
        }
        return Promise.reject(error)
      }
    )
  }

  /**
   * Get all departments with pagination and filters
   */
  async getDepartments(params = {}) {
    try {
      console.log('Making request to:', `${this.api.defaults.baseURL}/admin/departments`)
      console.log('Request headers:', this.api.defaults.headers)
      const response = await this.api.get('/admin/departments', { params })
      console.log('Response received:', response)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error fetching departments:', error)
      console.error('Error response:', error.response)
      console.error('Error status:', error.response?.status)
      console.error('Error data:', error.response?.data)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch departments',
        error: error.response?.data?.error || error.message,
        status: error.response?.status
      }
    }
  }

  /**
   * Get a single department by ID
   */
  async getDepartment(id) {
    try {
      const response = await this.api.get(`/admin/departments/${id}`)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error fetching department:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch department',
        error: error.response?.data?.error || error.message
      }
    }
  }

  /**
   * Create a new department
   */
  async createDepartment(departmentData) {
    try {
      const response = await this.api.post('/admin/departments', departmentData)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error creating department:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to create department',
        error: error.response?.data?.error || error.message,
        errors: error.response?.data?.errors || {}
      }
    }
  }

  /**
   * Update an existing department
   */
  async updateDepartment(id, departmentData) {
    try {
      const response = await this.api.put(`/admin/departments/${id}`, departmentData)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error updating department:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update department',
        error: error.response?.data?.error || error.message,
        errors: error.response?.data?.errors || {}
      }
    }
  }

  /**
   * Delete a department
   */
  async deleteDepartment(id) {
    try {
      const response = await this.api.delete(`/admin/departments/${id}`)
      return {
        success: true,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error deleting department:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to delete department',
        error: error.response?.data?.error || error.message
      }
    }
  }

  /**
   * Toggle department active status
   */
  async toggleDepartmentStatus(id) {
    try {
      const response = await this.api.patch(`/admin/departments/${id}/toggle-status`)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error toggling department status:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to toggle department status',
        error: error.response?.data?.error || error.message
      }
    }
  }

  /**
   * Get form data for creating/editing departments
   */
  async getFormData(departmentId = null) {
    try {
      const params = departmentId ? { department_id: departmentId } : {}
      console.log(
        'Making request to:',
        `${this.api.defaults.baseURL}/admin/departments/create-form-data`
      )
      console.log('With params:', params)
      const response = await this.api.get('/admin/departments/create-form-data', { params })
      console.log('Form data response received:', response)
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error fetching form data:', error)
      console.error('Form data error response:', error.response)
      console.error('Form data error status:', error.response?.status)
      console.error('Form data error data:', error.response?.data)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch form data',
        error: error.response?.data?.error || error.message,
        status: error.response?.status
      }
    }
  }

  /**
   * Get eligible HOD users
   */
  async getEligibleHods() {
    try {
      const response = await this.api.get('/admin/departments/eligible-hods')
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error fetching eligible HODs:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch eligible HODs',
        error: error.response?.data?.error || error.message
      }
    }
  }

  /**
   * Get eligible divisional director users
   */
  async getEligibleDivisionalDirectors() {
    try {
      const response = await this.api.get('/admin/departments/eligible-divisional-directors')
      return {
        success: true,
        data: response.data.data,
        message: response.data.message
      }
    } catch (error) {
      console.error('Error fetching eligible divisional directors:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to fetch eligible divisional directors',
        error: error.response?.data?.error || error.message
      }
    }
  }
}

export default new DepartmentService()
