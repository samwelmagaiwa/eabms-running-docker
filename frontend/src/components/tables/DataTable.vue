<template>
  <div
    class="bg-white rounded-lg border border-gray-200 shadow-sm transition-all duration-300 overflow-visible"
  >
    <!-- Enhanced Header -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
            <i class="fas fa-table text-indigo-600"></i>
          </div>
          <div>
            <h3 class="text-2xl font-bold text-gray-900">
              <slot name="title">{{ title }}</slot>
            </h3>
            <p class="text-lg text-gray-600" v-if="total">{{ total }} total records</p>
          </div>
        </div>
        <div class="flex items-center space-x-4">
          <div class="text-lg text-gray-500" v-if="total">
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800"
            >
              {{ total }} Records
            </span>
          </div>
          <button class="text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-download"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Enhanced Table Container -->
    <div class="overflow-x-auto overflow-y-visible bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <!-- Enhanced Header -->
        <thead class="bg-gradient-to-r from-indigo-50 to-blue-50">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              scope="col"
              class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap border-b border-gray-200"
            >
              <div class="flex items-center space-x-1">
                <span>{{ col.label }}</span>
                <i class="fas fa-sort text-gray-400 text-xs"></i>
              </div>
            </th>
          </tr>
        </thead>
        <!-- Enhanced Body -->
        <tbody class="bg-white divide-y divide-gray-100">
          <!-- Loading State -->
          <tr v-if="loading">
            <td :colspan="columns.length" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center">
                <div
                  class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-3"
                ></div>
                <p class="text-gray-500 font-medium text-lg">Loading data...</p>
              </div>
            </td>
          </tr>
          <!-- Empty State -->
          <tr v-else-if="!rows || rows.length === 0">
            <td :colspan="columns.length" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center">
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mb-3">
                  <i class="fas fa-inbox text-gray-400 text-xl"></i>
                </div>
                <p class="text-gray-500 font-medium text-lg">No records found</p>
                <p class="text-gray-400 text-lg">Try adjusting your search criteria</p>
              </div>
            </td>
          </tr>
          <!-- Data Rows -->
          <tr
            v-else
            v-for="(row, idx) in rows"
            :key="idx"
            class="hover:bg-indigo-50 transition-colors duration-150 border-b border-gray-100"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-6 py-4 text-lg text-gray-700 align-top"
            >
              <slot :name="col.key" :row="row">
                <template v-if="col.slot === 'status'">
                  <span
                    :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold',
                      getStatusClasses(row.status)
                    ]"
                  >
                    <span
                      class="w-2 h-2 rounded-full mr-2"
                      :class="getStatusDotClass(row.status)"
                    ></span>
                    {{ row.status || 'Pending' }}
                  </span>
                </template>
                <template v-else>
                  <div class="max-w-xs truncate" :title="getValue(row, col)">
                    {{ getValue(row, col) }}
                  </div>
                </template>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Enhanced Pagination -->
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 relative z-0">
      <div class="flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0">
        <div class="flex items-center space-x-4">
          <div class="text-lg text-gray-700">
            Showing <span class="font-semibold">{{ pageStart + 1 }}</span> -
            <span class="font-semibold">{{
              Math.min(pageStart + perPage, total || rows.length)
            }}</span>
            of
            <span class="font-semibold">{{ total || rows.length }}</span>
            results
          </div>
          <div class="flex items-center space-x-2">
            <label class="text-lg text-gray-600">Show:</label>
            <select
              class="border border-gray-300 rounded-md px-3 py-1 text-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              v-model.number="perPageLocal"
              @change="changePerPage"
            >
              <option :value="10">10</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <button
            class="px-4 py-2 text-lg font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            :disabled="page === 1"
            @click="goTo(page - 1)"
          >
            <i class="fas fa-chevron-left mr-1"></i>
            Previous
          </button>
          <button
            class="px-4 py-2 text-lg font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            :disabled="pageEnd >= (total || rows.length)"
            @click="goTo(page + 1)"
          >
            Next
            <i class="fas fa-chevron-right ml-1"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'DataTable',
    props: {
      title: { type: String, default: '' },
      columns: { type: Array, required: true },
      rows: { type: Array, default: () => [] },
      loading: { type: Boolean, default: false },
      total: { type: Number, default: 0 },
      page: { type: Number, default: 1 },
      perPage: { type: Number, default: 10 }
    },
    data() {
      return {
        pageLocal: this.page,
        perPageLocal: this.perPage
      }
    },
    computed: {
      pageStart() {
        return (this.pageLocal - 1) * this.perPageLocal
      },
      pageEnd() {
        return this.pageStart + this.perPageLocal
      }
    },
    methods: {
      goTo(newPage) {
        this.pageLocal = newPage
        this.$emit('paginate', {
          page: this.pageLocal,
          perPage: this.perPageLocal
        })
      },
      changePerPage() {
        this.pageLocal = 1
        this.$emit('paginate', {
          page: this.pageLocal,
          perPage: this.perPageLocal
        })
      },
      // Support nested keys like 'approvals.hod.name'
      getValue(row, col) {
        const raw = col.key.split('.').reduce((acc, k) => (acc ? acc[k] : undefined), row)
        return col.format ? col.format(raw) : raw
      },
      // Get status badge classes based on status value
      getStatusClasses(status) {
        const statusLower = (status || '').toLowerCase()
        if (statusLower === 'completed' || statusLower === 'active' || statusLower === 'implemented') {
          return 'bg-green-100 text-green-800 border border-green-200'
        } else if (statusLower === 'cancelled' || statusLower === 'inactive') {
          return 'bg-gray-100 text-gray-800 border border-gray-200'
        } else if (statusLower === 'rejected') {
          return 'bg-red-100 text-red-800 border border-red-200'
        } else if (statusLower.includes('pending')) {
          return 'bg-yellow-100 text-yellow-800 border border-yellow-200'
        }
        return 'bg-blue-100 text-blue-800 border border-blue-200'
      },
      // Get status dot color class
      getStatusDotClass(status) {
        const statusLower = (status || '').toLowerCase()
        if (statusLower === 'completed' || statusLower === 'active' || statusLower === 'implemented') {
          return 'bg-green-500'
        } else if (statusLower === 'cancelled' || statusLower === 'inactive') {
          return 'bg-gray-500'
        } else if (statusLower === 'rejected') {
          return 'bg-red-500'
        } else if (statusLower.includes('pending')) {
          return 'bg-yellow-500'
        }
        return 'bg-blue-500'
      }
    },
    watch: {
      page(val) {
        this.pageLocal = val
      },
      perPage(val) {
        this.perPageLocal = val
      }
    }
  }
</script>

<style scoped>
  /* Responsive helpers */
  @media (max-width: 640px) {
    table thead {
      display: none;
    }
    table tr {
      display: block;
      margin-bottom: 0.75rem;
      border-bottom: 1px solid #e5e7eb;
    }
    table td {
      display: flex;
      justify-content: space-between;
    }
    table td::before {
      content: attr(data-label);
      font-weight: 600;
      color: #6b7280;
      margin-right: 1rem;
    }
  }
</style>
