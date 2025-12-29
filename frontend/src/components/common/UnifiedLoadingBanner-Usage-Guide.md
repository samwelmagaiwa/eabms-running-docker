# UnifiedLoadingBanner Usage Guide

## Overview
The `UnifiedLoadingBanner` component provides a consistent, branded loading experience across all pages in the application. It features:
- Red (#FF0000) and Blue (#0000D1) orbiting spinner matching hospital branding
- Hospital logos and branding
- Customizable loading messages
- Glass morphism design with backdrop blur
- Accessibility support for reduced motion

## Installation

### 1. Import the Component
```javascript
import UnifiedLoadingBanner from '@/components/common/UnifiedLoadingBanner.vue'
```

### 2. Register in Components
```javascript
export default {
  components: {
    UnifiedLoadingBanner,
    // ... other components
  },
  // ...
}
```

### 3. Add to Template
```vue
<template>
  <!-- Your page content -->
  <main class="relative">
    <!-- Loading banner with absolute positioning -->
    <UnifiedLoadingBanner 
      :show="isLoading"
      loadingTitle="Loading Your Data"
      loadingSubtitle="Please wait..."
      departmentTitle="YOUR DEPARTMENT NAME"
    />
    
    <!-- Regular page content -->
    <div v-if="!isLoading">
      <!-- Your content here -->
    </div>
  </main>
</template>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `show` | Boolean | `true` | Controls visibility of the loading banner |
| `loadingTitle` | String | `"Loading Requests"` | Main loading message |
| `loadingSubtitle` | String | `"Please wait..."` | Secondary loading message |
| `departmentTitle` | String | `"SYSTEM MANAGEMENT"` | Department/section title |

## Usage Examples

### Basic Usage
```vue
<UnifiedLoadingBanner :show="isLoading" />
```

### Custom Messages for Different Pages
```vue
<!-- For ICT Approval pages -->
<UnifiedLoadingBanner 
  :show="isLoading"
  loadingTitle="Loading Device Requests"
  loadingSubtitle="Fetching approval data..."
  departmentTitle="ICT DEVICE REQUESTS MANAGEMENT"
/>

<!-- For Booking pages -->
<UnifiedLoadingBanner 
  :show="isLoading"
  loadingTitle="Checking Your Requests"
  loadingSubtitle="Verifying pending booking requests..."
  departmentTitle="ICT EQUIPMENT BOOKING SYSTEM"
/>

<!-- For User Management pages -->
<UnifiedLoadingBanner 
  :show="isLoading"
  loadingTitle="Loading User Data"
  loadingSubtitle="Fetching user information..."
  departmentTitle="USER MANAGEMENT SYSTEM"
/>

<!-- For HoD/Divisional pages -->
<UnifiedLoadingBanner 
  :show="isLoading"
  loadingTitle="Loading Recommendations"
  loadingSubtitle="Preparing divisional requests..."
  departmentTitle="DIVISIONAL RECOMMENDATIONS SYSTEM"
/>
```

## Implementation Steps for Existing Pages

### Step 1: Identify Loading States
Look for existing loading indicators in your component:
- `isLoading` variables
- `v-if="loading"` conditions
- Spinner components
- Loading text/messages

### Step 2: Replace Existing Loading UI
Before:
```vue
<!-- Old loading UI -->
<div v-if="isLoading" class="text-center p-8">
  <i class="fas fa-spinner fa-spin text-2xl"></i>
  <p>Loading...</p>
</div>
```

After:
```vue
<!-- New unified loading UI -->
<UnifiedLoadingBanner 
  :show="isLoading"
  loadingTitle="Loading Data"
  loadingSubtitle="Please wait..."
  departmentTitle="YOUR DEPARTMENT"
/>
```

### Step 3: Ensure Proper Positioning
The banner uses absolute positioning, so ensure the parent container is `relative`:

```vue
<template>
  <main class="relative">
    <!-- Loading banner will overlay the entire main area -->
    <UnifiedLoadingBanner :show="isLoading" />
    
    <!-- Regular content -->
    <div class="content">...</div>
  </main>
</template>
```

## Department Title Suggestions

Use appropriate department titles based on the page context:

- **ICT Approval**: `"ICT DEVICE REQUESTS MANAGEMENT"`
- **Booking Service**: `"ICT EQUIPMENT BOOKING SYSTEM"`
- **User Management**: `"USER MANAGEMENT SYSTEM"`
- **HoD Recommendations**: `"DIVISIONAL RECOMMENDATIONS SYSTEM"`
- **DICT Recommendations**: `"DIRECTOR ICT RECOMMENDATIONS"`
- **Access Requests**: `"ACCESS RIGHTS MANAGEMENT"`
- **Device Inventory**: `"DEVICE INVENTORY MANAGEMENT"`
- **Internal Access**: `"INTERNAL SYSTEM ACCESS"`

## CSS Considerations

The component includes:
- Fade-in animations
- Backdrop blur effects
- Responsive design
- Accessibility support for reduced motion

No additional CSS is required unless you want to customize positioning.

## Accessibility Features

- Respects `prefers-reduced-motion` setting
- Proper color contrast
- Semantic structure
- Screen reader friendly

## Performance Notes

- Component is lightweight
- Animations are hardware accelerated
- Images are optimized with proper loading
- Graceful fallbacks for missing assets

## Migration Checklist

For each page you're updating:

- [ ] Import UnifiedLoadingBanner
- [ ] Register in components
- [ ] Replace old loading UI
- [ ] Set appropriate loading messages
- [ ] Choose relevant department title
- [ ] Test loading state functionality
- [ ] Verify accessibility
- [ ] Check responsive behavior

## Common Patterns

### Pattern 1: Data Fetching
```javascript
data() {
  return {
    isLoading: false,
    data: null
  }
},
async mounted() {
  this.isLoading = true
  try {
    this.data = await this.fetchData()
  } finally {
    this.isLoading = false
  }
}
```

### Pattern 2: Form Submission
```javascript
async submitForm() {
  this.isSubmitting = true
  try {
    await this.processForm()
  } finally {
    this.isSubmitting = false
  }
}
```

### Pattern 3: Multiple Loading States
```javascript
data() {
  return {
    isLoadingUsers: false,
    isLoadingDepartments: false
  }
},
computed: {
  isLoading() {
    return this.isLoadingUsers || this.isLoadingDepartments
  }
}
```

## Support

If you encounter issues or need assistance implementing the UnifiedLoadingBanner, please refer to the existing implementation in:
- `RequestsList.vue` (original implementation)
- `BookingService.vue` (updated example)