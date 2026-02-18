<template>
  <div class="flex flex-col h-screen">
    <AppHeader />
    <div class="flex flex-1 overflow-visible">
      <ModernSidebar />
      <main class="flex-1 p-2 bg-blue-900 min-h-screen overflow-y-auto relative">
        <UnifiedLoadingBanner
          :show="loading"
          loadingTitle="Loading Requests"
          loadingSubtitle="Fetching your requests..."
          departmentTitle="REQUEST STATUS & TRACKING"
          :forceSpin="true"
        />
        <!-- Medical Background Pattern -->
        <div class="absolute inset-0 overflow-hidden">
          <div class="absolute inset-0 opacity-5">
            <div class="grid grid-cols-12 gap-8 h-full transform rotate-45">
              <div
                v-for="i in 48"
                :key="i"
                class="bg-white rounded-full w-2 h-2 animate-pulse"
                :style="{ animationDelay: i * 0.1 + 's' }"
              ></div>
            </div>
          </div>
        </div>

        <div class="max-w-13xl mx-auto relative z-10">
          <!-- Header Section -->
          <div class="bg-white/10 rounded-t-lg p-2 mb-0 border-b border-blue-300/30">
            <div class="flex justify-between items-center">
              <div class="text-center flex-1">
                <h1 class="text-xl font-bold text-white mb-1 tracking-wide drop-shadow-lg">
                  <i class="fas fa-clipboard-check mr-2 text-blue-300"></i>
                  REQUEST STATUS & TRACKING
                </h1>
                <p class="text-blue-100/80 text-sm">
                  Track your submitted requests and view approval status
                </p>
              </div>
            </div>
          </div>

          <!-- Main Content -->
          <div class="bg-white/10 rounded-b-lg overflow-visible">
            <div class="p-4 space-y-4">
              <!-- Pending Combined Access Request Restriction Message -->
              <div
                v-if="showPendingAccessMessage"
                class="medical-card bg-gradient-to-r from-red-600/25 to-pink-700/25 border-2 border-red-400/40 p-4 rounded-2xl mb-4"
              >
                <div class="flex items-center space-x-4">
                  <div
                    class="w-16 h-16 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg"
                  >
                    <i class="fas fa-ban text-white text-2xl"></i>
                  </div>
                  <div class="flex-1">
                    <h3 class="text-base font-bold text-white mb-2">
                      <i class="fas fa-layer-group mr-2 text-red-300"></i>
                      Access Request Blocked
                    </h3>
                    <p class="text-red-100/90 text-base mb-3">
                      {{
                        blockMessage ||
                        'You cannot submit a new Combined Access request while you have a pending request that needs to be processed.'
                      }}
                    </p>
                    <div class="flex items-center space-x-4">
                      <div
                        v-if="pendingAccessRequestId"
                        class="bg-red-500/20 px-3 py-1 rounded-full border border-red-400/30"
                      >
                        <span class="text-red-300 text-sm font-medium">
                          Pending Request: {{ pendingAccessRequestId }}
                        </span>
                      </div>
                      <div class="bg-red-500/20 px-3 py-1 rounded-full border border-red-400/30">
                        <span class="text-red-300 text-sm font-medium">
                          Policy: One pending access request at a time
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col space-y-2">
                    <button
                      @click="refreshRequests"
                      class="px-3 py-1.5 bg-red-500/20 hover:bg-red-500/30 border border-red-400/30 rounded-lg text-red-300 text-sm font-medium transition-colors"
                    >
                      <i class="fas fa-sync-alt mr-1"></i>
                      Refresh Status
                    </button>
                    <button
                      @click="showPendingAccessMessage = false"
                      class="w-8 h-8 bg-red-500/20 hover:bg-red-500/30 rounded-lg flex items-center justify-center transition-colors"
                    >
                      <i class="fas fa-times text-red-300"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Pending Booking Restriction Message -->
              <div
                v-if="showPendingBookingMessage"
                class="medical-card bg-gradient-to-r from-yellow-600/25 to-orange-700/25 border-2 border-yellow-400/40 p-4 rounded-2xl mb-4"
              >
                <div class="flex items-center space-x-4">
                  <div
                    class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg"
                  >
                    <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                  </div>
                  <div class="flex-1">
                    <h3 class="text-base font-bold text-white mb-2">
                      Pending Booking Request Restriction
                    </h3>
                    <p class="text-yellow-100/90 text-base mb-3">
                      You cannot submit a new booking request because you already have a pending
                      booking request that needs to be processed.
                    </p>
                    <div class="flex items-center space-x-4">
                      <div
                        class="bg-yellow-500/20 px-3 py-1 rounded-full border border-yellow-400/30"
                      >
                        <span class="text-yellow-300 text-sm font-medium">
                          Policy: One pending booking at a time
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-col space-y-2">
                    <button
                      v-if="pendingBookingId"
                      @click="viewPendingBookingDetails"
                      class="px-3 py-1.5 bg-yellow-500/20 hover:bg-yellow-500/30 border border-yellow-400/30 rounded-lg text-yellow-300 text-sm font-medium transition-colors"
                    >
                      <i class="fas fa-eye mr-1"></i>
                      View Pending Request
                    </button>
                    <button
                      @click="showPendingBookingMessage = false"
                      class="w-8 h-8 bg-yellow-500/20 hover:bg-yellow-500/30 rounded-lg flex items-center justify-center transition-colors"
                    >
                      <i class="fas fa-times text-yellow-300"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Success Message (shown after submission) -->
              <div
                v-if="showSuccessMessage"
                class="medical-card bg-gradient-to-r from-green-600/25 to-green-700/25 border-2 border-green-400/40 p-3 rounded-2xl"
              >
                <div class="flex items-center space-x-3">
                  <div
                    class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg"
                  >
                    <i class="fas fa-check-circle text-white text-xl"></i>
                  </div>
                  <div class="flex-1">
                    <h3 class="text-lg font-bold text-white mb-1">
                      Request Submitted Successfully!
                    </h3>
                    <p class="text-green-100/90 text-base mb-2">
                      Your {{ requestType }} has been submitted and is now in the approval process.
                    </p>
                    <div class="flex items-center space-x-3">
                      <div
                        class="bg-green-500/20 px-3 py-1 rounded-full border border-green-400/30"
                      >
                        <span class="text-green-300 text-sm font-medium">
                          Request ID: #{{ latestRequestId }}
                        </span>
                      </div>
                      <div
                        class="bg-green-500/20 px-3 py-1 rounded-full border border-green-400/30"
                      >
                        <span class="text-green-300 text-sm font-medium">
                          Status: Pending Review
                        </span>
                      </div>
                    </div>
                  </div>
                  <button
                    @click="showSuccessMessage = false"
                    class="w-8 h-8 bg-green-500/20 hover:bg-green-500/30 rounded-lg flex items-center justify-center transition-colors"
                  >
                    <i class="fas fa-times text-green-300"></i>
                  </button>
                </div>
              </div>

              <!-- Request List -->
              <div class="bg-white/10 border border-blue-300/30 p-4 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center space-x-3">
                    <div
                      class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg"
                    >
                      <i class="fas fa-list-alt text-white text-base"></i>
                    </div>
                    <div>
                      <h3 class="text-lg font-bold text-white">My Requests</h3>
                      <p class="text-blue-100/80 text-xs">View all your submitted requests</p>
                    </div>
                  </div>
                  <div class="flex items-center space-x-3">
                    <button
                      @click="refreshRequests"
                      :disabled="loading"
                      class="px-3 py-1.5 bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/30 rounded-lg text-blue-300 text-sm font-medium transition-colors"
                    >
                      <OrbitingDots v-if="loading" size="xs" class="mr-2" />
                      <i v-else class="fas fa-sync-alt mr-2"></i>
                      Refresh
                    </button>
                    <div class="bg-blue-500/20 px-3 py-1.5 rounded-full border border-blue-400/30">
                      <span class="text-blue-300 text-sm font-medium">
                        Total: {{ totalRequests }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="text-center py-8">
                  <div class="inline-flex items-center space-x-3 text-blue-100">
                    <OrbitingDots size="md" />
                    <span>Loading your requests...</span>
                  </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="requests.length === 0" class="text-center py-12">
                  <div
                    class="w-20 h-20 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4"
                  >
                    <i class="fas fa-inbox text-blue-400 text-2xl"></i>
                  </div>
                  <h4 class="text-xl font-semibold text-white mb-2">No Requests Found</h4>
                  <p class="text-blue-200/80 text-base mb-6">
                    You haven't submitted any requests yet.
                  </p>
                  <button
                    @click="goToSubmitRequest"
                    class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl font-medium hover:from-blue-600 hover:to-blue-700 transition-all duration-300"
                  >
                    <i class="fas fa-plus mr-2"></i>
                    Submit New Request
                  </button>
                </div>

                <!-- Requests Table -->
                <div v-else class="overflow-visible">
                  <!-- Desktop Table -->
                  <div class="hidden lg:block bg-white/10 rounded-lg">
                    <table class="w-full relative table-fixed">
                      <thead class="bg-blue-800/50">
                        <tr class="border-b border-blue-300/20">
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[8%]">
                            ID
                          </th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[8%]">Type</th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[12%]">
                            Services
                          </th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[10%]">
                            <div class="flex items-center space-x-1">
                              <span
                                :class="
                                  hasImplementedOrApproved ? 'text-green-400' : 'text-blue-200'
                                "
                              >
                                Status
                              </span>
                              <i
                                v-if="hasImplementedOrApproved"
                                class="fas fa-check-circle text-green-400 text-xs"
                              ></i>
                            </div>
                          </th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[12%]">
                            Step
                          </th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[10%]">
                            Date
                          </th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[10%]">
                            Device
                          </th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[10%]">
                            Return
                          </th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[12%]">
                            SMS
                          </th>
                          <th class="text-left py-2 px-2 text-blue-100 font-bold text-xs w-[8%]">
                            Actions
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="request in requests"
                          :key="request.id"
                          class="border-t border-blue-300/20 hover:bg-blue-700/30 transition-colors"
                        >
                          <td class="py-2 px-2">
                            <div class="font-medium text-white text-sm">
                              {{ formatRequestId(request.id) }}
                            </div>
                            <div class="text-blue-300 text-xs mt-0.5 truncate">
                              {{ getRequestTypeLabel(request) }}
                            </div>
                          </td>
                          <td class="py-2 px-2">
                            <div class="flex items-center space-x-1">
                              <i
                                :class="getRequestTypeIcon(request.type)"
                                class="text-blue-400 text-sm"
                              ></i>
                              <span class="text-white text-sm truncate">{{
                                getRequestTypeName(request.type)
                              }}</span>
                            </div>
                          </td>
                          <td class="py-2 px-2">
                            <div class="flex flex-wrap gap-0.5">
                              <span
                                v-for="service in request.services"
                                :key="service"
                                class="px-1.5 py-0.5 bg-blue-500/20 text-blue-300 text-xs rounded-full border border-blue-400/30 truncate max-w-[70px]"
                                :title="service"
                              >
                                {{ service }}
                              </span>
                            </div>
                          </td>
                          <td class="py-2 px-2">
                            <div class="flex items-center space-x-1">
                              <div
                                class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                :class="getStatusColor(request.status)"
                              ></div>
                              <span
                                class="text-sm font-semibold flex items-center space-x-1"
                                :class="getStatusTextColor(request.status)"
                              >
                                <span class="truncate">{{ getStatusText(request.status) }}</span>
                                <i
                                  v-if="
                                    request.status === 'implemented' ||
                                    request.status === 'approved'
                                  "
                                  class="fas fa-check-circle text-green-400 text-sm"
                                ></i>
                              </span>
                            </div>
                          </td>
                          <td class="py-2 px-2">
                            <div class="text-white text-sm truncate">
                              {{ getCurrentStepText(request.current_step, request.type) }}
                            </div>
                            <div class="text-blue-300 text-xs">
                              <span v-if="request.current_step === 0"
                                >Waiting</span
                              >
                              <span v-else-if="request.type === 'booking_service'"
                                >{{ request.current_step }}/3</span
                              >
                              <span v-else>{{ request.current_step }}/6</span>
                            </div>
                            <div
                              v-if="
                                request.type === 'combined_access' &&
                                (request.hod_status === 'skipped' ||
                                  request.divisional_status === 'skipped')
                              "
                              class="flex items-center gap-1 mt-0.5"
                            >
                              <span
                                v-if="request.hod_status === 'skipped'"
                                class="px-1.5 py-0.5 text-xs rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-200"
                              >
                                HOD
                              </span>
                              <span
                                v-if="request.divisional_status === 'skipped'"
                                class="px-1.5 py-0.5 text-xs rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-200"
                              >
                                Div
                              </span>
                            </div>
                          </td>
                          <td class="py-2 px-2">
                            <div class="text-white text-sm">
                              {{ formatDate(request.created_at) }}
                            </div>
                            <div class="text-blue-300 text-xs">
                              {{ formatTime(request.created_at) }}
                            </div>
                          </td>
                          <td class="py-2 px-2">
                            <div
                              v-if="
                                request.type === 'booking_service' && request.device_availability
                              "
                            >
                              <div
                                v-if="request.device_availability.is_available"
                                class="flex items-center space-x-1"
                              >
                                <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                                <span class="text-green-300 text-xs font-semibold">OK</span>
                              </div>
                              <div
                                v-else-if="request.device_availability.status === 'out_of_stock'"
                                class="space-y-0.5"
                              >
                                <div class="flex items-center space-x-1">
                                  <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                                  <span class="text-yellow-300 text-xs font-semibold">In Use</span>
                                </div>
                                <div
                                  v-if="
                                    request.device_availability.current_users &&
                                    request.device_availability.current_users.length > 0
                                  "
                                  class="text-xs text-blue-200"
                                >
                                  By:
                                  {{
                                    request.device_availability.current_users
                                      .map((u) => u.user_name)
                                      .join(', ')
                                  }}
                                </div>
                                <div
                                  v-if="request.device_availability.nearest_return"
                                  class="text-xs text-orange-300"
                                >
                                  Available:
                                  {{ request.device_availability.nearest_return.relative_time }}
                                </div>
                                <div
                                  v-if="request.device_availability.nearest_return"
                                  class="text-xs text-blue-300"
                                >
                                  {{ request.device_availability.nearest_return.date_time }}
                                </div>
                              </div>
                              <div v-else class="flex items-center space-x-1">
                                <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                                <span class="text-red-300 text-xs font-semibold">N/A</span>
                              </div>
                            </div>
                            <div v-else class="text-gray-400 text-sm">-</div>
                          </td>
                          <td class="py-2 px-2">
                            <div v-if="request.type === 'booking_service'">
                              <div class="flex items-center space-x-1">
                                <div
                                  class="w-2.5 h-2.5 rounded-full"
                                  :class="getReturnStatusColor(request.return_status)"
                                ></div>
                                <span
                                  class="text-sm font-semibold truncate"
                                  :class="getReturnStatusTextColor(request.return_status)"
                                >
                                  {{ getReturnStatusText(request.return_status) }}
                                </span>
                              </div>
                            </div>
                            <div v-else class="text-gray-400 text-sm">-</div>
                          </td>
                          <td class="py-2 px-2">
                            <div class="flex items-center flex-wrap gap-1">
                              <div class="flex items-center space-x-1">
                                <div
                                  class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                  :class="
                                    getSmsStatusColor(request.sms_status || request.sms_to_hod_status)
                                  "
                                ></div>
                                <span
                                  class="text-sm font-semibold"
                                  :class="
                                    getSmsStatusTextColor(
                                      request.sms_status || request.sms_to_hod_status
                                    )
                                  "
                                >
                                  {{
                                    getSmsStatusText(request.sms_status || request.sms_to_hod_status)
                                  }}
                                </span>
                              </div>
                              <button
                                v-if="
                                  request.type === 'combined_access' &&
                                  ['failed', 'pending'].includes(request.sms_to_hod_status) &&
                                  !['disabled', 'test_mode'].includes(request.sms_to_hod_status)
                                "
                                @click.stop="retrySendSms(request)"
                                :disabled="isRetrying(request.id)"
                                class="px-1.5 py-0.5 text-xs rounded border border-blue-300/50 text-blue-100 hover:bg-blue-700/40 disabled:opacity-50"
                                title="Retry SMS"
                              >
                                <span v-if="!isRetrying(request.id)"><i class="fas fa-redo"></i></span>
                                <span v-else><i class="fas fa-spinner fa-spin"></i></span>
                              </button>
                              <span
                                v-if="getRetryAttempts(request.id) > 0"
                                class="text-xs text-blue-200"
                              >
                                ({{ getRetryAttempts(request.id) }})
                              </span>
                            </div>
                          </td>
                          <td class="py-2 px-2">
                            <div class="relative actions-cell" @click.stop>
                              <!-- Three-dot menu button -->
                              <button
                                :ref="el => setButtonRef(el, request.id)"
                                @click.stop="toggleActionsMenu(request.id, $event)"
                                class="px-2 py-1.5 bg-blue-500/30 hover:bg-blue-500/40 border border-blue-400/40 rounded-lg text-blue-200 transition-colors"
                              >
                                <i class="fas fa-ellipsis-v text-sm"></i>
                              </button>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Mobile Card Layout -->
                  <div class="lg:hidden space-y-4">
                    <div
                      v-for="request in requests"
                      :key="request.id"
                      class="bg-blue-500/10 border border-blue-400/30 rounded-xl p-4 hover:bg-blue-500/15 transition-colors"
                    >
                      <div class="flex items-center justify-between mb-3">
                        <div class="font-medium text-white">{{ formatRequestId(request.id) }}</div>
                        <div class="text-blue-300 text-sm">{{ getRequestTypeLabel(request) }}</div>
                        <div class="flex items-center space-x-2">
                          <div
                            class="w-2 h-2 rounded-full"
                            :class="getStatusColor(request.status)"
                          ></div>
                          <span
                            class="text-sm font-medium flex items-center space-x-1"
                            :class="getStatusTextColor(request.status)"
                          >
                            <span>{{ getStatusText(request.status) }}</span>
                            <i
                              v-if="
                                request.status === 'implemented' || request.status === 'approved'
                              "
                              class="fas fa-check-circle text-green-400 text-xs"
                            ></i>
                          </span>
                        </div>
                      </div>

                      <div class="space-y-2 mb-4">
                        <div class="flex items-center space-x-2">
                          <i :class="getRequestTypeIcon(request.type)" class="text-blue-400"></i>
                          <span class="text-white text-sm">{{
                            getRequestTypeName(request.type)
                          }}</span>
                        </div>

                        <div class="flex flex-wrap gap-1">
                          <span
                            v-for="service in request.services"
                            :key="service"
                            class="px-2 py-1 bg-blue-500/20 text-blue-300 text-xs rounded-full border border-blue-400/30"
                          >
                            {{ service }}
                          </span>
                        </div>

                        <div class="text-white text-sm">
                          {{ getCurrentStepText(request.current_step, request.type) }}
                          <span class="text-blue-300 text-xs ml-2">
                            <span v-if="request.current_step === 0"
                              >(Waiting from another user)</span
                            >
                            <span v-else-if="request.type === 'booking_service'"
                              >(Step {{ request.current_step }} of 3)</span
                            >
                            <span v-else>(Step {{ request.current_step }} of 6)</span>
                          </span>
                          <div
                            v-if="
                              request.type === 'combined_access' &&
                              (request.hod_status === 'skipped' ||
                                request.divisional_status === 'skipped')
                            "
                            class="flex items-center gap-1 mt-1"
                          >
                            <span
                              v-if="request.hod_status === 'skipped'"
                              class="px-1.5 py-0.5 text-[10px] rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-200"
                            >
                              HOD skipped
                            </span>
                            <span
                              v-if="request.divisional_status === 'skipped'"
                              class="px-1.5 py-0.5 text-[10px] rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-200"
                            >
                              Divisional skipped
                            </span>
                          </div>
                        </div>

                        <div class="text-blue-300 text-xs">
                          {{ formatDate(request.created_at) }} at
                          {{ formatTime(request.created_at) }}
                        </div>

                        <!-- Device Availability for mobile -->
                        <div
                          v-if="request.type === 'booking_service' && request.device_availability"
                          class="mt-2"
                        >
                          <div
                            v-if="request.device_availability.is_available"
                            class="flex items-center space-x-2"
                          >
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                            <span class="text-green-300 text-xs font-medium">Device Available</span>
                          </div>
                          <div
                            v-else-if="request.device_availability.status === 'out_of_stock'"
                            class="space-y-1"
                          >
                            <div class="flex items-center space-x-2">
                              <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                              <span class="text-yellow-300 text-xs font-medium">Device In Use</span>
                            </div>
                            <div
                              v-if="
                                request.device_availability.current_users &&
                                request.device_availability.current_users.length > 0
                              "
                              class="text-xs text-blue-200 ml-4"
                            >
                              By:
                              {{
                                request.device_availability.current_users
                                  .map((u) => u.user_name)
                                  .join(', ')
                              }}
                            </div>
                            <div
                              v-if="request.device_availability.nearest_return"
                              class="text-xs text-orange-300 ml-4"
                            >
                              Check back:
                              {{ request.device_availability.nearest_return.relative_time }}
                            </div>
                          </div>
                          <div v-else class="flex items-center space-x-2">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                            <span class="text-red-300 text-xs font-medium">Device Unavailable</span>
                          </div>
                        </div>

                        <!-- Return Status for mobile -->
                        <div v-if="request.type === 'booking_service'" class="mt-2">
                          <div class="flex items-center space-x-2">
                            <div
                              class="w-2 h-2 rounded-full"
                              :class="getReturnStatusColor(request.return_status)"
                            ></div>
                            <span
                              class="text-xs font-medium"
                              :class="getReturnStatusTextColor(request.return_status)"
                            >
                              Return: {{ getReturnStatusText(request.return_status) }}
                            </span>
                          </div>
                        </div>
                      </div>

                      <div class="flex flex-col space-y-2">
                        <button
                          @click="viewRequestDetails(request)"
                          class="w-full px-3 py-2 bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/30 rounded-lg text-blue-300 text-sm font-medium transition-colors flex items-center justify-center"
                        >
                          <i class="fas fa-eye mr-2"></i>
                          View Details
                        </button>

                        <button
                          v-if="canEditRequest(request)"
                          @click="editRequest(request)"
                          class="w-full px-3 py-2 bg-green-500/20 hover:bg-green-500/30 border border-green-400/30 rounded-lg text-green-300 text-sm font-medium transition-colors flex items-center justify-center"
                        >
                          <i class="fas fa-edit mr-2"></i>
                          Edit & Resubmit
                        </button>

                        <button
                          v-if="canCancelRequest(request)"
                          @click="cancelRequest(request)"
                          class="w-full px-3 py-2 bg-red-500/20 hover:bg-red-500/30 border border-red-400/30 rounded-lg text-red-300 text-sm font-medium transition-colors flex items-center justify-center"
                        >
                          <i class="fas fa-times mr-2"></i>
                          Cancel Request
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modal removed - now using full page view -->
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Edit Request Modal -->
    <EditRequestModal
      :isVisible="showEditModal"
      :requestId="selectedRequest?.id || ''"
      :requestType="selectedRequest?.type || ''"
      :requestStatus="selectedRequest?.status || ''"
      @confirm="handleEditConfirm"
      @close="closeEditModal"
    />

    <!-- Resubmit Confirmation Modal -->
    <ResubmitConfirmationModal
      :isVisible="showResubmitModal"
      :requestData="{
        id: selectedRequest?.id || '',
        type: getRequestTypeName(selectedRequest?.type || ''),
        status: selectedRequest?.status || ''
      }"
      @confirm="handleResubmitConfirm"
      @cancel="closeResubmitModal"
    />

    <!-- Teleported Dropdown Menu -->
    <Teleport to="body">
      <div
        v-if="activeMenuId !== null && getActiveRequest()"
        class="fixed w-52 bg-slate-800 border-2 border-blue-400/40 rounded-xl shadow-2xl z-[99999] overflow-hidden"
        :style="dropdownStyle"
        @click.stop
      >
        <!-- View button -->
        <button
          @click="handleViewMenuAction(getActiveRequest())"
          class="w-full px-4 py-3 text-left text-blue-300 hover:bg-blue-500/20 transition-colors flex items-center space-x-3 border-b border-blue-400/20"
        >
          <i class="fas fa-eye text-blue-400 w-5"></i>
          <span class="font-medium">View Details</span>
        </button>

        <!-- Edit button (conditional) -->
        <button
          v-if="canEditRequest(getActiveRequest())"
          @click="handleEditMenuAction(getActiveRequest())"
          class="w-full px-4 py-3 text-left text-green-300 hover:bg-green-500/20 transition-colors flex items-center space-x-3 border-b border-blue-400/20"
        >
          <i class="fas fa-edit text-green-400 w-5"></i>
          <span class="font-medium">
            {{ getActiveRequest()?.status === 'cancelled' ? 'Edit & Resubmit' : 'Edit' }}
          </span>
        </button>

        <!-- Cancel button (conditional) -->
        <button
          v-if="canCancelRequest(getActiveRequest())"
          @click="handleCancelMenuAction(getActiveRequest())"
          class="w-full px-4 py-3 text-left text-red-300 hover:bg-red-500/20 transition-colors flex items-center space-x-3"
        >
          <i class="fas fa-times text-red-400 w-5"></i>
          <span class="font-medium">Cancel Request</span>
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script>
  import { ref, onMounted, computed } from 'vue'
  import { useRouter, useRoute } from 'vue-router'
  import ModernSidebar from '@/components/ModernSidebar.vue'
  import AppHeader from '@/components/AppHeader.vue'
  import EditRequestModal from '@/components/modals/EditRequestModal.vue'
  import ResubmitConfirmationModal from '@/components/modals/ResubmitConfirmationModal.vue'
  import UnifiedLoadingBanner from '@/components/common/UnifiedLoadingBanner.vue'
  import { useAuth } from '@/composables/useAuth'
  import { useAuthStore } from '@/stores/auth'
  import requestStatusService from '@/services/requestStatusService'
  import apiClient from '@/services/apiClient'
  import notificationService from '@/services/notificationService'

  export default {
    name: 'RequestStatusPage',
    components: {
      ModernSidebar,
      AppHeader,
      EditRequestModal,
      ResubmitConfirmationModal,
      UnifiedLoadingBanner
    },
    setup() {
      const router = useRouter()
      const route = useRoute()
      const { requireRole, ROLES } = useAuth()

      // Local state
      // Sidebar state now managed by Pinia - no local state needed
      const loading = ref(false)
      const showSuccessMessage = ref(false)
      const showPendingBookingMessage = ref(false)
      const pendingBookingId = ref(null)
      const showPendingAccessMessage = ref(false)
      const pendingAccessRequestId = ref(null)
      const blockMessage = ref('')
      const requestType = ref('')
      const latestRequestId = ref('')
      const cancelingRequest = ref(null)

      // Modal state
      const showEditModal = ref(false)
      const showResubmitModal = ref(false)
      const selectedRequest = ref(null)

      // Actions menu state
      const activeMenuId = ref(null)
      const dropdownPosition = ref({ top: 0, left: 0 })
      const buttonRefs = ref({})

      // Reactive data for requests
      const requests = ref([])
      const totalRequests = ref(0)
      const currentPage = ref(1)
      const perPage = ref(15)
      const lastPage = ref(1)

      // Retry state (Staff -> HOD SMS)
      const retryAttempts = ref({})
      const retryTimers = ref({})
      const maxRetryAttempts = 5
      const retryDelays = [3000, 7000, 15000, 30000, 60000]

      // Standard approval steps (for access requests) - Updated to 6 steps
      const approvalSteps = [
        { id: 1, label: 'User Info', description: 'Submit your access request' },
        { id: 2, label: 'HOD Review', description: 'Head of Department review' },
        { id: 3, label: 'Divisional Director', description: 'Divisional Director approval' },
        { id: 4, label: 'DICT Review', description: 'DICT verification process' },
        { id: 5, label: 'Head IT', description: 'Head IT assessment' },
        { id: 6, label: 'ICT Officer', description: 'ICT Officer processing and final approval' }
      ]

      // Booking service specific steps (only 3 steps)
      const bookingSteps = [
        {
          id: 1,
          label: 'Request Submitted',
          description: 'User submitted booking request details'
        },
        {
          id: 2,
          label: 'ICT Approval',
          description: 'ICT approve the booking request'
        },
        {
          id: 3,
          label: 'Device Received',
          description: 'ICT receive the device for clearing in system'
        }
      ]

      // Guard this route - staff and ICT Officers/ICT Secretary can access
      onMounted(() => {
        requireRole([ROLES.STAFF, ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT])

        // Close dropdown when clicking outside
        document.addEventListener('click', closeActionsMenu)

        // Check if redirected from form submission
        if (route.query.success === 'true') {
          showSuccessMessage.value = true
          requestType.value = route.query.type || 'request'
          latestRequestId.value = route.query.id || 'REQ-2025-NEW'

          // Clear query parameters
          router.replace({ query: {} })
        }

        // Check if redirected due to pending booking restriction
        if (route.query.pendingBooking === 'true') {
          showPendingBookingMessage.value = true
          pendingBookingId.value = route.query.pendingId || null

          // Clear query parameters but keep the notification visible
          router.replace({ query: {} })
        }

        // Check if redirected due to pending combined access request restriction
        if (route.query.blocked === 'true') {
          showPendingAccessMessage.value = true
          pendingAccessRequestId.value = route.query.pending_request_id || null
          blockMessage.value =
            route.query.message ||
            'You cannot submit a new Combined Access request while you have a pending request.'

          // Clear query parameters but keep the notification visible
          router.replace({ query: {} })
        }

        loadRequests()
      })

      // Methods
      const loadRequests = async (page = 1, filters = {}) => {
        loading.value = true
        try {
          const response = await requestStatusService.getRequestsPaginated(
            page,
            perPage.value,
            filters
          )

          if (response.success) {
            requests.value = response.data.data || []
            totalRequests.value = response.data.total || 0
            currentPage.value = response.data.current_page || 1
            lastPage.value = response.data.last_page || 1

            // initialize auto-retry for any failed/pending SMS to HOD
            initAutoRetryForList()

            console.log('✅ Requests loaded successfully:', {
              total: totalRequests.value,
              current_page: currentPage.value,
              requests_count: requests.value.length,
              sample_request: requests.value[0] // Show first request for debugging
            })
          } else {
            console.error('❌ Failed to load requests:', response.error)
            // Show user-friendly error message
            alert('Failed to load requests: ' + response.error)
          }
        } catch (error) {
          console.error('❌ Error loading requests:', error)
          alert('An error occurred while loading requests. Please try again.')
        } finally {
          loading.value = false
        }
      }

      const refreshRequests = () => {
        loadRequests(currentPage.value)
      }

      const editCancelledRequest = async (request) => {
        console.log('📝 Editing cancelled request for resubmission:', {
          requestId: request.original_id || request.id,
          requestType: request.type,
          currentStatus: request.status
        })

        // Navigate to appropriate edit page based on request type
        let editPath = ''
        let hasEditSupport = true

        switch (request.type) {
          case 'booking_service':
            editPath = '/edit-booking-request'
            break
          case 'combined_access':
            editPath = '/user-combined-form'
            break
          case 'jeeva_access':
          case 'jeeva':
            alert(
              `Edit functionality for ${getRequestTypeName(request.type)} requests is currently in development.\n\n` +
                `For now, you can:\n` +
                `1. Submit a new ${getRequestTypeName(request.type)} request\n` +
                `2. Contact support for assistance with modifying this request\n\n` +
                `Combined access requests already support editing.`
            )
            hasEditSupport = false
            break
          case 'wellsoft':
            alert(
              `Edit functionality for ${getRequestTypeName(request.type)} requests is currently in development.\n\n` +
                `For now, you can:\n` +
                `1. Submit a new ${getRequestTypeName(request.type)} request\n` +
                `2. Contact support for assistance with modifying this request\n\n` +
                `Combined access requests already support editing.`
            )
            hasEditSupport = false
            break
          case 'internet_access_request':
          case 'internet':
            alert(
              `Edit functionality for ${getRequestTypeName(request.type)} requests is currently in development.\n\n` +
                `For now, you can:\n` +
                `1. Submit a new ${getRequestTypeName(request.type)} request\n` +
                `2. Contact support for assistance with modifying this request\n\n` +
                `Combined access requests already support editing.`
            )
            hasEditSupport = false
            break
          default:
            alert(
              `Edit functionality not yet implemented for ${getRequestTypeName(request.type)} requests.`
            )
            hasEditSupport = false
            break
        }

        if (!hasEditSupport) {
          return
        }

        try {
          const navigationTarget = {
            path: editPath,
            query: {
              id: request.original_id || request.id,
              mode: 'edit',
              status: 'cancelled' // Pass cancelled status instead of rejected
            }
          }

          console.log('📝 EditCancelledRequest: Navigation target prepared:', navigationTarget)

          // Navigate to edit form
          const navigationResult = await router.push(navigationTarget)
          console.log(
            '✅ EditCancelledRequest: Navigation completed successfully:',
            navigationResult
          )
        } catch (error) {
          console.error('❌ EditCancelledRequest: Navigation failed:', error)
          alert(
            `Navigation error: Unable to navigate to edit page.\n\n` +
              `Please try refreshing the page or contact support.\n\n` +
              `Error: ${error.message}`
          )
        }
      }

      const viewRequestDetails = (request) => {
        // Navigate to staff-specific request details page
        // Use original_id (database ID) instead of formatted id
        router.push({
          path: '/request-details',
          query: {
            id: request.original_id || request.id,
            type: request.type
          }
        })
      }

      const editRequest = async (request) => {
        console.log('🚀 EditRequest: Starting edit/resubmit request flow', {
          requestId: request.id,
          originalId: request.original_id,
          requestType: request.type,
          status: request.status,
          canEdit: canEditRequest(request)
        })

        // Check current user authentication and role
        const piniaAuthStore = useAuthStore()
        console.log('🔍 EditRequest: Current auth state:', {
          isAuthenticated: piniaAuthStore.isAuthenticated,
          userRole: piniaAuthStore.userRole,
          userName: piniaAuthStore.user?.name,
          userId: piniaAuthStore.user?.id
        })

        // Check if request can be edited/resubmitted
        if (!canEditRequest(request)) {
          alert(
            `This ${getRequestTypeName(request.type)} request cannot be edited in its current status: ${getStatusText(request.status)}`
          )
          return
        }

        // Handle cancelled requests - allow editing before resubmission
        if (request.status === 'cancelled') {
          // Show attractive confirmation modal instead of basic confirm dialog
          selectedRequest.value = request
          showResubmitModal.value = true
          return
        }

        // Show custom modal for other rejected requests
        selectedRequest.value = request
        showEditModal.value = true

        // The rest of the logic is now in handleEditConfirm method
        return
      }

      const handleEditConfirm = async () => {
        const request = selectedRequest.value
        if (!request) return

        console.log('✅ EditRequest: User confirmed via modal, proceeding with navigation')

        // Navigate to appropriate edit page based on request type
        let editPath = ''
        let hasEditSupport = true

        switch (request.type) {
          case 'booking_service':
            editPath = '/edit-booking-request'
            break
          case 'combined_access':
            editPath = '/user-combined-form'
            break
          case 'jeeva_access':
          case 'jeeva':
            // Individual forms don't have :id routes yet, show helpful message
            alert(
              `Edit functionality for ${getRequestTypeName(request.type)} requests is currently in development.\n\n` +
                `For now, you can:\n` +
                `1. Submit a new ${getRequestTypeName(request.type)} request\n` +
                `2. Contact support for assistance with modifying this request\n\n` +
                `Combined access requests already support editing.`
            )
            hasEditSupport = false
            break
          case 'wellsoft':
            alert(
              `Edit functionality for ${getRequestTypeName(request.type)} requests is currently in development.\n\n` +
                `For now, you can:\n` +
                `1. Submit a new ${getRequestTypeName(request.type)} request\n` +
                `2. Contact support for assistance with modifying this request\n\n` +
                `Combined access requests already support editing.`
            )
            hasEditSupport = false
            break
          case 'internet_access_request':
          case 'internet':
            alert(
              `Edit functionality for ${getRequestTypeName(request.type)} requests is currently in development.\n\n` +
                `For now, you can:\n` +
                `1. Submit a new ${getRequestTypeName(request.type)} request\n` +
                `2. Contact support for assistance with modifying this request\n\n` +
                `Combined access requests already support editing.`
            )
            hasEditSupport = false
            break
          default:
            alert(
              `Edit functionality not yet implemented for ${getRequestTypeName(request.type)} requests.`
            )
            hasEditSupport = false
            break
        }

        if (!hasEditSupport) {
          return
        }

        // Navigate to edit page with the request data
        const navigationData = {
          requestId: request.id,
          originalId: request.original_id,
          requestType: request.type,
          editPath: editPath,
          finalId: request.original_id || request.id,
          currentRoute: route.fullPath,
          currentPath: route.path
        }

        console.log('📝 EditRequest: Preparing navigation with data:', navigationData)

        try {
          const navigationTarget = {
            path: editPath,
            query: {
              id: request.original_id || request.id,
              mode: 'edit',
              status: 'rejected'
            }
          }

          console.log('📝 EditRequest: Navigation target prepared:', navigationTarget)
          console.log('📝 EditRequest: Current router state:', {
            currentRoute: router.currentRoute.value,
            hasRouter: !!router,
            routerOptions: router.options
          })

          // Use await to catch any navigation errors
          console.log('🚀 EditRequest: Attempting navigation...')
          const navigationResult = await router.push(navigationTarget)
          console.log('✅ EditRequest: Navigation completed successfully:', navigationResult)
        } catch (error) {
          console.error('❌ EditRequest: Navigation failed with error:', {
            error: error,
            message: error.message,
            stack: error.stack,
            type: error.type,
            to: error.to,
            from: error.from
          })

          // Check if this is a navigation guard rejection
          if (error.type === 2 || error.message?.includes('navigation guard')) {
            console.error('❌ EditRequest: Navigation blocked by route guard')
            alert(
              `Navigation blocked by route guard. This might be due to:\n\n` +
                `1. Missing authentication token\n` +
                `2. Insufficient user permissions\n` +
                `3. Route configuration issue\n\n` +
                `Please try logging out and back in, or contact support.`
            )
            return
          }

          // Fallback navigation attempt
          console.log('🔄 EditRequest: Attempting fallback navigation...')
          try {
            const fallbackUrl = `${editPath}?id=${request.original_id || request.id}&mode=edit&status=rejected`
            console.log('🔄 EditRequest: Fallback URL:', fallbackUrl)
            await router.push(fallbackUrl)
            console.log('✅ EditRequest: Fallback navigation successful')
          } catch (fallbackError) {
            console.error('❌ EditRequest: Fallback navigation also failed:', fallbackError)
            alert(
              `Navigation error: Unable to navigate to edit page.\n\nPlease try refreshing the page or contact support.\n\nError: ${error.message}\nFallback Error: ${fallbackError.message}`
            )
          }
        }
      }

      const closeEditModal = () => {
        showEditModal.value = false
        selectedRequest.value = null
      }

      const closeResubmitModal = () => {
        showResubmitModal.value = false
        selectedRequest.value = null
      }

      const handleResubmitConfirm = async () => {
        // Close the modal first
        showResubmitModal.value = false

        // Get the selected request
        const request = selectedRequest.value
        if (!request) return

        // Navigate to the appropriate edit form based on request type
        await handleEditConfirm()
      }

      const goToSubmitRequest = () => {
        router.push('/user-dashboard')
      }

      const submitNewRequest = () => {
        router.push('/user-dashboard')
      }

      const viewPendingBookingDetails = () => {
        if (pendingBookingId.value) {
          router.push({
            path: '/request-details',
            query: {
              id: pendingBookingId.value,
              type: 'booking_service'
            }
          })
        }
      }

      // Check if request can be cancelled
      const canCancelRequest = (request) => {
        // Only allow cancellation for pending requests
        const canCancel = request.status === 'pending' && !cancelingRequest.value
        console.log('🔍 canCancelRequest check:', {
          requestId: request.id,
          status: request.status,
          cancelingRequest: cancelingRequest.value,
          canCancel: canCancel
        })
        return canCancel
      }

      // Check if request can be edited/resubmitted
      const canEditRequest = (request) => {
        // Allow editing rejected and cancelled requests for all types
        const rejectedStatuses = [
          'rejected',
          'hod_rejected',
          'divisional_rejected',
          'ict_director_rejected',
          'head_it_rejected',
          'ict_officer_rejected',
          'cancelled' // Allow resubmission of cancelled requests
        ]

        const canEdit = rejectedStatuses.includes(request.status) && !cancelingRequest.value

        console.log('🔍 canEditRequest check:', {
          requestId: request.id,
          requestType: request.type,
          status: request.status,
          canEdit: canEdit
        })

        return canEdit
      }

      // Cancel request functionality
      const cancelRequest = async (request) => {
        if (!canCancelRequest(request)) {
          return
        }

        // Confirm cancellation
        const confirmed = confirm(
          `Are you sure you want to cancel this ${request.type === 'booking_service' ? 'booking' : 'access'} request?\n\nRequest ID: #${request.id}\nType: ${getRequestTypeName(request.type)}\n\nThis action cannot be undone.`
        )

        if (!confirmed) {
          return
        }

        cancelingRequest.value = request.id

        try {
          // Call the appropriate cancel API based on request type using apiClient
          if (request.type === 'booking_service') {
            // For booking service requests, call the booking service API
            await apiClient.delete(`/booking-service/bookings/${request.original_id || request.id}`)
          } else {
            // For access requests, call the user access API
            await apiClient.delete(`/v1/user-access/${request.original_id || request.id}`)
          }

          // Axios automatically handles status codes, so if we get here, it was successful
          // Show success message
          alert(`Request #${request.id} has been cancelled successfully.`)

          // Refresh the requests list
          await loadRequests(currentPage.value)
        } catch (error) {
          console.error('❌ Error cancelling request:', error)
          alert(
            `Failed to cancel request: ${error.message}\n\nPlease try again or contact support if the problem persists.`
          )
        } finally {
          cancelingRequest.value = null
        }
      }

      // Use service methods for consistent formatting
      const getRequestTypeIcon = (type) => {
        return requestStatusService.getRequestTypeIcon(type)
      }

      const getRequestTypeName = (type) => {
        return requestStatusService.getRequestTypeDisplayName(type)
      }

      const getStatusColor = (status) => {
        return requestStatusService.getStatusColorClass(status)
      }

      const getStatusTextColor = (status) => {
        return requestStatusService.getStatusTextColorClass(status)
      }

      const getStatusText = (status) => {
        return requestStatusService.getStatusDisplayName(status)
      }

      const getCurrentStepText = (step, requestType = null) => {
        // Special case for step 0: Waiting from another user (per user rule)
        if (step === 0) {
          return 'Waiting from another user'
        }

        // Use different steps array based on request type
        const stepsArray = requestType === 'booking_service' ? bookingSteps : approvalSteps
        const stepObj = stepsArray.find((s) => s.id === step)
        return stepObj ? stepObj.label : `Step ${step}`
      }

      const getStepStatusClass = (stepId, currentStep, status) => {
        if (stepId < currentStep || (status === 'approved' && stepId <= 6)) {
          return 'bg-green-500'
        } else if (stepId === currentStep && status === 'pending') {
          return 'bg-blue-500'
        } else if (status === 'rejected' && stepId === currentStep) {
          return 'bg-red-500'
        } else {
          return 'bg-gray-300'
        }
      }

      const getStepTextClass = (stepId, currentStep, status) => {
        if (stepId < currentStep || (status === 'approved' && stepId <= 6)) {
          return 'text-green-700'
        } else if (stepId === currentStep) {
          return status === 'rejected' ? 'text-red-700' : 'text-blue-700'
        } else {
          return 'text-gray-500'
        }
      }

      const formatDate = (dateString) => {
        return requestStatusService.formatDate(dateString)
      }

      const formatTime = (dateString) => {
        return requestStatusService.formatTime(dateString)
      }

      // Return status methods
      const getReturnStatusText = (returnStatus) => {
        const statusMap = {
          not_yet_returned: 'Not Yet Returned',
          returned: 'Returned',
          returned_but_compromised: 'Returned but Compromised'
        }
        return statusMap[returnStatus] || returnStatus || 'Not Yet Returned'
      }

      const getReturnStatusColor = (returnStatus) => {
        const colorMap = {
          not_yet_returned: 'bg-yellow-400',
          returned: 'bg-green-400',
          returned_but_compromised: 'bg-orange-400'
        }
        return colorMap[returnStatus] || 'bg-gray-400'
      }

      const getReturnStatusTextColor = (returnStatus) => {
        const textColorMap = {
          not_yet_returned: 'text-yellow-400',
          returned: 'text-green-400',
          returned_but_compromised: 'text-orange-400'
        }
        return textColorMap[returnStatus] || 'text-gray-400'
      }

      // Format request ID with REQ prefix and padding
      const formatRequestId = (id) => {
        if (!id) return 'N/A'
        const numericId = String(id).padStart(6, '0')
        return `#REQ-${numericId}`
      }

      // Get request type label with services information
      const getRequestTypeLabel = (request) => {
        if (!request) return 'Unknown'

        const baseType = getRequestTypeName(request.type)

        // For combined access requests, include the services
        if (request.type === 'combined_access' && request.services && request.services.length > 0) {
          const serviceNames = request.services.join(', ')
          return `${baseType} - ${serviceNames}`
        }

        return baseType
      }

      // Check if there are any implemented or approved requests
      const hasImplementedOrApproved = computed(() => {
        return requests.value.some(
          (request) => request.status === 'implemented' || request.status === 'approved'
        )
      })

      // Set button ref for dropdown positioning
      const setButtonRef = (el, requestId) => {
        if (el) {
          buttonRefs.value[requestId] = el
        }
      }

      // Get active request for dropdown
      const getActiveRequest = () => {
        if (!activeMenuId.value) return null
        return requests.value.find((r) => r.id === activeMenuId.value)
      }

      // Computed dropdown style
      const dropdownStyle = computed(() => {
        return {
          top: `${dropdownPosition.value.top}px`,
          left: `${dropdownPosition.value.left}px`
        }
      })

      // Toggle actions menu with position calculation
      const toggleActionsMenu = (requestId, event) => {
        if (activeMenuId.value === requestId) {
          activeMenuId.value = null
          return
        }

        activeMenuId.value = requestId

        // Calculate position based on button click
        if (event && event.target) {
          const button = event.target.closest('button')
          if (button) {
            const rect = button.getBoundingClientRect()
            const dropdownHeight = 150 // Approximate dropdown height
            const dropdownWidth = 208 // 13rem = 208px

            // Position above button if near bottom, otherwise below
            let top = rect.bottom + 8
            if (rect.bottom + dropdownHeight > window.innerHeight) {
              top = rect.top - dropdownHeight - 8
            }

            // Ensure it doesn't go off the right edge
            let left = rect.right - dropdownWidth
            if (left < 16) {
              left = 16
            }

            dropdownPosition.value = { top, left }
          }
        }
      }

      // Close actions menu
      const closeActionsMenu = () => {
        activeMenuId.value = null
      }

      // Action menu button handlers to run multiple operations on click
      const handleViewMenuAction = (request) => {
        viewRequestDetails(request)
        closeActionsMenu()
      }

      const handleEditMenuAction = (request) => {
        editRequest(request)
        closeActionsMenu()
      }

      const handleCancelMenuAction = (request) => {
        cancelRequest(request)
        closeActionsMenu()
      }

      // SMS retry helpers (Staff -> HOD)
      const getRetryAttempts = (id) => retryAttempts.value[id] || 0
      const isRetrying = (id) => !!retryTimers.value[id]
      const scheduleNextRetry = (id, request) => {
        // Only schedule auto-retry for combined access (HOD-based) requests
        if (!request || request.type !== 'combined_access') return
        retryAttempts.value[id] = (retryAttempts.value[id] || 0) + 1
        if (retryAttempts.value[id] > maxRetryAttempts) return
        const delay = retryDelays[Math.min(retryAttempts.value[id] - 1, retryDelays.length - 1)]
        retryTimers.value[id] = setTimeout(async () => {
          clearTimeout(retryTimers.value[id])
          delete retryTimers.value[id]
          await retrySendSms(request)
        }, delay)
      }
      const retrySendSms = async (request) => {
        if (!request || request.type !== 'combined_access') return
        const id = request.id
        if (!retryAttempts.value[id]) retryAttempts.value[id] = 0
        if (isRetrying(id)) return
        try {
          retryTimers.value[id] = setTimeout(() => {}, 0)
          await notificationService.resendSmsGeneric({
            requestId: id,
            role: 'staff',
            target: 'hod'
          })
          setTimeout(async () => {
            await loadRequests(currentPage.value)
            clearTimeout(retryTimers.value[id])
            delete retryTimers.value[id]
            const updated = (requests.value || []).find((r) => r.id === id) || request
            const status = updated.sms_to_hod_status || 'pending'
            if (!['sent'].includes(status)) {
              scheduleNextRetry(id, updated)
            } else {
              retryAttempts.value[id] = 0
            }
          }, 1200)
        } catch (e) {
          clearTimeout(retryTimers.value[id])
          delete retryTimers.value[id]
          scheduleNextRetry(id, request)
        }
      }
      const initAutoRetryForList = () => {
        ;(requests.value || []).forEach((r) => {
          // Auto-retry applies only to combined access requests where HOD SMS is relevant
          if (!r || r.type !== 'combined_access') return
          const st = r.sms_to_hod_status || 'pending'
          // Only auto-retry for failed/pending, NOT for disabled/test_mode (intentional skip)
          if (['failed', 'pending'].includes(st) && !['disabled', 'test_mode'].includes(st)) {
            if (!retryTimers.value[r.id] && (retryAttempts.value[r.id] || 0) === 0) {
              scheduleNextRetry(r.id, r)
            }
          }
        })
      }

      // SMS Status methods - handles all possible SMS statuses
      // 'sent' = SMS sent to provider, waiting for delivery confirmation
      // 'delivered' = Delivery confirmed via callback from provider
      // 'pending' = Not yet processed/sent
      // 'failed' = SMS sending failed
      // 'disabled' = SMS service is disabled (nothing was sent)
      // 'test_mode' = SMS is in test mode (nothing was sent)
      const getSmsStatusText = (smsStatus) => {
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

      const getSmsStatusColor = (smsStatus) => {
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

      const getSmsStatusTextColor = (smsStatus) => {
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

      return {
        loading,
        requests,
        totalRequests,
        currentPage,
        perPage,
        lastPage,
        showSuccessMessage,
        showPendingBookingMessage,
        pendingBookingId,
        showPendingAccessMessage,
        pendingAccessRequestId,
        blockMessage,
        requestType,
        latestRequestId,
        cancelingRequest,
        showEditModal,
        showResubmitModal,
        selectedRequest,
        activeMenuId,
        dropdownStyle,
        setButtonRef,
        getActiveRequest,
        approvalSteps,
        bookingSteps,
        loadRequests,
        refreshRequests,
        editCancelledRequest,
        viewRequestDetails,
        editRequest,
        handleEditConfirm,
        closeEditModal,
        closeResubmitModal,
        handleResubmitConfirm,
        goToSubmitRequest,
        submitNewRequest,
        viewPendingBookingDetails,
        canCancelRequest,
        canEditRequest,
        cancelRequest,
        getRequestTypeIcon,
        getRequestTypeName,
        getStatusColor,
        getStatusTextColor,
        getStatusText,
        getCurrentStepText,
        getStepStatusClass,
        getStepTextClass,
        formatDate,
        formatTime,
        getReturnStatusText,
        getReturnStatusColor,
        getReturnStatusTextColor,
        formatRequestId,
        getRequestTypeLabel,
        hasImplementedOrApproved,
        toggleActionsMenu,
        closeActionsMenu,
        getSmsStatusText,
        getSmsStatusColor,
        getSmsStatusTextColor,
        handleViewMenuAction,
        handleEditMenuAction,
        handleCancelMenuAction,
        // retry helpers
        getRetryAttempts,
        isRetrying,
        retrySendSms
      }
    }
  }
</script>

<style scoped>
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

  .medical-card {
    position: relative;
    overflow: hidden;
    background: rgba(59, 130, 246, 0.1);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
  }

  /* Table styling */
  table {
    border-collapse: separate;
    border-spacing: 0;
  }

  tbody tr:hover {
    background: rgba(59, 130, 246, 0.1);
  }

  /* Modal improvements */
  .modal-overlay {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
  }

  /* Ensure modal content is scrollable */
  .modal-body {
    scrollbar-width: thin;
    scrollbar-color: rgba(59, 130, 246, 0.3) transparent;
  }

  .modal-body::-webkit-scrollbar {
    width: 6px;
  }

  .modal-body::-webkit-scrollbar-track {
    background: transparent;
  }

  .modal-body::-webkit-scrollbar-thumb {
    background-color: rgba(59, 130, 246, 0.3);
    border-radius: 3px;
  }

  .modal-body::-webkit-scrollbar-thumb:hover {
    background-color: rgba(59, 130, 246, 0.5);
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

  .animate-float {
    animation: float 6s ease-in-out infinite;
  }

  /* Responsive improvements */
  @media (max-width: 640px) {
    .modal-overlay {
      padding: 0.5rem;
    }
  }

  /* Table layout */
  .table-fixed {
    table-layout: fixed;
  }
</style>
