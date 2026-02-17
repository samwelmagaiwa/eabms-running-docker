<template>
  <div
    class="medical-card bg-gradient-to-r from-blue-600/25 to-cyan-600/25 border-2 border-blue-400/40 rounded-2xl backdrop-blur-sm hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-500 group"
  >
    <!-- Enhanced Header Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-6 header-shadow">
      <!-- Top Row: Title and Description -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <div
            class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 backdrop-blur-sm border border-white/30 icon-shadow"
          >
            <i class="fas fa-users-cog text-white text-lg drop-shadow-lg"></i>
          </div>
          <div>
            <h2 class="text-3xl font-bold text-white drop-shadow-md">User Management System</h2>
            <p class="text-blue-100 text-lg drop-shadow-sm">
              Monitor and manage user access across all platforms
            </p>
          </div>
        </div>

        <!-- Toggle Button -->
        <button
          class="text-white hover:text-blue-200 transition-colors p-3 rounded-xl hover:bg-white/20 backdrop-blur-sm border border-white/20 shadow-lg hover:shadow-xl transform hover:scale-105 duration-300"
          @click="open = !open"
        >
          <i
            :class="['fas text-lg drop-shadow-sm', open ? 'fa-chevron-up' : 'fa-chevron-down']"
          ></i>
        </button>
      </div>

      <!-- Bottom Row: User Count Cards -->
      <div class="flex items-center justify-center space-x-4">
        <!-- Jeeva Users Count -->
        <div
          class="bg-white/15 rounded-xl px-4 py-3 backdrop-blur-sm border border-white/20 shadow-lg hover:bg-white/20 transition-all duration-300 group flex-1 max-w-xs"
        >
          <div class="flex items-center space-x-3">
            <div
              class="w-8 h-8 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-lg flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300"
            >
              <i class="fas fa-database text-white text-sm drop-shadow-sm"></i>
            </div>
            <div class="text-white">
              <p class="text-lg font-medium drop-shadow-sm">Jeeva</p>
              <p class="text-3xl font-bold drop-shadow-md">
                {{ totals.jeeva || 0 }}
              </p>
            </div>
          </div>
        </div>

        <!-- Wellsoft Users Count -->
        <div
          class="bg-white/15 rounded-xl px-4 py-3 backdrop-blur-sm border border-white/20 shadow-lg hover:bg-white/20 transition-all duration-300 group flex-1 max-w-xs"
        >
          <div class="flex items-center space-x-3">
            <div
              class="w-8 h-8 bg-gradient-to-br from-purple-400 to-indigo-500 rounded-lg flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300"
            >
              <i class="fas fa-laptop-medical text-white text-sm drop-shadow-sm"></i>
            </div>
            <div class="text-white">
              <p class="text-lg font-medium drop-shadow-sm">Wellsoft</p>
              <p class="text-3xl font-bold drop-shadow-md">
                {{ totals.wellsoft || 0 }}
              </p>
            </div>
          </div>
        </div>

        <!-- Internet Users Count -->
        <div
          class="bg-white/15 rounded-xl px-4 py-3 backdrop-blur-sm border border-white/20 shadow-lg hover:bg-white/20 transition-all duration-300 group flex-1 max-w-xs"
        >
          <div class="flex items-center space-x-3">
            <div
              class="w-8 h-8 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg flex items-center justify-center shadow-md group-hover:scale-110 transition-transform duration-300"
            >
              <i class="fas fa-wifi text-white text-sm drop-shadow-sm"></i>
            </div>
            <div class="text-white">
              <p class="text-lg font-medium drop-shadow-sm">Internet</p>
              <p class="text-3xl font-bold drop-shadow-md">
                {{ totals.internet || 0 }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Content Section -->
    <transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-4"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div v-if="open" class="px-6 pb-6 bg-gradient-to-b from-blue-50/10 to-transparent">
        <!-- Enhanced Search and Export Controls -->
        <div
          class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 mt-4"
        >
          <!-- Enhanced Search Box -->
          <div class="flex-1 max-w-md">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-blue-300 text-sm drop-shadow-sm"></i>
              </div>
              <input
                v-model="searchQuery"
                type="text"
                class="block w-full pl-12 pr-10 py-3 text-lg border-2 border-blue-300/30 rounded-xl leading-5 bg-white/15 backdrop-blur-sm placeholder-blue-200/60 text-white focus:outline-none focus:placeholder-blue-200/40 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-300 hover:bg-white/20 focus:bg-white/20 shadow-lg focus:shadow-xl"
                placeholder="Search users..."
                @input="handleSearch"
              />
              <!-- Clear Search Button -->
              <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button
                  @click="clearSearch"
                  class="text-blue-300 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/20"
                >
                  <i class="fas fa-times text-sm drop-shadow-sm"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Enhanced Export and Filter Controls -->
          <div class="flex items-center space-x-3">
            <!-- Enhanced Filter Dropdown -->
            <div class="relative">
              <select
                v-model="filterStatus"
                class="appearance-none bg-white/15 backdrop-blur-sm border-2 border-blue-300/30 rounded-xl px-4 py-3 pr-10 text-lg font-medium text-white hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition-all duration-300 hover:bg-white/20 focus:bg-white/20 shadow-lg cursor-pointer"
                @change="handleFilter"
              >
                <option value="" class="bg-gray-800 text-white">All Status</option>
                <option value="active" class="bg-gray-800 text-white">
                  Active (Non-Cancelled)
                </option>
                <option value="pending" class="bg-gray-800 text-white">Pending</option>
                <option value="completed" class="bg-gray-800 text-white">Completed</option>
                <option value="rejected" class="bg-gray-800 text-white">Rejected</option>
                <option value="cancelled" class="bg-gray-800 text-white">Cancelled</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none">
                <i class="fas fa-chevron-down text-blue-300 text-sm drop-shadow-sm"></i>
              </div>
            </div>

            <!-- Enhanced Export to Excel Button -->
            <button
              @click="exportToExcel"
              :disabled="loading || !hasData"
              class="inline-flex items-center px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white text-lg font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl backdrop-blur-sm border border-green-400/30"
            >
              <i class="fas fa-file-excel mr-2 text-sm drop-shadow-sm"></i>
              <span v-if="!exporting" class="drop-shadow-sm">Export</span>
              <span v-else class="flex items-center">
                <OrbitingDots size="xs" class="mr-2" />
                <span class="drop-shadow-sm">Exporting...</span>
              </span>
            </button>

            <!-- Enhanced Refresh Button -->
            <button
              @click="refreshData"
              :disabled="loading"
              class="inline-flex items-center px-3 py-3 bg-white/15 text-white text-lg font-medium rounded-xl hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl backdrop-blur-sm border border-white/20"
            >
              <i
                :class="['fas fa-sync-alt', loading ? 'fa-spin' : '', 'text-sm drop-shadow-sm']"
              ></i>
            </button>
          </div>
        </div>

        <!-- Enhanced Tabs -->
        <div class="border-b border-blue-300/30 mb-6">
          <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button
              v-for="t in tabs"
              :key="t.key"
              @click="selectTab(t.key)"
              :class="[
                'px-6 py-4 text-lg font-semibold border-b-2 transition-all duration-300 flex items-center rounded-t-xl backdrop-blur-sm transform hover:scale-105',
                activeTab === t.key
                  ? 'border-blue-400 text-white bg-blue-600/30 shadow-lg'
                  : 'border-transparent text-blue-200 hover:text-white hover:border-blue-400/50 hover:bg-blue-700/20'
              ]"
            >
              <i :class="[t.icon, 'mr-3 text-base drop-shadow-sm']"></i>
              <span class="drop-shadow-sm">{{ t.label }}</span>
              <span
                :class="[
                  'ml-3 px-3 py-1 text-xs rounded-full font-bold shadow-md',
                  activeTab === t.key ? 'bg-blue-500 text-white' : 'bg-white/20 text-blue-100'
                ]"
              >
                {{ totals[t.key] || 0 }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Enhanced Tables Section -->
        <div
          class="bg-white/10 rounded-2xl border-2 border-blue-300/30 backdrop-blur-sm shadow-2xl overflow-visible"
        >
          <!-- Jeeva Users Table -->
          <div v-if="activeTab === 'jeeva'" class="min-h-96 overflow-visible">
            <DataTable
              title="Jeeva System Users"
              :columns="jeevaColumns"
              :rows="jeevaUsers"
              :loading="loading"
              :total="totals.jeeva"
              @paginate="onPaginate('jeeva', $event)"
            >
              <template #actions="{ row }">
                <div
                  class="relative flex justify-center items-center"
                  :class="{ 'z-[100]': activeActionRowId === row.id }"
                >
                  <button
                    @click.stop="toggleActionMenu(row.id)"
                    class="p-2 text-blue-300 hover:text-white hover:bg-white/20 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    title="Actions"
                  >
                    <i class="fas fa-ellipsis-v"></i>
                  </button>

                  <!-- Dropdown Menu - positioned above and to the left -->
                  <div
                    v-if="activeActionRowId === row.id"
                    class="absolute right-full mr-2 top-0 w-52 bg-slate-900/95 border border-blue-400/30 rounded-xl shadow-2xl backdrop-blur-md z-[100] py-1 action-dropdown"
                  >
                    <button
                      @click="handleAction('download', row)"
                      class="w-full text-left px-4 py-3 text-sm text-blue-100 hover:bg-blue-600/40 hover:text-white transition-colors flex items-center group/item"
                    >
                      <i
                        class="fas fa-file-pdf mr-3 text-blue-400 group-hover/item:scale-110 transition-transform"
                      ></i>
                      <span>Download PDF</span>
                    </button>
                    <div class="h-px bg-white/10 mx-2"></div>
                    <button
                      @click="handleAction('delete', row)"
                      class="w-full text-left px-4 py-3 text-sm text-red-300 hover:bg-red-600/40 hover:text-white transition-colors flex items-center group/item"
                    >
                      <i
                        class="fas fa-trash-alt mr-3 text-red-500 group-hover/item:scale-110 transition-transform"
                      ></i>
                      <span>Delete Request</span>
                    </button>
                  </div>
                </div>
              </template>
            </DataTable>
          </div>

          <!-- Wellsoft Users Table -->
          <div v-if="activeTab === 'wellsoft'" class="min-h-96 overflow-visible">
            <DataTable
              title="Wellsoft System Users"
              :columns="wellsoftColumns"
              :rows="wellsoftUsers"
              :loading="loading"
              :total="totals.wellsoft"
              @paginate="onPaginate('wellsoft', $event)"
            >
              <template #actions="{ row }">
                <div
                  class="relative flex justify-center items-center"
                  :class="{ 'z-[100]': activeActionRowId === row.id }"
                >
                  <button
                    @click.stop="toggleActionMenu(row.id)"
                    class="p-2 text-blue-300 hover:text-white hover:bg-white/20 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    title="Actions"
                  >
                    <i class="fas fa-ellipsis-v"></i>
                  </button>

                  <!-- Dropdown Menu - positioned to the left -->
                  <div
                    v-if="activeActionRowId === row.id"
                    class="absolute right-full mr-2 top-0 w-52 bg-slate-900/95 border border-blue-400/30 rounded-xl shadow-2xl backdrop-blur-md z-[100] py-1 action-dropdown"
                  >
                    <button
                      @click="handleAction('download', row)"
                      class="w-full text-left px-4 py-3 text-sm text-blue-100 hover:bg-blue-600/40 hover:text-white transition-colors flex items-center group/item"
                    >
                      <i
                        class="fas fa-file-pdf mr-3 text-blue-400 group-hover/item:scale-110 transition-transform"
                      ></i>
                      <span>Download PDF</span>
                    </button>
                    <div class="h-px bg-white/10 mx-2"></div>
                    <button
                      @click="handleAction('delete', row)"
                      class="w-full text-left px-4 py-3 text-sm text-red-300 hover:bg-red-600/40 hover:text-white transition-colors flex items-center group/item"
                    >
                      <i
                        class="fas fa-trash-alt mr-3 text-red-500 group-hover/item:scale-110 transition-transform"
                      ></i>
                      <span>Delete Request</span>
                    </button>
                  </div>
                </div>
              </template>
            </DataTable>
          </div>

          <!-- Internet Users Table -->
          <div v-if="activeTab === 'internet'" class="min-h-96 overflow-visible">
            <DataTable
              title="Internet Access Users"
              :columns="internetColumns"
              :rows="internetUsers"
              :loading="loading"
              :total="totals.internet"
              @paginate="onPaginate('internet', $event)"
            >
              <template #actions="{ row }">
                <div
                  class="relative flex justify-center items-center"
                  :class="{ 'z-[100]': activeActionRowId === row.id }"
                >
                  <button
                    @click.stop="toggleActionMenu(row.id)"
                    class="p-2 text-blue-300 hover:text-white hover:bg-white/20 rounded-full transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    title="Actions"
                  >
                    <i class="fas fa-ellipsis-v"></i>
                  </button>

                  <!-- Dropdown Menu - positioned to the left -->
                  <div
                    v-if="activeActionRowId === row.id"
                    class="absolute right-full mr-2 top-0 w-52 bg-slate-900/95 border border-blue-400/30 rounded-xl shadow-2xl backdrop-blur-md z-[100] py-1 action-dropdown"
                  >
                    <button
                      @click="handleAction('download', row)"
                      class="w-full text-left px-4 py-3 text-sm text-blue-100 hover:bg-blue-600/40 hover:text-white transition-colors flex items-center group/item"
                    >
                      <i
                        class="fas fa-file-pdf mr-3 text-blue-400 group-hover/item:scale-110 transition-transform"
                      ></i>
                      <span>Download PDF</span>
                    </button>
                    <div class="h-px bg-white/10 mx-2"></div>
                    <button
                      @click="handleAction('delete', row)"
                      class="w-full text-left px-4 py-3 text-sm text-red-300 hover:bg-red-600/40 hover:text-white transition-colors flex items-center group/item"
                    >
                      <i
                        class="fas fa-trash-alt mr-3 text-red-500 group-hover/item:scale-110 transition-transform"
                      ></i>
                      <span>Delete Request</span>
                    </button>
                  </div>
                </div>
              </template>
            </DataTable>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
  import {
    fetchJeevaUsers,
    fetchWellsoftUsers,
    fetchInternetUsers,
    deleteUserAccess,
    downloadUserAccessPdf
  } from '@/utils/api'
  import DataTable from './tables/DataTable.vue'
  import OrbitingDots from '@/components/common/OrbitingDots.vue'
  import Swal from 'sweetalert2'

  export default {
    name: 'UserManagementDropdown',
    components: {
      DataTable,
      OrbitingDots
    },
    props: {
      defaultOpen: { type: Boolean, default: false }
    },
    data() {
      return {
        open: false,
        activeTab: 'jeeva',
        loading: false,
        exporting: false,
        searchQuery: '',
        filterStatus: '',
        activeActionRowId: null,
        pagination: {
          jeeva: { page: 1, perPage: 10 },
          wellsoft: { page: 1, perPage: 10 },
          internet: { page: 1, perPage: 10 }
        },
        jeevaUsers: [],
        wellsoftUsers: [],
        internetUsers: [],
        totals: { jeeva: 0, wellsoft: 0, internet: 0 },
        tabs: [
          { key: 'jeeva', label: 'Jeeva Users', icon: 'fas fa-database' },
          {
            key: 'wellsoft',
            label: 'Wellsoft Users',
            icon: 'fas fa-laptop-medical'
          },
          { key: 'internet', label: 'Internet Users', icon: 'fas fa-wifi' }
        ],
        // Columns should mirror each form fields; here's a representative mapping based on current components
        jeevaColumns: [
          { key: 'requestNumber', label: 'Request ID' },
          { key: 'pfNumber', label: 'PF Number' },
          { key: 'staffName', label: 'Staff Name' },
          { key: 'phoneNumber', label: 'Phone' },
          { key: 'department', label: 'Department' },
          { key: 'signature', label: 'Signature' },
          { key: 'date', label: 'Date Issued' },
          { key: 'requestType', label: 'Action Requested' },
          {
            key: 'selectedModules',
            label: 'Modules',
            format: (v) => (Array.isArray(v) ? v.join(', ') : v)
          },
          { key: 'accessType', label: 'Access' },
          {
            key: 'tempDate',
            label: 'Temporary Until',
            format: (v) => (v ? `${v.day || ''}/${v.month || ''}/${v.year || ''}` : '')
          },
          { key: 'hod_name', label: 'User-HOD' },
          { key: 'hod_comments', label: 'Comments' },
          { key: 'divisional_director_name', label: 'Divisional Director' },
          { key: 'ict_director_name', label: 'Director ICT' },
          { key: 'head_it_name', label: 'HOD(IT)' },
          { key: 'ict_officer_name', label: 'ICT OFFICER' },
          { key: 'status', label: 'Status', slot: 'status' },
          { key: 'actions', label: 'Action' }
        ],
        wellsoftColumns: [
          { key: 'requestNumber', label: 'Request ID' },
          { key: 'pfNumber', label: 'PF Number' },
          { key: 'staffName', label: 'Staff Name' },
          { key: 'phoneNumber', label: 'Phone' },
          { key: 'department', label: 'Department' },
          { key: 'signature', label: 'Signature' },
          { key: 'date', label: 'Date Issued' },
          { key: 'requestType', label: 'Action Requested' },
          {
            key: 'selectedModules',
            label: 'Modules',
            format: (v) => (Array.isArray(v) ? v.join(', ') : v)
          },
          { key: 'accessType', label: 'Access' },
          {
            key: 'tempDate',
            label: 'Temporary Until',
            format: (v) => (v ? `${v.day || ''}/${v.month || ''}/${v.year || ''}` : '')
          },
          { key: 'hod_name', label: 'User-HOD' },
          { key: 'hod_comments', label: 'Comments' },
          { key: 'divisional_director_name', label: 'Divisional Director' },
          { key: 'ict_director_name', label: 'Director ICT' },
          { key: 'head_it_name', label: 'HOD(IT)' },
          { key: 'ict_officer_name', label: 'ICT OFFICER' },
          { key: 'status', label: 'Status', slot: 'status' },
          { key: 'actions', label: 'Action' }
        ],
        internetColumns: [
          { key: 'requestNumber', label: 'Request ID' },
          { key: 'employeeFullName', label: 'Employee Name' },
          { key: 'pfNumber', label: 'PF Number' },
          { key: 'phoneNumber', label: 'Phone' },
          { key: 'department', label: 'Department' },
          { key: 'designation', label: 'Designation' },
          {
            key: 'internetPurposes',
            label: 'Purposes',
            format: (v) => (Array.isArray(v) ? v.filter(Boolean).join(', ') : v)
          },
          { key: 'signature', label: 'Signature' },
          { key: 'date', label: 'date-issued' },
          { key: 'hod_name', label: 'User-HOD' },
          { key: 'hod_comments', label: 'Comments' },
          { key: 'divisional_director_name', label: 'Divisional Director' },
          { key: 'ict_director_name', label: 'Director ICT' },
          { key: 'head_it_name', label: 'HOD(IT)' },
          { key: 'ict_officer_name', label: 'ICT OFFICER' },
          { key: 'status', label: 'Status', slot: 'status' },
          { key: 'actions', label: 'Action' }
        ]
      }
    },
    computed: {
      hasData() {
        const currentData = this.getCurrentData()
        return currentData && currentData.length > 0
      },
      filteredData() {
        return this.getCurrentData()
      }
    },
    methods: {
      getCurrentData() {
        switch (this.activeTab) {
          case 'jeeva':
            return this.jeevaUsers
          case 'wellsoft':
            return this.wellsoftUsers
          case 'internet':
            return this.internetUsers
          default:
            return []
        }
      },
      handleSearch() {
        // Debounce search to avoid too many API calls
        clearTimeout(this.searchTimeout)
        this.searchTimeout = setTimeout(() => {
          this.loadData()
        }, 300)
      },
      clearSearch() {
        this.searchQuery = ''
        this.loadData()
      },
      handleFilter() {
        this.loadData()
      },
      refreshData() {
        this.loadData()
      },
      selectTab(key) {
        this.activeTab = key
        this.loadData()
      },
      async exportToExcel() {
        if (!this.hasData) return

        try {
          this.exporting = true

          // Get current data
          const data = this.filteredData
          const columns = this.getCurrentColumns()

          // Create CSV content
          const headers = columns.map((col) => col.label).join(',')
          const rows = data.map((row) => {
            return columns
              .map((col) => {
                let value = this.getCellValue(row, col)
                // Escape commas and quotes in CSV
                if (typeof value === 'string' && (value.includes(',') || value.includes('"'))) {
                  value = `"${value.replace(/"/g, '""')}"`
                }
                return value || ''
              })
              .join(',')
          })

          const csvContent = [headers, ...rows].join('\n')

          // Create and download file
          const blob = new Blob([csvContent], {
            type: 'text/csv;charset=utf-8;'
          })
          const link = document.createElement('a')
          const url = URL.createObjectURL(blob)
          link.setAttribute('href', url)
          link.setAttribute(
            'download',
            `${this.activeTab}_users_${new Date().toISOString().split('T')[0]}.csv`
          )
          link.style.visibility = 'hidden'
          document.body.appendChild(link)
          link.click()
          document.body.removeChild(link)

          // Show success message (you can replace this with a toast notification)
          console.log('Export completed successfully')
        } catch (error) {
          console.error('Export failed:', error)
          // Show error message (you can replace this with a toast notification)
        } finally {
          this.exporting = false
        }
      },
      getCurrentColumns() {
        switch (this.activeTab) {
          case 'jeeva':
            return this.jeevaColumns
          case 'wellsoft':
            return this.wellsoftColumns
          case 'internet':
            return this.internetColumns
          default:
            return []
        }
      },
      getCellValue(row, col) {
        const value = col.key.split('.').reduce((acc, k) => (acc ? acc[k] : undefined), row)
        return col.format ? col.format(value) : value
      },
      async loadData() {
        // Prevent duplicate calls while loading
        if (this.loading) return

        try {
          this.loading = true
          const { page, perPage } = this.pagination[this.activeTab]
          const searchParams = {
            page,
            perPage,
            search: this.searchQuery,
            status: this.filterStatus
          }

          if (this.activeTab === 'jeeva') {
            const res = await fetchJeevaUsers(searchParams)
            this.jeevaUsers = res.items || []
            this.totals.jeeva = res.total || this.jeevaUsers.length
          } else if (this.activeTab === 'wellsoft') {
            const res = await fetchWellsoftUsers(searchParams)
            this.wellsoftUsers = res.items || []
            this.totals.wellsoft = res.total || this.wellsoftUsers.length
          } else if (this.activeTab === 'internet') {
            const res = await fetchInternetUsers(searchParams)
            this.internetUsers = res.items || []
            this.totals.internet = res.total || this.internetUsers.length
          }
        } catch (e) {
          // Ignore abort errors (these happen when requests are cancelled)
          if (e.message === 'Request aborted' || e.message?.includes('aborted')) {
            console.log('Request was aborted, ignoring')
            return
          }
          console.error('Failed to load users', e)
          // Set some dummy data for demonstration
          this.totals = { jeeva: 0, wellsoft: 0, internet: 0 }
        } finally {
          this.loading = false
        }
      },
      onPaginate(type, { page, perPage }) {
        this.pagination[type] = { page, perPage }
        this.activeTab = type
        this.loadData()
      },
      toggleActionMenu(rowId) {
        if (this.activeActionRowId === rowId) {
          this.activeActionRowId = null
        } else {
          this.activeActionRowId = rowId
        }
      },
      closeActionMenu() {
        this.activeActionRowId = null
      },
      handleAction(action, row) {
        if (action === 'download') {
          this.downloadPdf(row)
        } else if (action === 'delete') {
          this.confirmDelete(row)
        }
        this.closeActionMenu()
      },
      async confirmDelete(row) {
        const result = await Swal.fire({
          title: 'Are you sure?',
          text: `You are about to delete the request for ${row.staffName || row.employeeFullName}. This action cannot be undone.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, delete it!',
          background: '#1e293b',
          color: '#ffffff'
        })

        if (result.isConfirmed) {
          try {
            this.loading = true
            const res = await deleteUserAccess(row.id)
            if (res.success) {
              Swal.fire({
                title: 'Deleted!',
                text: 'The request has been deleted.',
                icon: 'success',
                background: '#1e293b',
                color: '#ffffff'
              })
              this.loadData()
            } else {
              throw new Error(res.message)
            }
          } catch (error) {
            Swal.fire({
              title: 'Error!',
              text: error.message || 'Failed to delete the request.',
              icon: 'error',
              background: '#1e293b',
              color: '#ffffff'
            })
          } finally {
            this.loading = false
          }
        }
      },
      async downloadPdf(row) {
        try {
          this.loading = true
          const res = await downloadUserAccessPdf(row.id)
          if (!res.success) {
            throw new Error(res.message)
          }
        } catch (error) {
          console.error('Failed to download PDF:', error)
          Swal.fire({
            title: 'Error!',
            text: error.message || 'Failed to download PDF.',
            icon: 'error',
            background: '#1e293b',
            color: '#ffffff'
          })
        } finally {
          this.loading = false
        }
      }
    },
    mounted() {
      this.open = this.defaultOpen
      // Load data only here, not in watch, to prevent race condition
      if (this.open) {
        this.$nextTick(() => {
          this.loadData()
        })
      }
      window.addEventListener('click', this.closeActionMenu)
    },
    beforeUnmount() {
      window.removeEventListener('click', this.closeActionMenu)
    },
    watch: {
      open(val, oldVal) {
        // Only load data when open changes from false to true (not on initial mount)
        if (val && oldVal === false) this.loadData()
      }
    }
  }
</script>

<style scoped>
  /* Medical Glass morphism effects */
  .medical-card {
    position: relative;
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

  .header-shadow {
    box-shadow:
      0 8px 16px -4px rgba(0, 0, 0, 0.15),
      0 4px 8px -2px rgba(59, 130, 246, 0.2),
      0 2px 4px -1px rgba(0, 0, 0, 0.1),
      inset 0 2px 4px rgba(255, 255, 255, 0.15),
      inset 0 -2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
  }

  .header-shadow::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
      135deg,
      rgba(255, 255, 255, 0.1) 0%,
      transparent 50%,
      rgba(0, 0, 0, 0.05) 100%
    );
    pointer-events: none;
    border-radius: inherit;
  }

  .icon-shadow {
    box-shadow:
      0 16px 32px rgba(59, 130, 246, 0.4),
      0 12px 24px rgba(59, 130, 246, 0.3),
      0 8px 16px rgba(0, 0, 0, 0.15),
      0 4px 8px rgba(0, 0, 0, 0.1),
      0 0 0 2px rgba(255, 255, 255, 0.1),
      inset 0 2px 4px rgba(255, 255, 255, 0.25),
      inset 0 -2px 4px rgba(0, 0, 0, 0.1);
  }

  .action-dropdown {
    animation: slideLeft 0.2s ease-out;
    transform-origin: top right;
  }

  @keyframes slideLeft {
    from {
      opacity: 0;
      transform: scale(0.95) translateX(10px);
    }
    to {
      opacity: 1;
      transform: scale(1) translateX(0);
    }
  }
</style>
