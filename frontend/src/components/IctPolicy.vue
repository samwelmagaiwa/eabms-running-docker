<template>
  <div
    class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-teal-900 py-8 px-4 relative overflow-hidden"
  >
    <!-- Medical Background Pattern -->
    <div class="absolute inset-0 overflow-hidden">
      <!-- Medical Cross Pattern -->
      <div class="absolute inset-0 opacity-5">
        <div class="grid grid-cols-12 gap-8 h-full transform rotate-45">
          <div
            v-for="i in 24"
            :key="i"
            class="bg-white rounded-full w-2 h-2 animate-pulse"
            :style="{ animationDelay: i * 0.2 + 's' }"
          ></div>
        </div>
      </div>
      <!-- Floating medical icons -->
      <div class="absolute inset-0">
        <div
          v-for="i in 8"
          :key="i"
          class="absolute text-white opacity-5 animate-float"
          :style="getIconStyle(i)"
        >
          <i :class="getIconClass(i)"></i>
        </div>
      </div>
    </div>

    <div class="max-w-none mx-4 relative z-10">
      <!-- Professional Header -->
      <div class="medical-glass-card rounded-t-3xl p-8 mb-0 border-b border-blue-300/30">
        <div class="text-center">
          <div class="flex items-center justify-center mb-4">
            <h1 class="text-4xl font-bold text-white tracking-wide drop-shadow-lg">ICT POLICY</h1>
          </div>

          <p class="text-blue-100 text-lg font-medium">
            Muhimbili National Hospital - Information and Communication Technology Department
          </p>
        </div>

        <!-- Version Control Information -->
        <div class="bg-white/10 backdrop-blur-sm px-6 py-4 border-t border-blue-300/30 mt-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="text-center md:text-left">
              <span class="font-semibold text-blue-100">Version:</span>
              <span class="text-cyan-300 font-bold ml-1">1.0</span>
            </div>
            <div class="text-center">
              <span class="font-semibold text-blue-100">Approved:</span>
              <span class="text-emerald-300 font-bold ml-1">March 15, 2020</span>
            </div>
            <div class="text-center md:text-right">
              <span class="font-semibold text-blue-100">Last Updated:</span>
              <span class="text-yellow-300 font-bold ml-1">November 22, 2019</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Structured Policy Content -->
      <div class="medical-glass-card rounded-b-3xl p-8">
        <!-- 1. Policy Overview -->
        <section class="mb-6">
          <div
            class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 p-6 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
          >
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300"
              >
                <span class="text-white font-bold text-lg">1</span>
              </div>
              <h2 class="text-xl font-bold text-white">POLICY OVERVIEW</h2>
            </div>
            <div class="ml-16">
              <p class="text-blue-100 leading-relaxed mb-4">
                This ICT Policy establishes the framework for the
                <strong class="text-emerald-300">secure, efficient, and appropriate use</strong>
                of information and communication technology resources at Muhimbili National
                Hospital.
              </p>
              <div
                class="bg-white/15 rounded-xl p-4 border-2 border-emerald-300/30 backdrop-blur-sm"
              >
                <p class="text-sm text-blue-100">
                  <strong class="text-emerald-300">Scope:</strong> This policy applies to all staff,
                  contractors, volunteers, and authorized users of hospital ICT systems, networks,
                  and devices.
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- 2. Information Security -->
        <section class="mb-6">
          <div
            class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 p-6 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
          >
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300"
              >
                <span class="text-white font-bold text-lg">2</span>
              </div>
              <h2 class="text-xl font-bold text-white">INFORMATION SECURITY</h2>
            </div>
            <div class="ml-16">
              <p class="text-blue-100 leading-relaxed mb-4">
                Information security is
                <strong class="text-emerald-300">paramount</strong> to protecting patient data,
                hospital operations, and institutional reputation.
              </p>

              <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-4">
                  <div
                    class="bg-white/15 rounded-xl p-4 border-2 border-emerald-300/30 backdrop-blur-sm"
                  >
                    <h4 class="font-bold text-emerald-300 mb-3 flex items-center">
                      <i
                        class="fas fa-lock text-emerald-400 mr-2"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                      Access Control
                    </h4>
                    <ul class="text-sm text-blue-100 space-y-2">
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Protect login credentials and never share passwords
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Use only approved software and applications
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Report security incidents immediately
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="space-y-4">
                  <div
                    class="bg-white/15 rounded-xl p-4 border-2 border-emerald-300/30 backdrop-blur-sm"
                  >
                    <h4 class="font-bold text-emerald-300 mb-3 flex items-center">
                      <i
                        class="fas fa-database text-emerald-400 mr-2"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                      Data Protection
                    </h4>
                    <ul class="text-sm text-blue-100 space-y-2">
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Follow data classification procedures
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Ensure workstations are locked when unattended
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Use encryption for sensitive data transmission
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- 3. Network and Internet Usage -->
        <section class="mb-6">
          <div
            class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 p-6 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
          >
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300"
              >
                <span class="text-white font-bold text-lg">3</span>
              </div>
              <h2 class="text-xl font-bold text-white">NETWORK AND INTERNET USAGE</h2>
            </div>
            <div class="ml-16">
              <p class="text-blue-100 leading-relaxed mb-4">
                Hospital network and internet resources are provided for
                <strong class="text-emerald-300">legitimate business purposes</strong>
                only.
              </p>

              <div
                class="bg-white/15 rounded-xl p-6 border-2 border-emerald-300/30 backdrop-blur-sm"
              >
                <h3 class="text-lg font-bold text-emerald-300 mb-4">Acceptable Use Guidelines</h3>

                <div class="grid md:grid-cols-2 gap-6">
                  <div>
                    <h4 class="font-semibold text-emerald-300 mb-2 flex items-center">
                      <i class="fas fa-check-circle text-emerald-400 mr-2"></i>
                      Permitted Activities:
                    </h4>
                    <ul class="text-sm text-blue-100 space-y-1">
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Work-related research and communication
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Accessing approved hospital systems
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Professional development resources
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-check text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Emergency personal communication (limited)
                      </li>
                    </ul>
                  </div>
                  <div>
                    <h4 class="font-semibold text-emerald-300 mb-2 flex items-center">
                      <i class="fas fa-times-circle text-emerald-400 mr-2"></i>
                      Prohibited Activities:
                    </h4>
                    <ul class="text-sm text-blue-100 space-y-1">
                      <li class="flex items-start">
                        <i
                          class="fas fa-times text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Accessing inappropriate websites
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-times text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Downloading unauthorized software
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-times text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Excessive personal use
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-times text-emerald-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Streaming non-work content
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- 4. Email and Communication Systems -->
        <section class="mb-6">
          <div
            class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 p-6 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
          >
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300"
              >
                <span class="text-white font-bold text-lg">4</span>
              </div>
              <h2 class="text-xl font-bold text-white">EMAIL AND COMMUNICATION SYSTEMS</h2>
            </div>
            <div class="ml-16">
              <div class="grid md:grid-cols-2 gap-6">
                <div
                  class="bg-white/15 rounded-xl p-4 border-2 border-emerald-300/30 backdrop-blur-sm"
                >
                  <h4 class="font-bold text-emerald-300 mb-3 flex items-center">
                    <i
                      class="fas fa-envelope text-emerald-400 mr-2"
                      style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                    ></i>
                    Email Best Practices
                  </h4>
                  <ul class="text-sm text-blue-100 space-y-2">
                    <li class="flex items-start">
                      <i
                        class="fas fa-check text-emerald-400 mr-2 mt-1"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                      Use professional language in all communications
                    </li>
                    <li class="flex items-start">
                      <i
                        class="fas fa-check text-emerald-400 mr-2 mt-1"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                      Be cautious with email attachments and links
                    </li>
                    <li class="flex items-start">
                      <i
                        class="fas fa-check text-emerald-400 mr-2 mt-1"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                      Respect confidentiality in all communications
                    </li>
                  </ul>
                </div>

                <div
                  class="bg-white/15 rounded-xl p-4 border-2 border-emerald-300/30 backdrop-blur-sm"
                >
                  <h4 class="font-bold text-emerald-300 mb-3 flex items-center">
                    <i
                      class="fas fa-exclamation-triangle text-emerald-400 mr-2"
                      style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                    ></i>
                    Security Warnings
                  </h4>
                  <ul class="text-sm text-blue-100 space-y-2">
                    <li class="flex items-start">
                      <i
                        class="fas fa-times text-emerald-400 mr-2 mt-1"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                      Do not use hospital email for personal business
                    </li>
                    <li class="flex items-start">
                      <i
                        class="fas fa-times text-emerald-400 mr-2 mt-1"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                      Report suspicious emails or phishing attempts
                    </li>
                    <li class="flex items-start">
                      <i
                        class="fas fa-times text-emerald-400 mr-2 mt-1"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                      Never share login credentials via email
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- 5. Mobile Devices and BYOD -->
        <section class="mb-6">
          <div
            class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 p-6 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
          >
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300"
              >
                <span class="text-white font-bold text-lg">5</span>
              </div>
              <h2 class="text-xl font-bold text-white">MOBILE DEVICES AND BYOD</h2>
            </div>
            <div class="ml-16">
              <p class="text-blue-100 leading-relaxed mb-4">
                Personal devices used for hospital business must meet
                <strong class="text-emerald-300">security requirements</strong>:
              </p>

              <div
                class="bg-white/15 rounded-xl p-6 border-2 border-emerald-300/30 backdrop-blur-sm"
              >
                <div class="grid md:grid-cols-3 gap-4">
                  <div class="text-center">
                    <div
                      class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-3"
                    >
                      <i
                        class="fas fa-mobile-alt text-white"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                    </div>
                    <h4 class="font-semibold text-emerald-300 mb-2">Device Security</h4>
                    <ul class="text-xs text-blue-100 space-y-1">
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Strong authentication
                      </li>
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Security software
                      </li>
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Regular updates
                      </li>
                    </ul>
                  </div>
                  <div class="text-center">
                    <div
                      class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-3"
                    >
                      <i
                        class="fas fa-shield-alt text-white"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                    </div>
                    <h4 class="font-semibold text-emerald-300 mb-2">Data Protection</h4>
                    <ul class="text-xs text-blue-100 space-y-1">
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Separate work/personal data
                      </li>
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Remote wipe capability
                      </li>
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Encrypted storage
                      </li>
                    </ul>
                  </div>
                  <div class="text-center">
                    <div
                      class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-3"
                    >
                      <i
                        class="fas fa-exclamation-circle text-white"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                    </div>
                    <h4 class="font-semibold text-emerald-300 mb-2">Incident Reporting</h4>
                    <ul class="text-xs text-blue-100 space-y-1">
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Report lost devices
                      </li>
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Security breaches
                      </li>
                      <li class="flex items-center justify-center">
                        <i
                          class="fas fa-check text-emerald-400 mr-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Suspicious activity
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- 6. Compliance and Enforcement -->
        <section class="mb-6">
          <div
            class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 p-6 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
          >
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300"
              >
                <span class="text-white font-bold text-lg">6</span>
              </div>
              <h2 class="text-xl font-bold text-white">COMPLIANCE AND ENFORCEMENT</h2>
            </div>
            <div class="ml-16">
              <div class="bg-white/15 rounded-xl p-4 border-2 border-blue-300/30 backdrop-blur-sm">
                <h3 class="text-lg font-bold text-blue-300 mb-4">Policy Violations</h3>
                <p class="text-blue-100 mb-4">
                  Compliance with this policy is
                  <strong class="text-blue-300">mandatory</strong>. Violations may result in:
                </p>

                <div class="grid md:grid-cols-2 gap-6">
                  <div>
                    <h4 class="font-semibold text-blue-300 mb-2">Immediate Actions:</h4>
                    <ul class="text-sm text-blue-100 space-y-1">
                      <li class="flex items-start">
                        <i
                          class="fas fa-exclamation-triangle text-blue-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Suspension of ICT access privileges
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-exclamation-triangle text-blue-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Investigation of incident
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-exclamation-triangle text-blue-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Documentation of violation
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-exclamation-triangle text-blue-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Notification of management
                      </li>
                    </ul>
                  </div>
                  <div>
                    <h4 class="font-semibold text-blue-300 mb-2">Potential Consequences:</h4>
                    <ul class="text-sm text-blue-100 space-y-1">
                      <li class="flex items-start">
                        <i
                          class="fas fa-times text-blue-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Formal disciplinary action
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-times text-blue-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Termination of employment
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-times text-blue-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Legal action where appropriate
                      </li>
                      <li class="flex items-start">
                        <i
                          class="fas fa-times text-blue-400 mr-2 mt-1"
                          style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                        ></i>
                        Reporting to regulatory authorities
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Contact Information -->
        <section class="mb-6">
          <div
            class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 p-6 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
          >
            <h3 class="text-lg font-bold text-white mb-4 flex items-center">
              <i class="fas fa-headset text-blue-400 mr-2"></i>
              ICT Support and Contact Information
            </h3>
            <p class="text-blue-100 mb-4">
              For ICT support, policy questions, or incident reporting:
            </p>
            <div class="grid md:grid-cols-2 gap-4">
              <div class="bg-white/15 rounded-xl p-4 border-2 border-blue-300/30 backdrop-blur-sm">
                <h4 class="font-semibold text-blue-300 mb-2">ICT Help Desk</h4>
                <p class="text-sm text-blue-100">Email: helpdesk@mnh.go.tz</p>
                <p class="text-sm text-blue-100">Phone: +255 22 215 0302 (Ext. 2150)</p>
                <p class="text-sm text-blue-100">Emergency: +255 754 123 456</p>
                <p class="text-sm text-blue-100">Office: ICT Building, Ground Floor</p>
              </div>
              <div class="bg-white/15 rounded-xl p-4 border-2 border-blue-300/30 backdrop-blur-sm">
                <h4 class="font-semibold text-blue-300 mb-2">ICT Management</h4>
                <p class="text-sm text-blue-100">Email: ict.director@mnh.go.tz</p>
                <p class="text-sm text-blue-100">Phone: +255 22 215 0302 (Ext. 2100)</p>
                <p class="text-sm text-blue-100">Office: ICT Building, 2nd Floor</p>
                <p class="text-sm text-blue-100">
                  <strong class="text-blue-300">Office Hours:</strong> Monday - Friday, 8:00 AM -
                  5:00 PM
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- Acknowledgment Section -->
        <section class="mb-0">
          <div
            class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 p-6 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
          >
            <div class="flex items-center mb-4">
              <div
                class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg group-hover:scale-110 transition-transform duration-300"
              >
                <i
                  class="fas fa-file-signature text-white text-lg"
                  style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                ></i>
              </div>
              <h2 class="text-xl font-bold text-white">POLICY ACKNOWLEDGMENT</h2>
            </div>

            <!-- Two Column Layout -->
            <div class="grid md:grid-cols-2 gap-8 ml-16">
              <!-- Left Column: Policy Description -->
              <div>
                <p class="text-blue-100 mb-6 leading-relaxed">
                  By using hospital ICT systems, you confirm that you understand and agree to comply
                  with this ICT Policy and all related procedures.
                </p>

                <!-- Checkbox for Agreement -->
                <div
                  class="bg-white/15 rounded-xl p-4 shadow-md border-2 border-blue-300/30 backdrop-blur-sm"
                >
                  <label class="flex items-start cursor-pointer">
                    <span class="text-blue-100 leading-relaxed">
                      <strong class="text-white">I have read and accept the ICT Policy</strong
                      ><br />
                      <span class="text-blue-200 text-sm">
                        I agree to comply with all policy requirements and understand that
                        violations may result in disciplinary action or legal consequences.
                      </span>
                    </span>
                  </label>
                </div>
              </div>

              <!-- Right Column: Ready to Proceed -->
              <div class="flex flex-col justify-center">
                <!-- Timer Message (when active) -->
                <div
                  v-if="isTimerActive"
                  class="bg-gradient-to-r from-amber-500/20 to-orange-500/20 rounded-xl p-6 border-2 border-amber-400/40 backdrop-blur-sm text-center mb-4 animate-pulse"
                >
                  <div class="flex items-center justify-center mb-3">
                    <div
                      class="w-12 h-12 bg-gradient-to-r from-amber-500 to-orange-500 rounded-full flex items-center justify-center mr-3 animate-spin"
                    >
                      <i class="fas fa-clock text-white text-lg"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Please Wait</h3>
                  </div>
                  <p class="text-amber-100 text-sm mb-4">
                    You must read the ICT policy for at least
                    <span class="font-bold text-amber-200">{{ readingTimer }}</span>
                    seconds before proceeding
                  </p>
                  <div class="w-full bg-amber-900/30 rounded-full h-2 mb-3">
                    <div
                      class="bg-gradient-to-r from-amber-400 to-orange-400 h-2 rounded-full transition-all duration-1000"
                      :style="{ width: ((5 - readingTimer) / 5) * 100 + '%' }"
                    ></div>
                  </div>
                  <p class="text-amber-200 text-xs">
                    Please take time to carefully review all policy sections
                  </p>
                </div>

                <!-- Ready to Proceed (when timer is done) -->
                <div
                  v-else
                  class="bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-xl p-6 border-2 border-blue-400/40 backdrop-blur-sm text-center"
                >
                  <div class="flex items-center justify-center mb-3">
                    <div
                      class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mr-3"
                    >
                      <i
                        class="fas fa-check text-white text-lg"
                        style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                      ></i>
                    </div>
                    <h3 class="text-lg font-bold text-white">Ready to Proceed</h3>
                  </div>
                  <p class="text-blue-100 text-sm mb-4">
                    You have completed the required reading time. Select the checkbox to proceed.
                  </p>

                  <button
                    @click="proceedWithAcknowledgment"
                    :disabled="!acknowledged || !canProceed"
                    :class="[
                      'w-full px-6 py-4 rounded-xl font-semibold transition-all duration-300 border-2',
                      acknowledged && canProceed
                        ? 'bg-gradient-to-r from-blue-600 to-cyan-600 text-white hover:from-blue-700 hover:to-cyan-700 border-blue-500/50 shadow-xl hover:shadow-2xl transform hover:scale-105'
                        : 'bg-gray-600 text-gray-300 cursor-not-allowed border-gray-500/50'
                    ]"
                  >
                    <i
                      class="fas fa-arrow-right mr-2"
                      style="font-family: 'Font Awesome 6 Free'; font-weight: 900"
                    ></i>
                    Accept Policy & Proceed
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Footer -->
      <div class="text-center mt-8">
        <div
          class="inline-block bg-white/10 backdrop-blur-sm border border-blue-300/30 px-6 py-3 rounded-xl shadow-lg"
        >
          <p class="text-sm text-blue-100">
            © 2025 Muhimbili National Hospital - ICT Department | Policy effective as of publication
            date
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'IctPolicy',
    data() {
      return {
        acknowledged: false,
        readingTimer: 5,
        isTimerActive: false,
        canProceed: false,
        timerInterval: null,
        isMounted: false
      }
    },
    methods: {
      getIconStyle(index) {
        // Use index to create deterministic but varied positions
        const seed = (index * 137.508) % 10000 // Prevent large numbers
        return {
          left: Math.abs((seed * 7) % 100) + '%',
          top: Math.abs((seed * 11) % 100) + '%',
          animationDelay: Math.abs((seed * 3) % 3) + 's',
          animationDuration: Math.abs((seed * 2) % 3) + 2 + 's',
          fontSize: Math.abs((seed * 5) % 20) + 10 + 'px'
        }
      },
      getIconClass(index) {
        const icons = ['fa-heartbeat', 'fa-user-md', 'fa-hospital', 'fa-stethoscope', 'fa-plus']
        return ['fas', icons[index % icons.length]]
      },
      startReadingTimer() {
        // Clear any existing timer first
        if (this.timerInterval) {
          clearInterval(this.timerInterval)
        }

        this.isTimerActive = true
        this.canProceed = false
        this.readingTimer = 5

        this.timerInterval = setInterval(() => {
          this.readingTimer--
          if (this.readingTimer <= 0) {
            clearInterval(this.timerInterval)
            this.timerInterval = null
            this.isTimerActive = false
            this.canProceed = true
          }
        }, 1000)
      },
      proceedWithAcknowledgment() {
        if (this.acknowledged && this.canProceed) {
          // Store acknowledgment in localStorage with timestamp
          const acknowledgment = {
            acknowledged: true,
            timestamp: new Date().toISOString(),
            version: '2.1'
          }
          localStorage.setItem('ictPolicyAcknowledged', JSON.stringify(acknowledgment))

          // Show success message
          this.showNotification(
            'ICT Policy accepted successfully! You may now proceed with your access request.',
            'success'
          )

          // Emit event to parent component or redirect
          this.$emit('policy-acknowledged', acknowledgment)

          // Optional: Close modal or redirect to form
          // this.$router.push('/access-request')
        }
      },

      showNotification(message, type = 'info') {
        // Simple notification system
        const colors = {
          success: 'green',
          error: 'red',
          info: 'blue'
        }

        const toast = document.createElement('div')
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-semibold z-50 bg-${colors[type]}-600 shadow-lg`
        toast.textContent = message
        document.body.appendChild(toast)

        setTimeout(() => {
          if (document.body.contains(toast)) {
            document.body.removeChild(toast)
          }
        }, 5000)
      },

      checkExistingAcknowledgment() {
        // Check if user has already acknowledged this version
        const stored = localStorage.getItem('ictPolicyAcknowledged')
        if (stored) {
          try {
            const acknowledgment = JSON.parse(stored)
            if (acknowledgment.version === '2.1' && acknowledgment.acknowledged) {
              this.acknowledged = true
            }
          } catch (e) {
            // Invalid stored data, ignore
          }
        }
      }
    },
    mounted() {
      try {
        if (this.isMounted) return // Prevent double mounting
        this.isMounted = true

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth'

        // Check for existing acknowledgment
        this.checkExistingAcknowledgment()

        // Start reading timer
        this.startReadingTimer()
      } catch (error) {
        console.error('Error during component mounting:', error)
      }
    },
    beforeUnmount() {
      try {
        this.isMounted = false

        // Clean up timer
        if (this.timerInterval) {
          clearInterval(this.timerInterval)
          this.timerInterval = null
        }

        // Reset scroll behavior
        document.documentElement.style.scrollBehavior = 'auto'
      } catch (error) {
        console.error('Error during component unmounting:', error)
      }
    }
  }
</script>

<style scoped>
  /* Custom font imports */
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

  /* Apply custom font */
  * {
    font-family:
      'Inter',
      -apple-system,
      BlinkMacSystemFont,
      'Segoe UI',
      Roboto,
      sans-serif;
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

  /* Animations */
  @keyframes float {
    0%,
    100% {
      transform: translateY(0px) translateZ(0);
    }
    50% {
      transform: translateY(-20px) translateZ(0);
    }
  }

  .animate-float {
    animation: float 8s ease-in-out infinite;
    will-change: transform;
    backface-visibility: hidden;
  }

  /* Compact hover effects */
  .bg-white:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease;
  }

  /* Custom scrollbar */
  ::-webkit-scrollbar {
    width: 8px;
  }

  ::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }

  ::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #3b82f6, #1d4ed8);
    border-radius: 4px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #1e40af);
  }

  /* Responsive design */
  @media (max-width: 768px) {
    .lg\:grid-cols-2 {
      grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .text-2xl {
      font-size: 1.5rem;
    }

    .text-xl {
      font-size: 1.125rem;
    }
  }

  /* Print styles */
  @media print {
    .min-h-screen {
      min-height: auto;
    }

    .bg-gradient-to-br,
    .bg-gradient-to-r {
      background: white !important;
      color: black !important;
    }

    .shadow-xl,
    .shadow-lg {
      box-shadow: none !important;
    }

    .rounded-2xl,
    .rounded-xl {
      border-radius: 8px !important;
    }
  }

  /* Accessibility improvements */
  @media (prefers-reduced-motion: reduce) {
    .transform:hover {
      transform: none;
    }

    .animate-float,
    .animate-pulse,
    .animate-spin,
    .animate-bounce {
      animation: none !important;
    }
  }

  /* Focus styles for accessibility */
  button:focus,
  a:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
  }
</style>
"
