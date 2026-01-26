/**
 * Application Configuration
 * Centralized configuration management using environment variables
 */

// API Configuration
// API Configuration
export const API_CONFIG = {
  // Force localhost:8000 if VUE_APP_API_URL is missing, empty, or relative (starts with /)
  // This ensures we don't accidentally use relative paths in development
  BASE_URL:
    !process.env.VUE_APP_API_URL ||
    process.env.VUE_APP_API_URL.startsWith('/') ||
    process.env.VUE_APP_API_URL === ''
      ? 'http://localhost/api'
      : process.env.VUE_APP_API_URL,
  TIMEOUT: parseInt(process.env.VUE_APP_API_TIMEOUT) || 10000
}

// Debug log to verify what's actually being used
if (typeof window !== 'undefined' && process.env.VUE_APP_DEBUG === 'true') {
  console.log('🔧 API Config Loaded:', {
    rawEnvVar: process.env.VUE_APP_API_URL,
    resolvedBaseURL: API_CONFIG.BASE_URL
  })
}

// Application Configuration
export const APP_CONFIG = {
  NAME: process.env.VUE_APP_NAME || 'Mnh Access Management System',
  VERSION: process.env.VUE_APP_VERSION || '1.0.0',
  DEBUG: process.env.VUE_APP_DEBUG === 'true',
  LOG_LEVEL: process.env.VUE_APP_LOG_LEVEL || 'info'
}

// Environment Detection
export const ENVIRONMENT = {
  IS_DEVELOPMENT: process.env.NODE_ENV === 'development',
  IS_PRODUCTION: process.env.NODE_ENV === 'production'
}

// Validation function to check required environment variables
export const validateConfig = () => {
  const requiredVars = ['VUE_APP_API_URL']

  const missing = requiredVars.filter((varName) => !process.env[varName])

  if (missing.length > 0) {
    console.error('❌ Missing required environment variables:', missing)
    console.error('Please check your .env file and ensure all required variables are set.')
    return false
  }

  return true
}

// Log configuration in development (only if not in production)
if (typeof window !== 'undefined' && APP_CONFIG.DEBUG && ENVIRONMENT.IS_DEVELOPMENT) {
  console.log('🔧 Application Configuration:', {
    api: API_CONFIG,
    app: APP_CONFIG,
    environment: ENVIRONMENT
  })

  // Validate configuration
  validateConfig()
}

// Export default configuration object
export default {
  API: API_CONFIG,
  APP: APP_CONFIG,
  ENV: ENVIRONMENT,
  validate: validateConfig
}
