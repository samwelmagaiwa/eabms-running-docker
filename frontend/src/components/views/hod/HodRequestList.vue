<template>
  <div class="flex flex-col h-screen">
    <Header />
    <div class="flex flex-1 overflow-hidden">
      <div class="sidebar-narrow">
        <ModernSidebar />
      </div>
      <main class="flex-1 p-4 bg-blue-900 overflow-y-auto">
        <div class="max-w-full mx-auto">
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
              <h3 class="text-yellow-200 text-lg font-bold">Pending HOD Approval</h3>
              <p class="text-white text-3xl font-bold">{{ stats.pendingHod }}</p>
            </div>

            <div class="bg-green-600/25 border border-green-400/40 p-4 rounded-lg">
              <h3 class="text-green-200 text-lg font-bold">HOD Approved</h3>
              <p class="text-white text-3xl font-bold">{{ stats.hodApproved }}</p>
            </div>

            <div class="bg-red-600/25 border border-red-400/40 p-4 rounded-lg">
              <h3 class="text-red-200 text-lg font-bold">HOD Rejected</h3>
              <p class="text-white text-3xl font-bold">{{ stats.hodRejected }}</p>
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
                <option value="hod_approved">HOD Approved</option>
                <option value="hod_rejected">HOD Rejected</option>
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
          <div class="bg-white/10 rounded-lg overflow-visible relative">
            <table class="w-full">
              <thead class="bg-blue-800/50">
                <tr>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">Request ID</th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">Request Type</th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                    Personal Information
                  </th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                    Submission Date
                  </th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                    Current Status
                  </th>
                  <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">SMS Status</th>
                  <th class="px-4 py-3 text-center text-blue-100 text-sm font-bold">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="request in filteredRequests"
                  :key="request.id"
                  class="border-t border-blue-300/20 hover:bg-blue-700/30"
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

                  <!-- Submission Date -->
                  <td class="px-4 py-3">
                    <div class="text-white font-bold text-sm">
                      {{ formatDate(request.created_at || request.submission_date) }}
                    </div>
                    <div class="text-blue-300 text-xs">
                      {{ formatTime(request.created_at || request.submission_date) }}
                    </div>
                  </td>

                  <!-- Current Status -->
                  <td class="px-4 py-4">
                    <div class="flex flex-col">
                      <span
                        v-if="request.divisional_status === 'skipped'"
                        class="mb-1 rounded text-xs font-semibold inline-block bg-red-900/30 text-red-300 border border-red-500/40 px-2 py-0.5"
                        :style="{ width: 'fit-content' }"
                      >
                        No Divisional Director — Stage Skipped
                      </span>
                      <span
                        :class="
                          getStatusBadgeClass(request.hod_status || request.status || 'pending_hod')
                        "
                        class="rounded text-sm font-bold inline-block"
                        :style="{ padding: '4px 8px', width: 'fit-content' }"
                      >
                        {{ getStatusText(request.hod_status || request.status || 'pending_hod') }}
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
                        :disabled="isRetrying(request.id)"
                        class="ml-2 px-2 py-1 text-xs rounded border border-blue-300/50 text-blue-100 hover:bg-blue-700/40 disabled:opacity-50"
                        title="Retry sending SMS"
                      >
                        <span v-if="!isRetrying(request.id)">Retry</span>
                        <span v-else><i class="fas fa-spinner fa-spin mr-1"></i>Retrying</span>
                      </button>
                      <span
                        v-if="getRetryAttempts(request.id) > 0"
                        class="text-xs text-blue-200 ml-1"
                      >
                        ({{ getRetryAttempts(request.id) }})
                      </span>
                    </div>
                  </td>

                  <!-- Actions -->
                  <td class="px-4 py-3 text-center relative">
                    <div class="flex justify-center three-dot-menu">
                      <button
                        @click.stop="toggleDropdown(request.id)"
                        :data-request-id="request.id"
                        class="three-dot-button p-2 text-white hover:bg-blue-600/40 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-400 hover:scale-105 active:scale-95"
                        :class="{ 'bg-blue-600/40 shadow-lg': activeDropdown === request.id }"
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
                    ? 'Try adjusting your filters'
                    : 'No combined access requests have been submitted yet.'
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

    <!-- Loading Modal -->
    <div
      v-if="isLoading"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div
        class="rounded-xl shadow-2xl p-8 text-center border border-blue-400/40"
        style="background: linear-gradient(90deg, #0b3a82, #0a2f6f, #0b3a82)"
      >
        <div class="flex justify-center mb-4">
          <div
            class="w-12 h-12 border-4 border-white border-t-transparent rounded-full animate-spin"
          ></div>
        </div>
        <p class="text-blue-100 font-medium">Loading requests...</p>
      </div>
    </div>

    <!-- Timeline Modal -->
    <RequestTimeline
      :show="showTimeline"
      :request-id="selectedRequestId"
      @close="closeTimeline"
      @updated="handleTimelineUpdate"
    />

    <!-- Global Dropdown Portal -->
    <div v-if="activeDropdown" class="dropdown-portal">
      <div
        class="fixed w-56 origin-top-right bg-white rounded-xl shadow-2xl border border-gray-200/50 focus:outline-none backdrop-blur-sm dropdown-menu"
        :style="getGlobalDropdownStyle()"
        @click.stop
      >
        <div class="py-2">
          <template v-if="activeRequest">
            <!-- Show Approve and Edit buttons only for pending requests -->
            <button
              v-if="!isCancelledByUser(activeRequest) && !isRequestApproved(activeRequest)"
              @click="viewAndProcessRequest(activeRequest.id)"
              class="group flex items-center w-full px-4 py-3 text-xl text-green-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-green-100 hover:text-green-800 transition-all duration-200 font-semibold"
            >
              <i
                class="fas fa-check-circle mr-3 text-green-600 group-hover:text-green-700 text-xl"
              ></i>
              Approve
            </button>

            <button
              v-if="!isCancelledByUser(activeRequest) && !isRequestApproved(activeRequest)"
              @click="editRequestForReview(activeRequest.id)"
              class="group flex items-center w-full px-4 py-3 text-xl text-blue-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-100 hover:text-blue-800 transition-all duration-200 font-semibold"
            >
              <i class="fas fa-edit mr-3 text-blue-500 group-hover:text-blue-600 text-xl"></i>
              Edit Request
            </button>

            <!-- Show View Approved Request for approved requests -->
            <button
              v-if="isRequestApproved(activeRequest)"
              @click="viewApprovedRequest(activeRequest.id)"
              class="group flex items-center w-full px-4 py-3 text-xl text-purple-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-purple-100 hover:text-purple-800 transition-all duration-200 font-semibold"
            >
              <i class="fas fa-eye mr-3 text-purple-600 group-hover:text-purple-700 text-xl"></i>
              View Approved Request
            </button>

            <button
              v-if="isCancelledByUser(activeRequest)"
              @click="deleteRequest(activeRequest.id)"
              class="group flex items-center w-full px-4 py-3 text-xl text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-red-100 hover:text-red-700 transition-all duration-200 font-semibold"
            >
              <i class="fas fa-trash mr-3 text-red-500 group-hover:text-red-600 text-xl"></i>
              Delete
            </button>

            <button
              v-if="canEdit(activeRequest)"
              @click="editRequest(activeRequest.id)"
              class="group flex items-center w-full px-4 py-3 text-xl text-gray-700 hover:bg-gradient-to-r hover:from-amber-50 hover:to-amber-100 hover:text-amber-700 transition-all duration-200 font-semibold"
            >
              <i class="fas fa-edit mr-3 text-amber-500 group-hover:text-amber-600 text-xl"></i>
              Edit
            </button>

            <!-- Cancel button only for pending requests -->
            <div
              v-if="canCancel(activeRequest) && !isRequestApproved(activeRequest)"
              class="border-t border-gray-100 my-1"
            ></div>

            <button
              v-if="canCancel(activeRequest) && !isRequestApproved(activeRequest)"
              @click="cancelRequest(activeRequest.id)"
              class="group flex items-center w-full px-4 py-3 text-xl text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-red-100 hover:text-red-700 transition-all duration-200 font-semibold"
            >
              <i class="fas fa-ban mr-3 text-red-500 group-hover:text-red-600 text-xl"></i>
              Cancel
            </button>

            <div class="border-t border-gray-100 my-1"></div>

            <button
              @click="viewTimeline(activeRequest)"
              class="group flex items-center w-full px-4 py-3 text-xl text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-indigo-100 hover:text-indigo-700 transition-all duration-200 font-semibold"
            >
              <i
                class="fas fa-history mr-3 text-indigo-500 group-hover:text-indigo-600 text-xl"
              ></i>
              View Timeline
            </button>
          </template>
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
  import combinedAccessService from '@/services/combinedAccessService'
  import statusUtils from '@/utils/statusUtils'
  import notificationService from '@/services/notificationService'

  export default {
    name: 'HodRequestList',
    components: {
      Header,
      ModernSidebar,
      AppFooter,
      RequestTimeline
    },
    setup() {
      return {
        // No local state needed for sidebar
      }
    },
    data() {
      return {
        requests: [],
        searchQuery: '',
        statusFilter: '',
        isLoading: false,
        stats: {
          pendingHod: 0,
          hodApproved: 0,
          hodRejected: 0,
          total: 0
        },
        error: null,
        activeDropdown: null,
        // Effects toggles
        enableBackgroundFX: false,
        enableTransitions: false,
        // Timeline modal state
        showTimeline: false,
        selectedRequestId: null,
        // Add status utilities for consistent status handling
        $statusUtils: statusUtils,
        // Debounce handling
        fetchTimeout: null,
        isFetchingData: false,
        // Retry state
        retryAttempts: {},
        retryTimers: {},
        maxRetryAttempts: 5,
        retryDelays: [3000, 7000, 15000, 30000, 60000]
      }
    },
    computed: {
      filteredRequests() {
        // Ensure requests is always an array
        if (!Array.isArray(this.requests)) {
          console.warn('HodRequestList: requests is not an array:', this.requests)
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

        // Filter by status
        if (this.statusFilter) {
          filtered = filtered.filter(
            (request) =>
              (request.hod_status || request.status || 'pending_hod') === this.statusFilter
          )
        }

        // Sort: pending first, then by FIFO order (oldest first)
        return filtered.sort((a, b) => {
          const statusA = a.hod_status || a.status || 'pending_hod'
          const statusB = b.hod_status || b.status || 'pending_hod'

          // Pending requests come first
          const isPendingA = statusA === 'pending_hod' || statusA === 'pending'
          const isPendingB = statusB === 'pending_hod' || statusB === 'pending'

          if (isPendingA && !isPendingB) return -1
          if (!isPendingA && isPendingB) return 1

          // Within same priority group, sort by FIFO (oldest first)
          const dateA = new Date(a.created_at || a.submission_date || 0)
          const dateB = new Date(b.created_at || b.submission_date || 0)
          return dateA - dateB
        })
      },
      activeRequest() {
        return this.getActiveRequest()
      }
    },
    async mounted() {
      try {
        console.log('HodRequestList: Component mounted, initializing...')
        const token = localStorage.getItem('auth_token')
        if (!token) {
          console.log('HodRequestList: waiting for auth-ready event before fetching...')
          this.isLoading = true
          window.addEventListener('auth-ready', this.onAuthReady, { once: true })
        } else {
          await this.fetchRequests()
          console.log('HodRequestList: Component initialized successfully')
        }

        // Poll periodically to reflect user-side cancellations (silent to avoid blocking UI)
        // Delay first poll to avoid immediate duplicate request
        this._poller = setInterval(() => this.fetchRequests({ silent: true }), 30000)

        // Add click listener to close dropdowns when clicking outside
        document.addEventListener('click', this.closeDropdowns)
      } catch (error) {
        console.error('HodRequestList: Error during mount:', error)
        this.error = 'Failed to initialize component: ' + error.message
        this.isLoading = false
      }
    },

    beforeUnmount() {
      // Clean up the click listener
      document.removeEventListener('click', this.closeDropdowns)
      if (this._poller) clearInterval(this._poller)
      window.removeEventListener('auth-ready', this.onAuthReady)
    },

    methods: {
      onAuthReady() {
        console.log('HodRequestList: auth-ready received; fetching requests...')
        this.fetchRequests()
      },

      toggleDropdown(requestId) {
        const idStr = String(requestId)
        console.log('Toggle dropdown for request:', idStr)
        console.log('Current activeDropdown:', this.activeDropdown)

        if (String(this.activeDropdown) === idStr) {
          this.activeDropdown = null
          console.log('Closing dropdown')
        } else {
          this.activeDropdown = idStr
          console.log('Opening dropdown for:', idStr)

          // Wait for DOM update before calculating position
          this.$nextTick(() => {
            // Force recalculation of dropdown position
            this.$forceUpdate()
          })
        }
      },

      closeDropdowns(event) {
        // Only close if clicking outside the dropdown area
        if (!event || !event.target.closest('.relative')) {
          this.activeDropdown = null
        }
      },

      // Deprecated (kept for reference) - per-cell dropdown
      getDropdownStyle(requestId) {
        if (this.activeDropdown !== requestId) {
          return { display: 'none' }
        }

        // Find the button element
        const idStr = String(requestId)
        const buttonElement = document.querySelector(`[data-request-id="${idStr}"]`)
        const dropdownEl = document.getElementById('dropdown-' + idStr)

        // Fallback defaults
        let rect = { top: 50, bottom: 60, right: window.innerWidth - 10 }
        if (buttonElement) rect = buttonElement.getBoundingClientRect()

        const viewportHeight = window.innerHeight
        const measuredHeight = dropdownEl ? dropdownEl.offsetHeight : 240
        const dropdownHeight = Math.min(measuredHeight || 240, viewportHeight - 20)

        let top = rect.bottom + 8
        let right = Math.max(10, window.innerWidth - rect.right)

        // If dropdown would go below viewport, position it above the button
        if (top + dropdownHeight > viewportHeight) {
          top = Math.max(10, rect.top - dropdownHeight - 8)
        }

        // Ensure top stays within viewport
        if (top < 10) top = 10

        return {
          position: 'fixed',
          top: top + 'px',
          right: right + 'px',
          zIndex: 99999
        }
      },
      getActiveRequest() {
        if (!this.activeDropdown) return null
        return this.filteredRequests.find((r) => String(r.id) === String(this.activeDropdown))
      },

      getGlobalDropdownStyle() {
        if (!this.activeDropdown) return { display: 'none' }

        const idStr = String(this.activeDropdown)
        const buttonElement = document.querySelector(`[data-request-id="${idStr}"]`)
        const menuWidth = 224 // w-56
        const defaultStyle = { position: 'fixed', top: '50px', left: '10px', zIndex: 99999 }

        if (!buttonElement) return defaultStyle

        const rect = buttonElement.getBoundingClientRect()
        const viewportHeight = window.innerHeight
        const viewportWidth = window.innerWidth
        const measuredHeight = 240

        let top = rect.bottom + 8
        let left = Math.min(rect.left, viewportWidth - menuWidth - 10)

        // Flip up if bottom overflows
        if (top + measuredHeight > viewportHeight) {
          top = Math.max(10, rect.top - measuredHeight - 8)
        }

        if (left < 10) left = 10
        if (top < 10) top = 10

        return {
          position: 'fixed',
          top: top + 'px',
          left: left + 'px',
          zIndex: 99999
        }
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
          console.log('Fetching combined access requests for HOD approval...')

          const response = await combinedAccessService.getHodRequests({
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
            // Initialize auto-retry for pending/failed SMS (to Divisional Director)
            this.initAutoRetryForList()
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
          const response = await combinedAccessService.getHodStatistics()

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
          pendingHod: requests.filter((r) => (r.hod_status || r.status) === 'pending_hod').length,
          hodApproved: requests.filter((r) => (r.hod_status || r.status) === 'hod_approved').length,
          hodRejected: requests.filter((r) => (r.hod_status || r.status) === 'hod_rejected').length,
          approved: requests.filter((r) => (r.hod_status || r.status) === 'approved').length,
          implemented: requests.filter((r) => (r.hod_status || r.status) === 'implemented').length,
          completed: requests.filter((r) => (r.hod_status || r.status) === 'completed').length,
          cancelled: requests.filter((r) => (r.hod_status || r.status) === 'cancelled').length,
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
        this.closeDropdowns()
        // Navigate to HOD-specific review path in read-only mode for approval
        this.$router.push({
          path: `/hod-combined-requests/both-service-form/${requestId}`,
          query: { mode: 'readonly' }
        })
      },

      editRequestForReview(requestId) {
        this.closeDropdowns()
        // Navigate to editable form for HOD to modify staff request
        this.$router.push({
          path: `/hod-combined-requests/both-service-form/${requestId}`,
          query: { mode: 'edit', role: 'hod' }
        })
      },

      editRequest(requestId) {
        this.closeDropdowns()
        // Navigate to edit mode
        this.$router.push(`/both-service-form/${requestId}/edit`)
      },

      async cancelRequest(requestId) {
        this.closeDropdowns()
        try {
          const confirmed = confirm(
            'Are you sure you want to cancel this request? This action cannot be undone.'
          )
          if (!confirmed) return

          const reason = prompt('Please provide a reason for cancellation:')
          if (!reason || reason.trim() === '') {
            alert('Cancellation reason is required')
            return
          }

          console.log('Cancelling request:', requestId)

          const response = await combinedAccessService.cancelRequest(requestId, reason)

          if (response.success) {
            // Update local state
            const requestIndex = this.requests.findIndex((r) => r.id === requestId)
            if (requestIndex !== -1) {
              this.requests[requestIndex].hod_status = 'cancelled'
              this.requests[requestIndex].status = 'cancelled'
            }

            this.calculateStats()
            alert('Request cancelled successfully!')
          } else {
            throw new Error(response.error || 'Failed to cancel request')
          }
        } catch (error) {
          console.error('Error cancelling request:', error)
          alert('Error cancelling request: ' + error.message)
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
        // HOD can edit requests that are pending
        return (request.hod_status || request.status) === 'pending_hod'
      },

      canCancel(request) {
        // HOD can cancel requests that are not already rejected or cancelled
        const status = request.hod_status || request.status
        return status !== 'hod_rejected' && status !== 'cancelled'
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
        return this.$statusUtils.getStatusText(status, 'HodRequestList')
      },

      // Timeline modal methods
      viewTimeline(request) {
        console.log('📅 HodRequestList: Opening timeline for request:', request.id)
        this.activeDropdown = null
        this.selectedRequestId = request.id
        this.showTimeline = true
      },

      closeTimeline() {
        console.log('📅 HodRequestList: Closing timeline modal')
        this.showTimeline = false
        this.selectedRequestId = null
      },

      async handleTimelineUpdate() {
        console.log('🔄 HodRequestList: Timeline updated, refreshing requests list...')
        await this.fetchRequests()
      },

      // Helper to detect user-cancelled requests
      isCancelledByUser(request) {
        const status = request.hod_status || request.status
        if (status !== 'cancelled') return false
        // Prefer backend-provided boolean if present
        if (typeof request.cancelled_by_user !== 'undefined') {
          return !!request.cancelled_by_user
        }
        // Fallbacks: compare canceller to owner or detect reason text
        const byId =
          !!request.cancelled_by && !!request.user_id && request.cancelled_by === request.user_id
        const byReason = (request.cancellation_reason || '')
          .toString()
          .toLowerCase()
          .includes('cancelled by user')
        return byId || byReason
      },

      // Delete from HOD view (UI-only removal if API not available)
      async deleteRequest(requestId) {
        this.closeDropdowns()
        const confirmed = confirm('Delete this cancelled request from your list?')
        if (!confirmed) return
        try {
          const res = await combinedAccessService.deleteCancelledRequest(requestId)
          if (!res.success) throw new Error(res.error)
          this.requests = this.requests.filter((r) => r.id !== requestId)
          this.calculateStats()
          alert('Request deleted.')
        } catch (e) {
          console.error('HOD delete failed:', e)
          alert('Failed to delete request: ' + e.message)
        }
      },

      // Check if request is approved by HOD
      isRequestApproved(request) {
        const status = request.hod_status || request.status
        return (
          status === 'hod_approved' ||
          status === 'approved' ||
          status === 'divisional_approved' ||
          status === 'ict_director_approved' ||
          status === 'head_it_approved' ||
          status === 'implemented' ||
          status === 'completed'
        )
      },

      // View approved request in full read-only mode
      viewApprovedRequest(requestId) {
        this.closeDropdowns()
        // Navigate to view-only mode with strict readonly flag
        this.$router.push({
          path: `/hod-combined-requests/both-service-form/${requestId}`,
          query: { mode: 'view', readonly: 'true' }
        })
      },

      // SMS retry helpers (HOD -> Divisional Director)
      getRetryAttempts(id) {
        return this.retryAttempts[id] || 0
      },
      isRetrying(id) {
        return !!this.retryTimers[id]
      },
      async retrySendSms(request) {
        if (!request) return
        const id = request.id
        if (!this.retryAttempts[id]) this.retryAttempts[id] = 0
        if (this.isRetrying(id)) return
        try {
          this.retryTimers[id] = setTimeout(() => {}, 0)
          await notificationService.resendSmsGeneric({
            requestId: id,
            role: 'hod',
            target: 'divisional_director'
          })
          setTimeout(async () => {
            await this.fetchRequests({ silent: true })
            clearTimeout(this.retryTimers[id])
            delete this.retryTimers[id]
            const latest = (this.requests || []).find((r) => r.id === id) || request
            const status = this.getRelevantSmsStatus(latest)
            if (!['sent', 'delivered'].includes(status)) {
              this.scheduleNextRetry(id, request)
            } else {
              this.retryAttempts[id] = 0
            }
          }, 1200)
        } catch (e) {
          clearTimeout(this.retryTimers[id])
          delete this.retryTimers[id]
          this.scheduleNextRetry(id, request)
        }
      },
      scheduleNextRetry(id, request) {
        this.retryAttempts[id] = (this.retryAttempts[id] || 0) + 1
        if (this.retryAttempts[id] > this.maxRetryAttempts) return
        const delay =
          this.retryDelays[Math.min(this.retryAttempts[id] - 1, this.retryDelays.length - 1)]
        this.retryTimers[id] = setTimeout(async () => {
          clearTimeout(this.retryTimers[id])
          delete this.retryTimers[id]
          await this.retrySendSms(request)
        }, delay)
      },
      initAutoRetryForList() {
        ;(this.requests || []).forEach((r) => {
          const st = this.getRelevantSmsStatus(r)
          if (['failed', 'pending'].includes(st)) {
            if (!this.retryTimers[r.id] && (this.retryAttempts[r.id] || 0) === 0) {
              this.scheduleNextRetry(r.id, r)
            }
          }
        })
      },

      // SMS Status methods
      getRelevantSmsStatus(request) {
        // For HOD: show SMS status for NEXT workflow step after their approval
        const status = request.hod_status || request.status

        console.log('🔍 HOD SMS Status Check:', {
          requestId: request.id,
          status: status,
          hod_status: request.hod_status,
          sms_to_divisional_status: request.sms_to_divisional_status
        })

        // If HOD has APPROVED: show Divisional Director notification status (next in workflow)
        if (
          status === 'hod_approved' ||
          status === 'approved' ||
          status === 'divisional_approved' ||
          status === 'implemented'
        ) {
          console.log('✅ HOD approved - showing divisional SMS status')
          return request.sms_to_divisional_status || 'pending'
        }

        // If PENDING HOD approval or any other state: return 'pending' (no action notification sent yet)
        // Don't show sms_to_hod_status (that's the incoming notification)
        console.log('⏳ Pending HOD action - returning pending')
        return 'pending'
      },

      // SMS Status methods - handles all possible SMS statuses
      // 'sent' = SMS sent to provider, waiting for delivery confirmation
      // 'delivered' = Delivery confirmed via callback from provider
      // 'pending' = Not yet processed/sent
      // 'failed' = SMS sending failed
      // 'disabled' = SMS service is disabled
      // 'test_mode' = SMS is in test mode
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
      }
    }
  }
</script>

<style scoped>
  /* Force dropdown to be visible above all content */
  .relative {
    position: relative;
    z-index: 1;
  }

  /* Ensure dropdown menus are always on top */
  .dropdown-menu {
    /* Use fixed so it ignores ancestor overflow and aligns to viewport */
    position: fixed !important;
    z-index: 10000 !important;
    /* Keep visible within viewport */
    max-height: calc(100vh - 20px);
    overflow: auto;
    box-shadow:
      0 20px 25px -5px rgba(0, 0, 0, 0.1),
      0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
  }

  /* Prevent overflow clipping in containers */
  .table-container {
    overflow: visible !important;
  }

  /* Dropdown portal - ensure it's above everything */
  .dropdown-portal {
    z-index: 10000;
  }

  .dropdown-portal > div {
    z-index: 10000 !important;
    position: fixed !important;
  }

  /* Pause heavy animations while loading to reduce main-thread work */
  .is-loading * {
    animation: none !important;
    transition: none !important;
  }

  /* Ensure main content doesn't block header dropdowns */
  main {
    position: relative;
    z-index: 1;
  }

  /* Medical Glass morphism effects */
  .medical-glass-card {
    background: rgba(59, 130, 246, 0.15);
    backdrop-filter: blur(25px);
    -webkit-backdrop-filter: blur(25px);
    border: 2px solid rgba(96, 165, 250, 0.3);
    box-shadow:
      0 8px 32px rgba(29, 78, 216, 0.4),
      inset 0 1px 0 rgba(255, 255, 255, 0.1);
  }

  /* Animations */
  @keyframes float {
    0%,
    100% {
      transform: translateY(0px);
    }
    50% {
      transform: translateY(-20px);
    }
  }

  @keyframes fade-in {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fade-in-delay {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes slide-up {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .animate-float {
    animation: float 6s ease-in-out infinite;
  }

  .animate-fade-in {
    animation: fade-in 1s ease-out;
  }

  .animate-fade-in-delay {
    animation: fade-in-delay 1s ease-out 0.3s both;
  }

  .animate-slide-up {
    animation: slide-up 0.6s ease-out;
  }

  /* Focus styles for accessibility */
  input:focus,
  select:focus {
    box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.1);
  }

  button:focus {
    box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.3);
  }

  /* Smooth transitions (opt-in only to avoid global cost) */
  .fx-transitions * {
    transition-property:
      color, background-color, border-color, text-decoration-color, fill, stroke, opacity,
      box-shadow, transform, filter, backdrop-filter;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
  }
  /* Status filter dropdown styling */
  .status-select {
    background-color: rgba(255, 255, 255, 0.12);
    color: #fff;
    border-color: rgba(147, 197, 253, 0.4);
  }
  .status-select:focus {
    outline: none;
    border-color: #60a5fa;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.35);
  }
  .status-select option,
  .status-select optgroup {
    background-color: #1e3a8a;
    color: #ffffff;
  }
</style>
