<template>
  <aside
    class="h-full flex flex-col transition-all duration-300 ease-in-out overflow-hidden relative shadow-2xl sidebar-responsive"
    :class="[
      isCollapsed ? 'w-16 sidebar-collapsed' : 'sidebar-expanded',
      !shouldShowSidebar ? 'invisible pointer-events-none opacity-0' : 'opacity-100'
    ]"
    :style="!isCollapsed ? 'width: 16rem;' : ''"
    aria-label="Sidebar navigation"
    style="
      background: linear-gradient(
        135deg,
        #1e3a8a 0%,
        #1e40af 25%,
        #1d4ed8 50%,
        #1e40af 75%,
        #1e3a8a 100%
      );
      box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.1);
      border-right: 1px solid rgba(59, 130, 246, 0.4);
    "
    :aria-hidden="!shouldShowSidebar"
  >
    <!-- Enhanced Background Layers -->
    <div class="absolute inset-0">
      <!-- Primary shadow layer -->
      <div
        class="absolute inset-0 bg-gradient-to-br from-blue-900/20 via-transparent to-blue-800/30 rounded-2xl m-1"
      ></div>
      <!-- Decorative Border -->
      <div class="absolute inset-0 border-2 border-dashed border-blue-300/20 rounded-2xl m-2"></div>
      <!-- Inner glow -->
      <div
        class="absolute inset-0 bg-gradient-to-t from-transparent via-blue-400/5 to-blue-300/10 rounded-2xl m-3"
      ></div>
    </div>

    <!-- Main Content Container -->
    <div class="relative z-10 flex flex-col h-full">
      <!-- Header with Menu Icon and Profile -->
      <div class="mb-6 px-4 pt-4">
        <!-- Menu Icon and Brand -->
        <div class="flex items-center justify-between mb-4">
          <!-- Menu Toggle Button -->
          <button
            @click="toggleCollapse"
            class="menu-toggle-btn p-2 rounded-lg transition-all duration-300 hover:bg-blue-600/60 text-white group relative overflow-hidden"
            :aria-label="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          >
            <!-- Animated Menu Icon -->
            <div class="relative w-6 h-6">
              <!-- Top line -->
              <span
                class="absolute left-0 top-1 w-6 h-0.5 bg-white rounded-full transition-all duration-300 transform origin-center"
                :class="{
                  'rotate-45 translate-y-2': isCollapsed,
                  'rotate-0 translate-y-0': !isCollapsed
                }"
              ></span>
              <!-- Middle line -->
              <span
                class="absolute left-0 top-3 w-6 h-0.5 bg-white rounded-full transition-all duration-300"
                :class="{
                  'opacity-0 scale-0': isCollapsed,
                  'opacity-100 scale-100': !isCollapsed
                }"
              ></span>
              <!-- Bottom line -->
              <span
                class="absolute left-0 top-5 w-6 h-0.5 bg-white rounded-full transition-all duration-300 transform origin-center"
                :class="{
                  '-rotate-45 -translate-y-2': isCollapsed,
                  'rotate-0 translate-y-0': !isCollapsed
                }"
              ></span>
            </div>
            <!-- Hover effect -->
            <div
              class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"
            ></div>
          </button>

          <!-- Brand/Logo (when expanded) -->
          <div
            class="transition-all duration-300 ease-in-out overflow-hidden"
            :class="{
              'w-0 opacity-0': isCollapsed,
              'w-auto opacity-100': !isCollapsed
            }"
          >
            <div class="flex items-center space-x-2">
              <div
                class="w-8 h-8 bg-gradient-to-br from-blue-500/30 to-blue-600/30 rounded-lg backdrop-blur-sm border border-blue-400/50 flex items-center justify-center shadow-lg"
              >
                <i class="fas fa-hospital text-white text-sm"></i>
              </div>
              <span class="text-white font-bold responsive-brand-text tracking-wide">MNH</span>
            </div>
          </div>
        </div>

        <!-- User Profile Section -->
        <div class="transition-all duration-500 ease-in-out">
          <!-- Expanded Profile -->
          <div
            v-if="!isCollapsed"
            class="profile-expanded opacity-100 transform translate-y-0 transition-all duration-500 ease-out"
          >
            <div
              class="flex items-center space-x-3 p-3 rounded-xl bg-blue-600/60 border border-blue-500/40 backdrop-blur-sm hover:bg-blue-600/80 transition-all duration-300 group cursor-pointer"
            >
              <!-- User Avatar -->
              <div class="relative">
                <div
                  class="w-14 h-14 rounded-full bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg border-2 border-white/20 group-hover:scale-110 transition-transform duration-300 relative overflow-hidden"
                >
                  <!-- Avatar background layers -->
                  <div
                    class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent"
                  ></div>
                  <!-- Actual profile photo if available -->
                  <img
                    v-if="
                      currentUser?.value?.profile_photo_url ||
                      piniaAuthStore?.user?.profile_photo_url
                    "
                    :src="
                      currentUser?.value?.profile_photo_url ||
                      piniaAuthStore?.user?.profile_photo_url
                    "
                    alt="Profile photo"
                    class="w-full h-full object-cover rounded-full relative z-10"
                  />
                  <!-- Fallback to initials -->
                  <span v-else class="text-white font-bold text-lg relative z-10 drop-shadow-lg">{{
                    userInitials
                  }}</span>
                  <!-- Online indicator -->
                  <div
                    class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white shadow-sm animate-pulse"
                  ></div>
                </div>
                <!-- Avatar glow -->
                <div
                  class="absolute inset-0 bg-orange-500/20 rounded-full blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-300 -z-10"
                ></div>
              </div>

              <!-- User Info -->
              <div class="flex-1 min-w-0">
                <h3
                  class="text-white font-bold responsive-profile-name truncate group-hover:text-orange-100 transition-colors duration-300 text-sm"
                >
                  {{ userName || 'JOHN DOE' }}
                </h3>
                <p
                  class="text-blue-100 responsive-profile-role truncate opacity-80 group-hover:opacity-100 transition-opacity duration-300 text-xs"
                >
                  {{ userDepartmentOrRole }}
                </p>
              </div>

              <!-- Profile chevron -->
              <div
                class="w-5 h-5 text-blue-200 group-hover:text-white transition-colors duration-300"
              >
                <svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                  />
                </svg>
              </div>
            </div>
          </div>

          <!-- Collapsed Profile -->
          <div
            v-else
            class="profile-collapsed opacity-100 transform translate-y-0 transition-all duration-500 ease-out flex justify-center"
          >
            <div class="relative group cursor-pointer">
              <div
                class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg border-2 border-white/20 group-hover:scale-110 transition-transform duration-300 relative overflow-hidden"
              >
                <!-- Avatar background layers -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent"></div>
                <!-- Actual profile photo if available -->
                <img
                  v-if="
                    currentUser?.value?.profile_photo_url || piniaAuthStore?.user?.profile_photo_url
                  "
                  :src="
                    currentUser?.value?.profile_photo_url || piniaAuthStore?.user?.profile_photo_url
                  "
                  alt="Profile photo"
                  class="w-full h-full object-cover rounded-full relative z-10"
                />
                <!-- Fallback to initials -->
                <span
                  v-else
                  class="text-white font-bold responsive-profile-name relative z-10 drop-shadow-lg"
                  >{{ userInitials }}</span
                >
                <!-- Online indicator -->
                <div
                  class="absolute bottom-0 right-0 w-2 h-2 bg-green-400 rounded-full border border-white shadow-sm animate-pulse"
                ></div>
              </div>
              <!-- Avatar glow -->
              <div
                class="absolute inset-0 bg-orange-500/20 rounded-full blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-300 -z-10"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="flex-1 min-h-0 space-y-1 overflow-y-auto custom-scrollbar px-4">
        <!-- Dashboard Section -->
        <div v-if="filteredDashboardItems.length > 0" class="mb-2">
          <div
            v-if="!isCollapsed"
            class="flex items-center justify-between px-4 py-2 responsive-section-header font-bold text-blue-200 uppercase tracking-wider cursor-pointer hover:text-white transition-colors duration-200 section-header text-xs"
            @click="toggleSectionLocal('dashboard')"
          >
            <span>Dashboard</span>
            <svg
              class="w-4 h-4 transition-transform duration-200"
              :class="{ 'rotate-180': !expandedSections.dashboard }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </div>

          <div
            class="space-y-1 transition-all duration-300 ease-in-out overflow-hidden"
            :class="{
              'max-h-0 opacity-0': !isCollapsed && !expandedSections.dashboard && !debugHeadOfIt,
              'max-h-96 opacity-100': isCollapsed || expandedSections.dashboard || debugHeadOfIt
            }"
            :style="{
              maxHeight: isCollapsed || expandedSections.dashboard || debugHeadOfIt ? '24rem' : '0',
              opacity: isCollapsed || expandedSections.dashboard || debugHeadOfIt ? '1' : '0'
            }"
          >
            <router-link
              v-for="item in filteredDashboardItems"
              :key="item.path"
              :to="item.path"
              class="group flex items-center px-4 py-3 responsive-nav-item font-medium rounded-lg transition-all duration-200 relative ml-2 shadow-sm nav-item text-xs"
              :class="[
                route.path === item.path
                  ? 'bg-white text-blue-600 shadow-lg transform scale-105'
                  : 'text-white hover:bg-blue-600/60 hover:text-white hover:shadow-md hover:transform hover:scale-102',
                isCollapsed ? 'justify-center ml-0' : ''
              ]"
              v-tooltip="isCollapsed ? item.displayName : ''"
            >
              <div
                class="flex items-center justify-center responsive-icon-small transition-colors duration-200 relative"
                :class="isCollapsed ? '' : 'mr-3'"
              >
                <i :class="[item.icon, 'text-current']"></i>
                <!-- Notification Badge for collapsed state -->
                <div
                  v-if="getNotificationCount(item.path) > 0 && isCollapsed"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1rem] h-4 flex items-center justify-center px-1 shadow-lg border border-white notification-badge animate-pulse"
                  style="z-index: 999 !important"
                  :title="`Debug: ${getNotificationCount(item.path)} notifications for ${item.path}`"
                >
                  {{ getNotificationCount(item.path) }}
                </div>
              </div>
              <span v-if="!isCollapsed" class="truncate uppercase tracking-wide">{{
                item.displayName.toUpperCase()
              }}</span>
              <!-- Notification Badge -->
              <div
                v-if="getNotificationCount(item.path) > 0 && !isCollapsed"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 shadow-lg border-2 border-white notification-badge animate-pulse"
                style="z-index: 999 !important"
                :title="`Debug: ${getNotificationCount(item.path)} notifications for ${item.path}`"
              >
                {{ getNotificationCount(item.path) }}
              </div>
              <!-- Active indicator -->
              <div
                v-if="
                  route.path === item.path && !isCollapsed && getNotificationCount(item.path) === 0
                "
                class="absolute right-2 w-2 h-2 bg-blue-600 rounded-full shadow-sm active-indicator"
              ></div>
            </router-link>
          </div>
        </div>

        <!-- User Management Section (Admin only) -->
        <div v-if="filteredUserManagementItems.length > 0" class="mb-2">
          <div
            v-if="!isCollapsed"
            class="flex items-center justify-between px-4 py-2 responsive-section-header font-bold text-blue-200 uppercase tracking-wider cursor-pointer hover:text-white transition-colors duration-200 section-header text-xs"
            @click="toggleSectionLocal('userManagement')"
          >
            <span>User Management</span>
            <svg
              class="w-4 h-4 transition-transform duration-200"
              :class="{ 'rotate-180': !expandedSections.userManagement }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </div>

          <div
            class="space-y-1 transition-all duration-300 ease-in-out overflow-hidden"
            :class="{
              'max-h-0 opacity-0': !isCollapsed && !expandedSections.userManagement,
              'max-h-96 opacity-100': isCollapsed || expandedSections.userManagement
            }"
            :style="{
              maxHeight: isCollapsed || expandedSections.userManagement ? '24rem' : '0',
              opacity: isCollapsed || expandedSections.userManagement ? '1' : '0'
            }"
          >
            <router-link
              v-for="item in filteredUserManagementItems"
              :key="item.path"
              :to="item.path"
              class="group flex items-center px-4 py-3 responsive-nav-item font-medium rounded-lg transition-all duration-200 relative ml-2 shadow-sm nav-item text-xs"
              :class="[
                route.path === item.path
                  ? 'bg-white text-blue-600 shadow-lg transform scale-105'
                  : 'text-white hover:bg-blue-600/60 hover:text-white hover:shadow-md hover:transform hover:scale-102',
                isCollapsed ? 'justify-center ml-0' : ''
              ]"
              v-tooltip="isCollapsed ? item.displayName : ''"
            >
              <div
                class="flex items-center justify-center responsive-icon-small transition-colors duration-200 relative"
                :class="isCollapsed ? '' : 'mr-3'"
              >
                <i :class="[item.icon, 'text-current']"></i>
                <!-- Notification Badge for collapsed state -->
                <div
                  v-if="getNotificationCount(item.path) > 0 && isCollapsed"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1rem] h-4 flex items-center justify-center px-1 shadow-lg border border-white notification-badge animate-pulse"
                >
                  {{ getNotificationCount(item.path) }}
                </div>
              </div>
              <span v-if="!isCollapsed" class="truncate uppercase tracking-wide">{{
                item.displayName.toUpperCase()
              }}</span>
              <!-- Notification Badge -->
              <div
                v-if="getNotificationCount(item.path) > 0 && !isCollapsed"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 shadow-lg border-2 border-white notification-badge animate-pulse"
              >
                {{ getNotificationCount(item.path) }}
              </div>
              <!-- Active indicator -->
              <div
                v-if="
                  route.path === item.path && !isCollapsed && getNotificationCount(item.path) === 0
                "
                class="absolute right-2 w-2 h-2 bg-blue-600 rounded-full shadow-sm active-indicator"
              ></div>
            </router-link>
          </div>
        </div>

        <!-- Requests Management Section -->
        <div v-if="filteredRequestsManagementItems.length > 0" class="mb-2">
          <div
            v-if="!isCollapsed"
            class="flex items-center justify-between px-4 py-2 responsive-section-header font-bold text-blue-200 uppercase tracking-wider cursor-pointer hover:text-white transition-colors duration-200 section-header text-xs"
            @click="toggleSectionLocal('requestsManagement')"
          >
            <span>Requests</span>
            <svg
              class="w-4 h-4 transition-transform duration-200"
              :class="{ 'rotate-180': !expandedSections.requestsManagement }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </div>

          <div
            class="space-y-1 transition-all duration-300 ease-in-out overflow-hidden"
            :class="{
              'max-h-0 opacity-0':
                !isCollapsed && !expandedSections.requestsManagement && !debugHeadOfIt,
              'max-h-96 opacity-100':
                isCollapsed || expandedSections.requestsManagement || debugHeadOfIt
            }"
            :style="{
              maxHeight:
                isCollapsed || expandedSections.requestsManagement || debugHeadOfIt ? '24rem' : '0',
              opacity:
                isCollapsed || expandedSections.requestsManagement || debugHeadOfIt ? '1' : '0'
            }"
          >
            <router-link
              v-for="item in filteredRequestsManagementItems"
              :key="item.path"
              :to="item.path"
              class="group flex items-center px-4 py-3 responsive-nav-item font-medium rounded-lg transition-all duration-200 relative ml-2 shadow-sm nav-item text-xs"
              :class="[
                route.path === item.path
                  ? 'bg-white text-blue-600 shadow-lg transform scale-105'
                  : 'text-white hover:bg-blue-600/60 hover:text-white hover:shadow-md hover:transform hover:scale-102',
                isCollapsed ? 'justify-center ml-0' : ''
              ]"
              v-tooltip="isCollapsed ? item.displayName : ''"
            >
              <div
                class="flex items-center justify-center responsive-icon-small transition-colors duration-200 relative"
                :class="isCollapsed ? '' : 'mr-3'"
              >
                <i :class="[item.icon, 'text-current']"></i>
                <!-- Notification Badge for collapsed state -->
                <div
                  v-if="getNotificationCount(item.path) > 0 && isCollapsed"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1rem] h-4 flex items-center justify-center px-1 shadow-lg border border-white notification-badge animate-pulse"
                >
                  {{ getNotificationCount(item.path) }}
                </div>
              </div>
              <span v-if="!isCollapsed" class="truncate uppercase tracking-wide">{{
                item.displayName.toUpperCase()
              }}</span>
              <!-- Notification Badge -->
              <div
                v-if="getNotificationCount(item.path) > 0 && !isCollapsed"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 shadow-lg border-2 border-white notification-badge animate-pulse"
              >
                {{ getNotificationCount(item.path) }}
              </div>
              <!-- Active indicator -->
              <div
                v-if="
                  route.path === item.path && !isCollapsed && getNotificationCount(item.path) === 0
                "
                class="absolute right-2 w-2 h-2 bg-blue-600 rounded-full shadow-sm active-indicator"
              ></div>
            </router-link>
          </div>
        </div>

        <!-- Device Management Section (ICT Officer only) -->
        <div v-if="filteredDeviceManagementItems.length > 0" class="mb-2">
          <div
            v-if="!isCollapsed"
            class="flex items-center justify-between px-4 py-2 responsive-section-header font-bold text-blue-200 uppercase tracking-wider cursor-pointer hover:text-white transition-colors duration-200 section-header text-xs"
            @click="toggleSectionLocal('deviceManagement')"
          >
            <span>Device Management</span>
            <svg
              class="w-4 h-4 transition-transform duration-200"
              :class="{ 'rotate-180': !expandedSections.deviceManagement }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </div>

          <div
            class="space-y-1 transition-all duration-300 ease-in-out overflow-hidden"
            :class="{
              'max-h-0 opacity-0': !isCollapsed && !expandedSections.deviceManagement,
              'max-h-96 opacity-100': isCollapsed || expandedSections.deviceManagement
            }"
            :style="{
              maxHeight: isCollapsed || expandedSections.deviceManagement ? '24rem' : '0',
              opacity: isCollapsed || expandedSections.deviceManagement ? '1' : '0'
            }"
          >
            <router-link
              v-for="item in filteredDeviceManagementItems"
              :key="item.path"
              :to="item.path"
              class="group flex items-center px-4 py-3 responsive-nav-item font-medium rounded-lg transition-all duration-200 relative ml-2 shadow-sm nav-item text-xs"
              :class="[
                route.path === item.path
                  ? 'bg-white text-blue-600 shadow-lg transform scale-105'
                  : 'text-white hover:bg-blue-600/60 hover:text-white hover:shadow-md hover:transform hover:scale-102',
                isCollapsed ? 'justify-center ml-0' : ''
              ]"
              v-tooltip="isCollapsed ? item.displayName : ''"
            >
              <div
                class="flex items-center justify-center responsive-icon-small transition-colors duration-200 relative"
                :class="isCollapsed ? '' : 'mr-3'"
              >
                <i :class="[item.icon, 'text-current']"></i>
                <!-- Notification Badge for collapsed state -->
                <div
                  v-if="getNotificationCount(item.path) > 0 && isCollapsed"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1rem] h-4 flex items-center justify-center px-1 shadow-lg border border-white notification-badge animate-pulse"
                >
                  {{ getNotificationCount(item.path) }}
                </div>
              </div>
              <span v-if="!isCollapsed" class="truncate uppercase tracking-wide">{{
                item.displayName.toUpperCase()
              }}</span>
              <!-- Notification Badge -->
              <div
                v-if="getNotificationCount(item.path) > 0 && !isCollapsed"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 shadow-lg border-2 border-white notification-badge animate-pulse"
              >
                {{ getNotificationCount(item.path) }}
              </div>
              <!-- Active indicator -->
              <div
                v-if="
                  route.path === item.path && !isCollapsed && getNotificationCount(item.path) === 0
                "
                class="absolute right-2 w-2 h-2 bg-blue-600 rounded-full shadow-sm active-indicator"
              ></div>
            </router-link>
          </div>
        </div>
      </nav>

      <!-- Bottom Section -->
      <div
        class="bottom-section space-y-3 pt-4 pb-4 px-4 border-t border-blue-400/30"
        style="flex-shrink: 0; margin-top: auto"
      >
        <!-- Help Center -->
        <button
          @click="showHelp"
          class="w-full flex items-center px-4 py-3 responsive-bottom-section font-medium rounded-lg transition-all duration-200 text-white hover:bg-blue-600/60"
          :class="isCollapsed ? 'justify-center' : ''"
          v-tooltip="isCollapsed ? 'Help Center' : ''"
        >
          <div
            class="flex items-center justify-center responsive-icon-small transition-colors duration-200"
            :class="isCollapsed ? '' : 'mr-3'"
          >
            <svg
              class="responsive-icon-small text-current"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
          <span
            v-if="!isCollapsed"
            class="truncate uppercase tracking-wide responsive-bottom-section"
            >HELP CENTER</span
          >
        </button>

        <!-- Logout -->
        <button
          @click="handleLogout"
          class="w-full flex items-center px-4 py-3 responsive-bottom-section font-medium rounded-lg transition-all duration-200 text-white hover:bg-blue-600/60"
          :class="isCollapsed ? 'justify-center' : ''"
          v-tooltip="isCollapsed ? 'Log Out' : ''"
        >
          <div
            class="flex items-center justify-center responsive-icon-small transition-colors duration-200"
            :class="isCollapsed ? '' : 'mr-3'"
          >
            <svg
              class="responsive-icon-small text-current"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
          </div>
          <span
            v-if="!isCollapsed"
            class="truncate uppercase tracking-wide responsive-bottom-section"
            >LOG OUT</span
          >
        </button>
      </div>
    </div>

    <!-- Help Modal (teleported to body to prevent backdrop covering sidebar) -->
    <Teleport to="body">
      <div
        v-if="showHelpModal"
        class="fixed inset-0 bg-black/60 flex items-center justify-center z-[10001] backdrop-blur-sm"
        @click="showHelpModal = false"
      >
        <div
          class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 scale-100 overflow-hidden max-h-[85vh] overflow-y-auto"
          @click.stop
        >
          <!-- Header -->
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-center shadow-lg">
            <div
              class="w-16 h-16 bg-blue-500/30 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm border border-blue-300/40 shadow-lg"
            >
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-1">Help & Support</h3>
            <p class="text-blue-100 text-sm">Quick help based on your role</p>
          </div>

          <!-- Body -->
          <div class="p-6">
            <div class="space-y-4">
              <div
                class="flex items-center p-4 rounded-lg shadow-sm border bg-blue-50 border-blue-100"
              >
                <div
                  class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3 shadow-md"
                >
                  <svg
                    class="w-5 h-5 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                    />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-800">ICT Support</p>
                  <a class="text-sm text-gray-600 hover:text-blue-700" href="tel:+255222215701">
                    +255222215701
                  </a>
                </div>
              </div>

              <div
                class="flex items-center p-4 rounded-lg shadow-sm border bg-blue-50 border-blue-100"
              >
                <div
                  class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3 shadow-md"
                >
                  <svg
                    class="w-5 h-5 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    />
                  </svg>
                </div>
                <div>
                  <p class="font-medium text-gray-800">Email Support</p>
                  <a class="text-sm text-gray-600 hover:text-blue-700" href="mailto:ict@mnh.or.tz">
                    ict@mnh.or.tz
                  </a>
                </div>
              </div>

              <!-- User Guide (Role-based dropdown) -->
              <div class="rounded-lg shadow-sm border bg-blue-50 border-blue-100">
                <button
                  type="button"
                  @click="showUserGuide = !showUserGuide"
                  class="w-full flex items-center p-4 text-left"
                >
                  <div
                    class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-3 shadow-md flex-shrink-0"
                  >
                    <svg
                      class="w-5 h-5 text-white"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                      />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800">User Guide</p>
                    <p class="text-sm text-gray-600">Access system documentation</p>
                  </div>
                  <div
                    class="w-8 h-8 rounded-lg bg-white/80 border border-blue-100 flex items-center justify-center"
                  >
                    <svg
                      class="w-4 h-4 text-blue-700 transition-transform duration-200"
                      :class="showUserGuide ? 'rotate-180' : ''"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                      />
                    </svg>
                  </div>
                </button>

                <transition
                  enter-active-class="transition duration-200 ease-out"
                  enter-from-class="transform opacity-0 -translate-y-1"
                  enter-to-class="transform opacity-100 translate-y-0"
                  leave-active-class="transition duration-150 ease-in"
                  leave-from-class="transform opacity-100 translate-y-0"
                  leave-to-class="transform opacity-0 -translate-y-1"
                >
                  <div v-if="showUserGuide" class="px-4 pb-4">
                    <div class="bg-white rounded-xl border border-blue-100 p-4">
                      <p class="text-xs text-gray-500 mb-2">
                        Showing guidance for:
                        <span class="font-medium">{{ userGuideRoleLabel }}</span>
                      </p>
                      <div class="space-y-3">
                        <div v-for="(section, idx) in userGuideSections" :key="idx">
                          <p class="text-sm font-semibold text-gray-800 mb-1">
                            {{ section.title }}
                          </p>
                          <ul class="list-disc pl-5 text-sm text-gray-700 space-y-1">
                            <li v-for="(item, i) in section.items" :key="i">{{ item }}</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>

            <!-- Close Button -->
            <div class="mt-6">
              <button
                @click="showHelpModal = false"
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-xl font-medium hover:bg-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </aside>
</template>

<script>
  import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
  import { useRouter, useRoute } from 'vue-router'
  import { ROLES, getAllowedRoutes } from '../utils/permissions'
  import { useAuth } from '../composables/useAuth'
  import { useSidebar } from '../composables/useSidebar'
  import { useAuthStore } from '../stores/auth'
  import auth from '../utils/auth'
  import { logoutGuard } from '@/utils/logoutGuard'

  export default {
    name: 'ModernSidebar',
    // Remove props and emits since we're using Vuex now
    // props: {
    //   collapsed: {
    //     type: Boolean,
    //     default: false
    //   }
    // },
    // emits: ['update:collapsed'],
    setup() {
      const router = useRouter()
      const route = useRoute()
      const { user: currentUser, userRole, logout, isAuthenticated, isLoading } = useAuth()
      const {
        isCollapsed,
        expandedSections,
        toggleSidebar,
        toggleSection,
        setSectionExpanded: _setSectionExpanded,
        setCollapsed
      } = useSidebar()

      // Use Pinia auth store for additional reliability
      const piniaAuthStore = useAuthStore()
      const DEBUG = process.env.NODE_ENV === 'development'

      // Ensure Pinia auth is initialized
      if (!piniaAuthStore.isInitialized) {
        piniaAuthStore.initializeAuth()
      }

      // Local state
      const stableUserRole = ref(null)
      const searchQuery = ref('')
      const showHelpModal = ref(false)
      const showUserGuide = ref(false)
      // expandedSections now comes from useSidebar composable

      // Debug logging for Head of IT
      const debugHeadOfIt = computed(() => {
        const role = piniaAuthStore?.userRole || userRole?.value || stableUserRole?.value
        const isHeadOfIt = role === ROLES.HEAD_OF_IT
        if (isHeadOfIt) {
          if (DEBUG)
            console.log('🔧 HEAD OF IT Debug:', {
              role,
              menuItems: menuItems.value.length,
              dashboardItems: dashboardItems.value.length,
              requestsItems: requestsManagementItems.value.length,
              expandedSections: expandedSections.value
            })
        }
        return isHeadOfIt
      })

      // Responsive behavior
      const isSmallScreen = ref(window.innerWidth < 768)

      // Watch for screen size changes
      const handleResize = () => {
        isSmallScreen.value = window.innerWidth < 768
        if (isSmallScreen.value && !isCollapsed.value) {
          setCollapsed(true)
        }
      }

      // Watch for authentication state changes
      watch(
        [isAuthenticated, userRole],
        ([authenticated, role]) => {
          try {
            if (DEBUG) console.log('🔄 Sidebar: Auth state changed:', { authenticated, role })
            if (authenticated && role) {
              stableUserRole.value = role
              if (DEBUG) console.log('✅ Sidebar: Stable user role set to:', role)
            }
          } catch (error) {
            console.warn('Error in auth state watcher:', error)
          }
        },
        { immediate: true }
      )

      // Initialize auth state and setup event listeners on mount
      onMounted(async () => {
        // Setup resize listener
        window.addEventListener('resize', handleResize)
        handleResize() // Initial check

        // Initialize auth state
        const token = localStorage.getItem('auth_token')
        const userData = localStorage.getItem('user_data')

        if (token && userData) {
          try {
            const user = JSON.parse(userData)
            if (user.role) {
              stableUserRole.value = user.role
            }
          } catch (error) {
            console.error('Failed to parse stored user data:', error)
          }

          if (!isAuthenticated?.value && !isLoading?.value) {
            await nextTick()
            auth.initializeAuth()
          }
        }

        if (isAuthenticated?.value && userRole?.value) {
          stableUserRole.value = userRole.value

          // Auto-expand sections for Head of IT
          if (userRole.value === ROLES.HEAD_OF_IT) {
            if (DEBUG) console.log('📦 Expanding sections for Head of IT')
            _setSectionExpanded('dashboard', true)
            _setSectionExpanded('requestsManagement', true)
          }
        }

        // Check if we're on divisional dashboard route and expand sidebar immediately
        await nextTick()
        if (route.path.startsWith('/divisional-dashboard')) {
          if (isCollapsed.value) {
            console.log(
              '📂 [onMounted] Auto-expanding sidebar for divisional dashboard route:',
              route.path
            )
            setCollapsed(false)
          } else {
            console.log('✅ [onMounted] Sidebar already expanded for:', route.path)
          }
        }
      })

      // Cleanup event listeners on unmount
      onUnmounted(() => {
        window.removeEventListener('resize', handleResize)
      })

      // Computed properties
      // isCollapsed now comes from useSidebar composable

      const userName = computed(() => {
        try {
          return currentUser?.value?.name || piniaAuthStore?.user?.name || 'JOHN DOE'
        } catch (error) {
          console.warn('Error getting userName:', error)
          return 'JOHN DOE'
        }
      })

      const userInitials = computed(() => {
        try {
          const name = userName.value || 'JOHN DOE'
          return (
            name
              .split(' ')
              .map((n) => n?.[0] || '')
              .join('')
              .toUpperCase()
              .slice(0, 2) || 'JD'
          )
        } catch (error) {
          console.warn('Error getting userInitials:', error)
          return 'JD'
        }
      })

      const userDepartmentOrRole = computed(() => {
        try {
          // Try to get department name from user data
          const user = currentUser?.value || piniaAuthStore?.user
          if (user?.department?.name) {
            return user.department.name
          }
          if (user?.department_name) {
            return user.department_name
          }
          // Fallback to role display name
          const role = piniaAuthStore?.userRole || userRole?.value || stableUserRole?.value
          return getRoleDisplayName(role)
        } catch (error) {
          console.warn('Error getting userDepartmentOrRole:', error)
          return 'Staff Member'
        }
      })

      // Enhanced sidebar visibility logic with dual auth store support
      const shouldShowSidebar = computed(() => {
        try {
          // Check both Vuex and Pinia auth states for maximum reliability
          const vuexAuth = isAuthenticated?.value || false
          const vuexRole = userRole?.value || null
          const vuexLoading = isLoading?.value || false

          const piniaAuth = piniaAuthStore?.isAuthenticated || false
          const piniaRole = piniaAuthStore?.userRole || null
          const piniaLoading = piniaAuthStore?.isLoading || false

          // Use the most reliable source (prefer Pinia if both are available)
          const hasAuth = piniaAuth || vuexAuth
          const hasRole = piniaRole || vuexRole || stableUserRole?.value
          const notLoading = !piniaLoading && !vuexLoading

          console.log('🔍 Sidebar visibility check (dual auth):', {
            vuex: { auth: vuexAuth, role: vuexRole, loading: vuexLoading },
            pinia: { auth: piniaAuth, role: piniaRole, loading: piniaLoading },
            stable: { role: stableUserRole?.value },
            final: { hasAuth, hasRole, notLoading },
            shouldShow: hasAuth && hasRole && notLoading
          })

          return hasAuth && hasRole && notLoading
        } catch (error) {
          console.warn('Error in shouldShowSidebar computed:', error)
          return false
        }
      })

      // Route metadata function (moved before computed properties)
      function getRouteMetadata(route) {
        const metadata = {
          // Dashboards
          '/admin-dashboard': {
            name: 'AdminDashboard',
            displayName: 'Admin Dashboard',
            icon: 'fas fa-user-shield',
            category: 'dashboard',
            description: 'Administrative control panel'
          },
          '/user-dashboard': {
            name: 'UserDashboard',
            displayName: 'User Dashboard',
            icon: 'fas fa-home',
            category: 'dashboard',
            description: 'User portal and services'
          },
          '/dict-dashboard': {
            name: 'DictDashboard',
            displayName: 'ICT Director Dashboard',
            icon: 'fas fa-user-cog',
            category: 'dashboard',
            description: 'ICT Director control panel'
          },
          '/hod-dashboard': {
            name: 'HodDashboard',
            displayName: 'HOD Dashboard',
            icon: 'fas fa-user-tie',
            category: 'dashboard',
            description: 'Head of Department panel'
          },
          '/divisional-dashboard': {
            name: 'DivisionalDashboard',
            displayName: 'Divisional Dashboard',
            icon: 'fas fa-building',
            category: 'dashboard',
            description: 'Divisional Director panel'
          },
          '/ict-dashboard/access-requests': {
            name: 'IctAccessRequests',
            displayName: 'Access Requests',
            icon: 'fas fa-clipboard-check',
            category: 'requests-management',
            description: 'Access requests approved by Head of IT'
          },
          '/ict-dashboard/access-service': {
            name: 'IctAccessService',
            displayName: 'Access Service',
            icon: 'fas fa-tags',
            category: 'requests-management',
            description: 'Create new access or booking request'
          },
          '/head_of_it-dashboard': {
            name: 'HeadOfItDashboard',
            displayName: 'Dashboard',
            icon: 'fas fa-user-cog',
            category: 'dashboard',
            description: 'Head of IT control panel'
          },

          // User Management (Admin only)
          '/service-users': {
            name: 'ServiceUsers',
            displayName: 'Service Granted',
            icon: 'fas fa-check-circle',
            category: 'user-management',
            description: 'Manage granted services'
          },

          // Device Management
          '/ict-approval/requests': {
            name: 'RequestsList',
            displayName: 'Device Requests',
            icon: 'fas fa-clipboard-list',
            category: 'device-management',
            description: 'Manage device borrowing requests (ICT Officer)'
          },
          '/secretary-approval/requests': {
            name: 'SecretaryRequestsList',
            displayName: 'Device Requests',
            icon: 'fas fa-clipboard-list',
            category: 'device-management',
            description: 'Manage device borrowing requests (Secretary ICT)'
          },

          // Requests Management (for approvers)
          '/hod-dashboard/request-list': {
            name: 'HODDashboardRequestList',
            displayName: 'Access Requests',
            icon: 'fas fa-clipboard-check',
            category: 'requests-management',
            description: 'Review access requests'
          },
          '/divisional-dashboard/combined-requests': {
            name: 'DivisionalCombinedRequestList',
            displayName: 'Combined Requests',
            icon: 'fas fa-clipboard-list',
            category: 'requests-management',
            description: 'Review HOD-approved requests'
          },
          '/dict-dashboard/combined-requests': {
            name: 'DictCombinedRequestList',
            displayName: 'Access Requests',
            icon: 'fas fa-clipboard-check',
            category: 'requests-management',
            description: 'Review Divisional Director-approved requests'
          },
          '/head_of_it-dashboard/combined-requests': {
            name: 'HeadOfItCombinedRequestList',
            displayName: 'Access Requests',
            icon: 'fas fa-clipboard-check',
            category: 'requests-management',
            description: 'Review ICT Director-approved requests'
          }
        }

        return metadata[route] || {}
      }

      // Get menu items based on enhanced role detection
      const menuItems = computed(() => {
        try {
          // Use the most reliable role source
          const role = piniaAuthStore?.userRole || userRole?.value || stableUserRole?.value
          if (!role) {
            console.log('🔍 Menu items: No role found')
            return []
          }

          console.log('🔍 Menu items: Using role:', role)

          // Use unified permission helper so dynamic roles (from /admin/user-roles)
          // get their menus without hardcoding entries here.
          const routes = getAllowedRoutes(role) || []
          if (!routes.length) {
            console.log('🔍 Menu items: No allowed routes found for role:', role)
            return []
          }

          const items = routes
            .map((route) => {
              try {
                const metadata = getRouteMetadata(route)
                return {
                  path: route,
                  ...metadata
                }
              } catch (error) {
                console.warn('Error getting metadata for route:', route, error)
                return null
              }
            })
            // Only keep routes that have metadata configured
            .filter((item) => item && item.name)

          console.log('🔍 Menu items computed:', items.length, 'items for role:', role)
          return items
        } catch (error) {
          console.error('Error computing menu items:', error)
          return []
        }
      })

      // Categorize menu items
      const dashboardItems = computed(() => {
        try {
          return menuItems?.value?.filter((item) => item?.category === 'dashboard') || []
        } catch (error) {
          console.warn('Error filtering dashboard items:', error)
          return []
        }
      })

      const userManagementItems = computed(() => {
        try {
          return menuItems?.value?.filter((item) => item?.category === 'user-management') || []
        } catch (error) {
          console.warn('Error filtering user management items:', error)
          return []
        }
      })

      const deviceManagementItems = computed(() => {
        try {
          return menuItems?.value?.filter((item) => item?.category === 'device-management') || []
        } catch (error) {
          console.warn('Error filtering device management items:', error)
          return []
        }
      })

      const requestsManagementItems = computed(() => {
        try {
          return menuItems?.value?.filter((item) => item?.category === 'requests-management') || []
        } catch (error) {
          console.warn('Error filtering requests management items:', error)
          return []
        }
      })

      // Decide if Access Requests should be hidden dynamically based on role/permissions
      const shouldHideAccessRequests = computed(() => {
        try {
          const role = piniaAuthStore?.userRole || userRole?.value || stableUserRole?.value

          // Business rule: ICT Secretary should never see the ICT Access Requests dashboard
          // in the sidebar. Their focus is device bookings only.
          if (role === ROLES.SECRETARY_ICT) {
            return true
          }

          return false
        } catch (error) {
          console.warn('Error computing shouldHideAccessRequests:', error)
          return false
        }
      })

      // Filtered items based on search
      const filteredDashboardItems = computed(() => {
        try {
          if (!searchQuery?.value) return dashboardItems?.value || []
          return (
            dashboardItems?.value?.filter((item) =>
              item?.displayName?.toLowerCase().includes(searchQuery.value.toLowerCase())
            ) || []
          )
        } catch (error) {
          console.error('Error filtering dashboard items:', error)
          return dashboardItems?.value || []
        }
      })

      const filteredUserManagementItems = computed(() => {
        try {
          if (!searchQuery?.value) return userManagementItems?.value || []
          return (
            userManagementItems?.value?.filter((item) =>
              item?.displayName?.toLowerCase().includes(searchQuery.value.toLowerCase())
            ) || []
          )
        } catch (error) {
          console.error('Error filtering user management items:', error)
          return userManagementItems?.value || []
        }
      })

      const filteredDeviceManagementItems = computed(() => {
        try {
          if (!searchQuery?.value) return deviceManagementItems?.value || []
          return (
            deviceManagementItems?.value?.filter((item) =>
              item?.displayName?.toLowerCase().includes(searchQuery.value.toLowerCase())
            ) || []
          )
        } catch (error) {
          console.error('Error filtering device management items:', error)
          return deviceManagementItems?.value || []
        }
      })

      const filteredRequestsManagementItems = computed(() => {
        try {
          let items = requestsManagementItems?.value || []

          // Dynamically hide Access Requests for booking-only roles (e.g., Secretary ICT)
          if (shouldHideAccessRequests.value) {
            items = items.filter((item) => item.path !== '/ict-dashboard/access-requests')
          }

          if (!searchQuery?.value) return items

          return (
            items?.filter((item) =>
              item?.displayName?.toLowerCase().includes(searchQuery.value.toLowerCase())
            ) || []
          )
        } catch (error) {
          console.error('Error filtering requests management items:', error)
          return requestsManagementItems?.value || []
        }
      })

      // Methods
      function toggleCollapse() {
        toggleSidebar()
        if (DEBUG) console.log('🔄 Sidebar toggled via Pinia')
      }

      function toggleSectionLocal(section) {
        toggleSection(section)
      }

      const roleForGuide = computed(() => {
        return piniaAuthStore?.userRole || userRole?.value || stableUserRole?.value || ROLES.STAFF
      })

      const userGuideRoleLabel = computed(() => {
        return getRoleDisplayName(roleForGuide.value)
      })

      const userGuideSections = computed(() => {
        const role = roleForGuide.value

        switch (role) {
          case ROLES.ADMIN:
            return [
              {
                title: 'Admin: setup & maintenance',
                items: [
                  'Open Admin Dashboard to manage users, roles, and departments.',
                  'Correct role + department assignment controls which menus/forms a user can access.'
                ]
              },
              {
                title: 'Admin: user support',
                items: [
                  'If a user cannot see expected pages, verify their role assignment first.',
                  'Use onboarding reset when a user must re-accept terms/ICT policy or restart onboarding.'
                ]
              }
            ]
          case ROLES.STAFF:
            return [
              {
                title: 'Staff: submit a request',
                items: [
                  'Open User Dashboard and select the required form (Jeeva / Wellsoft / Internet / Combined).',
                  'Complete all fields accurately (request type, justification, user details) then submit.'
                ]
              },
              {
                title: 'Staff: follow up',
                items: [
                  'Track your request status from your dashboard.',
                  'If clarification is requested by approvers, update/re-submit promptly.'
                ]
              }
            ]
          case ROLES.HEAD_OF_DEPARTMENT:
            return [
              {
                title: 'HOD: review & recommend',
                items: [
                  'Use HOD Dashboard → Access Requests to review submissions from your department.',
                  'Approve/Reject (recommend) with clear comments and forward to the next approval stage.'
                ]
              },
              {
                title: 'HOD: data quality',
                items: [
                  'Ensure staff details and department information are correct for routing.',
                  'If details are missing, reject with reasons so the user can correct and re-submit.'
                ]
              }
            ]
          case ROLES.DIVISIONAL_DIRECTOR:
            return [
              {
                title: 'Divisional Director: approval stage',
                items: [
                  'Use Divisional Dashboard → Combined Requests to review escalated requests.',
                  'Approve/Reject with clear audit comments (who/what/why) to keep workflow moving.'
                ]
              }
            ]
          case ROLES.HEAD_OF_IT:
            return [
              {
                title: 'Head of IT: routing & oversight',
                items: [
                  'Use Head of IT Dashboard → Combined Requests to review and route requests.',
                  'Forward tasks to the ICT team for implementation and track bottlenecks.'
                ]
              },
              {
                title: 'Head of IT: escalation',
                items: [
                  'Escalate high-impact or policy-sensitive requests to ICT Director.',
                  'Coordinate with departments when submitted information is incomplete.'
                ]
              }
            ]
          case ROLES.ICT_OFFICER:
            return [
              {
                title: 'ICT Officer: implement requests',
                items: [
                  'Open ICT Dashboard → Access Requests to view items forwarded for implementation.',
                  'Validate request details and implement the required service or reject with reasons.'
                ]
              },
              {
                title: 'ICT Officer: update status',
                items: [
                  'Update the request status after action is taken so users/approvers can track progress.',
                  'Escalate exceptions to Head of IT / ICT Director.'
                ]
              }
            ]
          case ROLES.SECRETARY_ICT:
            return [
              {
                title: 'ICT Secretary: device bookings',
                items: [
                  'Use Device Requests to review device borrowing/booking requests.',
                  'Confirm availability and booking dates before approving/declining.'
                ]
              },
              {
                title: 'ICT Secretary: coordination',
                items: [
                  'Escalate technical implementation tasks to ICT Officer.',
                  'Use ICT Support contacts for communication and issue logging.'
                ]
              }
            ]
          case ROLES.ICT_DIRECTOR:
            return [
              {
                title: 'ICT Director: final oversight',
                items: [
                  'Use ICT Director Dashboard to review escalated / high-impact requests.',
                  'Approve/Reject based on ICT policy and ensure the ICT team follows through.'
                ]
              }
            ]
          default:
            return [
              {
                title: 'General guidance',
                items: [
                  'Your menus and forms are displayed based on your role and permissions.',
                  'If you cannot find a page you need, contact ICT Support or request role verification from Admin.'
                ]
              }
            ]
        }
      })

      function showHelp() {
        showHelpModal.value = true
      }

      function getRoleDisplayName(role) {
        if (!role) return 'User'

        const roleNames = {
          [ROLES.ADMIN]: 'Administrator',
          [ROLES.DIVISIONAL_DIRECTOR]: 'Divisional Director',
          [ROLES.HEAD_OF_DEPARTMENT]: 'Head of Department',
          [ROLES.ICT_DIRECTOR]: 'ICT Director',
          [ROLES.HEAD_OF_IT]: 'Head of IT',
          [ROLES.STAFF]: 'Staff Member',
          [ROLES.ICT_OFFICER]: 'ICT Officer',
          [ROLES.SECRETARY_ICT]: 'ICT Secretary'
        }
        return roleNames[role] || role
      }

      async function handleLogout() {
        try {
          await logoutGuard.executeLogout(async () => {
            await logout()
          })
          router.push('/')
        } catch (error) {
          console.error('Logout failed:', error)
          router.push('/')
        }
      }

      // Notification badge functionality
      const notificationCounts = ref({})

      function getNotificationCount(routePath) {
        const count = notificationCounts.value[routePath] || 0
        if (count > 0) {
          if (DEBUG) console.log(`🔍 getNotificationCount('${routePath}') returning:`, count)
        }
        return count
      }

      // Notification retry state
      const notificationRetries = ref(0)
      const maxNotificationRetries = 3
      const isNotificationDisabled = ref(false)

      async function fetchNotificationCounts(forceRefresh = false) {
        // Skip if temporarily disabled due to repeated failures
        if (isNotificationDisabled.value) {
          if (DEBUG) console.log('🚫 Notification fetching temporarily disabled')
          return
        }

        try {
          const role = piniaAuthStore?.userRole || userRole?.value || stableUserRole?.value
          if (process.env.NODE_ENV === 'development') {
            console.log('🔄 fetchNotificationCounts called with role:', role)
          }

          if (!role) {
            console.warn('No role found, skipping notification fetch')
            return
          }

          // Import the universal notification service dynamically to avoid circular dependencies
          const notificationService = (await import('@/services/notificationService')).default
          if (process.env.NODE_ENV === 'development') {
            console.log('📡 Calling notification service API...')
          }

          const result = await notificationService.getPendingRequestsCount(forceRefresh)

          if (process.env.NODE_ENV === 'development') {
            console.log('📊 API response:', result)

            // DEBUG: Log the API response details
            console.log('🔍 NOTIFICATION DEBUG - API Response:', {
              total_pending: result.total_pending,
              requires_attention: result.requires_attention,
              full_result: result,
              timestamp: new Date().toISOString()
            })
          }

          // Reset retry counter on successful fetch
          notificationRetries.value = 0

          // ICT Officer specific: cross-check with role-specific API and in-page override
          let finalPendingCount = result.total_pending || 0
          let ictSpecificCount = null
          let pageOverrideCount = null

          if (role === 'ict_officer') {
            try {
              const ictOfficerService = (await import('@/services/ictOfficerService')).default
              const ictResp = await ictOfficerService.getPendingRequestsCount()
              ictSpecificCount =
                typeof ictResp === 'object' ? (ictResp.total_pending ?? null) : ictResp
            } catch (e) {
              console.warn('⚠️ Failed to fetch ICT-specific pending count:', e?.message || e)
            }
          } else if (role === 'ict_director') {
            // Fallback for ICT Director: prefer DICT statistics first
            try {
              const dictService = (await import('@/services/dictAccessService')).default
              const statsResp = await dictService.getDictStatistics()
              const s = statsResp?.data || {}
              const dictPending = Number(
                s.pendingDict ?? s.pending ?? s.total_pending ?? s.pending_count ?? 0
              )
              if (finalPendingCount === 0 && dictPending > 0) {
                finalPendingCount = dictPending
              }
              // Deep fallback: compute from current request list when stats/universal return 0
              if (finalPendingCount === 0) {
                const listResp = await dictService.getDictRequests()
                // Robustly extract an array of records from various API shapes
                const pickArray = (root) => {
                  if (!root) return []
                  const candidates = [
                    root?.data?.data, // Laravel pagination: { data: { data: [] } }
                    root?.data?.records, // alternative key
                    root?.data?.items, // alternative key
                    root?.data?.rows, // alternative key
                    root?.data, // sometimes data is already the array
                    root?.items, // flattened
                    root?.records,
                    root?.rows
                  ]
                  for (const c of candidates) {
                    if (Array.isArray(c)) return c
                  }
                  // Deep scan: find first array value in one level of nesting
                  try {
                    const values = Object.values(root?.data || {})
                    const firstArray = values.find((v) => Array.isArray(v))
                    if (Array.isArray(firstArray)) return firstArray
                  } catch (e) {
                    /* no-op: safe fallback below */
                  }
                  return []
                }
                const arr = pickArray(listResp)
                const count = arr.filter((r) => {
                  const st = String(r.status || r.dict_status || '').toLowerCase()
                  const dictSt = String(r.ict_director_status || r.dict_status || '').toLowerCase()
                  const hodSt = String(r.hod_status || '').toLowerCase()
                  const divSt = String(r.divisional_status || '').toLowerCase()
                  const headItSt = String(r.head_it_status || '').toLowerCase()
                  const implSt = String(r.ict_officer_status || '').toLowerCase()

                  // Legacy overall-status based detection
                  const legacyPending =
                    st === 'pending' ||
                    st === 'divisional_approved' ||
                    st === 'pending_ict_director' ||
                    st === 'pending_dict' ||
                    dictSt === 'pending'

                  // Per-stage inference (covers ICT Officer-origin requests: HOD/DIV skipped)
                  const preDictDone =
                    ['approved', 'skipped'].includes(hodSt) &&
                    ['approved', 'skipped'].includes(divSt)
                  const notCompleted = !['approved', 'implemented', 'completed'].includes(implSt)
                  const headItNotTaken = !['approved', 'rejected'].includes(headItSt)
                  const dictPending = !['approved', 'rejected'].includes(dictSt) // treat empty/null as pending

                  const stageBasedPending =
                    preDictDone && dictPending && headItNotTaken && notCompleted

                  return legacyPending || stageBasedPending
                }).length
                if (count > 0) finalPendingCount = count
              }
            } catch (e) {
              console.warn('⚠️ Failed to fetch DICT pending count:', e?.message || e)
            }

            try {
              // AccessRequests page can set this for real-time accuracy
              if (
                typeof window !== 'undefined' &&
                typeof window.accessRequestsPendingCount === 'number'
              ) {
                pageOverrideCount = window.accessRequestsPendingCount
              }
            } catch (e) {
              console.warn('⚠️ Failed to read page override count:', e?.message || e)
            }

            // Decide final count with precedence: pageOverride -> ictSpecific -> universal
            finalPendingCount =
              (typeof pageOverrideCount === 'number' ? pageOverrideCount : null) ??
              (typeof ictSpecificCount === 'number' ? ictSpecificCount : null) ??
              finalPendingCount

            if (process.env.NODE_ENV === 'development') {
              console.log('🧮 ICT Badge Count Reconciliation:', {
                universal: result.total_pending,
                ictSpecific: ictSpecificCount,
                pageOverride: pageOverrideCount,
                chosen: finalPendingCount
              })
            }
          }

          // EXTENDED DEBUG: Log breakdown if available to see what's being counted
          if (
            (result.details?.breakdown || result.details) &&
            process.env.NODE_ENV === 'development'
          ) {
            console.log('📊 NOTIFICATION BREAKDOWN DEBUG:', {
              breakdown: result.details?.breakdown,
              details: result.details,
              role,
              endpoint_called: '/notifications/pending-count'
            })
          }

          // ULTRA DEBUG: Only warn if, after reconciliation, we still have >0 pending
          const warnAfter =
            role === 'ict_officer' ? finalPendingCount > 0 : result.total_pending > 0
          if (warnAfter) {
            console.warn('⚠️ NOTIFICATION ISSUE DETECTED:', {
              message: 'Badge showing pending count but requests may be completed',
              pending_count: role === 'ict_officer' ? finalPendingCount : result.total_pending,
              user_role: role,
              timestamp: new Date().toISOString(),
              suggestion:
                'Check backend /notifications/pending-count API - may be counting completed/implemented requests'
            })
          }

          if (finalPendingCount > 0) {
            // Get role-specific configuration to know which routes should show badges
            const config = notificationService.getRoleNotificationConfig(role)
            if (DEBUG) console.log('⚙️ Role config for', role, ':', config)

            // Clear old counts
            const oldCounts = { ...notificationCounts.value }
            notificationCounts.value = {}

            // Set badge count for relevant menu items
            config.menuItems.forEach((menuPath) => {
              // If we have a page override for this specific route, prefer it
              let routeCount = finalPendingCount
              if (
                role === 'ict_officer' &&
                menuPath === '/ict-dashboard/access-requests' &&
                typeof window !== 'undefined' &&
                typeof window.accessRequestsPendingCount === 'number'
              ) {
                routeCount = window.accessRequestsPendingCount
              }
              notificationCounts.value[menuPath] = routeCount
              if (DEBUG)
                console.log(
                  `🎯 Setting badge count ${routeCount} for route: ${menuPath} (was: ${oldCounts[menuPath] || 0})`
                )
            })

            if (DEBUG)
              console.log('🔔 Notification badges updated:', {
                role,
                oldCounts,
                newCount: finalPendingCount,
                routes: config.menuItems,
                finalCounts: notificationCounts.value,
                countChanged: JSON.stringify(oldCounts) !== JSON.stringify(notificationCounts.value)
              })
          } else {
            // Clear all notification counts
            const oldCounts = { ...notificationCounts.value }
            notificationCounts.value = {}
            if (process.env.NODE_ENV === 'development' && Object.keys(oldCounts).length > 0) {
              console.log(
                '🔕 Cleared notification badges (had:',
                Object.keys(oldCounts).length,
                'routes)'
              )
            }
          }
        } catch (error) {
          console.error('❌ Failed to fetch notification counts:', error)

          // Increment retry counter
          notificationRetries.value++

          // If we've exceeded max retries, disable notifications temporarily
          if (notificationRetries.value >= maxNotificationRetries) {
            console.warn(`⚠️ Disabling notifications after ${maxNotificationRetries} failures`)
            isNotificationDisabled.value = true

            // Re-enable after 5 minutes
            setTimeout(
              () => {
                if (DEBUG) console.log('♾️ Re-enabling notification fetching')
                isNotificationDisabled.value = false
                notificationRetries.value = 0
              },
              5 * 60 * 1000
            ) // 5 minutes
          }

          // Log detailed error info for debugging
          console.error('Error details:', {
            message: error.message,
            code: error.code,
            status: error.response?.status,
            retries: notificationRetries.value,
            disabled: isNotificationDisabled.value
          })
        }
      }

      // Fetch notification counts on mount and periodically
      let notificationInterval = null

      onMounted(() => {
        if (DEBUG) console.log('🚀 Sidebar mounted, triggering initial notification fetch')

        // Initial fetch with delay to ensure auth is ready
        setTimeout(() => {
          if (DEBUG) console.log('⏰ Delayed notification fetch triggered')
          fetchNotificationCounts()
        }, 2000)

        // Update counts every 30 seconds
        notificationInterval = setInterval(fetchNotificationCounts, 30000)

        // Expose fetchNotificationCounts globally for other components to use
        window.sidebarInstance = {
          fetchNotificationCounts: (force = false) => {
            if (DEBUG)
              console.log('🌍 🔔 GLOBAL SIDEBAR: Global sidebar notification refresh triggered:', {
                force,
                currentCounts: { ...notificationCounts.value },
                timestamp: new Date().toISOString()
              })
            fetchNotificationCounts(force)
          },
          setNotificationCount: (routePath, count) => {
            try {
              if (typeof count === 'number' && routePath) {
                if (DEBUG)
                  console.log('🌍 🔔 GLOBAL SIDEBAR: Forcing notification count set:', {
                    routePath,
                    count,
                    prev: notificationCounts.value[routePath] || 0
                  })
                notificationCounts.value[routePath] = count
              }
            } catch (e) {
              console.warn('Failed to set notification count globally:', e?.message || e)
            }
          }
        }

        // Listen for global refresh events
        const handleRefreshNotifications = (event) => {
          if (DEBUG)
            console.log('📻 🔔 NOTIFICATION EVENT: Received global refresh-notifications event:', {
              detail: event?.detail,
              timestamp: new Date().toISOString(),
              currentCounts: { ...notificationCounts.value }
            })
          fetchNotificationCounts(true) // force refresh
        }

        // Listen for ICT AccessRequests page pending-count override
        const handleIctPendingCount = (event) => {
          const count = event?.detail?.count
          if (typeof count === 'number') {
            if (DEBUG) console.log('🛰️ ICT AccessRequests pending count override received:', count)
            notificationCounts.value['/ict-dashboard/access-requests'] = count
          }
        }

        const handleForceRefreshNotifications = (event) => {
          if (DEBUG)
            console.log('🔥 🔔 NOTIFICATION EVENT: Received force-refresh-notifications event:', {
              detail: event?.detail,
              timestamp: new Date().toISOString(),
              currentCounts: { ...notificationCounts.value }
            })
          // Clear any existing counts and force immediate refresh
          const oldCounts = { ...notificationCounts.value }
          notificationCounts.value = {}
          if (DEBUG) console.log('🗑️ Cleared notification counts. Old counts:', oldCounts)
          fetchNotificationCounts(true)
        }

        if (window.addEventListener) {
          window.addEventListener('refresh-notifications', handleRefreshNotifications)
          window.addEventListener('force-refresh-notifications', handleForceRefreshNotifications)
          window.addEventListener('ict-access-requests-pending-count', handleIctPendingCount)
        }
      })

      // Clean up interval and global references on unmount
      onUnmounted(() => {
        if (notificationInterval) {
          clearInterval(notificationInterval)
        }

        // Clean up global references
        if (window.sidebarInstance) {
          delete window.sidebarInstance
        }

        // Remove event listener
        if (window.removeEventListener) {
          window.removeEventListener('refresh-notifications', () => {
            fetchNotificationCounts(true)
          })
        }
      })

      // Watch for route changes and refresh notifications when entering dashboard pages
      watch(
        route,
        (newRoute, _oldRoute) => {
          const dashboardRoutes = [
            '/ict-dashboard/access-requests',
            '/hod-dashboard/request-list',
            '/divisional-dashboard/combined-requests',
            '/dict-dashboard/combined-requests',
            '/head_of_it-dashboard/combined-requests'
          ]

          // Auto-expand sidebar when navigating to divisional dashboard routes
          if (newRoute.path.startsWith('/divisional-dashboard')) {
            if (isCollapsed.value) {
              console.log(
                '📂 Auto-expanding sidebar for divisional dashboard route:',
                newRoute.path
              )
              setCollapsed(false)
            }
          }

          // If navigating to a dashboard route that shows notifications
          if (dashboardRoutes.some((routePath) => newRoute.path.startsWith(routePath))) {
            console.log(
              '🗺️ Route changed to dashboard page, refreshing notifications:',
              newRoute.path
            )
            setTimeout(() => {
              fetchNotificationCounts(true) // force refresh after navigation
            }, 1000)
          }
        },
        { immediate: true }
      )

      // Theme is automatically initialized by useTheme composable

      return {
        // State
        isCollapsed,
        searchQuery,
        showHelpModal,
        showUserGuide,
        userGuideRoleLabel,
        userGuideSections,
        stableUserRole,
        expandedSections,

        // Router
        route,

        // Computed
        currentUser,
        userRole,
        isAuthenticated,
        isLoading,
        userName,
        userInitials,
        userDepartmentOrRole,
        shouldShowSidebar,
        dashboardItems,
        userManagementItems,
        deviceManagementItems,
        requestsManagementItems,
        filteredDashboardItems,
        filteredUserManagementItems,
        filteredDeviceManagementItems,
        filteredRequestsManagementItems,
        debugHeadOfIt,

        // Methods
        toggleCollapse,
        toggleSectionLocal,
        showHelp,
        getRoleDisplayName,
        handleLogout,
        getNotificationCount,
        fetchNotificationCounts,
        piniaAuthStore
      }
    },
    directives: {
      tooltip: {
        mounted(el, binding) {
          if (binding.value && typeof binding.value === 'string') {
            el.title = binding.value
          }
        },
        updated(el, binding) {
          if (binding.value && typeof binding.value === 'string') {
            el.title = binding.value
          } else {
            el.removeAttribute('title')
          }
        },
        unmounted(el) {
          el.removeAttribute('title')
        }
      }
    }
  }
</script>

<style scoped>
  /* Responsive Font Size Variables */
  .sidebar-responsive {
    --brand-font-size: 1.125rem; /* 18px */
    --profile-name-size: 1.125rem; /* 18px */
    --profile-role-size: 1rem; /* 16px */
    --section-header-size: 1rem; /* 16px */
    --nav-item-size: 1.125rem; /* 18px */
    --bottom-section-size: 1.125rem; /* 18px */

    /* Icon sizes */
    --icon-size-small: 1.25rem; /* 20px */
    --icon-size-medium: 1.5rem; /* 24px */

    /* Transition for font sizes */
    transition: all 0.3s ease-in-out;
  }

  .sidebar-expanded {
    --brand-font-size: 1.25rem; /* 20px */
    --profile-name-size: 1.125rem; /* 18px */
    --profile-role-size: 1rem; /* 16px */
    --section-header-size: 1rem; /* 16px */
    --nav-item-size: 1.125rem; /* 18px */
    --bottom-section-size: 1.125rem; /* 18px */

    --icon-size-small: 1.5rem; /* 24px */
    --icon-size-medium: 1.75rem; /* 28px */
  }

  .sidebar-collapsed {
    --brand-font-size: 0.625rem; /* 10px - hidden anyway */
    --profile-name-size: 0.625rem; /* 10px - hidden anyway */
    --profile-role-size: 0.5rem; /* 8px - hidden anyway */
    --section-header-size: 0.5rem; /* 8px - hidden anyway */
    --nav-item-size: 0.625rem; /* 10px - hidden anyway */
    --bottom-section-size: 0.625rem; /* 10px - hidden anyway */

    --icon-size-small: 0.875rem; /* 14px */
    --icon-size-medium: 1rem; /* 16px */
  }

  /* Responsive text classes */
  .responsive-brand-text {
    font-size: var(--brand-font-size);
    transition: font-size 0.3s ease-in-out;
  }

  .responsive-profile-name {
    font-size: var(--profile-name-size);
    transition: font-size 0.3s ease-in-out;
  }

  .responsive-profile-role {
    font-size: var(--profile-role-size);
    transition: font-size 0.3s ease-in-out;
  }

  .responsive-section-header {
    font-size: var(--section-header-size);
    transition: font-size 0.3s ease-in-out;
  }

  .responsive-nav-item {
    font-size: var(--nav-item-size);
    transition: font-size 0.3s ease-in-out;
  }

  .responsive-bottom-section {
    font-size: var(--bottom-section-size);
    transition: font-size 0.3s ease-in-out;
  }

  .responsive-icon-small {
    width: var(--icon-size-small);
    height: var(--icon-size-small);
    transition:
      width 0.3s ease-in-out,
      height 0.3s ease-in-out;
  }

  .responsive-icon-medium {
    width: var(--icon-size-medium);
    height: var(--icon-size-medium);
    transition:
      width 0.3s ease-in-out,
      height 0.3s ease-in-out;
  }

  /* Custom scrollbar */
  .custom-scrollbar::-webkit-scrollbar {
    width: 4px;
  }

  .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
    box-shadow: inset 0 0 2px rgba(255, 255, 255, 0.2);
  }

  .custom-scrollbar::-webkit-scrollbar-track {
    background-color: transparent;
  }

  /* Enhanced shadow effects */
  .shadow-sm {
    box-shadow:
      0 1px 2px 0 rgba(0, 0, 0, 0.05),
      0 1px 3px 0 rgba(0, 0, 0, 0.1);
  }

  .shadow-md {
    box-shadow:
      0 4px 6px -1px rgba(0, 0, 0, 0.1),
      0 2px 4px -1px rgba(0, 0, 0, 0.06);
  }

  .shadow-lg {
    box-shadow:
      0 10px 15px -3px rgba(0, 0, 0, 0.1),
      0 4px 6px -2px rgba(0, 0, 0, 0.05),
      inset 0 1px 0 rgba(255, 255, 255, 0.1);
  }

  .shadow-2xl {
    box-shadow:
      0 25px 50px -12px rgba(0, 0, 0, 0.25),
      0 0 0 1px rgba(255, 255, 255, 0.1),
      inset 0 1px 0 rgba(255, 255, 255, 0.1);
  }

  /* Multi-layer background effects */
  .bg-gradient-to-br {
    --tw-gradient-from: transparent;
    --tw-gradient-to: transparent;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
    background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
  }

  .bg-gradient-to-t {
    --tw-gradient-from: transparent;
    --tw-gradient-to: transparent;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
    background-image: linear-gradient(to top, var(--tw-gradient-stops));
  }

  /* Gradient color utilities */
  .from-black\/10 {
    --tw-gradient-from: rgba(0, 0, 0, 0.1);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(0, 0, 0, 0));
  }

  .via-transparent {
    --tw-gradient-stops: var(--tw-gradient-from), transparent, var(--tw-gradient-to, transparent);
  }

  .to-black\/20 {
    --tw-gradient-to: rgba(0, 0, 0, 0.2);
  }

  .from-transparent {
    --tw-gradient-from: transparent;
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent);
  }

  .via-white\/5 {
    --tw-gradient-stops:
      var(--tw-gradient-from), rgba(255, 255, 255, 0.05),
      var(--tw-gradient-to, rgba(255, 255, 255, 0));
  }

  .to-white\/10 {
    --tw-gradient-to: rgba(255, 255, 255, 0.1);
  }

  /* Enhanced hover effects */
  .hover\:scale-102:hover {
    transform: scale(1.02);
  }

  .scale-105 {
    transform: scale(1.05);
  }

  /* Fallback for transform utilities */
  .transform {
    --tw-translate-x: 0;
    --tw-translate-y: 0;
    --tw-rotate: 0;
    --tw-skew-x: 0;
    --tw-skew-y: 0;
    --tw-scale-x: 1;
    --tw-scale-y: 1;
    --tw-transform: translateX(var(--tw-translate-x)) translateY(var(--tw-translate-y))
      rotate(var(--tw-rotate)) skewX(var(--tw-skew-x)) skewY(var(--tw-skew-y))
      scaleX(var(--tw-scale-x)) scaleY(var(--tw-scale-y));
    transform: var(--tw-transform);
  }

  .hover\:transform:hover {
    transform: var(--tw-transform);
  }

  /* Smooth transitions */
  .transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  }

  .transition-transform {
    transition-property: transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
  }

  .transition-colors {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
  }

  /* Backdrop blur support */
  .backdrop-blur-sm {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
  }

  /* Focus styles */
  .focus\:ring-2:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 2px var(--tw-ring-color);
  }

  /* Section header styles */
  .section-header {
    position: relative;
    overflow: hidden;
  }

  .section-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transform: translateX(-100%);
    transition: transform 0.6s ease;
  }

  .section-header:hover::before {
    transform: translateX(100%);
  }

  /* Navigation item enhanced styles */
  .nav-item {
    position: relative;
    overflow: hidden;
  }

  .nav-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s ease;
  }

  .nav-item:hover::before {
    left: 100%;
  }

  /* Active indicator pulse animation */
  @keyframes pulse {
    0%,
    100% {
      opacity: 1;
    }
    50% {
      opacity: 0.7;
    }
  }

  .active-indicator {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  }

  /* Layered shadow for depth */
  .layered-shadow {
    box-shadow:
      0 1px 3px rgba(0, 0, 0, 0.12),
      0 1px 2px rgba(0, 0, 0, 0.24),
      inset 0 1px 0 rgba(255, 255, 255, 0.1),
      inset 0 -1px 0 rgba(0, 0, 0, 0.1);
  }

  /* Glassmorphism effect */
  .glass-effect {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  /* Enhanced Menu Toggle Button */
  .menu-toggle-btn {
    position: relative;
    z-index: 10;
  }

  .menu-toggle-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
  }

  .menu-toggle-btn:active {
    transform: scale(0.95);
  }

  /* Profile Section Animations */
  .profile-expanded {
    animation: slideInFromLeft 0.5s ease-out;
  }

  .profile-collapsed {
    animation: slideInFromLeft 0.5s ease-out;
  }

  .search-expanded {
    animation: slideInFromLeft 0.6s ease-out;
    animation-delay: 0.1s;
    animation-fill-mode: both;
  }

  .search-collapsed {
    animation: slideInFromLeft 0.6s ease-out;
    animation-delay: 0.1s;
    animation-fill-mode: both;
  }

  @keyframes slideInFromLeft {
    0% {
      opacity: 0;
      transform: translateX(-20px);
    }
    100% {
      opacity: 1;
      transform: translateX(0);
    }
  }

  /* Enhanced Sidebar Transitions */
  .h-full {
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Global transitions for all responsive elements */
  .sidebar-responsive * {
    transition:
      font-size 0.3s ease-in-out,
      width 0.3s ease-in-out,
      height 0.3s ease-in-out;
  }

  /* Ensure text doesn't wrap during transitions */
  .responsive-brand-text,
  .responsive-profile-name,
  .responsive-profile-role,
  .responsive-section-header,
  .responsive-nav-item,
  .responsive-bottom-section {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Profile hover effects */
  .profile-expanded .group:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  }

  .profile-collapsed .group:hover {
    transform: translateY(-1px) scale(1.05);
  }

  /* Search input enhancements */
  .search-expanded input:focus {
    transform: scale(1.02);
    box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
  }

  /* Brand logo animation */
  .brand-logo {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Navigation items enhanced transitions */
  .nav-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .nav-item:hover {
    transform: translateX(4px) scale(1.02);
  }

  /* Section headers enhanced */
  .section-header {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .section-header:hover {
    transform: translateX(2px);
    background: rgba(37, 99, 235, 0.3);
    border-radius: 8px;
    padding-left: 16px;
    padding-right: 16px;
  }

  /* Bottom section buttons */
  .bottom-section button {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .bottom-section button:hover {
    transform: translateX(4px) scale(1.02);
    background: rgba(37, 99, 235, 0.6);
  }

  /* Staggered animation for navigation items */
  .nav-item:nth-child(1) {
    animation-delay: 0.1s;
  }
  .nav-item:nth-child(2) {
    animation-delay: 0.2s;
  }
  .nav-item:nth-child(3) {
    animation-delay: 0.3s;
  }
  .nav-item:nth-child(4) {
    animation-delay: 0.4s;
  }
  .nav-item:nth-child(5) {
    animation-delay: 0.5s;
  }

  /* Notification Badge Styles */
  .notification-badge {
    z-index: 10;
    font-size: 0.6rem;
    line-height: 1;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    box-shadow:
      0 2px 4px rgba(239, 68, 68, 0.4),
      0 0 0 1px rgba(255, 255, 255, 0.8);
    transition: transform 0.2s ease-in-out;
  }

  .notification-badge:hover {
    transform: scale(1.1);
    animation-play-state: paused;
  }

  /* Responsive design helpers */
  @media (max-width: 768px) {
    .sidebar-mobile {
      position: fixed;
      z-index: 50;
      left: 0;
      top: 0;
    }

    /* Faster transitions on mobile */
    .h-full {
      transition: width 0.3s ease-in-out;
    }

    .menu-toggle-btn {
      padding: 12px;
    }

    /* Reduce animations on mobile for performance */
    .profile-expanded,
    .profile-collapsed,
    .search-expanded,
    .search-collapsed {
      animation: none;
    }
  }
</style>
