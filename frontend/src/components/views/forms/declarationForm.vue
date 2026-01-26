<template>
  <div>
    <!-- Simple Loading Banner Component -->
    <SimpleLoadingBanner
      ref="loadingBanner"
      v-if="isLoadingProfile"
      :show="isLoadingProfile"
      :auto-start="true"
      @loading-complete="onLoadingComplete"
    />

    <div
      v-show="!isLoadingProfile"
      class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-teal-900 py-2 px-4 relative overflow-hidden"
      role="main"
      aria-label="Staff Declaration Form for Muhimbili National Hospital"
      :aria-busy="isLoadingProfile"
    >
      <!-- Medical Background Pattern -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <!-- Medical Cross Pattern -->
        <div class="absolute inset-0 opacity-5">
          <div class="grid grid-cols-12 gap-8 h-full transform rotate-45">
            <div
              v-for="i in backgroundDots"
              :key="i.id"
              class="bg-white rounded-full w-2 h-2 animate-pulse"
              :style="{ animationDelay: i.delay }"
            ></div>
          </div>
        </div>
        <!-- Floating medical icons -->
        <div class="absolute inset-0">
          <div
            v-for="icon in floatingIcons"
            :key="icon.id"
            class="absolute text-white opacity-10 animate-float"
            :style="{
              left: icon.left,
              top: icon.top,
              animationDelay: icon.animationDelay,
              animationDuration: icon.animationDuration,
              fontSize: icon.fontSize
            }"
          >
            <i :class="icon.iconClass"></i>
          </div>
        </div>
      </div>

      <div
        class="max-w-none mx-auto relative z-10 px-4 sm:px-6 lg:px-8 xl:px-12 2xl:px-16 3xl:px-20"
      >
        <!-- Header Section -->
        <div class="medical-glass-card rounded-t-3xl p-3 mb-0 border-b border-blue-300/30">
          <div class="flex justify-between items-center">
            <!-- Left Logo -->
            <div class="w-16 h-16 mr-3 transform hover:scale-110 transition-transform duration-300">
              <div
                class="w-full h-full bg-gradient-to-br from-blue-500/20 to-teal-500/20 rounded-2xl backdrop-blur-sm border-2 border-blue-300/40 flex items-center justify-center shadow-2xl hover:shadow-blue-500/25"
              >
                <img
                  src="/assets/images/ngao2.png"
                  alt="National Shield"
                  class="max-w-12 max-h-12 object-contain"
                />
              </div>
            </div>

            <!-- Center Content -->
            <div class="text-center flex-1">
              <h1
                class="text-2xl font-bold text-white mb-1 tracking-wide drop-shadow-lg animate-fade-in"
              >
                MUHIMBILI NATIONAL HOSPITAL
              </h1>
              <div class="relative inline-block mb-1">
                <div
                  class="bg-gradient-to-r from-red-600 to-red-700 text-white px-4 py-1 rounded-full text-base font-bold shadow-2xl transform hover:scale-105 transition-all duration-300 border-2 border-red-400/60"
                >
                  <span class="relative z-10 flex items-center gap-1">
                    <i class="fas fa-shield-alt text-red-200 text-xs"></i>
                    RESTRICTED
                  </span>
                  <div
                    class="absolute inset-0 bg-gradient-to-r from-red-700 to-red-800 rounded-full opacity-0 hover:opacity-100 transition-opacity duration-300"
                  ></div>
                </div>
              </div>
              <h2
                class="text-2xl font-bold text-blue-100 tracking-wide drop-shadow-md animate-fade-in-delay"
              >
                DECLARATIONS BY STAFF
              </h2>
            </div>

            <!-- Right Logo -->
            <div class="w-16 h-16 ml-3 transform hover:scale-110 transition-transform duration-300">
              <div
                class="w-full h-full bg-gradient-to-br from-teal-500/20 to-blue-500/20 rounded-2xl backdrop-blur-sm border-2 border-teal-300/40 flex items-center justify-center shadow-2xl hover:shadow-teal-500/25"
              >
                <img
                  src="/assets/images/logo2.png"
                  alt="Muhimbili Logo"
                  class="max-w-12 max-h-12 object-contain"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Main Form -->
        <div class="medical-glass-card rounded-b-3xl overflow-hidden">
          <form
            @submit.prevent="submitDeclaration"
            class="p-3 space-y-3"
            role="form"
            aria-labelledby="form-title"
            novalidate
          >
            <!-- Declaration Text -->
            <div
              class="medical-card bg-gradient-to-r from-blue-600/25 to-blue-700/25 border-2 border-blue-400/40 p-2 rounded-xl backdrop-blur-sm hover:shadow-xl hover:shadow-blue-500/20 transition-all duration-300 group"
            >
              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <div
                    class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 border border-blue-300/50"
                  >
                    <i class="fas fa-info-circle text-white text-lg"></i>
                  </div>
                </div>
                <div class="flex-1">
                  <h3 id="form-title" class="text-base font-bold text-white mb-1 flex items-center">
                    <i class="fas fa-clipboard-list mr-2 text-blue-300" aria-hidden="true"></i>
                    Declaration Information
                  </h3>
                  <div class="text-blue-100 leading-relaxed space-y-1">
                    <p class="text-base font-medium">
                      These declarations have been designed to certify that users acknowledge that
                      they are aware of Muhimbili National Hospital Acceptable Information and
                      Communication Technology use policy and agree to abide by their terms.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Personal Information Section -->
            <div
              class="medical-card bg-gradient-to-r from-blue-600/25 to-blue-700/25 border-2 border-blue-400/40 p-2 rounded-xl backdrop-blur-sm hover:shadow-xl hover:shadow-blue-500/20 transition-all duration-300 group"
            >
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-3">
                  <div
                    class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 border border-blue-300/50"
                  >
                    <i class="fas fa-user-md text-white text-lg"></i>
                  </div>
                  <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-id-card mr-2 text-blue-300"></i>
                    Personal Information
                  </h3>
                </div>
                <!-- Auto-population status -->
                <div class="flex items-center space-x-2">
                  <span
                    v-if="isLoadingProfile"
                    class="text-sm text-blue-300 font-medium bg-blue-500/20 px-2 py-1 rounded-full border border-blue-400/30 animate-pulse"
                  >
                    <OrbitingDots size="xs" class="mr-1" />
                    Loading...
                  </span>
                  <span
                    v-else-if="autoPopulated"
                    class="text-base text-green-300 font-medium bg-green-500/20 px-2 py-1 rounded-full border border-green-400/30"
                  >
                    <i class="fas fa-check mr-1 text-sm"></i>
                    Auto-populated
                  </span>
                  <span
                    v-else-if="profileLoadError"
                    class="text-xs text-yellow-300 font-medium bg-yellow-500/20 px-2 py-1 rounded-full border border-yellow-400/30"
                  >
                    <i class="fas fa-exclamation-triangle mr-1 text-xs"></i>
                    Manual entry
                  </span>
                </div>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 md:gap-6">
                <!-- Full Name -->
                <div class="">
                  <label
                    class="block text-base font-bold text-blue-100 mb-2 flex items-center justify-between"
                  >
                    <span>Full Name <span class="text-red-400">*</span></span>
                    <span
                      v-if="autoPopulated && formData.fullName && !isLoadingProfile"
                      class="text-xs text-green-300 font-medium bg-green-500/20 px-1.5 py-0.5 rounded-full border border-green-400/30 flex items-center gap-1"
                    >
                      <i class="fas fa-lock text-xs"></i>
                      Protected
                    </span>
                  </label>
                  <div class="relative">
                    <input
                      id="full-name"
                      v-model="formData.fullName"
                      type="text"
                      class="medical-input w-full px-2 py-1.5 border-2 rounded-lg focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm transition-all duration-300 text-sm"
                      :class="{
                        'bg-blue-500/30 border-blue-400/60 focus:border-blue-500 hover:bg-blue-500/40 focus:bg-blue-500/50 focus:shadow-lg focus:shadow-blue-500/30':
                          !autoPopulated || isLoadingProfile,
                        'bg-green-500/20 border-green-400/60 cursor-not-allowed':
                          autoPopulated && formData.fullName && !isLoadingProfile
                      }"
                      :placeholder="
                        isLoadingProfile
                          ? 'Loading your name...'
                          : autoPopulated && formData.fullName
                            ? 'Auto-populated from your profile'
                            : 'Enter your full name'
                      "
                      :disabled="isLoadingProfile || (autoPopulated && formData.fullName)"
                      :readonly="autoPopulated && formData.fullName && !isLoadingProfile"
                      :aria-describedby="
                        autoPopulated && formData.fullName ? 'full-name-help' : undefined
                      "
                      aria-required="true"
                      autocomplete="name"
                      required
                    />
                    <div
                      class="absolute inset-0 rounded-lg bg-gradient-to-r from-blue-500/10 to-blue-600/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                    ></div>
                    <!-- Loading/Success indicator -->
                    <div
                      class="absolute right-2 top-1/2 transform -translate-y-1/2 text-blue-300/70"
                    >
                      <OrbitingDots v-if="isLoadingProfile" size="xs" />
                      <i
                        v-else-if="autoPopulated && formData.fullName"
                        class="fas fa-check text-green-400 text-xs"
                      ></i>
                    </div>
                  </div>
                  <!-- Help text for protected field -->
                  <p
                    id="full-name-help"
                    v-if="autoPopulated && formData.fullName && !isLoadingProfile"
                    class="text-xs text-green-200/70 mt-1 italic flex items-center"
                    role="status"
                  >
                    <i class="fas fa-info-circle mr-1 text-xs" aria-hidden="true"></i>
                    Auto-populated, cannot be edited
                  </p>
                </div>

                <!-- PF Number -->
                <div class="">
                  <label
                    class="block text-base font-bold text-blue-100 mb-2 flex items-center justify-between"
                  >
                    <span>PF Number <span class="text-red-400">*</span></span>
                    <span
                      v-if="autoPopulated && formData.pfNumber && !isLoadingProfile"
                      class="text-xs text-green-300 font-medium bg-green-500/20 px-1.5 py-0.5 rounded-full border border-green-400/30 flex items-center gap-1"
                    >
                      <i class="fas fa-lock text-xs"></i>
                      Protected
                    </span>
                  </label>
                  <div class="relative">
                    <input
                      id="pf-number"
                      v-model="formData.pfNumber"
                      type="text"
                      class="medical-input w-full px-2 py-1.5 border-2 rounded-lg focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm transition-all duration-300 text-sm"
                      :class="{
                        'bg-blue-500/30 border-blue-400/60 focus:border-blue-500 hover:bg-blue-500/40 focus:bg-blue-500/50 focus:shadow-lg focus:shadow-blue-500/30':
                          !autoPopulated || isLoadingProfile,
                        'bg-green-500/20 border-green-400/60 cursor-not-allowed':
                          autoPopulated && formData.pfNumber && !isLoadingProfile
                      }"
                      :placeholder="
                        isLoadingProfile
                          ? 'Loading your PF Number...'
                          : autoPopulated && formData.pfNumber
                            ? 'Auto-populated from your profile'
                            : 'Enter PF Number'
                      "
                      :disabled="isLoadingProfile || (autoPopulated && formData.pfNumber)"
                      :readonly="autoPopulated && formData.pfNumber && !isLoadingProfile"
                      :aria-describedby="
                        autoPopulated && formData.pfNumber ? 'pf-number-help' : undefined
                      "
                      aria-required="true"
                      autocomplete="off"
                      pattern="[-A-Za-z0-9/]+"
                      required
                    />
                    <div
                      class="absolute inset-0 rounded-lg bg-gradient-to-r from-blue-500/10 to-blue-600/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                    ></div>
                    <!-- Loading/Success indicator -->
                    <div
                      class="absolute right-2 top-1/2 transform -translate-y-1/2 text-blue-300/70"
                    >
                      <OrbitingDots v-if="isLoadingProfile" size="xs" />
                      <i
                        v-else-if="autoPopulated && formData.pfNumber"
                        class="fas fa-check text-green-400 text-xs"
                      ></i>
                    </div>
                  </div>
                  <!-- Help text for protected field -->
                  <p
                    id="pf-number-help"
                    v-if="autoPopulated && formData.pfNumber && !isLoadingProfile"
                    class="text-xs text-green-200/70 mt-1 italic flex items-center"
                    role="status"
                  >
                    <i class="fas fa-info-circle mr-1 text-xs" aria-hidden="true"></i>
                    Auto-populated, cannot be edited
                  </p>
                </div>

                <!-- Department/Unit -->
                <div class="">
                  <label
                    class="block text-base font-bold text-blue-100 mb-2 flex items-center justify-between"
                  >
                    <span>Department/Unit <span class="text-red-400">*</span></span>
                    <span
                      v-if="autoPopulated && formData.department && !isLoadingProfile"
                      class="text-xs text-green-300 font-medium bg-green-500/20 px-1.5 py-0.5 rounded-full border border-green-400/30 flex items-center gap-1"
                    >
                      <i class="fas fa-lock text-xs"></i>
                      Protected
                    </span>
                  </label>
                  <div class="relative">
                    <select
                      id="department"
                      v-model="formData.department"
                      class="medical-input w-full px-2 py-1.5 bg-blue-500/30 border-2 border-blue-400/60 rounded-lg focus:border-blue-500 focus:outline-none text-white backdrop-blur-sm transition-all duration-300 hover:bg-blue-500/40 focus:bg-blue-500/50 focus:shadow-lg focus:shadow-blue-500/30 appearance-none text-sm"
                      :disabled="isLoadingProfile || (autoPopulated && formData.department)"
                      :readonly="autoPopulated && formData.department && !isLoadingProfile"
                      :aria-describedby="
                        autoPopulated && formData.department ? 'department-help' : undefined
                      "
                      aria-required="true"
                      required
                    >
                      <option value="" disabled class="bg-gray-800 text-gray-300">
                        Select your department
                      </option>
                      <option
                        v-for="dept in departments"
                        :key="dept"
                        :value="dept"
                        class="bg-gray-800 text-white"
                      >
                        {{ dept }}
                      </option>
                    </select>
                    <div
                      class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
                    >
                      <i class="fas fa-chevron-down text-blue-300"></i>
                    </div>
                    <div
                      class="absolute inset-0 rounded-lg bg-gradient-to-r from-blue-500/10 to-blue-600/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                    ></div>
                  </div>
                  <p
                    id="department-help"
                    v-if="autoPopulated && formData.department && !isLoadingProfile"
                    class="text-xs text-green-200/70 mt-1 italic flex items-center"
                    role="status"
                  >
                    <i class="fas fa-info-circle mr-1 text-xs" aria-hidden="true"></i>
                    Auto-populated, cannot be edited
                  </p>
                </div>

                <!-- Job Title -->
                <div class="">
                  <label class="block text-base font-bold text-blue-100 mb-2">
                    Job Title <span class="text-red-400">*</span>
                  </label>
                  <div class="relative">
                    <input
                      id="job-title"
                      v-model="formData.jobTitle"
                      type="text"
                      class="medical-input w-full px-2 py-1.5 bg-blue-500/30 border-2 border-blue-400/60 rounded-lg focus:border-blue-500 focus:outline-none text-white placeholder-blue-200/60 backdrop-blur-sm transition-all duration-300 hover:bg-blue-500/40 focus:bg-blue-500/50 focus:shadow-lg focus:shadow-blue-500/30 text-sm"
                      placeholder="Enter your job title"
                      aria-required="true"
                      autocomplete="organization-title"
                      required
                    />
                    <div
                      class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500/10 to-blue-600/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Declaration Statement -->
            <div
              class="medical-card bg-gradient-to-r from-blue-600/25 to-blue-700/25 border-2 border-blue-400/40 p-3 rounded-xl backdrop-blur-sm hover:shadow-xl hover:shadow-blue-500/20 transition-all duration-300 group"
            >
              <div class="flex items-center space-x-2 mb-2">
                <div
                  class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 border border-blue-300/50"
                >
                  <i class="fas fa-file-contract text-white text-xs"></i>
                </div>
                <h3 class="text-base font-bold text-white flex items-center">
                  <i class="fas fa-scroll mr-2 text-blue-300 text-xs"></i>
                  Declaration Statement
                </h3>
              </div>

              <div class="bg-white/15 p-3 rounded-xl backdrop-blur-sm border-2 border-blue-300/30">
                <div class="text-blue-100 leading-relaxed text-lg space-y-3 text-justify">
                  <p>
                    I
                    <strong class="text-red-500">{{
                      formData.fullName || '______________________'
                    }}</strong>
                    PF No.
                    <strong class="text-red-500">{{ formData.pfNumber || '______' }}</strong>
                    acknowledge that Muhimbili National Hospital acceptable ICT use policy has been
                    made available to me for adequate review and understanding.
                  </p>

                  <p>
                    I certify that I have been given ample opportunity to read and understand it,
                    and ask questions about my responsibilities on it. I am, therefore, aware that I
                    am accountable to all its terms and requirements; and that I shall abide by
                    them.
                  </p>

                  <p>
                    I also understand that failure to abide by them; Muhimbili National Hospital
                    shall take against me appropriate disciplinary action or legal action, or both,
                    as the case may be.
                  </p>
                </div>
              </div>
            </div>

            <!-- Signature Section -->
            <div
              class="medical-card bg-gradient-to-r from-blue-600/25 to-blue-700/25 border-2 border-blue-400/40 p-2 rounded-xl backdrop-blur-sm hover:shadow-xl hover:shadow-blue-500/20 transition-all duration-300 group"
            >
              <div class="flex items-center space-x-2 mb-1">
                <div
                  class="w-6 h-6 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300 border border-blue-300/50"
                >
                  <i class="fas fa-signature text-white text-xs"></i>
                </div>
                <h3 class="text-sm font-bold text-white flex items-center">
                  <i class="fas fa-pen-fancy mr-1 text-blue-300 text-xs"></i>
                  Digital Signature
                </h3>
              </div>

              <div
                class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-2 md:gap-3"
              >
                <!-- Digital Signature Action -->
                <div class="md:col-span-2 xl:col-span-3 2xl:col-span-5">
                  <label class="block text-sm font-bold text-blue-100 mb-1">
                    Signature <span class="text-red-400">*</span>
                  </label>

                  <div
                    class="w-full px-2 py-1 border-2 border-blue-400/60 rounded-lg bg-blue-500/20 backdrop-blur-sm transition-all duration-300 shadow-lg hover:shadow-xl hover:bg-blue-500/30 min-h-[32px] flex flex-col sm:flex-row sm:items-center gap-2"
                  >
                    <button
                      type="button"
                      @click="signDeclaration"
                      :disabled="isSigning || hasUserSigned"
                      class="px-2.5 py-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-xs sm:text-sm font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 flex items-center gap-2 shadow hover:shadow-lg transform hover:scale-105 disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none border border-blue-400/60 focus:ring-1 focus:ring-blue-300"
                      :aria-label="
                        hasUserSigned
                          ? 'Declaration already signed'
                          : 'Digitally sign this declaration'
                      "
                    >
                      <OrbitingDots v-if="isSigning" size="xs" />
                      <i
                        v-else
                        :class="hasUserSigned ? 'fas fa-check' : 'fas fa-pen-fancy'"
                        class="text-xs"
                      ></i>
                      <span>
                        {{ hasUserSigned ? 'Signed' : 'Click to Sign Digitally' }}
                      </span>
                    </button>

                    <div class="flex-1 text-xs leading-snug">
                      <p v-if="hasUserSigned" class="text-green-300 font-medium">
                        Signed on {{ formattedSignedAt }}
                      </p>
                      <p v-else class="text-blue-100/80">
                        You must digitally sign this declaration before submitting.
                      </p>
                      <p
                        v-if="signatureToken"
                        class="text-[10px] text-blue-200/80 mt-0.5 break-all"
                      >
                        Token: {{ signatureTokenPreview }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Date -->
                <div class="2xl:col-span-1">
                  <label class="block text-sm font-bold text-blue-100 mb-1">
                    Date <span class="text-red-400">*</span>
                  </label>
                  <div class="relative">
                    <input
                      id="signature-date"
                      v-model="formData.date"
                      type="date"
                      class="medical-input w-full px-2 py-1.5 bg-blue-500/30 border-2 border-blue-400/60 rounded-lg focus:border-blue-500 focus:outline-none text-white backdrop-blur-sm transition-all duration-300 hover:bg-blue-500/40 focus:bg-blue-500/50 focus:shadow-lg focus:shadow-blue-500/30 text-sm"
                      aria-required="true"
                      :max="maxFutureDate"
                      required
                    />
                    <div
                      class="absolute inset-0 rounded-xl bg-gradient-to-r from-emerald-500/10 to-teal-500/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                    ></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Agreement Checkbox -->
            <div
              class="medical-card bg-gradient-to-r from-red-600/25 to-rose-600/25 border-2 border-red-400/40 p-3 rounded-xl backdrop-blur-sm hover:shadow-xl hover:shadow-red-500/20 transition-all duration-300 group"
            >
              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 mt-1">
                  <div class="relative">
                    <input
                      v-model="formData.agreement"
                      type="checkbox"
                      id="agreement"
                      class="w-7 h-7 text-red-500 bg-white/15 border-2 border-red-300/40 rounded-lg focus:ring-red-400 focus:ring-2 backdrop-blur-sm transition-all duration-300"
                      aria-required="true"
                      aria-describedby="agreement-text"
                      required
                    />
                    <div
                      class="absolute inset-0 rounded-lg bg-gradient-to-r from-red-500/10 to-rose-500/10 opacity-0 hover:opacity-100 transition-opacity duration-300 pointer-events-none"
                    ></div>
                  </div>
                </div>
                <label for="agreement" class="text-base text-blue-100 leading-relaxed flex-1">
                  <span class="font-bold text-red-300 flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    I hereby confirm that:
                  </span>
                  <span id="agreement-text">
                    I have read, understood, and agree to abide by all terms and conditions of the
                    Muhimbili National Hospital Acceptable ICT Use Policy. I acknowledge that
                    violation of this policy may result in disciplinary action or legal
                    consequences.
                  </span>
                </label>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="border-t-2 border-blue-300/30 pt-3">
              <div class="flex flex-col sm:flex-row justify-center gap-3">
                <button
                  type="submit"
                  :disabled="!isFormValid || isSubmitting"
                  class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-300 font-semibold flex items-center justify-center shadow-lg hover:shadow-xl hover:shadow-blue-500/25 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none border-2 border-blue-500/50 focus:ring-2 focus:ring-blue-300 focus:ring-offset-2"
                  :aria-label="
                    isSubmitting ? 'Submitting declaration form' : 'Submit declaration form'
                  "
                >
                  <OrbitingDots v-if="isSubmitting" size="sm" class="mr-3" />
                  <i v-else class="fas fa-file-signature mr-3" aria-hidden="true"></i>
                  {{ isSubmitting ? 'Submitting...' : 'Submit Declaration' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Back Button -->
    <button
      @click="goBack"
      @keydown.enter="goBack"
      @keydown.space.prevent="goBack"
      class="fixed bottom-6 left-6 w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-blue-300 focus:ring-offset-2 z-40 group"
      aria-label="Go back to previous page"
    >
      <i class="fas fa-arrow-left text-lg group-hover:animate-pulse" aria-hidden="true"></i>
    </button>
  </div>
</template>

<script>
  import userProfileService from '../../../services/userProfileService'
  import SimpleLoadingBanner from '../../common/SimpleLoadingBanner.vue'
  import OrbitingDots from '../../common/OrbitingDots.vue'
  import signatureService from '@/services/signatureService'
  import { APP_CONFIG } from '@/utils/config'

  // Constants for better maintainability
  const FORM_CONSTANTS = {
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    ALLOWED_FILE_TYPES: ['image/png', 'image/jpeg', 'image/jpg', 'application/pdf'],
    MAX_RETRIES: 3,
    RETRY_BASE_DELAY: 1000,
    MAX_RETRY_DELAY: 3000,
    NOTIFICATION_DURATION: 4000,
    SUCCESS_DELAY: 1500,
    MAX_FIELD_LENGTH: 255,
    MAX_TEXT_LENGTH: 1000
  }

  const DEPARTMENTS = Object.freeze([
    'Administration',
    'Anesthesiology',
    'Cardiology',
    'Dermatology',
    'Emergency Medicine',
    'Endocrinology',
    'Gastroenterology',
    'General Surgery',
    'Gynecology',
    'Hematology',
    'ICT Department',
    'Internal Medicine',
    'Laboratory',
    'Nephrology',
    'Neurology',
    'Nursing',
    'Obstetrics',
    'Oncology',
    'Ophthalmology',
    'Orthopedics',
    'Pediatrics',
    'Pharmacy',
    'Psychiatry',
    'Pulmonology',
    'Radiology',
    'Rehabilitation',
    'Urology',
    'Other'
  ])

  const NOTIFICATION_COLORS = Object.freeze({
    success: 'green',
    error: 'red',
    info: 'blue',
    warning: 'yellow'
  })

  // eslint-disable-next-line no-unused-vars
  const FLOATING_ICON_CLASSES = Object.freeze([
    'fa-heartbeat',
    'fa-user-md',
    'fa-hospital',
    'fa-stethoscope',
    'fa-plus'
  ])

  export default {
    name: 'DeclarationForm',
    components: {
      SimpleLoadingBanner,
      OrbitingDots
    },
    emits: ['form-submitted', 'go-back'],
    data() {
      return {
        isSubmitting: false,
        // Digital signature state
        isSigning: false,
        hasUserSigned: false,
        signatureToken: '',
        signedAt: null,
        // Legacy preview fields (kept for backward compatibility, not used in UI)
        signaturePreview: '',
        signatureFileName: '',
        // Auto-population states
        isLoadingProfile: true,
        profileLoadError: null,
        autoPopulated: false,
        departments: [...DEPARTMENTS], // Use spread to create mutable copy
        formData: {
          fullName: '',
          pfNumber: '',
          department: '',
          jobTitle: '',
          signature: null,
          date: '',
          agreement: false
        },
        // Performance optimization caches
        backgroundDotsCache: null,
        floatingIconsCache: null,

        // Memory management references
        retryButton: null,
        retryClickHandler: null,
        retryTimeout: null,
        fileReaders: new Set()
      }
    },

    computed: {
      isFormValid() {
        return this.formValidationState.isValid
      },

      // Calculate maximum future date (3 months from today)
      maxFutureDate() {
        const date = new Date()
        date.setMonth(date.getMonth() + 3)
        return date.toISOString().split('T')[0]
      },

      backgroundDots() {
        // Return pre-computed dots (initialized in created hook)
        return this.backgroundDotsCache || []
      },
      floatingIcons() {
        // Return pre-computed icons (initialized in created hook)
        return this.floatingIconsCache || []
      },
      formValidationState() {
        // Cache validation state to avoid repeated calculations
        const hasName = !!this.formData.fullName
        const hasPfNumber = !!this.formData.pfNumber
        const hasDepartment = !!this.formData.department
        const hasJobTitle = !!this.formData.jobTitle
        const hasDate = !!this.formData.date
        const hasAgreement = !!this.formData.agreement
        const hasSignature = !!this.hasUserSigned

        return {
          isValid:
            hasName &&
            hasPfNumber &&
            hasDepartment &&
            hasJobTitle &&
            hasDate &&
            hasAgreement &&
            hasSignature,
          missingFields: [
            !hasName && 'Full Name',
            !hasPfNumber && 'PF Number',
            !hasDepartment && 'Department',
            !hasJobTitle && 'Job Title',
            !hasDate && 'Date',
            !hasAgreement && 'Agreement',
            !hasSignature && 'Digital Signature'
          ].filter(Boolean)
        }
      },

      formattedSignedAt() {
        if (!this.signedAt) return ''
        try {
          return new Date(this.signedAt).toLocaleString()
        } catch (e) {
          return this.signedAt
        }
      },

      signatureTokenPreview() {
        if (!this.signatureToken) return ''
        if (this.signatureToken.length <= 16) return this.signatureToken
        return `${this.signatureToken.slice(0, 10)}…${this.signatureToken.slice(-4)}`
      }
    },

    methods: {
      /**
       * Handle loading banner completion
       */
      onLoadingComplete() {
        if (APP_CONFIG.DEBUG) console.log('🎉 Loading banner animation completed')
        // Loading banner has finished its fade-out animation
      },

      /**
       * Trigger loading completion on the banner
       */
      completeLoadingBanner() {
        // Find the SimpleLoadingBanner component and call its completeLoading method
        this.$nextTick(() => {
          const loadingBanner = this.$refs.loadingBanner
          if (loadingBanner && loadingBanner.completeLoading) {
            loadingBanner.completeLoading()
          }
        })
      },

      /**
       * Auto-populate user data from the authenticated user's profile
       */
      async autoPopulateUserData() {
        if (APP_CONFIG.DEBUG) console.log('🔄 Starting declaration form auto-population...')
        this.isLoadingProfile = true
        this.profileLoadError = null

        // Check authentication first
        const token = localStorage.getItem('auth_token')
        if (!token) {
          console.error('❌ No authentication token found')
          this.profileLoadError = 'No authentication token found'
          this.isLoadingProfile = false
          return
        }

        if (APP_CONFIG.DEBUG)
          console.log('✅ Authentication token found, proceeding with profile fetch')

        try {
          const result = await userProfileService.getFormAutoPopulationData()
          console.log('📋 Auto-population API response:', result)

          if (result.success && result.data) {
            console.log('✅ User profile retrieved for declaration:', result.data)

            // Validate and populate form fields safely
            const populationResult = this.processUserProfileData(result.data)

            if (populationResult.success) {
              this.autoPopulated = true
              console.log('✅ Declaration form auto-populated:', populationResult.data)

              // Show success notification with specific fields populated
              if (populationResult.populatedFields.length > 0) {
                this.showNotification(
                  `Auto-populated: ${populationResult.populatedFields.join(', ')}`,
                  'info'
                )
              }
            } else {
              console.warn('⚠️ No fields could be auto-populated:', populationResult.error)
              this.showNotification('Profile data available but could not be used', 'warning')
            }
          } else {
            console.warn('⚠️ Failed to auto-populate declaration form:', result.error)
            this.profileLoadError = result.error || 'Failed to load profile data'
            this.showNotification(
              'Could not auto-populate your details. Please fill them manually.',
              'warning'
            )
          }
        } catch (error) {
          console.error('❌ Error during declaration auto-population:', error)
          this.profileLoadError = error.message || 'Network error while loading profile'
          this.showNotification(
            'Error loading your profile. Please fill in your details manually.',
            'error'
          )
        } finally {
          // Trigger the loading banner completion animation before hiding
          this.completeLoadingBanner()
          // isLoadingProfile will be set to false after the animation completes
          // The loading banner will emit 'loading-complete' event when done
          setTimeout(() => {
            this.isLoadingProfile = false
          }, 400) // Slightly longer than the 300ms fade-out animation
        }
      },

      async signDeclaration() {
        try {
          if (this.isSigning) return
          this.isSigning = true

          // Use a synthetic document id for declaration onboarding; backend maps this
          const res = await signatureService.sign('declaration')

          if (res && (res.success || res.data)) {
            this.hasUserSigned = true
            this.signedAt = res?.data?.signed_at || res?.signed_at || new Date().toISOString()
            this.signatureToken = res?.data?.signature_hash || res?.signature_hash || ''

            this.showNotification(
              `Declaration signed on ${new Date(this.signedAt).toLocaleString()}`,
              'success'
            )
          } else {
            this.showNotification(res?.message || 'Failed to sign declaration', 'error')
          }
        } catch (error) {
          console.error('Error signing declaration:', error)
          this.showNotification(error.message || 'Failed to sign declaration', 'error')
        } finally {
          this.isSigning = false
        }
      },

      /**
       * Process and validate user profile data for auto-population
       */
      processUserProfileData(profileData) {
        if (!profileData || typeof profileData !== 'object') {
          return { success: false, error: 'Invalid profile data format' }
        }

        const populatedFields = []
        const errors = []

        try {
          // Validate and set full name
          const fullName = this.validateAndSanitizeText(
            profileData.staffName || profileData.fullName || '',
            'Full Name'
          )
          if (fullName.isValid) {
            this.formData.fullName = fullName.value
            populatedFields.push('name')
          } else if (fullName.error) {
            errors.push(fullName.error)
          }

          // Validate and set PF number
          const pfNumber = this.validateAndSanitizeText(profileData.pfNumber || '', 'PF Number')
          if (pfNumber.isValid) {
            this.formData.pfNumber = pfNumber.value
            populatedFields.push('PF number')
          } else if (pfNumber.error) {
            errors.push(pfNumber.error)
          }

          // Validate and set department
          const departmentName =
            profileData.departmentFullName ||
            profileData.departmentName ||
            profileData.departmentCode ||
            ''
          const department = this.validateAndSanitizeText(departmentName, 'Department')

          if (department.isValid && department.value) {
            // Add to departments list if not present
            if (!this.departments.includes(department.value)) {
              console.log('📝 Adding user department to available options:', department.value)
              this.departments.push(department.value)
              this.departments.sort()
            }

            this.formData.department = department.value
            populatedFields.push('department')
          } else if (department.error) {
            errors.push(department.error)
          }

          return {
            success: populatedFields.length > 0,
            populatedFields,
            errors,
            data: {
              fullName: this.formData.fullName,
              pfNumber: this.formData.pfNumber,
              department: this.formData.department
            }
          }
        } catch (error) {
          console.error('Error processing profile data:', error)
          return {
            success: false,
            error: 'Error processing profile data',
            populatedFields: [],
            errors: [error.message]
          }
        }
      },

      /**
       * Validate and sanitize text input
       */
      validateAndSanitizeText(value, fieldName) {
        if (!value || typeof value !== 'string') {
          return { isValid: false, value: '', error: null }
        }

        // Trim and sanitize
        const cleaned = value.trim().replace(/[<>"'&]/g, '')

        // Length validation
        if (cleaned.length === 0) {
          return { isValid: false, value: '', error: null }
        }

        if (cleaned.length > FORM_CONSTANTS.MAX_FIELD_LENGTH) {
          return {
            isValid: false,
            value: '',
            error: `${fieldName} is too long (max ${FORM_CONSTANTS.MAX_FIELD_LENGTH} characters)`
          }
        }

        // Pattern validation for specific fields
        if (fieldName === 'PF Number' && cleaned && !/^[A-Z0-9\-\/]+$/i.test(cleaned)) {
          return {
            isValid: false,
            value: '',
            error: 'PF Number contains invalid characters'
          }
        }

        return { isValid: true, value: cleaned, error: null }
      },

      /**
       * Enhanced file security validation
       */
      validateFileSecurity(file) {
        // Basic file type validation
        if (!FORM_CONSTANTS.ALLOWED_FILE_TYPES.includes(file.type)) {
          return {
            isValid: false,
            error: `Please select a valid file type: ${FORM_CONSTANTS.ALLOWED_FILE_TYPES.join(', ')
              .replace(/image\//g, '')
              .replace(/application\//g, '')
              .toUpperCase()}`
          }
        }

        // File size validation
        if (file.size > FORM_CONSTANTS.MAX_FILE_SIZE) {
          return {
            isValid: false,
            error: `File size must be less than ${FORM_CONSTANTS.MAX_FILE_SIZE / (1024 * 1024)}MB`
          }
        }

        // Filename security validation
        const dangerousPatterns = [
          /\.exe$/i,
          /\.bat$/i,
          /\.cmd$/i,
          /\.com$/i,
          /\.scr$/i,
          /\.js$/i,
          /\.vbs$/i
        ]
        if (dangerousPatterns.some((pattern) => pattern.test(file.name))) {
          return {
            isValid: false,
            error: 'File type not allowed for security reasons'
          }
        }

        // File name length validation
        if (file.name.length > 255) {
          return {
            isValid: false,
            error: 'Filename is too long'
          }
        }

        // Check for double extensions
        const parts = file.name.split('.')
        if (parts.length > 2) {
          return {
            isValid: false,
            error: 'Files with multiple extensions are not allowed'
          }
        }

        return { isValid: true }
      },

      async submitDeclaration() {
        if (!this.isFormValid) {
          const missingFields = this.formValidationState.missingFields
          this.showNotification(`Missing required fields: ${missingFields.join(', ')}`, 'error')
          return
        }

        // Additional runtime validation
        const validationResult = this.validateFormData()
        if (!validationResult.isValid) {
          this.showNotification(validationResult.error, 'error')
          return
        }

        try {
          // Show loading state
          this.isSubmitting = true
          this.showNotification('Submitting declaration...', 'info')

          // Prepare form data for submission
          const declarationData = {
            fullName: this.formData.fullName,
            pfNumber: this.formData.pfNumber,
            department: this.formData.department,
            jobTitle: this.formData.jobTitle,
            date: this.formData.date,
            agreement: this.formData.agreement,
            // Optional metadata about the digital signature token
            signatureMethod: 'digital_token',
            signatureHash: this.signatureToken || null,
            signedAt: this.signedAt || null
          }

          // Create FormData for compatibility with existing backend (even without files)
          const formData = new FormData()

          // Add declaration data with proper type conversion
          Object.keys(declarationData).forEach((key) => {
            let value = declarationData[key]
            // Convert boolean to string for FormData, but ensure it's a proper boolean string
            if (typeof value === 'boolean') {
              value = value ? '1' : '0' // Use 1/0 instead of true/false for better backend handling
            }
            if (value !== null && value !== undefined) {
              formData.append(key, value)
            }
          })

          if (APP_CONFIG.DEBUG) {
            console.log('Form data being sent:', {
              ...declarationData,
              hasDigitalSignature: this.hasUserSigned
            })
          }

          // Import API client and submit
          const { authAPI } = await import('../../../utils/apiClient')
          const result = await authAPI.submitDeclaration(formData)

          if (result.success) {
            if (APP_CONFIG.DEBUG) console.log('Declaration submitted successfully:', result.data)
            this.showNotification('Declaration submitted successfully!', 'success')

            // Emit event to parent component with the declaration data
            setTimeout(() => {
              this.$emit('form-submitted', declarationData)
            }, FORM_CONSTANTS.SUCCESS_DELAY)
          } else {
            console.error('Declaration submission failed:', result.error)
            this.showNotification(result.error || 'Failed to submit declaration', 'error')

            // Show validation errors if present
            if (result.errors) {
              Object.values(result.errors).forEach((errorArray) => {
                errorArray.forEach((error) => {
                  this.showNotification(error, 'error')
                })
              })
            }
          }
        } catch (error) {
          console.error('Error submitting declaration:', error)
          this.showNotification('An error occurred while submitting the declaration', 'error')
        } finally {
          this.isSubmitting = false
        }
      },

      resetForm() {
        this.formData = {
          fullName: '',
          pfNumber: '',
          department: '',
          jobTitle: '',
          signature: null,
          date: '',
          agreement: false
        }
        this.signaturePreview = ''
        this.signatureFileName = ''
        this.hasUserSigned = false
        this.signatureToken = ''
        this.signedAt = null
        this.showNotification('Form has been reset', 'info')
      },

      printForm() {
        window.print()
      },

      showNotification(message, type = 'info') {
        if (!message) return

        // Sanitize message to prevent XSS
        const sanitizedMessage = String(message).replace(/<[^>]*>/g, '')
        const colors = NOTIFICATION_COLORS

        // Use a more efficient notification system
        this.createOptimizedNotification(sanitizedMessage, colors[type] || 'blue')
      },

      /**
       * Optimized notification creation with better performance
       */
      createOptimizedNotification(message, bgColor) {
        // Reuse notification container if it exists
        let container = document.getElementById('notification-container')
        if (!container) {
          container = document.createElement('div')
          container.id = 'notification-container'
          container.className = 'fixed top-4 right-4 z-[9999] space-y-2 pointer-events-none'
          document.body.appendChild(container)
        }

        const toast = document.createElement('div')
        toast.className = `px-6 py-3 rounded-lg text-white font-semibold bg-${bgColor}-600 shadow-lg transform transition-all duration-300 max-w-md pointer-events-auto`
        toast.textContent = message
        toast.style.transform = 'translateX(100%)'

        container.appendChild(toast)

        // Use RAF for smooth animation
        requestAnimationFrame(() => {
          toast.style.transform = 'translateX(0)'
        })

        // Auto-remove with optimized cleanup
        setTimeout(
          () => this.removeNotification(toast, container),
          FORM_CONSTANTS.NOTIFICATION_DURATION
        )
      },

      /**
       * Optimized notification removal
       */
      removeNotification(toast, container) {
        if (!toast.parentNode) return

        toast.style.transform = 'translateX(100%)'
        const removeTimeout = setTimeout(() => {
          if (toast.parentNode) {
            container.removeChild(toast)

            // Remove container if empty and still in DOM
            if (container.children.length === 0 && document.body.contains(container)) {
              document.body.removeChild(container)
            }
          }
          clearTimeout(removeTimeout)
        }, 300)
      },

      /**
       * Comprehensive form data validation
       */
      validateFormData() {
        const errors = []

        // Validate each field
        const nameValidation = this.validateAndSanitizeText(this.formData.fullName, 'Full Name')
        if (!nameValidation.isValid) {
          errors.push(nameValidation.error || 'Full name is required')
        }

        const pfValidation = this.validateAndSanitizeText(this.formData.pfNumber, 'PF Number')
        if (!pfValidation.isValid) {
          errors.push(pfValidation.error || 'PF Number is required')
        }

        const deptValidation = this.validateAndSanitizeText(this.formData.department, 'Department')
        if (!deptValidation.isValid) {
          errors.push(deptValidation.error || 'Department is required')
        }

        const jobValidation = this.validateAndSanitizeText(this.formData.jobTitle, 'Job Title')
        if (!jobValidation.isValid) {
          errors.push(jobValidation.error || 'Job title is required')
        }

        // Date validation
        if (!this.formData.date) {
          errors.push('Date is required')
        } else {
          const selectedDate = new Date(this.formData.date)
          const maxFutureDate = new Date()
          maxFutureDate.setMonth(maxFutureDate.getMonth() + 3)

          if (selectedDate > maxFutureDate) {
            errors.push('Date cannot be more than 3 months in the future')
          }
        }

        // Agreement validation
        if (!this.formData.agreement) {
          errors.push('You must agree to the terms and conditions')
        }

        // Signature validation - require digital token based signature
        if (!this.hasUserSigned) {
          errors.push('Digital signature is required')
        }

        return {
          isValid: errors.length === 0,
          error: errors.length > 0 ? errors[0] : null,
          errors
        }
      },

      goBack() {
        this.$emit('go-back')
      },

      /**
       * Check if user is properly authenticated
       */
      checkAuthentication() {
        const token = localStorage.getItem('auth_token')
        const userData = localStorage.getItem('user_data')

        if (!token) {
          console.error('❌ No authentication token found')
          return false
        }

        if (!userData) {
          console.warn('⚠️ No user data found in localStorage')
        }

        // For Laravel Sanctum, we can't decode the token like JWT
        // The token expiry is handled server-side
        // We can only check if the token exists and is not empty
        if (token.trim().length === 0) {
          console.error('❌ Authentication token is empty')
          return false
        }

        // Optional: Check if token looks valid (basic format check)
        // Sanctum tokens are typically long random strings
        if (token.length < 10) {
          console.warn('⚠️ Authentication token appears to be too short')
          return false
        }

        return true
      },

      /**
       * Handle authentication failure
       */
      handleAuthenticationFailure() {
        // Clear potentially invalid tokens
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_data')
        localStorage.removeItem('session_data')

        // Show user-friendly message
        this.showNotification('Session expired. Please log in again.', 'error')

        // Redirect to login after a short delay
        setTimeout(() => {
          if (typeof window !== 'undefined') {
            window.location.href = '/'
          }
        }, 2000)
      },

      /**
       * Initialize form with retry logic
       */
      async initializeFormWithRetry() {
        let attempt = 1

        while (attempt <= FORM_CONSTANTS.MAX_RETRIES) {
          try {
            console.log(`🔄 Initializing form attempt ${attempt}/${FORM_CONSTANTS.MAX_RETRIES}`)
            await this.autoPopulateUserData()

            // If successful, break out of retry loop
            if (!this.profileLoadError) {
              console.log('✅ Form initialization successful')
              return
            }
          } catch (error) {
            console.error(`❌ Form initialization attempt ${attempt} failed:`, error)
          }

          attempt++

          // If not the last attempt, wait before retrying
          if (attempt <= FORM_CONSTANTS.MAX_RETRIES) {
            const delay = Math.min(
              FORM_CONSTANTS.RETRY_BASE_DELAY * attempt,
              FORM_CONSTANTS.MAX_RETRY_DELAY
            )
            console.log(`⏳ Retrying in ${delay}ms...`)
            await new Promise((resolve) => setTimeout(resolve, delay))
          }
        }

        // All retries failed
        console.error('❌ All initialization attempts failed')
        this.handleInitializationFailure()
      },

      /**
       * Handle complete initialization failure
       */
      handleInitializationFailure() {
        this.isLoadingProfile = false
        this.profileLoadError = 'Failed to initialize form after multiple attempts'

        this.showNotification(
          'Unable to load your profile data. You can still fill the form manually.',
          'warning'
        )

        // Provide manual fallback option
        this.showManualEntryOption()
      },

      /**
       * Show manual entry option when auto-population fails
       */
      showManualEntryOption() {
        // Create a retry button with proper cleanup
        const retryButton = document.createElement('button')
        retryButton.className =
          'fixed bottom-20 right-6 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2 transition-all duration-300'
        retryButton.innerHTML = '<i class="fas fa-sync-alt"></i> Retry Loading Profile'

        const handleRetryClick = async () => {
          this.removeRetryButton(retryButton, handleRetryClick)
          this.isLoadingProfile = true
          this.profileLoadError = null
          await this.initializeFormWithRetry()
        }

        retryButton.addEventListener('click', handleRetryClick)
        document.body.appendChild(retryButton)

        // Store reference for cleanup
        this.retryButton = retryButton
        this.retryClickHandler = handleRetryClick

        // Auto-remove after 10 seconds
        this.retryTimeout = setTimeout(() => {
          this.removeRetryButton(retryButton, handleRetryClick)
        }, 10000)
      },

      /**
       * Clean up retry button and its event listeners
       */
      removeRetryButton(button, handler) {
        if (button && document.body.contains(button)) {
          button.removeEventListener('click', handler)
          document.body.removeChild(button)
        }

        if (this.retryTimeout) {
          clearTimeout(this.retryTimeout)
          this.retryTimeout = null
        }

        this.retryButton = null
        this.retryClickHandler = null
      },

      /**
       * Load available departments from backend API
       */
      async loadAvailableDepartments() {
        try {
          console.log('🏢 Loading departments from backend...')
          const result = await userProfileService.getDepartments()

          if (result.success && result.data && Array.isArray(result.data)) {
            // Extract department display names and merge with existing list
            const backendDepartments = result.data
              .map((dept) => dept.display_name || dept.full_name || dept.name || '')
              .filter((name) => name.length > 0)

            // Merge with existing departments, remove duplicates, and sort
            const allDepartments = [...new Set([...this.departments, ...backendDepartments])]
            this.departments = Object.freeze(allDepartments.sort()) // Freeze to prevent accidental mutations

            console.log(
              `✅ Loaded ${backendDepartments.length} departments from backend, total: ${this.departments.length}`
            )
          } else {
            console.warn('⚠️ Failed to load departments from backend, using static list')
          }
        } catch (error) {
          console.warn('⚠️ Error loading departments from backend:', error.message)
          console.log('📋 Using static department list as fallback')
        }
      },

      /**
       * Initialize background dots cache
       */
      initializeBackgroundDots() {
        if (this.backgroundDotsCache) return

        const dots = []
        for (let i = 1; i <= 48; i++) {
          dots.push({
            id: `dot-${i}`,
            delay: `${i * 0.1}s`
          })
        }

        this.backgroundDotsCache = Object.freeze(dots)
      },

      /**
       * Initialize floating icons cache
       */
      initializeFloatingIcons() {
        if (this.floatingIconsCache) return

        const icons = []
        const iconClasses = FLOATING_ICON_CLASSES

        // Use seed-based randomization for consistent positioning
        const seedRandom = (seed) => {
          const x = Math.sin(seed) * 10000
          return x - Math.floor(x)
        }

        for (let i = 1; i <= 15; i++) {
          const seed1 = i * 12345
          const seed2 = i * 67890
          const seed3 = i * 54321
          const seed4 = i * 98765
          const seed5 = i * 13579

          icons.push({
            id: `icon-${i}`,
            left: `${seedRandom(seed1) * 100}%`,
            top: `${seedRandom(seed2) * 100}%`,
            animationDelay: `${seedRandom(seed3) * 3}s`,
            animationDuration: `${seedRandom(seed4) * 3 + 2}s`,
            fontSize: `${seedRandom(seed5) * 20 + 10}px`,
            iconClass: `fas ${iconClasses[Math.floor(seedRandom(seed1 * 2) * iconClasses.length)]}`
          })
        }

        this.floatingIconsCache = Object.freeze(icons)
      },

      /**
       * Clean up component resources
       */
      cleanupResources() {
        // Cleanup file readers
        if (this.fileReaders && this.fileReaders.size > 0) {
          this.fileReaders.forEach((reader) => {
            if (reader.readyState === FileReader.LOADING) {
              reader.abort()
            }
          })
          this.fileReaders.clear()
        }

        // Cleanup retry button
        if (this.retryButton || this.retryClickHandler) {
          this.removeRetryButton(this.retryButton, this.retryClickHandler)
        }

        // Clear notification container
        const container = document.getElementById('notification-container')
        if (container && document.body.contains(container)) {
          document.body.removeChild(container)
        }

        // Clear caches
        this.backgroundDotsCache = null
        this.floatingIconsCache = null
      }
    },

    async mounted() {
      console.log('🔄 Declaration form mounted, starting initialization...')

      // Check authentication first
      if (!this.checkAuthentication()) {
        console.error('❌ User not authenticated, redirecting to login')
        this.handleAuthenticationFailure()
        return
      }

      // Set current date as default
      const today = new Date().toISOString().split('T')[0]
      this.formData.date = today

      // Load available departments from backend (parallel with auto-population)
      this.loadAvailableDepartments()

      // Initialize background elements for better performance
      this.initializeBackgroundDots()
      this.initializeFloatingIcons()

      // Auto-populate user data using the profile service with retry logic
      await this.initializeFormWithRetry()
    },

    beforeUnmount() {
      // Cleanup resources to prevent memory leaks
      this.cleanupResources()
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

  /* Select dropdown styling */
  select.medical-input {
    background-image: none;
  }

  select.medical-input option {
    background-color: #1f2937;
    color: white;
    padding: 8px;
  }

  select.medical-input option:hover {
    background-color: #374151;
  }

  select.medical-input option:disabled {
    color: #9ca3af;
  }

  /* Animations */
  @keyframes blob {
    0% {
      transform: translate(0px, 0px) scale(1);
    }
    33% {
      transform: translate(30px, -50px) scale(1.1);
    }
    66% {
      transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
      transform: translate(0px, 0px) scale(1);
    }
  }

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

  .animate-blob {
    animation: blob 7s infinite;
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

  .animation-delay-2000 {
    animation-delay: 2s;
  }

  .animation-delay-4000 {
    animation-delay: 4s;
  }

  /* Custom transitions and animations */
  .transition-all {
    transition: all 0.3s ease;
  }

  /* Glass morphism effect */
  .backdrop-blur-sm {
    backdrop-filter: blur(8px);
  }

  /* Enhanced form sections */
  .border-l-4 {
    position: relative;
    transition: all 0.3s ease;
  }

  .border-l-4:hover {
    transform: translateX(2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  }

  /* Print styles */
  @media print {
    .min-h-screen {
      min-height: auto;
    }

    button {
      display: none !important;
    }

    .shadow-xl {
      box-shadow: none;
    }

    .bg-gradient-to-br {
      background: white !important;
    }

    .rounded-t-2xl,
    .rounded-b-2xl {
      border-radius: 0 !important;
    }

    .border-l-4 {
      border-left: 2px solid #000 !important;
    }

    .bg-blue-50,
    .bg-green-50,
    .bg-yellow-50,
    .bg-red-50 {
      background: #f9f9f9 !important;
    }

    .text-white {
      color: #000 !important;
    }

    .bg-red-600 {
      background: #000 !important;
      color: #fff !important;
    }
  }

  /* Responsive adjustments */
  @media (max-width: 1024px) {
    .lg\:grid-cols-4 {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 768px) {
    .lg\:grid-cols-4 {
      grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .grid-cols-2 {
      grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .md\:col-span-2 {
      grid-column: span 1;
    }
  }

  /* Custom input focus styles */
  input:focus,
  select:focus,
  textarea:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  /* Checkbox custom styling */
  input[type='checkbox']:checked {
    background-color: #dc2626;
    border-color: #dc2626;
  }

  /* Animation for form sections */
  .border-l-4 {
    animation: slideInLeft 0.5s ease-out;
  }

  @keyframes slideInLeft {
    from {
      opacity: 0;
      transform: translateX(-20px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  /* Button hover effects */
  button:hover:not(:disabled) {
    transform: translateY(-2px) scale(1.02);
  }

  button:active:not(:disabled) {
    transform: translateY(0) scale(0.98);
  }

  /* Signature area hover effects */
  .border-dashed:hover {
    border-color: #3b82f6;
    background-color: rgba(59, 130, 246, 0.1);
  }

  /* Protected field styles */
  .medical-input[readonly] {
    background: rgba(34, 197, 94, 0.15) !important;
    border-color: rgba(34, 197, 94, 0.4) !important;
    cursor: not-allowed;
    user-select: none;
  }

  .medical-input[readonly]:hover {
    background: rgba(34, 197, 94, 0.2) !important;
    transform: none !important;
  }

  .medical-input[readonly]:focus {
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2) !important;
    border-color: rgba(34, 197, 94, 0.6) !important;
  }

  /* Custom scrollbar */
  ::-webkit-scrollbar {
    width: 6px;
  }

  ::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
  }

  ::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
  }
</style>
