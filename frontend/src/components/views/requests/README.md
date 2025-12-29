# Requests Dashboard System

## Overview

A comprehensive requests management system for ICT Officers, HODs, Divisional Directors, DICT, and Head of IT to review and approve access requests with role-based filtering and approval workflows.

## Components

### 1. InternalAccessList.vue

**Purpose**: Main dashboard showing paginated, searchable, sortable data table of requests

**Features**:

- **Role-based filtering**: Each role sees only relevant requests
- **Search functionality**: Search by PF Number, Staff Name, Department
- **Sorting**: Sortable by Request ID, Type, Submission Date
- **Pagination**: Configurable items per page
- **Statistics cards**: Pending, Approved, Rejected, Total counts
- **Quick actions**: View and Approve buttons

**Table Headers**:

- Request ID
- Request Type (jeeva_access,wellsoft,internet_access_request)
- Personal Information (PF Number, Staff Name, Department, Digital Signature)
- Module Requested for (use/revoke)
- Module Request (select service as per jeeva_access,wellsoft,internet_access_request) from the Request Type if multiple select both
- Access Rights (Permanent/Temporary)
- Approval (ROLE BASED WHERE HOD,Divisional Director,DICT,HOD_IT,ICT Officer )can only be filled and signed by the user with the matching role. Other sections should be visible but disabled (readonly/muted). Ensure backend validation enforces these role-based restrictions
  -COMMENTS be required to HOD
  -For Implementation (apply logic as of approval means role based Head of IT,ICT Officer granting access) some part be muted as per role
- Submission Date
- Current Status
- Actions (View/Approve/reject)

**Role-based Request Filtering**:

- **HOD**: `hod_approval_status = "pending"`
- **Divisional Director**: `hod_approval_status = "approved" AND divisional_status = "pending"`
- **DICT**: `divisional_status = "approved" AND dict_status = "pending"`
- **HOD_IT**: `dict_status = "approved" AND head_of_it_status = "pending"`
- **ICT Officer**: `head_of_it_status = "approved" AND ict_status = "pending"`

### 2. InternalAccessDetails.vue

**Purpose**: Detailed view for reviewing and approving individual requests

**Features**:

- **Request details**: Complete staff information and request specifics
- **Approval trail**: Visual workflow showing all approval stages
- **Role-based actions**: Approve/Reject buttons based on user role
- **Comments system**: Add approval/rejection comments
- **Status tracking**: Real-time status updates

**Approval Trail Stages**:

1. Head of Department
2. Divisional Director
3. DICT (ICT Director)
4. Head of IT
5. ICT Officer (Final approval)

## Navigation & Routing

### Routes Added:

```javascript
// Internal Access Requests Dashboard
{
  path: '/internal-access/list',
  name: 'InternalAccessList',
  component: () => import('../components/views/requests/InternalAccessList.vue'),
  meta: {
    requiresAuth: true,
    roles: [ROLES.DIVISIONAL_DIRECTOR, ROLES.HEAD_OF_DEPARTMENT, ROLES.HOD_IT, ROLES.ICT_DIRECTOR, ROLES.ICT_OFFICER]
  }
},
{
  path: '/internal-access/details',
  name: 'InternalAccessDetails',
  component: () => import('../components/views/requests/InternalAccessDetails.vue'),
  meta: {
    requiresAuth: true,
    roles: [ROLES.DIVISIONAL_DIRECTOR, ROLES.HEAD_OF_DEPARTMENT, ROLES.HOD_IT, ROLES.ICT_DIRECTOR, ROLES.ICT_OFFICER]
  }
}
```

### Sidebar Navigation:

- Added "Requests Management" section in DynamicSidebar
- Accessible to all approver roles
- Shows active state when on requests pages
- Tree-style navigation with expandable sections

## Permissions & Access Control

### Updated Role Permissions:

All approver roles now have access to:

- `/internal-access/list`
- `/internal-access/details`

### Role-specific Dashboard Access:

- **HEAD_OF_DEPARTMENT**: `/hod-dashboard` + requests management
- **HOD_IT**: `/hod-it-dashboard` + requests management
- **DIVISIONAL_DIRECTOR**: `/divisional-dashboard` + requests management
- **ICT_DIRECTOR**: `/dict-dashboard` + requests management
- **ICT_OFFICER**: `/ict-dashboard` + requests management

## Request Flow Navigation

### From List to Details:

When clicking on a request row or "View" button:

```javascript
// Navigate to appropriate form based on request type
const routes = {
  combined: '/internal-access/details'
}

router.push({
  path: route,
  query: {
    id: request.id,
    type: request.type
  }
})
```

### Form Pre-filling:

The details page loads the appropriate form template based on request type:

- **Jeeva Access**: Shows Jeeva-specific modules and fields
- **Wellsoft Access**: Shows Wellsoft modules and configuration
- **Internet Access**: Shows internet access parameters
- **Combined Services**: Shows all applicable modules

## Data Structure

### Mock Request Object:

```javascript
{
  id: 'REQ-001',
  type: 'jeeva', // jeeva, wellsoft, internet, combined
  staffName: 'Dr. John Doe',
  pfNumber: 'PF001234',
  department: 'Cardiology',
  digitalSignature: true,
  moduleRequestedFor: 'Use', // Use, Revoke
  selectedModules: ['DOCTOR CONSULTATION', 'MEDICAL RECORDS'],
  accessType: 'Permanent (until retirement)', // or 'Temporary Until'
  temporaryUntil: null, // Date if temporary
  submissionDate: '2024-01-15',
  currentStatus: 'pending', // pending, approved, rejected

  // Approval statuses for each stage
  hodApprovalStatus: 'pending',
  divisionalStatus: 'pending',
  dictStatus: 'pending',
  headOfItStatus: 'pending',
  ictStatus: 'pending',

  // Approval dates
  hodApprovalDate: null,
  divisionalApprovalDate: null,
  dictApprovalDate: null,
  headOfItApprovalDate: null,
  ictApprovalDate: null,

  comments: 'Need access for patient consultation...'
}
```

## Styling & Design

### Color Scheme:

- **Primary**: Blue gradient theme matching other dashboards
- **Requests Management**: Orange/Amber accent colors
- **Status indicators**:
  - Pending: Yellow
  - Approved: Green
  - Rejected: Red

### Glass Morphism Effects:

- Consistent with existing dashboard design
- Backdrop blur and transparency
- Gradient borders and shadows
- Smooth animations and transitions

## API Integration Points

### Required API Endpoints:

```javascript
// Get requests filtered by user role
GET /api/requests?role={userRole}&status={status}&type={type}

// Get specific request details
GET /api/requests/{requestId}

// Approve request
POST /api/requests/{requestId}/approve
{
  approverRole: 'head_of_department',
  comments: 'Approved for medical consultation access'
}

// Reject request
POST /api/requests/{requestId}/reject
{
  approverRole: 'head_of_department',
  comments: 'Insufficient justification provided'
}
```

## Usage Instructions

### For Approvers:

1. **Access Dashboard**: Navigate to "Requests Management" → "Access Requests"
2. **Filter Requests**: Use search, status, and type filters
3. **Review Request**: Click "View" to see full details
4. **Take Action**: Use "Approve" or "Reject" buttons with optional comments
5. **Track Progress**: Monitor approval trail for request status

### For System Integration:

1. **Replace Mock Data**: Connect to actual API endpoints
2. **Update Permissions**: Ensure role-based access is properly configured
3. **Customize Fields**: Modify request fields based on actual requirements
4. **Add Notifications**: Implement real-time notifications for status changes

## Security Considerations

- **Role-based access control**: Users only see requests at their approval stage
- **Route protection**: All routes require authentication and proper role
- **Data validation**: Input validation on all form fields
- **Audit trail**: Complete approval history with timestamps and comments

## Future Enhancements

- **Real-time updates**: WebSocket integration for live status updates
- **Email notifications**: Automated notifications for approval requests
- **Bulk actions**: Approve/reject multiple requests at once
- **Advanced filtering**: Date ranges, department-specific filters
- **Export functionality**: Export requests to PDF/Excel
- **Dashboard analytics**: Charts and graphs for request trends
