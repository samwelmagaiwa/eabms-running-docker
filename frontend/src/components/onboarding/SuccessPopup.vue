<template>
  <div
    class="fixed inset-0 bg-slate-900/40 backdrop-blur-md flex items-center justify-center z-[9999] p-4"
  >
    <!-- Main Modal -->
    <div
      class="bg-white rounded-3xl w-full max-w-lg overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-slate-100 transform transition-all animate-modal-pop"
    >
      <!-- Premium Hero Header -->
      <div
        class="relative bg-gradient-to-br from-blue-700 via-blue-800 to-blue-900 p-10 text-center"
      >
        <!-- Decoration Circles -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full -ml-12 -mb-12"></div>

        <!-- Animated Icon Container -->
        <div class="relative inline-block mb-6">
          <div
            class="w-20 h-20 bg-gradient-to-tr from-amber-300 to-amber-500 rounded-2xl flex items-center justify-center mx-auto shadow-2xl animate-float"
          >
            <i class="fas fa-trophy text-white text-3xl drop-shadow-md"></i>
          </div>
          <!-- Sparkles -->
          <div class="absolute -top-2 -right-2 animate-pulse">
            <i class="fas fa-sparkles text-amber-300 text-sm"></i>
          </div>
        </div>

        <h2 class="text-3xl font-extrabold text-white mb-2 tracking-tight">Congratulations!</h2>
        <div class="inline-flex items-center px-3 py-1 bg-white/10 rounded-full backdrop-blur-sm">
          <span class="text-blue-100 text-sm font-semibold uppercase tracking-wider">{{
            userRoleDisplay
          }}</span>
        </div>
      </div>

      <!-- Content Section -->
      <div class="p-10 bg-white">
        <!-- User Info -->
        <div class="text-center mb-8">
          <div class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-2">
            Welcome Aboard
          </div>
          <h3 class="text-3xl font-black text-slate-900 mb-4 tracking-tight">
            {{ userName }}
          </h3>
          <p class="text-slate-600 text-base leading-relaxed max-w-sm mx-auto">
            Your onboarding is now officially complete. You have full access to the portal features.
          </p>
        </div>

        <!-- Checklist Cards -->
        <div class="space-y-3 mb-10">
          <div
            v-for="(item, index) in [
              'Terms of Service Accepted',
              'ICT Policy Acknowledged',
              'Declaration Form Submitted'
            ]"
            :key="index"
            class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100 transition-all hover:bg-slate-100/80 group"
          >
            <div
              class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm transition-transform group-hover:scale-110"
            >
              <i class="fas fa-check text-white text-[10px]"></i>
            </div>
            <span class="text-slate-700 font-bold text-sm">{{ item }}</span>
          </div>
        </div>

        <!-- Primary Action -->
        <button
          @click="handleContinue"
          class="group relative w-full bg-slate-900 hover:bg-blue-600 text-white py-5 px-8 rounded-2xl font-bold text-lg transition-all duration-300 shadow-[0_10px_20px_rgba(15,23,42,0.15)] hover:shadow-[0_15px_30px_rgba(37,99,235,0.3)] active:scale-[0.98] overflow-hidden"
        >
          <div
            class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
          ></div>
          <div class="relative flex items-center justify-center gap-3">
            <span>Continue to Dashboard</span>
            <i
              class="fas fa-chevron-right text-sm transition-transform group-hover:translate-x-1"
            ></i>
          </div>
        </button>
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
  @keyframes modal-pop {
    0% {
      opacity: 0;
      transform: scale(0.9) translateY(20px);
    }
    100% {
      opacity: 1;
      transform: scale(1) translateY(0);
    }
  }

  .animate-modal-pop {
    animation: modal-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  }

  @keyframes float {
    0%,
    100% {
      transform: translateY(0px) rotate(0deg);
    }
    50% {
      transform: translateY(-8px) rotate(2deg);
    }
  }

  .animate-float {
    animation: float 4s ease-in-out infinite;
  }

  /* Custom focus ring for the button */
  button:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 4px;
  }

  /* Responsive refinements */
  @media (max-width: 640px) {
    .p-10 {
      padding: 1.5rem;
    }

    h3 {
      font-size: 1.5rem;
    }
  }
</style>
"
