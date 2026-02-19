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
            <h3 class="font-bold text-xl">Error</h3>
            <p class="text-lg">{{ error }}</p>
            <button
              @click="fetchRequests"
              class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-base font-medium"
            >
              Retry
            </button>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-yellow-600/25 border border-yellow-400/40 p-4 rounded-xl">
              <h3 class="text-yellow-200 text-lg font-bold">Pending My Approval</h3>
              <p class="text-white text-3xl font-extrabold mt-1">{{ stats.pendingDict }}</p>
            </div>
            <div class="bg-green-600/25 border border-green-400/40 p-4 rounded-xl">
              <h3 class="text-green-200 text-lg font-bold">Approved by Me</h3>
              <p class="text-white text-3xl font-extrabold mt-1">{{ stats.dictApproved }}</p>
            </div>
            <div class="bg-red-600/25 border border-red-400/40 p-4 rounded-xl">
              <h3 class="text-red-200 text-lg font-bold">Rejected by Me</h3>
              <p class="text-white text-3xl font-extrabold mt-1">{{ stats.dictRejected }}</p>
            </div>
            <div class="bg-blue-600/25 border border-blue-400/40 p-4 rounded-xl">
              <h3 class="text-blue-200 text-lg font-bold">Total Requests</h3>
              <p class="text-white text-3xl font-extrabold mt-1">{{ stats.total }}</p>
            </div>
          </div>

          <!-- Filters -->
          <div class="bg-white/10 rounded-lg p-4 mb-6">
            <div class="flex gap-4">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by staff name, PF number, or department..."
                class="flex-1 px-4 py-3 bg-white/20 border-2 border-blue-300/40 rounded-lg text-white placeholder-blue-200/60 text-base"
              />
              <select
                v-model="statusFilter"
                class="px-4 py-3 bg-white/20 border-2 border-blue-300/40 rounded-lg text-white text-base status-select"
              >
                <option value="">All My Requests</option>
                <option value="pending">Show All Pending</option>
                <option value="divisional_approved">Pending My Approval</option>
                <option value="ict_director_approved">Approved by Me</option>
                <option value="dict_approved">Dict Approved</option>
                <option value="dict_rejected">Rejected by Me</option>
                <option value="ict_director_rejected">ICT Rejected</option>
                <option value="approved">Approved</option>
                <option value="implemented">Implemented</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <button
                @click="refreshRequests"
                class="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-base font-bold"
              >
                Refresh
              </button>
            </div>
          </div>

          <!-- Requests Table -->
          <div class="bg-white/10 rounded-lg" style="overflow: visible">
            <div style="overflow-x: auto">
              <table class="w-full">
                <thead class="bg-blue-800/50">
                  <tr>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">Request ID</th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                      Request Type
                    </th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                      Personal Information
                    </th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-bold">
                      Divisional Approval Date
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
                    :class="[
                      'border-t border-blue-300/20 hover:bg-blue-700/30 transition-colors duration-200',
                      isPendingStatus(request.status)
                        ? 'bg-yellow-900/20 border-l-4 border-yellow-400'
                        : 'bg-green-900/10 border-l-4 border-green-600'
                    ]"
                  >
                    <!-- Request ID -->
                    <td class="px-4 py-3">
                      <div class="flex items-center space-x-2">
                        <!-- Status Indicator Icon -->
                        <div class="flex-shrink-0">
                          <i
                            v-if="isPendingStatus(request.status)"
                            class="fas fa-clock text-yellow-400 text-base"
                            title="Pending your approval"
                          ></i>
                          <i
                            v-else
                            class="fas fa-check-circle text-green-400 text-base"
                            title="Processed by you"
                          ></i>
                        </div>
                        <div>
                          <div class="text-white font-semibold text-sm">
                            {{
                              request.request_id || `REQ-${request.id.toString().padStart(6, '0')}`
                            }}
                          </div>
                          <div class="text-purple-300 text-xs">ID: {{ request.id }}</div>
                        </div>
                      </div>
                    </td>

                    <!-- Request Type -->
                    <td class="px-4 py-3">
                      <div class="flex flex-wrap gap-2">
                        <span
                          v-if="hasService(request, 'jeeva')"
                          class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800"
                        >
                          Jeeva
                        </span>
                        <span
                          v-if="hasService(request, 'wellsoft')"
                          class="px-2 py-1 rounded text-xs bg-green-100 text-green-800"
                        >
                          Wellsoft
                        </span>
                        <span
                          v-if="hasService(request, 'internet')"
                          class="px-2 py-1 rounded text-xs bg-cyan-100 text-cyan-800"
                        >
                          Internet
                        </span>
                      </div>
                    </td>

                    <!-- Personal Information -->
                    <td class="px-4 py-3">
                      <div class="text-white font-semibold text-sm">
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

                    <!-- Divisional Approval Date -->
                    <td class="px-4 py-3">
                      <div class="text-white font-semibold text-sm">
                        {{ formatDate(request.divisional_approved_at) }}
                      </div>
                      <div class="text-blue-300 text-xs">
                        {{ formatTime(request.divisional_approved_at) }}
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
                          class="rounded text-sm font-semibold inline-block"
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
                    <td class="px-4 py-3">
                      <div class="flex items-center space-x-3">
                        <div
                          class="w-3 h-3 rounded-full"
                          :class="getSmsStatusColor(getRelevantSmsStatus(request))"
                        ></div>
                        <span
                          class="text-sm font-semibold"
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

                        <!-- Dropdown menu (using Teleport to render at body level) -->
                        <Teleport to="body">
                          <div
                            v-show="openDropdownId === request.id"
                            :ref="'dropdown-' + request.id"
                            class="dropdown-menu fixed w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-1 min-w-max"
                            :style="getDropdownStyle(request.id)"
                            @click.stop="() => {}"
                          >
                            <!-- Role-specific actions -->
                            <template
                              v-for="(action, index) in getAvailableActions(request)"
                              :key="action.key"
                            >
                              <button
                                @click.stop="executeAction(action.key, request)"
                                class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50 transition-all duration-200 flex items-center space-x-3 group"
                                :class="[
                                  {
                                    'border-t border-gray-100':
                                      index > 0 &&
                                      shouldShowSeparator(
                                        action,
                                        getAvailableActions(request)[index - 1]
                                      )
                                  },
                                  getActionColorClass(action)
                                ]"
                              >
                                <i
                                  :class="[action.icon, getActionIconColorClass(action)]"
                                  class="flex-shrink-0 transition-colors duration-200 text-base"
                                ></i>
                                <span class="font-semibold">{{ action.label }}</span>
                              </button>
                            </template>
                          </div>
                        </Teleport>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Empty State -->
            <div v-if="filteredRequests.length === 0" class="text-center py-12">
              <h3 class="text-white text-xl font-medium mb-2">No requests found</h3>
              <p class="text-blue-300 text-base">
                {{
                  searchQuery || statusFilter
                    ? 'No requests match your current filters.'
                    : 'No requests available for ICT Director review. Requests appear here when approved by Divisional Directors.'
                }}
              </p>
            </div>

            <!-- Pagination -->
            <div v-if="filteredRequests.length > 0" class="px-4 py-3 border-t border-blue-300/30">
              <div class="text-blue-300 text-base">
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
      :loading-subtitle="'Fetching requests awaiting ICT Director review...'"
      :department-title="'ICT Director Dashboard'"
    />

    <!-- Timeline Modal -->
    <RequestTimeline
      :show="showTimeline"
      :request-id="selectedRequestId"
      @close="closeTimeline"
      @updated="handleTimelineUpdate"
    />
  </div>
</template>

<script>
  import Header from '@/components/header.vue'
  import ModernSidebar from '@/components/ModernSidebar.vue'
  import AppFooter from '@/components/footer.vue'
  import RequestTimeline from '@/components/common/RequestTimeline.vue'
  import UnifiedLoadingBanner from '@/components/common/UnifiedLoadingBanner.vue'
  import dictAccessService from '@/services/dictAccessService'
  import statusUtils from '@/utils/statusUtils'
  import { useAuth } from '@/composables/useAuth'
  import notificationService from '@/services/notificationService'

  export default {
    name: 'DictRequestList',
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
          pendingDict: 0,
          dictApproved: 0,
          dictRejected: 0,
          total: 0
        },
        error: null,
        // Dropdown state management
        openDropdownId: null,
        dropdownPositions: {},
        // Timeline modal state
        showTimeline: false,
        selectedRequestId: null,
        // Retry state for SMS to Head of IT
        retryAttempts: {},
        retryTimers: {},
        maxRetryAttempts: 5,
        retryDelays: [3000, 7000, 15000, 30000, 60000],
        // Add status utilities for consistent status handling
        $statusUtils: statusUtils
      }
    },
    watch: {
      // Watch for route changes to refresh data when returning from form
      $route(to, from) {
        console.log('DictRequestList: Route changed from', from?.path, 'to', to?.path)

        // If coming back to this route from the form, refresh data
        if (
          to.path === '/dict-dashboard/combined-requests' &&
          from?.path?.includes('/both-service-form/')
        ) {
          console.log('DictRequestList: Detected return from form - refreshing data')
          this.fetchRequests()
        }
      }
    },
    computed: {
      filteredRequests() {
        // Ensure requests is always an array
        if (!Array.isArray(this.requests)) {
          console.warn('DictRequestList: requests is not an array:', this.requests)
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

        // Filter by status - handle special 'pending' filter and exact database status
        if (this.statusFilter) {
          if (this.statusFilter === 'pending') {
            // Show all pending requests (waiting for ICT Director approval)
            filtered = filtered.filter((request) => this.isPendingStatus(request.status))
          } else {
            // Filter by exact status
            filtered = filtered.filter((request) => request.status === this.statusFilter)
          }
        }

        // FIFO Sorting: Pending requests first (by submission/approval date), then approved requests
        return filtered.sort((a, b) => {
          // Priority 1: Pending requests should come first
          const aPending = this.isPendingStatus(a.status)
          const bPending = this.isPendingStatus(b.status)

          if (aPending && !bPending) return -1 // a comes first
          if (!aPending && bPending) return 1 // b comes first

          // Priority 2: Within same category (pending/approved), use FIFO based on relevant date
          const getRelevantDate = (request) => {
            // For pending requests, use divisional approval date (when they entered the queue)
            if (this.isPendingStatus(request.status)) {
              return new Date(request.divisional_approved_at || request.created_at || 0)
            }
            // For approved/processed requests, use ICT director approval date
            return new Date(request.ict_director_approved_at || request.updated_at || 0)
          }

          const dateA = getRelevantDate(a)
          const dateB = getRelevantDate(b)

          // FIFO: older dates first (earliest submitted/approved first)
          return dateA - dateB
        })
      }
    },
    async mounted() {
      try {
        console.log('DictRequestList: Component mounted, initializing...')
        await this.fetchRequests()

        // Add window focus listener to refresh data when user returns from approval form
        window.addEventListener('focus', this.onWindowFocus)

        // Add click listener to close dropdowns when clicking outside
        document.addEventListener('click', this.closeAllDropdowns)

        console.log('DictRequestList: Component initialized successfully')
      } catch (error) {
        console.error('DictRequestList: Error during mount:', error)
        this.error = 'Failed to initialize component: ' + error.message
        this.isLoading = false
      }
    },

    // Add Vue 3 router navigation guard to refresh data when returning to this route
    async beforeRouteEnter(to, from, next) {
      // This runs before the component is created
      console.log('DictRequestList: Entering route from:', from?.path || 'initial')

      // If coming from form approval, we want to refresh data after component is mounted
      if (from?.path?.includes('/both-service-form/')) {
        next((vm) => {
          console.log('DictRequestList: Refreshing data after return from form')
          vm.fetchRequests()
        })
      } else {
        next()
      }
    },

    async beforeRouteUpdate(to, from, next) {
      // This runs when the route is updated but the same component is reused
      console.log('DictRequestList: Route updated from', from?.path, 'to', to?.path)

      if (to.meta?.refreshOnEnter || from?.path?.includes('/both-service-form/')) {
        await this.fetchRequests()
      }

      next()
    },

    async activated() {
      // This runs when the component is activated (keep-alive) or re-entered
      console.log('DictRequestList: Component activated - refreshing data')
      await this.fetchRequests()
    },

    beforeUnmount() {
      // Clean up event listeners
      window.removeEventListener('focus', this.onWindowFocus)
      document.removeEventListener('click', this.closeAllDropdowns)
    },
    methods: {
      async fetchRequests() {
        this.isLoading = true
        this.error = null

        try {
          console.log('Fetching combined access requests for ICT Director (pending + approved)...')

          const response = await dictAccessService.getDictRequests({
            search: this.searchQuery || undefined,
            status: this.statusFilter || undefined,
            per_page: 50,
            include_processed: true, // Include both pending and already processed by ICT Director
            show_all_ict_director_requests: true // Show all requests relevant to ICT Director
          })

          if (response.success) {
            // Normalize response to array regardless of pagination shape
            const raw = response.data
            let items = []
            if (Array.isArray(raw)) {
              items = raw
            } else if (raw && Array.isArray(raw.data)) {
              items = raw.data
            } else if (raw && raw.data && Array.isArray(raw.data.data)) {
              // Some APIs nest as { data: { data: [...] } }
              items = raw.data.data
            }
            this.requests = items
            console.log('Combined access requests loaded:', this.requests.length)
            console.log('Raw response data:', response.data)

            // Push page-level badge override for DICT route in sidebar
            try {
              const pendingCount = (items || []).filter((r) => {
                const st = String(r.status || '').toLowerCase()
                const dictSt = String(r.ict_director_status || r.dict_status || '').toLowerCase()
                return st === 'pending' || dictSt === 'pending'
              }).length
              if (window.sidebarInstance?.setNotificationCount) {
                window.sidebarInstance.setNotificationCount(
                  '/dict-dashboard/combined-requests',
                  pendingCount
                )
                console.log('🔔 DictRequestList: Set sidebar pending badge to', pendingCount)
              }
              // Also expose a generic page override used by ModernSidebar fallback
              window.accessRequestsPendingCount = pendingCount
            } catch (e) {
              console.warn('DictRequestList: Failed to set sidebar badge override', e)
            }

            // Initialize auto-retry on freshly loaded list
            this.$nextTick(() => this.initAutoRetryForList())

            // Also fetch statistics
            await this.fetchStatistics()
          } else {
            throw new Error(response.error || 'Failed to fetch requests')
          }
        } catch (error) {
          console.error('Error fetching requests:', error)
          this.error =
            'Unable to load combined access requests. Please check your connection and try again.'
          this.requests = []
          this.calculateStats()
        } finally {
          this.isLoading = false
        }
      },

      async fetchStatistics() {
        try {
          const response = await dictAccessService.getDictStatistics()

          if (response.success && response.data) {
            // Map backend keys (ict director) to UI cards explicitly
            const d = response.data || {}
            this.stats = {
              pendingDict: Number(d.pendingDict ?? d.pending ?? 0),
              dictApproved: Number(d.dictApproved ?? 0),
              dictRejected: Number(d.dictRejected ?? 0),
              total: Number(d.total ?? 0)
            }

            // If backend returned zeros but we have data loaded, fallback to local calculation
            const sum =
              (this.stats.pendingDict || 0) +
              (this.stats.dictApproved || 0) +
              (this.stats.dictRejected || 0) +
              (this.stats.total || 0)
            if (sum === 0 && Array.isArray(this.requests) && this.requests.length > 0) {
              this.calculateStats()
            }
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
          // ICT Director perspective uses mapped statuses: 'pending' | 'approved' | 'rejected'
          pendingDict: requests.filter((r) => r.status === 'pending').length,
          dictApproved: requests.filter(
            (r) => r.status === 'approved' || r.ict_director_status === 'approved'
          ).length,
          dictRejected: requests.filter(
            (r) => r.status === 'rejected' || r.ict_director_status === 'rejected'
          ).length,
          total: requests.length
        }
      },

      async refreshRequests() {
        await this.fetchRequests()
      },

      viewAndProcessRequest(requestId) {
        // Navigate to ICT Director-specific both-service-form review route
        this.$router.push(`/dict-dashboard/both-service-form/${requestId}`)
      },

      viewApprovedRequest(requestId) {
        // Navigate to view approved request (read-only mode)
        this.$router.push(`/dict-dashboard/both-service-form/${requestId}`)
      },

      editRequest(requestId) {
        // Navigate to edit mode (same as Divisional Director)
        this.$router.push(`/both-service-form/${requestId}/edit`)
      },

      async cancelRequest(requestId) {
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

          const response = await dictAccessService.cancelRequest(requestId, reason)

          if (response.success) {
            // Update local state
            const requestIndex = this.requests.findIndex((r) => r.id === requestId)
            if (requestIndex !== -1) {
              this.requests[requestIndex].dict_status = 'cancelled'
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
        // ICT Director can edit requests that are Divisional Director approved and pending ICT Director approval
        return request.status === 'divisional_approved'
      },

      canCancel(request) {
        // ICT Director can cancel requests that are not in final states
        const finalStatuses = ['dict_rejected', 'cancelled', 'approved', 'implemented', 'completed']
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
        return this.$statusUtils.getStatusBadgeClass(status, 'DictRequestList')
      },

      getStatusIcon(status) {
        return this.$statusUtils.getStatusIcon(status)
      },

      getStatusText(status) {
        // Use centralized status utility with component name for debugging
        return this.$statusUtils.getStatusText(status, 'DictRequestList')
      },

      isPendingStatus(status) {
        // ICT Director perspective (controller maps ict_director_status to 'pending')
        return status === 'pending'
      },

      /**
       * Handle request approval - updates local state to prevent disappearing
       * @param {number} requestId - The request ID
       * @param {Object} approvalData - Approval data
       */
      async handleRequestApproval(requestId, approvalData) {
        try {
          console.log('Approving request:', requestId, approvalData)

          // Call the approval API
          const response = await dictAccessService.approveRequest(requestId, approvalData)

          if (response.success) {
            // Update local state to reflect approval without removing from list
            const requestIndex = this.requests.findIndex((r) => r.id === requestId)
            if (requestIndex !== -1) {
              // Update the status to approved but keep in list - use multiple possible approved statuses
              this.requests[requestIndex].status = 'dict_approved' // Main ICT Director approved status
              this.requests[requestIndex].dict_status = 'approved'
              this.requests[requestIndex].ict_director_approved_at = new Date().toISOString()
              this.requests[requestIndex].ict_director_comments = approvalData.comments || ''
              this.requests[requestIndex].ict_director_name = approvalData.name || ''

              console.log('✅ Request locally updated to approved:', {
                id: requestId,
                newStatus: this.requests[requestIndex].status,
                dictStatus: this.requests[requestIndex].dict_status
              })

              // Trigger reactivity
              this.$forceUpdate()
            }

            // Update statistics
            this.calculateStats()

            alert('Request approved successfully!')

            // Optionally refresh data to ensure consistency
            await this.fetchRequests()
          } else {
            throw new Error(response.error || 'Failed to approve request')
          }
        } catch (error) {
          console.error('Error approving request:', error)
          alert('Error approving request: ' + error.message)
        }
      },

      /**
       * Handle request rejection - updates local state
       * @param {number} requestId - The request ID
       * @param {Object} rejectionData - Rejection data
       */
      async handleRequestRejection(requestId, rejectionData) {
        try {
          console.log('Rejecting request:', requestId, rejectionData)

          // Call the rejection API
          const response = await dictAccessService.rejectRequest(requestId, rejectionData)

          if (response.success) {
            // Update local state to reflect rejection
            const requestIndex = this.requests.findIndex((r) => r.id === requestId)
            if (requestIndex !== -1) {
              this.requests[requestIndex].status = 'dict_rejected' // Main ICT Director rejected status
              this.requests[requestIndex].dict_status = 'rejected'
              this.requests[requestIndex].ict_director_rejected_at = new Date().toISOString()
              this.requests[requestIndex].ict_director_comments = rejectionData.comments || ''
              this.requests[requestIndex].ict_director_name = rejectionData.name || ''

              console.log('❌ Request locally updated to rejected:', {
                id: requestId,
                newStatus: this.requests[requestIndex].status,
                dictStatus: this.requests[requestIndex].dict_status
              })

              // Trigger reactivity
              this.$forceUpdate()
            }

            // Update statistics
            this.calculateStats()

            alert('Request rejected successfully!')

            // Optionally refresh data to ensure consistency
            await this.fetchRequests()
          } else {
            throw new Error(response.error || 'Failed to reject request')
          }
        } catch (error) {
          console.error('Error rejecting request:', error)
          alert('Error rejecting request: ' + error.message)
        }
      },

      /**
       * Handle window focus event - refresh data when user returns to the list
       * This ensures that if user approves/rejects a request and comes back,
       * the list shows updated status
       */
      onWindowFocus() {
        // Only refresh if the component is currently active, not loading, and on the right route
        const isOnDictRequestsPage = this.$route?.path === '/dict-dashboard/combined-requests'

        if (!this.isLoading && !document.hidden && isOnDictRequestsPage) {
          console.log('Window focused on dict requests page - refreshing data')
          // Use a small delay to ensure any backend updates are processed
          setTimeout(() => {
            this.fetchRequests()
          }, 500)
        }
      },

      // Dropdown management methods
      toggleDropdown(requestId) {
        console.log('Toggle dropdown for request:', requestId)
        this.openDropdownId = this.openDropdownId === requestId ? null : requestId

        // Add a small delay to ensure the dropdown is positioned correctly
        if (this.openDropdownId === requestId) {
          this.$nextTick(() => {
            this.positionDropdown(requestId)
            console.log('Dropdown opened for request:', requestId)
          })
        }
      },

      positionDropdown(requestId) {
        // Find the button element for this request
        const button = document.querySelector(`[aria-label*='${requestId}']`)
        if (!button) return

        const rect = button.getBoundingClientRect()
        const viewportHeight = window.innerHeight
        const dropdownHeight = 200 // Approximate dropdown height
        const spaceBelow = viewportHeight - rect.bottom

        // Store position info
        if (!this.dropdownPositions) {
          this.dropdownPositions = {}
        }

        // Calculate position relative to viewport
        const position = spaceBelow < dropdownHeight + 20 ? 'top' : 'bottom'
        this.dropdownPositions[requestId] = {
          position,
          top: position === 'bottom' ? rect.bottom + 4 : rect.top - dropdownHeight - 4,
          left: rect.right - 192 // 192px = 12rem (w-48)
        }
        this.$forceUpdate()
      },

      getDropdownStyle(requestId) {
        const pos = this.dropdownPositions?.[requestId]
        if (!pos) return { zIndex: 99999 }

        return {
          top: `${pos.top}px`,
          left: `${pos.left}px`,
          zIndex: 99999,
          boxShadow: '0 10px 25px rgba(0, 0, 0, 0.15), 0 4px 6px rgba(0, 0, 0, 0.1)'
        }
      },

      closeAllDropdowns(event) {
        // Only close if clicking outside the dropdown
        if (!event || !event.target.closest('.three-dot-menu')) {
          console.log('Closing all dropdowns')
          this.openDropdownId = null
        }
      },

      // Role-specific actions logic
      getAvailableActions(request) {
        const actions = []
        const userRole = this.userRole
        const status = request.status

        // Define role mappings
        const ROLES = {
          HEAD_OF_IT: 'head_of_it',
          ICT_OFFICER: 'ict_officer',
          ICT_DIRECTOR: 'ict_director',
          DIVISIONAL_DIRECTOR: 'divisional_director',
          HEAD_OF_DEPARTMENT: 'hod',
          STAFF: 'staff'
        }

        // Head of IT actions
        if (userRole === ROLES.HEAD_OF_IT) {
          if (['dict_approved', 'ict_director_approved', 'approved'].includes(status)) {
            actions.push({
              key: 'assign_task',
              label: 'Assign Task',
              icon: 'fas fa-user-plus'
            })
            actions.push({
              key: 'cancel_task',
              label: 'Cancel Task',
              icon: 'fas fa-times-circle'
            })
          }
          actions.push({
            key: 'view_timeline',
            label: 'View Timeline',
            icon: 'fas fa-history'
          })
        }
        // ICT Officer actions
        else if (userRole === ROLES.ICT_OFFICER) {
          // Only show for requests assigned to this officer
          if (['assigned', 'in_progress', 'head_it_approved'].includes(status)) {
            actions.push({
              key: 'update_progress',
              label: 'Update Progress',
              icon: 'fas fa-edit'
            })
          }
          actions.push({
            key: 'view_timeline',
            label: 'View Timeline',
            icon: 'fas fa-history'
          })
        }
        // ICT Director actions (current default for this page)
        else if (userRole === ROLES.ICT_DIRECTOR) {
          // Check if already approved by ICT Director
          const isApproved = [
            'ict_director_approved',
            'dict_approved',
            'approved',
            'head_it_approved',
            'assigned',
            'in_progress',
            'implemented',
            'completed'
          ].includes(status)

          if (isApproved) {
            // Show view and timeline for approved requests
            actions.push({
              key: 'view_approved_request',
              label: 'View Approved Request',
              icon: 'fas fa-eye',
              color: 'blue'
            })
            actions.push({
              key: 'view_timeline',
              label: 'View Timeline',
              icon: 'fas fa-history',
              color: 'gray'
            })
          } else {
            // Show approve and cancel for pending requests
            actions.push({
              key: 'view_and_process',
              label: 'Approve',
              icon: 'fas fa-check-circle',
              color: 'green'
            })
            if (this.canEdit(request)) {
              actions.push({
                key: 'edit_request',
                label: 'Edit Request',
                icon: 'fas fa-edit',
                color: 'blue'
              })
            }
            if (this.canCancel(request)) {
              actions.push({
                key: 'cancel_request',
                label: 'Cancel Request',
                icon: 'fas fa-times',
                color: 'red'
              })
            }
            actions.push({
              key: 'view_timeline',
              label: 'View Timeline',
              icon: 'fas fa-history',
              color: 'gray'
            })
          }
        }
        // Other roles (Divisional Director, HOD, Requester)
        else {
          actions.push({
            key: 'view_and_process',
            label: 'Approve',
            icon: 'fas fa-check-circle',
            color: 'green'
          })
          actions.push({
            key: 'view_timeline',
            label: 'View Timeline',
            icon: 'fas fa-history'
          })
        }

        return actions
      },

      // Execute action based on key
      executeAction(actionKey, request) {
        this.closeAllDropdowns()

        switch (actionKey) {
          case 'assign_task':
            this.assignTask(request)
            break
          case 'cancel_task':
            this.cancelTask(request)
            break
          case 'update_progress':
            this.updateProgress(request)
            break
          case 'view_timeline':
            this.viewTimeline(request)
            break
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
          default:
            console.warn('Unknown action:', actionKey)
        }
      },

      // Helper methods for button styling
      getActionColorClass(action) {
        const colorMap = {
          green: 'text-green-600 hover:text-green-700 hover:bg-green-50',
          red: 'text-red-600 hover:text-red-700 hover:bg-red-50',
          blue: 'text-blue-600 hover:text-blue-700 hover:bg-blue-50',
          gray: 'text-gray-600 hover:text-gray-700'
        }
        return colorMap[action.color] || 'text-gray-700 hover:text-gray-900'
      },

      getActionIconColorClass(action) {
        const iconColorMap = {
          green: 'text-green-500 group-hover:text-green-600',
          red: 'text-red-500 group-hover:text-red-600',
          blue: 'text-blue-500 group-hover:text-blue-600',
          gray: 'text-gray-400 group-hover:text-gray-600'
        }
        return iconColorMap[action.color] || 'text-gray-400 group-hover:text-gray-600'
      },

      // New action methods
      assignTask(request) {
        console.log('Assigning task for request:', request.id)
        // Navigate to ICT Officer selection page
        this.$router.push(`/head_of_it-dashboard/select-ict-officer/${request.id}`)
      },

      cancelTask(request) {
        console.log('Cancelling task for request:', request.id)
        // Similar to existing cancelRequest but for Head of IT
        this.cancelRequest(request.id)
      },

      updateProgress(request) {
        console.log('Updating progress for request:', request.id)
        // This would typically open a modal or navigate to an update form
        alert(
          'Update Progress functionality would be implemented here. This might open a modal or dedicated page.'
        )
      },

      viewTimeline(request) {
        console.log('📅 DictRequestList: Opening timeline for request:', request.id)
        this.selectedRequestId = request.id
        this.showTimeline = true
      },

      closeTimeline() {
        console.log('📅 DictRequestList: Closing timeline modal')
        this.showTimeline = false
        this.selectedRequestId = null
      },

      async handleTimelineUpdate() {
        console.log('🔄 DictRequestList: Timeline updated, refreshing requests list...')
        await this.fetchRequests()
        this.initAutoRetryForList()
      },

      // Helper method to determine if separator should be shown
      shouldShowSeparator(currentAction, previousAction) {
        // Add separator before destructive actions or different action groups
        const destructiveActions = ['cancel_task', 'cancel_request']
        const viewActions = ['view_timeline', 'view_and_process']

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

      // SMS retry helpers (ICT Director -> Head of IT)
      getRetryAttempts(id) {
        return this.retryAttempts[id] || 0
      },
      isRetrying(id) {
        return !!this.retryTimers[id]
      },
      async retrySendSms(request) {
        if (!request) return
        // Do not attempt resend if feature is disabled
        if (!notificationService.isResendEnabled || !notificationService.isResendEnabled()) {
          console.warn('DictRequestList: SMS resend disabled by feature flag; skipping retry loop')
          return
        }
        const id = request.id
        if (!this.retryAttempts[id]) this.retryAttempts[id] = 0
        if (this.isRetrying(id)) return
        try {
          this.retryTimers[id] = setTimeout(() => {}, 0)
          await notificationService.resendSmsGeneric({
            requestId: id,
            role: 'ict_director',
            target: 'head_of_it'
          })
          setTimeout(async () => {
            await this.fetchRequests()
            clearTimeout(this.retryTimers[id])
            delete this.retryTimers[id]
            const status = this.getRelevantSmsStatus(
              (this.requests || []).find((r) => r.id === id) || request
            )
            // Stop retrying if SMS was sent or delivered successfully
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
        // Do not start auto-retry scheduler if resend is disabled
        if (!notificationService.isResendEnabled || !notificationService.isResendEnabled()) {
          console.log('DictRequestList: Auto-retry disabled (resend feature off)')
          return
        }
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
        // For ICT Director: show SMS status for NEXT workflow step after their approval
        const status = request.ict_director_status || request.status

        console.log('🔍 DICT SMS Status Check:', {
          requestId: request.id,
          status: status,
          ict_director_status: request.ict_director_status,
          sms_to_head_it_status: request.sms_to_head_it_status,
          sms_to_requester_status: request.sms_to_requester_status
        })

        // If status is rejected or cancelled: show the requester notification status
        if (
          ['rejected', 'hod_rejected', 'divisional_rejected', 'dict_rejected', 'dict_cancelled', 'cancelled'].includes(
            status
          )
        ) {
          console.log('❌ Request rejected/cancelled - showing requester SMS status')
          return request.sms_to_requester_status || 'pending'
        }

        // If ICT Director has APPROVED: show Head of IT notification status
        if (
          status === 'ict_director_approved' ||
          status === 'dict_approved' ||
          status === 'approved' ||
          status === 'head_it_approved' ||
          status === 'implemented'
        ) {
          console.log('✅ DICT approved - showing Head of IT SMS status')
          return request.sms_to_head_it_status || 'pending'
        }

        // If PENDING ICT Director approval: return 'pending' (no action notification sent yet)
        console.log('⏳ Pending DICT action - returning pending')
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
  .sidebar-narrow {
    /* Force the sidebar to be a bit wider */
    max-width: 280px;
  }

  /* Override the sidebar width classes for this component */
  .sidebar-narrow :deep(.sidebar-expanded) {
    width: 280px !important;
  }

  .sidebar-narrow :deep(.sidebar-collapsed) {
    width: 64px !important;
  }

  /* Three-dot menu enhancements */
  .three-dot-menu {
    position: relative;
  }

  /* Dropdown menu styles (now rendered via Teleport at body level) */
  .dropdown-menu {
    z-index: 99999 !important;
    min-width: 12rem;
    max-width: 16rem;
  }

  /* Fix for table cell positioning context */
  .three-dot-menu {
    position: relative;
    z-index: 1;
  }

  .three-dot-menu .dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    transform: translateX(0);
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

  /* Make sure table doesn't clip dropdown */
  table {
    position: relative;
    z-index: 1;
  }

  /* Ensure table container allows dropdown overflow */
  .bg-white\/10.rounded-lg {
    overflow: visible !important;
  }

  /* Ensure tbody allows overflow */
  tbody {
    position: relative;
  }

  /* Ensure table rows allow overflow */
  tbody tr {
    position: relative;
  }

  /* Ensure Actions column allows overflow */
  td:last-child {
    overflow: visible !important;
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

  /* Ensure main content doesn't block header dropdowns */
  main {
    position: relative;
    z-index: 1;
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
