import axios from 'axios'

const API_BASE_URL = process.env.VUE_APP_API_URL || '/api'

class SignatureService {
  constructor() {
    this.api = axios.create({
      baseURL: API_BASE_URL,
      timeout: 10000,
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' }
    })

    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('auth_token')
        if (token) config.headers.Authorization = `Bearer ${token}`
        return config
      },
      (error) => Promise.reject(error)
    )
  }

  async sign(documentId) {
    const res = await this.api.post('/documents/sign', { document_id: documentId })
    return { success: true, data: res.data }
  }

  async list(documentId) {
    const res = await this.api.get(`/documents/${documentId}/signatures`)
    return { success: true, data: res.data.data }
  }

  async verify(signatureId) {
    const res = await this.api.get(`/signatures/${signatureId}/verify`)
    return { success: res.data.success, data: res.data }
  }
}

export default new SignatureService()
