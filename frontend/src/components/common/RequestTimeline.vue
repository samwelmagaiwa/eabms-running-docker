<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[12000]"
    @click.self="close"
  >
    <div
      class="rounded-lg max-w-6xl w-full mx-6 h-[95vh] overflow-hidden bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white flex flex-col timeline-typography"
    >
      <!-- Modal Header -->
      <div
        class="flex items-center justify-between p-6 border-b border-blue-600/40 bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white"
      >
        <div>
          <h2 class="text-2xl font-semibold text-white">Request Timeline</h2>
          <p class="text-base text-blue-200" v-if="normalizedRequest">
            Request #{{ normalizedRequest.display_id }} - {{ normalizedRequest.staff_name || '—' }}
          </p>
        </div>
        <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            ></path>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 overflow-y-auto flex-1">
        <div v-if="loading" class="py-16 flex items-center justify-center">
          <div
            class="flex items-center space-x-3 px-4 py-2 rounded-full bg-blue-700/40 border border-blue-400/40 shadow-lg backdrop-blur-sm"
          >
            <div class="relative">
              <div
                class="h-6 w-6 rounded-full border-2 border-blue-300 border-t-transparent animate-spin"
              ></div>
              <div
                class="absolute inset-0 rounded-full"
                style="box-shadow: 0 0 12px 2px rgba(59, 130, 246, 0.6)"
              ></div>
            </div>
            <span class="text-blue-100 font-medium tracking-wide">Loading timeline…</span>
          </div>
        </div>

        <div v-else-if="error" class="text-center py-12">
          <div class="text-red-600 mb-2">
            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z"
              ></path>
            </svg>
          </div>
          <p class="text-red-600 font-medium">Failed to load timeline</p>
          <p class="text-gray-500 text-sm mt-1">{{ error }}</p>
          <button
            @click="loadTimeline"
            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
          >
            Retry
          </button>
        </div>

        <!-- Timeline Content -->
        <div v-else-if="timelineData" class="relative">
          <!-- Request Information -->
          <div class="mb-4 p-3 bg-blue-800 text-white rounded-lg border border-blue-400/50">
            <div class="flex items-center justify-between mb-1">
              <h3 class="font-medium text-white text-sm">Request Details</h3>
              <button
                v-if="normalizedRequest?.id && !hasUserSigned"
                @click="handleSignDocument"
                :disabled="isSigning"
                class="px-2 py-1 text-xs rounded bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50"
                title="Digitally sign this document"
              >
                {{ isSigning ? 'Signing…' : 'Sign Document' }}
              </button>
              <span v-else-if="hasUserSigned" class="text-emerald-300 text-xs"
                >You signed this document</span
              >
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <div>
                <span class="font-medium">Staff Name:</span>
                {{ normalizedRequest?.staff_name || '—' }}
              </div>
              <div>
                <span class="font-medium">PF Number:</span>
                {{ normalizedRequest?.pf_number || '—' }}
              </div>
              <div>
                <span class="font-medium">Department:</span>
                {{ normalizedRequest?.department_name || 'N/A' }}
              </div>
              <div>
                <span class="font-medium">Request Type:</span>
                {{ normalizedRequest?.request_type_name || '—' }}
              </div>
              <div v-if="normalizedRequest?.access_type_name">
                <span class="font-medium">Access Type:</span>
                {{ normalizedRequest?.access_type_name }}
              </div>
              <div>
                <span class="font-medium">Submitted:</span>
                {{ formatDateTime(normalizedRequest?.created_at) }}
              </div>
            </div>

            <!-- Digital Signature History -->
            <div
              v-if="signatures && signatures.length"
              class="mt-3 pt-2 border-t border-blue-600/40"
            >
              <h4 class="text-sm font-semibold text-white mb-2">Digital Signatures</h4>
              <ul class="space-y-1.5">
                <li
                  v-for="s in signatures"
                  :key="s.id"
                  class="text-sm text-blue-100 leading-relaxed"
                >
                  <span class="text-emerald-300 font-medium">Signed by</span>
                  {{ s.user_name || 'Unknown' }}
                  <span class="text-emerald-300 font-medium">at</span> {{ s.signed_at || '—' }}
                  <span class="text-emerald-300 font-medium">Signature</span>:
                  {{ s.signature_preview }}…
                </li>
              </ul>
            </div>
          </div>

          <!-- Timeline Steps -->
          <div class="space-y-4">
            <div
              v-for="(step, index) in timelineSteps"
              :key="step.id"
              class="relative flex items-start"
            >
              <!-- Timeline Line -->
              <div
                v-if="index < timelineSteps.length - 1"
                class="absolute left-3 top-8 w-0.5 h-12 bg-gray-200"
                :class="{
                  'bg-green-400': step.status === 'completed',
                  'bg-red-400': step.status === 'rejected',
                  'bg-yellow-400': step.status === 'pending'
                }"
              ></div>

              <!-- Step Icon -->
              <div
                class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center border-2 bg-blue-800"
                :class="getStepIconClasses(step.status)"
              >
                <svg
                  v-if="step.status === 'completed'"
                  class="w-3 h-3 text-white"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  ></path>
                </svg>
                <svg
                  v-else-if="step.status === 'rejected'"
                  class="w-3 h-3 text-white"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                  ></path>
                </svg>
                <div v-else class="w-1.5 h-1.5 rounded-full bg-gray-400"></div>
              </div>

              <!-- Step Content -->
              <div class="ml-4 flex-1">
                <div
                  class="bg-blue-800 text-white border rounded-lg p-3 shadow-sm"
                  :class="{
                    'border-green-300': step.status === 'completed',
                    'border-red-300': step.status === 'rejected',
                    'border-yellow-300': step.status === 'pending',
                    'border-blue-300': step.status === 'active'
                  }"
                >
                  <!-- Step Header -->
                  <div class="flex items-center justify-between mb-1">
                    <h4 class="font-medium text-sm text-white">
                      {{ step.title }}
                    </h4>
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="getStatusClasses(step.statusLabel || step.status)"
                      :style="getStatusStyle(step.statusLabel || step.status)"
                    >
                      {{ step.statusLabel }}
                    </span>
                  </div>

                  <!-- Step Details -->
                  <div class="space-y-1 text-xs">
                    <div v-if="step.actor" class="flex items-center gap-2">
                      <span class="font-medium text-white w-28 text-xs">Actor:</span>
                      <span class="text-xs text-white leading-relaxed">{{ step.actor }}</span>
                      <span v-if="step.position" class="text-blue-200 text-xs"
                        >({{ step.position }})</span
                      >
                    </div>

                    <div v-if="step.timestamp" class="flex items-center gap-2">
                      <span class="font-medium text-white w-28 text-xs">Date:</span>
                      <span class="text-xs text-white leading-relaxed">{{
                        formatDateTime(step.timestamp)
                      }}</span>
                    </div>

                    <div v-if="step.pfNumber" class="flex items-center gap-2">
                      <span class="font-medium text-white w-28 text-xs">PF Number:</span>
                      <span class="text-xs text-white">{{ step.pfNumber }}</span>
                    </div>

                    <div v-if="step.id !== 'submission'" class="flex items-start gap-2">
                      <span class="font-medium text-white w-28 flex-shrink-0 text-xs"
                        >Comments:</span
                      >
                      <span
                        class="text-white text-xs leading-relaxed whitespace-pre-wrap break-words"
                        >{{ step.comments || '—' }}</span
                      >
                    </div>

                    <!-- HOD/Divisional signature area: show red 'Stage Skipped' badge when applicable -->
                    <div
                      v-if="
                        (step.id === 'divisional' &&
                          (timelineData?.request?.divisional_status === 'skipped' ||
                            step.statusLabel === 'Skipped') &&
                          !isDivisionalApprovalEditable) ||
                        (step.id === 'hod' &&
                          (timelineData?.request?.hod_status === 'skipped' ||
                            step.statusLabel === 'Skipped') &&
                          !isHodApprovalEditable)
                      "
                      class="flex items-center gap-2"
                    >
                      <span class="font-medium text-white w-28 text-xs">Signature:</span>
                      <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-900/30 text-red-300 border border-red-500/40"
                      >
                        {{
                          step.id === 'divisional'
                            ? 'No Divisional Director — Stage Skipped'
                            : 'No HOD — Stage Skipped'
                        }}
                      </span>
                    </div>

                    <div v-else-if="step.hasSignature" class="flex items-center gap-2">
                      <span class="font-medium text-white w-28 text-xs">Signature:</span>
                      <span class="text-green-300 text-xs">✓ Signed</span>
                    </div>

                    <div
                      v-if="step.rejectionReasons && step.rejectionReasons.length"
                      class="flex items-start gap-2"
                    >
                      <span class="font-medium text-white w-28 flex-shrink-0 text-xs"
                        >Reasons:</span
                      >
                      <div class="space-y-0.5">
                        <div
                          v-for="reason in step.rejectionReasons"
                          :key="reason"
                          class="text-red-700 bg-red-100 px-1.5 py-0.5 rounded text-xs"
                        >
                          {{ reason }}
                        </div>
                      </div>
                    </div>

                    <div v-if="step.assignedOfficer" class="flex items-center">
                      <span class="font-medium text-white w-28 text-xs">Officer:</span>
                      <span class="text-xs text-white">{{ step.assignedOfficer }}</span>
                    </div>
                  </div>

                  <!-- ICT Officer Update Form -->
                  <div
                    v-if="
                      step.id === 'ict_officer_implementation' &&
                      canUpdateImplementation &&
                      !showUpdateForm
                    "
                    class="mt-2"
                  >
                    <button
                      @click="openUpdateForm"
                      class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                      <svg
                        class="w-3 h-3 mr-1"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                      Update Progress
                    </button>
                  </div>

                  <!-- Progress Update Form -->
                  <div
                    v-if="step.id === 'ict_officer_implementation' && showUpdateForm"
                    class="mt-2 border-t border-gray-200 pt-2"
                  >
                    <div class="bg-gray-50 rounded-lg p-3">
                      <div class="flex items-center justify-between mb-2">
                        <h5 class="text-xs font-medium text-gray-900">
                          Update Implementation Progress
                        </h5>
                        <button @click="closeUpdateForm" class="text-gray-400 hover:text-gray-600">
                          <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"
                            />
                          </svg>
                        </button>
                      </div>

                      <form @submit.prevent="updateImplementationProgress" class="space-y-2">
                        <div>
                          <label
                            for="status"
                            class="block text-xs font-medium text-gray-700 mb-0.5"
                          >
                            Status
                          </label>
                          <select
                            id="status"
                            v-model="updateForm.status"
                            required
                            class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                          >
                            <option value="">Select Status</option>
                            <option value="implementation_in_progress">In Progress</option>
                            <option value="implemented">Implemented</option>
                            <option value="rejected">Rejected</option>
                          </select>
                        </div>

                        <div>
                          <label
                            for="comments"
                            class="block text-xs font-medium text-gray-700 mb-0.5"
                          >
                            Implementation Comments
                          </label>
                          <textarea
                            id="comments"
                            v-model="updateForm.comments"
                            rows="2"
                            placeholder="Add comments about the implementation progress..."
                            class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 resize-none"
                          ></textarea>
                        </div>

                        <div v-if="updateError" class="text-red-600 text-xs">
                          {{ updateError }}
                        </div>

                        <div class="flex justify-end space-x-2">
                          <button
                            type="button"
                            @click="closeUpdateForm"
                            class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                          >
                            Cancel
                          </button>
                          <button
                            type="submit"
                            :disabled="updating || !updateForm.status"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                          >
                            <svg
                              v-if="updating"
                              class="w-3 h-3 mr-1 animate-spin"
                              fill="none"
                              viewBox="0 0 24 24"
                            >
                              <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                              ></circle>
                              <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                              ></path>
                            </svg>
                            <span v-if="updating">Updating...</span>
                            <span v-else>Update Progress</span>
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div
        class="flex justify-between p-4 border-t border-blue-600/40 bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900"
      >
        <!-- Update Progress Button (for assigned ICT Officers) -->
        <div>
          <button
            v-if="canShowUpdateProgressButton"
            @click="openUpdateProgress"
            class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
          >
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            Update Progress
          </button>
        </div>

        <!-- Close Button -->
        <div>
          <button
            @click="close"
            class="px-3 py-1.5 text-xs text-white rounded transition-colors shadow-md"
            style="background-color: #ff0000"
            @mouseenter="$event.target.style.backgroundColor = '#cc0000'"
            @mouseleave="$event.target.style.backgroundColor = '#FF0000'"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import ictOfficerService from '../../services/ictOfficerService'
  import dictAccessService from '../../services/dictAccessService'
  import divisionalAccessService from '../../services/divisionalAccessService'
  import headOfItService from '../../services/headOfItService'
  import combinedAccessService from '../../services/combinedAccessService'
  import { useAuth } from '@/composables/useAuth'

  export default {
    name: 'RequestTimeline',
    props: {
      show: {
        type: Boolean,
        default: false
      },
      requestId: {
        type: [Number, String],
        default: null
      }
    },
    emits: ['close', 'updated', 'openUpdateProgress'],
    data() {
      return {
        loading: false,
        error: null,
        timelineData: null,
        // Digital signatures
        signatures: [],
        hasUserSigned: false,
        isSigning: false,
        // ICT Officer update form
        showUpdateForm: false,
        updateForm: {
          status: '',
          comments: ''
        },
        updating: false,
        updateError: null
      }
    },
    setup() {
      const { hasRole, ROLES, user } = useAuth()
      return {
        hasRole,
        ROLES,
        currentUser: user
      }
    },
    computed: {
      normalizedRequest() {
        const r = this.timelineData?.request || this.timelineData?.request_info || null
        if (!r) return null
        // Debug once per load to see available fields (non-breaking)
        if (process.env.NODE_ENV === 'development') {
          try {
            console.log('🔍 RequestTimeline normalizedRequest source:', {
              keys: Object.keys(r || {}),
              sample: {
                staff_name: r.staff_name,
                pf_number: r.pf_number,
                department: r.department,
                department_name: r.department_name
              }
            })
          } catch (e) {
            // ignore logging errors
          }
        }
        const requestTypes = Array.isArray(r.request_types)
          ? r.request_types
          : Array.isArray(r.request_type)
            ? r.request_type
            : r.request_type_name
              ? [r.request_type_name]
              : r.services || []
        const requestTypeName = Array.isArray(requestTypes)
          ? requestTypes.join(', ')
          : requestTypes || ''
        return {
          id: r.id || r.request_id,
          display_id: r.request_id || (r.id ? `REQ-${String(r.id).padStart(6, '0')}` : ''),
          // Be very defensive: support multiple backend field names used across views
          staff_name:
            r.staff_name ||
            r.staffName ||
            r.full_name ||
            r.name ||
            r.employee_name ||
            r.applicant_name ||
            r.user_name ||
            r.requester_name ||
            r.requester?.name ||
            r.profile?.staff_name ||
            r.profile?.full_name ||
            r.user?.name ||
            '',
          pf_number:
            r.pf_number ||
            r.pfNumber ||
            r.PF_NUMBER ||
            r.pf ||
            r.employee_id ||
            r.employee_no ||
            r.employeeNumber ||
            r.profile?.pf_number ||
            r.profile?.employee_id ||
            r.pf_number_display ||
            '',
          department_name:
            (r.department &&
              (r.department.name || r.department.department_name || r.department.label)) ||
            r.department_name ||
            r.departmentLabel ||
            r.department_label ||
            r.department ||
            r.dept ||
            r.profile?.department_name ||
            r.profile?.department ||
            r.department_id ||
            '',
          request_type_name: requestTypeName,
          access_type_name: r.access_type_name || r.access_type || '',
          created_at: r.created_at || r.submission_date || r.createdAt || null
        }
      },
      isIctOfficer() {
        return this.hasRole(this.ROLES.ICT_OFFICER)
      },

      isIctDirector() {
        return this.hasRole(this.ROLES.ICT_DIRECTOR)
      },

      isDivisionalDirector() {
        return this.hasRole(this.ROLES.DIVISIONAL_DIRECTOR)
      },

      isHeadOfIT() {
        return this.hasRole(this.ROLES.HEAD_OF_IT)
      },

      isHeadOfDepartment() {
        return this.hasRole(this.ROLES.HEAD_OF_DEPARTMENT)
      },

      currentService() {
        // Determine which service to use based on user role
        if (this.isIctOfficer) {
          return ictOfficerService
        } else if (this.isIctDirector) {
          return dictAccessService
        } else if (this.isDivisionalDirector) {
          return divisionalAccessService
        } else if (this.isHeadOfIT) {
          return headOfItService
        } else if (this.isHeadOfDepartment) {
          return combinedAccessService
        } else {
          // Default fallback to ICT Officer service
          return ictOfficerService
        }
      },

      canUpdateImplementation() {
        if (!this.isIctOfficer || !this.timelineData?.request) return false

        const request = this.timelineData.request
        // Check if this ICT officer is assigned to this request and it's not yet completed
        return (
          request.ict_officer_user_id &&
          request.ict_officer_user_id === this.currentUser?.id &&
          !request.ict_officer_implemented_at &&
          request.ict_officer_status !== 'implemented' &&
          request.ict_officer_status !== 'rejected'
        )
      },

      canShowUpdateProgressButton() {
        if (!this.isIctOfficer || !this.timelineData?.request) return false

        const request = this.timelineData.request
        console.log('🔍 RequestTimeline Debug - Request data:', {
          ict_officer_user_id: request.ict_officer_user_id,
          current_user_id: this.currentUser?.id,
          ict_officer_status: request.ict_officer_status,
          status: request.status,
          isIctOfficer: this.isIctOfficer
        })

        // Show button if:
        // 1. User is ICT Officer assigned to this request
        // 2. Request is in a state that allows progress updates (assigned or in progress)
        // 3. Not yet completed or rejected
        const canUpdate =
          request.ict_officer_user_id &&
          request.ict_officer_user_id === this.currentUser?.id &&
          (request.status === 'assigned_to_ict' ||
            request.status === 'implementation_in_progress' ||
            request.ict_officer_status === 'assigned' ||
            request.ict_officer_status === 'implementation_in_progress') &&
          request.ict_officer_status !== 'implemented' &&
          request.ict_officer_status !== 'rejected'

        console.log('🔍 Can show update button:', canUpdate)
        return canUpdate
      },

      isHodApprovalEditable() {
        // Similar logic to both-service-form.vue
        const request = this.timelineData?.request || this.timelineData?.request_info
        if (!request) return false

        // ROLE CHECK: Strict check for HOD
        if (!this.isHeadOfDepartment) return false

        // Normally actionable if current stage is hod
        if (this.currentApprovalStage === 'hod') return true

        // ACTIONABLE SKIP CHECK:
        // If it's currently marked as skipped but not yet approved
        const isHodSkipped = (request.hod_status || '').toLowerCase() === 'skipped'
        const isHodApproved = !!request.hod_approved_at || request.hod_status === 'approved'

        if (isHodSkipped && !isHodApproved) {
          return true
        }

        return false
      },

      isDivisionalApprovalEditable() {
        // Similar logic to both-service-form.vue
        const request = this.timelineData?.request || this.timelineData?.request_info
        if (!request) return false

        // ROLE CHECK: Strict check for Divisional Director
        if (!this.isDivisionalDirector) return false

        // Normally actionable if current stage is divisional
        if (this.currentApprovalStage === 'divisional') return true

        // ACTIONABLE SKIP CHECK:
        // If HOD has approved and it's currently marked as skipped,
        // it's actionable for the Divisional Director to corrective sign.
        const hodStatus = (request.hod_status || '').toLowerCase()
        const isDivSkipped = request.divisional_status === 'skipped'
        const isDivApproved =
          !!request.divisional_approved_at || request.divisional_status === 'approved'

        if (hodStatus === 'approved' && isDivSkipped && !isDivApproved) {
          return true
        }

        return false
      },

      currentApprovalStage() {
        const request = this.timelineData?.request || this.timelineData?.request_info
        if (!request) return null

        const status = (request.status || '').toLowerCase()
        if (status === 'pending' || status === 'pending_hod') return 'hod'
        if (status === 'hod_approved' || status === 'pending_divisional') return 'divisional'
        if (status === 'divisional_approved' || status === 'pending_ict_director')
          return 'ict_director'
        if (status === 'ict_director_approved' || status === 'pending_head_it') return 'head_it'
        if (
          status === 'head_it_approved' ||
          status === 'pending_ict_officer' ||
          status === 'assigned_to_ict' ||
          status === 'implementation_in_progress'
        )
          return 'ict_officer'

        return 'completed'
      },

      timelineSteps() {
        if (!this.timelineData) return []

        const request = this.timelineData.request || this.timelineData.request_info
        if (!request) return []

        const steps = []

        // 1. Request Submission
        steps.push({
          id: 'submission',
          title: 'Request Submitted',
          status: 'completed',
          statusLabel: 'Completed',
          actor: request.staff_name,
          pfNumber: request.pf_number,
          position: 'Requester',
          timestamp: request.created_at,
          comments: null,
          hasSignature: !!request.signature_path
        })

        // 2. HOD Approval
        const hodStatus = this.getApprovalStatus('hod', request)
        const hodActor =
          request.hod_name ||
          request.hod_approved_by_name ||
          request.approved_by_name ||
          'Head of Department'
        const hodTimestamp =
          request.hod_approved_at ||
          request.hod_rejected_at ||
          request.updated_at ||
          request.created_at ||
          null
        steps.push({
          id: 'hod',
          title: 'HOD/BM Approval',
          status: hodStatus.status,
          statusLabel: hodStatus.label,
          actor: hodActor,
          pfNumber: request.hod_pf_number,
          position: 'Head of Department',
          timestamp: hodTimestamp,
          comments: request.hod_comments,
          hasSignature: !!request.hod_signature_path
        })

        // 3. Divisional Director Approval
        const divStatus = this.getApprovalStatus('divisional', request)
        const divisionalActor =
          request.divisional_director_name ||
          request.divisional_name ||
          request.divisional_approved_by_name ||
          request.approved_by_name ||
          'Divisional Director'
        const divisionalTimestamp =
          request.divisional_approved_at ||
          request.divisional_rejected_at ||
          request.updated_at ||
          request.created_at ||
          null
        const divisionalComments =
          request.divisional_director_comments || request.divisional_comments || null
        steps.push({
          id: 'divisional',
          title: 'Divisional Director Approval',
          status: divStatus.status,
          statusLabel: divStatus.label,
          actor: divisionalActor,
          pfNumber: request.divisional_pf_number,
          position: 'Divisional Director',
          timestamp: divisionalTimestamp,
          comments: divisionalComments,
          hasSignature: !!request.divisional_director_signature_path
        })

        // 4. ICT Director Approval
        const ictDirStatus = this.getApprovalStatus('ict_director', request)
        const ictDirectorActor =
          request.ict_director_name ||
          request.dict_director_name ||
          request.ict_director_approved_by_name ||
          request.approved_by_name ||
          'ICT Director'
        const ictDirectorTimestamp =
          request.ict_director_approved_at ||
          request.dict_approved_at ||
          request.updated_at ||
          request.created_at ||
          null
        const ictDirectorComments = request.ict_director_comments || request.dict_comments || null
        steps.push({
          id: 'ict_director',
          title: 'ICT Director Approval',
          status: ictDirStatus.status,
          statusLabel: ictDirStatus.label,
          actor: ictDirectorActor,
          pfNumber: request.ict_director_pf_number,
          position: 'ICT Director',
          timestamp: ictDirectorTimestamp,
          comments: ictDirectorComments,
          hasSignature: !!request.dict_signature_path,
          rejectionReasons: request.ict_director_rejection_reasons || []
        })

        // 5. Head of IT Approval
        const headItStatus = this.getApprovalStatus('head_it', request)
        const headItActor =
          request.head_it_name ||
          request.head_of_it_name ||
          request.head_it_approved_by_name ||
          request.approved_by_name ||
          'Head of IT'
        const headItTimestamp =
          request.head_it_approved_at ||
          request.head_it_rejected_at ||
          request.updated_at ||
          request.created_at ||
          null
        const headItComments = request.head_it_comments || null
        steps.push({
          id: 'head_it',
          title: 'Head of IT Approval',
          status: headItStatus.status,
          statusLabel: headItStatus.label,
          actor: headItActor,
          pfNumber: request.head_it_pf_number,
          position: 'Head of IT',
          timestamp: headItTimestamp,
          comments: headItComments,
          hasSignature: !!request.head_it_signature_path
        })

        // 6. ICT Officer Implementation
        let implementationStatus = 'pending'
        let implementationLabel = 'Waiting'
        let implementationTimestamp = null

        // Use status field as primary source of truth, fall back to timestamps
        if (request.ict_officer_status === 'rejected') {
          implementationStatus = 'rejected'
          implementationLabel = 'Rejected'
          implementationTimestamp = request.ict_officer_rejected_at
        } else if (request.ict_officer_status === 'implemented') {
          implementationStatus = 'completed'
          implementationLabel = 'Implemented'
          implementationTimestamp = this.getValidTimestamp(request.ict_officer_implemented_at)
        } else if (request.ict_officer_status === 'implementation_in_progress') {
          implementationStatus = 'active'
          implementationLabel = 'In Progress'
          implementationTimestamp =
            this.getValidTimestamp(request.ict_officer_started_at) ||
            this.getValidTimestamp(request.ict_officer_assigned_at)
        } else if (request.ict_officer_assigned_at && request.ict_officer_user_id) {
          implementationStatus = 'active'
          implementationLabel = 'Assigned - Pending Implementation'
          implementationTimestamp = this.getValidTimestamp(request.ict_officer_assigned_at)
        }

        // Fallback to overall progress: if request is approved/implemented/completed, mark as completed
        const overallIndex = this.getOverallProgressIndex(request)
        if (overallIndex >= 6 || (overallIndex >= 5 && implementationStatus !== 'rejected')) {
          implementationStatus = 'completed'
          implementationLabel = 'Implemented'
          if (!implementationTimestamp) {
            implementationTimestamp =
              this.getValidTimestamp(request.ict_officer_implemented_at) ||
              this.getValidTimestamp(request.updated_at) ||
              this.getValidTimestamp(request.head_it_approved_at)
          }
        }

        // Build robust officer name, comments, and timestamp fallbacks so details always display
        const officerName =
          request.ict_officer_name ||
          (request.assigned_ict_officer && request.assigned_ict_officer.name) ||
          request.assigned_ict_officer ||
          request.ict_officer ||
          'ICT Department'

        const officerComments =
          request.implementation_comments ||
          request.ict_officer_comments ||
          request.ict_implementation_comments ||
          request.ict_implementation_comment ||
          request.ict_implementation_details ||
          '—'

        const officerTimestamp =
          implementationTimestamp ||
          this.getValidTimestamp(request.ict_officer_implemented_at) ||
          this.getValidTimestamp(request.ict_officer_rejected_at) ||
          this.getValidTimestamp(request.ict_officer_started_at) ||
          this.getValidTimestamp(request.ict_officer_assigned_at) ||
          this.getValidTimestamp(request.updated_at) ||
          this.getValidTimestamp(request.created_at)

        steps.push({
          id: 'ict_officer_implementation',
          title: 'ICT Officer Implementation',
          status: implementationStatus,
          statusLabel: implementationLabel,
          actor: officerName,
          pfNumber: request.ict_officer_pf_number,
          position: 'ICT Officer',
          timestamp: officerTimestamp,
          comments: officerComments,
          hasSignature: !!request.ict_officer_signature_path
        })

        // 8. Final Status (if cancelled)
        if (request.cancelled_at) {
          // Use actual canceller's name and role from backend
          const cancelledByName = request.cancelled_by_name || request.cancelled_by || 'System'
          const cancelledByRole = request.cancelled_by_role
            ? this.formatRoleName(request.cancelled_by_role)
            : 'System'

          steps.push({
            id: 'cancellation',
            title: 'Request Cancelled',
            status: 'rejected',
            statusLabel: 'Cancelled',
            actor: cancelledByName,
            position: cancelledByRole,
            timestamp: request.cancelled_at,
            comments: request.cancellation_reason
          })
        }

        return steps
      }
    },
    watch: {
      show(newVal) {
        if (newVal && this.requestId) {
          this.loadTimeline()
        }
      },
      requestId(newVal, oldVal) {
        if (this.show && newVal && newVal !== oldVal) {
          this.loadTimeline()
        }
      }
    },
    methods: {
      async loadTimeline() {
        if (!this.requestId) return

        // If we already have timeline data for this request and no error, avoid refetching
        if (
          this.timelineData?.request?.id &&
          String(this.timelineData.request.id) === String(this.requestId) &&
          !this.loading &&
          !this.error
        ) {
          return
        }

        this.loading = true
        this.error = null

        try {
          console.log(`🔄 RequestTimeline: Loading timeline with service for role:`, {
            isIctOfficer: this.isIctOfficer,
            isIctDirector: this.isIctDirector,
            isDivisionalDirector: this.isDivisionalDirector,
            isHeadOfIT: this.isHeadOfIT,
            isHeadOfDepartment: this.isHeadOfDepartment
          })

          const id = this.requestId // snapshot to avoid prop changes (e.g., modal close) causing null

          // Build a small failover chain: primary -> optional HOD timeline -> ictOfficerService
          const chain = []
          chain.push(this.currentService)

          // ICT Officers do not have access to HOD /combinedAccessService timeline endpoints, so skip them
          if (!this.isIctOfficer && this.currentService !== combinedAccessService) {
            chain.push(combinedAccessService)
          }

          if (this.currentService !== ictOfficerService) chain.push(ictOfficerService)

          let loaded = false
          let lastError = null

          for (const svc of chain) {
            try {
              const result = await svc.getRequestTimeline(id)

              // Handle different response formats from different services
              if (result && result.success !== undefined) {
                if (result.success) {
                  this.timelineData = result.data
                  loaded = true
                  break
                } else {
                  lastError = new Error(
                    result.error || result.message || 'Failed to load timeline data'
                  )
                }
              } else if (result) {
                this.timelineData = result
                loaded = true
                break
              } else {
                lastError = new Error('No timeline data returned')
              }
            } catch (e) {
              lastError = e
              // Try next service in the chain
            }
          }

          if (!loaded) {
            // Last resort: fetch request details and synthesize a read-only timeline
            let req = null
            try {
              // Prefer HOD-authorized show endpoint first
              const r1 = await combinedAccessService.getHodRequestById(id)
              if (r1?.success && r1.data) req = r1.data
            } catch (e) {
              // ignore and try fallback
            }
            if (!req) {
              try {
                // Fallback to general form endpoint
                const r2a = await combinedAccessService.getRequestById(id)
                if (r2a?.success && r2a.data) req = r2a.data
                if (!req) {
                  const r2 = await ictOfficerService.getAccessRequestById(id)
                  if (r2?.success && r2.data) req = r2.data
                }
              } catch (e) {
                // ignore
              }
            }
            if (req) {
              this.timelineData = { request: req, ict_assignments: [] }
              loaded = true
            }
          }

          if (!loaded) throw lastError || new Error('Failed to load timeline')

          console.log('✅ RequestTimeline: Timeline loaded successfully')

          // Load digital signature history for this document id
          try {
            const docId = this.timelineData?.request?.id || this.timelineData?.request_info?.id
            if (docId) {
              const svc = (await import('@/services/signatureService')).default
              const res = await svc.list(docId)
              this.signatures = Array.isArray(res?.data) ? res.data : []
              const me = this.currentUser?.id
              this.hasUserSigned = !!this.signatures.find((s) => s.user_id === me)
            }
          } catch (e) {
            // Non-fatal
            console.warn('Failed to load digital signatures', e)
            this.signatures = []
          }
        } catch (error) {
          console.error('❌ RequestTimeline: Error loading timeline:', error)
          this.error =
            error.message || error.response?.data?.message || 'Failed to load timeline data'
        } finally {
          this.loading = false
        }
      },

      close() {
        this.$emit('close')
      },

      async handleSignDocument() {
        try {
          this.isSigning = true
          const docId = this.timelineData?.request?.id || this.timelineData?.request_info?.id
          if (!docId) return
          const svc = (await import('@/services/signatureService')).default
          const res = await svc.sign(docId)
          if (res?.data?.success) {
            // Refresh list
            const list = await svc.list(docId)
            this.signatures = Array.isArray(list?.data) ? list.data : []
            this.hasUserSigned = true
          }
        } catch (e) {
          console.error('Digital sign failed', e)
        } finally {
          this.isSigning = false
        }
      },

      openUpdateProgress() {
        console.log('📝 RequestTimeline: Opening update progress modal')
        this.$emit('openUpdateProgress', this.timelineData?.request)
      },

      openUpdateForm() {
        this.showUpdateForm = true
        this.updateForm.status = ''
        this.updateForm.comments = ''
        this.updateError = null
      },

      closeUpdateForm() {
        this.showUpdateForm = false
        this.updateForm.status = ''
        this.updateForm.comments = ''
        this.updateError = null
      },

      async updateImplementationProgress() {
        if (!this.updateForm.status) {
          this.updateError = 'Please select a status'
          return
        }

        this.updating = true
        this.updateError = null

        try {
          const result = await ictOfficerService.updateImplementationProgress(
            this.requestId,
            this.updateForm.status,
            this.updateForm.comments
          )

          if (result.success) {
            // Refresh the timeline data
            await this.loadTimeline()

            // Close the form
            this.closeUpdateForm()

            // Emit event to parent to refresh main table
            this.$emit('updated')

            // Show success message
            this.showSuccessMessage()
          } else {
            this.updateError = result.message || 'Failed to update progress'
          }
        } catch (error) {
          console.error('Error updating progress:', error)
          this.updateError = error.message || 'Network error while updating progress'
        } finally {
          this.updating = false
        }
      },

      showSuccessMessage() {
        // Create a temporary success message
        const successDiv = document.createElement('div')
        successDiv.className =
          'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300'
        successDiv.innerHTML = `
        <div class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          Progress updated successfully!
        </div>
      `

        document.body.appendChild(successDiv)

        // Remove after 3 seconds
        setTimeout(() => {
          if (successDiv.parentNode) {
            successDiv.style.transform = 'translateX(100%)'
            successDiv.style.opacity = '0'
            setTimeout(() => {
              document.body.removeChild(successDiv)
            }, 300)
          }
        }, 3000)
      },

      getApprovalStatus(stage, request) {
        const statusField = `${stage}_status`
        const approvedField =
          stage === 'ict_officer' ? `${stage}_implemented_at` : `${stage}_approved_at`

        const status = request[statusField]
        const approvedAt = request[approvedField]

        let result = {}

        if (status === 'rejected') {
          result = { status: 'rejected', label: 'Rejected' }
        } else if (status === 'skipped' && (stage === 'divisional' || stage === 'hod')) {
          // Stage intentionally skipped (e.g., ICT Officer-origin request)
          // BUT check if it's actionable by the current user (e.g., HOD or Divisional Director correcting a skip)
          if (
            (stage === 'divisional' && this.isDivisionalApprovalEditable) ||
            (stage === 'hod' && this.isHodApprovalEditable)
          ) {
            result = { status: 'active', label: 'Pending' }
          } else {
            result = { status: 'completed', label: 'Skipped' }
          }
        } else if (
          status === 'approved' ||
          (stage === 'ict_officer' && status === 'implemented') ||
          approvedAt
        ) {
          result = {
            status: 'completed',
            label: stage === 'ict_officer' ? 'Implemented' : 'Approved'
          }
        } else {
          // Fallback to overall progress when per-stage fields are missing
          const progress = this.getOverallProgressIndex(request)
          const stageIndexMap = {
            hod: 1,
            divisional: 2,
            ict_director: 3,
            head_it: 4,
            ict_officer: 5
          }
          const stageIndex = stageIndexMap[stage] || 0

          if (progress > stageIndex) {
            result = {
              status: 'completed',
              label: stage === 'ict_officer' ? 'Implemented' : 'Approved'
            }
          } else if (progress === stageIndex) {
            // Active stage
            const activeLabel =
              stage === 'ict_officer' && request.ict_officer_status === 'implementation_in_progress'
                ? 'In Progress'
                : 'Pending'
            result = { status: 'active', label: activeLabel }
          } else if (this.isPreviousStageComplete(stage, request)) {
            result = { status: 'active', label: 'Pending' }
          } else {
            result = { status: 'pending', label: 'Waiting' }
          }
        }

        // For ICT Officer assignment status
        if (stage === 'ict_officer') {
          if (request.ict_officer_assigned_at && request.ict_officer_user_id) {
            result.assignmentStatus = 'completed'
            result.assignmentLabel = 'Assigned'
          } else if (request.head_it_approved_at) {
            result.assignmentStatus = 'active'
            result.assignmentLabel = 'Pending Assignment'
          } else {
            result.assignmentStatus = 'pending'
            result.assignmentLabel = 'Waiting'
          }
        }

        return result
      },

      getOverallProgressIndex(request) {
        const s = (request.status || '').toString()
        // Map overall status to a stage index
        // 0: submitted, 1: HOD approved, 2: Divisional approved, 3: ICT Director approved,
        // 4: Head IT approved, 5: ICT Officer stage, 6: Final approved/completed
        const map = {
          pending: 0,
          pending_hod: 0,
          hod_approved: 1,
          pending_divisional: 1,
          divisional_approved: 2,
          pending_ict_director: 2,
          ict_director_approved: 3,
          dict_approved: 3,
          pending_head_it: 3,
          head_it_approved: 4,
          pending_ict_officer: 4,
          implementation_in_progress: 5,
          implemented: 5,
          approved: 6,
          completed: 6
        }
        return map[s] ?? 0
      },

      isPreviousStageComplete(currentStage, request) {
        const stages = ['hod', 'divisional', 'ict_director', 'head_it', 'ict_officer']
        const currentIndex = stages.indexOf(currentStage)

        if (currentIndex === 0) return true // HOD is always first

        const previousStage = stages[currentIndex - 1]
        const previousStatusField = `${previousStage}_status`
        const previousApprovedField =
          previousStage === 'ict_officer'
            ? `${previousStage}_implemented_at`
            : `${previousStage}_approved_at`

        return (
          request[previousStatusField] === 'approved' ||
          (previousStage === 'ict_officer' && request[previousStatusField] === 'implemented') ||
          !!request[previousApprovedField]
        )
      },

      getStepIconClasses(status) {
        switch (status) {
          case 'completed':
            return 'bg-green-500 border-green-500'
          case 'rejected':
            return 'bg-red-500 border-red-500'
          case 'pending':
            return 'bg-yellow-500 border-yellow-500'
          case 'active':
            return 'bg-blue-500 border-blue-500'
          default:
            return 'bg-gray-200 border-gray-300'
        }
      },

      getStatusClasses(status) {
        const s = (status || '').toString().toLowerCase()
        if (['completed', 'approved', 'assigned', 'implemented'].includes(s)) {
          return 'bg-green-600 text-white'
        }
        if (['rejected', 'cancelled'].includes(s)) {
          return 'bg-red-600 text-white'
        }
        if (['pending', 'pending assignment', 'waiting'].includes(s)) {
          // Use exact red via inline style; keep text white here
          return 'text-white'
        }
        return 'bg-gray-500 text-white'
      },

      getStatusStyle(status) {
        const s = (status || '').toString().toLowerCase()
        if (['pending', 'pending assignment', 'waiting'].includes(s)) {
          return { backgroundColor: '#FF0000' }
        }
        return {}
      },

      getValidTimestamp(timestamp) {
        if (!timestamp) return null

        try {
          const date = new Date(timestamp)
          // Check if date is valid and not too far in the past/future
          if (isNaN(date.getTime()) || date.getFullYear() < 2020 || date.getFullYear() > 2030) {
            console.warn('Invalid timestamp detected:', timestamp)
            return null
          }
          return timestamp
        } catch (error) {
          console.warn('Error parsing timestamp:', timestamp, error)
          return null
        }
      },

      formatDateTime(dateString) {
        if (!dateString) return '—'
        try {
          const date = new Date(dateString)
          return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          })
        } catch (e) {
          return dateString
        }
      },

      formatRoleName(role) {
        const roleMap = {
          head_of_department: 'Head of Department',
          divisional_director: 'Divisional Director',
          ict_director: 'ICT Director',
          head_of_it: 'Head of IT',
          ict_officer: 'ICT Officer',
          admin: 'Administrator',
          staff: 'Staff'
        }
        return roleMap[role] || role
      }
    }
  }
</script>

<style scoped>
  /* Custom scrollbar for timeline content */
  .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
  }

  .overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
  }

  .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
  }

  .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
  }
  /* Typography scale for timeline modal */
  .timeline-typography {
    font-size: 1.05rem;
  }
  .timeline-typography .text-xs {
    font-size: 1.1rem !important;
    line-height: 1.5 !important;
  }
  .timeline-typography .text-sm {
    font-size: 1.25rem !important;
  }
  .timeline-typography .text-base {
    font-size: 1.375rem !important;
  }
  .timeline-typography h2 {
    font-size: 1.8rem !important;
  }
  .timeline-typography h3 {
    font-size: 1.5rem !important;
  }
  .timeline-typography h4 {
    font-size: 1.375rem !important;
  }
</style>
