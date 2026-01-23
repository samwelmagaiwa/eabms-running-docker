<template>
  <div class="min-h-screen bg-gray-100">
    <div class="flex bg-white overflow-hidden w-full h-screen">
      <!-- Left side: Branding -->
      <div class="hidden md:block w-1/2 relative overflow-hidden">
        <div
          class="absolute inset-0 bg-cover bg-center background-animate"
          style="background-image: url('/assets/images/image1.jpg')"
        ></div>
        <div class="relative z-10 flex flex-col items-up justify-up h-full p-8 text-white"></div>
        <!-- Enhanced S-curved design -->
        <svg
          class="absolute right-0 top-0 h-full w-32 text-white"
          viewBox="0 0 100 100"
          preserveAspectRatio="none"
        >
          <!-- Background Fill -->
          <path d="M100 0 L100 100 L50 100 C 100 66 0 33 50 0 L 100 0 Z" fill="currentColor" />
          <!-- Shining S-Curve Stroke -->
          <path
            d="M 50 100 C 100 66 0 33 50 0"
            fill="none"
            stroke="white"
            stroke-width="1.5"
            style="filter: drop-shadow(0 0 3px rgba(255, 255, 255, 0.9))"
          />
        </svg>
      </div>

      <!-- Right side: Login Form -->
      <div class="w-full md:w-1/2 p-8 flex flex-col justify-center items-center">
        <h1 class="text-4xl font-serif font-bold text-primary animate-fadeIn mb-2">
          Muhimbili National Hospital
        </h1>

        <div
          class="logo-circle rounded-full overflow-hidden border-2 border-primary flex items-center justify-center mb-6"
        >
          <img
            src="/assets/images/logo.jpg"
            alt="Muhimbili Logo"
            class="max-w-full max-h-full animate-flipX object-contain"
          />
        </div>
        <div class="w-full max-w-md">
          <h2 class="text-2xl font-bold mb-4 text-center text-primary">Login</h2>

          <!-- Error message display -->
          <div
            v-if="errorMessage"
            class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded flex items-center"
          >
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ errorMessage }}
          </div>
          <form @submit.prevent="handleLogin">
            <div class="mb-4">
              <label class="block text-gray-700 font-bold text-left">Email</label>
              <input
                v-model="email"
                type="email"
                class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                required
                placeholder="Enter your email"
                :disabled="loading"
              />
            </div>

            <div class="mb-4">
              <label class="block text-gray-700 font-bold text-left">Password</label>
              <input
                v-model="password"
                type="password"
                class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                required
                placeholder="Enter your password"
                :disabled="loading"
              />
            </div>

            <!-- Remember Email Checkbox -->
            <div class="mb-4 flex items-center justify-between">
              <label class="flex items-center text-sm text-gray-600">
                <input
                  v-model="rememberEmail"
                  type="checkbox"
                  class="mr-2 rounded focus:ring-2 focus:ring-primary"
                />
                <span>Remember my email</span>
              </label>

              <!-- Clear saved email button -->
              <button
                v-if="email"
                type="button"
                @click="clearSavedEmail"
                class="text-xs text-gray-500 hover:text-red-600 transition-colors"
                title="Clear saved email"
              >
                <i class="fas fa-times mr-1"></i>
                Clear
              </button>
            </div>

            <button
              type="submit"
              :disabled="isLoading"
              class="w-full bg-primary text-white p-2 rounded hover:bg-opacity-90 transition ease-in-out duration-300 animate-fadeIn delay-2 animate-bounceIn disabled:opacity-50"
            >
              <span v-if="loading">
                <OrbitingDots size="sm" class="mr-2" />
                Logging in...
              </span>
              <span v-else>
                <i class="fas fa-sign-in-alt mr-2"></i>
                Login
              </span>
            </button>
          </form>

          <!-- Forgot Password Button -->
          <div class="mt-4 text-center">
            <button
              type="button"
              class="text-sm text-primary hover:underline focus:outline-none"
              @click="handleForgotPassword"
            >
              Forgot your password?
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Snackbar -->
    <div
      v-if="showSuccessSnackbar"
      class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50"
    >
      <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        Login successful! Redirecting...
      </div>
    </div>

    <!-- Forgot Password Modal -->
    <ForgotPasswordModal :show="showForgotPassword" @close="showForgotPassword = false" />
  </div>
</template>

<script>
  import { ref, computed, watch, onMounted } from 'vue'
  import { useRoute } from 'vue-router'
  import { useAuth } from '@/composables/useAuth'
  import loginMemory from '@/utils/loginMemory'
  import OrbitingDots from '@/components/common/OrbitingDots.vue'
  import ForgotPasswordModal from '@/components/ForgotPasswordModal.vue'

  export default {
    name: 'LoginPage',
    components: {
      OrbitingDots,
      ForgotPasswordModal
    },
    setup() {
      const { login, isLoading, error, clearError } = useAuth()
      const route = useRoute()

      // Form data with localStorage persistence for email
      const email = ref('')
      const password = ref('')
      const rememberEmail = ref(true)
      const loading = ref(false)
      const showSuccessSnackbar = ref(false)
      const showForgotPassword = ref(false)

      // Load saved email on component mount
      const loadSavedEmail = () => {
        const savedEmail = loginMemory.getSavedEmail()
        if (savedEmail) {
          email.value = savedEmail
          rememberEmail.value = true
        }
      }

      // Save email to localStorage when remember is enabled
      const saveEmail = () => {
        if (rememberEmail.value && email.value) {
          loginMemory.saveEmail(email.value)
        } else {
          loginMemory.clearSavedEmail()
        }
      }

      // Watch for changes in rememberEmail checkbox
      watch(rememberEmail, (newValue) => {
        if (!newValue) {
          loginMemory.clearSavedEmail()
        } else if (email.value) {
          saveEmail()
        }
      })

      // Error handling
      const errorMessage = computed(() => {
        if (route.query.error === 'access_denied') {
          return 'Access denied. You do not have permission to access that page.'
        }
        return error.value
      })

      // Methods
      const handleLogin = async () => {
        clearError()

        if (!email.value || !password.value) {
          return
        }

        loading.value = true

        // Save email if remember is enabled
        if (rememberEmail.value) {
          saveEmail()
        }

        console.log('🚀 Attempting login with:', {
          email: email.value,
          rememberEmail: rememberEmail.value
        })

        // Use the auth composable's login method which handles navigation
        const result = await login({
          email: email.value,
          password: password.value
        })

        if (result.success) {
          console.log('✅ Login successful! Navigation should be handled by auth composable.')
          // Show success snackbar briefly
          showSuccessSnackbar.value = true
          setTimeout(() => {
            showSuccessSnackbar.value = false
          }, 3000)
          // Clear password but keep email if remember is enabled
          password.value = ''
        } else {
          console.error('❌ Login failed:', result)
          console.log('🔍 Current error value from store:', error.value)
          console.log('🔍 Current errorMessage computed:', errorMessage.value)
          // Clear password on failed login for security
          password.value = ''
        }

        loading.value = false
      }

      const clearSavedEmail = () => {
        loginMemory.clearSavedEmail()
        email.value = ''
        rememberEmail.value = false
      }

      const handleForgotPassword = () => {
        showForgotPassword.value = true
      }

      // Initialize component
      onMounted(() => {
        clearError()
        loadSavedEmail()
      })

      return {
        email,
        password,
        rememberEmail,
        loading,
        errorMessage,
        isLoading,
        handleLogin,
        clearSavedEmail,
        handleForgotPassword,
        showForgotPassword,
        showSuccessSnackbar
      }
    }
  }
</script>

<style scoped>
  /* Define primary color */
  .bg-primary {
    background-color: #1e40af; /* Blue-700 */
  }

  .text-primary {
    color: #1e40af; /* Blue-700 */
  }

  .border-primary {
    border-color: #1e40af; /* Blue-700 */
  }

  .focus\:ring-primary:focus {
    --tw-ring-color: #1e40af;
  }

  /* Animation keyframes */
  @keyframes rotate {
    from {
      transform: rotate(0deg);
    }
    to {
      transform: rotate(360deg);
    }
  }

  @keyframes flipX {
    0% {
      transform: scaleX(1);
    }
    50% {
      transform: scaleX(-1);
    }
    100% {
      transform: scaleX(1);
    }
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  @keyframes bounceIn {
    0% {
      transform: scale(0.1);
      opacity: 0;
    }
    60% {
      transform: scale(1.2);
      opacity: 1;
    }
    100% {
      transform: scale(1);
    }
  }

  @keyframes example {
    0% {
      background-position: 0% 0%;
      transform: translate(0, 0);
    }
    25% {
      background-position: 100% 0%;
      transform: translate(20px, 0);
    }
    50% {
      background-position: 100% 100%;
      transform: translate(20px, 20px);
    }
    75% {
      background-position: 0% 100%;
      transform: translate(0, 20px);
    }
    100% {
      background-position: 0% 0%;
      transform: translate(0, 0);
    }
  }

  /* Animation classes */
  .animate-rotate {
    animation: rotate 2s linear infinite;
  }

  .animate-flipX {
    animation: flipX 2s linear infinite;
  }

  .animate-fadeIn {
    animation: fadeIn 1.5s ease-in;
  }

  .animate-bounceIn {
    animation: bounceIn 1s;
  }

  .delay-1 {
    animation-delay: 0.5s;
  }

  .delay-2 {
    animation-delay: 1s;
  }

  .background-animate {
    animation: example 5s linear 2s infinite alternate;
  }

  /* Ensure the login logo container is always a perfect circle,
     regardless of global width utility overrides or screen size. */
  .logo-circle {
    /* Keep the container perfectly circular but let it scale with viewport width. */
    width: min(40vw, 10rem);
    height: min(40vw, 10rem);
    border-radius: 9999px;
    flex-shrink: 0; /* Prevent vertical squashing */
  }

  @media (max-width: 768px) {
    .logo-circle {
      width: min(50vw, 9rem);
      height: min(50vw, 9rem);
    }
  }
</style>
