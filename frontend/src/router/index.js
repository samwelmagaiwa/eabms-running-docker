import { createRouter, createWebHistory } from 'vue-router'
import { h } from 'vue'
import { ROLES } from '../utils/permissions'
import { preloadRouteBasedImages } from '../utils/imagePreloader'
import { enhancedNavigationGuard } from '../utils/routeGuards'
import combinedAccessService from '../services/combinedAccessService'

// Lazy load LoginPageWrapper as well for better initial bundle size
const LoginPageWrapper = () =>
  import(/* webpackChunkName: "auth" */ '../components/LoginPageWrapper.vue')

const routes = [
  // Public routes
  {
    path: '/',
    name: 'LoginPage',
    component: LoginPageWrapper,
    meta: {
      requiresAuth: false,
      isPublic: true
    }
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPageWrapper,
    meta: {
      requiresAuth: false,
      isPublic: true
    }
  },
  {
    path: '/ict-policy',
    name: 'IctPolicy',
    component: () => import(/* webpackChunkName: "public" */ '../components/IctPolicy.vue'),
    meta: {
      requiresAuth: false,
      isPublic: true
    }
  },
  {
    path: '/terms-of-service',
    name: 'TermsOfService',
    component: () => import(/* webpackChunkName: "public" */ '../components/TermsOfService.vue'),
    meta: {
      requiresAuth: false,
      isPublic: true
    }
  },
  {
    path: '/api-docs',
    name: 'ApiDocsRedirect',
    // Simple SPA route that redirects to the backend modern API docs UI
    beforeEnter: () => {
      window.location.href = '/api-docs-modern'
    },
    meta: {
      requiresAuth: false,
      isPublic: true
    }
  },

  // Dashboard routes
  {
    path: '/admin-dashboard',
    name: 'AdminDashboard',
    component: () => import('../components/admin/AdminDashboard.vue'),
    meta: { requiresAuth: true, roles: [ROLES.ADMIN] }
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboardAlt',
    component: () => import('../components/admin/AdminDashboard.vue'),
    meta: { requiresAuth: true, roles: [ROLES.ADMIN] }
  },

  {
    path: '/admin/user-roles',
    name: 'UserRoleAssignment',
    component: () => import('../components/admin/UserRoleAssignment.vue'),
    meta: { requiresAuth: true, roles: [ROLES.ADMIN] }
  },
  {
    path: '/admin/clean-role-assignment',
    name: 'CleanRoleAssignment',
    component: () => import('../components/admin/CleanRoleAssignment.vue'),
    meta: { requiresAuth: true, roles: [ROLES.ADMIN] }
  },

  {
    path: '/admin/departments',
    name: 'DepartmentManagement',
    component: () => import('../components/admin/DepartmentManagement.vue'),
    meta: { requiresAuth: true, roles: [ROLES.ADMIN] }
  },
  {
    path: '/admin/device-inventory',
    name: 'DeviceInventoryManagement',
    component: () => import('../components/admin/DeviceInventoryManagement.vue'),
    meta: { requiresAuth: true, roles: [ROLES.ADMIN] }
  },
  {
    path: '/user-dashboard',
    name: 'UserDashboard',
    component: () => import('../components/UserDashboard.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.STAFF, ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },
  {
    path: '/dict-dashboard',
    name: 'DictDashboard',
    component: () => import('../components/DictDashboard.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_DIRECTOR]
    }
  },
  {
    path: '/hod-dashboard',
    name: 'HodDashboard',
    component: () => import('../components/HodDashboard.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_DEPARTMENT]
    }
  },
  {
    path: '/hod-dashboard/create-user',
    name: 'HodCreateUser',
    component: () => import('../components/views/hod/HodCreateUser.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_DEPARTMENT]
    }
  },
  {
    path: '/divisional-dashboard',
    name: 'DivisionalDashboard',
    component: () => import('../components/DivisionalDashboard.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.DIVISIONAL_DIRECTOR]
    }
  },
  {
    path: '/ict-dashboard',
    redirect: '/ict-dashboard/access-requests'
  },
  {
    path: '/head_of_it-dashboard',
    redirect: '/head_of_it-dashboard/combined-requests'
  },

  // Onboarding flow (for first-time users)
  {
    path: '/onboarding',
    name: 'Onboarding',
    component: () => import('../components/OnboardingPage.vue'),
    meta: {
      requiresAuth: true,
      roles: Object.values(ROLES).filter((role) => role !== ROLES.ADMIN) // All roles except admin
    }
  },

  // Settings (accessible to all authenticated users)
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('../components/SettingsPage.vue'),
    meta: {
      requiresAuth: true,
      roles: Object.values(ROLES)
    }
  },

  // User Profile (accessible to all authenticated users)
  {
    path: '/profile',
    name: 'UserProfile',
    component: () => import('../components/UserProfile.vue'),
    meta: {
      requiresAuth: true,
      roles: Object.values(ROLES)
    }
  },

  // Access approval forms
  {
    path: '/jeeva-access',
    name: 'JeevaAccessForm',
    component: () => import('../components/views/forms/JeevaAccessForm.vue'),
    meta: {
      requiresAuth: true,
      roles: [
        ROLES.DIVISIONAL_DIRECTOR,
        ROLES.HEAD_OF_DEPARTMENT,
        ROLES.ICT_DIRECTOR,
        ROLES.HEAD_OF_IT,
        ROLES.ICT_OFFICER
      ]
    }
  },
  {
    path: '/wellsoft-access',
    name: 'WellsoftAccessForm',
    component: () => import('../components/views/forms/wellSoftAccessForm.vue'),
    meta: {
      requiresAuth: true,
      roles: [
        ROLES.DIVISIONAL_DIRECTOR,
        ROLES.HEAD_OF_DEPARTMENT,
        ROLES.ICT_DIRECTOR,
        ROLES.HEAD_OF_IT,
        ROLES.ICT_OFFICER
      ]
    }
  },
  {
    path: '/internet-access',
    name: 'InternetAccessForm',
    component: () => import('../components/views/forms/internetAccessForm.vue'),
    meta: {
      requiresAuth: true,
      roles: [
        ROLES.DIVISIONAL_DIRECTOR,
        ROLES.HEAD_OF_DEPARTMENT,
        ROLES.ICT_DIRECTOR,
        ROLES.HEAD_OF_IT,
        ROLES.ICT_OFFICER
      ]
    }
  },
  // Deprecated: old public both-service-form routes (use HOD-specific route instead)
  // {
  //   path: '/both-service-form',
  //   name: 'BothServiceForm',
  //   component: () => import('../components/views/forms/both-service-form.vue'),
  //   meta: {
  //     requiresAuth: true,
  //     roles: [
  //       ROLES.DIVISIONAL_DIRECTOR,
  //       ROLES.HEAD_OF_DEPARTMENT,
  //       ROLES.ICT_DIRECTOR,
  //       ROLES.HEAD_OF_IT,
  //       ROLES.ICT_OFFICER,
  //       ROLES.STAFF
  //     ]
  //   },
  //   alias: ['/both-service-from']
  // },
  // {
  //   path: '/both-service-form/:id',
  //   name: 'BothServiceFormReview',
  //   component: () => import('../components/views/forms/both-service-form.vue'),
  //   meta: {
  //     requiresAuth: true,
  //     roles: [
  //       ROLES.DIVISIONAL_DIRECTOR,
  //       ROLES.HEAD_OF_DEPARTMENT,
  //       ROLES.ICT_DIRECTOR,
  //       ROLES.HEAD_OF_IT,
  //       ROLES.ICT_OFFICER,
  //       ROLES.STAFF
  //     ]
  //   }
  // },
  // HOD-friendly path for reviewing combined requests
  {
    path: '/hod-combined-requests/both-service-form/:id',
    name: 'HodBothServiceFormReview',
    component: () => import('../components/views/forms/both-service-form.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_DEPARTMENT]
    }
  },
  // Deprecated: old edit route for both-service-form
  // {
  //   path: '/both-service-form/:id/edit',
  //   name: 'BothServiceFormEdit',
  //   component: () => import('../components/views/forms/both-service-form.vue'),
  //   meta: {
  //     requiresAuth: true,
  //     roles: [
  //       ROLES.DIVISIONAL_DIRECTOR,
  //       ROLES.HEAD_OF_DEPARTMENT,
  //       ROLES.ICT_DIRECTOR,
  //       ROLES.HEAD_OF_IT,
  //       ROLES.ICT_OFFICER,
  //       ROLES.STAFF
  //     ]
  //   }
  // },

  // User submission forms
  {
    path: '/user-combined-form',
    name: 'UserCombinedForm',
    component: () => import('../components/views/forms/UserCombinedAccessForm.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.STAFF, ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },
  {
    path: '/booking-service',
    name: 'BookingService',
    component: () => import('../components/views/booking/BookingService.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.STAFF, ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },
  {
    path: '/edit-booking-request',
    name: 'EditBookingRequest',
    component: () => import('../components/views/booking/EditBookingRequest.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.STAFF, ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },
  {
    path: '/request-status',
    name: 'RequestStatusPage',
    component: () => import('../components/views/requests/RequestStatusPage.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.STAFF, ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },
  {
    path: '/request-details',
    name: 'StaffRequestDetails',
    component: () => import('../components/views/requests/InternalAccessDetails.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.STAFF, ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },

  // ICT Approval routes (ICT Officer & Secretary, with separate entry points)
  {
    path: '/ict-approval/requests',
    name: 'RequestsList',
    component: () => import('../components/views/ict-approval/RequestsList.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_OFFICER]
    }
  },
  {
    path: '/secretary-approval/requests',
    name: 'SecretaryRequestsList',
    component: () => import('../components/views/ict-approval/RequestsList.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.SECRETARY_ICT]
    }
  },
  // ICT Officer review route for combined both-service form
  {
    path: '/ict-dashboard/both-service-form/:id',
    name: 'IctOfficerBothServiceFormReview',
    component: () => import('../components/views/forms/both-service-form.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_OFFICER]
    }
  },
  {
    path: '/ict-dashboard/access-requests',
    name: 'IctDashboard',
    component: () => import('../components/views/ict-officer/AccessRequests.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_OFFICER]
    }
  },
  {
    path: '/ict-dashboard/access-service',
    name: 'IctAccessService',
    redirect: '/user-dashboard',
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },
  {
    path: '/user-security-access/:id',
    name: 'UserSecurityAccess',
    component: () => import('../components/views/UserSecurityAccess.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_OFFICER, ROLES.STAFF, ROLES.SECRETARY_ICT] // ICT Officers/Secretary ICT can grant access, Staff can view read-only
    }
  },
  {
    path: '/ict-approval/requests-simple',
    name: 'RequestsListSimple',
    component: () => import('../components/views/ict-approval/RequestsListSimple.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },
  {
    path: '/ict-approval/requests-original',
    name: 'RequestsListOriginal',
    component: () => import('../components/views/ict-approval/RequestsList.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_OFFICER, ROLES.SECRETARY_ICT]
    }
  },
  {
    path: '/ict-approval/request/:id',
    name: 'RequestDetails',
    component: () => import('../components/views/ict-approval/RequestDetails.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_OFFICER]
    }
  },
  {
    path: '/secretary-approval/request/:id',
    name: 'SecretaryRequestDetails',
    component: () => import('../components/views/ict-approval/RequestDetails.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.SECRETARY_ICT]
    }
  },
  // Internal Access Requests Dashboard (for approvers) - Redirect to new combined requests
  {
    path: '/hod-dashboard/request-list',
    name: 'HODDashboardRequestList',
    redirect: '/hod-dashboard/combined-requests'
  },
  // HOD Combined Access Requests List
  {
    path: '/hod-dashboard/combined-requests',
    name: 'HODCombinedRequestList',
    component: () =>
      import(/* webpackChunkName: "hod" */ '../components/views/hod/HodRequestList.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_DEPARTMENT]
    }
  },

  // HOD Divisional Recommendations
  {
    path: '/hod-dashboard/divisional-recommendations',
    name: 'HODDivisionalRecommendations',
    component: () =>
      import(/* webpackChunkName: "hod" */ '../components/views/hod/DivisionalRecommendations.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_DEPARTMENT]
    }
  },

  // HOD Request Resubmission
  {
    path: '/hod-dashboard/resubmit-request/:id',
    name: 'HODRequestResubmission',
    component: () =>
      import(/* webpackChunkName: "hod" */ '../components/views/hod/RequestResubmission.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_DEPARTMENT]
    }
  },

  // Divisional Director Combined Access Requests List
  {
    path: '/divisional-dashboard/combined-requests',
    name: 'DivisionalCombinedRequestList',
    component: () =>
      import(
        /* webpackChunkName: "divisional" */ '../components/views/divisional/DivisionalRequestList.vue'
      ),
    meta: {
      requiresAuth: true,
      roles: [ROLES.DIVISIONAL_DIRECTOR]
    }
  },
  // Divisional Director review route for combined both-service form
  {
    path: '/divisional-dashboard/both-service-form/:id',
    name: 'DivisionalBothServiceFormReview',
    component: () => import('../components/views/forms/both-service-form.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.DIVISIONAL_DIRECTOR]
    }
  },
  // Backward compatibility redirect if any code still links to /both-service-form/:id
  {
    path: '/both-service-form/:id',
    redirect: (to) => `/divisional-dashboard/both-service-form/${to.params.id}`
  },

  // Divisional Director Dict Recommendations
  {
    path: '/divisional-dashboard/dict-recommendations',
    name: 'DivisionalDictRecommendations',
    component: () =>
      import(
        /* webpackChunkName: "divisional" */ '../components/views/divisional/DictRecommendations.vue'
      ),
    meta: {
      requiresAuth: true,
      roles: [ROLES.DIVISIONAL_DIRECTOR]
    }
  },

  // ICT Director Combined Access Requests List
  {
    path: '/dict-dashboard/combined-requests',
    name: 'DictCombinedRequestList',
    component: () =>
      import(/* webpackChunkName: "dict" */ '../components/views/dict/DictRequestList.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_DIRECTOR],
      refreshOnEnter: true // Force refresh when entering this route
    }
  },
  // ICT Director review route for combined both-service form
  {
    path: '/dict-dashboard/both-service-form/:id',
    name: 'DictBothServiceFormReview',
    component: () => import('../components/views/forms/both-service-form.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ICT_DIRECTOR]
    }
  },

  // Head of IT Combined Access Requests List
  {
    path: '/head_of_it-dashboard/combined-requests',
    name: 'HeadOfItCombinedRequestList',
    component: () =>
      import(
        /* webpackChunkName: "head-of-it" */ '../components/views/head-of-it/HeadOfItRequestList.vue'
      ),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_IT],
      refreshOnEnter: true // Force refresh when entering this route
    }
  },

  // Head of IT Dict Recommendations
  {
    path: '/head_of_it-dashboard/dict-recommendations',
    name: 'HeadOfItDictRecommendations',
    component: () =>
      import(
        /* webpackChunkName: "head-of-it" */ '../components/views/head-of-it/HeadOfItDictRecommendations.vue'
      ),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_IT]
    }
  },

  // Head of IT Select ICT Officer
  {
    path: '/head_of_it-dashboard/select-ict-officer/:id',
    name: 'HeadOfItSelectIctOfficer',
    component: () =>
      import(
        /* webpackChunkName: "head-of-it" */ '../components/views/head-of-it/SelectIctOfficer.vue'
      ),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_IT]
    }
  },

  // Head of IT Review Form (redirects to both-service-form for Head of IT review)
  {
    path: '/head_of_it-dashboard/both-service-form/:id',
    name: 'HeadOfItBothServiceFormReview',
    component: () => import('../components/views/forms/both-service-form.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.HEAD_OF_IT]
    }
  },

  // Redirect old route to new route for backward compatibility
  {
    path: '/internal-access/list',
    redirect: '/hod-dashboard/request-list'
  },

  // Admin User Management routes
  {
    path: '/service-users',
    name: 'ServiceUsers',
    component: () => import('../components/admin/JeevaUsers.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ADMIN]
    }
  },
  {
    path: '/admin/onboarding-reset',
    name: 'OnboardingReset',
    component: () => import('../components/admin/OnboardingReset.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ADMIN]
    }
  },

  {
    path: '/wellsoft-users',
    name: 'WellsoftUsers',
    component: () => import('../components/admin/WellsoftUsers.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ADMIN]
    }
  },
  {
    path: '/internet-users',
    name: 'InternetUsers',
    component: () => import('../components/admin/InternetUsers.vue'),
    meta: {
      requiresAuth: true,
      roles: [ROLES.ADMIN]
    }
  },

  // Legacy admin routes (for backward compatibility)
  {
    path: '/admin/users/jeeva',
    redirect: '/service-users'
  },
  {
    path: '/jeeva-users',
    redirect: '/service-users'
  },
  {
    path: '/admin/users/wellsoft',
    redirect: '/wellsoft-users'
  },
  {
    path: '/admin/users/internet',
    redirect: '/internet-users'
  },
  {
    path: '/admin/department-hods',
    redirect: '/admin/departments'
  },
  // Admin dashboard redirect for consistency
  {
    path: '/admin',
    redirect: '/admin-dashboard'
  },

  // Catch-all route for 404 errors
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => {
      return import('../components/NotFound.vue').catch(() => {
        // Fallback if NotFound component doesn't exist - using render function instead of template
        return Promise.resolve({
          name: 'NotFoundFallback',
          render() {
            return h('div', { class: 'text-center p-8' }, [
              h('h1', { class: 'text-2xl font-bold text-red-600' }, 'Page Not Found'),
              h('p', { class: 'mt-4' }, 'The requested page could not be found.')
            ])
          }
        })
      })
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard (Pinia-only)
router.beforeEach(async (to, from, next) => {
  try {
    return enhancedNavigationGuard(to, from, next)
  } catch (error) {
    console.error('❌ Router: Navigation error:', error)
    next('/login')
  }
})

// Image preloading after navigation
router.afterEach((to) => {
  // Preload images for the target route
  preloadRouteBasedImages(to.path)
})

// After navigation guard for error handling
router.afterEach((to, from, failure) => {
  if (failure) {
    // Only log actual navigation failures, not expected ones
    if (failure.type === 8) {
      // Navigation was cancelled (type 8), this is normal behavior
      console.log('Navigation cancelled:', failure.message)
    } else if (failure.type === 16) {
      // Navigation was duplicated (type 16), user is already on target route
      console.log('Navigation duplicated - already on target route')
    } else {
      // Log actual navigation errors
      console.error('Navigation failed:', failure)
      console.error('Failed route:', to)
      console.error('Previous route:', from)

      // Provide user-friendly error message for critical failures
      if (failure.type === 2) {
        console.error('Route not found - this might indicate a missing route definition')
      }
    }
  }
})

// Global error handler for route component loading
router.onError((error) => {
  console.error('Router error:', error)

  // Handle component loading errors
  if (error.message && error.message.includes('Loading chunk')) {
    console.error('Chunk loading failed, reloading page...')
    // Don't reload if we're on the both-service-form route - just log the error
    const currentPath = router.currentRoute.value.path
    if (currentPath.includes('/both-service-form/')) {
      console.error(
        '⚠️ Chunk loading failed on both-service-form, but preventing reload to avoid disappearing page'
      )
      return
    }
    window.location.reload()
  }

  // Handle __vccOpts errors
  if (error.message && error.message.includes('__vccOpts')) {
    console.error('Component compilation error, attempting recovery...')
    const currentPath = router.currentRoute.value.path
    if (currentPath.includes('/both-service-form/')) {
      console.error(
        '⚠️ Component compilation error on both-service-form, but preventing navigation to avoid disappearing page'
      )
      return
    }
    // Try to navigate to a safe route
    router.push('/').catch(() => {
      window.location.href = '/'
    })
  }
})

// Prevent navigation to non-existent both-service-form IDs and keep showing current request
router.beforeEach(async (to, from, next) => {
  try {
    const isBothServiceForm = to.path.includes('/both-service-form/')
    if (!isBothServiceForm) return next()

    // Avoid loops
    if (to.fullPath === from.fullPath) return next()

    // Extract id from params or path
    const id = to.params?.id || to.path.match(/both-service-form\/(\d+)/)?.[1]
    if (!id) return next()

    // Validate existence with a short timeout to avoid hanging navigation
    const timeout = new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 6000))
    const res = await Promise.race([combinedAccessService.getRequestById(id), timeout])

    const ok = res && res.success && res.data
    if (ok) return next()

    // If invalid, keep showing current request (cancel navigation), or redirect to a safe page
    if (from && from.path && from.path.includes('/both-service-form/')) return next(false)
    // Redirect to a safe, generic dashboard instead of ICT Access Requests
    return next('/user-dashboard')
  } catch (e) {
    // On error, prefer to keep user on existing page
    if (from && from.path && from.path.includes('/both-service-form/')) return next(false)
    return next('/user-dashboard')
  }
})

// Strip stray cache-busting query params like ?v=... to keep URLs clean and prevent resource aborts
router.beforeEach((to, from, next) => {
  if (to.query && Object.prototype.hasOwnProperty.call(to.query, 'v')) {
    const cleanQuery = { ...to.query }
    delete cleanQuery.v
    next({ name: to.name, params: to.params, query: cleanQuery, replace: true })
  } else {
    next()
  }
})

export default router
