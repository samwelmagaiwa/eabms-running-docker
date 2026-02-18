<template>
  <div class="flex flex-col h-screen">
    <Header />
    <div class="flex flex-1 overflow-hidden">
      <div class="sidebar-narrow">
        <ModernSidebar />
      </div>
      <main class="flex-1 p-4 bg-blue-900 overflow-y-auto">
        <div class="max-w-13 mx-auto">
          <!-- Error Display -->
          <div
            v-if="error"
            class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"
          >
            <h3 class="font-bold">Error</h3>
            <p>{{ error }}</p>
            <button
              @click="fetchRequests"
              class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
            >
              Retry
            </button>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-yellow-600/25 border border-yellow-400/40 p-4 rounded-lg">
              <h3 class="text-yellow-200 text-lg font-bold">Pending Divisional Approval</h3>
              <p class="text-white text-3xl font-bold">{{ stats.pendingDivisional }}</p>
            </div>
            <div class="bg-green-600/25 border border-green-400/40 p-4 rounded-lg">
              <h3 class="text-green-200 text-lg font-bold">Divisional Approved</h3>
              <p class="text-white text-3xl font-bold">{{ stats.divisionalApproved }}</p>
            </div>
            <div class="bg-red-600/25 border border-red-400/40 p-4 rounded-lg">
              <h3 class="text-red-200 text-lg font-bold">Divisional Rejected</h3>
              <p class="text-white text-3xl font-bold">{{ stats.divisionalRejected }}</p>
            </div>
            <div class="bg-blue-600/25 border border-blue-400/40 p-4 rounded-lg">
              <h3 class="text-blue-200 text-lg font-bold">Total Requests</h3>
              <p class="text-white text-3xl font-bold">{{ stats.total }}</p>
            </div>
          </div>

          <!-- Filters -->
          <div class="bg-white/10 rounded-lg p-4 mb-6">
            <div class="flex gap-4">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by staff name, PF number, or department..."
                class="flex-1 px-4 py-3 bg-white/20 border border-blue-300/30 rounded text-white placeholder-blue-200/60 text-base"
              />
              <select
                v-model="statusFilter"
                class="px-4 py-3 bg-white/20 border border-blue-300/30 rounded text-white text-base status-select"
              >
                <option value="">All Statuses</option>
                <option value="pending">Pending Submission</option>
                <option value="pending_hod">Pending HOD</option>
                <option value="hod_approved">Pending Divisional</option>
                <option value="divisional_approved">Divisional Approved</option>
                <option value="divisional_rejected">Divisional Rejected</option>
                <option value="approved">Fully Approved</option>
                <option value="implemented">Implemented</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <button
                @click="refreshRequests"
                class="px-6 py-3 bg-teal-600 text-white rounded hover:bg-teal-700 text-base font-bold"
              >
                Refresh
              </button>
            </div>
          </div>

          <!-- Requests Table -->
          <div class="bg-white/10 rounded-lg relative" style="overflow: visible !important">
            <table class="w-full" style="overflow: visible !important">
              <thead class="bg-blue-800/50">
                <tr></tr>
                <tr>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">Request ID</th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">Request Type</th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                    Personal Information
                  </th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                    HOD Approval Date
                  </th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                    Current Status
                  </th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">SMS Status</th>
                  <th class="px-4 py-3 text-center text-blue-100 text-sm font-bold">Actions</th>
                </tr>
              </thead>
              <tbody style="overflow: visible !important">
                <tr
                  v-for="request in filteredRequests"
                  :key="request.id"
                  class="border-t border-blue-300/20 hover:bg-blue-700/30"
                  style="overflow: visible !important"
                >
                  <!-- Request ID -->
                  <td class="px-4 py-3">
                    <div class="text-white font-bold text-sm">
                      {{ request.request_id || `REQ-${request.id.toString().padStart(6, '0')}` }}
                    </div>
                    <div class="text-purple-300 text-xs">ID: {{ request.id }}</div>
                  </td>

                  <!-- Request Type -->
                  <td class="px-4 py-4">
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-if="hasService(request, 'jeeva')"
                        class="px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800"
                      >
                        Jeeva
                      </span>
                      <span
                        v-if="hasService(request, 'wellsoft')"
                        class="px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-800"
                      >
                        Wellsoft
                      </span>
                      <span
                        v-if="hasService(request, 'internet')"
                        class="px-2 py-1 rounded text-xs font-bold bg-cyan-100 text-cyan-800"
                      >
                        Internet
                      </span>
                    </div>
                  </td>

                  <!-- Personal Information -->
                  <td class="px-4 py-3">
                    <div class="text-white font-bold text-sm">
                      {{ request.staff_name || request.full_name || 'Unknown User' }}
                    </div>
                    <div class="text-blue-300 text-xs">
                      {{ request.phone || request.phone_number || 'No phone' }}
                    </div>
                    <div v-if="request.pf_number" class="text-teal-300 text-xs">
                      PF: {{ request.pf_number }}
                    </div>
                    <div class="text-blue-200 text-xs">
                      Dept: {{ request.department || 'Unknown' }}
                    </div>
                  </td>

                  <!-- HOD Approval Date -->
                  <td class="px-4 py-3">
                    <div class="text-white font-bold text-sm">
                      {{ formatDate(request.hod_approved_at || request.hod_approval_date) }}
                    </div>
                    <div class="text-blue-300 text-xs">
                      {{ formatTime(request.hod_approved_at || request.hod_approval_date) }}
                    </div>
                  </td>

                  <!-- Current Status -->
                  <td class="px-4 py-3">
                    <div class="flex flex-col">
                      <span
                        v-if="request.divisional_status === 'skipped'"
                        class="mb-1 rounded text-xs font-semibold inline-block bg-red-900/30 text-red-300 border border-red-500/40 px-2 py-0.5"
                        :style="{ width: 'fit-content' }"
                      >
                        No Divisional Director — Stage Skipped
                      </span>
                      <!-- Display the exact database status -->
                      <span
                        :class="getStatusBadgeClass(request.status)"
                        class="rounded text-sm font-bold inline-block"
                        :style="{ padding: '4px 8px', width: 'fit-content' }"
                      >
                        {{ getStatusText(request.status) }}
                      </span>
                      <span
                        v-if="request.has_signature || request.signature"
                        class="mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-900/30 text-emerald-300 border border-emerald-500/40 w-fit"
                      >
                        Digitally signed
                      </span>
                    </div>
                  </td>

                  <!-- SMS Status -->
                  <td class="px-4 py-4">
                    <div class="flex items-center space-x-2">
                      <div
                        class="w-3 h-3 rounded-full"
                        :class="getSmsStatusColor(getRelevantSmsStatus(request))"
                      ></div>
                      <span
                        class="text-sm font-medium"
                        :class="getSmsStatusTextColor(getRelevantSmsStatus(request))"
                      >
                        {{ getSmsStatusText(getRelevantSmsStatus(request)) }}
                      </span>
                      <button
                        v-if="['failed', 'pending'].includes(getRelevantSmsStatus(request))"
                        @click.stop="retrySendSms(request)"
                        :disabled="isRetrying && isRetrying(request.id)"
                        class="ml-2 px-2 py-1 text-xs rounded border border-blue-300/50 text-blue-100 hover:bg-blue-700/40 disabled:opacity-50"
                        title="Retry sending SMS"
                      >
                        <span v-if="!(isRetrying && isRetrying(request.id))">Retry</span>
                        <span v-else><i class="fas fa-spinner fa-spin mr-1"></i>Retrying</span>
                      </button>
                      <span
                        v-if="getRetryAttempts && getRetryAttempts(request.id) > 0"
                        class="text-xs text-blue-200 ml-1"
                      >
                        ({{ getRetryAttempts(request.id) }})
                      </span>
                    </div>
                  </td>

                  <!-- Actions -->
                  <td class="px-4 py-3 text-center relative">
                    <div class="flex justify-center three-dot-menu">
                      <!-- Three-dot menu button -->
                      <button
                        @click.stop="toggleDropdown(request.id)"
                        class="three-dot-button p-2 text-white hover:bg-blue-600/40 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400 hover:scale-105 active:scale-95"
                        :class="{ 'bg-blue-600/40 shadow-lg': openDropdownId === request.id }"
                        :aria-label="
                          'Actions for request ' +
                          (request.request_id || 'REQ-' + request.id.toString().padStart(6, '0'))
                        "
                      >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                          <path
                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"
                          />
                        </svg>
                      </button>

                      <!-- Dropdown menu -->
                      <div
                        v-show="openDropdownId === request.id"
                        class="dropdown-menu absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-[10000] py-1 min-w-max"
                        @click.stop="() => {}"
                        style="
                          box-shadow:
                            0 10px 25px rgba(0, 0, 0, 0.15),
                            0 4px 6px rgba(0, 0, 0, 0.1);
                        "
                      >
                        <!-- Available actions -->
                        <template
                          v-for="(action, index) in getAvailableActions(request)"
                          :key="action.key"
                        >
                          <button
                            @click.stop="executeAction(action.key, request)"
                            class="w-full text-left px-4 py-3 text-lg transition-all duration-200 flex items-center space-x-3 group"
                            :class="[
                              getActionButtonClass(action.key),
                              {
                                'border-t border-gray-200':
                                  index > 0 &&
                                  shouldShowSeparator(
                                    action,
                                    getAvailableActions(request)[index - 1]
                                  )
                              }
                            ]"
                          >
                            <i
                              :class="[action.icon, getActionIconClass(action.key)]"
                              class="flex-shrink-0 transition-colors duration-200 text-lg"
                            ></i>
                            <span class="font-semibold">{{ action.label }}</span>
                          </button>
                        </template>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Empty State -->
            <div v-if="filteredRequests.length === 0" class="text-center py-12">
              <h3 class="text-white text-2xl font-bold mb-2">No requests found</h3>
              <p class="text-blue-300 text-lg">
                {{
                  searchQuery || statusFilter
                    ? 'No HOD-approved requests from your department are pending your approval.'
                    : 'Try adjusting your filters'
                }}
              </p>
            </div>

            <!-- Pagination -->
            <div v-if="filteredRequests.length > 0" class="px-4 py-3 border-t border-blue-300/30">
              <div class="text-blue-300 text-lg font-bold">
                Showing {{ filteredRequests.length }} of {{ requests.length }} requests
              </div>
            </div>
          </div>

          <AppFooter />
        </div>
      </main>
    </div>

    <!-- Unified Loading Banner -->
    <UnifiedLoadingBanner
      v-if="isLoading"
      :loading-title="'Loading Access Requests'"
      :loading-subtitle="'Fetching HOD-approved requests awaiting divisional review...'"
      :department-title="'Divisional Director Dashboard'"
    />

    <!-- Timeline Modal -->
    <RequestTimeline
      :show="showTimeline"
      :request-id="selectedRequestId"
      @close="closeTimeline"
      @updated="handleTimelineUpdate"
    />

    <!-- Cancel Confirmation Modal -->
    <div
      v-if="showCancelModal"
      class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[9999] p-4"
      @click.self="closeCancelModal"
    >
      <div
        class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl shadow-2xl max-w-md w-full p-6 border-2 border-blue-400/40 transform transition-all duration-300 animate-scale-up"
      >
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center">
              <i class="fas fa-exclamation-triangle text-red-300 text-xl"></i>
            </div>
            <h3 class="text-xl font-bold text-white">Confirm Cancellation</h3>
          </div>
          <button
            @click="closeCancelModal"
            class="text-blue-200 hover:text-white transition-colors duration-200"
          >
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <!-- Content -->
        <div class="mb-6">
          <p class="text-blue-100 text-lg mb-4 leading-relaxed font-medium">
            Are you sure you want to cancel this request? This action cannot be undone.
          </p>
          <div class="bg-blue-800/40 rounded-lg p-3 border border-blue-400/30">
            <label class="block text-base font-semibold text-blue-200 mb-2">
              Cancellation Reason <span class="text-red-400">*</span>
            </label>
            <textarea
              v-model="cancelReason"
              placeholder="Please provide a reason for cancellation..."
              class="w-full px-3 py-2 bg-white border-2 border-blue-300/50 rounded-lg focus:border-blue-400 focus:outline-none text-gray-900 placeholder-gray-400 transition-all duration-300 resize-none text-base"
              rows="3"
            ></textarea>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button
            @click="closeCancelModal"
            class="flex-1 px-4 py-2.5 bg-blue-800/50 text-white rounded-lg hover:bg-blue-800 transition-all duration-300 font-semibold border border-blue-400/30 text-base"
          >
            <i class="fas fa-arrow-left mr-2"></i>
            Keep Request
          </button>
          <button
            @click="confirmCancel"
            :disabled="!cancelReason || cancelReason.trim() === '' || isCancelling"
            class="flex-1 px-4 py-2.5 rounded-lg transition-all duration-300 font-semibold flex items-center justify-center text-base"
            :class="
              !cancelReason || cancelReason.trim() === '' || isCancelling
                ? 'bg-gray-500 text-gray-300 cursor-not-allowed opacity-50'
                : 'bg-red-600 text-white hover:bg-red-700 shadow-lg hover:shadow-xl transform hover:scale-105'
            "
          >
            <i v-if="isCancelling" class="fas fa-spinner fa-spin mr-2"></i>
            <i v-else class="fas fa-times-circle mr-2"></i>
            {{ isCancelling ? 'Cancelling...' : 'Cancel Request' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import Header from '@/components/header.vue'
  import ModernSidebar from '@/components/ModernSidebar.vue'
  import AppFooter from '@/components/footer.vue'
  import RequestTimeline from '@/components/common/RequestTimeline.vue'
  import UnifiedLoadingBanner from '@/components/common/UnifiedLoadingBanner.vue'
  import divisionalAccessService from '@/services/divisionalAccessService'
  import statusUtils from '@/utils/statusUtils'
  import { useAuth } from '@/composables/useAuth'

  export default {
    name: 'DivisionalRequestList',
    components: {
      Header,
      ModernSidebar,
      AppFooter,
      RequestTimeline,
      UnifiedLoadingBanner
    },
    setup() {
      const { userRole } = useAuth()
      return { userRole }
    },
    data() {
      return {
        requests: [],
        searchQuery: '',
        statusFilter: '',
        isLoading: false,
        stats: {
          pendingDivisional: 0,
          divisionalApproved: 0,
          divisionalRejected: 0,
          total: 0
        },
        error: null,
        // Dropdown state management
        openDropdownId: null,
        // Retry state for SMS to ICT Director
        retryAttempts: {},
        retryTimers: {},
        maxRetryAttempts: 5,
        retryDelays: [3000, 7000, 15000, 30000, 60000],
        // Timeline modal state
        showTimeline: false,
        selectedRequestId: null,
        // Cancel modal state
        showCancelModal: false,
        requestToCancel: null,
        cancelReason: '',
        isCancelling: false,
        // Add status utilities for consistent status handling
        $statusUtils: statusUtils,
        // Debounce handling
        fetchTimeout: null,
        isFetchingData: false
      }
    },
    computed: {
      filteredRequests() {
        // Ensure requests is always an array
        if (!Array.isArray(this.requests)) {
          console.warn('DivisionalRequestList: requests is not an array:', this.requests)
          return []
        }

        let filtered = this.requests

        // Filter by search query
        if (this.searchQuery) {
          const query = this.searchQuery.toLowerCase()
          filtered = filtered.filter(
            (request) =>
              (request.staff_name || request.full_name || '').toLowerCase().includes(query) ||
              (request.pf_number || '').toLowerCase().includes(query) ||
              (request.department || '').toLowerCase().includes(query) ||
              (request.request_id || '').toLowerCase().includes(query)
          )
        }

        // Filter by status - use exact database status
        if (this.statusFilter) {
          filtered = filtered.filter((request) => request.status === this.statusFilter)
        }

        // Sort by HOD approval date (FIFO order - oldest HOD approval first)
        return filtered.sort((a, b) => {
          const dateA = new Date(a.hod_approved_at || a.hod_approval_date || 0)
          const dateB = new Date(b.hod_approved_at || b.hod_approval_date || 0)
          return dateA - dateB
        })
      }
    },
    async mounted() {
      try {
        console.log('DivisionalRequestList: Component mounted, initializing...')
        const token = localStorage.getItem('auth_token')
        if (!token) {
          console.log('DivisionalRequestList: waiting for auth-ready event before fetching...')
          this.isLoading = true
          window.addEventListener('auth-ready', this.onAuthReady, { once: true })
        } else {
          await this.fetchRequests()
          console.log('DivisionalRequestList: Component initialized successfully')
        }

        // Poll periodically to reflect user-side cancellations (silent to avoid blocking UI)
        // Delay first poll to avoid immediate duplicate request
        this._poller = setInterval(() => this.fetchRequests({ silent: true }), 30000)

        // Add click listener to close dropdowns when clicking outside
        document.addEventListener('click', this.closeAllDropdowns)
      } catch (error) {
        console.error('DivisionalRequestList: Error during mount:', error)
        this.error = 'Failed to initialize component: ' + error.message
        this.isLoading = false
      }
    },

    beforeUnmount() {
      // Clean up event listeners
      document.removeEventListener('click', this.closeAllDropdowns)
      if (this._poller) clearInterval(this._poller)
      window.removeEventListener('auth-ready', this.onAuthReady)
    },
    methods: {
      onAuthReady() {
        console.log('DivisionalRequestList: auth-ready received; fetching requests...')
        this.fetchRequests()
      },

      async fetchRequests(options = { silent: false }) {
        // Prevent multiple simultaneous fetches
        if (this.isFetchingData) {
          console.log('Fetch already in progress, skipping...')
          return
        }

        this.isFetchingData = true
        if (!options?.silent) {
          this.isLoading = true
          this.error = null
        }

        try {
          console.log('Fetching combined access requests for Divisional Director approval...')

          const response = await divisionalAccessService.getDivisionalRequests({
            search: this.searchQuery || undefined,
            status: this.statusFilter || undefined,
            per_page: 50
          })

          if (response.success) {
            // Handle the nested response structure: response.data.data.data
            const responseData = response.data?.data || response.data || {}
            this.requests = Array.isArray(responseData.data)
              ? responseData.data
              : Array.isArray(responseData)
                ? responseData
                : []
            console.log('Combined access requests loaded:', this.requests.length)
            console.log('Raw response data:', response.data)

            // Also fetch statistics
            await this.fetchStatistics()
            // Initialize auto-retry for pending/failed SMS (to ICT Director)
            if (typeof this.initAutoRetryForList === 'function') this.initAutoRetryForList()
          } else {
            throw new Error(response.error || 'Failed to fetch requests')
          }
        } catch (error) {
          // Ignore abort errors from rapid clicks or navigation
          if (
            error.message === 'Request aborted' ||
            error.message?.includes('aborted') ||
            error.message?.includes('canceled')
          ) {
            // Silently ignore aborted requests - no logging, no error display
            this.isFetchingData = false
            if (!options?.silent) this.isLoading = false
            return
          }

          console.error('Error fetching requests:', error)
          if (!options?.silent) {
            this.error =
              'Unable to load combined access requests. Please check your connection and try again.'
          }
          this.requests = []
          this.calculateStats()
        } finally {
          this.isFetchingData = false
          if (!options?.silent) this.isLoading = false
        }
      },

      async fetchStatistics() {
        try {
          const response = await divisionalAccessService.getDivisionalStatistics()

          if (response.success) {
            this.stats = response.data
          } else {
            // Fall back to calculating stats from loaded requests
            this.calculateStats()
          }
        } catch (error) {
          console.error('Error fetching statistics:', error)
          // Fall back to calculating stats from loaded requests
          this.calculateStats()
        }
      },

      calculateStats() {
        // Ensure requests is an array before calculating stats
        const requests = Array.isArray(this.requests) ? this.requests : []

        this.stats = {
          // Count based on exact database status values for divisional director
          pending: requests.filter((r) => r.status === 'pending' || r.status === 'pending_hod')
            .length,
          hodApproved: requests.filter((r) => r.status === 'hod_approved').length,
          pendingDivisional: requests.filter((r) => r.status === 'hod_approved').length,
          divisionalApproved: requests.filter((r) => r.status === 'divisional_approved').length,
          divisionalRejected: requests.filter((r) => r.status === 'divisional_rejected').length,
          approved: requests.filter((r) => r.status === 'approved').length,
          implemented: requests.filter((r) => r.status === 'implemented').length,
          completed: requests.filter((r) => r.status === 'completed').length,
          cancelled: requests.filter((r) => r.status === 'cancelled').length,
          total: requests.length
        }
      },

      async refreshRequests() {
        // Debounce rapid refresh clicks
        if (this.fetchTimeout) {
          clearTimeout(this.fetchTimeout)
        }

        this.fetchTimeout = setTimeout(() => {
          this.fetchRequests()
        }, 300)
      },

      viewAndProcessRequest(requestId) {
        // Navigate to divisional review route for both-service-form.vue
        this.$router.push(`/divisional-dashboard/both-service-form/${requestId}`)
      },

      editRequest(requestId) {
        // Navigate to divisional review route (edit handled within page if applicable)
        this.$router.push(`/divisional-dashboard/both-service-form/${requestId}?mode=edit`)
      },

      cancelRequest(requestId) {
        // Open cancel confirmation modal
        this.requestToCancel = requestId
        this.showCancelModal = true
        this.cancelReason = ''
      },

      closeCancelModal() {
        this.showCancelModal = false
        this.requestToCancel = null
        this.cancelReason = ''
        this.isCancelling = false
      },

      async confirmCancel() {
        if (!this.cancelReason || this.cancelReason.trim() === '') {
          return
        }

        this.isCancelling = true

        try {
          console.log('Cancelling request:', this.requestToCancel)

          const response = await divisionalAccessService.cancelRequest(
            this.requestToCancel,
            this.cancelReason.trim()
          )

          if (response.success) {
            // Update local state
            const requestIndex = this.requests.findIndex((r) => r.id === this.requestToCancel)
            if (requestIndex !== -1) {
              this.requests[requestIndex].divisional_status = 'cancelled'
              this.requests[requestIndex].status = 'cancelled'
            }

            this.calculateStats()
            this.closeCancelModal()

            // Show success message
            alert('Request cancelled successfully!')
          } else {
            throw new Error(response.error || 'Failed to cancel request')
          }
        } catch (error) {
          console.error('Error cancelling request:', error)
          alert('Error cancelling request: ' + error.message)
          this.isCancelling = false
        }
      },

      hasService(request, serviceType) {
        return (
          (request.services && request.services.includes(serviceType)) ||
          (request.request_types &&
            request.request_types.some(
              (type) =>
                (serviceType === 'jeeva' && type === 'jeeva_access') ||
                (serviceType === 'wellsoft' && type === 'wellsoft') ||
                (serviceType === 'internet' && type === 'internet_access_request')
            ))
        )
      },

      canEdit(request) {
        // Divisional Director can edit requests that are HOD approved and pending divisional approval
        return request.status === 'hod_approved'
      },

      canCancel(request) {
        // Divisional Director can cancel requests that are not in final states
        const finalStatuses = [
          'divisional_rejected',
          'cancelled',
          'approved',
          'implemented',
          'completed'
        ]
        return !finalStatuses.includes(request.status)
      },

      formatDate(dateString) {
        if (!dateString) return 'N/A'
        const date = new Date(dateString)
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })
      },

      formatTime(dateString) {
        if (!dateString) return 'N/A'
        const date = new Date(dateString)
        return date.toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit'
        })
      },

      getStatusBadgeClass(status) {
        return this.$statusUtils.getStatusBadgeClass(status)
      },

      getStatusIcon(status) {
        return this.$statusUtils.getStatusIcon(status)
      },

      getStatusText(status) {
        // Use centralized status utility with component name for debugging
        return this.$statusUtils.getStatusText(status, 'DivisionalRequestList')
      },

      // Dropdown management methods
      toggleDropdown(requestId) {
        console.log('Toggle dropdown for request:', requestId)
        this.openDropdownId = this.openDropdownId === requestId ? null : requestId

        if (this.openDropdownId === requestId) {
          this.$nextTick(() => {
            console.log('Dropdown opened for request:', requestId)
          })
        }
      },

      closeAllDropdowns(event) {
        // Only close if clicking outside the dropdown
        if (!event || !event.target.closest('.three-dot-menu')) {
          console.log('Closing all dropdowns')
          this.openDropdownId = null
        }
      },

      // Available actions based on user role and request status
      getAvailableActions(request) {
        const actions = []
        const status = request.status

        // Check if already approved by Divisional Director
        const isAlreadyApproved = [
          'divisional_approved',
          'ict_director_approved',
          'dict_approved',
          'head_it_approved',
          'approved',
          'assigned_to_ict',
          'implementation_in_progress',
          'implemented',
          'completed'
        ].includes(status)

        // Only show Approve button if NOT already approved and pending divisional approval
        if (!isAlreadyApproved && status === 'hod_approved') {
          actions.push({
            key: 'view_and_process',
            label: 'Approve',
            icon: 'fas fa-check-circle',
            color: 'green'
          })
        }

        // View Approved Request - only for approved requests
        if (isAlreadyApproved) {
          actions.push({
            key: 'view_approved_request',
            label: 'View Approved Request',
            icon: 'fas fa-eye',
            color: 'blue'
          })
        }

        // Cancel action for cancellable requests (not for already approved)
        if (this.canCancel(request) && !isAlreadyApproved) {
          actions.push({
            key: 'cancel_request',
            label: 'Cancel Request',
            icon: 'fas fa-times'
          })
        }

        // Always show View Timeline
        actions.push({
          key: 'view_timeline',
          label: 'View Timeline',
          icon: 'fas fa-history'
        })

        return actions
      },

      // Execute action based on key
      executeAction(actionKey, request) {
        this.closeAllDropdowns()

        switch (actionKey) {
          case 'view_and_process':
            this.viewAndProcessRequest(request.id)
            break
          case 'view_approved_request':
            this.viewApprovedRequest(request.id)
            break
          case 'edit_request':
            this.editRequest(request.id)
            break
          case 'cancel_request':
            this.cancelRequest(request.id)
            break
          case 'view_timeline':
            this.viewTimeline(request)
            break
          default:
            console.warn('Unknown action:', actionKey)
        }
      },

      viewApprovedRequest(requestId) {
        // Navigate to view-only mode for approved requests
        this.$router.push({
          path: `/divisional-dashboard/both-service-form/${requestId}`,
          query: { mode: 'view', readonly: 'true' }
        })
      },

      // Timeline modal methods
      viewTimeline(request) {
        console.log('📅 DivisionalRequestList: Opening timeline for request:', request.id)
        this.selectedRequestId = request.id
        this.showTimeline = true
      },

      closeTimeline() {
        console.log('📅 DivisionalRequestList: Closing timeline modal')
        this.showTimeline = false
        this.selectedRequestId = null
      },

      async handleTimelineUpdate() {
        console.log('🔄 DivisionalRequestList: Timeline updated, refreshing requests list...')
        await this.fetchRequests()
      },

      // Helper method to determine if separator should be shown
      shouldShowSeparator(currentAction, previousAction) {
        // Add separator before destructive actions or different action groups
        const destructiveActions = ['cancel_request']
        const viewActions = ['view_timeline', 'view_progress']

        // Separate destructive actions
        if (
          destructiveActions.includes(currentAction.key) &&
          !destructiveActions.includes(previousAction?.key)
        ) {
          return true
        }

        // Separate view actions from edit actions
        if (
          viewActions.includes(currentAction.key) &&
          !viewActions.includes(previousAction?.key) &&
          previousAction?.key !== 'view_and_process'
        ) {
          return true
        }

        return false
      },

      // SMS Status methods - handles all possible SMS statuses
      // 'sent' = SMS sent to provider, waiting for delivery confirmation
      // 'delivered' = Delivery confirmed via callback from provider
      // 'pending' = Not yet processed/sent
      // 'failed' = SMS sending failed
      // 'disabled' = SMS service is disabled
      // 'test_mode' = SMS is in test mode
      getRelevantSmsStatus(request) {
        // For Divisional Director: show SMS status for NEXT workflow step after their approval
        const status = request.divisional_status || request.status

        // If Divisional Director has APPROVED: show ICT Director notification status
        if (
          status === 'divisional_approved' ||
          status === 'approved' ||
          status === 'dict_approved' ||
          status === 'implemented'
        ) {
          return request.sms_to_ict_director_status || 'pending'
        }

        // If PENDING Divisional approval: return 'pending' (no action notification sent yet)
        // Don't show sms_to_divisional_status (that's the incoming notification)
        return 'pending'
      },

      getSmsStatusText(smsStatus) {
        const statusMap = {
          delivered: 'Delivered',
          sent: 'Sent',
          pending: 'Pending',
          failed: 'Failed',
          disabled: 'Disabled',
          test_mode: 'Test Mode'
        }
        return statusMap[smsStatus] || 'Pending'
      },

      getSmsStatusColor(smsStatus) {
        const colorMap = {
          delivered: 'bg-green-500',
          sent: 'bg-blue-500',
          pending: 'bg-yellow-500',
          failed: 'bg-red-500',
          disabled: 'bg-gray-500',
          test_mode: 'bg-purple-500'
        }
        return colorMap[smsStatus] || 'bg-gray-400'
      },

      getSmsStatusTextColor(smsStatus) {
        const textColorMap = {
          delivered: 'text-green-400',
          sent: 'text-blue-400',
          pending: 'text-yellow-400',
          failed: 'text-red-400',
          disabled: 'text-gray-400',
          test_mode: 'text-purple-400'
        }
        return textColorMap[smsStatus] || 'text-gray-400'
      },

      // Get button styling classes based on action type
      getActionButtonClass(actionKey) {
        const classMap = {
          view_and_process: 'text-green-700 hover:bg-green-50 hover:text-green-800',
          view_approved_request: 'text-blue-700 hover:bg-blue-50 hover:text-blue-800',
          cancel_request: 'text-red-700 hover:bg-red-50 hover:text-red-800',
          view_progress: 'text-blue-700 hover:bg-blue-50 hover:text-blue-800',
          view_timeline: 'text-gray-700 hover:bg-gray-50 hover:text-gray-800'
        }
        return classMap[actionKey] || 'text-gray-700 hover:bg-gray-50 hover:text-gray-800'
      },

      // Get icon styling classes based on action type
      getActionIconClass(actionKey) {
        const classMap = {
          view_and_process: 'text-green-600 group-hover:text-green-700',
          view_approved_request: 'text-blue-600 group-hover:text-blue-700',
          cancel_request: 'text-red-600 group-hover:text-red-700',
          view_progress: 'text-blue-600 group-hover:text-blue-700',
          view_timeline: 'text-gray-600 group-hover:text-gray-700'
        }
        return classMap[actionKey] || 'text-gray-600 group-hover:text-gray-700'
      }
    }
  }
</script>

<style scoped>
  /* Unified styling for status filter dropdown to match header blue */
  .status-select {
    background-color: rgba(255, 255, 255, 0.12);
    color: #fff;
    border-color: rgba(147, 197, 253, 0.4);
  }
  .status-select:focus {
    outline: none;
    border-color: #60a5fa; /* blue-400 */
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.35);
  }
  .status-select option,
  .status-select optgroup {
    background-color: #1e3a8a; /* blue-800 */
    color: #ffffff;
  }
  /* Override the sidebar width for this component to be wider */
  .sidebar-narrow :deep(.sidebar-expanded) {
    width: 20.5rem !important;
  }

  .sidebar-narrow :deep(.sidebar-collapsed) {
    width: 64px !important;
  }

  /* Ensure sidebar maintains flexbox layout for bottom buttons */
  .sidebar-narrow :deep(aside) {
    display: flex !important;
    flex-direction: column !important;
    height: 100vh !important;
  }

  .sidebar-narrow :deep(aside > div) {
    display: flex !important;
    flex-direction: column !important;
    height: 100% !important;
  }

  .sidebar-narrow :deep(nav) {
    flex: 1 !important;
    min-height: 0 !important;
    max-height: calc(100vh - 350px) !important;
    overflow-y: auto !important;
  }

  .sidebar-narrow :deep(.bottom-section) {
    margin-top: auto !important;
    flex-shrink: 0 !important;
    padding-bottom: 1rem !important;
  }

  /* Three-dot menu enhancements */
  .three-dot-menu {
    position: relative;
  }

  /* Ensure dropdown is always visible and properly positioned */
  .dropdown-menu {
    position: absolute !important;
    z-index: 99999 !important;
    min-width: 12rem;
    max-width: 16rem;
  }

  /* Fix for table cell positioning context */
  tbody tr {
    position: relative;
  }

  tbody tr td:last-child {
    position: relative !important;
    overflow: visible !important;
  }

  .three-dot-menu {
    position: relative;
    z-index: 1;
  }

  .three-dot-menu .dropdown-menu {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
  }

  /* Open dropdown upward for last 2 rows to prevent clipping */
  tbody tr:nth-last-child(-n + 2) .dropdown-menu {
    bottom: calc(100% + 4px);
    top: auto;
  }

  /* Mobile responsive adjustments */
  @media (max-width: 768px) {
    .dropdown-menu {
      min-width: 10rem;
      max-width: 14rem;
      right: 1rem !important;
    }

    /* Increase touch target size on mobile */
    .three-dot-button {
      padding: 12px !important;
      min-width: 44px;
      min-height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .dropdown-menu button {
      padding: 12px 16px !important;
      font-size: 16px !important;
    }
  }

  /* Tablet adjustments */
  @media (min-width: 769px) and (max-width: 1024px) {
    .dropdown-menu {
      min-width: 11rem;
      max-width: 15rem;
    }
  }

  /* Animation for dropdown appearance */
  .dropdown-menu {
    animation: dropdownFadeIn 0.15s ease-out;
    transform-origin: top right;
    /* Ensure dropdown appears above other elements */
    box-shadow:
      0 10px 25px rgba(0, 0, 0, 0.15),
      0 4px 6px rgba(0, 0, 0, 0.1) !important;
  }

  /* Ensure table cells allow overflow for dropdown */
  .three-dot-menu {
    position: relative;
    z-index: 10;
  }

  /* Make sure table doesn't clip dropdown but stays below header */
  table {
    position: relative;
    z-index: 1;
    overflow: visible;
  }

  /* Ensure main content doesn't block header dropdowns */
  main {
    position: relative;
    z-index: 1;
  }

  @keyframes dropdownFadeIn {
    from {
      opacity: 0;
      transform: scale(0.95) translateY(-5px);
    }
    to {
      opacity: 1;
      transform: scale(1) translateY(0);
    }
  }

  /* Hover effects for better UX */
  .dropdown-menu button:hover {
    transform: translateX(2px);
  }

  /* High contrast focus states for accessibility */
  .three-dot-button:focus {
    outline: 2px solid #3b82f6 !important;
    outline-offset: 2px;
  }

  .dropdown-menu button:focus {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
    background-color: #f3f4f6;
  }
</style>
