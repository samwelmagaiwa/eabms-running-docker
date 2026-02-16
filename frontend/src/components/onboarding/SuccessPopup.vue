<template>
  <div
    class="fixed inset-0 bg-gradient-to-br from-slate-900/60 via-blue-900/40 to-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[9999] p-4"
  >
    <!-- Landscape Modern Card -->
    <div
      class="bg-white rounded-2xl w-full max-w-4xl overflow-hidden shadow-[0_25px_60px_rgba(0,0,0,0.25)] border border-white/20 transform transition-all animate-slide-up"
    >
      <!-- Main Content Grid - Landscape Layout -->
      <div class="grid md:grid-cols-[1fr_1.2fr] gap-0">
        <!-- Left Section - Hero with Trophy -->
        <div
          class="relative bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 p-8 flex flex-col items-center justify-center text-center overflow-hidden"
        >
          <!-- Animated Background Patterns -->
          <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-40 h-40 bg-white rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white rounded-full blur-2xl"></div>
          </div>

          <!-- Trophy Icon with Glow -->
          <div class="relative mb-6">
            <div
              class="w-24 h-24 bg-gradient-to-tr from-amber-400 to-yellow-500 rounded-3xl flex items-center justify-center shadow-2xl animate-bounce-gentle relative z-10"
            >
              <i class="fas fa-trophy text-white text-4xl drop-shadow-lg"></i>
            </div>
            <!-- Glow Effect -->
            <div
              class="absolute inset-0 bg-amber-400/40 rounded-3xl blur-xl animate-pulse-glow"
            ></div>
            <!-- Sparkles -->
            <div class="absolute -top-3 -right-3 animate-spin-slow">
              <i class="fas fa-sparkles text-amber-300 text-xl"></i>
            </div>
            <div class="absolute -bottom-2 -left-2 animate-ping">
              <i class="fas fa-star text-yellow-300 text-sm"></i>
            </div>
          </div>

          <!-- Congratulations Text -->
          <div class="relative z-10">
            <h2 class="text-4xl font-black text-white mb-4 tracking-tight drop-shadow-lg">
              Success!
            </h2>
            <div
              class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-full backdrop-blur-md border border-white/30 mb-4"
            >
              <i class="fas fa-shield-check text-white text-sm"></i>
              <span class="text-white text-sm font-bold uppercase tracking-wider">{{
                userRoleDisplay
              }}</span>
            </div>

            <!-- Welcome Message -->
            <div class="mt-6 space-y-2">
              <p class="text-blue-100 text-xs font-semibold uppercase tracking-widest">
                Welcome Aboard
              </p>
              <h3 class="text-2xl font-bold text-white drop-shadow">
                {{ userName }}
              </h3>
            </div>
          </div>
        </div>

        <!-- Right Section - Checklist & Action -->
        <div class="bg-gradient-to-br from-slate-50 to-blue-50/30 p-8 flex flex-col justify-center">
          <!-- Info Message -->
          <div class="mb-6">
            <p class="text-slate-700 text-sm leading-relaxed font-medium">
              🎉 Your onboarding is
              <span class="font-bold text-blue-600">officially complete</span>! You now have full
              access to all portal features.
            </p>
          </div>

          <!-- Compact Checklist -->
          <div class="space-y-2.5 mb-8">
            <div
              v-for="(item, index) in [
                'Terms of Service Accepted',
                'ICT Policy Acknowledged',
                'Declaration Form Submitted'
              ]"
              :key="index"
              class="flex items-center gap-3 p-3 rounded-xl bg-white border border-slate-200/60 shadow-sm transition-all hover:shadow-md hover:border-blue-300 group"
              :style="{ animationDelay: `${index * 100}ms` }"
            >
              <div
                class="w-5 h-5 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm transition-all group-hover:scale-110 group-hover:rotate-12"
              >
                <i class="fas fa-check text-white text-[9px]"></i>
              </div>
              <span class="text-slate-700 font-semibold text-xs">{{ item }}</span>
            </div>
          </div>

          <!-- Action Button -->
          <button
            @click="handleContinue"
            class="group relative w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white py-4 px-6 rounded-xl font-bold text-base transition-all duration-300 shadow-lg hover:shadow-xl active:scale-[0.98] overflow-hidden"
          >
            <!-- Shine Effect -->
            <div
              class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"
            ></div>
            <div class="relative flex items-center justify-center gap-2.5">
              <span>Continue to Dashboard</span>
              <i
                class="fas fa-arrow-right text-sm transition-transform group-hover:translate-x-1"
              ></i>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'SuccessPopup',
    props: {
      userName: {
        type: String,
        required: true
      },
      userRoleDisplay: {
        type: String,
        default: 'Staff Member'
      },
      message: {
        type: String,
        default:
          'You have successfully completed the onboarding process. Now you can proceed with your requests.'
      }
    },
    emits: ['continue'],
    methods: {
      handleContinue() {
        console.log('🎯 SuccessPopup: Continue button clicked')
        console.log('🚀 SuccessPopup: Emitting continue event')
        this.$emit('continue')
      }
    }
  }
</script>

<style scoped>
  /* Slide up animation */
  @keyframes slide-up {
    0% {
      opacity: 0;
      transform: translateY(30px) scale(0.95);
    }
    100% {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  .animate-slide-up {
    animation: slide-up 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  }

  /* Gentle bounce for trophy */
  @keyframes bounce-gentle {
    0%,
    100% {
      transform: translateY(0) rotate(0deg);
    }
    50% {
      transform: translateY(-10px) rotate(3deg);
    }
  }

  .animate-bounce-gentle {
    animation: bounce-gentle 3s ease-in-out infinite;
  }

  /* Pulsing glow effect */
  @keyframes pulse-glow {
    0%,
    100% {
      opacity: 0.3;
      transform: scale(1);
    }
    50% {
      opacity: 0.6;
      transform: scale(1.1);
    }
  }

  .animate-pulse-glow {
    animation: pulse-glow 2s ease-in-out infinite;
  }

  /* Slow spin for sparkles */
  @keyframes spin-slow {
    from {
      transform: rotate(0deg);
    }
    to {
      transform: rotate(360deg);
    }
  }

  .animate-spin-slow {
    animation: spin-slow 8s linear infinite;
  }

  /* Focus styles */
  button:focus-visible {
    outline: 3px solid #60a5fa;
    outline-offset: 3px;
  }

  /* Mobile responsive adjustments */
  @media (max-width: 768px) {
    .grid {
      grid-template-columns: 1fr;
    }

    .p-8 {
      padding: 1.5rem;
    }

    h2 {
      font-size: 2rem;
    }

    h3 {
      font-size: 1.5rem;
    }
  }
</style>
"
