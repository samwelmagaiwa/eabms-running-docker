<!-- eslint-disable vue/no-parsing-error -->
<!-- eslint-disable prettier/prettier -->
<template>
  <div class="flex flex-col h-screen">
    <Header />
    <div class="flex flex-1 overflow-hidden">
      <ModernSidebar />
      <main
        :class="[
          'flex-1 bg-gradient-to-br from-blue-900 via-blue-800 to-teal-900 overflow-y-auto relative',
          isReviewMode
            ? 'p-1'
            : isDivisionalDirectorUser
              ? 'p-1'
              : isHodApprovalEditable
                ? 'p-1'
                : 'p-1'
        ]"
      >
        <!-- Medical Background Pattern -->
        <div class="absolute inset-0 overflow-hidden">
          <!-- Medical Cross Pattern -->
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
          <!-- Floating medical icons -->
          <div class="absolute inset-0">
            <div
              v-for="i in 15"
              :key="i"
              class="absolute text-white opacity-10 animate-float"
              :style="{
                left: Math.random() * 100 + '%',
                top: Math.random() * 100 + '%',
                animationDelay: Math.random() * 3 + 's',
                animationDuration: Math.random() * 3 + 2 + 's',
                fontSize: Math.random() * 20 + 10 + 'px'
              }"
            >
              <i
                :class="[
                  'fas',
                  ['fa-heartbeat', 'fa-user-md', 'fa-hospital', 'fa-stethoscope', 'fa-plus'][
                    Math.floor(Math.random() * 5)
                  ]
                ]"
              ></i>
            </div>
          </div>
        </div>

        <!-- Top-center toast -->
        <div v-if="toast && toast.show" class="fixed top-4 left-1/2 -translate-x-1/2 z-[9999]">
          <div class="px-4 py-2 rounded-lg shadow-xl border border-white/20 text-white bg-green-600/90"
               :class="toast.type === 'error' ? 'bg-red-600/90' : 'bg-green-600/90'">
            <span class="text-xs font-semibold">{{ toast.message }}</span>
          </div>
        </div>

        <!-- Loading Banner -->
        <UnifiedLoadingBanner 
          :show="isLoading"
          :loadingTitle="getLoadingTitle()"
          :loadingSubtitle="getLoadingSubtitle()"
          departmentTitle="ACCESS REQUEST APPROVAL SYSTEM"
        />

        <div
          :class="[
            isReviewMode ? 'max-w-full mx-1' : 'max-w-7xl mx-auto',
            'relative z-10',
            isReviewMode ? 'review-mode-compact' : '',
            isReviewMode && isHeadItUser ? 'head-it-compact' : '',
            !isReviewMode && isDivisionalDirectorUser ? 'divisional-director-compact' : '',
            !isReviewMode && isIctDirectorUser ? 'ict-director-compact' : '',
            !isReviewMode && isHodApprovalEditable ? 'hod-compact' : ''
          ]"
        >
          <!-- Header Section - Reduced height for divisional directors and ICT directors -->
          <div
            :class="[
              'medical-glass-card rounded-t-3xl border-b border-blue-300/30',
              isReviewMode
                ? 'p-1 mb-0'
                : isDivisionalDirectorUser
                  ? 'p-0.5 mb-0'
                  : isIctDirectorUser
                    ? 'p-1.5 mb-0'
                    : isHodApprovalEditable
                      ? 'p-1 mb-0'
                      : 'p-1 mb-0'
            ]"
          >
            <div class="flex justify-between items-center">
              <!-- Left Logo - Increased sizes for better visibility -->
              <div
                :class="[
                  'mr-2 transform hover:scale-110 transition-transform duration-300',
                  isReviewMode
                    ? 'w-14 h-14'
                    : isDivisionalDirectorUser
                      ? 'w-14 h-14'
                      : isIctDirectorUser
                        ? 'w-16 h-16'
                        : isHodApprovalEditable
                          ? 'w-16 h-16'
                          : 'w-18 h-18'
                ]"
              >
                <div
                  class="w-full h-full bg-gradient-to-br from-blue-500/20 to-teal-500/20 rounded-2xl backdrop-blur-sm border-2 border-blue-300/40 flex items-center justify-center shadow-2xl hover:shadow-blue-500/25"
                >
                  <img
                    src="/assets/images/ngao2.png"
                    alt="National Shield"
                    :class="
                      isReviewMode
                        ? 'max-w-12 max-h-12'
                        : isDivisionalDirectorUser
                          ? 'max-w-12 max-h-12'
                          : isIctDirectorUser
                            ? 'max-w-14 max-h-14'
                            : isHodApprovalEditable
                              ? 'max-w-14 max-h-14'
                              : 'max-w-16 max-h-16'
                    "
                    class="object-contain"
                  />
                </div>
              </div>

              <!-- Center Content - Reduced margins for divisional directors -->
              <div class="text-center flex-1">
                <h1
                  class="text-lg font-bold text-white tracking-wide drop-shadow-lg animate-fade-in"
                  :class="
                    isReviewMode
                      ? 'mb-0'
                      : isDivisionalDirectorUser
                        ? 'mb-0'
                        : isIctDirectorUser
                          ? 'mb-0.5'
                          : isHodApprovalEditable
                            ? 'mb-0.5'
                            : 'mb-1'
                  "
                >
                  MUHIMBILI NATIONAL HOSPITAL
                </h1>
                <div
                  :class="[
                    'relative inline-block',
                    isReviewMode
                      ? 'mb-0'
                      : isDivisionalDirectorUser
                        ? 'mb-0'
                        : isIctDirectorUser
                          ? 'mb-1'
                          : isHodApprovalEditable
                            ? 'mb-1'
                            : 'mb-1'
                  ]"
                >
                  <div
                    :class="[
                      'bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-full text-sm font-bold shadow-xl transform hover:scale-105 transition-all duration-300 border-2 border-blue-400/60',
                      isReviewMode
                        ? 'px-2 py-0'
                        : isDivisionalDirectorUser
                          ? 'px-2 py-0'
                          : isIctDirectorUser
                            ? 'px-3 py-0.5'
                            : isHodApprovalEditable
                              ? 'px-4 py-1'
                              : 'px-4 py-1'
                    ]"
                  >
                    <span class="relative z-10 flex items-center gap-2">
                      <i class="fas fa-layer-group"></i>
                      COMBINED ACCESS REQUEST
                    </span>
                    <div
                      class="absolute inset-0 bg-gradient-to-r from-blue-700 to-blue-800 rounded-full opacity-0 hover:opacity-100 transition-opacity duration-300"
                    ></div>
                  </div>
                </div>
                <h2
                  class="text-sm font-bold text-blue-100 tracking-wide drop-shadow-md animate-fade-in-delay"
                >
                  {{ isReviewMode ? 'REQUEST REVIEW - ' + displayRequestId : 'UNIFIED SERVICES FORM' }}
                </h2>
              </div>

              <!-- Right Logo - Increased sizes for better visibility -->
              <div
                :class="[
                  'ml-2 transform hover:scale-110 transition-transform duration-300',
                  isReviewMode
                    ? 'w-14 h-14'
                    : isDivisionalDirectorUser
                      ? 'w-14 h-14'
                      : isIctDirectorUser
                        ? 'w-16 h-16'
                        : isHodApprovalEditable
                          ? 'w-16 h-16'
                          : 'w-18 h-18'
                ]"
              >
                <div
                  class="w-full h-full bg-gradient-to-br from-teal-500/20 to-blue-500/20 rounded-2xl backdrop-blur-sm border-2 border-teal-300/40 flex items-center justify-center shadow-2xl hover:shadow-teal-500/25"
                >
                  <img
                    src="/assets/images/logo2.png"
                    alt="Muhimbili Logo"
                    :class="
                      isReviewMode
                        ? 'max-w-12 max-h-12'
                        : isDivisionalDirectorUser
                          ? 'max-w-12 max-h-12'
                          : isIctDirectorUser
                            ? 'max-w-14 max-h-14'
                            : isHodApprovalEditable
                              ? 'max-w-14 max-h-14'
                              : 'max-w-16 max-h-16'
                    "
                    class="object-contain"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Main Form -->
          <div class="medical-glass-card rounded-b-3xl overflow-hidden">
            <form
              @submit.prevent="onSubmit"
              :class="[
                isReviewMode
                  ? 'p-1 space-y-1'
                  : isDivisionalDirectorUser
                    ? 'p-1 space-y-1'
                    : isIctDirectorUser
                      ? 'p-1.5 space-y-1.5'
                      : isHodApprovalEditable
                        ? 'p-1.5 space-y-1.5'
                        : 'p-0.5 space-y-0.5',
                { 'review-mode': isReviewMode }
              ]"
            >
              <div
                :class="[
                  'flex-1 grid grid-cols-1 min-h-0',
                  isReviewMode
                    ? 'gap-1'
                    : isDivisionalDirectorUser
                      ? 'gap-1'
                      : isIctDirectorUser
                        ? 'gap-2'
                        : isHodApprovalEditable
                          ? 'gap-2'
                          : 'gap-1'
                ]"
              >
                <!-- Left: shared + selectors -->
                <div
                  aria-labelledby="applicant-details"
                  :class="[
                    'xl:col-span-4',
                    isReviewMode
                      ? 'space-y-0.5'
                      : isDivisionalDirectorUser
                        ? 'space-y-0.5'
                        : isIctDirectorUser
                          ? 'space-y-1'
                          : isHodApprovalEditable
                            ? 'space-y-1'
                            : 'space-y-0.5'
                  ]"
                >
                  <!-- Personal Information Section -->
                  <div
                    :class="[
                      'medical-card bg-gradient-to-r from-blue-600/25 to-blue-700/25 border-2 border-blue-400/40 rounded-lg backdrop-blur-sm hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 group',
                      isReviewMode
                        ? 'p-0.5'
                        : isDivisionalDirectorUser
                          ? 'p-0.5'
                          : isIctDirectorUser
                            ? 'p-1'
                            : isHodApprovalEditable
                              ? 'p-1'
                              : 'p-0.5'
                    ]"
                  >
                    <div
                      :class="[
                        'flex items-center space-x-2',
                        isReviewMode
                          ? 'mb-0.5'
                          : isDivisionalDirectorUser
                            ? 'mb-1'
                            : isHodApprovalEditable
                              ? 'mb-1'
                              : 'mb-0.5'
                      ]"
                    >
                      <div
                        class="w-6 h-6 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 border border-blue-300/50"
                      >
                        <i class="fas fa-user-md text-white text-sm"></i>
                      </div>
                      <h3 class="text-xs font-bold text-white flex items-center">
                        <i class="fas fa-id-card mr-1 text-blue-300 text-xs"></i>
                        Personal Information
                      </h3>
                    </div>
                    <div
                      :class="[
                        'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
                        isDivisionalDirectorUser
                          ? 'gap-2'
                          : isHodApprovalEditable
                            ? 'gap-3'
                            : 'gap-1'
                      ]"
                    >
                      <div>
                        <label class="block text-xs font-medium text-blue-100 mb-1">
                          PF Number <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                          <input
                            v-model.trim="form.shared.pfNumber"
                            type="text"
                            :readonly="isReviewMode || isFormSectionReadOnly"
                            class="medical-input personal-info-input w-full px-2 py-1 bg-white/15 border-2 border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm transition-all duration-300 hover:bg-white/20 focus:bg-white/20 focus:shadow-lg focus:shadow-blue-500/20 text-xs"
                            :class="{ 'font-bold': form.shared.pfNumber }"
                            placeholder="PF Number"
                            required
                          />
                          <div
                            class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/10 to-blue-600/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                          ></div>
                        </div>
                        <p v-if="errors.pfNumber" class="error text-red-400 text-sm mt-1">
                          {{ errors.pfNumber }}
                        </p>
                      </div>

                      <div>
                        <label class="block text-xs font-medium text-blue-100 mb-1">
                          Staff Name <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                          <input
                            v-model.trim="form.shared.staffName"
                            type="text"
                            :readonly="isReviewMode || isFormSectionReadOnly"
                            class="medical-input personal-info-input w-full px-2 py-1 bg-white/15 border-2 border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm transition-all duration-300 hover:bg-white/20 focus:bg-white/20 focus:shadow-lg focus:shadow-blue-500/20 text-xs"
                            :class="{ 'font-bold': form.shared.staffName }"
                            placeholder="Full name"
                            required
                          />
                          <div
                            class="absolute inset-0 rounded-lg bg-gradient-to-r from-blue-500/10 to-blue-600/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                          ></div>
                        </div>
                        <p v-if="errors.staffName" class="error text-red-400 text-sm mt-1">
                          {{ errors.staffName }}
                        </p>
                      </div>

                      <div>
                        <label class="block text-xs font-medium text-blue-100 mb-1">
                          Department <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                          <input
                            v-model.trim="form.shared.department"
                            type="text"
                            :readonly="isReviewMode || isFormSectionReadOnly"
                            class="medical-input personal-info-input w-full px-2 py-1 bg-white/15 border-2 border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm transition-all duration-300 hover:bg-white/20 focus:bg-white/20 focus:shadow-lg focus:shadow-blue-500/20 text-xs"
                            :class="{ 'font-bold': form.shared.department }"
                            placeholder="Department"
                            required
                          />
                          <div
                            class="absolute inset-0 rounded-lg bg-gradient-to-r from-blue-500/10 to-blue-600/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                          ></div>
                        </div>
                      </div>

                      <div>
                        <label class="block text-xs font-medium text-blue-100 mb-1">
                          Contact Number <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                          <input
                            v-model.trim="form.shared.phone"
                            type="tel"
                            :readonly="isReviewMode || isFormSectionReadOnly"
                            class="medical-input personal-info-input w-full px-2 py-1 bg-white/15 border-2 border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm transition-all duration-300 hover:bg-white/20 focus:bg-white/20 focus:shadow-lg focus:shadow-blue-500/20 text-xs"
                            :class="{ 'font-bold': form.shared.phone }"
                            placeholder="e.g. 0712 000 000"
                            @blur="form.shared.phone = normalizePhoneNumber(form.shared.phone)"
                            required
                          />
                          <div
                            class="absolute inset-0 rounded-lg bg-gradient-to-r from-blue-500/10 to-blue-600/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                          ></div>
                        </div>
                      </div>
                    </div>

                    <!-- Bottom row: Module Requested for (left), Access Rights (middle), and Signature (right) -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4 items-stretch">
                      <!-- Module Requested for Section (Left Column) -->
                      <div class="flex flex-col h-full">
                        <div
                          class="bg-white/10 rounded-lg p-2 border border-blue-300/30 backdrop-blur-sm w-full min-h-[85px] flex flex-col justify-center"
                          :class="{
                            'opacity-50': isReviewMode && !hasWellsoftRequest && !hasJeevaRequest
                          }"
                        >
                          <label
                            class="block text-xs font-bold text-blue-100 mb-1 text-center uppercase"
                          >
                            <i class="fas fa-toggle-on mr-1 text-blue-300 text-xs"></i>
                            Module Requested for
                            <span class="text-red-400">*</span>
                            <span
                              v-if="isReviewMode && !hasWellsoftRequest && !hasJeevaRequest && !isHodApprovalEditable"
                              class="text-xs px-2 py-1 bg-gray-500/30 rounded-full text-gray-300"
                            >
                              <i class="fas fa-lock text-xs mr-1"></i>
                              Not Applicable
                            </span>
                          </label>
                          <div class="flex items-center gap-3 justify-center">
                            <label
                              class="flex items-center cursor-pointer px-2 py-1 rounded-lg transition-all border min-w-12"
                              :class="{
                                'hover:bg-blue-500/20': !isFormSectionReadOnly,
                                'bg-red-500/30 border-2 border-red-400/50 text-white shadow-lg ring-2 ring-red-400/40':
                                  isFormSectionReadOnly && wellsoftRequestType === 'use',
                                'bg-white/5 border-white/10 text-blue-200/80':
                                  isFormSectionReadOnly && wellsoftRequestType !== 'use',
                                'pointer-events-none':
                                  isReviewMode && !hasWellsoftRequest && !hasJeevaRequest
                              }"
                            >
                              <input
                                v-model="wellsoftRequestType"
                                type="radio"
                                value="use"
                              :disabled="
                                  (isReviewMode && !hasWellsoftRequest && !hasJeevaRequest && !isHodApprovalEditable) ||
                                  isFormSectionReadOnly
                                "
                                :class="[
                                  'w-3 h-3 border-blue-300 focus:ring-blue-500 mr-1',
                                  isFormSectionReadOnly
                                    ? wellsoftRequestType === 'use'
                                      ? 'text-amber-500 accent-amber-500'
                                      : 'text-blue-400 accent-blue-400'
                                    : 'text-blue-600'
                                ]"
                              />
                              <span
                                class="text-xs font-semibold flex items-center"
                                :class="{
                                  'text-white':
                                    isFormSectionReadOnly && wellsoftRequestType === 'use',
                                  'text-blue-100':
                                    !isFormSectionReadOnly || wellsoftRequestType !== 'use'
                                }"
                              >
                                <i
                                  class="fas fa-plus-circle mr-1 text-xs"
                                  :class="{
                                    'text-white':
                                      isFormSectionReadOnly && wellsoftRequestType === 'use',
                                    'text-green-400':
                                      !isFormSectionReadOnly || wellsoftRequestType !== 'use'
                                  }"
                                ></i>
                                Use
                              </span>
                            </label>
                            <label
                              class="flex items-center cursor-pointer px-2 py-1 rounded-lg transition-all border min-w-12"
                              :class="{
                                'hover:bg-red-500/20': !isFormSectionReadOnly,
                                'bg-red-500/30 border-2 border-red-400/50 text-white shadow-lg ring-2 ring-red-400/40':
                                  isFormSectionReadOnly && wellsoftRequestType === 'revoke',
                                'bg-white/5 border-white/10 text-blue-200/80':
                                  isFormSectionReadOnly && wellsoftRequestType !== 'revoke',
                                'pointer-events-none':
                                  isReviewMode && !hasWellsoftRequest && !hasJeevaRequest
                              }"
                            >
                              <input
                                v-model="wellsoftRequestType"
                                type="radio"
                                value="revoke"
                              :disabled="
                                  (isReviewMode && !hasWellsoftRequest && !hasJeevaRequest && !isHodApprovalEditable) ||
                                  isFormSectionReadOnly
                                "
                                :class="[
                                  'w-3 h-3 border-blue-300 focus:ring-blue-500 mr-1',
                                  isFormSectionReadOnly
                                    ? wellsoftRequestType === 'revoke'
                                      ? 'text-amber-500 accent-amber-500'
                                      : 'text-blue-400 accent-blue-400'
                                    : 'text-blue-600'
                                ]"
                              />
                              <span
                                class="text-xs font-semibold flex items-center"
                                :class="{
                                  'text-white':
                                    isFormSectionReadOnly && wellsoftRequestType === 'revoke',
                                  'text-blue-100':
                                    !isFormSectionReadOnly || wellsoftRequestType !== 'revoke'
                                }"
                              >
                                <i
                                  class="fas fa-minus-circle mr-1 text-xs"
                                  :class="{
                                    'text-white':
                                      isFormSectionReadOnly && wellsoftRequestType === 'revoke',
                                    'text-red-400':
                                      !isFormSectionReadOnly || wellsoftRequestType !== 'revoke'
                                  }"
                                ></i>
                                Revoke
                              </span>
                            </label>
                          </div>
                        </div>
                      </div>

                      <!-- Access Rights Section (Middle) -->
                      <div class="flex flex-col h-full">
                        <!-- HOD Access Rights Interface -->
                        <div
                          v-if="isHodApprovalEditable"
                          class="bg-white/10 rounded-lg border border-blue-300/30 p-2 w-full min-h-[85px] flex flex-col justify-center"
                        >
                          <!-- Access Rights Section -->
                          <div>
                            <label class="block text-xs font-bold text-blue-100 mb-1 text-center">
                              <i class="fas fa-clock mr-1"></i>
                              Please Specify Access Rights:
                            </label>
                            <div class="space-y-0.5 text-left">
                              <label class="flex items-center cursor-pointer text-xs">
                                <input
                                  v-model="hodAccessType"
                                  type="radio"
                                  value="permanent"
                                  class="w-3 h-3 text-blue-600 mr-2"
                                />
                                <span class="text-blue-200">Permanent (until retirement)</span>
                              </label>
                              <label class="flex items-center cursor-pointer text-xs">
                                <input
                                  v-model="hodAccessType"
                                  type="radio"
                                  value="temporary"
                                  class="w-3 h-3 text-blue-600 mr-2"
                                />
                                <span class="text-blue-200">Temporary Until:</span>
                                <input
                                  v-if="hodAccessType === 'temporary'"
                                  v-model="hodTemporaryUntil"
                                  type="date"
                                  :min="tomorrow"
                                  class="ml-2 px-1 py-0.5 bg-white/10 border border-blue-300/40 rounded text-xs text-white focus:ring-1 focus:ring-blue-400"
                                />
                              </label>
                            </div>
                          </div>
                        </div>

                        <!-- Read-only Access Rights display for non-HOD editing views -->
                        <div
                          v-else
                          class="bg-white/10 rounded-lg border border-blue-300/30 p-2 w-full min-h-[85px] flex flex-col justify-center"
                        >
                          <label class="block text-xs font-bold text-blue-100 mb-1 text-center">
                            <i class="fas fa-user-shield mr-1"></i>
                            Access Rights
                          </label>
                          <div class="flex items-center justify-center gap-3">
                            <span
                              v-if="(requestData?.access_type || hodAccessType) === 'permanent'"
                              class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-bold bg-red-500/30 text-white border-2 border-red-400/50 shadow-lg ring-2 ring-red-400/40"
                            >
                              <i class="fas fa-infinity mr-1 text-white"></i>
                              Permanent (until retirement)
                            </span>
                            <span
                              v-else
                              class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-bold bg-red-500/30 text-white border-2 border-red-400/50 shadow-lg ring-2 ring-red-400/40"
                            >
                              <i class="fas fa-hourglass-half mr-1 text-white"></i>
                              Temporary Until:
                              <span class="ml-1 font-bold">
                                {{ hodTemporaryUntil || formatDateForInput(requestData?.temporary_until) || 'N/A' }}
                              </span>
                            </span>
                          </div>
                        </div>
                      </div>

                      <!-- Signature Section -->
                      <div class="flex flex-col h-full">
                        <div
                          class="relative bg-white/10 rounded-lg border border-blue-300/30 p-2 w-full min-h-[85px] flex flex-col justify-center"
                        >
                          <label class="block text-xs font-bold text-blue-100 mb-1 text-center">
                            Signature <span class="text-red-400">*</span>
                          </label>

                          <!-- Unified digital signature status and action (review and edit) -->
                          <div
                            class="w-full px-1 py-0.5 border-2 rounded-lg backdrop-blur-sm transition-all duration-300 shadow-lg flex items-center justify-center min-h-[28px]"
                            :class="(hasSignature || hasUserSigned) ? 'border-green-400/50 bg-green-500/10' : 'border-blue-300/40 bg-white/15 hover:shadow-xl hover:shadow-blue-500/20'"
                          >
                            <!-- Loading state -->
                            <div v-if="loading" class="text-center">
                              <OrbitingDots size="sm" />
                              <p class="text-blue-100 text-xs mt-0.5">Loading signature...</p>
                            </div>
                            <!-- Loaded state -->
                            <div v-else class="text-center">
                              <div v-if="hasSignature || hasUserSigned" class="flex flex-col items-center gap-0.5">
                                <i class="fas fa-check-circle text-green-400 text-sm"></i>
                                <p class="text-green-300 text-xs font-bold">
                                  Digitally signed
                                  <span v-if="signatureDisplay?.name"> by {{ signatureDisplay.name }}</span>
                                </p>
                                <p v-if="signatureDisplay?.at" class="text-green-200 text-[10px]">
                                  on {{ formatDateTime(signatureDisplay.at) }}
                                </p>
                              </div>
                              <div v-else class="flex flex-col items-center">
                                <i class="fas fa-signature text-blue-300 text-sm"></i>
                                <p class="text-blue-100 text-xs mt-0.5">No signature yet</p>
                                <button
                                  type="button"
                                  @click="signCurrentDocument"
                                  :disabled="isSigning"
                                  class="px-2 py-0.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-xs font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 flex items-center gap-1 mx-auto shadow-lg hover:shadow-xl transform hover:scale-105 border border-blue-400/50 mt-1"
                                >
                                  <i class="fas fa-pen-alt"></i>
                                  Sign Document
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- HOD Module Selection Section - Jeeva and Wellsoft Side by Side -->
                    <!-- Show to HOD users when they can edit OR when viewing staff's request in review mode -->
                    <!-- Always show both Jeeva and Wellsoft sections so HOD can see all modules (selected and unselected) -->
                    <div
                      v-if="isHodApprovalEditable || (isReviewMode && getUserRole()?.toLowerCase() === 'head_of_department')"
                      class="mt-2"
                    >
                      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 relative">
                        <!-- Vertical Divider Line -->
                        <div
                          class="hidden lg:block absolute left-2/3 top-0 bottom-0 w-px bg-gradient-to-b from-blue-300/30 via-blue-400/50 to-blue-300/30 transform -translate-x-px"
                        ></div>

                        <!-- Jeeva Modules (Left Side - Takes 2 columns) -->
                        <!-- Always show to HOD so they can see all modules even if service wasn't selected -->
                        <div class="lg:col-span-2">
                          <div
                            class="bg-white/10 rounded-lg border border-purple-300/30 p-4 w-full h-auto"
                          >
                            <div class="mb-1">
                              <label
                                class="block text-sm font-semibold text-purple-200 mb-1 flex items-center justify-between"
                              >
                                <span>
                                  <i class="fas fa-database mr-2"></i>
                                  Jeeva Modules
                                </span>
                                <span class="text-xs text-purple-300/70">
                                  Selected: {{ selectedJeeva?.length || 0 }} modules
                                </span>
                              </label>
                            </div>
                            <div
                              class="grid grid-cols-4 gap-1 border border-purple-300/20 rounded p-2"
                            >
                              <label
                                v-for="module in jeevaModules"
                                :key="'jeeva-' + module"
                                :class="[
                                  'flex items-center text-xs p-0.5 rounded transition-colors leading-none',
                                  isFormSectionReadOnly ? 'cursor-default' : 'cursor-pointer hover:bg-white/10',
                                  selectedJeeva.includes(module) && isFormSectionReadOnly 
                                    ? 'bg-red-500/30 border-2 border-red-400/50 shadow-md ring-1 ring-red-400/40' 
                                    : selectedJeeva.includes(module) 
                                      ? 'bg-purple-500/20 border border-purple-400/30' 
                                      : 'border border-transparent'
                                ]"
                              >
                                <input
                                  v-model="selectedJeeva"
                                  :value="module"
                                  type="checkbox"
                                  :disabled="isFormSectionReadOnly"
                                  class="w-3 h-3 text-red-600 accent-red-600 mr-1 flex-shrink-0"
                                />
                                <span 
                                  :class="[
                                    'text-xs leading-none',
                                    selectedJeeva.includes(module) && isFormSectionReadOnly 
                                      ? 'text-red-100 font-semibold' 
                                      : 'text-purple-100'
                                  ]"
                                >{{
                                  module
                                }}</span>
                                <i 
                                  v-if="selectedJeeva.includes(module) && isFormSectionReadOnly"
                                  class="fas fa-check-circle text-red-400 text-xs ml-auto"
                                  title="Selected by staff"
                                ></i>
                              </label>
                            </div>
                          </div>
                        </div>

                        <!-- Wellsoft Modules (Right Side - Takes 1 column) -->
                        <!-- Always show to HOD so they can see all modules even if service wasn't selected -->
                        <div class="lg:col-span-1">
                          <div
                            class="bg-white/10 rounded-lg border border-amber-300/30 p-4 w-full h-auto"
                          >
                            <div class="mb-3">
                              <label
                                class="block text-sm font-semibold text-amber-200 mb-3 flex items-center justify-between"
                              >
                                <span>
                                  <i class="fas fa-hospital mr-2"></i>
                                  Wellsoft Modules
                                </span>
                                <span class="text-xs text-amber-300/70">
                                  Selected: {{ selectedWellsoft?.length || 0 }} modules
                                </span>
                              </label>
                            </div>
                            <div
                              class="grid grid-cols-2 gap-2 border border-amber-300/20 rounded p-3"
                            >
                              <label
                                v-for="module in wellsoftModules"
                                :key="'wellsoft-' + module"
                                :class="[
                                  'flex items-center text-xs p-1.5 rounded transition-colors',
                                  isFormSectionReadOnly ? 'cursor-default' : 'cursor-pointer hover:bg-white/10',
                                  selectedWellsoft.includes(module) && isFormSectionReadOnly 
                                    ? 'bg-red-500/30 border-2 border-red-400/50 shadow-md ring-1 ring-red-400/40' 
                                    : selectedWellsoft.includes(module) 
                                      ? 'bg-amber-500/20 border border-amber-400/30' 
                                      : 'border border-transparent'
                                ]"
                              >
                                <input
                                  v-model="selectedWellsoft"
                                  :value="module"
                                  type="checkbox"
                                  :disabled="isFormSectionReadOnly"
                                  class="w-3 h-3 text-red-600 accent-red-600 mr-2 flex-shrink-0"
                                />
                                <span 
                                  :class="[
                                    'text-xs leading-tight',
                                    selectedWellsoft.includes(module) && isFormSectionReadOnly 
                                      ? 'text-red-100 font-semibold' 
                                      : 'text-amber-100'
                                  ]"
                                >{{
                                  module
                                }}</span>
                                <i 
                                  v-if="selectedWellsoft.includes(module) && isFormSectionReadOnly"
                                  class="fas fa-check-circle text-red-400 text-xs ml-auto"
                                  title="Selected by staff"
                                ></i>
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- HOD Comments + Internet Purposes Section (with visual divider and gap) -->
                    <!-- Show to HOD users or when not in review mode or when HOD can edit -->
                    <!-- Hide in strict view mode and during loading -->
                    <div class="mt-4 mb-2" v-if="!loading && !isStrictViewMode && (!isReviewMode || isHodApprovalEditable || (isReviewMode && getUserRole()?.toLowerCase() === 'head_of_department'))">
                      <div class="medical-card bg-gradient-to-r from-blue-600/15 to-indigo-600/15 border-2 border-blue-400/40 rounded-lg p-3">
                        <div :class="['grid items-start', hasInternetRequest ? 'grid-cols-1 lg:grid-cols-2 gap-6' : 'grid-cols-1 gap-3']">
                          <!-- Left: Internet Purposes (own outer wrap) -->
                          <div v-if="hasInternetRequest" class="h-full">
                            <div class="bg-white/5 rounded-lg border border-blue-300/20 p-3 h-full">
                              <div class="text-xs text-blue-100">
                                <strong class="mr-2">Internet Purposes</strong>
                                <span v-if="!hasInternetRequest">Not Requested</span>
                                <ul v-else class="space-y-1 mt-1">
                                  <li
                                    v-for="(p, i) in filteredInternetPurposes.slice(0, 4)"
                                    :key="'ip-' + i"
                                    class="flex items-start gap-2"
                                  >
                                    <span
                                      class="w-5 h-5 inline-flex items-center justify-center rounded-full bg-white/10 border border-blue-300/30 text-blue-200 text-xs font-semibold flex-shrink-0"
                                    >
                                      {{ i + 1 }}
                                    </span>
                                    <span class="truncate">{{ p }}</span>
                                  </li>
                                </ul>
                              </div>
                            </div>
                          </div>
                          <!-- Right: HOD Comments (own outer wrap) -->
                          <div class="h-full">
                            <div class="bg-white/5 rounded-lg border border-blue-300/20 p-3 h-full">
                              <label class="block text-blue-100 text-sm mb-1">
                                HOD Comments <span class="text-red-400">*</span>
                              </label>
                              <textarea
                                v-model="hodComments"
                                :disabled="false"
                                required
                                class="w-full h-20 bg-white/10 border border-blue-300/30 rounded-lg p-2 text-white placeholder-blue-300/60 text-sm resize-none overflow-hidden"
                                placeholder="HOD specify access category here..."
                                rows="3"
                              ></textarea>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                  <!-- Previous Comments Section - Show modules summary and comments for all users in review mode -->
                  <!-- Always show Review Summary to provide visibility of what was originally requested -->

                  <div
                    v-if="requestData && !loading"
                    v-show="!loading"
                    :class="[
                      'medical-card bg-gradient-to-r from-amber-600/15 to-orange-600/15 border-2 border-amber-400/40 rounded-lg backdrop-blur-sm hover:shadow-lg hover:shadow-amber-500/20 transition-all duration-300 group mb-4',
                      isReviewMode && isIctDirectorUser
                        ? 'p-0.5'
                        : isDivisionalDirectorUser || isIctDirectorUser
                          ? 'p-1'
                          : isHodApprovalEditable
                            ? 'p-1.5'
                            : 'p-1'
                    ]"
                  >
                    <div class="flex items-center justify-between mb-1">
                      <div class="flex items-center space-x-2">
                        <div
                          class="w-5 h-5 bg-gradient-to-br from-amber-500 to-orange-600 rounded flex items-center justify-center shadow group-hover:scale-105 transition-transform duration-200 border border-amber-300/50"
                        >
                          <i class="fas fa-comments text-white text-xs"></i>
                        </div>
                        <span
                          class="text-xs px-2 py-0.5 bg-amber-500/30 rounded-full text-amber-200 font-medium"
                        >
                          {{ (previousComments && previousComments.length) || 0 }}
                        </span>
                      </div>
                      <h3 class="text-xs font-bold text-white flex items-center">
                        <i class="fas fa-history mr-1 text-amber-300 text-xs"></i>
                        Review Summary
                        <span
                          v-if="isRequestFullyCompleted"
                          class="ml-2 text-xs px-2 py-0.5 bg-green-500/30 rounded-full text-green-300 border border-green-400/30"
                        >
                          <i class="fas fa-check-circle mr-1 text-xs"></i>
                          Completed
                        </span>
                      </h3>
                    </div>

                    <!-- Skeleton loader for initial load -->
                    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-2">
                      <!-- Skeleton for modules summary -->
                      <div class="bg-white/5 rounded-lg border border-amber-300/20 animate-pulse">
                        <div class="h-12 bg-amber-600/20 rounded-t-lg"></div>
                        <div class="p-4 space-y-3">
                          <div class="h-4 bg-white/10 rounded w-3/4"></div>
                          <div class="h-4 bg-white/10 rounded w-1/2"></div>
                          <div class="h-4 bg-white/10 rounded w-2/3"></div>
                        </div>
                      </div>
                      <!-- Skeleton for comments table -->
                      <div class="bg-white/5 rounded-lg border border-amber-300/20 animate-pulse">
                        <div class="h-12 bg-amber-600/20 rounded-t-lg"></div>
                        <div class="p-4 space-y-2">
                          <div class="h-3 bg-white/10 rounded w-full"></div>
                          <div class="h-3 bg-white/10 rounded w-5/6"></div>
                          <div class="h-3 bg-white/10 rounded w-4/5"></div>
                        </div>
                      </div>
                    </div>

                    <!-- Two-column layout: Modules Summary + Comments Table -->
                    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-2 mb-1">
                      <!-- Left: Selected Modules Summary -->
                      <div class="lg:col-span-1">
                        <div
                          class="bg-blue-700/20 rounded-lg border border-amber-300/30 backdrop-blur-sm overflow-hidden"
                        >
                          <!-- Header -->
                          <div
                            class="border-b border-amber-300/30 p-1"
                            style="background-color: #0047ab"
                          >
                            <h4 class="text-xs font-medium text-white flex items-center leading-none">
                              <i class="fas fa-list-check mr-1 text-white text-xs"></i>
                              Selected Modules Summary
                            </h4>
                          </div>

                          <!-- Tabular Content -->
                          <div class="p-1">
                            <table class="w-full">
                              <thead>
                                <tr class="border-b border-amber-300/20">
                                  <th
                                    class="text-left py-0.5 px-1.5 text-xs font-medium text-blue-200 uppercase tracking-wide"
                                  >
                                    <i class="fas fa-laptop mr-1 text-xs"></i>Wellsoft
                                  </th>
                                  <th
                                    class="text-left py-0.5 px-1.5 text-xs font-medium text-cyan-200 uppercase tracking-wide"
                                  >
                                    <i class="fas fa-box mr-1 text-xs"></i>Jeeva
                                  </th>
                                  <th
                                    class="text-left py-0.5 px-1.5 text-xs font-medium text-green-200 uppercase tracking-wide"
                                  >
                                    <i class="fas fa-globe mr-1 text-xs"></i>Internet
                                  </th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr class="align-top">
                                  <!-- Wellsoft Modules Column -->
                                  <td
                                    class="py-2 px-2 border-r border-amber-300/20 vertical-align-top"
                                  >
                                    <div
                                      v-if="!hasWellsoftRequest"
                                      class="text-xs text-gray-400 italic"
                                    >
                                      Not Requested
                                    </div>
                                    <div
                                      v-else-if="selectedWellsoft.length === 0"
                                      class="text-xs text-gray-400 italic"
                                    >
                                      No modules selected
                                    </div>
                                    <div v-else class="space-y-0.5">
                                      <div
                                        v-for="module in selectedWellsoft"
                                        :key="'well-' + module"
                                        class="flex items-center space-x-1.5"
                                      >
                                        <i class="fas fa-check-circle text-red-400 text-xs"></i>
                                        <span class="text-xs text-blue-100 font-normal">{{
                                          module
                                        }}</span>
                                      </div>
                                    </div>
                                  </td>

                                  <!-- Jeeva Modules Column -->
                                  <td
                                    class="py-2 px-2 border-r border-amber-300/20 vertical-align-top"
                                  >
                                    <div
                                      v-if="!hasJeevaRequest"
                                      class="text-xs text-gray-400 italic"
                                    >
                                      Not Requested
                                    </div>
                                    <div
                                      v-else-if="selectedJeeva.length === 0"
                                      class="text-xs text-gray-400 italic"
                                    >
                                      No modules selected
                                    </div>
                                    <div v-else class="space-y-0.5">
                                      <div
                                        v-for="module in selectedJeeva"
                                        :key="'jeeva-' + module"
                                        class="flex items-center space-x-1.5"
                                      >
                                        <i class="fas fa-check-circle text-red-400 text-xs"></i>
                                        <span class="text-xs text-cyan-100 font-medium">{{
                                          module
                                        }}</span>
                                      </div>
                                    </div>
                                  </td>

                                  <!-- Internet Purpose Column -->
                                  <td class="py-2 px-2 vertical-align-top">
                                    <div
                                      v-if="!hasInternetRequest"
                                      class="text-xs text-gray-400 italic"
                                    >
                                      Not Requested
                                    </div>
                                    <div
                                      v-else-if="!filteredInternetPurposes.length"
                                      class="text-xs text-gray-400 italic"
                                    >
                                      No purposes specified
                                    </div>
                                    <div v-else class="space-y-0.5">
                                      <div
                                        v-for="(purpose, index) in filteredInternetPurposes.slice(
                                          0,
                                          4
                                        )"
                                        :key="'purpose-' + index"
                                        class="flex items-start space-x-1.5"
                                      >
                                        <div
                                          class="w-3 h-3 bg-green-500/30 rounded-full flex items-center justify-center flex-shrink-0"
                                        >
                                          <span class="text-xs text-green-300 font-bold">{{
                                            index + 1
                                          }}</span>
                                        </div>
                                        <span
                                          class="text-xs text-green-100 font-medium leading-tight"
                                        >{{ purpose }}</span>
                                      </div>
                                      <div
                                        v-if="filteredInternetPurposes.length > 4"
                                        class="text-xs text-green-200/70 italic"
                                      >
                                        +{{ filteredInternetPurposes.length - 4 }} more
                                      </div>
                                    </div>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>

                      <!-- Right: Comments Table -->
                      <div class="lg:col-span-1">
                        <div
                          class="bg-white/10 rounded-lg border border-amber-300/30 backdrop-blur-sm overflow-hidden"
                        >
                          <!-- Table Header -->
                          <div
                            class="border-b border-amber-300/30 p-1"
                            style="background-color: #0047ab"
                          >
                            <div
                              class="grid grid-cols-12 gap-0 text-[10px] leading-none font-medium text-white uppercase tracking-wide"
                            >
                              <div class="col-span-2">Name</div>
                              <div class="col-span-2">Role</div>
                              <div class="col-span-4">Comments</div>
                              <div class="col-span-2">Status</div>
                              <div class="col-span-2">Date</div>
                            </div>
                          </div>

                          <!-- Table Body with Virtual Scrolling -->
                          <div class="overflow-visible">
                            <!-- Show message if no comments -->
                            <div
                              v-if="!previousComments || previousComments.length === 0"
                              class="p-4 text-center"
                            >
                              <div class="text-amber-300/70 text-xs italic">
                                <i class="fas fa-info-circle mr-1"></i>
                                No previous comments available
                              </div>
                            </div>
                            <!-- Show only visible comments for performance -->
                            <div
                              v-for="(comment, index) in visibleComments"
                              :key="comment.stage + '-' + index"
                              :class="rowClass(comment, index)"
                            >
                              <!-- Name -->
                              <div class="col-span-2">
                                <div class="flex items-center space-x-0.5">
                                  <div
                                    :class="[
                                      'w-4 h-4 rounded-full flex items-center justify-center text-xs flex-shrink-0',
                                      comment.isApproved
                                        ? 'bg-green-500/30 text-green-300'
                                        : comment.isRejected
                                          ? 'bg-red-500/30 text-red-300'
                                          : 'bg-blue-500/30 text-blue-300'
                                    ]"
                                  >
                                    <i
                                      :class="[
                                        'text-xs',
                                        comment.isApproved
                                          ? 'fas fa-check'
                                          : comment.isRejected
                                            ? 'fas fa-times'
                                            : 'fas fa-clock'
                                      ]"
                                    ></i>
                                  </div>
                                  <div class="text-[9px] leading-[1.05] text-white font-normal truncate">
                                    {{ comment.name || 'Name not available' }}
                                  </div>
                                </div>
                              </div>

                              <!-- Role -->
                              <div class="col-span-2">
                                <div class="text-[9px] leading-[1.05] text-amber-200 font-normal">
                                  {{ comment.stageName }}
                                </div>
                              </div>

                              <!-- Comments -->
                              <div class="col-span-4">
                                <div
                                  :class="[
                                    'p-0 rounded text-[8px] leading-[1.05] border',
                                    comment.isApproved
                                      ? 'bg-green-900/20 border-green-400/30 text-green-100'
                                      : comment.isRejected
                                        ? 'bg-red-900/20 border-red-400/30 text-red-100'
                                        : 'bg-blue-900/20 border-blue-400/30 text-blue-100'
                                  ]"
                                >
                                  {{ comment.comments }}
                                </div>
                              </div>

                              <!-- Status -->
                              <div class="col-span-2">
                                <span
                                  :class="[
                                    'inline-flex items-center px-1 py-0 rounded text-[9px] font-normal',
                                    comment.isApproved
                                      ? 'bg-green-600/30 text-green-200 border border-green-400/30'
                                      : comment.isRejected
                                        ? 'bg-red-600/30 text-red-200 border border-red-400/30'
                                        : 'bg-blue-600/30 text-blue-200 border border-blue-400/30'
                                  ]"
                                >
                                  <i
                                    :class="[
                                      'mr-1 text-xs',
                                      comment.isApproved
                                        ? 'fas fa-check-circle'
                                        : comment.isRejected
                                          ? 'fas fa-times-circle'
                                          : 'fas fa-clock'
                                    ]"
                                  ></i>
                                  {{ comment.isApproved ? 'OK' : comment.isRejected ? 'X' : 'Rev' }}
                                </span>
                              </div>

                              <!-- Date -->
                              <div class="col-span-2">
                                <div class="flex items-center text-[9px] leading-[1.05] text-gray-400">
                                  <i
                                    :class="[
                                      'fas mr-1',
                                      comment.hasSpecificDate
                                        ? 'fa-calendar text-gray-400'
                                        : 'fa-clock text-yellow-400'
                                    ]"
                                  ></i>
                                  <div
                                    v-if="comment.date"
                                    :class="comment.hasSpecificDate ? '' : 'text-yellow-300'"
                                  >
                                    {{ formatCommentDate(comment.date) }}
                                    <div v-if="!comment.hasSpecificDate" class="opacity-70 text-xs">
                                      (approx.)
                                    </div>
                                  </div>
                                  <div v-else class="text-yellow-300">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    <div class="text-xs">Date unavailable</div>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <!-- Show count if comments are truncated -->
                            <div
                              v-if="
                                previousComments && previousComments.length > maxVisibleComments
                              "
                              class="p-2 text-center border-t border-amber-300/20"
                            >
                              <span class="text-xs text-amber-300/70 italic">
                                Showing {{ maxVisibleComments }} of
                                {{ previousComments.length }} comments
                              </span>
                            </div>
                          </div>


                          <!-- Read-only notice -->
                          <div
                            class="border-t border-amber-300/30 p-1"
                            style="background-color: #0047ab"
                          >
                            <div class="flex items-center justify-center text-xs text-white">
                              <i class="fas fa-lock mr-1 text-xs"></i>
                              <span>Read-only</span>
                            </div>
                          </div>
                          <!-- Role comment editor inside right column, flush at bottom -->
<div class="mt-3">
                            <div
                              v-if="!( (getUserRole() || '').toLowerCase() === 'ict_officer' || (getUserRole() || '').toLowerCase() === 'officer_ict')"
                              class="rounded-lg p-2 border border-blue-300/30"
                              style="background-color: #0047ab"
                            >
                              <textarea
                                v-model="roleCommentsDraft"
                                :readonly="!isRoleCommentEditable"
                                :placeholder="roleCommentLabel"
                                class="w-full h-20 border border-blue-300/20 text-blue-100 placeholder-blue-200/70 rounded p-2 text-base focus:outline-none focus:ring-1 focus:ring-blue-400 resize-none role-comment-textarea"
                                style="background-color: #0047ab"
                              ></textarea>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

          <!-- Grant Access Popup (ICT Officer) -->
          <div v-if="showGrantAccessPopup" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white/10 rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden border border-blue-300/40 backdrop-blur-md">
              <div class="px-5 py-3" style="background-color:red">
                <h3 class="text-white text-2xl font-bold flex items-center gap-2">
                  <i class="fas fa-user-shield"></i>
                  Grant Access
                </h3>
                <p class="text-blue-100 text-base mt-1">Provide your implementation note before granting access.</p>
              </div>
              <div class="px-5 py-4 bg-blue-900/20">
                <label class="block text-blue-100 text-lg mb-1">ICT Officer Comment <span class="text-red-400">*</span></label>
                <textarea
                  v-model="grantAccessComment"
                  class="w-full h-28 bg-white/10 border border-blue-300/30 rounded-lg p-2 text-white placeholder-blue-300/60 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-blue-400"
                  placeholder="Enter implementation details (max 1000 characters)..."
                  rows="5"
                  required
                ></textarea>
                <div class="flex justify-between mt-1">
                  <span class="text-xs text-blue-200/70">Remaining: {{ Math.max(0, 1000 - (grantAccessComment || '').length) }}</span>
                  <span class="text-xs text-blue-200/80">
                    {{ (grantAccessComment || '').length }}/1000
                  </span>
                </div>
                <p v-if="grantAccessError" class="text-red-300 text-xs mt-1">{{ grantAccessError }}</p>
                
                <!-- SMS Preview -->
                <div class="mt-4 p-3 bg-blue-800/40 border border-blue-300/30 rounded-lg">
                  <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-sms text-blue-300 text-lg"></i>
                    <label class="text-blue-100 text-base font-semibold">SMS Preview (will be sent to requester):</label>
                  </div>
                  <div class="bg-blue-900/50 rounded p-2 text-base text-blue-100 font-mono leading-relaxed">
                    {{ getSmsPreviewText() }}
                  </div>
                  <div class="flex justify-between mt-1">
                    <span class="text-xs text-blue-200/70">Length: {{ getSmsPreviewText().length }} chars</span>
                    <span class="text-xs text-blue-200/70">(Long messages may be split into multiple SMS parts)</span>
                  </div>
                  <p class="text-blue-300/70 text-base mt-2 italic">
                    <i class="fas fa-info-circle mr-1"></i>
                    This message will be sent to {{ form.staff_name || 'the requester' }} at {{ form.phone_number || 'their phone number' }}
                  </p>
                </div>
              </div>
              <div class="px-5 py-3 flex gap-2 justify-end bg-blue-900/30 border-t border-blue-300/30">
                <button @click="cancelGrantAccess" type="button" class="px-3 py-1.5 rounded-lg text-white bg-gray-600 hover:bg-gray-700 text-sm">Cancel</button>
                <button @click="confirmGrantAccess" type="button" :disabled="processing || !grantAccessComment.trim()" class="px-3 py-1.5 rounded-lg text-white text-sm border border-blue-300/50 shadow disabled:opacity-50"
                  style="background:linear-gradient(135deg,#0f52ba 0%,#1e3a8a 100%)">
                  <i class="fas fa-key mr-1"></i>
                  Grant Access
                </button>
              </div>
            </div>
          </div>

          <!-- Head of IT Approval Success Modal -->
          <div v-if="showHeadItApproveSuccessModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
              <div class="px-6 py-4 text-center" style="background: linear-gradient(135deg, #0f52ba 0%, #1e3a8a 100%)">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
                  <i class="fas fa-check text-2xl" style="color: #0f52ba"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Request Approved</h3>
                <p class="text-blue-100 text-sm mt-1">The request has been approved successfully.</p>
              </div>
              <div class="px-6 py-6 text-center">
                <p class="text-gray-700 text-base mb-6">Click below to assign this task to an ICT Officer.</p>
                <div class="flex gap-3">
                  <button @click="redirectToAssignIctOfficer" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-semibold transition duration-200">
                    Assign to ICT Officer
                  </button>
                  <button @click="showHeadItApproveSuccessModal = false" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 px-4 rounded-lg transition duration-200">
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>

                  <!-- Approval Section -->
                  <div
                    :class="[
                      'medical-card bg-gradient-to-r from-blue-600/25 to-blue-700/25 border-2 border-blue-400/40 rounded-lg backdrop-blur-sm hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 group',
                      isReviewMode && isIctDirectorUser
                        ? 'p-0.5'
                        : isDivisionalDirectorUser || isIctDirectorUser
                          ? 'p-1'
                          : isHodApprovalEditable
                            ? 'p-1.5'
                            : 'p-1'
                    ]"
                  >
                    <div class="flex items-center mb-1">
                      <div class="flex items-center space-x-2">
                        <div
                          class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 border border-blue-300/50"
                        >
                          <i class="fas fa-check-circle text-white text-sm"></i>
                        </div>
                        <h3
                          :class="[
                            'text-sm font-bold text-white flex items-center',
                            isStrictViewMode ? 'select-none' : 'cursor-pointer select-none'
                          ]"
                          @click="!isStrictViewMode && (showApprovalSections = !showApprovalSections)"
                          :aria-expanded="showApprovalSections ? 'true' : 'false'"
                          :aria-controls="'approval-sections'"
                          :title="isStrictViewMode ? 'Approval information' : 'Toggle approval sections'"
                        >
                          <i class="fas fa-clipboard-check mr-1 text-blue-300"></i>
                          <span>{{ isStrictViewMode ? 'Approvals' : 'Click here to Approve' }}</span>
                          <i
                            v-if="!isStrictViewMode"
                            :class="['fas', showApprovalSections ? 'fa-chevron-up' : 'fa-chevron-down']"
                            class="ml-1"
                          ></i>
                        </h3>
                      </div>
                    </div>

                    <div
                      id="approval-sections"
                      v-show="isStrictViewMode || showApprovalSections"
                      :class="[
                        'grid grid-cols-1 lg:grid-cols-3',
                        isDivisionalDirectorUser
                          ? 'gap-3'
                          : isHodApprovalEditable
                            ? 'gap-4'
                            : 'gap-1'
                      ]"
                    >
                      <!-- HoD/BM -->
                      <div
                        :class="[
                          'bg-white/15 rounded-lg border border-blue-300/30 backdrop-blur-sm',
                          isDivisionalDirectorUser ? 'p-3' : isHodApprovalEditable ? 'p-3' : 'p-2'
                        ]"
                      >
                        <h5
                          class="font-bold text-white mb-1 text-center text-base flex items-center justify-center gap-2"
                        >
                          <i class="fas fa-user-tie mr-2 text-blue-300"></i>
                          HoD/BM
                          <span
                            v-if="isStageCompleted('hod')"
                            class="text-base px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-check text-base mr-1"></i>
                            Completed
                          </span>
                          <span
                            v-else-if="isHodSkipped"
                            class="text-base px-2 py-1 bg-red-500/30 rounded-full text-red-300"
                          >
                            <i class="fas fa-ban text-base mr-1"></i>
                            Skipped
                          </span>
                          <span
                            v-else-if="!isHodApprovalEditable && isReviewMode"
                            class="text-base px-2 py-1 bg-gray-500/30 rounded-full text-gray-300"
                          >
                            <i class="fas fa-clock text-base mr-1"></i>
                            Pending
                          </span>
                        </h5>
                        <div class="space-y-3">
                          <div>
                            <label class="block text-base font-semibold text-white mb-1">Name<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.approvals.hod.name"
                                type="text"
                                readonly
                                :placeholder="getApprovalNamePlaceholder('hod')"
                                class="medical-input w-full px-3 py-2 bg-white/30 border-2 border-blue-300/50 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-100/80 backdrop-blur-sm cursor-not-allowed font-medium"
                                :title="getApprovalNameTitle('hod')"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i
                                  class="fas fa-lock text-blue-300 text-xs"
                                  title="This field is auto-populated from your account"
                                ></i>
                              </div>
                            </div>
                          </div>
                          <div>
                            <label class="block text-base font-semibold text-white mb-1">Signature<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <!-- Debug: Show which template condition is active -->
                              <!-- {{ console.log('🖼️ HOD Template Debug:', { shouldShowHodSignedIndicator, shouldShowHodNoSignatureIndicator, hodSignaturePreview, isReviewMode }) }} -->

                              <!-- Show 'Signed' indicator for when HOD has signed (visible to later roles) -->
                              <div
                                v-if="shouldShowHodSignedIndicator"
                                :class="[
                                  'w-full px-3 py-2 rounded-xl backdrop-blur-sm transition-all duration-300 min-h-[35px] flex items-center justify-center relative',
                                  'border-2 border-green-400/50 bg-green-500/10 shadow-lg',
                                  isIctDirectorApprovalActive
                                    ? 'ring-1 ring-green-300/40 shadow-xl'
                                    : ''
                                ]"
                                :title="getSignedByYouTooltip('hod')"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-check text-green-400 text-sm"></i>
                                    </div>
                                    <span
                                      :class="[
                                        'text-base',
                                        isIctDirectorApprovalActive
                                          ? 'text-green-300 font-extrabold uppercase tracking-wide'
                                          : 'text-green-400 font-semibold'
                                      ]"
                                      >Signed</span>
                                  </div>
                                  <p
                                    :class="[
                                      isIctDirectorApprovalActive
                                        ? 'text-green-200 font-semibold text-base'
                                        : 'text-green-300/80 text-base'
                                    ]"
                                  >
                                    Approved at: {{ getApprovalDateFormatted('hod') }}
                                  </p>
                                </div>
                                <!-- Optional: Show signature preview icon -->
                                <div class="absolute top-2 right-2">
                                  <div
                                    class="w-6 h-6 bg-green-500/30 rounded-full flex items-center justify-center"
                                  >
                                    <i
                                      class="fas fa-signature text-green-400 text-xs"
                                      title="Signature on file"
                                    ></i>
                                  </div>
                                </div>
                              </div>

                              <!-- Skipped indicator for HOD when request is from ICT Officer -->
                              <div
                                v-else-if="isHodSkipped"
                                class="w-full px-3 py-2 border-2 border-red-400/60 rounded-xl bg-red-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[35px] flex items-center justify-center"
                                title="HOD approval skipped"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                      <i class="fas fa-ban text-red-400 text-sm"></i>
                                    </div>
                                    <span class="text-red-400 font-semibold text-base">Signature skipped</span>
                                  </div>
                                  <p class="text-red-300/80 text-base">HOD approval skipped</p>
                                </div>
                              </div>

                              <!-- Default read-only indicator for next stage when HOD has not signed -->
                              <div
                                v-else-if="shouldShowHodNoSignatureIndicator"
                                class="w-full px-3 py-2 border-2 border-red-400/50 rounded-xl bg-red-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[35px] flex items-center justify-center"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-times text-red-400 text-sm"></i>
                                    </div>
                                    <span class="text-red-400 font-semibold text-base">No signature on file</span>
                                  </div>
                                  <p class="text-red-300/80 text-base">HOD approval pending</p>
                                </div>
                              </div>

                              <!-- Edit mode: Show uploaded signature preview -->
                              <div
                                v-else-if="hodSignaturePreview"
                                class="w-full px-2 py-2 border-2 border-blue-300/40 rounded-lg bg-white/15 backdrop-blur-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-blue-500/20 min-h-[35px] flex items-center justify-center relative"
                              >
                                <div v-if="isImage(hodSignaturePreview)" class="text-center">
                                  <img
                                    :src="hodSignaturePreview"
                                    alt="HOD Signature"
                                    class="max-h-[50px] max-w-full object-contain mx-auto mb-1"
                                  />
                                  <p class="text-xs text-blue-100">
                                    {{ hodSignatureFileName }}
                                  </p>
                                </div>
                                <div v-else class="text-center">
                                  <div
                                    class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center mx-auto mb-1"
                                  >
                                    <i class="fas fa-file-pdf text-red-400 text-lg"></i>
                                  </div>
                                  <p class="text-xs text-blue-100">
                                    {{ hodSignatureFileName }}
                                  </p>
                                </div>

                                <div
                                  v-if="isHodApprovalEditable"
                                  class="absolute top-2 right-2 flex gap-1"
                                >
                                  <button
                                    type="button"
                                    @click="triggerHodSignatureUpload"
                                    class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-blue-600 transition-colors duration-200 shadow-lg"
                                    title="Change signature"
                                  >
                                    <i class="fas fa-edit"></i>
                                  </button>
                                  <button
                                    type="button"
                                    @click="clearHodSignature"
                                    class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-200 shadow-lg"
                                    title="Remove signature"
                                  >
                                    <i class="fas fa-times"></i>
                                  </button>
                                </div>
                              </div>

                              <!-- Default signature upload area (for HODs when they can edit) -->
                              <div
                                v-else
                                class="w-full px-3 py-2 border-2 border-dashed border-blue-300/40 rounded-lg focus-within:border-blue-400 bg-white/15 backdrop-blur-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-blue-500/20 min-h-[30px] flex items-center justify-center hover:bg-white/20"
                              >
                                <!-- Display signature status with proper styling like others -->
                                <div
                                  :class="[
                                    'w-full px-3 py-2 rounded-xl backdrop-blur-sm transition-all duration-300 min-h-[35px] flex items-center justify-center relative',
                                    requestData?.approvals?.hod?.signature_status === 'Signed'
                                      ? 'border-2 border-green-400/50 bg-green-500/10 shadow-lg'
                                      : 'border-2 border-red-400/50 bg-red-500/10 shadow-lg'
                                  ]"
                                >
                                  <div class="text-center">
                                    <div class="flex items-center justify-center space-x-2 mb-1">
                                      <div
                                        :class="[
                                          'w-8 h-8 rounded-full flex items-center justify-center',
                                          requestData?.approvals?.hod?.signature_status === 'Signed'
                                            ? 'bg-green-500/20'
                                            : 'bg-red-500/20'
                                        ]"
                                      >
                                        <i
                                          :class="[
                                            'text-sm',
                                            requestData?.approvals?.hod?.signature_status ===
                                            'Signed'
                                              ? 'fas fa-check text-green-400'
                                              : 'fas fa-times text-red-400'
                                          ]"
                                        ></i>
                                      </div>
                                      <span
                                        :class="[
                                          'text-sm font-semibold',
                                          requestData?.approvals?.hod?.signature_status === 'Signed'
                                            ? 'text-green-400'
                                            : 'text-red-400'
                                        ]"
                                      >
                                        {{
                                          requestData?.approvals?.hod?.signature_display ||
                                          'No signature'
                                        }}
                                      </span>
                                    </div>
                                    <!-- Optional: Show signature preview icon for signed status -->
                                    <div
                                      v-if="
                                        requestData?.approvals?.hod?.signature_status === 'Signed'
                                      "
                                      class="absolute top-2 right-2"
                                    >
                                      <div
                                        class="w-6 h-6 bg-green-500/30 rounded-full flex items-center justify-center"
                                      >
                                        <i
                                          class="fas fa-signature text-green-400 text-xs"
                                          title="Signature on file"
                                        ></i>
                                      </div>
                                    </div>
                                  </div>
                                </div>

                                <!-- Keep upload button section for when user can edit and signature is not already uploaded -->
                                <div v-if="requestData?.approvals?.hod?.signature_status !== 'Signed' && !hasUserSigned && !(currentUser?.name && historyHasSigner(currentUser.name))" class="text-center mt-2">
                                  <button
                                    v-if="canUploadHodSignature"
                                    type="button"
                                    @click="signCurrentDocument"
                                    :disabled="isSigning || !isReviewMode"
                                    class="px-3 py-1.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-sm font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 flex items-center gap-1 mx-auto shadow-lg hover:shadow-xl transform hover:scale-105 border border-blue-400/50"
                                  >
                                    <i class="fas fa-pen-alt"></i>
                                    Sign Document
                                  </button>
                                  <p v-if="!isReviewMode" class="text-xs text-blue-200 mt-1">Available after submitting the request</p>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div>
                            <label class="block text-base font-semibold text-white mb-1">Date (mm/dd/yyyy)<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.approvals.hod.date"
                                type="date"
                                :readonly="true"
:class="[
                                  'medical-input w-full px-3 py-2 bg-white/30 border-2 rounded-lg focus:outline-none text-white backdrop-blur-sm cursor-not-allowed font-medium',
                                  shouldShowHodSignedIndicator
                                    ? 'border-green-400/60 ring-1 ring-green-300/40'
                                    : '',
                                  shouldShowHodNoSignatureIndicator ? 'border-red-400/40' : '',
                                  isHodSkipped ? 'border-red-400/60 bg-red-500/10' : '',
                                  isIctDirectorApprovalActive && shouldShowHodSignedIndicator
                                    ? 'font-semibold text-green-200'
                                    : ''
                                ]"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i
                                  v-if="isHodSkipped"
                                  class="fas fa-ban text-red-400 text-xs"
                                  title="HOD approval skipped"
                                ></i>
                                <i
                                  v-else-if="shouldShowHodSignedIndicator"
                                  class="fas fa-check text-green-400 text-xs"
                                  title="HOD has signed - date populated from approval"
                                ></i>
                                <i
                                  v-else-if="shouldShowHodNoSignatureIndicator"
                                  class="fas fa-clock text-red-400 text-xs"
                                  title="Pending HOD approval"
                                ></i>
                                <i
                                  v-else
                                  class="fas fa-calendar text-blue-300 text-xs"
                                  title="Date will be populated when HOD approves"
                                ></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Divisional Director -->
                      <div
                        :class="[
                          'bg-white/15 rounded-lg border border-blue-300/30 backdrop-blur-sm',
                          isDivisionalDirectorUser ? 'p-3' : 'p-4'
                        ]"
                      >
                        <h5
                          class="font-bold text-white mb-1 text-center text-base flex items-center justify-center gap-2"
                        >
                          <i class="fas fa-user-circle mr-2 text-blue-300"></i>
                          Divisional Director
                          <!-- HOD view - Show signature status for higher approvers -->
                          <span
                            v-if="
                              isHodApprovalEditable &&
                              isReviewMode &&
                              requestData?.divisional_signature_path
                            "
                            class="text-base px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-signature text-base mr-1"></i>
                            Signed
                          </span>
                          <span
                            v-else-if="
                              isHodApprovalEditable &&
                              isReviewMode &&
                              !requestData?.divisional_signature_path
                            "
                            class="text-base px-2 py-1 bg-red-500/30 rounded-full text-red-300"
                          >
                            <i class="fas fa-times text-base mr-1"></i>
                            No Signature
                          </span>
                          <!-- Other roles view -->
                          <span
                            v-else-if="isStageCompleted('divisional')"
                            class="text-base px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-check text-base mr-1"></i>
                            Completed
                          </span>
                          <span
                            v-else-if="isDivisionalSkipped"
                            class="text-base px-2 py-1 bg-red-500/30 rounded-full text-red-300"
                          >
                            <i class="fas fa-ban text-base mr-1"></i>
                            Skipped
                          </span>
                          <span
                            v-else-if="!isDivisionalApprovalEditable && isReviewMode"
                            class="text-base px-2 py-1 bg-gray-500/30 rounded-full text-gray-300"
                          >
                            <i class="fas fa-clock text-base mr-1"></i>
                            Pending
                          </span>
                        </h5>
                        <div class="space-y-3">
                          <div>
                            <label class="block text-base font-medium text-blue-100 mb-1">Name<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.approvals.divisionalDirector.name"
                                type="text"
                                readonly
                                :placeholder="getApprovalNamePlaceholder('divisional_director')"
                                class="medical-input w-full px-3 py-2 bg-white/15 border border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm cursor-not-allowed"
                                :title="getApprovalNameTitle('divisional_director')"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i
                                  class="fas fa-lock text-blue-300 text-xs"
                                  title="This field is auto-populated from your account"
                                ></i>
                              </div>
                            </div>
                          </div>
                          <div>
                            <label class="block text-base font-medium text-blue-100 mb-1">Signature<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <!-- Show Divisional Director signed indicator for ICT Director stage -->
                              <div
                                v-if="shouldShowDivisionalSignedIndicator"
                                :class="[
                                  'w-full px-3 py-2 rounded-xl backdrop-blur-sm transition-all duration-300 min-h-[35px] flex items-center justify-center',
                                  'border-2 border-green-400/50 bg-green-500/10 shadow-lg',
                                  isIctDirectorApprovalActive
                                    ? 'ring-1 ring-green-300/40 shadow-xl'
                                    : ''
                                ]"
                                :title="getSignedByYouTooltip('divisional')"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-check text-green-400 text-sm"></i>
                                    </div>
                                    <span
                                      :class="[
                                        'text-base',
                                        isIctDirectorApprovalActive
                                          ? 'text-green-300 font-extrabold uppercase tracking-wide'
                                          : 'text-green-400 font-semibold'
                                      ]"
                                      >Signed</span>
                                  </div>
                                  <p
                                    :class="[
                                      isIctDirectorApprovalActive
                                        ? 'text-green-200 font-semibold text-base'
                                        : 'text-green-300/80 text-base'
                                    ]"
                                  >
                                    Approved at: {{ getApprovalDateFormatted('divisional') }}
                                  </p>
                                </div>
                              </div>

                              <!-- Divisional Director skipped indicator -->
                              <div
                                v-else-if="isDivisionalSkipped"
                                class="w-full px-3 py-2 border-2 border-red-400/60 rounded-xl bg-red-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[35px] flex items-center justify-center"
                                title="Divisional Director approval skipped"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                                      <i class="fas fa-ban text-red-400 text-sm"></i>
                                    </div>
                                    <span class="text-red-400 font-semibold text-base">Signature skipped</span>
                                  </div>
                                  <p class="text-red-300/80 text-base">
                                    Divisional Director approval skipped
                                  </p>
                                </div>
                              </div>

                              <!-- Show Divisional Director missing signature indicator for ICT Director stage -->
                              <div
                                v-else-if="shouldShowDivisionalNoSignatureIndicator"
                                class="w-full px-3 py-2 border-2 border-red-400/50 rounded-xl bg-red-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[35px] flex items-center justify-center"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-times text-red-400 text-sm"></i>
                                    </div>
                                    <span class="text-red-400 font-semibold text-base">No signature on file</span>
                                  </div>
                                  <p class="text-red-300/80 text-base">
                                    Divisional Director approval pending
                                  </p>
                                </div>
                              </div>

                              <div
                                v-else-if="!divDirectorSignaturePreview"
                                class="w-full px-3 py-2 border-2 border-dashed border-blue-300/40 rounded-lg focus-within:border-blue-400 bg-white/15 backdrop-blur-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-blue-500/20 min-h-[30px] flex items-center justify-center hover:bg-white/20"
                              >
                                <!-- Display signature status with proper styling like HOD -->
                                <div
                                  :class="[
                                    'w-full px-3 py-2 rounded-xl backdrop-blur-sm transition-all duration-300 min-h-[35px] flex items-center justify-center relative',
                                    requestData?.approvals?.divisionalDirector?.signature_status ===
                                    'Signed'
                                      ? 'border-2 border-green-400/50 bg-green-500/10 shadow-lg'
                                      : 'border-2 border-red-400/50 bg-red-500/10 shadow-lg'
                                  ]"
                                >
                                  <div class="text-center">
                                    <div class="flex items-center justify-center space-x-2 mb-1">
                                      <div
                                        :class="[
                                          'w-8 h-8 rounded-full flex items-center justify-center',
                                          requestData?.approvals?.divisionalDirector
                                            ?.signature_status === 'Signed'
                                            ? 'bg-green-500/20'
                                            : 'bg-red-500/20'
                                        ]"
                                      >
                                        <i
                                          :class="[
                                            'text-sm',
                                            requestData?.approvals?.divisionalDirector
                                              ?.signature_status === 'Signed'
                                              ? 'fas fa-check text-green-400'
                                              : 'fas fa-times text-red-400'
                                          ]"
                                        ></i>
                                      </div>
                                      <span
                                        :class="[
                                          'text-sm font-semibold',
                                          requestData?.approvals?.divisionalDirector
                                            ?.signature_status === 'Signed'
                                            ? 'text-green-400'
                                            : 'text-red-400'
                                        ]"
                                      >
                                        {{
                                          requestData?.approvals?.divisionalDirector
                                            ?.signature_display || 'No signature'
                                        }}
                                      </span>
                                    </div>
                                    <!-- Optional: Show signature preview icon for signed status -->
                                    <div
                                      v-if="
                                        requestData?.approvals?.divisionalDirector
                                          ?.signature_status === 'Signed'
                                      "
                                      class="absolute top-2 right-2"
                                    >
                                      <div
                                        class="w-6 h-6 bg-green-500/30 rounded-full flex items-center justify-center"
                                      >
                                        <i
                                          class="fas fa-signature text-green-400 text-xs"
                                          title="Signature on file"
                                        ></i>
                                      </div>
                                    </div>
                                  </div>
                                </div>

                                <!-- Keep upload button section for when user can edit -->
                                <div class="text-center mt-2">
                                  <button
                                    v-if="isDivisionalApprovalEditable && !(hasUserSigned || (currentUser?.name && historyHasSigner(currentUser.name)))"
                                    type="button"
                                    @click="signCurrentDocument"
                                    :disabled="isSigning || !isReviewMode"
                                    class="px-3 py-1.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-sm font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 flex items-center gap-1 mx-auto shadow-lg hover:shadow-xl transform hover:scale-105 border border-blue-400/50"
                                  >
                                    <i class="fas fa-pen-alt"></i>
                                    Sign Document
                                  </button>
                                  <p v-if="!isReviewMode" class="text-xs text-blue-200 mt-1">Available after submitting the request</p>
                                </div>
                              </div>

                            </div>
                          </div>
                          <div>
                            <label class="block text-base font-medium text-blue-100 mb-1">Date (mm/dd/yyyy)<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.approvals.divisionalDirector.date"
                                type="date"
                                :readonly="true"
:class="[
                                  'medical-input w-full px-3 py-2 bg-white/15 border rounded-lg focus:outline-none text-white backdrop-blur-sm cursor-not-allowed',
                                  shouldShowDivisionalSignedIndicator
                                    ? 'border-green-400/60 ring-1 ring-green-300/40'
                                    : '',
                                  shouldShowDivisionalNoSignatureIndicator
                                    ? 'border-red-400/40'
                                    : '',
                                  isDivisionalSkipped ? 'border-red-400/60 bg-red-500/10' : '',
                                  isIctDirectorApprovalActive && shouldShowDivisionalSignedIndicator
                                    ? 'font-semibold text-green-200'
                                    : ''
                                ]"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i
                                  v-if="isDivisionalSkipped"
                                  class="fas fa-ban text-red-400 text-xs"
                                  title="Divisional Director approval skipped"
                                ></i>
                                <i
                                  v-else-if="shouldShowDivisionalSignedIndicator"
                                  class="fas fa-check text-green-400 text-xs"
                                  title="Divisional Director has signed - date populated from approval"
                                ></i>
                                <i
                                  v-else-if="shouldShowDivisionalNoSignatureIndicator"
                                  class="fas fa-clock text-red-400 text-xs"
                                  title="Pending Divisional Director approval"
                                ></i>
                                <i
                                  v-else
                                  class="fas fa-calendar text-blue-300 text-xs"
                                  title="Date will be populated when Divisional Director approves"
                                ></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Director of ICT -->
                      <div
                        :class="[
                          'bg-white/15 rounded-lg border border-blue-300/30 backdrop-blur-sm',
                          isDivisionalDirectorUser ? 'p-3' : 'p-4'
                        ]"
                      >
                        <h5
                          class="font-bold text-white mb-1 text-center text-sm flex items-center justify-center gap-2"
                        >
                          <i class="fas fa-laptop-code mr-2 text-blue-300"></i>
                          Director of ICT
                          <!-- HOD view - Show signature status for higher approvers -->
                          <span
                            v-if="
                              isHodApprovalEditable &&
                              isReviewMode &&
                              requestData?.ict_director_signature_path
                            "
                            class="text-xs px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-signature text-xs mr-1"></i>
                            Signed
                          </span>
                          <span
                            v-else-if="
                              isHodApprovalEditable &&
                              isReviewMode &&
                              !requestData?.ict_director_signature_path
                            "
                            class="text-xs px-2 py-1 bg-red-500/30 rounded-full text-red-300"
                          >
                            <i class="fas fa-times text-xs mr-1"></i>
                            No Signature
                          </span>
                          <!-- Other roles view -->
                          <span
                            v-else-if="isStageCompleted('ict_director')"
                            class="text-xs px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-check text-xs mr-1"></i>
                            Completed
                          </span>
                          <span
                            v-else-if="!isIctDirectorApprovalEditable && isReviewMode"
                            class="text-xs px-2 py-1 bg-gray-500/30 rounded-full text-gray-300"
                          >
                            <i class="fas fa-clock text-xs mr-1"></i>
                            Pending
                          </span>
                        </h5>
                        <div class="space-y-3">
                          <div>
                            <label class="block text-base font-medium text-blue-100 mb-1">Name<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.approvals.directorICT.name"
                                type="text"
                                readonly
                                :placeholder="getApprovalNamePlaceholder('ict_director')"
                                class="medical-input w-full px-3 py-2 bg-white/15 border border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm cursor-not-allowed"
                                :title="getApprovalNameTitle('ict_director')"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i
                                  class="fas fa-lock text-blue-300 text-xs"
                                  title="This field is auto-populated from your account"
                                ></i>
                              </div>
                            </div>
                          </div>
                          <div>
                            <label class="block text-base font-medium text-blue-100 mb-1">Signature<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <!-- Show ICT Director signed indicator for Head IT stage -->
                              <div
                                v-if="shouldShowIctDirectorSignedIndicator"
                                class="w-full px-3 py-2 border-2 border-green-400/50 rounded-xl bg-green-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[35px] flex items-center justify-center"
                                :title="getSignedByYouTooltip('ict_director')"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-check text-green-400 text-sm"></i>
                                    </div>
                                    <span class="text-green-400 font-semibold text-base">Signed</span>
                                  </div>
                                  <p class="text-green-300/80 text-base">
                                    Approved at: {{ getApprovalDateFormatted('ict_director') }}
                                  </p>
                                </div>
                              </div>

                              <!-- Show ICT Director missing signature indicator for Head IT stage -->
                              <div
                                v-else-if="shouldShowIctDirectorNoSignatureIndicator"
                                class="w-full px-3 py-2 border-2 border-red-400/50 rounded-xl bg-red-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[35px] flex items-center justify-center"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-times text-red-400 text-sm"></i>
                                    </div>
                                    <span class="text-red-400 font-semibold text-base">No signature on file</span>
                                  </div>
                                  <p class="text-red-300/80 text-base">
                                    ICT Director approval pending
                                  </p>
                                </div>
                              </div>

                              <div
                                v-else-if="!directorICTSignaturePreview"
                                class="w-full px-3 py-2 border-2 border-dashed border-blue-300/40 rounded-lg focus-within:border-blue-400 bg-white/15 backdrop-blur-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-blue-500/20 min-h-[30px] flex items-center justify-center hover:bg-white/20"
                              >
                                <!-- Display signature status with proper styling like HOD -->
                                <div
                                  :class="[
                                    'w-full px-3 py-2 rounded-xl backdrop-blur-sm transition-all duration-300 min-h-[35px] flex items-center justify-center relative',
                                    requestData?.approvals?.directorICT?.signature_status ===
                                    'Signed'
                                      ? 'border-2 border-green-400/50 bg-green-500/10 shadow-lg'
                                      : 'border-2 border-red-400/50 bg-red-500/10 shadow-lg'
                                  ]"
                                >
                                  <div class="text-center">
                                    <div class="flex items-center justify-center space-x-2 mb-1">
                                      <div
                                        :class="[
                                          'w-8 h-8 rounded-full flex items-center justify-center',
                                          requestData?.approvals?.directorICT?.signature_status ===
                                          'Signed'
                                            ? 'bg-green-500/20'
                                            : 'bg-red-500/20'
                                        ]"
                                      >
                                        <i
                                          :class="[
                                            'text-sm',
                                            requestData?.approvals?.directorICT
                                              ?.signature_status === 'Signed'
                                              ? 'fas fa-check text-green-400'
                                              : 'fas fa-times text-red-400'
                                          ]"
                                        ></i>
                                      </div>
                                      <span
                                        :class="[
                                          'text-sm font-semibold',
                                          requestData?.approvals?.directorICT?.signature_status ===
                                          'Signed'
                                            ? 'text-green-400'
                                            : 'text-red-400'
                                        ]"
                                      >
                                        {{
                                          requestData?.approvals?.directorICT?.signature_display ||
                                          'No signature'
                                        }}
                                      </span>
                                    </div>
                                    <!-- Optional: Show signature preview icon for signed status -->
                                    <div
                                      v-if="
                                        requestData?.approvals?.directorICT?.signature_status ===
                                        'Signed'
                                      "
                                      class="absolute top-2 right-2"
                                    >
                                      <div
                                        class="w-6 h-6 bg-green-500/30 rounded-full flex items-center justify-center"
                                      >
                                        <i
                                          class="fas fa-signature text-green-400 text-xs"
                                          title="Signature on file"
                                        ></i>
                                      </div>
                                    </div>
                                  </div>
                                </div>

                                <!-- Keep upload button section for when user can edit -->
                                <div class="text-center mt-2">
                                  <button
                                    v-if="isIctDirectorApprovalEditable && !(hasUserSigned || (currentUser?.name && historyHasSigner(currentUser.name)))"
                                    type="button"
                                    @click="signCurrentDocument"
                                    :disabled="isSigning || !isReviewMode"
                                    class="px-3 py-1.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-xs font-semibold rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all duration-300 flex items-center gap-1 mx-auto shadow-lg hover:shadow-xl transform hover:scale-105 border border-green-400/50"
                                  >
                                    <i class="fas fa-pen-alt"></i>
                                    Sign Document
                                  </button>
                                  <p v-if="!isReviewMode" class="text-xs text-blue-200 mt-1">Available after submitting the request</p>
                                </div>
                              </div>

                            </div>
                          </div>
                          <div>
                            <label class="block text-xs font-medium text-blue-100 mb-1">Date (mm/dd/yyyy)<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.approvals.directorICT.date"
                                type="date"
                                :readonly="!isIctDirectorApprovalEditable"
                                class="medical-input w-full px-3 py-2 bg-white/15 border border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white backdrop-blur-sm"
                                :class="{
                                  'cursor-not-allowed': !isIctDirectorApprovalEditable,
                                  'ict-director-approval-editable': isIctDirectorApprovalEditable,
                                  'bg-green-100/20 border-green-400/40':
                                    shouldShowIctDirectorSignedIndicator,
                                  'bg-red-100/20 border-red-400/40':
                                    shouldShowIctDirectorNoSignatureIndicator
                                }"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i
                                  v-if="shouldShowIctDirectorSignedIndicator"
                                  class="fas fa-check text-green-400 text-xs"
                                  title="ICT Director has signed - date populated from approval"
                                ></i>
                                <i
                                  v-else-if="shouldShowIctDirectorNoSignatureIndicator"
                                  class="fas fa-clock text-red-400 text-xs"
                                  title="Pending ICT Director approval"
                                ></i>
                                <i
                                  v-else
                                  class="fas fa-calendar text-blue-300 text-xs"
                                  :title="
                                    isIctDirectorApprovalEditable
                                      ? 'Select ICT Director approval date'
                                      : 'Date will be populated when ICT Director approves'
                                  "
                                ></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>


                  <!-- For Implementation Section -->
                  <div :class="implementationCardClass">
                    <div :class="implementationHeaderClass">
                      <div
                        class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 border border-blue-300/50"
                      >
                        <i class="fas fa-cogs text-white text-sm"></i>
                      </div>
                      <h3 
                        :class="[
                          'text-base font-bold text-white flex items-center',
                          isStrictViewMode ? 'select-none' : 'cursor-pointer select-none'
                        ]"
                        @click="!isStrictViewMode && (showImplementationSections = !showImplementationSections)"
                        :aria-expanded="showImplementationSections ? 'true' : 'false'"
                        :aria-controls="'implementation-sections'"
                        :title="isStrictViewMode ? 'Implementation information' : 'Toggle implementation sections'"
                      >
                        <i class="fas fa-tools mr-1 text-blue-300"></i>
                        For Implementation
                        <i
                          v-if="!isStrictViewMode"
                          :class="['fas', showImplementationSections ? 'fa-chevron-up' : 'fa-chevron-down']"
                          class="ml-2"
                        ></i>
                      </h3>
                    </div>

                    <div id="implementation-sections" v-show="isStrictViewMode || showImplementationSections" :class="implementationGridClass">
                      <!-- Head of IT -->
                      <div :class="headItSectionClass">
                        <h5
                          class="font-bold text-white mb-0.5 text-center text-base flex items-center justify-center gap-1"
                        >
                          <i class="fas fa-user-cog mr-2"></i>
                          Head of IT
                          <!-- HOD view - Show signature status for higher approvers -->
                          <span
                            v-if="
                              isHodApprovalEditable &&
                              isReviewMode &&
                              requestData?.head_it_signature_path
                            "
                            class="text-base px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-signature text-base mr-1"></i>
                            Signed
                          </span>
                          <span
                            v-else-if="
                              isHodApprovalEditable &&
                              isReviewMode &&
                              !requestData?.head_it_signature_path
                            "
                            class="text-base px-2 py-1 bg-red-500/30 rounded-full text-red-300"
                          >
                            <i class="fas fa-times text-base mr-1"></i>
                            No Signature
                          </span>
                          <!-- Other roles view -->
                          <span
                            v-else-if="isStageCompleted('head_it')"
                            class="text-base px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-check text-base mr-1"></i>
                            Completed
                          </span>
                          <span
                            v-else-if="!isHeadItApprovalEditable && isReviewMode"
                            class="text-base px-2 py-1 bg-gray-500/30 rounded-full text-gray-300"
                          >
                            <i class="fas fa-clock text-base mr-1"></i>
                            Pending
                          </span>
                        </h5>
                        <div class="space-y-0.5">
                          <div>
                            <label class="block text-base font-medium text-blue-100 mb-0.5">Name<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.implementation.headIT.name"
                                type="text"
                                :readonly="
                                  !isHeadItApprovalEditable ||
                                  (isHeadItApprovalEditable && !!form.implementation.headIT.name)
                                "
                                :placeholder="getApprovalNamePlaceholder('head_it')"
                                class="medical-input w-full px-2 py-1 border border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm text-xs"
                                :class="{
                                  'cursor-not-allowed bg-gray-500/20':
                                    !isHeadItApprovalEditable ||
                                    (isHeadItApprovalEditable && !!form.implementation.headIT.name),
                                  'bg-white/15':
                                    !form.implementation.headIT.name && isHeadItApprovalEditable,
                                  'bg-blue-500/20 border-blue-400/50':
                                    isHeadItApprovalEditable && !!form.implementation.headIT.name,
                                  'font-bold text-yellow-300':
                                    isHeadItApprovalEditable && form.implementation.headIT.name
                                }"
                                :title="
                                  form.implementation.headIT.name
                                    ? 'Auto-populated from your account - cannot be modified'
                                    : getApprovalNameTitle('head_it')
                                "
                              />
                              <div
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center space-x-2"
                              >
                                <!-- Debug button for Head IT auto-population (development only) -->
                                <i
                                  class="fas fa-lock text-blue-300 text-xs"
                                  :class="{
                                    'text-yellow-300':
                                      isHeadItApprovalEditable && form.implementation.headIT.name
                                  }"
                                  :title="
                                    isHeadItApprovalEditable
                                      ? 'This field is auto-populated from your account'
                                      : 'This field will be auto-populated when Head IT reviews the request'
                                  "
                                ></i>
                              </div>
                            </div>
                          </div>
                          <div>
                            <label class="block text-base font-medium text-blue-100 mb-0.5">Signature<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <!-- Show Head IT signed indicator for ICT Officer stage -->
                              <div
                                v-if="shouldShowHeadITSignedIndicator"
                                class="w-full px-2 py-1 border-2 border-green-400/50 rounded-lg bg-green-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[25px] flex items-center justify-center"
                                :title="getSignedByYouTooltip('head_it')"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-6 h-6 bg-green-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-check text-green-400 text-sm"></i>
                                    </div>
                                    <span class="text-green-400 font-semibold text-base">Signed</span>
                                  </div>
                                  <p class="text-green-300/80 text-base">
                                    Approved at: {{ getApprovalDateFormatted('head_it') }}
                                  </p>
                                </div>
                              </div>

                              <!-- Show Head IT missing signature indicator for ICT Officer stage -->
                              <div
                                v-else-if="shouldShowHeadITNoSignatureIndicator"
                                class="w-full px-2 py-1 border-2 border-red-400/50 rounded-lg bg-red-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[25px] flex items-center justify-center"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-6 h-6 bg-red-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-times text-red-400 text-sm"></i>
                                    </div>
                                    <span class="text-red-400 font-semibold text-base">No signature on file</span>
                                  </div>
                                  <p class="text-red-300/80 text-base">Head IT approval pending</p>
                                </div>
                              </div>

                              <div
                                v-else-if="!headITSignaturePreview"
                                class="w-full px-2 py-1 border border-blue-300/30 rounded-lg focus-within:border-blue-400 bg-white/15 transition-all duration-300 shadow-sm hover:shadow-md min-h-[20px] flex items-center justify-center backdrop-blur-sm"
                              >
                                <div class="text-center">
                                  <div class="mb-1">
                                    <i class="fas fa-signature text-blue-300 text-sm mb-1"></i>
                                    <p class="text-blue-100 text-xs">No signature</p>
                                  </div>
                                  <button
                                    v-if="isHeadItApprovalEditable && !(hasUserSigned || (currentUser?.name && historyHasSigner(currentUser.name)))"
                                    type="button"
                                    @click="signCurrentDocument"
                                    :disabled="isSigning || !isReviewMode"
                                    class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors duration-200 flex items-center gap-1 mx-auto"
                                  >
                                    <i class="fas fa-pen-alt"></i>
                                    Sign Document
                                  </button>
                                  <p v-if="!isReviewMode" class="text-[10px] text-blue-200 mt-0.5">Available after submitting the request</p>
                                </div>
                              </div>

                            </div>
                          </div>
                          <div>
                            <label class="block text-base font-medium text-blue-100 mb-0.5">Date (mm/dd/yyyy)<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.implementation.headIT.date"
                                type="date"
                                :readonly="!isHeadItApprovalEditable"
                                class="medical-input w-full px-2 py-1 bg-white/15 border border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white backdrop-blur-sm text-xs"
                                :class="{
                                  'cursor-not-allowed': !isHeadItApprovalEditable,
                                  'font-bold text-yellow-300':
                                    isHeadItApprovalEditable && form.implementation.headIT.date,
                                  'bg-green-100/20 border-green-400/40':
                                    shouldShowHeadITSignedIndicator,
                                  'bg-red-100/20 border-red-400/40':
                                    shouldShowHeadITNoSignatureIndicator
                                }"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i
                                  v-if="shouldShowHeadITSignedIndicator"
                                  class="fas fa-check text-green-400 text-xs"
                                  title="Head IT has signed - date populated from approval"
                                ></i>
                                <i
                                  v-else-if="shouldShowHeadITNoSignatureIndicator"
                                  class="fas fa-clock text-red-400 text-xs"
                                  title="Pending Head IT approval"
                                ></i>
                                <i
                                  v-else
                                  class="fas fa-calendar text-blue-300 text-xs"
                                  title="Date will be populated when Head IT approves"
                                ></i>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- ICT Officer granting access -->
                      <div :class="ictOfficerSectionClass">
                        <h5
                          class="font-bold text-white mb-0.5 text-center text-base flex items-center justify-center gap-1"
                        >
                          <i class="fas fa-user-shield mr-2"></i>
                          ICT Officer granting access
                          <!-- HOD view - Show signature status for higher approvers -->
                          <span
                            v-if="
                              isHodApprovalEditable &&
                              isReviewMode &&
                              requestData?.ict_officer_signature_path
                            "
                            class="text-base px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-signature text-base mr-1"></i>
                            Signed
                          </span>
                          <span
                            v-else-if="
                              isHodApprovalEditable &&
                              isReviewMode &&
                              !requestData?.ict_officer_signature_path
                            "
                            class="text-base px-2 py-1 bg-red-500/30 rounded-full text-red-300"
                          >
                            <i class="fas fa-times text-base mr-1"></i>
                            No Signature
                          </span>
                          <!-- Other roles view -->
                          <span
                            v-else-if="isStageCompleted('ict_officer')"
                            class="text-base px-2 py-1 bg-green-500/30 rounded-full text-green-300"
                          >
                            <i class="fas fa-check text-base mr-1"></i>
                            Completed
                          </span>
                          <span
                            v-else-if="!isIctOfficerApprovalEditable && isReviewMode"
                            class="text-base px-2 py-1 bg-gray-500/30 rounded-full text-gray-300"
                          >
                            <i class="fas fa-clock text-xs mr-1"></i>
                            Pending
                          </span>
                        </h5>
                        <div class="space-y-0.5">
                          <div>
                            <label class="block text-xs font-medium text-blue-100 mb-0">Name<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.implementation.ictOfficer.name"
                                type="text"
                                readonly
                                :placeholder="getApprovalNamePlaceholder('ict_officer')"
                                class="medical-input w-full px-2 py-1 bg-white/15 border border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm cursor-not-allowed text-xs"
                                :title="getApprovalNameTitle('ict_officer')"
                              />
                              <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i
                                  class="fas fa-lock text-blue-300 text-xs"
                                  title="This field is auto-populated from your account"
                                ></i>
                              </div>
                            </div>
                          </div>
                          <div>
                            <label class="block text-xs font-medium text-blue-100 mb-0">Signature<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <div
                                v-if="
                                  !ictOfficerSignaturePreview &&
                                  !isImplementationAlreadyCompleted &&
                                  !(shouldShowIctOfficerSignedIndicator || (viewerStage() === 'ict_officer' && (hasUserSigned || (currentUser?.name && historyHasSigner(currentUser.name)))))
                                "
                                class="w-full px-2 py-1 border-2 border-dashed border-blue-300/40 rounded-lg focus-within:border-blue-400 bg-white/15 backdrop-blur-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-blue-500/20 min-h-[20px] flex items-center justify-center hover:bg-white/20"
                              >
                                <div class="text-center">
                                  <div class="mb-0.5">
                                    <i class="fas fa-signature text-blue-300 text-sm mb-0.5"></i>
                                    <p class="text-blue-100 text-xs">No signature yet</p>
                                  </div>
                                  <button
                                    v-if="
                                      (isIctOfficerApprovalEditable ||
                                        currentUser?.role === 'ict_officer') &&
                                      !isImplementationAlreadyCompleted &&
                                      !(hasUserSigned || (currentUser?.name && historyHasSigner(currentUser.name)))
                                    "
                                    type="button"
                                    @click="signCurrentDocument"
                                    :disabled="isSigning || !isReviewMode"
                                    class="px-2 py-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-xs font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 flex items-center gap-1 mx-auto shadow-lg hover:shadow-xl transform hover:scale-105 border border-blue-400/50"
                                  >
                                    <i class="fas fa-pen-alt"></i>
                                    Sign Document
                                  </button>
                                  <p v-if="!isReviewMode" class="text-[10px] text-blue-200 mt-0.5">Available after submitting the request</p>
                                </div>
                              </div>

                              <!-- Show existing signature when implementation is completed or when digitally signed -->
                              <div
                                v-else-if="
                                  shouldShowIctOfficerSignedIndicator ||
                                  (viewerStage() === 'ict_officer' && (hasUserSigned || (currentUser?.name && historyHasSigner(currentUser.name))))
                                "
                                class="w-full px-3 py-2 border-2 border-green-300/40 rounded-xl bg-white/15 backdrop-blur-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:shadow-green-500/20 min-h-[35px] flex items-center justify-center relative"
                                :title="getSignedByYouTooltip('ict_officer')"
                              >
                                <div class="text-center">
                                  <div class="flex items-center justify-center space-x-2 mb-1">
                                    <div
                                      class="w-8 h-8 bg-green-500/20 rounded-full flex items-center justify-center"
                                    >
                                      <i class="fas fa-check text-green-400 text-sm"></i>
                                    </div>
                                    <span class="text-green-300 font-extrabold text-base uppercase tracking-wide">Signed</span>
                                  </div>
                                  <p class="text-green-200 font-semibold text-sm">
                                    Approved at: {{ getApprovalDateFormatted('ict_officer') }}
                                  </p>
                                </div>
                                <!-- Optional: Show signature preview icon -->
                                <div class="absolute top-2 right-2">
                                  <div
                                    class="w-6 h-6 bg-green-500/30 rounded-full flex items-center justify-center"
                                  >
                                    <i
                                      class="fas fa-signature text-green-400 text-xs"
                                      title="Signature on file"
                                    ></i>
                                  </div>
                                </div>
                              </div>

                              <!-- Show no signature indicator (development fallback) -->
                              <div
                                v-else-if="shouldShowIctOfficerNoSignatureIndicator"
                                class="w-full px-2 py-1 border-2 border-dashed border-red-300/40 rounded-lg bg-red-500/10 backdrop-blur-sm transition-all duration-300 shadow-lg min-h-[20px] flex items-center justify-center"
                              >
                                <div class="text-center">
                                  <i class="fas fa-times text-red-400 text-sm mb-0.5"></i>
                                  <p class="text-red-300 text-xs">No signature</p>
                                </div>
                              </div>

                              <!-- Show uploaded signature preview -->
                            </div>
                          </div>
                          <div>
                            <label class="block text-xs font-medium text-blue-100 mb-0">Date<span class="text-red-400">*</span></label>
                            <div class="relative">
                              <input
                                v-model="form.approvals.hod.date"
                                type="date"
                                class="medical-input w-full px-2 py-1 bg-white/15 border border-blue-300/30 rounded-lg focus:border-blue-400 focus:outline-none text-white backdrop-blur-sm hod-approval-editable text-xs"
                              />
                            </div>
                          </div>

                          <!-- ICT Officer Approve Request Button (after signature upload) - Hide in strict view mode -->
                          <div
                            v-if="
                              !isStrictViewMode &&
                              (isIctOfficerApprovalEditable || currentUser?.role === 'ict_officer') &&
                              (shouldShowIctOfficerSignedIndicator || hasUserSigned) &&
                              !isImplementationAlreadyCompleted
                            "
                            class="mt-3"
                          >
                            <button
                              type="button"
                              @click="onIctOfficerApproveClick"
                              :disabled="isImplementationApprovalDisabled"
                              :class="[
                                'w-full px-4 py-2 rounded-lg transition-all duration-300 font-semibold flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-105 text-xs',
                                isImplementationApprovalDisabled
                                  ? 'bg-gray-500 text-gray-300 cursor-not-allowed opacity-50'
                                  : 'bg-gradient-to-r from-green-600 to-emerald-600 text-white hover:from-green-700 hover:to-emerald-700'
                              ]"
                            >
                              <i v-if="processing" class="fas fa-spinner fa-spin mr-2"></i>
                              <i v-else class="fas fa-check mr-2"></i>
                              {{ processing ? 'Approving...' : 'Approve Request' }}
                            </button>
                          </div>

                          <!-- Select ICT Officer Button (Head IT Only - Persistent after approval) - Hide in strict view mode -->
                          <div v-if="!isStrictViewMode && shouldShowSelectIctOfficerButton" class="mt-2">
                            <div
                              class="bg-yellow-500/10 border-2 border-yellow-400/30 rounded-lg p-2 backdrop-blur-sm"
                            >
                              <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                  <div
                                    class="w-6 h-6 bg-yellow-500/20 rounded-full flex items-center justify-center"
                                  >
                                    <i class="fas fa-user-shield text-yellow-400 text-sm"></i>
                                  </div>
                                  <div>
                                    <h6 class="text-xs font-semibold text-yellow-200 mb-0.5">
                                      ICT Officer Assignment Required
                                    </h6>
                                    <p class="text-xs text-yellow-300/80">
                                      Request approved. Please assign an ICT Officer.
                                    </p>
                                  </div>
                                </div>
                                <button
                                  @click="navigateToSelectIctOfficer"
                                  class="px-3 py-1.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-300 font-semibold flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-105 text-xs"
                                >
                                  <i class="fas fa-user-plus mr-1"></i>
                                  Select ICT Officer
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Action Buttons (Review Mode Only) - Hide in strict view mode -->
                  <div
                    v-if="isReviewMode && canApproveAtStage() && !showApprovalSuccessCard && !isStrictViewMode"
                    :class="actionButtonsClass"
                  >
                    <!-- Action Buttons -->
                    <div class="flex justify-between gap-4">
                      <!-- Approve Button - Left Side -->
                      <button
                        type="button"
                        @click="approveRequest"
                        :disabled="areApprovalButtonsDisabled"
                        :class="[
                          'flex-1 px-6 py-3 rounded-lg transition-all duration-300 font-semibold flex items-center justify-center shadow-lg transform',
                          areApprovalButtonsDisabled
                            ? 'bg-gray-500 text-gray-300 cursor-not-allowed opacity-50'
                            : 'bg-gradient-to-r from-green-600 to-emerald-600 text-white hover:from-green-700 hover:to-emerald-700 hover:shadow-xl hover:scale-105'
                        ]"
                        :title="approvalButtonTooltip"
                      >
                        <i v-if="loading || processing" class="fas fa-spinner fa-spin mr-2"></i>
                        <i v-else class="fas fa-check mr-2"></i>
                        {{ loading || processing ? 'Processing...' : 'Approve Request' }}
                      </button>

                      <!-- Reject Button - Right Side -->
                      <button
                        type="button"
                        @click="rejectRequest"
                        :disabled="areApprovalButtonsDisabled"
                        :class="[
                          'flex-1 px-6 py-3 rounded-lg transition-all duration-300 font-semibold flex items-center justify-center shadow-lg transform',
                          areApprovalButtonsDisabled
                            ? 'bg-gray-500 text-gray-300 cursor-not-allowed opacity-50'
                            : 'bg-gradient-to-r from-red-600 to-pink-600 text-white hover:from-red-700 hover:to-pink-700 hover:shadow-xl hover:scale-105'
                        ]"
                        :title="
                          isSignatureRequiredForApproval
                            ? 'Please upload your signature first'
                            : 'Reject this request'
                        "
                      >
                        <i class="fas fa-times mr-2"></i>
                        Reject Request
                      </button>
                    </div>
                  </div>

                  <!-- Footer & Submit (Normal Mode) -->
                  <div v-if="!isReviewMode" class="border-t-2 border-gray-200 pt-3">
                    <div class="flex flex-col sm:flex-row justify-between gap-3">
                      <button
                        type="button"
                        @click="onReset"
                        class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-all duration-300 font-semibold flex items-center justify-center shadow-lg hover:shadow-xl"
                      >
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                          <path
                            fill-rule="evenodd"
                            d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Reset Form
                      </button>
                      <button
                        type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-300 font-semibold flex items-center justify-center shadow-lg hover:shadow-xl"
                      >
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                          <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Submit Request
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Right: tabs -->
                <div
                  v-if="!isReviewMode"
                  aria-labelledby="module-tabs"
                  class="lg:col-span-1 space-y-4"
                >
                  <!-- Debug info for HOD module tabs (development only) -->
                  <div
                    v-if="isDevelopment && (getUserRole() || '').toLowerCase().includes('hod')"
                    class="bg-yellow-500/20 border-2 border-yellow-400/60 rounded-lg p-2 mb-4"
                  >
                    <h4 class="text-yellow-200 font-bold text-sm mb-2">🔧 HOD Module Debug Info</h4>
                    <div class="text-xs text-yellow-100 space-y-1">
                      <div>isReviewMode: {{ isReviewMode }}</div>
                      <div>isHodApprovalEditable: {{ isHodApprovalEditable }}</div>
                      <div>User Role: {{ getUserRole() }}</div>
                      <div>Request Status: {{ requestData?.status || 'none' }}</div>
                      <div>Show Tabs: {{ !isReviewMode || isHodApprovalEditable }}</div>
                      <div>Module reviewMode: {{ isReviewMode && !isHodApprovalEditable }}</div>
                      <div>Available Tabs: {{ tabs.map((t) => t.label).join(', ') }}</div>
                    </div>
                  </div>
                  <h2 id="module-tabs" class="sr-only">Module Details</h2>

                  <!-- Desktop tabs -->
                  <div class="hidden md:block">
                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                      <button
                        v-for="t in tabs"
                        :key="t.key"
                        class="tab"
                        :class="t.key === activeTab ? 'tab-active' : ''"
                        @click="activeTab = t.key"
                        :aria-selected="t.key === activeTab"
                        role="tab"
                      >
                        {{ t.label }}
                        <i
                          class="fas fa-times ml-2 text-xs opacity-70 hover:opacity-100"
                          @click.stop="tryCloseTab(t.key)"
                        ></i>
                      </button>
                    </div>
                    <!-- Removed module detail panel to eliminate large white section under chips -->
                    <div class="mt-2" v-if="false"></div>
                  </div>

                  <!-- Mobile accordion -->
                  <div class="md:hidden space-y-2">
                    <div v-for="t in tabs" :key="t.key" class="card">
                      <button
                        class="w-full flex items-center justify-between text-left"
                        @click="toggleAccordion(t.key)"
                      >
                        <span class="font-semibold">{{ t.label }}</span>
                        <i
                          :class="[
                            'fas',
                            openAccordions.has(t.key) ? 'fa-chevron-up' : 'fa-chevron-down'
                          ]"
                        ></i>
                      </button>
                      <!-- Removed mobile detail panel as requested -->
                      <transition name="fade">
                        <div v-if="false"></div>
                      </transition>
                    </div>
                  </div>
                </div>
                </div>
              </div>
            </form>
          </div>

          <!-- Footer moved to bottom of main content -->
          <div class="mt-6">
            <AppFooter />
          </div>
        </div>
      </main>
    </div>

    <!-- Close Tab Confirmation Modal -->
    <transition name="fade">
      <div
        v-if="confirm.key"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-4">
          <h3 class="text-lg font-semibold text-gray-800">Remove {{ confirm.label }}?</h3>
          <p class="text-sm text-gray-600 mt-1">
            You have unsaved data in this module. Are you sure you want to close it?
          </p>
          <div class="mt-4 flex justify-end gap-2">
            <button class="btn-secondary" @click="confirm = { key: '', label: '' }">Cancel</button>
            <button class="btn-danger" @click="closeTab(confirm.key)">Remove</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Rejection Reason Modal -->
    <transition name="fade">
      <div
        v-if="showRejectionModal"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
          <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
              <i class="fas fa-times text-red-600"></i>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-800">Reject Request</h3>
              <p class="text-sm text-gray-600">
                Please provide a reason for rejecting this request
              </p>
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-base font-medium text-gray-700 mb-1">Rejection Reason *</label>
            <textarea
              v-model="rejectionReason"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
              rows="4"
              placeholder="Enter the reason for rejecting this request..."
              required
            ></textarea>
          </div>

          <div class="flex justify-end space-x-3">
            <button
              @click="cancelRejectRequest"
              class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors"
            >
              Cancel
            </button>
            <button
              @click="confirmRejectRequest"
              :disabled="!rejectionReason?.trim()"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-300 text-white rounded-lg font-medium transition-colors flex items-center"
            >
              <i class="fas fa-times mr-2"></i>
              Reject Request
            </button>
          </div>
        </div>
      </div>
    </transition>


    <!-- Success Modal Popup (Head IT Only) -->
    <transition name="fade">
      <div
        v-if="showApprovalSuccessCard && isHeadItUser"
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
      >
        <div
          class="bg-white rounded-2xl shadow-2xl max-w-lg w-full transform transition-all duration-500 scale-100"
        >
          <!-- Header with success icon -->
          <div
            class="bg-gradient-to-r from-green-600 to-emerald-700 px-6 py-6 text-center rounded-t-2xl"
          >
            <div
              class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg"
            >
              <i class="fas fa-check text-green-600 text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">Request Approved Successfully!</h3>
          </div>

          <!-- Content -->
          <div class="px-6 py-6 text-center">
            <div class="mb-6">
              <div class="flex items-center justify-center mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                  <i class="fas fa-user-shield text-blue-600 text-xl"></i>
                </div>
              </div>
              <p class="text-gray-700 text-lg leading-relaxed">
                Now you can select the ICT Officer who will be assigned this task of releasing
                access to
                <span class="font-semibold text-blue-600">{{ getRequesterName() }}</span>.
              </p>
            </div>

            <!-- Action buttons -->
            <div class="flex gap-3">
              <button
                @click="closeSuccessModal"
                class="flex-1 bg-gray-500 text-white py-3 px-6 rounded-lg hover:bg-gray-600 transition duration-200 text-base font-medium"
              >
                Later
              </button>
              <button
                @click="navigateToSelectIctOfficer"
                class="flex-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-6 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-300 font-semibold flex items-center justify-center shadow-lg hover:shadow-xl transform hover:scale-105 text-base"
              >
                <i class="fas fa-user-plus mr-2"></i>
                Select ICT Officer
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ICT Officer Confirmation Popup -->
    <transition name="fade">
      <div
        v-if="showIctOfficerConfirmation"
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4"
      >
        <div
          class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all duration-500 scale-100"
        >
          <!-- Header with blue gradient -->
          <div
            class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-6 text-center rounded-t-2xl"
          >
            <div
              class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg"
            >
              <i class="fas fa-user-shield text-blue-600 text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Grant Access Confirmation</h3>
          </div>

          <!-- Content -->
          <div class="px-6 py-6 text-center">
            <p class="text-gray-700 text-base leading-relaxed mb-6">
              You are approaching to grant access to
              <span class="font-semibold text-blue-600">{{ getRequesterName() }}</span>. Do you want to continue?
            </p>

            <!-- Action buttons -->
            <div class="flex gap-3">
              <button
                @click="showIctOfficerConfirmation = false"
                class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 px-6 rounded-lg transition duration-200 text-base font-medium flex items-center justify-center"
              >
                <i class="fas fa-times mr-2"></i>
                No
              </button>
              <button
                @click="proceedToUserSecurityAccess"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg transition duration-200 text-base font-medium flex items-center justify-center"
              >
                <i class="fas fa-check mr-2"></i>
                Yes
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

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

  .medical-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.2), transparent);
    transition: left 0.5s;
  }

  .medical-card:hover::before {
    left: 100%;
  }

  .medical-input {
    position: relative;
    z-index: 1;
    color: white;
  }

  .medical-input::placeholder {
    color: rgba(191, 219, 254, 0.6);
  }

  .medical-input:focus {
    border-color: rgba(45, 212, 191, 0.8);
    box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.2);
  }

  /* Animations */
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

  .animate-fade-in {
    animation: fade-in 1s ease-out;
  }

  .animate-fade-in-delay {
    animation: fade-in-delay 1s ease-out 0.3s both;
  }

  /* Slide across animation for loading screen */
  @keyframes slide-across {
    0% {
      transform: translateX(-100%);
    }
    100% {
      transform: translateX(100%);
    }
  }

  .animate-slide-across {
    animation: slide-across 2s linear infinite;
  }

  /* Review mode styles */
  .review-mode input[readonly],
  .review-mode select[disabled],
  .review-mode textarea[readonly] {
    background-color: rgba(59, 130, 246, 0.1) !important;
    border-color: rgba(96, 165, 250, 0.2) !important;
    cursor: not-allowed;
  }

  .review-mode input[type='checkbox'][disabled],
  .review-mode input[type='radio'][disabled] {
    opacity: 0.7;
    cursor: not-allowed;
  }

  /* Enhanced font sizes for review mode - ICT Director readability */
  .review-mode {
    font-size: 1.125rem; /* 18px base font size */
    line-height: 1.6;
  }

  .review-mode .label,
  .review-mode label {
    font-size: 1.25rem !important; /* 20px for labels */
    font-weight: 600 !important;
    line-height: 1.5;
  }

  .review-mode input,
  .review-mode select,
  .review-mode textarea {
    font-size: 1.125rem !important; /* 18px for form inputs */
    line-height: 1.5;
    padding: 0.875rem 1rem !important; /* Increase padding for better readability */
  }

  .review-mode h1,
  .review-mode h2,
  .review-mode h3 {
    font-size: 1.5rem !important; /* 24px for headings */
    line-height: 1.4;
  }

  .review-mode p,
  .review-mode div,
  .review-mode span {
    font-size: 1.125rem; /* 18px for general text */
    line-height: 1.6;
  }

  .review-mode .text-xs {
    font-size: 1rem !important; /* Override small text to be readable */
  }

  .review-mode .text-sm {
    font-size: 1.125rem !important; /* Override small text */
  }

  .review-mode .text-base {
    font-size: 1.25rem !important; /* Larger base text */
  }

  .review-mode .text-lg {
    font-size: 1.375rem !important; /* Even larger text */
  }

  .review-mode .text-xl {
    font-size: 1.5rem !important; /* Extra large text */
  }

  /* Review mode button styling for better visibility */
  .review-mode button {
    font-size: 1.125rem !important;
    padding: 0.875rem 1.5rem !important;
    font-weight: 600 !important;
  }

  /* Review mode card and section spacing - ORIGINAL (overridden by compact) */
  .review-mode .card,
  .review-mode .border-l-2 {
    padding: 2rem !important;
    margin-bottom: 1.5rem;
  }

  /* Review mode compact layout - overrides above for minimal scrolling */
  .review-mode-compact {
    /* Ultra-compact spacing for all review mode roles */
    line-height: 1.3 !important;
  }

  .review-mode-compact .medical-card {
    /* Minimal card spacing */
    margin-bottom: 0.25rem !important;
    padding: 0.5rem !important;
  }

  .review-mode-compact .space-y-0\.5 > * + * {
    margin-top: 0.125rem !important;
  }

  .review-mode-compact .space-y-1 > * + * {
    margin-top: 0.25rem !important;
  }

  .review-mode-compact .space-y-2 > * + * {
    margin-top: 0.375rem !important;
  }

  .review-mode-compact .space-y-3 > * + * {
    margin-top: 0.5rem !important;
  }

  .review-mode-compact .space-y-4 > * + * {
    margin-top: 0.75rem !important;
  }

  .review-mode-compact .grid {
    gap: 0.5rem !important;
  }

  .review-mode-compact .p-1 {
    padding: 0.25rem !important;
  }

  .review-mode-compact .p-2 {
    padding: 0.5rem !important;
  }

  .review-mode-compact .p-3 {
    padding: 0.75rem !important;
  }

  .review-mode-compact .p-4 {
    padding: 1rem !important;
  }

  .review-mode-compact .mb-0\.5 {
    margin-bottom: 0.125rem !important;
  }

  .review-mode-compact .mb-1 {
    margin-bottom: 0.25rem !important;
  }

  .review-mode-compact .mb-1 {
    margin-bottom: 0.5rem !important;
  }

  .review-mode-compact .mb-3 {
    margin-bottom: 0.75rem !important;
  }

  .review-mode-compact .mb-4 {
    margin-bottom: 1rem !important;
  }

  .review-mode-compact h1,
  .review-mode-compact h2,
  .review-mode-compact h3,
  .review-mode-compact h4,
  .review-mode-compact h5 {
    margin-bottom: 0.25rem !important;
    line-height: 1.3 !important;
  }

  .review-mode-compact input,
  .review-mode-compact textarea,
  .review-mode-compact select {
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
    line-height: 1.4 !important;
  }

  .review-mode-compact label {
    margin-bottom: 0.25rem !important;
    line-height: 1.3 !important;
  }

  .review-mode-compact .bg-white\/15,
  .review-mode-compact .bg-white\/20 {
    padding: 0.375rem !important;
  }

  /* Override review-mode card spacing for compact mode */
  .review-mode-compact.review-mode .card,
  .review-mode-compact.review-mode .border-l-2 {
    padding: 0.5rem !important;
    margin-bottom: 0.25rem !important;
  }

  .review-mode-compact button {
    padding: 0.5rem 1rem !important;
    font-size: 1rem !important;
  }

  /* Divisional Director compact layout */
  .divisional-director-compact {
    /* Reduce overall vertical spacing */
    line-height: 1.3 !important;
    font-size: 1.125rem; /* 18px base font size - matching review-mode */
  }

  .divisional-director-compact .medical-card {
    /* Tighter card spacing */
    margin-bottom: 0.25rem !important;
  }

  .divisional-director-compact .space-y-1 > * + * {
    margin-top: 0.125rem !important;
  }

  .divisional-director-compact .space-y-2 > * + * {
    margin-top: 0.25rem !important;
  }

  .divisional-director-compact .space-y-3 > * + * {
    margin-top: 0.375rem !important;
  }

  .divisional-director-compact .space-y-4 > * + * {
    margin-top: 0.5rem !important;
  }

  .divisional-director-compact input,
  .divisional-director-compact textarea,
  .divisional-director-compact select {
    padding-top: 0.25rem !important;
    padding-bottom: 0.25rem !important;
    font-size: 1.125rem !important; /* 18px for form inputs - matching review-mode */
    line-height: 1.5;
  }

  .divisional-director-compact .grid {
    gap: 0.5rem !important;
  }

  .divisional-director-compact label {
    margin-bottom: 0.125rem !important;
    font-size: 1.25rem !important; /* 20px for labels - matching review-mode */
    font-weight: 600 !important;
    line-height: 1.5;
  }

  .divisional-director-compact .bg-white\/15 {
    padding: 0.5rem !important;
  }

  /* Ultra-compact for divisional directors */
  .divisional-director-compact .medical-input {
    padding: 0.125rem 0.375rem !important;
    min-height: auto !important;
    height: 1.75rem !important;
  }

  .divisional-director-compact textarea {
    padding: 0.25rem 0.375rem !important;
    min-height: 2rem !important;
  }

  .divisional-director-compact .rounded-xl,
  .divisional-director-compact .rounded-lg {
    border-radius: 0.375rem !important;
  }

  .divisional-director-compact .shadow-lg {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
  }

  .divisional-director-compact h1,
  .divisional-director-compact h2,
  .divisional-director-compact h3,
  .divisional-director-compact h4,
  .divisional-director-compact h5 {
    margin-bottom: 0.25rem !important;
    font-size: 1.5rem !important; /* 24px for headings - matching review-mode */
    line-height: 1.4;
  }

  .divisional-director-compact .mb-1 {
    margin-bottom: 0.125rem !important;
  }

  .divisional-director-compact .mb-1 {
    margin-bottom: 0.25rem !important;
  }

  .divisional-director-compact .mb-3 {
    margin-bottom: 0.375rem !important;
  }

  .divisional-director-compact .mb-4 {
    margin-bottom: 0.5rem !important;
  }

  .divisional-director-compact .p-3 {
    padding: 0.375rem !important;
  }

  .divisional-director-compact .p-4 {
    padding: 0.5rem !important;
  }

  /* Button styling for divisional director matching review-mode */
  .divisional-director-compact button {
    font-size: 1.125rem !important;
    padding: 0.5rem 1rem !important;
    font-weight: 600 !important;
  }

  /* General text styling for divisional director matching review-mode */
  .divisional-director-compact p,
  .divisional-director-compact div,
  .divisional-director-compact span {
    font-size: 1.125rem; /* 18px for general text */
    line-height: 1.6;
  }

  /* Text size classes for divisional director matching review-mode */
  .divisional-director-compact .text-xs {
    font-size: 1rem !important;
  }

  .divisional-director-compact .text-sm {
    font-size: 1.125rem !important;
  }

  .divisional-director-compact .text-base {
    font-size: 1.25rem !important;
  }

  .divisional-director-compact .text-lg {
    font-size: 1.375rem !important;
  }

  .divisional-director-compact .text-xl {
    font-size: 1.5rem !important;
  }

  /* ICT Director compact layout - matching review-mode font sizes */
  .ict-director-compact {
    /* Base font size matching review-mode */
    font-size: 1.125rem; /* 18px base font size - matching review-mode */
    line-height: 1.6;
  }

  .ict-director-compact .medical-card {
    /* Compact card spacing */
    margin-bottom: 0.5rem !important;
    padding: 0.75rem !important;
  }

  .ict-director-compact .space-y-1 > * + * {
    margin-top: 0.25rem !important;
  }

  .ict-director-compact .space-y-2 > * + * {
    margin-top: 0.5rem !important;
  }

  .ict-director-compact .space-y-3 > * + * {
    margin-top: 0.75rem !important;
  }

  .ict-director-compact .space-y-4 > * + * {
    margin-top: 1rem !important;
  }

  .ict-director-compact .grid {
    gap: 0.75rem !important;
  }

  /* Labels with large font size matching review-mode */
  .ict-director-compact label,
  .ict-director-compact .label {
    font-size: 1.25rem !important; /* 20px for labels - matching review-mode */
    font-weight: 600 !important;
    line-height: 1.5;
    margin-bottom: 0.5rem !important;
  }

  /* Form inputs with large font size matching review-mode */
  .ict-director-compact input,
  .ict-director-compact textarea,
  .ict-director-compact select {
    font-size: 1.125rem !important; /* 18px for form inputs - matching review-mode */
    line-height: 1.5;
    padding: 0.875rem 1rem !important; /* Increase padding for better readability */
  }

  /* Headings with large font size matching review-mode */
  .ict-director-compact h1,
  .ict-director-compact h2,
  .ict-director-compact h3,
  .ict-director-compact h4,
  .ict-director-compact h5 {
    font-size: 1.5rem !important; /* 24px for headings - matching review-mode */
    line-height: 1.4;
    margin-bottom: 0.75rem !important;
  }

  /* Button styling for ICT director matching review-mode */
  .ict-director-compact button {
    font-size: 1.125rem !important;
    padding: 0.875rem 1.5rem !important;
    font-weight: 600 !important;
  }

  /* General text styling for ICT director matching review-mode */
  .ict-director-compact p,
  .ict-director-compact div,
  .ict-director-compact span {
    font-size: 1.125rem; /* 18px for general text */
    line-height: 1.6;
  }

  /* Text size classes for ICT director matching review-mode */
  .ict-director-compact .text-xs {
    font-size: 1rem !important; /* Override small text to be readable */
  }

  .ict-director-compact .text-sm {
    font-size: 1.125rem !important; /* Override small text */
  }

  .ict-director-compact .text-base {
    font-size: 1.25rem !important; /* Larger base text */
  }

  .ict-director-compact .text-lg {
    font-size: 1.375rem !important; /* Even larger text */
  }

  .ict-director-compact .text-xl {
    font-size: 1.5rem !important; /* Extra large text */
  }

  /* Head of IT readable layout - larger typography for review mode */
  .head-it-compact {
    font-size: 1.25rem; /* 20px base */
    line-height: 1.75;
  }
  .head-it-compact .text-xs {
    font-size: 1.125rem !important;
  }
  .head-it-compact .text-sm {
    font-size: 1.25rem !important;
  }
  .head-it-compact .text-base {
    font-size: 1.375rem !important;
  }
  .head-it-compact .text-lg {
    font-size: 1.5rem !important;
  }
  .head-it-compact .text-xl {
    font-size: 1.75rem !important;
  }
  .head-it-compact input,
  .head-it-compact select,
  .head-it-compact textarea {
    padding: 1rem 1.125rem !important;
    font-size: 1.25rem !important;
  }
  .head-it-compact label,
  .head-it-compact .label {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
  }
  .head-it-compact h1,
  .head-it-compact h2,
  .head-it-compact h3 {
    font-size: 1.75rem !important;
  }
  .head-it-compact h4,
  .head-it-compact h5 {
    font-size: 1.5rem !important;
  }
  .head-it-compact p,
  .head-it-compact span,
  .head-it-compact li,
  .head-it-compact td,
  .head-it-compact th {
    font-size: 1.25rem !important;
    line-height: 1.7;
  }

  /* Padding and margin adjustments for ICT director */
  .ict-director-compact .p-1 {
    padding: 0.5rem !important;
  }

  .ict-director-compact .p-2 {
    padding: 0.75rem !important;
  }

  .ict-director-compact .p-3 {
    padding: 1rem !important;
  }

  .ict-director-compact .p-4 {
    padding: 1.25rem !important;
  }

  .ict-director-compact .mb-1 {
    margin-bottom: 0.5rem !important;
  }

  .ict-director-compact .mb-2 {
    margin-bottom: 0.75rem !important;
  }

  .ict-director-compact .mb-3 {
    margin-bottom: 1rem !important;
  }

  .ict-director-compact .mb-4 {
    margin-bottom: 1.25rem !important;
  }

  .ict-director-compact .bg-white\/15,
  .ict-director-compact .bg-white\/20 {
    padding: 0.875rem !important;
  }

  /* HOD compact layout - Enhanced for minimal scrolling */
  .hod-compact {
    /* Better utilization of screen width for HOD users */
    width: 100% !important;
    max-width: 100% !important;
    line-height: 1.1 !important; /* Even tighter line height */
  }

  .hod-compact .medical-card {
    /* Tighter card spacing for HOD users while utilizing full width */
    margin-bottom: 0.375rem !important; /* Reduced from 0.5rem */
    padding: 0.375rem !important; /* Reduced from 0.5rem */
    width: 100% !important;
  }

  .hod-compact .space-y-1 > * + * {
    margin-top: 0.125rem !important; /* Reduced from 0.25rem */
  }

  .hod-compact .space-y-2 > * + * {
    margin-top: 0.25rem !important; /* Reduced from 0.375rem */
  }

  .hod-compact .space-y-3 > * + * {
    margin-top: 0.375rem !important; /* Reduced from 0.5rem */
  }

  .hod-compact .space-y-4 > * + * {
    margin-top: 0.5rem !important; /* Reduced from 0.75rem */
  }

  .hod-compact .grid {
    gap: 0.5rem !important; /* Reduced from 0.75rem */
    width: 100% !important;
  }

  .hod-compact .grid.grid-cols-1 {
    grid-template-columns: 1fr !important;
  }

  .hod-compact .grid.grid-cols-2 {
    grid-template-columns: repeat(2, 1fr) !important;
  }

  .hod-compact .grid.grid-cols-4 {
    grid-template-columns: repeat(4, 1fr) !important;
  }

  .hod-compact .p-2 {
    padding: 0.25rem !important; /* Reduced from 0.375rem */
  }

  .hod-compact .p-3 {
    padding: 0.375rem !important; /* Reduced from 0.5rem */
  }

  .hod-compact .p-4 {
    padding: 0.5rem !important; /* Reduced from 0.75rem */
  }

  .hod-compact .mb-1 {
    margin-bottom: 0.125rem !important; /* Reduced from 0.25rem */
  }

  .hod-compact .mb-1 {
    margin-bottom: 0.25rem !important; /* Reduced from 0.375rem */
  }

  .hod-compact .mb-3 {
    margin-bottom: 0.375rem !important; /* Reduced from 0.5rem */
  }

  .hod-compact .mb-4 {
    margin-bottom: 0.5rem !important; /* Reduced from 0.75rem */
  }

  .hod-compact h3,
  .hod-compact h4,
  .hod-compact h5 {
    font-size: 1rem !important;
    margin-bottom: 0.25rem !important;
    line-height: 1.3 !important;
  }

  /* Additional HOD-specific reductions for input elements */
  .hod-compact input,
  .hod-compact textarea,
  .hod-compact select {
    padding-top: 0.25rem !important; /* Reduced from 0.375rem */
    padding-bottom: 0.25rem !important; /* Reduced from 0.375rem */
  }

  .hod-compact label {
    margin-bottom: 0.125rem !important; /* Reduced from 0.25rem */
  }

  .hod-compact .medical-input {
    padding: 0.25rem 0.5rem !important; /* Reduced from 0.375rem 0.75rem */
    font-size: 0.875rem !important;
  }

  .hod-compact label {
    font-size: 0.875rem !important;
    margin-bottom: 0.125rem !important; /* Reduced from 0.25rem */
  }

  /* Better width utilization for HOD form sections */
  .hod-compact .flex-1 {
    width: 100% !important;
  }

  .hod-compact form {
    width: 100% !important;
    max-width: 100% !important;
  }

  .hod-compact .medical-glass-card {
    width: 100% !important;
    max-width: 100% !important;
  }

  .hod-compact section {
    width: 100% !important;
  }

  /* Table styling for modules summary */
  .vertical-align-top {
    vertical-align: top !important;
  }

  /* Custom scrollbar for modules summary table */
  .custom-scrollbar::-webkit-scrollbar {
    width: 6px;
  }

  .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
  }

  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(251, 191, 36, 0.3);
    border-radius: 3px;
  }

  .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(251, 191, 36, 0.5);
  }

  /* Fade transition for modals */
  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 0.3s ease;
  }

  .fade-enter-from,
  .fade-leave-to {
    opacity: 0;
  }

  /* Modal animation styles */
  .fade-enter-active .transform,
  .fade-leave-active .transform {
    transition: all 0.3s ease;
  }

  .fade-enter-from .transform {
    transform: scale(0.9) translateY(-20px);
    opacity: 0;
  }

  .fade-leave-to .transform {
    transform: scale(0.9) translateY(-20px);
    opacity: 0;
  }

  /* Mobile modal adjustments */
  @media (max-width: 768px) {
    .max-w-lg {
      max-width: calc(100vw - 2rem);
    }
  }
</style>

<script>
  /* eslint-disable vue/no-parsing-error */
  /* eslint-disable no-unused-vars */

  // Import required services
  import Header from '@/components/header.vue'
  import ModernSidebar from '@/components/ModernSidebar.vue'
  import OrbitingDots from '@/components/OrbitingDots.vue'
  import AppFooter from '@/components/footer.vue'
  import UnifiedLoadingBanner from '@/components/common/UnifiedLoadingBanner.vue'
  import authService from '@/services/authService'
  import combinedAccessService from '@/services/combinedAccessService'
  import ictOfficerService from '@/services/ictOfficerService'
  import bothServiceFormService from '@/services/bothServiceFormService'
  import signatureService from '@/services/signatureService'
  import { useAuthStore } from '@/stores/auth'

  export default {
    name: 'BothServiceForm',
    components: {
      Header,
      ModernSidebar,
      OrbitingDots,
      AppFooter,
      UnifiedLoadingBanner
    },
    inject: {
      authStore: {
        from: 'authStore',
        default: () => useAuthStore()
      }
    },

    // Props for component
    props: {
      requestId: {
        type: [String, Number],
        default: null
      }
    },

    data() {
      return {
        // UI: Head of IT approval success modal
        showHeadItApproveSuccessModal: false,
        // ICT Officer Grant Access popup state
        showGrantAccessPopup: false,
        grantAccessComment: '',
        grantAccessError: '',
        // Recently added comment meta for highlight
        lastAddedCommentMeta: null,
        // Signature handling
        signaturePreview: '',
        signatureFileName: '',
        // HOD Access Rights (decided during HOD approval)
        hodAccessType: 'permanent',
        hodTemporaryUntil: '',
        hodTemporaryUntilError: '',
        // HOD Comments for access category specification
        hodComments: '',
        // Approval signatures
        hodSignaturePreview: '',
        hodSignatureFileName: '',
        divDirectorSignaturePreview: '',
        divDirectorSignatureFileName: '',
        directorICTSignaturePreview: '',
        directorICTSignatureFileName: '',
        // Implementation signatures
        headITSignaturePreview: '',
        headITSignatureFileName: '',
        ictOfficerSignaturePreview: '',
        ictOfficerSignatureFileName: '',
        // Jeeva Requested for selector state,
        // Role-based comment draft (saved on approval)
        roleCommentsDraft: '',
        // Jeeva Requested for selector state
        jeevaItemOpen: false,
        jeevaItemFocusIndex: 0,
        jeevaItemOptions: [
          'Use',
          'Revoke',
          'Access Rights',
          'Approval',
          'Comments',
          'For Implementation'
        ],
        jeevaItemSelections: [],
        // Dynamic modules loaded from database
        wellsoftModules: [],
        jeevaModules: [],
        // Suggested Internet purposes
        // internetPurposes: ['Research','Training','Remote Work','Telemedicine','Email/Communication'], // Removed duplicate - using the one in form data

        form: {
          shared: { pfNumber: '', staffName: '', department: '', phone: '' },
          // accessRights removed - this will be decided by HOD during approval
          approvals: {
            hod: {
              name: '',
              signature: '',
              date: ''
            },
            divisionalDirector: {
              name: '',
              signature: '',
              date: ''
            },
            directorICT: {
              name: '',
              signature: '',
              date: ''
            }
          },
          comments: '',
          implementation: {
            headIT: {
              name: '',
              signature: '',
              date: ''
            },
            ictOfficer: {
              name: '',
              signature: '',
              date: ''
            }
          }
        },

        selectedWellsoft: [],
        selectedJeeva: [],
        wellsoftRequestType: 'use',
        jeevaRequestType: 'use', // HOD can select use/revoke for Jeeva modules
        // search queries
        wellsoftQuery: '',
        jeevaQuery: '',
        internetPurposes: ['', '', '', ''],

        tabs: [],
        activeTab: '',
        openAccordions: new Set(),
        moduleData: {},

        confirm: { key: '', label: '' },
        toast: { show: false, message: '' },
        // Digital signature state
        isSigning: false,
        hasUserSigned: false,
        lastSignedAt: null,
        errors: { pfNumber: '', staffName: '' },
        // Review mode data
        requestData: null,
        loading: false,
        error: null,
        // Digital signature history for this document
        signatureHistory: [],
        // Rejection modal data
        showRejectionModal: false,
        rejectionReason: '',
        // Current authenticated user data
        currentUser: null,
        // Performance optimization flags
        loadingRequestData: false,
        // Main loading state for initial page load
        isLoading: false,
        // Debouncing flag for approval button
        processing: false,
        // Divisional Director comments (editable for divisional director)
        editableDivisionalDirectorComments: '',
        // Virtual scrolling configuration
        maxVisibleComments: 50,
        maxVisibleModules: 10,
        // Success message card state
        showApprovalSuccessCard: false,
        // ICT Officer confirmation popup
        showIctOfficerConfirmation: false,
        // UI collapsible toggle for Approval sections
        showApprovalSections: false,
        // UI collapsible toggle for Implementation sections
        showImplementationSections: false
      }
    },
    computed: {
      // Environment check for development features
      isDevelopment() {
        // Use process.env.NODE_ENV if available (provided by webpack DefinePlugin)
        // or fallback to checking hostname for development detection
        try {
          return process.env.NODE_ENV === 'development'
        } catch {
          // Fallback if process.env is not available
          return (
            window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
          )
        }
      },

      // Review mode check
      isReviewMode() {
        return this.getRequestId != null
      },

      // Get effective request ID (prefer route param, then loaded record id; ignore stale query if params present)
      getRequestId() {
        const propId = this.requestId
        const paramId = this.$route?.params?.id
        const queryId = this.$route?.query?.id
        // Coerce to numeric string if possible
        const norm = (v) => (v == null ? null : String(v).trim())
        let id = norm(propId) || norm(paramId) || null
        // If we have loaded requestData.id and it differs, trust the loaded record id
        const loadedId = norm(this.requestData?.id)
        if (!id && norm(queryId)) {
          id = norm(queryId)
        }
        if (loadedId && id && loadedId !== id) {
          // Prefer backend-loaded record id to avoid mismatches
          id = loadedId
        } else if (!id && loadedId) {
          id = loadedId
        }
        return id
      },

      // Formatted display id like REQ-000065; prefer backend-provided request_id if present
      displayRequestId() {
        const backendRef = this.requestData?.request_id || this.requestData?.requestId
        if (backendRef) return String(backendRef)
        const raw = this.getRequestId
        if (!raw) return ''
        const num = String(raw).replace(/\D+/g, '')
        return 'REQ-' + (num ? num.padStart(6, '0') : String(raw))
      },

      currentTab() {
        return this.tabs.find((t) => t.key === this.activeTab) || null
      },
      filteredWellsoft() {
        const q = (this.wellsoftQuery || '').toLowerCase()
        return !q
          ? this.wellsoftModules
          : this.wellsoftModules.filter((m) => m.toLowerCase().includes(q))
      },
      filteredJeeva() {
        const q = (this.jeevaQuery || '').toLowerCase()
        return !q ? this.jeevaModules : this.jeevaModules.filter((m) => m.toLowerCase().includes(q))
      },

      // Cached filtered internet purposes for performance
      filteredInternetPurposes() {
        if (!Array.isArray(this.internetPurposes)) return []
        return this.internetPurposes.filter((p) => p && p.trim())
      },

      // Virtual scrolling for comments - only show a limited number for performance
      visibleComments() {
        if (!Array.isArray(this.previousComments)) return []
        return this.previousComments.slice(0, this.maxVisibleComments)
      },

      tomorrow() {
        const d = new Date()
        d.setDate(d.getDate() + 1)
        const yyyy = d.getFullYear()
        const mm = String(d.getMonth() + 1).padStart(2, '0')
        const dd = String(d.getDate()).padStart(2, '0')
        return `${yyyy}-${mm}-${dd}`
      },

      signatureDisplay() {
        // Staff/applicant signature display for the top Signature card
        try {
          const staff = (this.requesterName || '').trim().toLowerCase()
          const list = Array.isArray(this.signatureHistory) ? this.signatureHistory : []
          const getTs = (s) => s?.signed_at || s?.created_at || s?.timestamp

          // 1) Prefer exact match with staff/applicant name
          const staffEntry = list.find(
            (s) =>
              String(s.user_name || s.name || '')
                .trim()
                .toLowerCase() === staff
          )
          if (staffEntry) {
            return {
              name: staffEntry.user_name || staffEntry.name,
              at: getTs(staffEntry)
            }
          }

          // 2) Fall back to earliest signature (usually staff signed first)
          if (list.length > 0) {
            const sortedAsc = [...list]
              .filter((s) => !!getTs(s))
              .sort((a, b) => new Date(getTs(a)) - new Date(getTs(b)))
            const first = sortedAsc[0] || list[0]
            return { name: first.user_name || first.name, at: getTs(first) }
          }

          return null
        } catch (e) {
          return null
        }
      },

      requesterName() {
        // Resolve the applicant (staff) name from available fields
        try {
          return (
            this.form?.shared?.staffName ||
            this.requestData?.shared?.staffName ||
            this.requestData?.staff_name ||
            this.requestData?.full_name ||
            ''
          )
        } catch {
          return ''
        }
      },

      summaryErrors() {
        const list = []
        // Defensive guards for shared
        const shared = this.form?.shared || {}
        if (!shared.pfNumber) list.push('PF Number is required.')
        if (!shared.staffName) list.push('Staff Name is required.')

        // Tabs validation (guard against undefined tabs/moduleData)
        const tabs = Array.isArray(this.tabs) ? this.tabs : []
        tabs.forEach((t) => {
          if (!t || !t.key) return
          // accessType validation removed - will be decided by HOD during approval
          if (t.type === 'internet' && !this.internetPurposes[0].trim()) {
            list.push('Internet Purpose is required.')
          }
        })
        return list
      },

      // Check if signature exists in the database
      hasSignature() {
        const rd = this.requestData || {}
        const hasData = !!rd
        const hasPath = !!rd.signature_path
        const pathNotEmpty = hasPath && String(rd.signature_path).trim() !== ''
        const digitalFlag = rd.digitalSignature === true || rd.digital_signature === true
        const signatureCounts = Number(rd.signatures_count || rd.signature_count || 0) > 0
        const historyHasAny =
          Array.isArray(this.signatureHistory) && this.signatureHistory.length > 0
        const staffMatch = this.historyHasSigner(this.requesterName)

        // Prefer any positive digital signal, but prioritize staff match
        const result =
          hasData && (pathNotEmpty || digitalFlag || signatureCounts || staffMatch || historyHasAny)

        if (this.isDevelopment) {
          console.log('hasSignature debug:', {
            signaturePath: rd.signature_path,
            digitalFlag,
            signatureCounts: rd.signatures_count || rd.signature_count,
            historyCount: (this.signatureHistory || []).length,
            staffMatch,
            staffName: this.requesterName,
            result
          })
        }

        return result
      },

      // Get request types from loaded data
      requestTypes() {
        if (!this.requestData) return []

        // Handle both array and object formats
        let types = this.requestData.request_types || this.requestData.request_type || []
        if (!Array.isArray(types)) {
          types = [types]
        }

        if (this.isDevelopment) {
          console.log('Request types (computed):', types)
        }
        return types
      },

      // Check if specific request type is included (optimized with direct checks)
      hasWellsoftRequest() {
        const types = this.requestTypes
        return types.includes('wellsoft')
      },

      hasJeevaRequest() {
        const types = this.requestTypes
        return types.includes('jeeva_access') || types.includes('jeeva')
      },

      hasInternetRequest() {
        const types = this.requestTypes
        return types.includes('internet_access_request') || types.includes('internet')
      },

      // Determine if sections should be readonly based on review mode and request type
      isWellsoftReadonly() {
        return (this.isReviewMode && !this.hasWellsoftRequest) || this.isFormSectionReadOnly
      },

      isJeevaReadonly() {
        return (this.isReviewMode && !this.hasJeevaRequest) || this.isFormSectionReadOnly
      },

      isInternetReadonly() {
        return (this.isReviewMode && !this.hasInternetRequest) || this.isFormSectionReadOnly
      },

      // Get current approval stage from request status
      currentApprovalStage() {
        if (!this.requestData) return 'pending'

        const status = (this.requestData.status || 'pending').toLowerCase()
        const stageMap = {
          pending: 'hod',
          pending_hod: 'hod',
          hod_approved: 'divisional',
          pending_divisional: 'divisional',
          divisional_approved: 'ict_director',
          pending_ict_director: 'ict_director',
          pending_dict: 'ict_director', // synonym sometimes used in DICT endpoints
          ict_director_approved: 'head_it',
          dict_approved: 'head_it', // synonym
          pending_head_it: 'head_it',
          head_it_approved: 'ict_officer',
          pending_ict_officer: 'ict_officer',
          implementation_in_progress: 'ict_officer',
          implemented: 'completed',
          approved: 'completed',
          completed: 'completed',
          hod_rejected: 'completed',
          // Rejected statuses - requests go back to HOD for revision
          divisional_rejected: 'hod', // Divisional rejected - back to HOD
          ict_director_rejected: 'hod', // ICT Director rejected - back to HOD
          head_it_rejected: 'hod', // Head IT rejected - back to HOD
          ict_officer_rejected: 'hod', // ICT Officer rejected - back to HOD
          cancelled: 'completed'
        }

        // Primary: map by overall status
        let stageFromStatus = stageMap[status]

        // Secondary: infer from per-stage statuses (works for ICT Officer-origin with skipped HOD/DIV)
        const hod = (this.requestData.hod_status || '').toLowerCase()
        const div = (this.requestData.divisional_status || '').toLowerCase()
        const dict = (
          this.requestData.ict_director_status ||
          this.requestData.dict_status ||
          ''
        ).toLowerCase()
        const head = (this.requestData.head_it_status || '').toLowerCase()
        const impl = (this.requestData.ict_officer_status || '').toLowerCase()

        let stageFromStages = null
        if (['implemented', 'approved', 'completed'].includes(impl)) {
          stageFromStages = 'completed'
        } else if (
          !['approved', 'rejected'].includes(head) &&
          ['approved', 'skipped'].includes(dict)
        ) {
          // Head IT stage when DICT has processed
          stageFromStages = 'head_it'
        } else if (
          !['approved', 'rejected'].includes(dict) &&
          ['approved', 'skipped'].includes(hod) &&
          ['approved', 'skipped'].includes(div)
        ) {
          // ICT Director stage when HOD/DIV done (approved/skipped) and DICT pending/empty
          stageFromStages = 'ict_director'
        } else if (!['approved', 'rejected'].includes(div) && hod === 'approved') {
          stageFromStages = 'divisional'
        } else if (!['approved', 'rejected'].includes(hod)) {
          stageFromStages = 'hod'
        }

        // Reconcile: choose the later stage between status-based and per-stage inference
        const rank = {
          hod: 1,
          divisional: 2,
          ict_director: 3,
          head_it: 4,
          ict_officer: 5,
          completed: 6
        }
        const pick = (a, b) => {
          if (!a) return b
          if (!b) return a
          return (rank[b] || 0) > (rank[a] || 0) ? b : a
        }

        const finalStage = pick(stageFromStatus, stageFromStages) || 'hod'
        return finalStage
      },

      // Determine if approval sections should be readonly based on current stage
      isHodApprovalEditable() {
        // Check if edit mode is explicitly enabled via query parameter
        const queryMode = this.$route?.query?.mode
        const queryRole = this.$route?.query?.role
        const queryReadonly = this.$route?.query?.readonly

        // Strict view-only mode - disable all editing
        if (queryMode === 'view' && queryReadonly === 'true') {
          return false
        }

        // If mode=edit and role=hod in query params, allow HOD to edit
        if (queryMode === 'edit' && queryRole === 'hod') {
          const userRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
          const hodRoles = ['head_of_department', 'hod']
          if (hodRoles.includes(userRole)) {
            console.log('🏥 HOD Edit Mode Enabled via Query Parameter')
            return true
          }
        }

        // If mode=readonly, disable editing
        if (queryMode === 'readonly') {
          return false
        }

        // Active only for HOD users while reviewing at the HOD stage
        if (!this.isReviewMode || !this.requestData) return false
        const userRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
        const hodRoles = ['head_of_department', 'hod']
        const status = this.requestData.status || 'pending'
        // HOD can edit only in these scenarios:
        // 1. Initial HOD approval stage (pending, pending_hod)
        // 2. When request is rejected back to HOD from higher levels
        const hodEditableStatuses = [
          'pending',
          'pending_hod',
          'hod_pending', // Alternative naming convention
          'submitted', // Initial submission state
          'divisional_rejected', // Rejected by Divisional Director - back to HOD
          'ict_director_rejected', // Rejected by ICT Director - back to HOD
          'head_it_rejected', // Rejected by Head IT - back to HOD
          'ict_officer_rejected' // Rejected by ICT Officer - back to HOD
        ]

        const canEdit = hodRoles.includes(userRole) && hodEditableStatuses.includes(status)

        // Reduced debug logging frequency to improve performance
        if (this.isDevelopment && Math.random() < 0.1) {
          // Only log 10% of the time
          console.log('🏥 HOD Module Access Check:', {
            userRole,
            canEdit,
            status,
            requestId: this.getRequestId
          })
        }

        return canEdit
      },

      // Allow HOD users to upload signature during HOD stage even if other gates are strict
      canUploadHodSignature() {
        // In strict view-only mode, disable all uploads
        const queryMode = this.$route?.query?.mode
        const queryReadonly = this.$route?.query?.readonly
        if (queryMode === 'view' && queryReadonly === 'true') {
          return false
        }

        if (this.isHodApprovalEditable) return true

        // Allow HOD to upload signature in readonly mode
        if (queryMode === 'readonly') {
          const userRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
          const hodRoles = ['head_of_department', 'hod']
          if (hodRoles.includes(userRole)) {
            return true
          }
        }

        if (!this.isReviewMode) return false
        const userRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
        const hodRoles = [
          'head_of_department',
          'hod',
          'department_head',
          'head_department',
          'hod_user',
          'head_of_dept'
        ]
        return hodRoles.includes(userRole) && this.currentApprovalStage === 'hod'
      },

      // Check if request was rejected back to HOD from a higher approval stage
      isRejectedBackToHod() {
        if (!this.requestData) return false
        const status = this.requestData.status || 'pending'
        const rejectedStatuses = [
          'divisional_rejected',
          'ict_director_rejected',
          'head_it_rejected',
          'ict_officer_rejected'
        ]
        return rejectedStatuses.includes(status)
      },

      // Get rejection details for display
      rejectionDetails() {
        if (!this.isRejectedBackToHod || !this.requestData) return null

        const status = this.requestData.status || 'pending'
        const rejectionMap = {
          divisional_rejected: {
            stage: 'Divisional Director',
            reason:
              this.requestData.divisional_rejection_reason || this.requestData.rejection_reason,
            date: this.requestData.divisional_rejection_date || this.requestData.rejection_date
          },
          ict_director_rejected: {
            stage: 'ICT Director',
            reason: this.requestData.dict_rejection_reason || this.requestData.rejection_reason,
            date: this.requestData.dict_rejection_date || this.requestData.rejection_date
          },
          head_it_rejected: {
            stage: 'Head of IT',
            reason: this.requestData.head_it_rejection_reason || this.requestData.rejection_reason,
            date: this.requestData.head_it_rejection_date || this.requestData.rejection_date
          },
          ict_officer_rejected: {
            stage: 'ICT Officer',
            reason:
              this.requestData.ict_officer_rejection_reason || this.requestData.rejection_reason,
            date: this.requestData.ict_officer_rejection_date || this.requestData.rejection_date
          }
        }

        return rejectionMap[status] || null
      },

      // Expose current saved comment for diffing and placeholders
      roleCommentOriginal() {
        return this.getExistingRoleComment()
      },

      // Role-based comment bindings
      roleCommentKey() {
        const role = (this.getUserRole() || '').toLowerCase()
        if (role === 'divisional_director') return 'divisional_director_comments'
        if (role === 'head_it' || role === 'head_of_it') return 'head_it_comments'
        if (role === 'ict_officer' || role === 'officer_ict') return 'ict_officer_comments'
        if (role === 'ict_director' || role === 'dict') return 'ict_director_comments'
        return ''
      },
      roleCommentLabel() {
        const role = (this.getUserRole() || '').toLowerCase()
        if (role === 'divisional_director') return 'Divisional Director Comment'
        if (role === 'head_it' || role === 'head_of_it') return 'Head of IT Comment'
        if (role === 'ict_officer' || role === 'officer_ict') return 'ICT Officer Comment'
        if (role === 'ict_director' || role === 'dict') return 'ICT Director Comment'
        return 'Comment'
      },
      isRoleCommentEditable() {
        // Make role comment writable for all approver roles regardless of stage
        const role = (this.getUserRole() || '').toLowerCase()
        return [
          'divisional_director',
          'head_it',
          'head_of_it',
          'ict_officer',
          'officer_ict',
          'ict_director',
          'dict'
        ].includes(role)
      },

      isDivisionalApprovalEditable() {
        // Active only for Divisional Director users while reviewing at the divisional stage
        if (!this.isReviewMode || !this.requestData) return false
        const userRole = (this.getUserRole() || '').toLowerCase()
        const divisionalRoles = ['divisional_director']
        return this.currentApprovalStage === 'divisional' && divisionalRoles.includes(userRole)
      },

      // Check if current user is a divisional director viewing the form (should see everything as read-only except their approval section)
      isDivisionalDirectorReadOnly() {
        if (!this.isReviewMode || !this.requestData) return false
        const userRole = (this.getUserRole() || '').toLowerCase()
        const divisionalRoles = ['divisional_director']
        // Active only for divisional director users (ICT directors handled separately)
        return divisionalRoles.includes(userRole)
      },

      // Check if current user is ICT Director viewing the form (should see everything as read-only except their approval section)
      isIctDirectorReadOnly() {
        if (!this.isReviewMode || !this.requestData) return false
        const userRole = (this.getUserRole() || '').toLowerCase()
        const ictDirectorRoles = ['ict_director', 'dict']
        // Active when user is ICT Director (no longer depends on role parameter)
        return ictDirectorRoles.includes(userRole)
      },

      // Consolidated read-only state for form sections (combines divisional director and ICT director)
      isFormSectionReadOnly() {
        // Check if readonly mode is explicitly set via query parameter
        const queryMode = this.$route?.query?.mode
        const queryReadonly = this.$route?.query?.readonly

        // Strict view-only mode (mode=view with readonly=true)
        if (queryMode === 'view' && queryReadonly === 'true') {
          if (this.isDevelopment) {
            console.log('🔒 Form in STRICT VIEW-ONLY mode')
          }
          return true
        }

        if (queryMode === 'readonly') {
          if (this.isDevelopment) {
            console.log('🔒 Form sections are READ-ONLY via query parameter')
          }
          return true
        }

        // If HOD can edit (initial approval or after rejection back to them), sections should be editable
        // This is the primary check - HOD users should always be able to edit when it's their turn
        if (this.isHodApprovalEditable) {
          if (this.isDevelopment) {
            console.log('🔓 Form sections are EDITABLE for HOD user')
          }
          return false
        }

        // Once HOD has approved and request moves to higher stages, make all modules read-only
        if (this.isReviewMode && this.requestData) {
          const status = this.requestData.status || 'pending'
          const hodStatus = this.requestData.hod_status || 'pending'
          const postHodStatuses = [
            'hod_approved',
            'pending_divisional',
            'divisional_approved',
            'pending_ict_director',
            'ict_director_approved',
            'pending_head_it',
            'head_it_approved',
            'pending_ict_officer',
            'implemented',
            'approved'
          ]

          // If status indicates form has moved beyond HOD stage, make modules read-only
          if (postHodStatuses.includes(status) || postHodStatuses.includes(hodStatus)) {
            if (this.isDevelopment) {
              console.log('🔒 Form sections are READ-ONLY - request moved beyond HOD stage:', {
                status,
                hodStatus
              })
            }
            return true
          }
        }

        const readOnlyResult = this.isDivisionalDirectorReadOnly || this.isIctDirectorReadOnly
        if (this.isDevelopment && readOnlyResult) {
          console.log('🔒 Form sections are READ-ONLY for divisional/ICT director')
        }

        return readOnlyResult
      },

      // Check if we're in strict view-only mode (no edits or actions allowed)
      isStrictViewMode() {
        const queryMode = this.$route?.query?.mode
        const queryReadonly = this.$route?.query?.readonly
        return queryMode === 'view' && queryReadonly === 'true'
      },

      // Check if current user is ICT Director
      isIctDirectorUser() {
        const userRole = (this.getUserRole() || '').toLowerCase()
        return ['ict_director', 'dict'].includes(userRole)
      },

      // Check if current user is Divisional Director
      isDivisionalDirectorUser() {
        const userRole = (this.getUserRole() || '').toLowerCase()
        return ['divisional_director'].includes(userRole)
      },

      // Check if current user is Head IT
      isHeadItUser() {
        const userRole = (this.getUserRole() || '').toLowerCase()
        return ['head_it', 'head_of_it'].includes(userRole)
      },

      // Check if Select ICT Officer button should be shown
      shouldShowSelectIctOfficerButton() {
        // Only show for Head IT users in review mode
        if (!this.isHeadItUser || !this.isReviewMode || !this.requestData) {
          return false
        }

        const status = this.requestData.status || 'pending'

        // Show button if:
        // 1. Request has been approved by Head IT (status is 'head_it_approved')
        // 2. But no ICT Officer has been assigned yet (check if ict_officer_name is empty/null)
        // 3. And request hasn't been completed yet
        const isHeadItApproved = status === 'head_it_approved' || status === 'pending_ict_officer'
        const noIctOfficerAssigned =
          !this.requestData.ict_officer_name && !this.form.implementation.ictOfficer.name
        const notCompleted = !['implemented', 'approved', 'completed'].includes(status)

        const shouldShow = isHeadItApproved && noIctOfficerAssigned && notCompleted

        // Debug logging in development
        if (this.isDevelopment && this.isHeadItUser) {
          console.log('🔍 Select ICT Officer Button Visibility Check:', {
            isHeadItUser: this.isHeadItUser,
            isReviewMode: this.isReviewMode,
            status: status,
            isHeadItApproved: isHeadItApproved,
            noIctOfficerAssigned: noIctOfficerAssigned,
            notCompleted: notCompleted,
            ict_officer_name: this.requestData.ict_officer_name,
            form_ict_officer_name: this.form.implementation.ictOfficer.name,
            shouldShow: shouldShow
          })
        }

        return shouldShow
      },

      // Check if ICT Director approval is active (based on user role and approval stage)
      isIctDirectorApprovalActive() {
        const userRole = (this.getUserRole() || '').toLowerCase()

        // ICT Director section is active if:
        // 1. User is ICT Director and we're in review mode at the ICT Director stage
        return (
          ['ict_director', 'dict'].includes(userRole) &&
          this.isReviewMode &&
          this.currentApprovalStage === 'ict_director'
        )
      },

      isIctDirectorApprovalEditable() {
        // Allow editing when the request is at or effectively at the ICT Director stage
        if (!this.isReviewMode) return true
        if (this.currentApprovalStage === 'ict_director') return true
        // Fallback: infer from per-stage statuses
        try {
          const hod = (this.requestData?.hod_status || '').toLowerCase()
          const div = (this.requestData?.divisional_status || '').toLowerCase()
          const dict = (
            this.requestData?.ict_director_status ||
            this.requestData?.dict_status ||
            ''
          ).toLowerCase()
          const isPreDictDone =
            ['approved', 'skipped'].includes(hod) && ['approved', 'skipped'].includes(div)
          const isDictPending = !['approved', 'rejected'].includes(dict)
          return isPreDictDone && isDictPending
        } catch {
          return false
        }
      },

      isHeadItApprovalEditable() {
        // Only allow Head IT users to edit when it's their stage
        if (!this.isReviewMode) return true // In create mode, allow editing

        const userRole = (this.getUserRole() || '').toLowerCase()
        const headItRoles = ['head_it', 'head_of_it']
        const currentStage = this.currentApprovalStage
        const result = currentStage === 'head_it' && headItRoles.includes(userRole)

        // Debug logging for role-based access control verification (development only)
        if (this.isDevelopment) {
          console.log('🔒 Head IT Approval Access Control Check:', {
            userRole,
            currentStage,
            headItRoles,
            isStageMatch: currentStage === 'head_it',
            isRoleMatch: headItRoles.includes(userRole),
            finalResult: result,
            isReviewMode: this.isReviewMode
          })
        }

        // Only active for Head IT users when at the head_it approval stage
        return result
      },

      isIctOfficerApprovalEditable() {
        // Only allow ICT Officer users to edit when it's their stage
        if (!this.isReviewMode) return true // In create mode, allow editing

        const userRole = (this.getUserRole() || '').toLowerCase()
        const ictOfficerRoles = ['ict_officer', 'officer_ict']

        // For ICT Officers logged in, always allow editing if they have the proper role
        // This simplifies the logic and ensures they can always interact with their section
        const isIctOfficer = ictOfficerRoles.includes(userRole)

        // Check if current user is assigned ICT Officer for this request
        const currentUserId = this.currentUser?.id
        const isAssignedOfficer =
          this.requestData?.ict_officer_user_id &&
          this.requestData.ict_officer_user_id === currentUserId

        // Check if request is in a state that allows ICT Officer implementation
        const status = this.requestData?.status || ''
        const canImplement = [
          'assigned_to_ict',
          'implementation_in_progress',
          'head_it_approved',
          'pending_ict_officer',
          'head_it_pending',
          'ict_director_approved'
        ].includes(status)

        // Check if implementation is not yet completed - be more thorough in checking
        const notYetImplemented =
          !this.requestData?.ict_officer_implemented_at &&
          this.requestData?.ict_officer_status !== 'implemented' &&
          this.requestData?.ict_officer_status !== 'rejected' &&
          this.requestData?.status !== 'implemented' &&
          this.requestData?.status !== 'approved' &&
          this.requestData?.status !== 'completed' &&
          !this.getValidIctOfficerDate

        // Allow editing if user is ICT Officer and:
        // 1. Access has not yet been implemented/granted AND
        // 2. Either they are assigned OR request is in implementable state
        const result = isIctOfficer && notYetImplemented && (isAssignedOfficer || canImplement)

        // Enhanced debug logging for role-based access control verification
        console.log('🔒 ICT Officer Approval Access Control DETAILED DEBUG:', {
          '=== BASIC CONDITIONS ===': '---',
          userRole: userRole,
          'userRole valid?': ictOfficerRoles.includes(userRole),
          currentStage: this.currentApprovalStage,
          status: status,
          isReviewMode: this.isReviewMode,
          requestId: this.getRequestId,
          '=== USER DATA ===': '---',
          currentUserId: currentUserId,
          'currentUser exists': !!this.currentUser,
          'currentUser object': this.currentUser,
          'currentUser name': this.currentUser?.name,
          'currentUser role': this.currentUser?.role || this.currentUser?.user_role,
          'getUserRole()': this.getUserRole(),
          '=== REQUEST DATA DETAILS ===': '---',
          'requestData exists': !!this.requestData,
          'full status': this.requestData?.status,
          assignedOfficerId: this.requestData?.ict_officer_user_id,
          ict_officer_name: this.requestData?.ict_officer_name,
          ict_officer_status: this.requestData?.ict_officer_status,
          ict_officer_implemented_at: this.requestData?.ict_officer_implemented_at,
          head_it_status: this.requestData?.head_it_status,
          ict_director_status: this.requestData?.ict_director_status,
          '=== CHECKS ===': '---',
          isIctOfficer: isIctOfficer,
          isAssignedOfficer: isAssignedOfficer,
          canImplement: canImplement,
          'canImplement statuses': [
            'assigned_to_ict',
            'implementation_in_progress',
            'head_it_approved',
            'pending_ict_officer'
          ],
          notYetImplemented: notYetImplemented,
          '=== FINAL ===': '---',
          finalResult: result,
          WHY_FALSE: result
            ? 'Access granted'
            : {
                noUserRole: !ictOfficerRoles.includes(userRole),
                notAssigned: !isAssignedOfficer,
                cannotImplement: !canImplement,
                alreadyImplemented: !notYetImplemented,
                allConditionsMet:
                  isIctOfficer && (isAssignedOfficer || canImplement || notYetImplemented)
              }
        })

        return result
      },

      // Check if ICT Officer has a valid implementation date
      getValidIctOfficerDate() {
        if (!this.requestData) return null

        const timestamp =
          this.requestData.ict_officer_implemented_at ||
          this.requestData.implementation?.ictOfficer?.date ||
          this.requestData.implementation?.ictOfficer?.approved_at

        if (!timestamp) return null

        try {
          const date = new Date(timestamp)
          // Check if date is valid and not too far in the past/future
          if (isNaN(date.getTime()) || date.getFullYear() < 2020 || date.getFullYear() > 2030) {
            if (this.isDevelopment) {
              console.warn('Invalid ICT Officer implementation timestamp detected:', timestamp)
            }
            return null
          }
          return timestamp
        } catch (error) {
          if (this.isDevelopment) {
            console.warn('Error parsing ICT Officer implementation timestamp:', timestamp, error)
          }
          return null
        }
      },

      // Check if a stage has been completed
      isStageCompleted() {
        return (stage) => {
          if (!this.requestData) return false

          // Check both status-based completion AND approval date presence
          // This ensures we catch completed stages even if status calculation is off
          const status = this.requestData.status || 'pending'
          const completedStages = {
            hod: [
              'hod_approved',
              'divisional_approved',
              'pending_divisional',
              'ict_director_approved',
              'pending_ict_director',
              'head_it_approved',
              'pending_head_it',
              'ict_officer_approved',
              'pending_ict_officer',
              'implemented',
              'approved'
            ],
            divisional: [
              'divisional_approved',
              'ict_director_approved',
              'pending_ict_director',
              'head_it_approved',
              'pending_head_it',
              'ict_officer_approved',
              'pending_ict_officer',
              'implemented',
              'approved'
            ],
            ict_director: [
              'ict_director_approved',
              'head_it_approved',
              'pending_head_it',
              'ict_officer_approved',
              'pending_ict_officer',
              'implemented',
              'approved'
            ],
            head_it: [
              'head_it_approved',
              'ict_officer_approved',
              'pending_ict_officer',
              'implemented',
              'approved'
            ],
            ict_officer: ['implemented', 'approved']
          }

          const statusBasedCompletion = completedStages[stage]?.includes(status) || false

          // Also check for approval date presence as a fallback
          // Check both flattened fields AND nested approval objects
          const dateBasedCompletion = {
            hod: !!(
              this.requestData.hod_approved_at ||
              this.requestData.approvals?.hod?.date ||
              this.requestData.approvals?.hod?.approved_at
            ),
            divisional: !!(
              this.requestData.divisional_approved_at ||
              this.requestData.approvals?.divisionalDirector?.date ||
              this.requestData.approvals?.divisionalDirector?.approved_at
            ),
            ict_director: !!(
              this.requestData.ict_director_approved_at ||
              this.requestData.dict_approved_at ||
              this.requestData.approvals?.directorICT?.date ||
              this.requestData.approvals?.directorICT?.approved_at
            ),
            head_it: !!(
              this.requestData.head_it_approved_at ||
              this.requestData.implementation?.headIT?.date ||
              this.requestData.implementation?.headIT?.approved_at
            ),
            ict_officer: !!(
              (this.requestData.ict_officer_status === 'implemented' ||
                this.requestData.ict_officer_status === 'approved') &&
              this.getValidIctOfficerDate
            )
          }

          const result = statusBasedCompletion || dateBasedCompletion[stage] || false

          // TEMPORARY DEBUG: Log stage completion check
          if (stage === 'hod' || stage === 'divisional' || stage === 'ict_director') {
            console.log(`🔍 isStageCompleted(${stage}):`, {
              status,
              statusBasedCompletion,
              dateBasedCompletion: dateBasedCompletion[stage],
              approvalDate:
                stage === 'hod'
                  ? this.requestData.hod_approved_at
                  : stage === 'divisional'
                    ? this.requestData.divisional_approved_at
                    : stage === 'ict_director'
                      ? this.requestData.ict_director_approved_at
                      : 'unknown',
              availableDateFields: Object.keys(this.requestData).filter(
                (key) =>
                  key.includes('approved') || key.includes('implemented') || key.includes('date')
              ),
              result
            })
          }

          return result
        }
      },

      // Check if a digital signature (by current user) is required for the current stage
      isSignatureRequiredForApproval() {
        if (!this.isReviewMode || !this.requestData) return false

        const stage = this.currentApprovalStage
        const userRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
        const roleMap = {
          hod: [
            'head_of_department',
            'hod',
            'department_head',
            'head_department',
            'hod_user',
            'head_of_dept'
          ],
          divisional: ['divisional_director'],
          ict_director: ['ict_director', 'dict'],
          head_it: ['head_it', 'head_of_it']
        }

        // Ensure signature is required at ICT Director stage regardless of role string mismatches
        let requiresSignature = stage === 'ict_director'
        if (!requiresSignature) {
          requiresSignature = !!roleMap[stage] && roleMap[stage].includes(userRole)
        }
        if (!requiresSignature) return false

        const currentUserName = this.currentUser?.name || ''
        const hasDigital = !!this.hasUserSigned || this.historyHasSigner(currentUserName)
        return !hasDigital
      },

      // Get HOD comments from requestData for display in review mode
      displayedHodComments() {
        if (!this.requestData) return null

        // Try multiple possible sources for HOD comments - prioritize hod_comments field
        const hodComments =
          this.requestData.hod_comments ||
          this.requestData.comments ||
          this.requestData.approvals?.hod?.comments ||
          null

        if (this.isDevelopment) {
          console.log('HOD Comments debug:', {
            requestData_keys: Object.keys(this.requestData),
            hodComments: hodComments,
            hod_comments_field: this.requestData.hod_comments,
            general_comments: this.requestData.comments,
            approval_comments: this.requestData.approvals?.hod?.comments
          })
        }

        return hodComments?.trim() || null
      },

      // Get Divisional Director comments from requestData for ICT Directors to view
      divisionalDirectorComments() {
        if (!this.requestData) return null

        // Try multiple possible sources for Divisional Director comments
        const divisionalComments =
          this.requestData.divisional_comments ||
          this.requestData.approvals?.divisionalDirector?.comments ||
          null

        if (this.isDevelopment) {
          console.log('Divisional Director Comments debug:', {
            requestData_keys: Object.keys(this.requestData),
            divisionalComments: divisionalComments,
            divisional_comments_field: this.requestData.divisional_comments,
            approval_comments: this.requestData.approvals?.divisionalDirector?.comments
          })
        }

        return divisionalComments?.trim() || null
      },

      // Get ICT Director comments from requestData for Head IT to view
      ictDirectorComments() {
        if (!this.requestData) return null

        // Try multiple possible sources for ICT Director comments
        const ictDirectorComments =
          this.requestData.ict_director_comments ||
          this.requestData.dict_comments ||
          this.requestData.approvals?.directorICT?.comments ||
          null

        return ictDirectorComments?.trim() || null
      },

      // Get Head IT comments from requestData for ICT Officer to view
      headItComments() {
        if (!this.requestData) return null

        // Try multiple possible sources for Head IT comments
        const headItComments =
          this.requestData.head_it_comments ||
          this.requestData.implementation?.headIT?.comments ||
          null

        return headItComments?.trim() || null
      },

      // Get ICT Officer comments from requestData
      ictOfficerComments() {
        if (!this.requestData) return null

        // Try multiple possible sources for ICT Officer comments
        const ictOfficerComments =
          this.requestData.ict_officer_comments ||
          this.requestData.implementation?.ictOfficer?.comments ||
          null

        return ictOfficerComments?.trim() || null
      },

      // Collect all previous comments from completed stages in chronological order
      // This should show ALL comments from completed stages, regardless of current request status
      previousComments() {
        if (!this.requestData) {
          return []
        }

        const comments = []
        const currentRole = this.getUserRole()?.toLowerCase()

        // Define the approval workflow stages in order
        const stages = [
          {
            key: 'hod',
            name: 'Head of Department',
            icon: 'fa-user-tie',
            color: 'blue',
            getComments: () => this.displayedHodComments,
            getDate: () => {
              const date =
                this.requestData.hod_approved_at ||
                this.requestData.approvals?.hod?.approved_at ||
                this.requestData.approvals?.hod?.date ||
                this.requestData.hod_date ||
                this.requestData.updated_at ||
                this.requestData.created_at
              if (this.isDevelopment) {
                console.log('🗓️ HOD Date:', {
                  date,
                  sources: {
                    hod_approved_at: this.requestData.hod_approved_at,
                    approval_approved_at: this.requestData.approvals?.hod?.approved_at,
                    approval_date: this.requestData.approvals?.hod?.date,
                    hod_date: this.requestData.hod_date,
                    updated_at: this.requestData.updated_at,
                    created_at: this.requestData.created_at
                  }
                })
              }
              return date
            },
            getName: () => this.requestData.hod_name || this.requestData.approvals?.hod?.name,
            isCompleted: () => this.isStageCompleted('hod'),
            getStatus: () =>
              this.requestData.hod_status || (this.isStageCompleted('hod') ? 'approved' : 'pending')
          },
          {
            key: 'divisional',
            name: 'Divisional Director',
            icon: 'fa-user-shield',
            color: 'purple',
            getComments: () => this.divisionalDirectorComments,
            getDate: () => {
              return (
                this.requestData.divisional_approved_at ||
                this.requestData.approvals?.divisionalDirector?.approved_at ||
                this.requestData.approvals?.divisionalDirector?.date ||
                this.requestData.divisional_date ||
                this.requestData.updated_at ||
                this.requestData.created_at
              )
            },
            getName: () =>
              this.requestData.divisional_director_name ||
              this.requestData.approvals?.divisionalDirector?.name,
            isCompleted: () => this.isStageCompleted('divisional'),
            getStatus: () =>
              this.requestData.divisional_status ||
              (this.isStageCompleted('divisional') ? 'approved' : 'pending')
          },
          {
            key: 'ict_director',
            name: 'ICT Director',
            icon: 'fa-user-cog',
            color: 'teal',
            getComments: () => this.ictDirectorComments,
            getDate: () => {
              return (
                this.requestData.dict_approved_at ||
                this.requestData.ict_director_approved_at ||
                this.requestData.approvals?.directorICT?.approved_at ||
                this.requestData.approvals?.directorICT?.date ||
                this.requestData.dict_date ||
                this.requestData.ict_director_date ||
                this.requestData.updated_at ||
                this.requestData.created_at
              )
            },
            getName: () =>
              this.requestData.dict_name || this.requestData.approvals?.directorICT?.name,
            isCompleted: () => this.isStageCompleted('ict_director'),
            getStatus: () =>
              this.requestData.ict_director_status ||
              this.requestData.dict_status ||
              (this.isStageCompleted('ict_director') ? 'approved' : 'pending')
          },
          {
            key: 'head_it',
            name: 'Head of IT',
            icon: 'fa-laptop-code',
            color: 'indigo',
            getComments: () => this.headItComments,
            getDate: () => {
              return (
                this.requestData.head_it_approved_at ||
                this.requestData.implementation?.headIT?.approved_at ||
                this.requestData.implementation?.headIT?.date ||
                this.requestData.head_it_date ||
                this.requestData.updated_at ||
                this.requestData.created_at
              )
            },
            getName: () =>
              this.requestData.head_it_name || this.requestData.implementation?.headIT?.name,
            isCompleted: () => this.isStageCompleted('head_it'),
            getStatus: () =>
              this.requestData.head_it_status ||
              (this.isStageCompleted('head_it') ? 'approved' : 'pending')
          },
          {
            key: 'ict_officer',
            name: 'ICT Officer',
            icon: 'fa-tools',
            color: 'green',
            getComments: () => this.ictOfficerComments,
            getDate: () => {
              return (
                this.requestData.ict_officer_approved_at ||
                this.requestData.implementation?.ictOfficer?.approved_at ||
                this.requestData.implementation?.ictOfficer?.date ||
                this.requestData.ict_officer_date ||
                this.requestData.updated_at ||
                this.requestData.created_at
              )
            },
            getName: () =>
              this.requestData.ict_officer_name ||
              this.requestData.implementation?.ictOfficer?.name,
            isCompleted: () => this.isStageCompleted('ict_officer'),
            getStatus: () =>
              this.requestData.ict_officer_status ||
              (this.isStageCompleted('ict_officer') ? 'approved' : 'pending')
          }
        ]

        // Show comments from all completed stages that have comments
        // Head of IT should see previous comments from HOD, Divisional Director, and ICT Director
        stages.forEach((stage) => {
          // Always include completed stages that have comments, regardless of current user role
          // The "Previous Comments" section should show all prior approvers' feedback
          const stageComments = stage.getComments()
          // Include if stage completed OR it's the current viewer's stage (to show in-progress comments)
          const shouldInclude =
            stage.isCompleted() || (stage.key === this.viewerStage() && !!stageComments)

          // TEMPORARY DEBUG: Log each stage processing
          try {
            console.log(
              `🔍 Stage ${stage.key}: completed=${shouldInclude}, hasComments=${!!stageComments}`,
              stageComments ? `"${stageComments.substring(0, 30)}..."` : 'no comments'
            )
          } catch (e) {
            console.error(`🚨 Error logging stage ${stage.key}:`, e)
          }

          if (shouldInclude) {
            if (stageComments) {
              const status = stage.getStatus()
              const stageDate = stage.getDate()
              const fallbackDate = this.requestData.updated_at || this.requestData.created_at

              comments.push({
                stage: stage.key,
                stageName: stage.name,
                icon: stage.icon,
                color: stage.color,
                comments: stageComments,
                date: stageDate || fallbackDate,
                name: stage.getName(),
                status: status,
                isApproved: status === 'approved',
                isRejected: status === 'rejected',
                hasSpecificDate: !!stageDate
              })
            }
          }
        })

        // Sort comments
        const sortedComments = comments.sort((a, b) => {
          const dateA = a.date || this.requestData.updated_at || this.requestData.created_at
          const dateB = b.date || this.requestData.updated_at || this.requestData.created_at

          if (!dateA && !dateB) return 0
          if (!dateA) return 1
          if (!dateB) return -1

          return new Date(dateA) - new Date(dateB)
        })

        return sortedComments
      },

      // Check if there are any previous comments to show
      hasPreviousComments() {
        return this.previousComments && this.previousComments.length > 0
      },

      // Check if the request is fully completed/approved (all stages done)
      isRequestFullyCompleted() {
        if (!this.requestData) return false

        // A request is fully completed when ICT Officer has implemented it
        const status = this.requestData.getCalculatedOverallStatus
          ? this.requestData.getCalculatedOverallStatus()
          : this.requestData.status || 'pending'

        const completedStatuses = ['implemented', 'approved', 'completed']
        const hasIctOfficerImplementation = !!this.requestData.ict_officer_implemented_at

        return completedStatuses.includes(status) || hasIctOfficerImplementation
      },

      // Get all validation errors for current approval stage
      validationErrors() {
        const errors = []

        // Basic field validation
        if (!this.form?.shared?.pfNumber?.trim()) {
          errors.push('PF Number is required')
        }
        if (!this.form?.shared?.staffName?.trim()) {
          errors.push('Staff Name is required')
        }
        if (!this.form?.shared?.department?.trim()) {
          errors.push('Department is required')
        }
        if (!this.form?.shared?.phone?.trim()) {
          errors.push('Contact Number is required')
        }

        // Stage-specific validation
        const userRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
        const hodRoles = ['head_of_department', 'hod']
        const isHodUser = hodRoles.includes(userRole)
        const isHodStage = this.currentApprovalStage === 'hod'

        // HOD validation
        if (isHodUser && isHodStage) {
          if (!this.hodComments?.trim()) {
            errors.push('HOD Comments are required')
          }
          // At least one module must be selected
          const hasModules =
            this.selectedWellsoft?.length > 0 ||
            this.selectedJeeva?.length > 0 ||
            this.filteredInternetPurposes?.length > 0
          if (!hasModules) {
            errors.push('At least one module or service must be selected')
          }
        }

        // Signature validation for current stage
        if (this.isSignatureRequiredForApproval) {
          errors.push('Signature is required for approval')
        }

        return errors
      },

      // Check if approval buttons should be disabled due to missing signature or HOD comments
      areApprovalButtonsDisabled() {
        // Enable once digital signature exists; keep disabled only for loading/processing/signature-missing
        return this.loading || this.processing || this.isSignatureRequiredForApproval
      },

      // Get the appropriate tooltip message for approval button
      approvalButtonTooltip() {
        const errors = this.validationErrors
        if (errors.length > 0) {
          return errors.join('; ')
        }
        return 'Approve this request'
      },

      // General signature presence check for any stage
      hasStageSigned() {
        return (stage) => {
          if (!this.requestData) return false

          const map = {
            hod: {
              sig:
                this.requestData.approvals?.hod?.signature ||
                this.requestData.approvals?.hod?.signature_path ||
                this.requestData.hod_signature_path ||
                '',
              date:
                this.requestData.approvals?.hod?.date ||
                this.requestData.approvals?.hod?.approved_at ||
                this.requestData.hod_approved_at ||
                '',
              name: this.requestData.approvals?.hod?.name || this.requestData.hod_name || ''
            },
            divisional: {
              sig:
                this.requestData.approvals?.divisionalDirector?.signature ||
                this.requestData.approvals?.divisionalDirector?.signature_path ||
                this.requestData.divisional_signature_path,
              date:
                this.requestData.approvals?.divisionalDirector?.date ||
                this.requestData.approvals?.divisionalDirector?.approved_at ||
                this.requestData.divisional_approved_at,
              name:
                this.requestData.approvals?.divisionalDirector?.name ||
                this.requestData.divisional_director_name
            },
            ict_director: {
              sig:
                this.requestData.approvals?.directorICT?.signature ||
                this.requestData.approvals?.directorICT?.signature_path ||
                this.requestData.dict_signature_path,
              date:
                this.requestData.approvals?.directorICT?.date ||
                this.requestData.approvals?.directorICT?.approved_at ||
                this.requestData.dict_approved_at,
              name: this.requestData.approvals?.directorICT?.name || this.requestData.dict_name
            },
            head_it: {
              sig:
                this.requestData.implementation?.headIT?.signature ||
                this.requestData.implementation?.headIT?.signature_path ||
                this.requestData.head_it_signature_path,
              date:
                this.requestData.implementation?.headIT?.date ||
                this.requestData.implementation?.headIT?.approved_at ||
                this.requestData.head_it_approved_at,
              name: this.requestData.implementation?.headIT?.name || this.requestData.head_it_name
            },
            ict_officer: {
              sig:
                this.requestData.implementation?.ictOfficer?.signature ||
                this.requestData.implementation?.ictOfficer?.signature_path ||
                this.requestData.ict_officer_signature_path,
              date:
                this.requestData.implementation?.ictOfficer?.date ||
                this.requestData.implementation?.ictOfficer?.approved_at ||
                this.requestData.ict_officer_approved_at,
              name:
                this.requestData.implementation?.ictOfficer?.name ||
                this.requestData.ict_officer_name
            }
          }

          const e = map[stage] || {}
          const hasSig = !!(e.sig && String(e.sig).length > 0)
          const hasDate = !!e.date
          const hasName = !!(e.name && String(e.name).trim().length > 0)

          // Also check if this stage is completed based on request status
          const isStageCompleted = this.isStageCompleted(stage)

          // A stage is considered signed if ANY of these are true:
          // A) Legacy: It has signature file AND date AND name
          // B) Legacy: Stage is completed AND has a name AND date
          // C) Legacy: For HOD specifically, hod_signature_path exists
          // D) Digital: Signature history contains a signature by the stage's approver name
          // E) Digital: Current viewer is that stage role and has signed (history contains currentUser.name)
          let result = (hasSig && hasDate && hasName) || (isStageCompleted && hasName && hasDate)

          // C) Special case for HOD (legacy file path)
          if (stage === 'hod' && this.requestData?.hod_signature_path) {
            result = true
            if (this.isDevelopment) {
              console.log(
                '\u2705 HOD signature found via hod_signature_path:',
                this.requestData.hod_signature_path
              )
            }
          }

          // D) Digital: Check signature history for matching approver name
          if (!result && hasName && this.historyHasSigner(e.name)) {
            result = true
          }

          // E) Digital: If viewer is that stage role and history has current user name
          const roleMap = {
            hod: ['head_of_department', 'hod'],
            divisional: ['divisional_director'],
            ict_director: ['ict_director', 'dict'],
            head_it: ['head_it', 'head_of_it'],
            ict_officer: ['ict_officer', 'officer_ict']
          }
          const viewerRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
          if (
            !result &&
            roleMap[stage] &&
            roleMap[stage].includes(viewerRole) &&
            this.currentUser?.name &&
            this.historyHasSigner(this.currentUser.name)
          ) {
            result = true
          }

          // Debug logging for signature detection (development only)
          if (this.isDevelopment) {
            console.log(`\ud83d\udd0d Stage ${stage} signature check:`, {
              signature: e.sig,
              date: e.date,
              name: e.name,
              hasSig,
              hasDate,
              hasName,
              isStageCompleted,
              hod_signature_path: stage === 'hod' ? this.requestData?.hod_signature_path : 'N/A',
              result
            })
          }

          return result
        }
      },

      // Should show signed indicators for the previous stage while current stage is pending
      shouldShowHodSignedIndicator() {
        // Show as signed when:
        // 1) Viewer is after HOD stage and HOD stage is signed (original behaviour), OR
        // 2) Viewer IS the HOD and they have digitally signed (in this session or history), OR
        // 3) Divisional Director fallback when legacy hod_signature_path exists
        const afterStage =
          this.isReviewMode && this.viewerAfter('hod') && this.hasStageSigned('hod')
        const isHodViewer = this.viewerStage() === 'hod'
        const hasDigital =
          !!this.hasUserSigned ||
          (this.currentUser?.name && this.historyHasSigner(this.currentUser.name))
        const hodSelf = this.isReviewMode && isHodViewer && hasDigital

        // Explicit fallback: if user is divisional director and hod_signature_path exists, show signed
        const isDivisionalDirector =
          (this.getUserRole() || '').toLowerCase() === 'divisional_director'
        const hodSignatureExists = !!this.requestData?.hod_signature_path
        const fallbackResult = this.isReviewMode && isDivisionalDirector && hodSignatureExists

        const result = afterStage || hodSelf || fallbackResult

        if (this.isDevelopment) {
          console.log('\ud83d\udfe2 shouldShowHodSignedIndicator:', {
            isReviewMode: this.isReviewMode,
            viewerAfter_hod: this.viewerAfter('hod'),
            hasStageSigned_hod: this.hasStageSigned('hod'),
            isHodViewer,
            hasDigital,
            isDivisionalDirector,
            hodSignatureExists,
            result
          })
        }
        return result
      },
      shouldShowHodNoSignatureIndicator() {
        // If the current user can edit/upload as HOD, do NOT show the no-signature indicator
        if (this.isHodApprovalEditable || this.canUploadHodSignature) {
          return false
        }
        // Hide when a local signature has been selected (preview) or exists in form state
        if (this.hodSignaturePreview || this.form?.approvals?.hod?.signature) {
          return false
        }
        // Don't show no signature if we should show signed indicator
        const shouldShowSigned = this.shouldShowHodSignedIndicator
        const baseResult =
          this.isReviewMode && this.viewerAfter('hod') && !this.hasStageSigned('hod')
        const result = baseResult && !shouldShowSigned

        if (this.isDevelopment) {
          console.log('\ud83d\udd34 shouldShowHodNoSignatureIndicator:', {
            isReviewMode: this.isReviewMode,
            viewerAfter_hod: this.viewerAfter('hod'),
            hasStageSigned_hod: this.hasStageSigned('hod'),
            shouldShowSigned,
            baseResult,
            result,
            isHodApprovalEditable: this.isHodApprovalEditable,
            canUploadHodSignature: this.canUploadHodSignature
          })
        }
        return result
      },
      shouldShowDivisionalSignedIndicator() {
        // Show green signed state whenever a divisional signature exists in DB
        return this.isReviewMode && this.hasStageSigned('divisional')
      },
      shouldShowDivisionalNoSignatureIndicator() {
        return (
          this.isReviewMode && this.viewerAfter('divisional') && !this.hasStageSigned('divisional')
        )
      },
      // Skipped-state helpers for HOD and Divisional Director
      isHodSkipped() {
        const v = (this.requestData?.hod_status || '').toString().toLowerCase()
        return v === 'skipped'
      },
      isDivisionalSkipped() {
        const v = (this.requestData?.divisional_status || '').toString().toLowerCase()
        return v === 'skipped'
      },
      shouldShowIctDirectorSignedIndicator() {
        // Show green signed state whenever an ICT Director signature exists in DB
        return this.isReviewMode && this.hasStageSigned('ict_director')
      },
      shouldShowIctDirectorNoSignatureIndicator() {
        // Show 'no signature' only when NOT the ICT Director stage for the viewer.
        if (this.isIctDirectorApprovalEditable) return false
        return (
          this.isReviewMode &&
          this.viewerAfter('ict_director') &&
          !this.hasStageSigned('ict_director')
        )
      },
      shouldShowHeadITSignedIndicator() {
        // Show green signed state whenever a Head IT signature exists in DB
        return this.isReviewMode && this.hasStageSigned('head_it')
      },
      shouldShowHeadITNoSignatureIndicator() {
        // Don't show no signature if we should show signed indicator
        const shouldShowSigned = this.shouldShowHeadITSignedIndicator
        const baseResult =
          this.isReviewMode && this.viewerAfter('head_it') && !this.hasStageSigned('head_it')
        const result = baseResult && !shouldShowSigned

        if (this.isDevelopment) {
          console.log('🔴 shouldShowHeadITNoSignatureIndicator:', {
            isReviewMode: this.isReviewMode,
            viewerAfter_head_it: this.viewerAfter('head_it'),
            hasStageSigned_head_it: this.hasStageSigned('head_it'),
            shouldShowSigned,
            baseResult,
            result
          })
        }
        return result
      },

      // ICT Officer signature visibility indicators
      shouldShowIctOfficerSignedIndicator() {
        // Show green signed state whenever an ICT Officer signature exists in DB
        return this.isReviewMode && this.hasStageSigned('ict_officer')
      },
      shouldShowIctOfficerNoSignatureIndicator() {
        // Don't show no signature if we should show signed indicator
        const shouldShowSigned = this.shouldShowIctOfficerSignedIndicator
        const baseResult =
          this.isReviewMode &&
          this.viewerAfter('ict_officer') &&
          !this.hasStageSigned('ict_officer')
        const result = baseResult && !shouldShowSigned

        if (this.isDevelopment) {
          console.log('🔴 shouldShowIctOfficerNoSignatureIndicator:', {
            isReviewMode: this.isReviewMode,
            viewerAfter_ict_officer: this.viewerAfter('ict_officer'),
            hasStageSigned_ict_officer: this.hasStageSigned('ict_officer'),
            shouldShowSigned,
            baseResult,
            result
          })
        }
        return result
      },

      // UI class helpers to simplify template conditions
      implementationCardClass() {
        const baseClass =
          'medical-card bg-gradient-to-r from-blue-600/25 to-blue-700/25 border-2 border-blue-400/40 rounded-lg backdrop-blur-sm hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 group'

        if (this.isReviewMode && this.isIctDirectorUser) {
          return `${baseClass} p-0.5`
        } else if (this.isDivisionalDirectorUser || this.isIctDirectorUser) {
          return `${baseClass} p-0.5`
        } else {
          return `${baseClass} p-0.5`
        }
      },

      implementationHeaderClass() {
        const baseClass = 'flex items-center space-x-1'
        return this.isReviewMode && this.isIctDirectorUser
          ? `${baseClass} mb-0.5`
          : `${baseClass} mb-0.5`
      },

      implementationGridClass() {
        const baseClass = 'grid grid-cols-1 lg:grid-cols-2'

        if (this.isReviewMode && this.isIctDirectorUser) {
          return `${baseClass} gap-0.5`
        } else if (this.isDivisionalDirectorUser) {
          return `${baseClass} gap-1`
        } else {
          return `${baseClass} gap-1`
        }
      },

      headItSectionClass() {
        const baseClass = 'bg-white/15 rounded-lg border border-blue-300/30 backdrop-blur-sm'

        let paddingClass = ''
        if (this.isReviewMode && this.isIctDirectorUser) {
          paddingClass = 'p-1'
        } else if (this.isDivisionalDirectorUser) {
          paddingClass = 'p-1.5'
        } else {
          paddingClass = 'p-2'
        }

        return [baseClass, paddingClass].filter(Boolean).join(' ')
      },

      ictOfficerSectionClass() {
        const baseClass = 'bg-white/15 rounded-lg border border-blue-300/30 backdrop-blur-sm'

        let paddingClass = ''
        if (this.isReviewMode && this.isIctDirectorUser) {
          paddingClass = 'p-1'
        } else if (this.isDivisionalDirectorUser) {
          paddingClass = 'p-1.5'
        } else {
          paddingClass = 'p-2'
        }

        return [baseClass, paddingClass].filter(Boolean).join(' ')
      },

      actionButtonsClass() {
        const baseClass = 'flex flex-col'

        if (this.isReviewMode && this.isIctDirectorUser) {
          return `${baseClass} gap-1 mt-1`
        } else if (this.isDivisionalDirectorUser || this.isIctDirectorUser) {
          return `${baseClass} gap-2 mt-2`
        } else {
          return `${baseClass} gap-4 mt-4`
        }
      },

      // Check if ICT Officer implementation approval button should be disabled
      isImplementationApprovalDisabled() {
        // ICT Officer: enable when a digital signature exists (no legacy file required)
        const hasDigital =
          !!this.hasUserSigned ||
          (this.currentUser?.name && this.historyHasSigner(this.currentUser.name)) ||
          this.shouldShowIctOfficerSignedIndicator
        return this.processing || this.loading || !hasDigital
      },

      // Check if the implementation is already completed
      isImplementationAlreadyCompleted() {
        if (!this.requestData) return false

        // Check multiple indicators of completion
        const hasImplementationDate = !!this.getValidIctOfficerDate
        const hasImplementedStatus =
          this.requestData?.ict_officer_status === 'implemented' ||
          this.requestData?.status === 'implemented' ||
          this.requestData?.status === 'approved' ||
          this.requestData?.status === 'completed'
        const hasSignaturePath = !!this.requestData?.ict_officer_signature_path

        const isCompleted = hasImplementationDate || hasImplementedStatus || hasSignaturePath

        // Debug logging for development
        if (this.isDevelopment && this.isReviewMode) {
          console.log('🔍 Implementation completion check:', {
            hasImplementationDate,
            hasImplementedStatus,
            hasSignaturePath,
            isCompleted,
            ict_officer_implemented_at: this.requestData?.ict_officer_implemented_at,
            ict_officer_status: this.requestData?.ict_officer_status,
            status: this.requestData?.status
          })
        }

        return isCompleted
      }
    },
    async mounted() {
      console.log('🚀 Component mounted - starting initialization...')

      // Show loading screen
      this.isLoading = true

      // Use Promise.all for parallel loading to reduce total load time
      try {
        // Load critical data in parallel - modules can be cached
        const [currentUser] = await Promise.all([
          this.getCurrentUser(),
          this.loadModulesWithCaching() // Load modules with caching strategy
        ])
        console.log('🔍 After getCurrentUser:', {
          hasCurrentUser: !!this.currentUser,
          userName: this.currentUser?.name,
          userRole: this.currentUser?.role || this.currentUser?.user_role
        })

        // Fallback to localStorage if API fails
        if (!this.currentUser || !this.currentUser.name) {
          console.log('🔄 API failed, trying localStorage fallback...')
          this.tryGetUserFromLocalStorage()
          console.log('🔍 After localStorage:', {
            hasCurrentUser: !!this.currentUser,
            userName: this.currentUser?.name
          })
        }

        // Fallback to Pinia store
        if (!this.currentUser || !this.currentUser.name) {
          console.log('🔄 localStorage failed, trying Pinia store fallback...')
          this.tryGetUserFromStore()
          console.log('🔍 After Pinia store:', {
            hasCurrentUser: !!this.currentUser,
            userName: this.currentUser?.name,
            userRole: this.getUserRole()
          })
        }

        // Final check
        if (!this.currentUser || !this.currentUser.name) {
          console.error('❌ CRITICAL: Unable to load user from any source!')
          console.error('❌ This will cause role detection to fail')
        } else {
          console.log('✅ User successfully loaded:', this.currentUser.name)
          console.log('✅ Detected user role:', this.getUserRole())

          // IMMEDIATE HOD CHECK for troubleshooting
          const userRole = (this.getUserRole() || '').toLowerCase()
          const hodRoles = [
            'head_of_department',
            'hod',
            'department_head',
            'head_department',
            'hod_user',
            'head_of_dept'
          ]
          const isHodUser = hodRoles.includes(userRole)

          console.log('🚨 IMMEDIATE HOD CHECK ON MOUNT:', {
            userName: this.currentUser.name,
            detectedRole: userRole,
            isHodUser,
            hodRolesChecked: hodRoles,
            isInReviewMode: this.isReviewMode,
            requestId: this.getRequestId
          })
        }

        // In strict view mode, expand sections by default
        if (this.isStrictViewMode) {
          this.showApprovalSections = true
          this.showImplementationSections = true
        }

        if (this.isReviewMode && this.getRequestId) {
          await this.loadRequestData()

          // Hide loading screen after data is loaded
          this.isLoading = false

          // Debug: Check signature indicators after data loads (development only)
          if (this.isDevelopment) {
            setTimeout(() => {
              console.log('\u23f0 5-second debug check after mount:', {
                shouldShowHodSignedIndicator: this.shouldShowHodSignedIndicator,
                shouldShowHodNoSignatureIndicator: this.shouldShowHodNoSignatureIndicator,
                hasStageSigned_hod: this.hasStageSigned('hod'),
                viewerAfter_hod: this.viewerAfter('hod'),
                isReviewMode: this.isReviewMode,
                currentUser: this.currentUser,
                userRole: this.getUserRole(),
                viewerStage: this.viewerStage(),
                viewerRank: this.viewerRank(),
                requestStatus: this.requestData?.status,
                requestId: this.getRequestId
              })
            }, 5000)
          }
        } else {
          // Not in review mode, hide loading immediately
          this.isLoading = false
        }
      } catch (error) {
        console.error('❌ Component mount error:', error)
        // Hide loading screen on error
        this.isLoading = false
        // Don't let mount errors cause page to disappear
        // Just log the error and continue with basic functionality
        this.error = 'Failed to fully initialize component, but continuing...'
      }
    },
    watch: {
      selectedWellsoft: {
        handler(v) {
          this.syncTabs('wellsoft', v)
        },
        deep: true
      },
      selectedJeeva: {
        handler(v) {
          this.syncTabs('jeeva', v)
        },
        deep: true
      },
      // Watch for when approval sections become editable (now just for logging)
      isHodApprovalEditable: {
        handler(isEditable) {
          if (this.isDevelopment) {
            console.log('HOD approval editable changed:', {
              isEditable,
              currentHodName: this.form.approvals.hod.name,
              hasCurrentUser: !!this.currentUser,
              isReviewMode: this.isReviewMode,
              currentApprovalStage: this.currentApprovalStage
            })
          }
          // Role-based population is now handled by currentUser watcher
        },
        immediate: true
      },
      isDivisionalApprovalEditable: {
        handler(isEditable) {
          if (this.isDevelopment) {
            console.log('Divisional approval editable changed:', {
              isEditable,
              currentDivisionalName: this.form.approvals.divisionalDirector.name,
              hasCurrentUser: !!this.currentUser,
              isReviewMode: this.isReviewMode,
              currentApprovalStage: this.currentApprovalStage
            })
          }

          // Auto-populate Divisional Director date when they become editable
          if (isEditable && this.requestData) {
            this.populateDivisionalDirectorDateFromHod()
          }
        },
        immediate: true
      },
      isIctDirectorApprovalEditable: {
        handler(isEditable) {
          if (this.isDevelopment) {
            console.log('ICT Director approval editable changed:', {
              isEditable,
              currentIctDirectorName: this.form.approvals.directorICT.name,
              hasCurrentUser: !!this.currentUser,
              isReviewMode: this.isReviewMode,
              currentApprovalStage: this.currentApprovalStage
            })
          }
          // Role-based population is now handled by currentUser watcher
        },
        immediate: true
      },
      isHeadItApprovalEditable: {
        handler(isEditable) {
          if (this.isDevelopment) {
            console.log('Head IT approval editable changed:', {
              isEditable,
              currentHeadItName: this.form.implementation.headIT.name,
              hasCurrentUser: !!this.currentUser,
              isReviewMode: this.isReviewMode,
              currentApprovalStage: this.currentApprovalStage,
              userRole: this.getUserRole()
            })
          }

          // Auto-populate Head IT name when section becomes editable
          if (isEditable && this.currentUser && this.currentUser.name) {
            const userRole = (this.getUserRole() || '').toLowerCase()
            const isHeadIt = ['head_it', 'head_of_it', 'ict_head', 'it_head'].includes(userRole)

            if (isHeadIt && !this.form.implementation.headIT.name) {
              console.log('🎯 Auto-populating Head IT name:', this.currentUser.name)
              this.form.implementation.headIT.name = this.currentUser.name
              this.form.implementation.headIT.date = this.formatDateForInput(new Date())
              this.$forceUpdate() // Force reactivity update
            }
          }
        },
        immediate: true
      },
      isIctOfficerApprovalEditable: {
        handler(isEditable) {
          if (this.isDevelopment) {
            console.log('ICT Officer approval editable changed:', {
              isEditable,
              currentIctOfficerName: this.form.implementation.ictOfficer.name,
              hasCurrentUser: !!this.currentUser,
              isReviewMode: this.isReviewMode,
              currentApprovalStage: this.currentApprovalStage
            })
          }
          // Role-based population is now handled by currentUser watcher
        },
        immediate: true
      },
      // Watch for when currentUser data is loaded to trigger auto-population
      currentUser: {
        handler(newUser) {
          if (newUser && newUser.name) {
            if (this.isDevelopment) {
              console.log('Current user data loaded, role-based auto-population:', {
                userName: newUser.name,
                userId: newUser.id,
                userRole: newUser.role,
                isReviewMode: this.isReviewMode,
                currentApprovalStage: this.currentApprovalStage
              })
            }

            // Role-based population - only populate the field that matches user's role
            this.populateBasedOnUserRole(newUser)
          }
        },
        immediate: true
      },

      // Watch for changes in the central wellsoftRequestType and sync with jeevaRequestType
      wellsoftRequestType: {
        handler(newValue) {
          // Sync the central Use/Revoke selection with both module types
          this.jeevaRequestType = newValue
          if (this.isDevelopment) {
            console.log('🔄 Central request type changed:', {
              newValue,
              appliedToBoth: 'Jeeva and Wellsoft modules'
            })
          }
        },
        immediate: true
      },

      // Watch for when request data is loaded and user is Head IT
      requestData: {
        handler(newRequestData) {
          if (newRequestData && this.currentUser && this.currentUser.name) {
            const userRole = (this.getUserRole() || '').toLowerCase()
            const isHeadIt = ['head_it', 'head_of_it'].includes(userRole)
            const isHeadItStage = this.currentApprovalStage === 'head_it'

            if (isHeadIt && isHeadItStage && this.isReviewMode) {
              console.log(
                '🎯 Head IT user detected with loaded request data - triggering name population'
              )

              // Force population of Head IT name if it's empty
              if (!this.form.implementation.headIT.name) {
                console.log('🔄 Force-populating Head IT name field')
                this.populateApproverName('head_it')
              }
            }
          }
        },
        immediate: false
      },
      // Also refresh role comment when request loads
      requestDataLoadForComments: {
        handler() {
          this.loadRoleCommentDraft()
        },
        immediate: true
      }
    },
    methods: {
      formatDateTime(ts) {
        try {
          const d = typeof ts === 'string' ? new Date(ts) : ts
          if (isNaN(d?.getTime?.())) return ''
          return d.toLocaleString()
        } catch {
          return ''
        }
      },
      normalizePhoneNumber(input) {
        if (!input) return ''
        let v = String(input).trim().replace(/\s|-/g, '')
        if (v.startsWith('+255')) return v
        if (v.startsWith('255')) return '+' + v
        if (v.startsWith('0')) return '+255' + v.slice(1)
        return v
      },
      async signCurrentDocument() {
        try {
          if (this.isSigning) return
          const documentId = this.getRequestId || this.requestData?.id || this.$route.params.id
          if (!documentId) {
            this.showToast(
              'Cannot sign until the request is created. Please submit first.',
              'error'
            )
            return
          }
          this.isSigning = true
          const res = await signatureService.sign(documentId)
          if (res && (res.success || res.data)) {
            this.hasUserSigned = true
            this.lastSignedAt = res?.data?.signed_at || res?.signed_at || new Date().toISOString()
            const signer = this.currentUser?.name || 'you'
            this.toast = {
              show: true,
              message: `Document successfully signed by ${signer} on ${new Date(this.lastSignedAt).toLocaleString()}`
            }
            // Refresh local signature history so indicators update without reload
            await this.fetchSignatureHistory().catch(() => {})
            // Optimistically reflect signed status for current stage to hide buttons immediately
            try {
              const stage = this.viewerStage()
              if (this.requestData) {
                this.requestData.approvals = this.requestData.approvals || {}
                if (stage === 'hod') {
                  this.requestData.approvals.hod = {
                    ...(this.requestData.approvals.hod || {}),
                    signature_status: 'Signed',
                    signature_display: 'Digitally signed'
                  }
                } else if (stage === 'divisional') {
                  this.requestData.approvals.divisionalDirector = {
                    ...(this.requestData.approvals.divisionalDirector || {}),
                    signature_status: 'Signed',
                    signature_display: 'Digitally signed'
                  }
                } else if (stage === 'ict_director') {
                  this.requestData.approvals.directorICT = {
                    ...(this.requestData.approvals.directorICT || {}),
                    signature_status: 'Signed',
                    signature_display: 'Digitally signed'
                  }
                  // Auto-capture date for ICT Director when signing
                  try {
                    const today = this.formatDateForInput(new Date())
                    if (!this.form?.approvals?.directorICT?.date) {
                      this.form.approvals.directorICT.date = today
                    }
                    if (!this.requestData.approvals.directorICT.date) {
                      this.requestData.approvals.directorICT.date = today
                    }
                  } catch (e) {
                    /* no-op */
                  }
                } else if (stage === 'head_it') {
                  this.requestData.implementation = this.requestData.implementation || {}
                  this.requestData.implementation.headIT = {
                    ...(this.requestData.implementation.headIT || {}),
                    signature_status: 'Signed',
                    signature_display: 'Digitally signed'
                  }
                }
              }
            } catch (err) {
              // Ensure UI updates even if optimistic local patch fails
              console.warn('Signature optimistic update failed:', err)
            }
            this.$forceUpdate()
            // Ensure date input reflects immediately for DICT stage
            try {
              this.ensureIctDirectorDateAutoFill()
            } catch (e) {
              /* no-op */
            }
            setTimeout(() => (this.toast.show = false), 4000)
          } else {
            this.showToast(res?.message || 'Failed to sign document', 'error')
          }
        } catch (e) {
          console.error('Sign error:', e)
          this.showToast(e.message || 'Failed to sign document', 'error')
        } finally {
          this.isSigning = false
        }
      },
      // ===========================================
      // DIGITAL SIGNATURE HISTORY
      // ===========================================
      async fetchSignatureHistory() {
        try {
          const id = this.getRequestId || this.requestData?.id || this.$route.params.id
          if (!id) return
          const res = await signatureService.list(id)
          // Accept either {data: [...] } or direct array
          this.signatureHistory = Array.isArray(res?.data)
            ? res.data
            : Array.isArray(res)
              ? res
              : []
        } catch (e) {
          console.warn('Failed to load signature history:', e?.message || e)
          this.signatureHistory = []
        } finally {
          // After loading signature history, ensure DICT date is filled if viewer is ICT Director
          this.$nextTick(() => {
            try {
              this.ensureIctDirectorDateAutoFill()
            } catch (e) {
              /* no-op */
            }
          })
        }
      },

      // Auto-fill DICT date when signed/pending at DICT stage
      ensureIctDirectorDateAutoFill() {
        try {
          if (this.viewerStage() !== 'ict_director') return
          const hasDigital =
            !!this.hasUserSigned ||
            (this.currentUser?.name && this.historyHasSigner(this.currentUser.name))
          const atDictStage = this.currentApprovalStage === 'ict_director'
          if ((hasDigital || this.shouldShowIctDirectorSignedIndicator) && atDictStage) {
            const empty = !this.form?.approvals?.directorICT?.date
            if (empty) {
              this.form.approvals.directorICT.date = this.formatDateForInput(new Date())
              this.$forceUpdate()
            }
          }
        } catch (e) {
          /* no-op */
        }
      },

      // Helper: does history contain a signature by this exact person name (case-insensitive)?
      historyHasSigner(name) {
        if (!name) return false
        const n = String(name).trim().toLowerCase()
        return (this.signatureHistory || []).some(
          (s) =>
            String(s.user_name || s.name || '')
              .trim()
              .toLowerCase() === n
        )
      },

      // Return a hover tooltip like "Signed by You at HH:MM" for the given stage if current user signed
      getSignedByYouTooltip(stage) {
        try {
          const userName = this.currentUser?.name
          if (!userName) return ''
          // Only show when the viewer's role matches the stage role
          const roleMap = {
            hod: ['head_of_department', 'hod'],
            divisional: ['divisional_director'],
            ict_director: ['ict_director', 'dict'],
            head_it: ['head_it', 'head_of_it'],
            ict_officer: ['ict_officer', 'officer_ict']
          }
          const viewerRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
          if (!roleMap[stage] || !roleMap[stage].includes(viewerRole)) return ''

          // Find current user's signature entry
          const entry = (this.signatureHistory || []).find(
            (s) =>
              String(s.user_name || s.name || '')
                .trim()
                .toLowerCase() === String(userName).trim().toLowerCase()
          )
          if (!entry) return ''
          const ts = entry.signed_at || entry.created_at || entry.timestamp
          if (!ts) return 'Signed by You'
          const d = new Date(ts)
          if (isNaN(d.getTime())) return 'Signed by You'
          const time = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
          return `Signed by You at ${time}`
        } catch {
          return ''
        }
      },

      // ===========================================
      // ROLE COMMENT HELPERS
      // ===========================================
      getExistingRoleComment() {
        if (!this.requestData) return ''
        const key = this.roleCommentKey
        if (!key) return ''
        const aliases = {
          divisional_director_comments: ['divisional_comments'],
          ict_director_comments: ['dict_comments'],
          head_it_comments: ['head_it_comments'],
          ict_officer_comments: ['ict_officer_comments']
        }
        const tryKeys = [key, ...(aliases[key] || [])]
        for (const k of tryKeys) {
          if (this.requestData[k]) return this.requestData[k]
        }
        const nested = {
          divisional_director_comments: this.requestData.approvals?.divisionalDirector?.comments,
          ict_director_comments: this.requestData.approvals?.directorICT?.comments,
          head_it_comments: this.requestData.implementation?.headIT?.comments,
          ict_officer_comments: this.requestData.implementation?.ictOfficer?.comments
        }
        return nested[key] || ''
      },
      loadRoleCommentDraft() {
        this.roleCommentsDraft = this.getExistingRoleComment() || ''
      },

      // Intercept ICT Officer approve click to open Grant Access popup
      onIctOfficerApproveClick() {
        // Only ICT Officer flow
        const role = (this.getUserRole() || '').toLowerCase()
        if (!['ict_officer', 'officer_ict'].includes(role)) return
        // Require digital signature before proceeding
        const currentUserName = this.currentUser?.name || ''
        const hasDigital = !!this.hasUserSigned || this.historyHasSigner(currentUserName)
        if (!hasDigital) {
          this.showToast('Please digitally sign the request first', 'error')
          return
        }
        this.grantAccessComment = (this.roleCommentsDraft || '').trim()
        this.grantAccessError = ''
        this.showGrantAccessPopup = true
      },

      async confirmGrantAccess() {
        const comment = (this.grantAccessComment || '').trim()
        if (!comment) {
          this.grantAccessError = 'Comment is required.'
          return
        }
        this.grantAccessError = ''
        try {
          this.processing = true
          // Digital signature flow: no legacy file required
          const fdPayload = {
            ict_officer_name:
              this.form?.implementation?.ictOfficer?.name || this.currentUser?.name || '',
            ict_officer_date: new Date().toISOString().slice(0, 10),
            ict_officer_comments: comment,
            ict_officer_status: 'implemented'
          }

          const res = await bothServiceFormService.ictOfficerApprove(this.getRequestId, fdPayload)
          if (res.success) {
            this.showGrantAccessPopup = false
            this.showToast('Access granted successfully', 'success')

            // Immediately update local state to hide approve button
            if (this.requestData) {
              this.requestData.ict_officer_status = 'implemented'
              this.requestData.ict_officer_implemented_at = new Date().toISOString()
              this.requestData.ict_officer_comments = comment
              this.requestData.status = 'implemented'
            }

            // Redirect to ICT Officer dashboard after a brief delay to show success message
            setTimeout(() => {
              this.$router.push('/ict-dashboard/access-requests')
            }, 1500)
          } else {
            this.showToast(res.error || 'Failed to grant access', 'error')
          }
        } catch (e) {
          console.error('Grant access error:', e)
          this.showToast(e.message || 'Failed to grant access', 'error')
        } finally {
          this.processing = false
        }
      },

      cancelGrantAccess() {
        this.showGrantAccessPopup = false
        this.grantAccessError = ''
      },

      // Generate SMS preview text (matches backend template)
      getSmsPreviewText() {
        const name = this.form.staff_name || 'User'
        const ref =
          this.form.request_id || 'MLG-REQ' + String(this.getRequestId || '').padStart(6, '0')

        // Get access types (previous template)
        const accessTypes = []
        if (
          this.form.jeeva_access_request ||
          (this.form.jeeva_modules_selected && this.form.jeeva_modules_selected.length > 0)
        ) {
          accessTypes.push('Jeeva')
        }
        if (
          this.form.wellsoft_access_request ||
          (this.form.wellsoft_modules_selected && this.form.wellsoft_modules_selected.length > 0)
        ) {
          accessTypes.push('Wellsoft')
        }
        if (
          this.form.internet_access_request ||
          (this.form.internet_purposes && this.form.internet_purposes.length > 0)
        ) {
          accessTypes.push('Internet')
        }
        const types = accessTypes.length > 0 ? accessTypes.join(' & ') : 'Access'

        const comment = (this.grantAccessComment || '').trim() || '[YOUR COMMENT HERE]'

        // Avoid "Access access" when types falls back to "Access"
        const accessText =
          String(types).toLowerCase() === 'access' ? 'access request' : `${types} access`

        return `Dear ${name}, your ${accessText} has been GRANTED and is now ACTIVE. Ref: ${ref}. Note: ${comment} - EABMS`
      },

      // Helper to compute row class safely
      rowClass(comment, index) {
        const base =
          'grid grid-cols-12 gap-0 p-0 border-b border-amber-300/20 transition-none hover:bg-amber-500/10 items-start'
        const isLast = index === this.visibleComments.length - 1
        const highlight = this.isHighlightedComment(comment)
          ? 'ring-2 ring-green-400/60 bg-green-900/20'
          : ''
        return [base, highlight, isLast ? 'border-b-0' : '']
      },
      isHighlightedComment(comment) {
        try {
          if (!this.lastAddedCommentMeta) return false
          if (!comment || typeof comment !== 'object') return false
          if (comment.stage !== 'ict_officer') return false
          const a = (comment.comments || '').trim()
          const b = (this.lastAddedCommentMeta.text || '').trim()
          return a.length > 0 && a === b
        } catch (e) {
          return false
        }
      },

      // ===========================================
      // MODULE LOADING METHODS WITH CACHING
      // ===========================================

      // Cache modules in localStorage with 24-hour TTL
      async loadModulesWithCaching() {
        const CACHE_KEY_WELLSOFT = 'cached_wellsoft_modules'
        const CACHE_KEY_JEEVA = 'cached_jeeva_modules'
        const CACHE_TTL = 24 * 60 * 60 * 1000 // 24 hours

        const loadFromCacheOrAPI = async (cacheKey, apiCall, fallbackData) => {
          try {
            // Check cache first
            const cached = localStorage.getItem(cacheKey)
            if (cached) {
              const { data, timestamp } = JSON.parse(cached)
              if (Date.now() - timestamp < CACHE_TTL) {
                console.log(`✅ Using cached ${cacheKey}:`, data.length, 'modules')
                return data
              }
            }

            // Load from API if cache miss or expired
            const response = await apiCall()
            if (response && response.success && Array.isArray(response.data)) {
              const modules = response.data.map((module) => module.name).sort()
              // Cache the result
              localStorage.setItem(
                cacheKey,
                JSON.stringify({
                  data: modules,
                  timestamp: Date.now()
                })
              )
              console.log(`✅ ${cacheKey} loaded from API:`, modules.length, 'modules')
              return modules
            } else {
              throw new Error('Invalid API response')
            }
          } catch (error) {
            console.warn(`⚠️ Failed to load ${cacheKey}, using fallback`, error)
            return fallbackData
          }
        }

        // Load both module types in parallel with caching
        const [wellsoftModules, jeevaModules] = await Promise.all([
          loadFromCacheOrAPI(CACHE_KEY_WELLSOFT, combinedAccessService.getWellsoftModules, []),
          loadFromCacheOrAPI(CACHE_KEY_JEEVA, combinedAccessService.getJeevaModules, [])
        ])

        this.wellsoftModules = wellsoftModules
        this.jeevaModules = jeevaModules
      },

      // Legacy methods kept for compatibility - now just call cached version
      async loadWellsoftModules() {
        await this.loadModulesWithCaching()
      },

      async loadJeevaModules() {
        await this.loadModulesWithCaching()
      },

      // ===========================================
      // JEEVA DROPDOWN HANDLERS
      // ===========================================
      isJeevaItemSelected(label) {
        return this.jeevaItemSelections.includes(label)
      },
      toggleJeevaItem(label) {
        let sel = [...this.jeevaItemSelections]
        if (label === 'Use') sel = sel.filter((v) => v !== 'Revoke')
        if (label === 'Revoke') sel = sel.filter((v) => v !== 'Use')
        if (sel.includes(label)) sel = sel.filter((v) => v !== label)
        else sel.push(label)
        this.jeevaItemSelections = sel
        // propagate into first Jeeva tab if exists
        const firstJeeva = this.tabs.find((t) => t.type === 'jeeva')
        if (firstJeeva) {
          const cur = this.moduleData[firstJeeva.key] || {}
          this.moduleData[firstJeeva.key] = { ...cur, selections: sel }
        }
      },
      onJeevaItemsKeydown(e) {
        if (!this.jeevaItemOpen && (e.key === 'Enter' || e.key === ' ')) {
          e.preventDefault()
          this.jeevaItemOpen = true
          return
        }
        if (!this.jeevaItemOpen) return
        if (e.key === 'Escape') {
          e.preventDefault()
          this.jeevaItemOpen = false
          return
        }
        const max = this.jeevaItemOptions.length - 1
        if (e.key === 'ArrowDown') {
          e.preventDefault()
          this.jeevaItemFocusIndex = Math.min(max, this.jeevaItemFocusIndex + 1)
        }
        if (e.key === 'ArrowUp') {
          e.preventDefault()
          this.jeevaItemFocusIndex = Math.max(0, this.jeevaItemFocusIndex - 1)
        }
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault()
          this.toggleJeevaItem(this.jeevaItemOptions[this.jeevaItemFocusIndex])
        }
      },

      // ===========================================
      // TABS AND MODULE MANAGEMENT
      // ===========================================
      syncTabs(type, values) {
        const prefix = { wellsoft: 'W', jeeva: 'J', internet: 'I' }[type]
        const newTabs = []
        const newData = { ...this.moduleData }
        values.forEach((val) => {
          const key = `${prefix}:${val}`
          if (!newData[key]) newData[key] = {}
          newTabs.push({
            key,
            label: val,
            type,
            component: this.componentFor(type)
          })
        })
        this.tabs = newTabs
        this.moduleData = newData
        this.activeTab = this.tabs[0]?.key || ''
      },

      // ===========================================
      // HELPER FUNCTIONS
      // ===========================================
      isSelected(type, value) {
        if (type === 'wellsoft') return this.selectedWellsoft.includes(value)
        if (type === 'jeeva') return this.selectedJeeva.includes(value)
        return false
      },

      // ===========================================

      // APPROVAL AND USER ROLE METHODS
      // ===========================================

      /**
       * Get the appropriate name placeholder for approval sections based on user role
       */
      getApprovalNamePlaceholder(approvalType) {
        const userRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
        const roleMapping = {
          hod: [
            'head_of_department',
            'hod',
            'head_department',
            'department_head',
            'hod_user',
            'head_of_dept'
          ],
          divisional_director: ['divisional_director'],
          ict_director: ['ict_director', 'dict'],
          head_it: ['head_it', 'head_of_it'],
          ict_officer: ['ict_officer', 'officer_ict']
        }

        // Check if current user's role matches this approval type
        if (roleMapping[approvalType] && roleMapping[approvalType].includes(userRole)) {
          return this.currentUser?.name || 'Loading user...'
        }

        // For other roles, show empty or existing data
        return this.getExistingApprovalName(approvalType) || ''
      },

      /**
       * Get the appropriate title for approval name fields
       */
      getApprovalNameTitle(approvalType) {
        const userRole = (this.getUserRole() || '').toLowerCase().replace(/[\s-]+/g, '_')
        const roleMapping = {
          hod: [
            'head_of_department',
            'hod',
            'head_department',
            'department_head',
            'hod_user',
            'head_of_dept'
          ],
          divisional_director: ['divisional_director'],
          ict_director: ['ict_director', 'dict'],
          head_it: ['head_it', 'head_of_it'],
          ict_officer: ['ict_officer', 'officer_ict']
        }

        // Check if current user's role matches this approval type
        if (roleMapping[approvalType] && roleMapping[approvalType].includes(userRole)) {
          return 'Auto-filled with: ' + (this.currentUser?.name || 'Loading...')
        }

        return 'This field will be filled by the appropriate approver'
      },

      /**
       * Get existing approval name from form data or request data
       */
      getExistingApprovalName(approvalType) {
        switch (approvalType) {
          case 'hod':
            return this.form.approvals.hod.name || this.requestData?.approvals?.hod?.name
          case 'divisional_director':
            return (
              this.form.approvals.divisionalDirector.name ||
              this.requestData?.approvals?.divisionalDirector?.name
            )
          case 'ict_director':
            return (
              this.form.approvals.directorICT.name || this.requestData?.approvals?.directorICT?.name
            )
          case 'head_it':
            return (
              this.form.implementation.headIT.name || this.requestData?.implementation?.headIT?.name
            )
          case 'ict_officer':
            return (
              this.form.implementation.ictOfficer.name ||
              this.requestData?.implementation?.ictOfficer?.name
            )
          default:
            return ''
        }
      },

      /**
       * Get current user role from various sources
       */
      getUserRole() {
        // Try multiple sources for user role from currentUser data
        if (!this.currentUser) {
          console.warn('No current user found for role detection')

          // Try to get from authStore as backup
          if (this.authStore && (this.authStore.user || this.authStore.currentUser)) {
            const storeUser = this.authStore.user || this.authStore.currentUser
            console.log('🔄 Trying to get role from authStore:', storeUser)
            const storeRole = storeUser.role || storeUser.user_role || storeUser.primary_role
            if (storeRole) {
              console.log('✅ Got role from authStore:', storeRole)
              return storeRole
            }
          }

          return null
        }

        // Try different role properties
        const role =
          this.currentUser.role ||
          this.currentUser.user_role ||
          this.currentUser.primary_role ||
          this.currentUser.userRole

        // Handle array of roles - prioritize specific roles if user has multiple
        if (Array.isArray(this.currentUser.roles) && this.currentUser.roles.length > 0) {
          // Priority order for roles (highest to lowest)
          const rolePriority = [
            'head_of_it',
            'head_it',
            'ict_director',
            'divisional_director',
            'head_of_department',
            'hod',
            'department_head',
            'head_department',
            'hod_user',
            'head_of_dept',
            'ict_officer'
          ]

          // Extract role names from the roles array
          const userRoleNames = this.currentUser.roles.map((roleObj) => {
            return typeof roleObj === 'string' ? roleObj : roleObj.name
          })

          console.log('📦 User has multiple roles:', userRoleNames)

          // Find highest priority role
          for (const priorityRole of rolePriority) {
            if (userRoleNames.includes(priorityRole)) {
              console.log('✨ Using prioritized role:', priorityRole)
              return priorityRole
            }
          }

          // Fallback to first role if no priority match
          const firstRole = userRoleNames[0]
          console.log('📦 Using first role as fallback:', firstRole)
          return firstRole
        }

        console.log('🚨 CRITICAL: getUserRole debug for HOD troubleshooting:', {
          detectedRole: role,
          currentUserExists: !!this.currentUser,
          currentUserName: this.currentUser?.name,
          roleProperty: this.currentUser?.role,
          userRoleProperty: this.currentUser?.user_role,
          primaryRoleProperty: this.currentUser?.primary_role,
          userRoleProperty2: this.currentUser?.userRole,
          rolesArray: this.currentUser?.roles,
          rolesArrayLength: this.currentUser?.roles?.length,
          fullCurrentUser: JSON.stringify(this.currentUser, null, 2)
        })

        return role || ''
      },

      // ===========================================
      // MODULE SELECTION METHODS
      // ===========================================
      toggleWellsoft(m) {
        this.selectedWellsoft = this.isSelected('wellsoft', m)
          ? this.selectedWellsoft.filter((x) => x !== m)
          : [...this.selectedWellsoft, m]
      },
      toggleJeeva(m) {
        this.selectedJeeva = this.isSelected('jeeva', m)
          ? this.selectedJeeva.filter((x) => x !== m)
          : [...this.selectedJeeva, m]
      },
      selectAll(type) {
        if (type === 'wellsoft') this.selectedWellsoft = [...this.wellsoftModules]
        if (type === 'jeeva') this.selectedJeeva = [...this.jeevaModules]
      },
      clearAll(type) {
        if (type === 'wellsoft') this.selectedWellsoft = []
        if (type === 'jeeva') this.selectedJeeva = []
      },
      componentFor(type) {
        if (type === 'wellsoft') return 'WellsoftPanel'
        if (type === 'jeeva') return 'JeevaPanel'
        return 'InternetPanel'
      },

      // ===========================================
      // UI INTERACTION METHODS
      // ===========================================
      toggleAccordion(key) {
        this.openAccordions.has(key)
          ? this.openAccordions.delete(key)
          : this.openAccordions.add(key)
      },
      tryCloseTab(key) {
        const t = this.tabs.find((x) => x.key === key)
        if (!t) return
        this.confirm = { key, label: t.label }
      },
      closeTab(key) {
        this.tabs = this.tabs.filter((t) => t.key !== key)
        const data = { ...this.moduleData }
        delete data[key]
        this.moduleData = data
        const [p, v] = key.split(':')
        if (p === 'W') this.selectedWellsoft = this.selectedWellsoft.filter((x) => x !== v)
        if (p === 'J') this.selectedJeeva = this.selectedJeeva.filter((x) => x !== v)
        this.confirm = { key: '', label: '' }
        this.activeTab = this.tabs[0]?.key || ''
      },

      // Access type change handler
      onAccessTypeChange(type) {
        console.log('Access type changed to:', type)
        this.hodAccessType = type

        // Clear temporary until date and error if switching to permanent
        if (type === 'permanent') {
          this.hodTemporaryUntil = ''
          this.hodTemporaryUntilError = ''
        }

        // Force reactivity update
        this.$forceUpdate()
      },

      // Submit
      async onSubmit() {
        // Reset errors
        this.errors = { pfNumber: '', staffName: '' }

        // Basic validation
        if (!this.form.shared.pfNumber) {
          this.errors.pfNumber = 'PF Number is required.'
          return
        }
        if (!this.form.shared.staffName) {
          this.errors.staffName = 'Staff Name is required.'
          return
        }

        // Enhanced validation: Check each service type based on what's expected
        // Determine what services should be available based on request_types or inferred from selections
        const hasWellsoftSelected = this.selectedWellsoft.length > 0
        const hasJeevaSelected = this.selectedJeeva.length > 0
        const hasInternetPurposes = this.internetPurposes.some((purpose) => purpose.trim())

        // If in review mode, check against the request_types from the database
        if (this.isReviewMode && this.requestData) {
          const requestTypes = this.requestData.request_types || this.requestData.request_type || []

          if (requestTypes.includes('wellsoft') && this.selectedWellsoft.length === 0) {
            this.toast = {
              show: true,
              message:
                'This request requires Wellsoft access. Please select at least 1 Wellsoft module.'
            }
            setTimeout(() => (this.toast.show = false), 5000)
            return
          }

          if (
            (requestTypes.includes('jeeva_access') || requestTypes.includes('jeeva')) &&
            this.selectedJeeva.length === 0
          ) {
            this.toast = {
              show: true,
              message: 'This request requires Jeeva access. Please select at least 1 Jeeva module.'
            }
            setTimeout(() => (this.toast.show = false), 5000)
            return
          }

          if (
            (requestTypes.includes('internet_access_request') ||
              requestTypes.includes('internet')) &&
            !hasInternetPurposes
          ) {
            this.toast = {
              show: true,
              message:
                'This request requires Internet access. Please provide at least 1 internet purpose.'
            }
            setTimeout(() => (this.toast.show = false), 5000)
            return
          }
        } else {
          // For new requests, ensure at least one service is selected
          if (!hasWellsoftSelected && !hasJeevaSelected && !hasInternetPurposes) {
            this.toast = {
              show: true,
              message:
                'Please select at least one service (Wellsoft modules, Jeeva modules, or Internet access)'
            }
            setTimeout(() => (this.toast.show = false), 5000)
            return
          }
        }

        // Check if signature is provided (accept either uploaded file or digital signature)
        if (!this.signaturePreview && !this.hasUserSigned) {
          this.toast = {
            show: true,
            message: 'Please sign the document before submitting'
          }
          setTimeout(() => (this.toast.show = false), 5000)
          return
        }

        try {
          this.loading = true

          // Debug: Log form state before preparation
          console.log('=== FORM DEBUG START ===')
          console.log('Form state before submission:', {
            shared: this.form.shared,
            selectedWellsoft: this.selectedWellsoft,
            selectedJeeva: this.selectedJeeva,
            wellsoftRequestType: this.wellsoftRequestType,
            jeevaRequestType: this.jeevaRequestType,
            internetPurposes: this.internetPurposes,
            // HOD access rights
            hodAccessType: this.hodAccessType,
            hodTemporaryUntil: this.hodTemporaryUntil,
            approvals: this.form.approvals,
            implementation: this.form.implementation,
            comments: this.form.comments,
            signatureFile: this.form.shared.signature ? 'File object present' : 'No signature file'
          })

          // Prepare form data for submission
          const formData = {
            shared: {
              pfNumber: this.form.shared.pfNumber,
              staffName: this.form.shared.staffName,
              department: this.form.shared.department,
              phone: this.form.shared.phone
            },
            selectedWellsoft: this.selectedWellsoft,
            selectedJeeva: this.selectedJeeva,
            wellsoftRequestType: this.wellsoftRequestType,
            jeevaRequestType: this.jeevaRequestType, // HOD can specify use/revoke for Jeeva modules
            internetPurposes: this.internetPurposes.filter((purpose) => purpose.trim()),
            // HOD access rights (decided during HOD approval)
            hodAccessType: this.hodAccessType,
            hodTemporaryUntil: this.hodAccessType === 'temporary' ? this.hodTemporaryUntil : null,
            signature: this.form.shared.signature, // This should be the actual file object
            approvals: {
              hod: {
                name: this.form.approvals.hod.name,
                signature: this.form.approvals.hod.signature,
                date: this.form.approvals.hod.date
              },
              divisionalDirector: {
                name: this.form.approvals.divisionalDirector.name,
                signature: this.form.approvals.divisionalDirector.signature,
                date: this.form.approvals.divisionalDirector.date
              },
              directorICT: {
                name: this.form.approvals.directorICT.name,
                signature: this.form.approvals.directorICT.signature,
                date: this.form.approvals.directorICT.date
              }
            },
            implementation: {
              headIT: {
                name: this.form.implementation.headIT.name,
                signature: this.form.implementation.headIT.signature,
                date: this.form.implementation.headIT.date
              },
              ictOfficer: {
                name: this.form.implementation.ictOfficer.name,
                signature: this.form.implementation.ictOfficer.signature,
                date: this.form.implementation.ictOfficer.date
              }
            },
            comments: this.form.comments
          }

          console.log('Prepared formData object:', {
            ...formData,
            signature: formData.signature ? 'File uploaded' : 'No signature'
          })
          console.log('=== FORM DEBUG END ===')

          // Validate form data
          const validation = bothServiceFormService.validateFormData(formData)
          if (!validation.isValid) {
            this.toast = {
              show: true,
              message: validation.errors.join(', ')
            }
            setTimeout(() => (this.toast.show = false), 5000)
            return
          }

          // Submit the form
          const response = await bothServiceFormService.submitCombinedRequest(formData)

          if (response.success) {
            console.log('✅ Form submitted successfully:', response.data)

            this.toast = {
              show: true,
              message: `Combined access request submitted successfully! Request ID: ${response.data.id || 'N/A'}`
            }

            // Optionally redirect to a success page or reset form
            setTimeout(() => {
              this.toast.show = false
              // Uncomment to reset form after successful submission
              // this.onReset()
            }, 8000)
          } else {
            console.error('❌ Form submission failed:', response.error)

            // Handle validation errors
            if (response.errors) {
              let errorMessage = 'Please fix the following errors: '
              Object.keys(response.errors).forEach((field) => {
                if (Array.isArray(response.errors[field])) {
                  errorMessage += response.errors[field].join(', ') + ' '
                }
              })

              this.toast = {
                show: true,
                message: errorMessage
              }
            } else {
              this.toast = {
                show: true,
                message: response.message || 'Failed to submit request. Please try again.'
              }
            }

            setTimeout(() => (this.toast.show = false), 8000)
          }
        } catch (error) {
          console.error('❌ Unexpected error during form submission:', error)

          this.toast = {
            show: true,
            message: 'An unexpected error occurred. Please try again.'
          }
          setTimeout(() => (this.toast.show = false), 5000)
        } finally {
          this.loading = false
        }
      },
      onReset() {
        this.form = {
          shared: { pfNumber: '', staffName: '', department: '', phone: '' },
          // accessRights removed - will be decided by HOD during approval
          approvals: {
            hod: {
              name: '',
              signature: '',
              date: ''
            },
            divisionalDirector: {
              name: '',
              signature: '',
              date: ''
            },
            directorICT: {
              name: '',
              signature: '',
              date: ''
            }
          },
          comments: '',
          implementation: {
            headIT: {
              name: '',
              signature: '',
              date: ''
            },
            ictOfficer: {
              name: '',
              signature: '',
              date: ''
            }
          }
        }
        this.selectedWellsoft = []
        this.selectedJeeva = []
        this.wellsoftQuery = ''
        this.jeevaQuery = ''
        this.internetPurposes = ['', '', '', '']
        this.wellsoftRequestType = 'use'
        this.tabs = []
        this.moduleData = {}
        this.activeTab = ''
        this.openAccordions.clear()
        this.errors = { pfNumber: '', staffName: '' }
        // Reset signatures
        this.signaturePreview = ''
        this.signatureFileName = ''
        this.hodSignaturePreview = ''
        this.hodSignatureFileName = ''
        this.divDirectorSignaturePreview = ''
        this.divDirectorSignatureFileName = ''
        this.directorICTSignaturePreview = ''
        this.directorICTSignatureFileName = ''
        this.headITSignaturePreview = ''
        this.headITSignatureFileName = ''
        this.ictOfficerSignaturePreview = ''
        this.ictOfficerSignatureFileName = ''
      },

      // Review mode methods - optimized with request deduplication and timeout
      async loadRequestData() {
        // Prevent multiple simultaneous API calls for the same request
        if (this.loadingRequestData) {
          console.log('⏳ Request data already loading, waiting...')
          return
        }

        try {
          this.loading = true
          this.loadingRequestData = true
          this.error = null

          // Load request data for review mode with timeout
          console.log('💾 Loading request data for ID:', this.getRequestId)

          const requestId = this.getRequestId
          if (!requestId) {
            throw new Error('No request ID provided')
          }

          // Use race condition with timeout to prevent hanging
          const timeoutPromise = new Promise((_, reject) =>
            setTimeout(() => reject(new Error('Request timeout - API call took too long')), 15000)
          )

          const userRole = (this.getUserRole() || '').toLowerCase()
          console.log('💾 User role:', userRole)

          console.log('💾 Trying combined API service with timeout...')
          let response
          try {
            response = await Promise.race([
              combinedAccessService.getRequestById(requestId),
              timeoutPromise
            ])
            console.log('💾 Combined API response received successfully')
          } catch (error) {
            console.warn('💾 Combined API failed, trying role-specific API:', error.message)

            if (['ict_officer', 'officer_ict'].includes(userRole)) {
              console.log('💾 Using ICT Officer API service as fallback')
              response = await Promise.race([
                ictOfficerService.getAccessRequestById(requestId),
                timeoutPromise
              ])
            } else {
              console.error('💾 No fallback API available')
              throw error
            }
          }

          console.log('💾 API response received:', response)

          if (response && response.success && response.data) {
            console.log('✅ Request data loaded successfully')
            this.requestData = response.data
            // Initialize role comment draft once data is available
            this.loadRoleCommentDraft()
            if (this.isDevelopment) {
              console.log('Loaded request data:', this.requestData)
              console.log('🔍 Signature Status Debug:', {
                hod_signature_status: this.requestData.approvals?.hod?.signature_status,
                hod_signature_display: this.requestData.approvals?.hod?.signature_display,
                divisional_signature_status:
                  this.requestData.approvals?.divisionalDirector?.signature_status,
                divisional_signature_display:
                  this.requestData.approvals?.divisionalDirector?.signature_display,
                ict_signature_status: this.requestData.approvals?.directorICT?.signature_status,
                ict_signature_display: this.requestData.approvals?.directorICT?.signature_display,
                full_approvals_object: this.requestData.approvals
              })
            }
            // Initialize HOD access rights from server if present
            if (this.requestData.access_type) {
              this.hodAccessType = this.requestData.access_type
            }
            if (this.requestData.temporary_until) {
              // Expecting YYYY-MM-DD or date string; normalize to YYYY-MM-DD for input[type=date]
              const d = new Date(this.requestData.temporary_until)
              if (!isNaN(d.getTime())) {
                const yyyy = d.getFullYear()
                const mm = String(d.getMonth() + 1).padStart(2, '0')
                const dd = String(d.getDate()).padStart(2, '0')
                this.hodTemporaryUntil = `${yyyy}-${mm}-${dd}`
              }
            }
            // Load HOD comments from database
            if (this.requestData.hod_comments) {
              this.hodComments = this.requestData.hod_comments
            }
            if (this.isDevelopment) {
              console.log('Signature path from API:', this.requestData.signature_path)
              console.log('Type of signature_path:', typeof this.requestData.signature_path)
            }

            // Populate form with request data
            if (this.isDevelopment) {
              console.log('🔍 Debug: Full requestData structure:', this.requestData)
              console.log('🔍 Debug: requestData.shared structure:', this.requestData.shared)
            }

            // Map approvals from backend to form (names and dates) so read-only fields show values
            try {
              const ap = this.requestData.approvals || {}
              const impl = this.requestData.implementation || {}
              const rd = this.requestData

              if (this.isDevelopment) {
                console.log('🔍 Mapping approvals debug:', {
                  approvals_nested: ap,
                  direct_hod_name: rd.hod_name,
                  direct_hod_signature_path: rd.hod_signature_path,
                  direct_hod_approved_at: rd.hod_approved_at,
                  divisional_name: rd.divisional_director_name,
                  divisional_approved_at: rd.divisional_approved_at,
                  dict_name: rd.dict_name,
                  dict_approved_at: rd.dict_approved_at
                })
              }

              // HOD mapping - check multiple sources
              this.form.approvals.hod.name =
                ap.hod?.name || rd.hod_name || this.form.approvals.hod.name || ''
              this.form.approvals.hod.date = this.formatDateForInput(
                ap.hod?.date ||
                  ap.hod?.approved_at ||
                  rd.hod_approved_at ||
                  this.form.approvals.hod.date
              )

              // Divisional Director mapping
              this.form.approvals.divisionalDirector.name =
                ap.divisionalDirector?.name ||
                rd.divisional_director_name ||
                this.form.approvals.divisionalDirector.name ||
                ''
              this.form.approvals.divisionalDirector.date = this.formatDateForInput(
                ap.divisionalDirector?.date ||
                  ap.divisionalDirector?.approved_at ||
                  rd.divisional_approved_at ||
                  this.form.approvals.divisionalDirector.date
              )

              // ICT Director mapping
              this.form.approvals.directorICT.name =
                ap.directorICT?.name || rd.dict_name || this.form.approvals.directorICT.name || ''
              this.form.approvals.directorICT.date = this.formatDateForInput(
                ap.directorICT?.date ||
                  ap.directorICT?.approved_at ||
                  rd.dict_approved_at ||
                  this.form.approvals.directorICT.date
              )

              // Head IT mapping
              this.form.implementation.headIT.name =
                impl.headIT?.name || rd.head_it_name || this.form.implementation.headIT.name || ''
              this.form.implementation.headIT.date = this.formatDateForInput(
                impl.headIT?.date ||
                  impl.headIT?.approved_at ||
                  rd.head_it_approved_at ||
                  this.form.implementation.headIT.date
              )

              // ICT Officer mapping
              this.form.implementation.ictOfficer.name =
                impl.ictOfficer?.name ||
                rd.ict_officer_name ||
                this.form.implementation.ictOfficer.name ||
                ''
              this.form.implementation.ictOfficer.date = this.formatDateForInput(
                impl.ictOfficer?.date ||
                  impl.ictOfficer?.approved_at ||
                  rd.ict_officer_approved_at ||
                  this.form.implementation.ictOfficer.date
              )

              if (this.isDevelopment) {
                console.log('✅ Form after mapping:', {
                  hod: {
                    name: this.form.approvals.hod.name,
                    date: this.form.approvals.hod.date
                  },
                  divisional: {
                    name: this.form.approvals.divisionalDirector.name,
                    date: this.form.approvals.divisionalDirector.date
                  },
                  dict: {
                    name: this.form.approvals.directorICT.name,
                    date: this.form.approvals.directorICT.date
                  }
                })
              }

              // Force reactivity update after mapping
              this.$nextTick(() => {
                if (this.isDevelopment) {
                  console.log('🔄 After nextTick - checking signature status:', {
                    shouldShowHodSignedIndicator: this.shouldShowHodSignedIndicator,
                    shouldShowHodNoSignatureIndicator: this.shouldShowHodNoSignatureIndicator,
                    currentUser: this.currentUser?.name,
                    userRole: this.getUserRole(),
                    isReviewMode: this.isReviewMode,
                    requestStatus: this.requestData?.status
                  })
                }
              })
            } catch (e) {
              console.warn('Mapping approvals to form failed:', e)
            }

            // After mapping, load digital signature history for this request
            await this.fetchSignatureHistory()

            // Ensure DICT date is set if we are at DICT stage
            try {
              this.ensureIctDirectorDateAutoFill()
            } catch (e) {
              /* no-op */
            }

            // Check if data is in the 'shared' object (as returned by backend API)
            if (this.requestData.shared) {
              if (this.isDevelopment) {
                console.log('✅ Using data from requestData.shared')
              }
              this.form.shared = {
                pfNumber: this.requestData.shared.pfNumber || '',
                staffName: this.requestData.shared.staffName || '',
                department: this.requestData.shared.department || '',
                phone: this.normalizePhoneNumber(this.requestData.shared.phone || '')
              }
            } else {
              // Fallback to direct properties (legacy support)
              if (this.isDevelopment) {
                console.log('⚠️ Fallback: Using direct properties from requestData')
              }
              this.form.shared = {
                pfNumber: this.requestData.pf_number || '',
                staffName: this.requestData.staff_name || this.requestData.full_name || '',
                department: this.requestData.department || this.requestData.department_name || '',
                phone: this.normalizePhoneNumber(
                  this.requestData.phone || this.requestData.phone_number || ''
                )
              }
            }

            if (this.isDevelopment) {
              console.log('✅ Personal Information populated:', this.form.shared)
            }

            // Auto-populate Divisional Director date field from HOD approval date
            this.populateDivisionalDirectorDateFromHod()

            // Trigger Head IT name population if user is Head IT
            this.triggerHeadItNamePopulationIfNeeded()

            // Force ICT Officer approval refresh after data loads
            this.$nextTick(() => {
              console.log('🔄 Post-load ICT Officer approval check:', {
                hasCurrentUser: !!this.currentUser,
                hasRequestData: !!this.requestData,
                userRole: this.getUserRole(),
                currentStage: this.currentApprovalStage,
                status: this.requestData?.status,
                isIctOfficerApprovalEditable: this.isIctOfficerApprovalEditable
              })
              // Force update to recalculate computed properties
              this.$forceUpdate()
            })

            // Force reactivity update
            this.$forceUpdate()

            // Handle signature data
            if (this.requestData.signature_path) {
              if (this.isDevelopment) {
                console.log('Signature found:', this.requestData.signature_path)
              }
              // In review mode, we don't load the actual file, just show the status
              this.signatureFileName = this.getSignatureFileName(this.requestData.signature_path)
            }

            // Populate module selections based on actual database data
            if (this.requestData.request_types || this.requestData.request_type) {
              const types = this.requestData.request_types || this.requestData.request_type || []

              // Debug logging for HOD users
              if (
                this.isDevelopment &&
                [
                  'head_of_department',
                  'hod',
                  'department_head',
                  'head_department',
                  'hod_user',
                  'head_of_dept'
                ].includes(this.getUserRole()?.toLowerCase())
              ) {
                console.log('🏥 HOD Module Loading Debug:', {
                  requestTypes: types,
                  userRole: this.getUserRole(),
                  canEdit: this.isHodApprovalEditable,
                  isReviewMode: this.isReviewMode
                })
              }

              // Handle Wellsoft modules - use selected modules from database
              if (types.includes('wellsoft')) {
                // Try multiple possible column names for selected Wellsoft modules
                const wellsoftSelected =
                  this.requestData.wellsoft_modules_selected ||
                  this.requestData.selected_wellsoft ||
                  this.requestData.wellsoft_modules ||
                  []

                // Parse if it's a JSON string
                let parsedWellsoft = []
                if (typeof wellsoftSelected === 'string') {
                  try {
                    parsedWellsoft = JSON.parse(wellsoftSelected)
                  } catch (e) {
                    console.warn('Failed to parse Wellsoft modules:', wellsoftSelected)
                    parsedWellsoft = []
                  }
                } else if (Array.isArray(wellsoftSelected)) {
                  parsedWellsoft = wellsoftSelected
                }

                this.selectedWellsoft = Array.isArray(parsedWellsoft) ? parsedWellsoft : []

                // Load Wellsoft request type (use/revoke)
                const wellsoftReqType =
                  this.requestData.wellsoft_request_type || this.requestData.wellsoft_type || 'use'
                this.wellsoftRequestType = ['use', 'revoke'].includes(wellsoftReqType)
                  ? wellsoftReqType
                  : 'use'

                if (this.isDevelopment) {
                  console.log('Loaded Wellsoft selected modules:', this.selectedWellsoft)
                  console.log('Loaded Wellsoft request type:', this.wellsoftRequestType)
                }
              } else {
                this.selectedWellsoft = []
              }

              // Handle Jeeva modules - use selected modules from database
              if (types.includes('jeeva_access') || types.includes('jeeva')) {
                // Try multiple possible column names for selected Jeeva modules
                const jeevaSelected =
                  this.requestData.jeeva_modules_selected ||
                  this.requestData.selected_jeeva ||
                  this.requestData.jeeva_modules ||
                  []

                // Parse if it's a JSON string
                let parsedJeeva = []
                if (typeof jeevaSelected === 'string') {
                  try {
                    parsedJeeva = JSON.parse(jeevaSelected)
                  } catch (e) {
                    console.warn('Failed to parse Jeeva modules:', jeevaSelected)
                    parsedJeeva = []
                  }
                } else if (Array.isArray(jeevaSelected)) {
                  parsedJeeva = jeevaSelected
                }

                this.selectedJeeva = Array.isArray(parsedJeeva) ? parsedJeeva : []

                // Load Jeeva request type (use/revoke)
                const jeevaReqType =
                  this.requestData.jeeva_request_type || this.requestData.jeeva_type || 'use'
                this.jeevaRequestType = ['use', 'revoke'].includes(jeevaReqType)
                  ? jeevaReqType
                  : 'use'

                if (this.isDevelopment) {
                  console.log('Loaded Jeeva selected modules:', this.selectedJeeva)
                  console.log('Loaded Jeeva request type:', this.jeevaRequestType)
                }
              } else {
                this.selectedJeeva = []
              }

              // Handle Internet purposes
              if (types.includes('internet_access_request') || types.includes('internet')) {
                const internetPurposes =
                  this.requestData.internet_purposes || this.requestData.purpose || []
                const purposes = Array.isArray(internetPurposes)
                  ? internetPurposes
                  : [internetPurposes]
                // Ensure we have exactly 4 purpose slots
                this.internetPurposes = [...purposes, '', '', '', ''].slice(0, 4)
                if (this.isDevelopment) {
                  console.log('Loaded Internet purposes:', this.internetPurposes)
                }
              } else {
                this.internetPurposes = ['', '', '', '']
              }
            } else {
              // Reset all selections if no request types
              this.selectedWellsoft = []
              this.selectedJeeva = []
              this.internetPurposes = ['', '', '', '']
            }

            // Force tab generation for HOD users in review mode to ensure they see the module tabs
            // This is crucial for HOD workflow - they need to see and edit modules for pending requests

            // CRITICAL DEBUG: Always log this for troubleshooting HOD workflow
            console.log('🚨 CRITICAL HOD DEBUG - loadRequestData end:', {
              isHodApprovalEditable: this.isHodApprovalEditable,
              isReviewMode: this.isReviewMode,
              hasRequestData: !!this.requestData,
              requestStatus: this.requestData?.status,
              hodStatus: this.requestData?.hod_status,
              userRole: this.getUserRole(),
              requestId: this.getRequestId,
              currentTabs: this.tabs.length,
              tabDetails: this.tabs.map((t) => ({ key: t.key, label: t.label, type: t.type }))
            })

            if (this.isHodApprovalEditable) {
              if (this.isDevelopment) {
                console.log('🏥 HOD Force Tab Generation Debug:', {
                  isHodApprovalEditable: this.isHodApprovalEditable,
                  selectedWellsoft: this.selectedWellsoft,
                  selectedJeeva: this.selectedJeeva,
                  requestTypes: this.requestData.request_types || this.requestData.request_type,
                  currentTabs: this.tabs.length
                })
              }

              // For HOD users, ensure all available module types are accessible even if not initially selected
              // This allows them to select modules for pending requests
              const requestTypes =
                this.requestData.request_types || this.requestData.request_type || []

              // Check if this is a combined access request that should have Wellsoft and Jeeva options
              const shouldShowWellsoft = requestTypes.includes('wellsoft')
              const shouldShowJeeva =
                requestTypes.includes('jeeva_access') || requestTypes.includes('jeeva')
              const shouldShowInternet =
                requestTypes.includes('internet_access_request') ||
                requestTypes.includes('internet')

              // Always sync tabs for HOD users to allow module selection
              if (shouldShowWellsoft) {
                this.syncTabs('wellsoft', this.selectedWellsoft)
              }

              if (shouldShowJeeva) {
                this.syncTabs('jeeva', this.selectedJeeva)
              }

              // Also handle internet if present or if no specific request types are defined
              if (shouldShowInternet && this.internetPurposes.some((purpose) => purpose.trim())) {
                this.syncTabs('internet', [])
              }

              if (this.isDevelopment) {
                console.log('🔄 HOD Module tabs force-synced:', {
                  shouldShowWellsoft,
                  shouldShowJeeva,
                  shouldShowInternet,
                  wellsoftTabs: this.selectedWellsoft,
                  jeevaTabs: this.selectedJeeva,
                  internetPurposes: this.internetPurposes.filter((p) => p.trim()),
                  resultingTabs: this.tabs.map((t) => ({
                    key: t.key,
                    label: t.label,
                    type: t.type
                  }))
                })
              }

              // Force the first tab to be active if no tab is currently active
              if (
                this.tabs.length > 0 &&
                (!this.activeTab || !this.tabs.find((t) => t.key === this.activeTab))
              ) {
                this.activeTab = this.tabs[0].key
                if (this.isDevelopment) {
                  console.log('🎯 HOD: Set active tab to:', this.activeTab)
                }
              }
            }

            if (this.isDevelopment) {
              console.log('Form populated successfully')
            }
          } else {
            console.error('❌ Request data loading failed:')
            console.error('❌ Response:', response)
            console.error('❌ Response success:', response?.success)
            console.error('❌ Response data:', response?.data)
            console.error('❌ Response error:', response?.error)
            throw new Error(response?.error || response?.message || 'Failed to load request data')
          }
        } catch (error) {
          console.error('Error loading request data:', error)
          this.error = `Failed to load request data: ${error.message}`

          // For ICT Officers, try to continue with minimal functionality instead of failing completely
          const userRole = (this.getUserRole() || '').toLowerCase()
          if (['ict_officer', 'officer_ict'].includes(userRole)) {
            console.log(
              '🛡️ ICT Officer detected - attempting graceful degradation instead of complete failure'
            )
            // Only show toast if essential data is missing; otherwise, degrade silently
            const essentialOk = !!(this.requestData && this.requestData.id)
            if (!essentialOk) {
              this.toast = {
                show: true,
                message: 'Some data could not be loaded, but you can still view the form.'
              }
            } else {
              try {
                console.warn(
                  'BothServiceForm: Partial load for ICT Officer (non-critical data missing); suppressing toast'
                )
              } catch (e) {
                /* noop */
              }
            }
          } else {
            this.toast = {
              show: true,
              message: 'Error loading request data'
            }
          }

          setTimeout(() => (this.toast.show = false), 3000)
        } finally {
          this.loading = false
          this.loadingRequestData = false
        }
      },

      getApprovalStatus(role) {
        if (!this.requestData) return 'pending'

        switch (role) {
          case 'hod':
            return this.requestData.hod_approval_status || 'pending'
          case 'divisional':
            return this.requestData.divisional_approval_status || 'pending'
          case 'dict':
            return this.requestData.dict_approval_status || 'pending'
          case 'headOfIt':
            return this.requestData.head_it_approval_status || 'pending'
          case 'ict':
            return this.requestData.ict_approval_status || 'pending'
          default:
            return 'pending'
        }
      },

      // Extract filename from signature path
      getSignatureFileName(signaturePath) {
        if (!signaturePath) return ''
        return signaturePath.split('/').pop() || signaturePath
      },

      canApproveAtStage() {
        // Only show in review mode with loaded data
        if (!this.isReviewMode || !this.requestData) return false

        const stage = this.currentApprovalStage
        const userRole = (this.getUserRole() || '').toLowerCase()

        // Define role mappings for each stage
        const hodRoles = [
          'head_of_department',
          'hod',
          'department_head',
          'head_department',
          'hod_user',
          'head_of_dept'
        ]
        const divisionalRoles = ['divisional_director']
        const ictDirectorRoles = ['ict_director', 'dict']

        // Check if user can approve at current stage
        if (stage === 'hod' && hodRoles.includes(userRole)) {
          // Allow if HOD approval still pending (fallback to pending)
          const hodStatus = this.requestData.hod_approval_status || 'pending'
          return hodStatus === 'pending'
        }

        if (stage === 'divisional' && divisionalRoles.includes(userRole)) {
          // Allow if request is HOD approved and divisional approval is pending
          const status = this.requestData.status || 'pending'
          return status === 'hod_approved' || status === 'pending_divisional'
        }

        if (stage === 'ict_director') {
          // Only ICT Director users should see the action bar for this stage
          const isDictUser =
            ictDirectorRoles.includes(userRole) || this.viewerStage() === 'ict_director'

          if (!isDictUser) return false

          // If the ICT Director section is active or explicitly editable, show action bar
          if (this.isIctDirectorApprovalActive || this.isIctDirectorApprovalEditable) return true

          // Fallbacks for older payloads and generic statuses
          const status = (
            this.requestData.status ||
            this.requestData.dict_status ||
            ''
          ).toLowerCase()
          const dictStagePending =
            (
              this.requestData.ict_director_status ||
              this.requestData.dict_status ||
              ''
            ).toLowerCase() === 'pending'

          const result =
            ['divisional_approved', 'pending_ict_director', 'pending_dict'].includes(status) ||
            dictStagePending

          if (this.isDevelopment) {
            try {
              console.log('🔎 canApproveAtStage (DICT):', {
                stage,
                userRole,
                isDictUser,
                isIctDirectorApprovalActive: this.isIctDirectorApprovalActive,
                isIctDirectorApprovalEditable: this.isIctDirectorApprovalEditable,
                status,
                dictStagePending,
                result
              })
            } catch (e) {
              /* no-op: debug log suppressed */
            }
          }

          return result
        }

        // Head IT roles check
        const headItRoles = ['head_it', 'head_of_it']
        if (stage === 'head_it' && headItRoles.includes(userRole)) {
          // Allow if request is ICT Director approved and Head IT approval is pending
          const status = this.requestData.status || 'pending'
          return status === 'ict_director_approved' || status === 'pending_head_it'
        }

        return false
      },

      async approveRequest() {
        // Prevent multiple rapid clicks using debouncing
        if (this.processing || this.loading) {
          return
        }

        // Check if a digital signature is required but missing
        if (this.isSignatureRequiredForApproval) {
          this.toast = {
            show: true,
            message: 'Please digitally sign this request before approving.'
          }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        // Gentle validation gate: if we still have validation errors (e.g., HOD comments), prompt and stop
        const errs = this.validationErrors
        if (errs && errs.length) {
          this.toast = { show: true, message: errs[0] }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        try {
          this.processing = true
          this.loading = true

          const stage = this.currentApprovalStage
          const userRole = (this.getUserRole() || '').toLowerCase()

          // Route to appropriate approval method based on stage and role
          if (
            stage === 'hod' &&
            [
              'head_of_department',
              'hod',
              'department_head',
              'head_department',
              'hod_user',
              'head_of_dept'
            ].includes(userRole)
          ) {
            await this.approveAsHod()
          } else if (stage === 'divisional' && ['divisional_director'].includes(userRole)) {
            await this.approveAsDivisionalDirector()
          } else if (stage === 'ict_director' && ['ict_director', 'dict'].includes(userRole)) {
            await this.approveAsIctDirector()
          } else if (stage === 'head_it' && ['head_it', 'head_of_it'].includes(userRole)) {
            await this.approveAsHeadIt()
          } else {
            throw new Error('You do not have permission to approve at this stage')
          }
        } catch (error) {
          console.error('Error approving request:', error)
          this.toast = {
            show: true,
            message: `Error approving request: ${error.message}`
          }
          setTimeout(() => (this.toast.show = false), 3000)
        } finally {
          this.processing = false
          this.loading = false
        }
      },

      async approveAsHod() {
        // Validate minimal HOD fields
        const hodName = this.form.approvals.hod.name || this.currentUser?.name || ''
        const hodDate = this.form.approvals.hod.date || new Date().toISOString().slice(0, 10)
        // Digital signature required will be enforced by backend; proceed if comments are valid

        // Validate access rights (HOD-only)
        this.hodTemporaryUntilError = ''
        if (this.hodAccessType === 'temporary') {
          if (!this.hodTemporaryUntil) {
            this.hodTemporaryUntilError = 'Please select an end date for temporary access'
            this.toast = { show: true, message: this.hodTemporaryUntilError }
            setTimeout(() => (this.toast.show = false), 3000)
            return
          }
          // Ensure future date
          const today = new Date()
          today.setHours(0, 0, 0, 0)
          const tmp = new Date(this.hodTemporaryUntil)
          if (isNaN(tmp.getTime()) || tmp <= today) {
            this.hodTemporaryUntilError = 'Temporary access end date must be in the future'
            this.toast = { show: true, message: this.hodTemporaryUntilError }
            setTimeout(() => (this.toast.show = false), 3000)
            return
          }
        }

        const payload = {
          hodName,
          approvedDate: hodDate,
          comments: this.hodComments || this.form.comments || 'Approved by HOD',
          selectedWellsoft: this.selectedWellsoft,
          selectedJeeva: this.selectedJeeva,
          moduleRequestedFor: this.wellsoftRequestType || 'use',
          accessType: this.hodAccessType,
          temporaryUntil: this.hodAccessType === 'temporary' ? this.hodTemporaryUntil : undefined,
          hodComments: this.hodComments // Add HOD comments to payload
        }

        // Add timeout to prevent hanging requests
        const approvalPromise = bothServiceFormService.hodApproveModuleRequest(
          this.getRequestId,
          payload
        )
        const timeoutPromise = new Promise((_, reject) =>
          setTimeout(() => reject(new Error('Request timed out. Please try again.')), 30000)
        )

        const result = await Promise.race([approvalPromise, timeoutPromise])

        if (result.success) {
          // Optimistically update local comments so the summary reflects immediately
          this.updateRoleCommentsLocally(
            'hod',
            this.hodComments || this.form.comments || 'Approved by HOD'
          )

          // Persist last approved request so the list can keep it visible even if filtered
          try {
            const idStr = String(this.getRequestId)
            localStorage.setItem('lastApprovedRequestId', idStr)
            // Maintain a set of approved IDs that should remain visible on the list
            const keepRaw = localStorage.getItem('keepVisibleRequests')
            const keepArr = Array.isArray(JSON.parse(keepRaw || '[]'))
              ? JSON.parse(keepRaw || '[]')
              : []
            if (!keepArr.includes(idStr)) {
              keepArr.push(idStr)
              localStorage.setItem('keepVisibleRequests', JSON.stringify(keepArr))
            }
          } catch (e) {
            console.warn('Persist keepVisibleRequests failed', e)
          }

          this.toast = {
            show: true,
            message: 'Request approved successfully'
          }
          setTimeout(() => {
            this.toast.show = false
            this.goBackToRequests()
          }, 1200)
        } else {
          // Handle specific error types
          let errorMessage = 'Failed to approve request'

          if (result.errors && Object.keys(result.errors).length > 0) {
            errorMessage = 'Validation errors: ' + Object.values(result.errors).flat().join(', ')
          } else if (result.error) {
            errorMessage = result.error
          }

          throw new Error(errorMessage)
        }
      },

      async approveAsDivisionalDirector() {
        // Validate Divisional Director fields
        const divisionalDirectorName =
          this.form.approvals.divisionalDirector.name || this.currentUser?.name || ''
        const divisionalDate =
          this.form.approvals.divisionalDirector.date || new Date().toISOString().slice(0, 10)
        // Digital signature required will be enforced by backend

        // Use role comment draft as the authoritative comments input
        const roleComment = (this.roleCommentsDraft || '').trim()
        if (!roleComment) {
          this.toast = {
            show: true,
            message: 'Please provide your comments/recommendations before approving'
          }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        const payload = {
          divisionalDirectorName,
          approvedDate: divisionalDate,
          comments: roleComment
        }

        // Add timeout to prevent hanging requests
        const approvalPromise = bothServiceFormService.divisionalDirectorApprove(
          this.getRequestId,
          payload
        )
        const timeoutPromise = new Promise((_, reject) =>
          setTimeout(() => reject(new Error('Request timed out. Please try again.')), 30000)
        )

        const result = await Promise.race([approvalPromise, timeoutPromise])

        if (result.success) {
          // Optimistically update local comments for Divisional Director
          this.updateRoleCommentsLocally('divisional', roleComment)

          this.toast = {
            show: true,
            message: 'Request approved successfully by Divisional Director'
          }
          setTimeout(() => {
            this.toast.show = false
            this.goBackToDivisionalRequests()
          }, 1500)
        } else {
          // Handle specific error types
          let errorMessage = 'Failed to approve request'

          if (result.errors && Object.keys(result.errors).length > 0) {
            errorMessage = 'Validation errors: ' + Object.values(result.errors).flat().join(', ')
          } else if (result.error) {
            errorMessage = result.error
          }

          throw new Error(errorMessage)
        }
      },

      // ICT Director specific approval method
      async approveDictRequest() {
        // Prevent multiple rapid clicks
        if (this.processing || this.loading) {
          return
        }

        // Check if a digital signature is required but missing
        if (this.isSignatureRequiredForApproval) {
          this.toast = {
            show: true,
            message: 'Please digitally sign this request before approving.'
          }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        try {
          this.processing = true
          this.loading = true

          await this.approveAsIctDirector()
        } catch (error) {
          console.error('Error approving request as ICT Director:', error)
          this.toast = {
            show: true,
            message: `Error approving request: ${error.message}`
          }
          setTimeout(() => (this.toast.show = false), 3000)
        } finally {
          this.processing = false
          this.loading = false
        }
      },

      // ICT Director specific rejection method
      async rejectDictRequest() {
        // Check if signature is required but missing
        if (this.isSignatureRequiredForApproval) {
          this.toast = {
            show: true,
            message: 'Please upload your ICT Director signature before rejecting this request.'
          }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        // Show rejection reason modal
        this.showRejectionModal = true
      },

      async approveAsIctDirector() {
        // Guard: ensure the current user has the exact ict_director role to prevent 403s
        try {
          const roles = (this.currentUser?.roles || [])
            .map((r) => (typeof r === 'string' ? r : r?.name))
            .filter(Boolean)
          const normalized = roles.map((r) =>
            String(r)
              .toLowerCase()
              .replace(/[-\s]+/g, '_')
          )
          const isIctDirector = normalized.includes('ict_director') || normalized.includes('dict')
          if (!isIctDirector) {
            this.toast = {
              show: true,
              type: 'error',
              message: 'You do not have ICT Director privileges for this action.'
            }
            setTimeout(() => (this.toast.show = false), 4000)
            return
          }
        } catch (_) {
          // If user info missing, fall through to server-side check which will 403
        }

        // Validate ICT Director fields
        const ictDirectorName = this.form.approvals.directorICT.name || this.currentUser?.name || ''
        // Digital signature required will be enforced by backend

        // Validate ICT Director comments (from role comment editor)
        const roleComment = (this.roleCommentsDraft || '').trim()
        if (!roleComment) {
          this.toast = {
            show: true,
            message: 'Please provide your technical comments/evaluation before approving'
          }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        const ictDirectorDate =
          this.form.approvals.directorICT.date || new Date().toISOString().slice(0, 10)

        const payload = {
          ictDirectorName: ictDirectorName,
          approvedDate: ictDirectorDate,
          comments: roleComment
        }

        // Use the both service form service for approval (consistent with HOD/Divisional)
        const result = await bothServiceFormService.ictDirectorApprove(this.getRequestId, payload)

        if (result.success) {
          // Optimistically update local comments for ICT Director
          this.updateRoleCommentsLocally('ict_director', roleComment)

          this.toast = {
            show: true,
            message: 'Request approved successfully by ICT Director'
          }
          setTimeout(() => {
            this.toast.show = false
            this.goBackToDictRequests()
          }, 2000)
        } else {
          // Handle specific error types
          let errorMessage = 'Failed to approve request'

          if (result.errors && Object.keys(result.errors).length > 0) {
            errorMessage = 'Validation errors: ' + Object.values(result.errors).flat().join(', ')
          } else if (result.error) {
            errorMessage = result.error
          }

          throw new Error(errorMessage)
        }
      },

      goBackToDictRequests() {
        try {
          console.log('Navigating back to ICT Director combined requests list')
          this.$router.push('/dict-dashboard/combined-requests')
        } catch (error) {
          console.error('Error navigating back to ICT Director requests:', error)
          // Fallback navigation
          this.$router.push('/dict-dashboard')
        }
      },

      // Divisional back nav
      goBackToDivisionalRequests() {
        try {
          console.log('Navigating back to Divisional combined requests list')
          this.$router.push('/divisional-dashboard/combined-requests')
        } catch (error) {
          console.error('Error navigating back to Divisional requests:', error)
          this.$router.push('/divisional-dashboard/combined-requests')
        }
      },

      // Head IT specific approval method
      async approveHeadItRequest() {
        // Prevent multiple rapid clicks
        if (this.processing || this.loading) {
          return
        }

        // Digital signature required will be enforced by backend

        try {
          this.processing = true
          this.loading = true

          await this.approveAsHeadIt()
        } catch (error) {
          console.error('Error approving request as Head IT:', error)
          this.toast = {
            show: true,
            message: `Error approving request: ${error.message}`
          }
          setTimeout(() => (this.toast.show = false), 3000)
        } finally {
          this.processing = false
          this.loading = false
        }
      },

      // Head IT specific rejection method
      async rejectHeadItRequest() {
        // Check if signature is required but missing
        if (!this.form.implementation.headIT.signature) {
          this.toast = {
            show: true,
            message: 'Please upload your Head of IT signature before rejecting this request.'
          }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        // Show rejection reason modal
        this.showRejectionModal = true
      },

      async approveAsHeadIt() {
        // Validate Head IT fields
        const headItName = this.form.implementation.headIT.name || this.currentUser?.name || ''
        // Digital signature required will be enforced by backend

        // Auto-populate date if not set
        const headItDate =
          this.form.implementation.headIT.date || new Date().toISOString().slice(0, 10)
        this.form.implementation.headIT.date = headItDate

        const payload = {
          headItName: headItName,
          approvedDate: headItDate,
          comments: this.roleCommentsDraft || this.form.comments || 'Approved by Head of IT'
        }

        // Use the both service form service for Head IT approval
        const result = await bothServiceFormService.headItApprove(this.getRequestId, payload)

        if (result.success) {
          // Optimistically update local comments for Head of IT
          this.updateRoleCommentsLocally(
            'head_it',
            this.roleCommentsDraft || this.form.comments || 'Approved by Head of IT'
          )

          // Show approval success modal with CTA to assign ICT officer
          this.onHeadItApproveSuccess()
        } else {
          // Handle specific error types
          let errorMessage = 'Failed to approve request'

          if (result.errors && Object.keys(result.errors).length > 0) {
            errorMessage = 'Validation errors: ' + Object.values(result.errors).flat().join(', ')
          } else if (result.error) {
            errorMessage = result.error
          }

          throw new Error(errorMessage)
        }
      },

      goBackToHeadItRequests() {
        try {
          console.log('Navigating back to Head IT combined requests list')
          this.$router.push('/head_of_it-dashboard/combined-requests')
        } catch (error) {
          console.error('Error navigating back to Head IT requests:', error)
          // Fallback navigation
          this.$router.push('/head_of_it-dashboard')
        }
      },

      // Get requester's full name for success message
      getRequesterName() {
        if (this.requestData && this.requestData.staff_name) {
          return this.requestData.staff_name
        }
        if (this.form.shared.staffName) {
          return this.form.shared.staffName
        }
        return 'the requester'
      },

      // Navigate to ICT Officer selection page
      navigateToSelectIctOfficer() {
        try {
          console.log('Navigating to ICT Officer selection for request:', this.getRequestId)
          this.$router.push(`/head_of_it-dashboard/select-ict-officer/${this.getRequestId}`)
        } catch (error) {
          console.error('Error navigating to ICT Officer selection:', error)
          // Fallback: show error message and navigate back
          this.toast = {
            show: true,
            message: 'Error navigating to ICT Officer selection. Please try again.'
          }
          setTimeout(() => {
            this.toast.show = false
            this.goBackToHeadItRequests()
          }, 3000)
        }
      },

      // Close success modal and navigate back to requests
      closeSuccessModal() {
        this.showApprovalSuccessCard = false
        this.goBackToHeadItRequests()
      },

      async rejectRequest() {
        // Check if a digital signature is required but missing
        if (this.isSignatureRequiredForApproval) {
          this.toast = {
            show: true,
            message: 'Please digitally sign this request before rejecting.'
          }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        // Show rejection reason modal
        this.showRejectionModal = true
      },

      async confirmRejectRequest() {
        if (!this.rejectionReason?.trim()) {
          this.toast = {
            show: true,
            message: 'Please provide a reason for rejection'
          }
          setTimeout(() => (this.toast.show = false), 3000)
          return
        }

        try {
          this.loading = true
          this.showRejectionModal = false

          const stage = this.currentApprovalStage
          const userRole = (this.getUserRole() || '').toLowerCase()

          console.log('Rejecting request:', this.getRequestId, 'with reason:', this.rejectionReason)

          let result

          if (
            stage === 'hod' &&
            [
              'head_of_department',
              'hod',
              'department_head',
              'head_department',
              'hod_user',
              'head_of_dept'
            ].includes(userRole)
          ) {
            // HOD rejection using the existing API
            result = await combinedAccessService.updateHodApproval(this.getRequestId, {
              status: 'rejected',
              comments: this.rejectionReason
            })
          } else if (stage === 'divisional' && ['divisional_director'].includes(userRole)) {
            // Divisional Director rejection using new API
            const currentUserName = this.currentUser?.name || ''
            result = await bothServiceFormService.divisionalDirectorReject(this.getRequestId, {
              divisionalDirectorName: currentUserName,
              rejectionReason: this.rejectionReason,
              rejectionDate: new Date().toISOString().slice(0, 10)
            })
          } else if (stage === 'ict_director' && ['ict_director', 'dict'].includes(userRole)) {
            // ICT Director rejection using both service form service
            result = await bothServiceFormService.ictDirectorReject(this.getRequestId, {
              ictDirectorName: this.currentUser?.name || '',
              rejectionReason: this.rejectionReason,
              rejectionDate: new Date().toISOString().slice(0, 10)
            })
          } else if (stage === 'head_it' && ['head_it', 'head_of_it'].includes(userRole)) {
            // Head IT rejection using both service form service
            result = await bothServiceFormService.headItReject(this.getRequestId, {
              headItName: this.currentUser?.name || '',
              rejectionReason: this.rejectionReason,
              rejectionDate: new Date().toISOString().slice(0, 10)
            })
          } else {
            throw new Error('You do not have permission to reject at this stage')
          }

          if (result.success) {
            this.toast = {
              show: true,
              message: 'Request rejected successfully'
            }
            setTimeout(() => {
              this.toast.show = false
              // Navigate back to appropriate dashboard based on user role
              if (['ict_director', 'dict'].includes(userRole)) {
                this.goBackToDictRequests()
              } else if (['head_it', 'head_of_it'].includes(userRole)) {
                this.goBackToHeadItRequests()
              } else if (['divisional_director'].includes(userRole)) {
                this.goBackToDivisionalRequests()
              } else {
                this.goBackToRequests()
              }
            }, 2000)
          } else {
            throw new Error(result.error || 'Failed to reject request')
          }
        } catch (error) {
          console.error('Error rejecting request:', error)
          this.toast = {
            show: true,
            message: `Error rejecting request: ${error.message}`
          }
          setTimeout(() => (this.toast.show = false), 3000)
        } finally {
          this.loading = false
          this.rejectionReason = ''
        }
      },

      // Helper: update local requestData comments for immediate UI refresh
      updateRoleCommentsLocally(role, comment) {
        try {
          if (!this.requestData || !comment) return
          const cmt = String(comment).trim()
          if (!cmt) return
          const nowIso = new Date().toISOString()
          switch (role) {
            case 'hod':
              this.requestData.hod_comments = cmt
              this.requestData.approvals = this.requestData.approvals || {}
              this.requestData.approvals.hod = this.requestData.approvals.hod || {}
              this.requestData.approvals.hod.comments = cmt
              this.requestData.hod_approved_at = this.requestData.hod_approved_at || nowIso
              break
            case 'divisional':
              this.requestData.divisional_comments = cmt
              this.requestData.approvals = this.requestData.approvals || {}
              this.requestData.approvals.divisionalDirector =
                this.requestData.approvals.divisionalDirector || {}
              this.requestData.approvals.divisionalDirector.comments = cmt
              this.requestData.divisional_approved_at =
                this.requestData.divisional_approved_at || nowIso
              break
            case 'ict_director':
              this.requestData.ict_director_comments = cmt
              this.requestData.dict_comments = cmt
              this.requestData.approvals = this.requestData.approvals || {}
              this.requestData.approvals.directorICT = this.requestData.approvals.directorICT || {}
              this.requestData.approvals.directorICT.comments = cmt
              this.requestData.ict_director_approved_at =
                this.requestData.ict_director_approved_at || nowIso
              break
            case 'head_it':
              this.requestData.head_it_comments = cmt
              this.requestData.implementation = this.requestData.implementation || {}
              this.requestData.implementation.headIT = this.requestData.implementation.headIT || {}
              this.requestData.implementation.headIT.comments = cmt
              this.requestData.head_it_approved_at = this.requestData.head_it_approved_at || nowIso
              break
            default:
              break
          }
          this.requestData.updated_at = nowIso
          this.$forceUpdate()
        } catch (_) {
          /* no-op */
        }
      },

      // ICT Officer implementation approval method
      async approveIctOfficerImplementation() {
        if (!this.ictOfficerSignaturePreview && !this.form.implementation.ictOfficer.signature) {
          this.toast = {
            show: true,
            message: 'Please upload your signature before approving the implementation.'
          }
          setTimeout(() => (this.toast.show = false), 4000)
          return
        }

        this.processing = true

        try {
          const payload = {
            ict_officer_name: this.currentUser?.name || 'ICT Officer',
            approved_date:
              this.form.implementation.ictOfficer.date || new Date().toISOString().slice(0, 10),
            comments: this.form.comments || '',
            status: 'implemented'
          }

          const result = await bothServiceFormService.ictOfficerApprove(this.getRequestId, payload)

          if (result && result.success) {
            this.toast = {
              show: true,
              message: 'Implementation approved successfully.'
            }
            setTimeout(() => {
              this.toast.show = false
              this.$router.push('/ict-dashboard/access-requests')
            }, 1500)
          } else {
            throw new Error(result?.error || 'Failed to approve implementation')
          }
        } catch (error) {
          console.error('Error approving ICT Officer implementation:', error)
          this.toast = {
            show: true,
            message: `Error approving implementation: ${error.message}`
          }
          setTimeout(() => (this.toast.show = false), 4000)
        } finally {
          this.processing = false
        }
      },

      // Navigate to User Security Access page
      proceedToUserSecurityAccess() {
        this.showIctOfficerConfirmation = false
        this.$router.push({
          name: 'UserSecurityAccess',
          params: {
            id: this.getRequestId
          },
          query: {
            role: 'ict_officer'
          }
        })
      },

      cancelRejectRequest() {
        this.showRejectionModal = false
        this.rejectionReason = ''
      },

      goBackToRequests() {
        try {
          console.log('Navigating back to HOD combined requests list')
          this.$router.push({
            name: 'HODCombinedRequestList',
            path: '/hod-dashboard/combined-requests',
            query: { from: 'approval_done' }
          })
        } catch (error) {
          console.error('Error navigating back to requests:', error)
          // Fallback navigation using path
          this.$router.push('/hod-dashboard/combined-requests?from=approval_done')
        }
      },

      goToRequestDetails() {
        try {
          console.log('Navigating to request details for ID:', this.getRequestId)
          this.$router.push({
            path: '/request-details',
            query: {
              id: this.getRequestId,
              type: 'combined_access'
            }
          })
        } catch (error) {
          console.error('Error navigating to request details:', error)
          // Fallback navigation
          this.$router.push(`/request-details?id=${this.getRequestId}&type=combined_access`)
        }
      },

      // Signature handling methods
      triggerFileUpload() {
        this.$refs.signatureInput.click()
      },

      onSignatureChange(e) {
        const file = e.target.files[0]
        this.form.shared.signature = file || null

        if (!file) {
          this.signaturePreview = ''
          this.signatureFileName = ''
          return
        }

        // Validate file type
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf']
        if (!allowedTypes.includes(file.type)) {
          this.showNotification('Please select a valid file (PNG, JPG, or PDF)')
          this.clearSignature()
          return
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          this.showNotification('File size must be less than 5MB')
          this.clearSignature()
          return
        }

        this.signatureFileName = file.name

        if (file.type.startsWith('image/')) {
          const reader = new FileReader()
          reader.onload = () => {
            this.signaturePreview = reader.result
          }
          reader.readAsDataURL(file)
        } else {
          this.signaturePreview = 'pdf'
        }
      },

      isImage(preview) {
        return typeof preview === 'string' && preview !== 'pdf'
      },

      clearSignature() {
        this.form.shared.signature = null
        this.signaturePreview = ''
        this.signatureFileName = ''
        if (this.$refs.signatureInput) {
          this.$refs.signatureInput.value = ''
        }
      },

      showNotification(message) {
        // Simple notification - you can replace with a proper notification system
        alert(message)
      },

      showSignatureUploadSuccess(roleType) {
        this.toast = {
          show: true,
          message: `${roleType} signature uploaded successfully! You can now approve or reject this request.`
        }
        setTimeout(() => (this.toast.show = false), 4000)
      },

      showToast(message, type = 'success') {
        this.toast = {
          show: true,
          message: message,
          type: type
        }
        setTimeout(() => {
          this.toast.show = false
        }, 4000)
      },

      // Get formatted approval date for any stage
      getApprovalDateFormatted(stage) {
        if (!this.requestData) return 'Date unavailable'

        const dateMap = {
          hod:
            this.requestData.approvals?.hod?.date ||
            this.requestData.approvals?.hod?.approved_at ||
            this.requestData.hod_approved_at,
          divisional:
            this.requestData.approvals?.divisionalDirector?.date ||
            this.requestData.approvals?.divisionalDirector?.approved_at ||
            this.requestData.divisional_approved_at,
          ict_director:
            this.requestData.approvals?.directorICT?.date ||
            this.requestData.approvals?.directorICT?.approved_at ||
            this.requestData.dict_approved_at,
          head_it:
            this.requestData.implementation?.headIT?.date ||
            this.requestData.implementation?.headIT?.approved_at ||
            this.requestData.head_it_approved_at,
          ict_officer:
            this.requestData.implementation?.ictOfficer?.date ||
            this.requestData.implementation?.ictOfficer?.approved_at ||
            this.requestData.ict_officer_approved_at
        }

        let at = dateMap[stage]

        // Fallback: derive from digital signature history for that approver if available
        if (!at) {
          try {
            const approverNameMap = {
              hod: this.requestData.approvals?.hod?.name || this.requestData.hod_name,
              divisional:
                this.requestData.approvals?.divisionalDirector?.name ||
                this.requestData.divisional_director_name,
              ict_director:
                this.requestData.approvals?.directorICT?.name || this.requestData.dict_name,
              head_it:
                this.requestData.implementation?.headIT?.name || this.requestData.head_it_name,
              ict_officer:
                this.requestData.implementation?.ictOfficer?.name ||
                this.requestData.ict_officer_name
            }
            const approver = String(approverNameMap[stage] || '')
              .trim()
              .toLowerCase()
            const getTs = (s) => s?.signed_at || s?.created_at || s?.timestamp
            let entry = (this.signatureHistory || []).find(
              (s) =>
                String(s.user_name || s.name || '')
                  .trim()
                  .toLowerCase() === approver
            )

            // Additional fallback: if viewer is at this stage and has signed, use current user entry
            if (!entry) {
              const viewerIsStage = this.viewerStage() === stage
              const viewerName = (this.currentUser?.name || '').trim().toLowerCase()
              if (viewerIsStage && viewerName) {
                entry = (this.signatureHistory || []).find(
                  (s) =>
                    String(s.user_name || s.name || '')
                      .trim()
                      .toLowerCase() === viewerName
                )
                if (!entry && this.hasUserSigned && this.lastSignedAt) {
                  // Construct a synthetic entry from local state
                  at = this.lastSignedAt
                }
              }
            }
            if (!at && entry) at = getTs(entry)
          } catch (_) {
            /* no-op */
          }
        }

        if (!at) return 'Date unavailable'
        try {
          const date = new Date(at)
          if (isNaN(date.getTime())) return 'Date unavailable'
          return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
          })
        } catch (error) {
          console.warn('Error formatting approval date for', stage, error)
          return 'Date unavailable'
        }
      },

      // Auto-populate HOD date field for Divisional Director view using HOD approval date
      populateDivisionalDirectorDateFromHod() {
        if (!this.requestData) return

        // Only auto-populate when Divisional Director is reviewing
        const userRole = (this.getUserRole() || '').toLowerCase()
        const isDivisionalDirector = ['divisional_director'].includes(userRole)

        if (!isDivisionalDirector || !this.isReviewMode) return

        // Get HOD approval date from various possible sources
        const hodApprovedAt =
          this.requestData.approvals?.hod?.date ||
          this.requestData.approvals?.hod?.approved_at ||
          this.requestData.hod_approved_at ||
          null

        if (!hodApprovedAt) return

        try {
          // Convert HOD approval date to YYYY-MM-DD format for input[type="date"]
          const date = new Date(hodApprovedAt)
          if (isNaN(date.getTime())) return

          const yyyy = date.getFullYear()
          const mm = String(date.getMonth() + 1).padStart(2, '0')
          const dd = String(date.getDate()).padStart(2, '0')
          const formattedDate = `${yyyy}-${mm}-${dd}`

          // Only populate if the HOD date field is currently empty
          if (!this.form.approvals.hod.date) {
            this.form.approvals.hod.date = formattedDate
            console.log(
              '✅ Auto-populated HOD date from HOD approval for Divisional Director view:',
              {
                originalHodDate: hodApprovedAt,
                formattedDate: formattedDate
              }
            )
          }
        } catch (error) {
          console.warn('Error auto-populating HOD date for Divisional Director view:', error)
        }
      },

      // HOD Signature methods
      triggerHodSignatureUpload() {
        this.$refs.hodSignatureInput.click()
      },

      onHodSignatureChange(e) {
        const file = e.target.files[0]
        this.form.approvals.hod.signature = file || null

        if (!file) {
          this.hodSignaturePreview = ''
          this.hodSignatureFileName = ''
          return
        }

        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf']
        if (!allowedTypes.includes(file.type)) {
          this.showNotification('Please select a valid file (PNG, JPG, or PDF)')
          this.clearHodSignature()
          return
        }

        if (file.size > 5 * 1024 * 1024) {
          this.showNotification('File size must be less than 5MB')
          this.clearHodSignature()
          return
        }

        this.hodSignatureFileName = file.name
        // Auto-capture approval date on signature selection
        const today = new Date()
        const yyyy = today.getFullYear()
        const mm = String(today.getMonth() + 1).padStart(2, '0')
        const dd = String(today.getDate()).padStart(2, '0')
        this.form.approvals.hod.date = `${yyyy}-${mm}-${dd}`

        if (file.type.startsWith('image/')) {
          const reader = new FileReader()
          reader.onload = () => {
            this.hodSignaturePreview = reader.result
            this.showSignatureUploadSuccess('HOD')
          }
          reader.readAsDataURL(file)
        } else {
          this.hodSignaturePreview = 'pdf'
          this.showSignatureUploadSuccess('HOD')
        }
      },

      clearHodSignature() {
        this.form.approvals.hod.signature = null
        this.hodSignaturePreview = ''
        this.hodSignatureFileName = ''
        if (this.$refs.hodSignatureInput) {
          this.$refs.hodSignatureInput.value = ''
        }
      },

      // Divisional Director Signature methods
      triggerDivDirectorSignatureUpload() {
        this.$refs.divDirectorSignatureInput.click()
      },

      onDivDirectorSignatureChange(e) {
        const file = e.target.files[0]
        this.form.approvals.divisionalDirector.signature = file || null

        if (!file) {
          this.divDirectorSignaturePreview = ''
          this.divDirectorSignatureFileName = ''
          return
        }

        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf']
        if (!allowedTypes.includes(file.type)) {
          this.showNotification('Please select a valid file (PNG, JPG, or PDF)')
          this.clearDivDirectorSignature()
          return
        }

        if (file.size > 5 * 1024 * 1024) {
          this.showNotification('File size must be less than 5MB')
          this.clearDivDirectorSignature()
          return
        }

        this.divDirectorSignatureFileName = file.name
        // Auto-capture approval date on signature selection
        this.form.approvals.divisionalDirector.date = new Date().toISOString().slice(0, 10)

        if (file.type.startsWith('image/')) {
          const reader = new FileReader()
          reader.onload = () => {
            this.divDirectorSignaturePreview = reader.result
            this.showSignatureUploadSuccess('Divisional Director')
          }
          reader.readAsDataURL(file)
        } else {
          this.divDirectorSignaturePreview = 'pdf'
          this.showSignatureUploadSuccess('Divisional Director')
        }
      },

      clearDivDirectorSignature() {
        this.form.approvals.divisionalDirector.signature = null
        this.divDirectorSignaturePreview = ''
        this.divDirectorSignatureFileName = ''
        if (this.$refs.divDirectorSignatureInput) {
          this.$refs.divDirectorSignatureInput.value = ''
        }
      },

      // Director ICT Signature methods
      triggerDirectorICTSignatureUpload() {
        this.$refs.directorICTSignatureInput.click()
      },

      onDirectorICTSignatureChange(e) {
        const file = e.target.files[0]
        this.form.approvals.directorICT.signature = file || null

        if (!file) {
          this.directorICTSignaturePreview = ''
          this.directorICTSignatureFileName = ''
          return
        }

        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf']
        if (!allowedTypes.includes(file.type)) {
          this.showNotification('Please select a valid file (PNG, JPG, or PDF)')
          this.clearDirectorICTSignature()
          return
        }

        if (file.size > 5 * 1024 * 1024) {
          this.showNotification('File size must be less than 5MB')
          this.clearDirectorICTSignature()
          return
        }

        this.directorICTSignatureFileName = file.name
        // Auto-capture approval date on signature selection
        this.form.approvals.directorICT.date = new Date().toISOString().slice(0, 10)

        if (file.type.startsWith('image/')) {
          const reader = new FileReader()
          reader.onload = () => {
            this.directorICTSignaturePreview = reader.result
          }
          reader.readAsDataURL(file)
        } else {
          this.directorICTSignaturePreview = 'pdf'
        }
      },

      clearDirectorICTSignature() {
        this.form.approvals.directorICT.signature = null
        this.directorICTSignaturePreview = ''
        this.directorICTSignatureFileName = ''
        if (this.$refs.directorICTSignatureInput) {
          this.$refs.directorICTSignatureInput.value = ''
        }
      },

      // Head IT Signature methods
      triggerHeadITSignatureUpload() {
        this.$refs.headITSignatureInput.click()
      },

      onHeadITSignatureChange(e) {
        const file = e.target.files[0]
        this.form.implementation.headIT.signature = file || null

        if (!file) {
          this.headITSignaturePreview = ''
          this.headITSignatureFileName = ''
          return
        }

        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf']
        if (!allowedTypes.includes(file.type)) {
          this.showNotification('Please select a valid file (PNG, JPG, or PDF)')
          this.clearHeadITSignature()
          return
        }

        if (file.size > 5 * 1024 * 1024) {
          this.showNotification('File size must be less than 5MB')
          this.clearHeadITSignature()
          return
        }

        this.headITSignatureFileName = file.name
        // Auto-capture approval date on signature selection
        this.form.implementation.headIT.date = new Date().toISOString().slice(0, 10)

        if (file.type.startsWith('image/')) {
          const reader = new FileReader()
          reader.onload = () => {
            this.headITSignaturePreview = reader.result
            this.showSignatureUploadSuccess('Head of IT')
          }
          reader.readAsDataURL(file)
        } else {
          this.headITSignaturePreview = 'pdf'
          this.showSignatureUploadSuccess('Head of IT')
        }
      },

      clearHeadITSignature() {
        this.form.implementation.headIT.signature = null
        this.headITSignaturePreview = ''
        this.headITSignatureFileName = ''
        if (this.$refs.headITSignatureInput) {
          this.$refs.headITSignatureInput.value = ''
        }
      },

      // ICT Officer Signature methods
      triggerIctOfficerSignatureUpload() {
        this.$refs.ictOfficerSignatureInput.click()
      },

      onIctOfficerSignatureChange(e) {
        const file = e.target.files[0]
        this.form.implementation.ictOfficer.signature = file || null

        if (!file) {
          this.ictOfficerSignaturePreview = ''
          this.ictOfficerSignatureFileName = ''
          return
        }

        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf']
        if (!allowedTypes.includes(file.type)) {
          this.showNotification('Please select a valid file (PNG, JPG, or PDF)')
          this.clearIctOfficerSignature()
          return
        }

        if (file.size > 5 * 1024 * 1024) {
          this.showNotification('File size must be less than 5MB')
          this.clearIctOfficerSignature()
          return
        }

        this.ictOfficerSignatureFileName = file.name
        // Auto-capture date on signature selection
        this.form.implementation.ictOfficer.date = new Date().toISOString().slice(0, 10)

        if (file.type.startsWith('image/')) {
          const reader = new FileReader()
          reader.onload = () => {
            this.ictOfficerSignaturePreview = reader.result
          }
          reader.readAsDataURL(file)
        } else {
          this.ictOfficerSignaturePreview = 'pdf'
        }
      },

      clearIctOfficerSignature() {
        this.form.implementation.ictOfficer.signature = null
        this.ictOfficerSignaturePreview = ''
        this.ictOfficerSignatureFileName = ''
        if (this.$refs.ictOfficerSignatureInput) {
          this.$refs.ictOfficerSignatureInput.value = ''
        }
      },
      // Manual debug method to trigger auto-population (for testing)
      debugAutoPopulation() {
        console.log('=== DEBUG AUTO-POPULATION =====')
        console.log('Current user:', this.currentUser)
        console.log('Is review mode:', this.isReviewMode)
        console.log('Current approval stage:', this.currentApprovalStage)
        console.log('HOD editable:', this.isHodApprovalEditable)
        console.log('HOD current name:', this.form.approvals.hod.name)
        console.log('Request data status:', this.requestData?.status)

        console.log('\n=== TRIGGERING ROLE-BASED AUTO-POPULATION ===')

        // Use role-based population for debugging
        if (this.currentUser?.name) {
          console.log(
            'User role from database:',
            this.currentUser.role || this.currentUser.user_role
          )
          this.populateBasedOnUserRole(this.currentUser)
        } else {
          console.error('No current user data available!')
        }

        console.log('=== DEBUG COMPLETE ===')
      },

      // Helper to format dates for date inputs (YYYY-MM-DD)
      formatDateForInput(dateValue) {
        if (!dateValue) return ''
        try {
          const date = new Date(dateValue)
          if (isNaN(date.getTime())) return ''
          const year = date.getFullYear()
          const month = String(date.getMonth() + 1).padStart(2, '0')
          const day = String(date.getDate()).padStart(2, '0')
          return `${year}-${month}-${day}`
        } catch (error) {
          console.warn('Error formatting date for input:', dateValue, error)
          return ''
        }
      },

      // Helper to format dates for comment display
      formatCommentDate(dateValue) {
        if (!dateValue) return 'Date not available'
        try {
          const date = new Date(dateValue)
          if (isNaN(date.getTime())) {
            // Try to parse it as a different format if the first attempt failed
            const parsedDate = Date.parse(dateValue)
            if (isNaN(parsedDate)) return 'Invalid date format'
            return new Date(parsedDate).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            })
          }
          return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          })
        } catch (error) {
          console.warn('Error formatting comment date:', dateValue, error)
          return 'Date format error'
        }
      },

      // Trigger Head IT name population if the current user is Head IT
      triggerHeadItNamePopulationIfNeeded() {
        if (!this.currentUser || !this.currentUser.name || !this.isReviewMode) {
          return
        }

        const userRole = (this.getUserRole() || '').toLowerCase()
        const isHeadIt = ['head_it', 'head_of_it', 'ict_head', 'it_head'].includes(userRole)
        const isHeadItStage = this.currentApprovalStage === 'head_it'
        const isHeadItEditable = this.isHeadItApprovalEditable

        console.log('🔍 Head IT name population check:', {
          userRole,
          isHeadIt,
          isHeadItStage,
          isHeadItEditable,
          currentApprovalStage: this.currentApprovalStage,
          currentHeadItName: this.form.implementation.headIT.name,
          userName: this.currentUser.name
        })

        if (isHeadIt && isHeadItStage && isHeadItEditable) {
          console.log('✅ All conditions met for Head IT name population')

          // Only populate if the field is currently empty
          if (!this.form.implementation.headIT.name) {
            console.log('🎯 Populating Head IT name field with:', this.currentUser.name)
            this.populateApproverName('head_it')
          } else {
            console.log('ℹ️ Head IT name already populated:', this.form.implementation.headIT.name)
          }
        } else {
          console.log('⚠️ Conditions not met for Head IT name population')
        }
      },

      // Debug method to check Head IT status
      debugHeadItStatus() {
        console.log('\n=== HEAD IT DEBUG STATUS ===')
        console.log('User Info:', {
          currentUser: this.currentUser,
          userName: this.currentUser?.name,
          userRole: this.getUserRole(),
          allRoles: this.currentUser?.roles
        })
        console.log('Request Info:', {
          requestId: this.getRequestId,
          requestStatus: this.requestData?.status,
          currentApprovalStage: this.currentApprovalStage
        })
        console.log('Form State:', {
          headItName: this.form.implementation.headIT.name,
          headItDate: this.form.implementation.headIT.date,
          headItSignature: this.form.implementation.headIT.signature,
          hasSignaturePreview: !!this.headITSignaturePreview
        })
        console.log('Computed Properties:', {
          isReviewMode: this.isReviewMode,
          isHeadItApprovalEditable: this.isHeadItApprovalEditable,
          currentApprovalStage: this.currentApprovalStage
        })
        console.log('Button Conditions:', {
          condition1_isReviewMode: this.isReviewMode,
          condition2_isHeadItApprovalEditable: this.isHeadItApprovalEditable,
          condition3_hasHeadItSignature: !!this.form.implementation.headIT.signature,
          allConditionsMet:
            this.isReviewMode &&
            this.isHeadItApprovalEditable &&
            this.form.implementation.headIT.signature
        })
        console.log('=== END HEAD IT DEBUG ===\n')
      },

      // Determine viewer stage and rank helpers
      viewerStage() {
        const userRole = (this.getUserRole() || '').toLowerCase()
        const roleToStageMap = {
          head_of_department: 'hod',
          hod: 'hod',
          head_department: 'hod',
          divisional_director: 'divisional',
          director_divisional: 'divisional',
          ict_director: 'ict_director',
          director_ict: 'ict_director',
          head_it: 'head_it',
          head_of_it: 'head_it',
          ict_head: 'head_it',
          it_head: 'head_it',
          ict_officer: 'ict_officer',
          officer_ict: 'ict_officer'
        }
        const stage = roleToStageMap[userRole] || ''
        console.log('📍 viewerStage debug:', {
          userRole,
          mappedStage: stage,
          availableRoles: Object.keys(roleToStageMap)
        })
        return stage
      },
      viewerRank() {
        return this.rankForStage(this.viewerStage())
      },
      rankForStage(stage) {
        const ranks = { hod: 1, divisional: 2, ict_director: 3, head_it: 4, ict_officer: 5 }
        return ranks[stage] || 0
      },
      viewerAfter(stage) {
        const targetRank = this.rankForStage(stage)
        const myRank = this.viewerRank()
        // Viewers strictly AFTER the target stage should see status indicators
        const result = targetRank > 0 && myRank > targetRank
        console.log(`🔍 viewerAfter(${stage}):`, {
          targetRank,
          myRank,
          viewerStage: this.viewerStage(),
          result,
          note: 'Using strict > comparison so same-stage approvers see upload controls instead of read-only status'
        })
        return result
      },

      // Get current authenticated user
      async getCurrentUser() {
        try {
          console.log('🔍 Attempting to fetch current user...')
          const response = await authService.getCurrentUser()
          console.log('📨 Raw API response:', response)

          if (response && response.success) {
            console.log('✅ API call successful, response.data:', response.data)

            // Handle different possible response structures
            let userData = null

            if (response.data && response.data.data && response.data.data.name) {
              // Case: {success: true, data: {data: {name: 'John', ...}}}
              userData = response.data.data
              console.log('👤 Current user loaded from response.data.data:', userData)
            } else if (response.data && response.data.user && response.data.user.name) {
              // Case: {success: true, data: {user: {name: 'John', ...}}}
              userData = response.data.user
              console.log('👤 Current user loaded from response.data.user:', userData)
            } else if (response.data && response.data.name) {
              // Case: {success: true, data: {name: 'John', ...}}
              userData = response.data
              console.log('👤 Current user loaded from response.data directly:', userData)
            } else {
              console.error(
                '❌ User data structure not recognized. Full response:',
                JSON.stringify(response, null, 2)
              )
              console.error('❌ response.data content:', response.data)
            }

            if (userData && userData.name) {
              this.currentUser = userData
              console.log('📋 User name from database:', userData.name)
              console.log('📋 User ID:', userData.id)
              console.log('📋 User email:', userData.email)
              console.log('📋 User role:', userData.role || userData.user_role)
            } else {
              console.error('❌ No valid user data found:', userData)
            }
          } else {
            console.warn('❌ Failed to get current user:', response?.message || 'Unknown error')
            console.log('❌ Response status:', response?.status)
          }
        } catch (error) {
          console.error('💥 Error fetching current user:', error)
          console.error('💥 Error details:', {
            message: error.message,
            response: error.response,
            status: error.response?.status,
            data: error.response?.data
          })
        }
      },

      // Fallback method to get user from localStorage
      tryGetUserFromLocalStorage() {
        try {
          const storedUser = localStorage.getItem('user_data')
          if (storedUser) {
            const userData = JSON.parse(storedUser)
            console.log('💾 Found user in localStorage:', userData)

            if (userData && userData.name) {
              this.currentUser = userData
              console.log('✅ Using localStorage user data:', this.currentUser.name)
            } else if (userData && userData.user && userData.user.name) {
              this.currentUser = userData.user
              console.log('✅ Using localStorage user.user data:', this.currentUser.name)
            }
          } else {
            console.log('❌ No user_data found in localStorage')
          }
        } catch (error) {
          console.error('❌ Error parsing localStorage user_data:', error)
        }
      },

      // Fallback method to get user from Pinia store
      tryGetUserFromStore() {
        try {
          // Use the injected auth store
          const storeUser = this.authStore.user || this.authStore.currentUser
          console.log('🏪 Checking Pinia store for user:', {
            hasAuthStore: !!this.authStore,
            storeUser: storeUser,
            storeUserName: storeUser?.name,
            storeProperties: this.authStore ? Object.keys(this.authStore) : []
          })

          if (storeUser && storeUser.name) {
            this.currentUser = storeUser
            console.log('✅ Using Pinia store user data:', this.currentUser.name)
            console.log('✅ User role from store:', storeUser.role || storeUser.user_role)
          } else {
            console.log('❌ No user found in Pinia store')
          }
        } catch (error) {
          console.error('❌ Error accessing Pinia store:', error)
          console.log('❌ Pinia store not available, skipping store access')
        }
      },

      // Role-based auto-population - only populate field matching user's role
      populateBasedOnUserRole(user) {
        if (!user || !user.name) {
          console.log('❌ No valid user data for role-based population')
          return
        }

        const userRole = user.role || user.user_role || ''
        const userName = user.name

        console.log(`🎯 Role-based population for role: "${userRole}" with name: "${userName}"`)

        // First, clear ALL approval name fields to ensure clean state
        console.log('🧺 Clearing all approval name fields before role-based population')
        this.form.approvals.hod.name = ''
        this.form.approvals.divisionalDirector.name = ''
        this.form.approvals.directorICT.name = ''
        this.form.implementation.headIT.name = ''
        this.form.implementation.ictOfficer.name = ''
        console.log('✅ All fields cleared')

        // Map roles to approval stages
        const roleToStageMap = {
          head_of_department: 'hod',
          hod: 'hod',
          head_department: 'hod',
          divisional_director: 'divisional',
          director_divisional: 'divisional',
          ict_director: 'ict_director',
          director_ict: 'ict_director',
          head_it: 'head_it',
          head_of_it: 'head_it',
          ict_officer: 'ict_officer',
          officer_ict: 'ict_officer'
        }

        // Normalize role string for matching
        const normalizedRole = userRole.toLowerCase().replace(/[\s_-]+/g, '_')
        const mappedStage = roleToStageMap[normalizedRole]

        if (mappedStage) {
          console.log(`\u2705 Found role mapping: "${userRole}" \u2192 "${mappedStage}"`)

          // Check if the mapped stage is currently editable (active)
          const isStageEditable = this.isApprovalStageEditable(mappedStage)
          console.log(`\ud83d\udd0d Is ${mappedStage} stage editable?`, isStageEditable)

          if (isStageEditable) {
            console.log(
              `\ud83d\udd04 About to populate ONLY the ${mappedStage} field with: ${userName}`
            )
            this.populateApproverName(mappedStage)
          } else {
            console.log(
              `\u26a0\ufe0f Skipping ${mappedStage} field - not currently active/editable`
            )
          }

          // Verify the population worked correctly
          console.log('\ud83d\udd0d Post-population verification:')
          console.log('HOD name:', this.form.approvals.hod.name)
          console.log('Divisional Director name:', this.form.approvals.divisionalDirector.name)
          console.log('ICT Director name:', this.form.approvals.directorICT.name)
          console.log('Head IT name:', this.form.implementation.headIT.name)
          console.log('ICT Officer name:', this.form.implementation.ictOfficer.name)
        } else {
          console.log(
            `⚠️ No role mapping found for: "${userRole}". Available mappings:`,
            Object.keys(roleToStageMap)
          )

          // Fallback: if user is in review mode and has edit permissions for a specific stage
          if (this.isReviewMode) {
            if (this.isHodApprovalEditable) {
              console.log('📋 Fallback: Using HOD field based on edit permissions')
              this.populateApproverName('hod')
            } else if (this.isDivisionalApprovalEditable) {
              console.log('📋 Fallback: Using Divisional field based on edit permissions')
              this.populateApproverName('divisional')
            } else if (this.isIctDirectorApprovalEditable) {
              console.log('📋 Fallback: Using ICT Director field based on edit permissions')
              this.populateApproverName('ict_director')
            } else if (this.isHeadItApprovalEditable) {
              console.log('📋 Fallback: Using Head IT field based on edit permissions')
              this.populateApproverName('head_it')
            } else if (this.isIctOfficerApprovalEditable) {
              console.log('📋 Fallback: Using ICT Officer field based on edit permissions')
              this.populateApproverName('ict_officer')
            }
          }
        }
      },

      // Helper method to check if a specific approval stage is currently editable
      isApprovalStageEditable(stage) {
        const editableMap = {
          hod: this.isHodApprovalEditable,
          divisional: this.isDivisionalApprovalEditable,
          ict_director: this.isIctDirectorApprovalEditable,
          head_it: this.isHeadItApprovalEditable,
          ict_officer: this.isIctOfficerApprovalEditable
        }

        return editableMap[stage] || false
      },

      // Auto-populate approver name based on current user and approval stage
      populateApproverName(stage) {
        if (!this.currentUser || !this.currentUser.name) {
          console.log('No current user data available for auto-population', {
            hasCurrentUser: !!this.currentUser,
            currentUser: this.currentUser,
            userName: this.currentUser?.name
          })
          return
        }

        const userName = this.currentUser.name
        console.log(`Auto-populating ${stage} approver name with user from database:`, {
          stage,
          userName,
          userId: this.currentUser.id,
          userEmail: this.currentUser.email,
          userPfNumber: this.currentUser.pf_number
        })

        // Always populate the appropriate name field with authenticated user's name
        switch (stage) {
          case 'hod':
            this.form.approvals.hod.name = userName
            console.log(`✅ HOD name populated: ${userName}`)
            break
          case 'divisional':
            this.form.approvals.divisionalDirector.name = userName
            console.log(`✅ Divisional Director name populated: ${userName}`)
            break
          case 'ict_director':
            this.form.approvals.directorICT.name = userName
            // Auto-populate current date if not already set
            if (!this.form.approvals.directorICT.date) {
              this.form.approvals.directorICT.date = new Date().toISOString().slice(0, 10)
            }
            console.log(
              `✅ ICT Director name and date populated: ${userName}, ${this.form.approvals.directorICT.date}`
            )
            break
          case 'head_it':
            this.form.implementation.headIT.name = userName
            // Auto-populate current date if not already set
            if (!this.form.implementation.headIT.date) {
              this.form.implementation.headIT.date = new Date().toISOString().slice(0, 10)
            }
            console.log(
              `✅ Head of IT name and date populated: ${userName}, ${this.form.implementation.headIT.date}`
            )
            break
          case 'ict_officer':
            this.form.implementation.ictOfficer.name = userName
            console.log(`✅ ICT Officer name populated: ${userName}`)
            break
          default:
            console.warn(`Unknown approval stage: ${stage}`)
        }
      },
      // Injected: call after successful Head of IT approval
      onHeadItApproveSuccess() {
        this.showHeadItApproveSuccessModal = true
      },
      redirectToAssignIctOfficer() {
        this.showHeadItApproveSuccessModal = false
        const id = this.getRequestId || this.requestData?.id || this.$route.params.id
        if (id) this.$router.push(`/head_of_it-dashboard/select-ict-officer/${id}`)
      },

      getLoadingTitle() {
        if (this.isReviewMode) {
          return 'Loading Request Details'
        }
        return 'Loading Access Request'
      },

      getLoadingSubtitle() {
        if (this.isReviewMode) {
          return 'Retrieving request data for review...'
        }
        return 'Please wait...'
      }
    }
  }
</script>

<style scoped>
  /* Role comment textarea: lock resizing */
  .role-comment-textarea {
    resize: none;
  }

  /* Medical Background Animations */
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

  .focus\:border-primary:focus {
    border-color: #1e40af;
  }

  /* Custom checkbox and radio styling */
  input[type='checkbox']:checked,
  input[type='radio']:checked {
    background-color: #1e40af;
    border-color: #1e40af;
  }

  input[type='checkbox']:focus,
  input[type='radio']:focus {
    box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
  }

  /* Smooth transitions */
  .transition-all {
    transition: all 0.3s ease;
  }

  /* Glass morphism effect */
  .backdrop-blur-sm {
    backdrop-filter: blur(8px);
  }

  /* Enhanced form sections */
  .border-l-2 {
    position: relative;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
  }

  .border-l-2:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
  }

  /* Full width responsive container */
  .label {
    @apply block text-base font-medium text-gray-700 mb-1;
  }
  .input {
    @apply w-full rounded-lg border border-gray-300 px-4 py-3 text-base focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary;
  }
  .btn-primary {
    @apply inline-flex items-center px-6 py-3 rounded-lg bg-primary text-white text-base font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition shadow-md hover:shadow-lg;
  }
  .btn-secondary {
    @apply inline-flex items-center px-6 py-3 rounded-lg bg-gray-100 text-gray-800 text-base font-semibold hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition shadow-md hover:shadow-lg;
  }
  .btn-danger {
    @apply inline-flex items-center px-4 py-2 rounded-md bg-red-600 text-white text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition;
  }
  .btn-sm {
    @apply px-2 py-1 text-xs;
  }
  .card {
    @apply bg-white rounded-lg shadow-md border border-gray-200 p-4 mb-3;
  }
  .error {
    @apply text-xs text-red-600 mt-1;
  }
  .tab {
    @apply inline-flex items-center px-3 py-2 rounded-md bg-white border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition;
  }
  .tab-active {
    @apply bg-blue-50 border-blue-300 text-blue-800;
  }
  .fade-enter-active,
  .fade-leave-active {
    transition:
      opacity 0.2s,
      transform 0.2s;
  }
  .fade-enter-from,
  .fade-leave-to {
    opacity: 0;
    transform: translateY(4px);
  }
  .focus\:ring-primary {
    --tw-ring-color: #1e40af;
  }
  .option-tile {
    @apply flex items-center gap-2 px-2 py-2 rounded-md border border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300 text-left text-sm;
  }
  .option-tile-active {
    @apply border-blue-300 bg-blue-50;
  }

  /* Print styles */
  @media print {
    .min-h-screen {
      min-height: auto;
    }

    button {
      display: none;
    }

    .shadow-xl {
      box-shadow: none;
    }

    .bg-gradient-to-br {
      background: white;
    }
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .grid-cols-4 {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid-cols-3 {
      grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .grid-cols-2 {
      grid-template-columns: repeat(1, minmax(0, 1fr));
    }
  }

  @media (max-width: 640px) {
    .lg\:grid-cols-4 {
      grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .md\:grid-cols-3 {
      grid-template-columns: repeat(1, minmax(0, 1fr));
    }
  }

  /* Radio button specific styles */
  input[type='radio'] {
    appearance: auto;
    -webkit-appearance: auto;
    -moz-appearance: radio;
    opacity: 1 !important;
    position: relative !important;
    z-index: 10;
    pointer-events: auto !important;
  }

  /* Ensure radio buttons are always visible and clickable */
  input[type='radio']:checked {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
  }

  /* Radio button labels */
  label.cursor-pointer {
    cursor: pointer !important;
    user-select: none;
  }

  label.cursor-pointer:hover {
    background-color: rgba(59, 130, 246, 0.1) !important;
  }

  /* Active state for radio button containers */
  label.cursor-pointer.bg-blue-500\/10 {
    background-color: rgba(59, 130, 246, 0.15) !important;
    border-color: rgba(59, 130, 246, 0.5) !important;
  }

  /* Improve card spacing on smaller screens */
  @media (max-width: 768px) {
    .card {
      padding: 0.75rem;
    }
  }

  /* Ensure full width utilization */
  .max-w-full {
    max-width: 100%;
  }

  .max-w-8xl {
    max-width: 88rem; /* 1408px */
  }

  /* Personal Information Input Styling */
  .personal-info-input.font-bold {
    font-weight: 700 !important;
    letter-spacing: 0.025em;
    text-shadow: 0 0 1px rgba(255, 255, 255, 0.3);
  }

  /* Enhanced bold styling for populated personal info fields */
  .personal-info-input:not(:placeholder-shown) {
    font-weight: 600;
  }

  .personal-info-input.font-bold:not(:placeholder-shown) {
    font-weight: 800 !important;
    color: #fde047 !important; /* Yellow-300 for better contrast on dark background */
    background-color: rgba(
      59,
      130,
      246,
      0.25
    ) !important; /* Slightly more opaque blue background */
    border-color: rgba(251, 191, 36, 0.5) !important; /* Amber border for emphasis */
  }

  /* Custom scrollbar for Previous Comments section */
  .custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(251, 191, 36, 0.6) rgba(59, 130, 246, 0.2);
  }

  .custom-scrollbar::-webkit-scrollbar {
    width: 8px;
  }

  .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(59, 130, 246, 0.1);
    border-radius: 10px;
  }

  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, rgba(251, 191, 36, 0.7), rgba(245, 158, 11, 0.8));
    border-radius: 10px;
    border: 2px solid rgba(59, 130, 246, 0.2);
  }

  .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, rgba(251, 191, 36, 0.9), rgba(245, 158, 11, 1));
  }

  /* Timeline connector animation */
  .timeline-connector {
    position: relative;
    overflow: hidden;
  }

  .timeline-connector::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    width: 4px;
    height: 100%;
    background: linear-gradient(
      to bottom,
      transparent 0%,
      rgba(251, 191, 36, 0.6) 20%,
      rgba(251, 191, 36, 0.8) 50%,
      rgba(251, 191, 36, 0.6) 80%,
      transparent 100%
    );
    transform: translateX(-50%);
    animation: timeline-glow 2s ease-in-out infinite alternate;
  }

  @keyframes timeline-glow {
    0% {
      opacity: 0.6;
      box-shadow: 0 0 5px rgba(251, 191, 36, 0.3);
    }
    100% {
      opacity: 1;
      box-shadow: 0 0 15px rgba(251, 191, 36, 0.5);
    }
  }

  /* Previous Comments section animations */
  .previous-comments-enter-active,
  .previous-comments-leave-active {
    transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
  }

  .previous-comments-enter-from {
    opacity: 0;
    transform: translateY(-20px);
  }

  .previous-comments-leave-to {
    opacity: 0;
    transform: translateY(-20px);
  }

  /* Comment item hover effects */
  .comment-item:hover .comment-icon {
    transform: scale(1.1) rotate(5deg);
  }

  .comment-item:hover .comment-content {
    background-color: rgba(59, 130, 246, 0.05);
  }

  /* Responsive grid adjustments */
  @media (max-width: 1024px) {
    .lg\:col-span-3 {
      grid-column: span 1;
    }
  }
</style>
