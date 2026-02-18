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
              @click="fetchAccessRequests"
              class="mt-2 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-base font-medium"
            >
              Retry
            </button>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-green-600/25 border border-green-400/40 p-4 rounded-lg">
              <h3 class="text-green-200 text-base font-semibold">Available for Assignment</h3>
              <p class="text-white text-3xl font-bold">{{ stats.unassigned }}</p>
            </div>
            <div class="bg-blue-600/25 border border-blue-400/40 p-4 rounded-lg">
              <h3 class="text-blue-200 text-base font-semibold">Assigned to ICT</h3>
              <p class="text-white text-3xl font-bold">{{ stats.assigned }}</p>
            </div>
            <div class="bg-purple-600/25 border border-purple-400/40 p-4 rounded-lg">
              <h3 class="text-purple-200 text-base font-semibold">In Progress</h3>
              <p class="text-white text-3xl font-bold">{{ stats.inProgress }}</p>
            </div>
            <div class="bg-teal-600/25 border border-teal-400/40 p-4 rounded-lg">
              <h3 class="text-teal-200 text-base font-semibold">Total Requests</h3>
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
                class="flex-1 px-3 py-2 bg-white/20 border border-blue-300/30 rounded text-white placeholder-blue-200/60 text-sm"
              />
              <select
                v-model="statusFilter"
                class="px-3 py-2 bg-white/20 border border-blue-300/30 rounded text-white text-sm status-select"
              >
                <option value="">All Requests</option>
                <option value="unassigned">Available for Assignment</option>
                <option value="assigned_to_ict">Assigned to ICT</option>
                <option value="implementation_in_progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
              <button
                @click="refreshRequests"
                class="px-6 py-2 bg-teal-600 text-white rounded hover:bg-teal-700 text-sm font-medium"
              >
                Refresh
              </button>
            </div>
          </div>

          <!-- Requests Table -->
          <div class="bg-white/10 rounded-lg">
            <div class="overflow-x-auto" style="overflow-y: visible">
              <table class="w-full">
                <thead class="bg-blue-800/50">
                  <tr>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-semibold">
                      Request ID
                    </th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-semibold">
                      Request Type
                    </th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-semibold">
                      Requested Modules
                    </th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-semibold">
                      Requester Name & PF Number
                    </th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-semibold">
                      Head of IT Approval Date
                    </th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-semibold">Status</th>
                    <th class="px-4 py-3 text-left text-blue-100 text-sm font-semibold">
                      SMS Status
                    </th>
                    <th class="px-4 py-3 text-center text-blue-100 text-sm font-semibold">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="request in filteredRequests"
                    :key="request.id"
                    :class="[
                      'border-t border-blue-300/20 hover:bg-blue-700/30 transition-colors duration-200',
                      isUnassigned(request.status)
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
                            v-if="isUnassigned(request.status)"
                            class="fas fa-clock text-yellow-400 text-base"
                            title="Available for assignment"
                          ></i>
                          <i
                            v-else
                            class="fas fa-check-circle text-green-400 text-base"
                            title="Assigned or in progress"
                          ></i>
                        </div>
                        <div>
                          <div class="text-white font-medium text-sm">
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
                      <div class="text-white font-medium text-sm">
                        {{ getRequestType(request) }}
                      </div>
                      <div class="text-blue-300 text-xs">
                        {{ request.department_name || request.department || 'Unknown Dept' }}
                      </div>
                    </td>

                    <!-- Requested Modules -->
                    <td class="px-4 py-3">
                      <div class="flex flex-nowrap gap-1">
                        <span
                          v-if="hasService(request, 'jeeva')"
                          class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 whitespace-nowrap"
                        >
                          Jeeva
                        </span>
                        <span
                          v-if="hasService(request, 'wellsoft')"
                          class="px-2 py-1 rounded text-xs bg-green-100 text-green-800 whitespace-nowrap"
                        >
                          Wellsoft
                        </span>
                        <span
                          v-if="hasService(request, 'internet')"
                          class="px-2 py-1 rounded text-xs bg-cyan-100 text-cyan-800 whitespace-nowrap"
                        >
                          Internet
                        </span>
                      </div>
                    </td>

                    <!-- Requester Name & PF Number -->
                    <td class="px-4 py-3">
                      <div class="text-white font-medium text-sm">
                        {{ request.staff_name || request.full_name || 'Unknown User' }}
                      </div>
                      <div class="text-blue-300 text-xs">
                        {{ request.phone || request.phone_number || 'No phone' }}
                      </div>
                      <div v-if="request.pf_number" class="text-teal-300 text-xs">
                        PF: {{ request.pf_number }}
                      </div>
                    </td>

                    <!-- Head of IT Approval Date -->
                    <td class="px-4 py-3">
                      <div class="text-white font-medium text-sm">
                        {{
                          formatDate(
                            request.head_of_it_approval_date || request.head_of_it_approved_at
                          )
                        }}
                      </div>
                      <div class="text-blue-300 text-xs">
                        {{
                          formatTime(
                            request.head_of_it_approval_date || request.head_of_it_approved_at
                          )
                        }}
                      </div>
                    </td>

                    <!-- Status -->
                    <td class="px-4 py-3">
                      <div class="flex flex-col">
                        <span
                          :class="getStatusBadgeClass(request.status)"
                          class="rounded text-sm font-medium inline-block whitespace-nowrap"
                          :style="{ padding: '6px 12px', width: 'fit-content' }"
                        >
                          {{ getStatusText(request.status) }}
                        </span>
                      </div>
                    </td>

                    <!-- SMS Status -->
                    <td class="px-4 py-3">
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
                        <!-- Retry button shown for failed or pending -->
                        <button
                          v-if="
                            ['failed', 'pending'].includes(getRelevantSmsStatus(request)) &&
                            ['implemented', 'completed'].includes(request.status)
                          "
                          @click="retrySendSms(request)"
                          :disabled="isRetrying(request.id)"
                          class="ml-2 px-2 py-1 text-xs rounded border border-blue-300/50 text-blue-100 hover:bg-blue-700/40 disabled:opacity-50"
                          title="Retry sending SMS to requester"
                        >
                          <span v-if="!isRetrying(request.id)">Retry</span>
                          <span v-else> <i class="fas fa-spinner fa-spin mr-1"></i>Retrying </span>
                        </button>
                        <!-- Attempts badge -->
                        <span
                          v-if="getRetryAttempts(request.id) > 0"
                          class="text-xs text-blue-200 ml-1"
                        >
                          ({{ getRetryAttempts(request.id) }})
                        </span>
                      </div>
                    </td>

                    <!-- Actions -->
                    <td class="px-4 py-3 text-center" style="overflow: visible">
                      <div class="relative inline-block" style="overflow: visible">
                        <!-- Three-dot menu button -->
                        <button
                          @click="toggleDropdown(request.id, $event, request)"
                          class="w-8 h-8 flex items-center justify-center text-white hover:bg-white/10 rounded-full transition-colors duration-200"
                          :title="
                            'Actions for ' +
                            (request.request_id || 'REQ-' + request.id.toString().padStart(6, '0'))
                          "
                        >
                          <i class="fas fa-ellipsis-v text-sm"></i>
                        </button>
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
                    : 'No access requests approved by Head of IT are currently available.'
                }}
              </p>
            </div>

            <!-- Pagination -->
            <div v-if="filteredRequests.length > 0" class="px-4 py-3 border-t border-blue-300/30">
              <div class="text-blue-300 text-base">
                Showing {{ filteredRequests.length }} of {{ pagination.total }} requests
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Dropdown menu portal (teleported to body to avoid all overflow issues) -->
    <Teleport to="body">
      <div
        v-if="activeDropdown !== null"
        class="fixed w-56 bg-white rounded-lg shadow-2xl border border-gray-200 py-1"
        :style="getDropdownPosition()"
        style="z-index: 99999"
        @click.stop
      >
        <!-- Conditional actions based on status -->
        <template v-if="selectedRequest && isUnassigned(selectedRequest.status)">
          <!-- Available for Assignment - show Approve and View Timeline -->
          <button
            @click="handleMenuAction('viewRequest', selectedRequest)"
            class="w-full text-left px-4 py-3 text-sm bg-green-50 text-green-800 border-b border-green-200 hover:bg-green-100 focus:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-inset transition-all duration-200 flex items-center group first:rounded-t-lg"
          >
            <i
              class="fas fa-check mr-3 text-green-600 group-hover:text-green-700 group-focus:text-green-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold text-green-800">Approve</span>
          </button>

          <button
            @click="handleMenuAction('viewTimeline', selectedRequest)"
            class="w-full text-left px-4 py-3 text-sm bg-indigo-50 text-indigo-800 border-b border-indigo-200 hover:bg-indigo-100 focus:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset transition-all duration-200 flex items-center group"
          >
            <i
              class="fas fa-history mr-3 text-indigo-600 group-hover:text-indigo-700 group-focus:text-indigo-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold">View Timeline</span>
          </button>

          <button
            @click="handleMenuAction('downloadPdf', selectedRequest)"
            class="w-full text-left px-4 py-3 text-sm bg-rose-50 text-rose-800 hover:bg-rose-100 focus:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-inset transition-all duration-200 flex items-center group last:rounded-b-lg"
          >
            <i
              class="fas fa-file-pdf mr-3 text-rose-600 group-hover:text-rose-700 group-focus:text-rose-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold">Download PDF</span>
          </button>
        </template>

        <template v-else-if="selectedRequest && selectedRequest.status === 'assigned_to_ict'">
          <!-- Assigned to ICT - show Approve and View Timeline -->
          <button
            @click="handleMenuAction('viewRequest', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-green-50 text-green-800 border-b border-green-200 hover:bg-green-100 focus:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-inset transition-all duration-200 flex items-center group first:rounded-t-lg"
          >
            <i
              class="fas fa-check mr-3 text-green-600 group-hover:text-green-700 group-focus:text-green-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold text-green-800">Approve</span>
          </button>

          <button
            @click="handleMenuAction('viewTimeline', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-indigo-50 text-indigo-800 border-b border-indigo-200 hover:bg-indigo-100 focus:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset transition-all duration-200 flex items-center group"
          >
            <i
              class="fas fa-history mr-3 text-indigo-600 group-hover:text-indigo-700 group-focus:text-indigo-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold">View Timeline</span>
          </button>

          <button
            @click="handleMenuAction('downloadPdf', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-rose-50 text-rose-800 hover:bg-rose-100 focus:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-inset transition-all duration-200 flex items-center group last:rounded-b-lg"
          >
            <i
              class="fas fa-file-pdf mr-3 text-rose-600 group-hover:text-rose-700 group-focus:text-rose-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold">Download PDF</span>
          </button>
        </template>

        <template
          v-else-if="selectedRequest && selectedRequest.status === 'implementation_in_progress'"
        >
          <!-- Implementation in progress - show Approve and View Timeline -->
          <button
            @click="handleMenuAction('viewRequest', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-green-50 text-green-800 border-b border-green-200 hover:bg-green-100 focus:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-inset transition-all duration-200 flex items-center group first:rounded-t-lg"
          >
            <i
              class="fas fa-check mr-3 text-green-600 group-hover:text-green-700 group-focus:text-green-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold text-green-800">Approve</span>
          </button>

          <button
            @click="handleMenuAction('viewTimeline', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-indigo-50 text-indigo-800 border-b border-indigo-200 hover:bg-indigo-100 focus:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset transition-all duration-200 flex items-center group"
          >
            <i
              class="fas fa-history mr-3 text-indigo-600 group-hover:text-indigo-700 group-focus:text-indigo-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold">View Timeline</span>
          </button>

          <button
            @click="handleMenuAction('downloadPdf', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-rose-50 text-rose-800 hover:bg-rose-100 focus:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-inset transition-all duration-200 flex items-center group last:rounded-b-lg"
          >
            <i
              class="fas fa-file-pdf mr-3 text-rose-600 group-hover:text-rose-700 group-focus:text-rose-700 transition-colors duration-200 text-lg"
            ></i>
            <span class="font-semibold">Download PDF</span>
          </button>
        </template>

        <template
          v-else-if="
            selectedRequest &&
            (selectedRequest.status === 'completed' || selectedRequest.status === 'implemented')
          "
        >
          <!-- Completed/Implemented - show View Request (read-only) and View Timeline -->
          <button
            @click="handleMenuAction('viewRequest', selectedRequest)"
            class="w-full text-left px-4 py-3 text-sm bg-blue-50 text-blue-800 border-b border-blue-200 hover:bg-blue-100 focus:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset transition-all duration-200 flex items-center group first:rounded-t-lg"
          >
            <i
              class="fas fa-eye mr-3 text-blue-600 group-hover:text-blue-700 group-focus:text-blue-700 transition-colors duration-200"
            ></i>
            <span class="font-semibold text-blue-800">View Request</span>
          </button>

          <button
            @click="handleMenuAction('viewTimeline', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-indigo-50 text-indigo-800 border-b border-indigo-200 hover:bg-indigo-100 focus:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset transition-all duration-200 flex items-center group"
          >
            <i
              class="fas fa-history mr-3 text-indigo-600 group-hover:text-indigo-700 group-focus:text-indigo-700 transition-colors duration-200"
            ></i>
            <span class="font-semibold">View Timeline</span>
          </button>

          <button
            @click="handleMenuAction('downloadPdf', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-rose-50 text-rose-800 hover:bg-rose-100 focus:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-inset transition-all duration-200 flex items-center group last:rounded-b-lg"
          >
            <i
              class="fas fa-file-pdf mr-3 text-rose-600 group-hover:text-rose-700 group-focus:text-rose-700 transition-colors duration-200"
            ></i>
            <span class="font-semibold">Download PDF</span>
          </button>
        </template>

        <template v-else-if="selectedRequest">
          <!-- Fallback for cancelled or unknown statuses - show View Request and View Timeline -->
          <button
            @click="handleMenuAction('viewRequest', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-blue-50 text-blue-800 border-b border-blue-200 hover:bg-blue-100 focus:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-inset transition-all duration-200 flex items-center group first:rounded-t-lg"
          >
            <i
              class="fas fa-eye mr-3 text-blue-600 group-hover:text-blue-700 group-focus:text-blue-700 transition-colors duration-200"
            ></i>
            <span class="font-semibold text-blue-800">View Request</span>
          </button>

          <button
            @click="handleMenuAction('viewTimeline', selectedRequest)"
            class="w-full text-left px-4 py-3 text-lg bg-indigo-50 text-indigo-800 hover:bg-indigo-100 focus:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-inset transition-all duration-200 flex items-center group last:rounded-b-lg"
          >
            <i
              class="fas fa-history mr-3 text-indigo-600 group-hover:text-indigo-700 group-focus:text-indigo-700 transition-colors duration-200"
            ></i>
            <span class="font-semibold">View Timeline</span>
          </button>
        </template>
      </div>
    </Teleport>

    <AppFooter />

    <!-- PDF Download Progress Banner (dismissible safety) -->
    <div
      v-if="downloadingPdf"
      class="fixed inset-0 z-[100002] flex items-center justify-center bg-black/40"
      @click.self="downloadingPdf = false"
    >
      <div class="bg-white/90 rounded-xl shadow-2xl p-6 w-full max-w-md border border-blue-200">
        <div class="flex items-center gap-3 mb-3">
          <i class="fas fa-file-pdf text-rose-600 text-2xl"></i>
          <div class="text-lg font-semibold text-gray-800">
            Downloading PDF… {{ downloadPercent }}%
          </div>
        </div>
        <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-rose-500 to-blue-600"
            :style="{ width: downloadPercent + '%' }"
          ></div>
        </div>
        <div class="text-sm text-gray-700 mt-3 font-semibold">
          Please keep this tab open until the download completes.
        </div>
        <div class="mt-4 flex justify-end">
          <button
            type="button"
            class="px-3 py-1.5 text-sm rounded-md bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold"
            @click="downloadingPdf = false"
            title="Dismiss overlay"
          >
            Dismiss
          </button>
        </div>
      </div>
    </div>

    <!-- Centered MNH Loading Banner -->
    <div
      v-if="isLoading"
      class="absolute inset-0 z-40 flex items-center justify-center pointer-events-none animate-fade-in"
    >
      <div class="pointer-events-auto w-full max-w-lg mx-auto">
        <!-- Main Loading Card -->
        <div
          class="relative bg-gradient-to-br from-white/20 via-blue-50/30 to-white/10 border-2 border-blue-300/50 backdrop-blur-xl rounded-3xl p-8 shadow-2xl overflow-hidden"
        >
          <!-- Animated Background Pattern -->
          <div class="absolute inset-0 opacity-10">
            <div
              class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-slide-across"
            ></div>
            <div
              class="absolute top-4 left-4 w-16 h-16 rounded-full animate-float"
              style="background: rgba(255, 0, 0, 0.2)"
            ></div>
            <div
              class="absolute bottom-4 right-4 w-20 h-20 bg-blue-400/20 rounded-full animate-float"
              style="animation-delay: 1s"
            ></div>
            <div
              class="absolute top-1/2 left-8 w-12 h-12 rounded-full animate-float"
              style="background: rgba(255, 0, 0, 0.15); animation-delay: 2s"
            ></div>
          </div>

          <!-- Hospital Logos and Branding -->
          <div class="relative z-10">
            <div class="flex items-center justify-center gap-6 mb-6">
              <!-- Left Logo -->
              <div class="relative">
                <div
                  class="w-16 h-16 bg-gradient-to-br from-blue-500/30 to-teal-500/30 rounded-2xl backdrop-blur-sm border-2 border-blue-300/50 flex items-center justify-center shadow-xl hover:scale-105 transition-transform duration-300"
                >
                  <img
                    src="/assets/images/ngao2.png"
                    alt="National Shield"
                    class="max-w-12 max-h-12 object-contain drop-shadow-lg"
                  />
                </div>
                <div
                  class="absolute -inset-1 bg-gradient-to-r from-blue-400 to-teal-400 rounded-2xl opacity-20 blur-sm animate-pulse"
                ></div>
              </div>

              <!-- Center Loading Spinner -->
              <div class="relative flex flex-col items-center">
                <!-- Main Spinner -->
                <div class="relative mb-3">
                  <!-- Base circle -->
                  <div class="rounded-full h-12 w-12 border-2 border-gray-300/20"></div>
                  <!-- Red orbiting segment -->
                  <div
                    class="absolute inset-0 animate-spin rounded-full h-12 w-12 border-4 border-transparent shadow-lg"
                    style="border-top-color: #ff0000; animation-duration: 2s"
                  ></div>
                  <!-- Blue orbiting segment -->
                  <div
                    class="absolute inset-0 animate-spin rounded-full h-12 w-12 border-4 border-transparent shadow-lg"
                    style="
                      border-bottom-color: #0000d1;
                      animation-direction: reverse;
                      animation-duration: 1.5s;
                    "
                  ></div>
                  <!-- Additional red segment for more dynamic effect -->
                  <div
                    class="absolute inset-0 animate-spin rounded-full h-12 w-12 border-4 border-transparent shadow-lg"
                    style="border-right-color: rgba(255, 0, 0, 0.7); animation-duration: 2.5s"
                  ></div>
                  <!-- Additional blue segment -->
                  <div
                    class="absolute inset-0 animate-spin rounded-full h-12 w-12 border-4 border-transparent shadow-lg"
                    style="
                      border-left-color: rgba(0, 0, 209, 0.7);
                      animation-direction: reverse;
                      animation-duration: 1.8s;
                    "
                  ></div>
                </div>

                <!-- Loading Text -->
                <div class="text-center">
                  <div class="text-white text-xl font-bold tracking-wide mb-1 drop-shadow-md">
                    Loading Access Requests
                  </div>
                  <div class="text-blue-100 text-sm animate-pulse">Please wait...</div>
                </div>
              </div>

              <!-- Right Logo -->
              <div class="relative">
                <div
                  class="w-16 h-16 bg-gradient-to-br from-teal-500/30 to-blue-500/30 rounded-2xl backdrop-blur-sm border-2 border-teal-300/50 flex items-center justify-center shadow-xl hover:scale-105 transition-transform duration-300"
                >
                  <img
                    src="/assets/images/logo2.png"
                    alt="MNH Logo"
                    class="max-w-12 max-h-12 object-contain drop-shadow-lg"
                  />
                </div>
                <div
                  class="absolute -inset-1 bg-gradient-to-r from-teal-400 to-blue-400 rounded-2xl opacity-20 blur-sm animate-pulse"
                  style="animation-delay: 0.5s"
                ></div>
              </div>
            </div>

            <!-- Hospital Name and Department -->
            <div class="text-center border-t border-blue-300/30 pt-4">
              <div
                class="text-lg font-black tracking-widest mb-1 drop-shadow-sm"
                style="color: #ff0000"
              >
                MUHIMBILI NATIONAL HOSPITAL
              </div>
              <div class="text-teal-200 text-sm font-semibold tracking-wider">
                ICT ACCESS REQUESTS MANAGEMENT
              </div>
            </div>

            <!-- Progress Indicators -->
            <div class="flex justify-center gap-2 mt-4">
              <div class="w-2 h-2 rounded-full animate-bounce" style="background: #ff0000"></div>
              <div
                class="w-2 h-2 rounded-full animate-bounce"
                style="background: #0000d1; animation-delay: 0.1s"
              ></div>
              <div
                class="w-2 h-2 rounded-full animate-bounce"
                style="background: #ff0000; animation-delay: 0.2s"
              ></div>
              <div
                class="w-2 h-2 rounded-full animate-bounce"
                style="background: #0000d1; animation-delay: 0.3s"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline Modal -->
    <RequestTimeline
      :show="showTimeline"
      :request-id="selectedRequestId"
      @close="closeTimeline"
      @updated="handleTimelineUpdate"
      @openUpdateProgress="openUpdateProgress"
    />

    <!-- Update Progress Modal (separate from timeline) -->
    <div
      v-if="showUpdateProgress && selectedRequest"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[10001]"
      @click.self="closeUpdateProgress"
    >
      <div class="max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <UpdateProgress
          :show="showUpdateProgress"
          :request-id="selectedRequestId"
          :request-data="selectedRequest"
          @close="closeUpdateProgress"
          @updated="handleUpdateProgressUpdate"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
  import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
  import Header from '@/components/header.vue'
  import ModernSidebar from '@/components/ModernSidebar.vue'
  import AppFooter from '@/components/footer.vue'
  import RequestTimeline from '@/components/common/RequestTimeline.vue'
  import UpdateProgress from '@/components/common/UpdateProgress.vue'
  import { useAuth } from '@/composables/useAuth'
  import ictOfficerService from '@/services/ictOfficerService'
  import apiClient from '@/services/apiClient'

  // State
  const accessRequests = ref([])
  const searchQuery = ref('')
  const statusFilter = ref('')
  const isLoading = ref(false)
  const downloadingPdf = ref(false)
  const downloadPercent = ref(0)
  const stats = reactive({ unassigned: 0, assigned: 0, inProgress: 0, total: 0 })
  const error = ref(null)
  const pagination = reactive({ total: 0, currentPage: 1, perPage: 20 })

  // Timeline / dialogs
  const showTimeline = ref(false)
  const selectedRequestId = ref(null)
  const selectedRequest = ref(null)
  const showUpdateProgress = ref(false)

  // Dropdown
  const activeDropdown = ref(null)
  const dropdownPosition = ref(null)

  // Auth (called inside setup to avoid inject warnings)
  const { ROLES, requireRole, user, userRole, ensureInitialized } = useAuth()

  // Helpers
  const isUnassigned = (status) => status === 'head_of_it_approved' || !status

  const filteredRequests = computed(() => {
    if (!Array.isArray(accessRequests.value)) {
      console.warn('IctAccessRequests: accessRequests is not an array:', accessRequests.value)
      return []
    }

    let filtered = accessRequests.value

    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      filtered = filtered.filter(
        (request) =>
          (request.staff_name || request.full_name || '').toLowerCase().includes(query) ||
          (request.pf_number || '').toLowerCase().includes(query) ||
          (request.department_name || request.department || '').toLowerCase().includes(query) ||
          (request.request_id || '').toLowerCase().includes(query)
      )
    }

    if (statusFilter.value) {
      if (statusFilter.value === 'unassigned') {
        filtered = filtered.filter((r) => isUnassigned(r.status))
      } else {
        filtered = filtered.filter((r) => r.status === statusFilter.value)
      }
    }

    return filtered.sort((a, b) => {
      const aUnassigned = isUnassigned(a.status)
      const bUnassigned = isUnassigned(b.status)
      if (aUnassigned && !bUnassigned) return -1
      if (!aUnassigned && bUnassigned) return 1

      const getRelevantDate = (r) =>
        new Date(r.head_of_it_approval_date || r.head_of_it_approved_at || r.created_at || 0)
      return getRelevantDate(a) - getRelevantDate(b)
    })
  })

  async function fetchAccessRequests() {
    console.log('🔄 IctAccessRequests: Starting to fetch access requests...')
    isLoading.value = true
    error.value = null
    try {
      const result = await ictOfficerService.getAccessRequests()
      if (result.success) {
        const payload = result.data
        const list = Array.isArray(payload)
          ? payload
          : payload && Array.isArray(payload.data)
            ? payload.data
            : []
        const total = Array.isArray(payload) ? payload.length : (payload?.total ?? list.length)
        const currentPage = payload?.current_page ?? 1
        const perPage = payload?.per_page ?? list.length

        console.log('✅ IctAccessRequests: Requests loaded successfully:', list.length, {
          total,
          currentPage,
          perPage
        })
        accessRequests.value = list
        pagination.total = total
        pagination.currentPage = currentPage
        pagination.perPage = perPage

        const pendingForICT = accessRequests.value.filter((r) => isUnassigned(r.status)).length
        stats.unassigned = pendingForICT
        stats.assigned = accessRequests.value.filter((r) => r.status === 'assigned_to_ict').length
        stats.inProgress = accessRequests.value.filter(
          (r) => r.status === 'implementation_in_progress'
        ).length
        stats.total = accessRequests.value.length

        setPendingCountOverride(pendingForICT)

        // Diagnostics are expensive (extra API calls). Only run when explicitly enabled.
        const enableNotificationDiagnostics =
          process.env.NODE_ENV === 'development' &&
          typeof localStorage !== 'undefined' &&
          localStorage.getItem('ENABLE_NOTIFICATION_DIAGNOSTICS') === 'true'
        if (enableNotificationDiagnostics) {
          setTimeout(() => diagnoseNotificationDiscrepancy(), 1000)
        }
      } else {
        console.error('❌ IctAccessRequests: Failed to load requests:', result.message)
        error.value = result.message
        // Don't clear existing data on error - keep old data visible
      }
    } catch (err) {
      console.error('❌ IctAccessRequests: Error loading requests:', err)
      error.value = 'Network error while loading access requests. Please check your connection.'
      // Don't clear existing data on error - keep old data visible
    } finally {
      isLoading.value = false
    }
  }

  async function refreshRequests() {
    console.log('🔄 IctAccessRequests: Refreshing requests...')
    await fetchAccessRequests()
    initAutoRetryForList()
  }

  async function viewRequest(request) {
    console.log('👁️ IctAccessRequests: Viewing request details:', request.id)
    try {
      if (ensureInitialized && typeof ensureInitialized === 'function') {
        await ensureInitialized()
      }
      const role =
        (typeof userRole === 'function' ? userRole() : userRole?.value) ||
        user?.value?.primary_role ||
        user?.value?.role
      if (role !== ROLES.ICT_OFFICER) {
        console.warn('Blocked navigation: current role is not ICT_OFFICER', { role })
        window.location.href = '/ict-dashboard'
        await nextTick()
        alert('You do not have permission to process this request.')
        return
      }
      const targetId = request.id
      if (!targetId) {
        console.warn('Missing id on request, cannot open form.', request)
        await nextTick()
        alert('Cannot open form: missing request id.')
        return
      }
      window.location.href = `/ict-dashboard/both-service-form/${targetId}`
    } catch (e) {
      console.error('Failed to open request processing page:', e)
      await nextTick()
      alert('Unable to open the request. Please try again.')
    }
  }

  async function assignTask(request) {
    console.log('📋 IctAccessRequests: Assigning task for request:', request.id)
    try {
      const result = await ictOfficerService.assignTaskToSelf(
        request.id,
        'Task assigned by ICT Officer'
      )
      if (result.success) {
        await fetchAccessRequests()
        refreshNotificationBadge()
        alert('Task assigned successfully!')
      } else {
        alert('Failed to assign task: ' + result.message)
      }
    } catch (error) {
      console.error('Error assigning task:', error)
      alert('Network error while assigning task')
    }
  }

  async function updateProgress(request) {
    console.log('📈 IctAccessRequests: Updating progress for request:', request.id)
    try {
      const result = await ictOfficerService.updateProgress(
        request.id,
        'implementation_in_progress',
        'Implementation started'
      )
      if (result.success) {
        await fetchAccessRequests()
        refreshNotificationBadge()
        alert('Progress updated successfully!')
      } else {
        alert('Failed to update progress: ' + result.message)
      }
    } catch (error) {
      console.error('Error updating progress:', error)
      alert('Network error while updating progress')
    }
  }

  async function cancelTask(request) {
    console.log('❌ IctAccessRequests: Canceling task for request:', request.id)
    const reason = prompt('Please provide a reason for canceling this task:')
    if (reason && reason.trim()) {
      try {
        const result = await ictOfficerService.cancelTask(request.id, reason)
        if (result.success) {
          await fetchAccessRequests()
          refreshNotificationBadge()
          alert('Task canceled successfully!')
        } else {
          alert('Failed to cancel task: ' + result.message)
        }
      } catch (error) {
        console.error('Error canceling task:', error)
        alert('Network error while canceling task')
      }
    }
  }

  function hasService(request, serviceType) {
    if (serviceType === 'jeeva') {
      return (
        request.jeeva_access_required ||
        (request.request_types && request.request_types.includes('jeeva'))
      )
    }
    if (serviceType === 'wellsoft') {
      return (
        request.wellsoft_access_required ||
        (request.request_types && request.request_types.includes('wellsoft'))
      )
    }
    if (serviceType === 'internet') {
      return (
        request.internet_access_required ||
        (request.request_types && request.request_types.includes('internet'))
      )
    }
    return false
  }

  function getRequestType(request) {
    const services = []
    if (hasService(request, 'jeeva')) services.push('Jeeva')
    if (hasService(request, 'wellsoft')) services.push('Wellsoft')
    if (hasService(request, 'internet')) services.push('Internet')
    return services.length > 0 ? services.join(' + ') : 'Access Request'
  }

  function getStatusBadgeClass(status) {
    const statusClasses = {
      head_of_it_approved: 'bg-yellow-500 text-yellow-900',
      assigned_to_ict: 'bg-blue-500 text-blue-900',
      implementation_in_progress: 'bg-purple-500 text-purple-900',
      completed: 'bg-emerald-500 text-emerald-900',
      implemented: 'bg-green-500 text-green-900',
      cancelled: 'bg-red-500 text-red-900'
    }
    return statusClasses[status] || 'bg-gray-500 text-gray-900'
  }

  function getStatusText(status) {
    const statusTexts = {
      head_of_it_approved: 'Pending Implementation',
      assigned_to_ict: 'Assigned to ICT Officer',
      implementation_in_progress: 'Implementation in Progress',
      completed: 'Implementation Completed',
      implemented: 'Access Granted',
      cancelled: 'Task Cancelled'
    }
    return (
      statusTexts[status] ||
      status?.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase()) ||
      'Unknown Status'
    )
  }

  // SMS Status helper functions
  function getRelevantSmsStatus(request) {
    // Only show SMS status if access has been granted (implemented/completed)
    if (request.status === 'implemented' || request.status === 'completed') {
      // Accept broader fields and values
      const normalize = (val) => {
        if (val === undefined || val === null) return null
        if (typeof val === 'boolean') return val ? 'sent' : 'pending'
        if (typeof val === 'number') return val === 1 ? 'sent' : val === 0 ? 'pending' : null
        const v = String(val).toLowerCase().trim()
        if (
          [
            'sent',
            'delivered',
            'success',
            'successful',
            'ok',
            'delivrd',
            'delivery_ok',
            'submitted',
            'accepted',
            'done',
            'complete',
            'completed'
          ].includes(v)
        )
          return 'sent'
        if (
          [
            'failed',
            'fail',
            'error',
            'undelivered',
            'rejected',
            'expired',
            'blocked',
            'blacklisted'
          ].includes(v)
        )
          return 'failed'
        if (['queued', 'queue', 'processing', 'pending', 'in_progress'].includes(v))
          return 'pending'
        return v || null
      }
      const ts =
        request.requester_sms_sent_at ||
        request.sms_to_requester_at ||
        request.sms_delivered_to_requester_at
      if (ts) return 'sent'
      const candidates = [
        request.sms_to_requester_status,
        request.sms_to_requester,
        request.requester_sms_status,
        request.sms_delivery_status_to_requester,
        request.sms_status
      ]
        .map(normalize)
        .filter(Boolean)
      if (candidates.length) return candidates.find((s) => s !== 'pending') || candidates[0]
      return 'pending'
    }
    // Otherwise, show pending (access not yet granted)
    return 'pending'
  }

  // SMS Status methods - handles all possible SMS statuses
  // 'sent' = SMS sent to provider, waiting for delivery confirmation
  // 'delivered' = Delivery confirmed via callback from provider
  // 'pending' = Not yet processed/sent
  // 'failed' = SMS sending failed
  // 'disabled' = SMS service is disabled
  // 'test_mode' = SMS is in test mode
  function getSmsStatusText(smsStatus) {
    const statusMap = {
      delivered: 'Delivered',
      sent: 'Sent',
      pending: 'Pending',
      failed: 'Failed',
      disabled: 'Disabled',
      test_mode: 'Test Mode'
    }
    return statusMap[smsStatus] || 'Pending'
  }

  function getSmsStatusColor(smsStatus) {
    const colorMap = {
      delivered: 'bg-green-500',
      sent: 'bg-blue-500',
      pending: 'bg-yellow-500',
      failed: 'bg-red-500',
      disabled: 'bg-gray-500',
      test_mode: 'bg-purple-500'
    }
    return colorMap[smsStatus] || 'bg-gray-400'
  }

  function getSmsStatusTextColor(smsStatus) {
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

  function viewTimeline(request) {
    console.log('📅 IctAccessRequests: Opening timeline for request:', request.id)
    selectedRequestId.value = request.id
    selectedRequest.value = request
    showTimeline.value = true
  }

  function closeTimeline() {
    console.log('📅 IctAccessRequests: Closing timeline modal')
    showTimeline.value = false
    selectedRequestId.value = null
    selectedRequest.value = null
    showUpdateProgress.value = false
  }

  async function handleTimelineUpdate() {
    console.log('🔄 IctAccessRequests: Timeline updated, refreshing requests list...')
    await fetchAccessRequests()
    refreshNotificationBadge()
  }

  function openUpdateProgress(request) {
    console.log('📝 IctAccessRequests: Opening update progress section for request:', request?.id)
    if (request && canShowUpdateProgress(request)) {
      selectedRequest.value = request
      selectedRequestId.value = request.id
      showUpdateProgress.value = true
    }
  }

  function closeUpdateProgress() {
    console.log('📝 IctAccessRequests: Closing update progress section')
    showUpdateProgress.value = false
  }

  async function handleUpdateProgressUpdate() {
    console.log('🔄 IctAccessRequests: Progress updated, refreshing data...')
    await fetchAccessRequests()
    refreshNotificationBadge()
    initAutoRetryForList()
    closeTimeline()
    setTimeout(() => refreshNotificationBadge(), 3000)
    setTimeout(() => refreshNotificationBadge(), 5000)
  }

  function canShowUpdateProgress(request) {
    if (!request) return false
    const currentUserId = user.value?.id
    return (
      request.ict_officer_user_id &&
      request.ict_officer_user_id === currentUserId &&
      !request.ict_officer_implemented_at &&
      request.ict_officer_status !== 'implemented' &&
      request.ict_officer_status !== 'rejected' &&
      !['implemented', 'completed'].includes(request.status) &&
      (request.status === 'assigned_to_ict' || request.status === 'implementation_in_progress')
    )
  }

  function toggleDropdown(requestId, event, request) {
    if (activeDropdown.value === requestId) {
      activeDropdown.value = null
      selectedRequest.value = null
    } else {
      activeDropdown.value = requestId
      selectedRequest.value = request

      if (event) {
        const button = event.target.closest('button')
        if (button) {
          const rect = button.getBoundingClientRect()

          // Always show above the button
          dropdownPosition.value = {
            top: rect.top + window.scrollY,
            right: window.innerWidth - rect.right - window.scrollX,
            showAbove: true
          }
        }
      }
    }
  }

  function getDropdownPosition() {
    if (dropdownPosition.value) {
      const dropdownWidth = 192
      const dropdownHeight = 120 // Reduced from 200 to actual height
      let { top, right, showAbove } = dropdownPosition.value

      // Adjust horizontal position if too close to left edge
      if (right + dropdownWidth > window.innerWidth) {
        right = Math.max(4, window.innerWidth - dropdownWidth - 4)
      }

      // If showing above, adjust top position to be just above the button
      if (showAbove) {
        top = top - dropdownHeight
        // Ensure it doesn't go above viewport
        if (top < window.scrollY + 4) {
          top = window.scrollY + 4
        }
      }

      return { top: `${top}px`, right: `${right}px`, left: 'auto' }
    }
    return { top: '100%', right: '0', left: 'auto', position: 'absolute' }
  }

  function handleClickOutside(event) {
    if (
      activeDropdown.value &&
      !event.target.closest('[style*="z-index: 9999"]') &&
      !event.target.closest('button[title*="Actions for"]')
    ) {
      activeDropdown.value = null
      dropdownPosition.value = null
    }
  }

  async function downloadPdf(request) {
    try {
      if (!request?.id) {
        alert('Missing request id')
        return
      }
      downloadingPdf.value = true
      downloadPercent.value = 0
      const res = await apiClient.get(`/both-service-form/${request.id}/export-pdf`, {
        responseType: 'blob',
        onDownloadProgress: (evt) => {
          if (evt.total) {
            downloadPercent.value = Math.min(100, Math.round((evt.loaded * 100) / evt.total))
          } else {
            // If total is not provided, show an animated fallback
            const next = downloadPercent.value + 3
            downloadPercent.value = next >= 95 ? 95 : next
          }
        }
      })
      const blob = new Blob([res.data], { type: 'application/pdf' })
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `access-request-${request.id}.pdf`
      document.body.appendChild(link)
      link.click()
      URL.revokeObjectURL(url)
      link.remove()
      downloadPercent.value = 100
      setTimeout(() => (downloadingPdf.value = false), 400)
    } catch (e) {
      console.error('Failed to download PDF:', e)
      downloadingPdf.value = false
      downloadPercent.value = 0
      alert('Failed to download PDF. Please try again.')
    }
  }

  function handleMenuAction(action, request) {
    activeDropdown.value = null
    dropdownPosition.value = null
    switch (action) {
      case 'viewRequest':
        viewRequest(request)
        break
      case 'viewTimeline':
        viewTimeline(request)
        break
      case 'downloadPdf':
        downloadPdf(request)
        break
      case 'assignTask':
        assignTask(request)
        break
      case 'updateProgress':
        updateProgress(request)
        break
      case 'cancelTask':
        cancelTask(request)
        break
      default:
        console.warn('Unknown action:', action)
    }
  }

  function formatDate(dateString) {
    if (!dateString) return 'N/A'
    try {
      return new Date(dateString).toLocaleDateString('en-GB')
    } catch {
      return 'Invalid Date'
    }
  }

  function formatTime(dateString) {
    if (!dateString) return 'N/A'
    try {
      return new Date(dateString).toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit'
      })
    } catch {
      return 'Invalid Time'
    }
  }

  function refreshNotificationBadge() {
    try {
      console.log('🔔 AccessRequests: Triggering notification badge refresh')
      const currentPendingCount = accessRequests.value.filter((r) => isUnassigned(r.status)).length
      setPendingCountOverride(currentPendingCount)
      clearNotificationCache()
      if (window.dispatchEvent) {
        const event = new CustomEvent('force-refresh-notifications', {
          detail: { source: 'AccessRequests', reason: 'request_updated', timestamp: Date.now() }
        })
        window.dispatchEvent(event)
        console.log('🚀 AccessRequests: Dispatched force-refresh-notifications event')
      }
      if (window.sidebarInstance && window.sidebarInstance.fetchNotificationCounts) {
        console.log('📞 AccessRequests: Calling sidebar fetchNotificationCounts directly')
        window.sidebarInstance.fetchNotificationCounts(true)
      }
    } catch (e) {
      console.warn('Failed to refresh notification badge:', e)
    }
  }

  function clearNotificationCache() {
    try {
      import('@/services/notificationService').then((m) => {
        m.default.clearCache()
        console.log('🗑️ AccessRequests: Cleared notification service cache')
      })
    } catch (e) {
      console.warn('Failed to clear notification cache:', e)
    }
  }

  function setPendingCountOverride(count) {
    try {
      console.log('🎠 AccessRequests: Setting pending count override:', count)
      if (typeof window !== 'undefined') {
        window.accessRequestsPendingCount = count
      }
      if (window.dispatchEvent) {
        const event = new CustomEvent('ict-access-requests-pending-count', {
          detail: { count, source: 'AccessRequests', timestamp: Date.now() }
        })
        window.dispatchEvent(event)
      }
      if (window.sidebarInstance && window.sidebarInstance.setNotificationCount) {
        window.sidebarInstance.setNotificationCount('/ict-dashboard/access-requests', count)
      }
      console.log('📻 AccessRequests: Emitted pending count override event')
    } catch (e) {
      console.warn('Failed to set pending count override:', e)
    }
  }

  async function diagnoseNotificationDiscrepancy() {
    try {
      console.log('🔍 DIAGNOSTIC: Comparing notification APIs...')
      const notificationService = (await import('@/services/notificationService')).default
      const universalResult = await notificationService.getPendingRequestsCount(true)
      const ictResult = await ictOfficerService.getPendingRequestsCount()
      const pageStatuses = accessRequests.value.map((r) => ({
        id: r.id,
        status: r.status,
        request_id: r.request_id || `REQ-${r.id.toString().padStart(6, '0')}`
      }))
      if (process.env.NODE_ENV === 'development') {
        console.log('🔴 NOTIFICATION API COMPARISON:', {
          universal_api: {
            endpoint: '/notifications/pending-count',
            count: universalResult.total_pending,
            full_response: universalResult
          },
          ict_specific_api: {
            endpoint: '/ict-officer/pending-count',
            count: ictResult.total_pending || ictResult,
            full_response: ictResult
          },
          page_data: {
            total_requests: accessRequests.value.length,
            pending_requests: accessRequests.value.filter((r) => isUnassigned(r.status)).length,
            completed_requests: accessRequests.value.filter((r) =>
              ['completed', 'implemented'].includes(r.status)
            ).length,
            all_statuses: pageStatuses
          },
          analysis: {
            universal_vs_ict:
              universalResult.total_pending === (ictResult.total_pending || ictResult)
                ? 'MATCH'
                : 'MISMATCH',
            badge_vs_page:
              universalResult.total_pending ===
              accessRequests.value.filter((r) => isUnassigned(r.status)).length
                ? 'CONSISTENT'
                : 'INCONSISTENT'
          }
        })
      }
    } catch (e) {
      console.error('🚫 DIAGNOSTIC ERROR:', e)
    }
  }

  // Lifecycle
  // Retry state
  const retryAttempts = reactive({}) // requestId -> count
  const retryTimers = reactive({}) // requestId -> timer id
  const maxRetryAttempts = 5
  const retryDelays = [3000, 7000, 15000, 30000, 60000]

  function getRetryAttempts(id) {
    return retryAttempts[id] || 0
  }
  function isRetrying(id) {
    return !!retryTimers[id]
  }

  async function retrySendSms(request) {
    if (!request) return
    const id = request.id
    if (!retryAttempts[id]) retryAttempts[id] = 0

    // Guard against spamming
    if (isRetrying(id)) return

    try {
      retryTimers[id] = setTimeout(() => {}, 0) // mark busy
      const phone = request.phone_number || request.phone || null
      await ictOfficerService.resendRequesterSms(id, phone)
      // Short delay to allow backend to update, then refresh
      setTimeout(async () => {
        await fetchAccessRequests()
        clearTimeout(retryTimers[id])
        delete retryTimers[id]
        const status = getRelevantSmsStatus(
          accessRequests.value.find((r) => r.id === id) || request
        )
        if (status !== 'sent') {
          // schedule next retry if allowed
          scheduleNextRetry(id, request)
        } else {
          retryAttempts[id] = 0
        }
      }, 1200)
    } catch (e) {
      clearTimeout(retryTimers[id])
      delete retryTimers[id]
      scheduleNextRetry(id, request)
    }
  }

  function scheduleNextRetry(id, request) {
    retryAttempts[id] = (retryAttempts[id] || 0) + 1
    if (retryAttempts[id] > maxRetryAttempts) return
    const delay = retryDelays[Math.min(retryAttempts[id] - 1, retryDelays.length - 1)]
    retryTimers[id] = setTimeout(() => {
      clearTimeout(retryTimers[id])
      delete retryTimers[id]
      retrySendSms(request)
    }, delay)
  }

  function initAutoRetryForList() {
    for (const r of accessRequests.value) {
      const st = getRelevantSmsStatus(r)
      if (['failed', 'pending'].includes(st) && ['implemented', 'completed'].includes(r.status)) {
        // start gentle auto-retry only if not already running
        if (!retryTimers[r.id] && (retryAttempts[r.id] || 0) === 0) {
          scheduleNextRetry(r.id, r)
        }
      }
    }
  }

  onMounted(async () => {
    try {
      console.log('IctAccessRequests: Component mounted, initializing...')
      // Safety: ensure stale overlays are cleared on mount
      isLoading.value = false
      downloadingPdf.value = false

      requireRole([ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT])
      await fetchAccessRequests()
      console.log('IctAccessRequests: Component initialized successfully')
      refreshNotificationBadge()
      initAutoRetryForList()

      // Optional diagnostics: enable by running in DevTools:
      // localStorage.setItem('ENABLE_NOTIFICATION_DIAGNOSTICS','true') then refresh.
      const enableNotificationDiagnostics =
        process.env.NODE_ENV === 'development' &&
        typeof localStorage !== 'undefined' &&
        localStorage.getItem('ENABLE_NOTIFICATION_DIAGNOSTICS') === 'true'
      if (enableNotificationDiagnostics) {
        diagnoseNotificationDiscrepancy()
      }
      document.addEventListener('click', handleClickOutside)

      // Extra safety: auto-clear loading overlays if they persist too long
      setTimeout(() => {
        if (isLoading.value) {
          console.warn('IctAccessRequests: Forcing isLoading=false after timeout')
          isLoading.value = false
        }
      }, 10000)
      setTimeout(() => {
        if (downloadingPdf.value && downloadPercent.value === 0) {
          console.warn('IctAccessRequests: Forcing downloadingPdf=false after timeout')
          downloadingPdf.value = false
        }
      }, 30000)
    } catch (e) {
      console.error('IctAccessRequests: Error during mount:', e)
      error.value = 'Failed to initialize component: ' + e.message
      isLoading.value = false
      downloadingPdf.value = false
    }
  })

  onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
  })
</script>

<style scoped>
  .sidebar-narrow {
    flex-shrink: 0;
  }

  /* Animations */
  @keyframes float {
    0%,
    100% {
      transform: translateY(0px);
    }
    50% {
      transform: translateY(-15px);
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

  /* Sliding background animation for loading banner */
  @keyframes slide-across {
    0% {
      transform: translateX(-100%);
    }
    100% {
      transform: translateX(100%);
    }
  }

  .animate-float {
    animation: float 6s ease-in-out infinite;
  }

  .animate-fade-in {
    animation: fade-in 1s ease-out;
  }

  .animate-slide-across {
    animation: slide-across 2.5s ease-in-out infinite;
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
