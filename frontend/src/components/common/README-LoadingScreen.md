# LoadingScreen Component

A modern, animated loading screen component for Muhimbili National Hospital applications featuring the hospital logo, orbiting colored dots, and a percentage progress counter.

## Features

✨ **Modern Design**: Clean, professional appearance matching Muhimbili theme colors (blue, gold, white)  
🎭 **Smooth Animations**: CSS keyframe animations with orbiting dots and fade transitions  
📱 **Fully Responsive**: Optimized for desktop, tablet, and mobile devices  
🎯 **Progress Tracking**: Real-time percentage counter with custom loading phases  
🎨 **Customizable**: Configurable duration, messages, and auto-start behavior  
♿ **Accessible**: Supports reduced motion preferences and high contrast mode  
🔧 **Reusable**: Drop-in component that can be used anywhere in the project  

## Installation

The component is already created in `/src/components/common/LoadingScreen.vue`. No additional dependencies required.

## Basic Usage

### 1. Simple Implementation
```vue
<template>
  <div>
    <LoadingScreen 
      :show="isLoading"
      @loading-complete="onLoadingComplete"
    />
    
    <div v-if="!isLoading">
      <!-- Your main content here -->
    </div>
  </div>
</template>

<script>
import LoadingScreen from '@/components/common/LoadingScreen.vue'

export default {
  components: { LoadingScreen },
  
  data() {
    return {
      isLoading: true
    }
  },
  
  methods: {
    onLoadingComplete() {
      console.log('Loading finished!')
      // Navigate or perform other actions
    }
  }
}
</script>
```

### 2. Custom Configuration
```vue
<LoadingScreen
  :show="showLoading"
  :duration="5000"
  :message="'Setting up your workspace...'"
  :auto-start="true"
  @loading-complete="handleComplete"
  @loading-progress="handleProgress"
  ref="loadingRef"
/>
```

### 3. Manual Progress Control
```vue
<script>
export default {
  methods: {
    async loadData() {
      // Manual progress control
      this.$refs.loadingRef.setProgress(25)
      await this.fetchUserData()
      
      this.$refs.loadingRef.setProgress(75)
      await this.fetchAppData()
      
      this.$refs.loadingRef.setProgress(100)
    }
  }
}
</script>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `show` | Boolean | `true` | Controls component visibility |
| `duration` | Number | `3000` | Loading duration in milliseconds |
| `message` | String | `'Loading your dashboard...'` | Initial loading message |
| `autoStart` | Boolean | `true` | Auto-start loading on mount |

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `loading-complete` | `none` | Fired when loading reaches 100% |
| `loading-progress` | `{ percentage: Number, isComplete: Boolean }` | Progress updates |

## Methods

### Public Methods (via `ref`)

| Method | Parameters | Description |
|--------|------------|-------------|
| `startLoading()` | `none` | Manually start loading animation |
| `stopLoading()` | `none` | Stop loading and hide component |
| `setProgress(percentage)` | `percentage: Number (0-100)` | Set progress manually |
| `reset()` | `none` | Reset to initial state |

## Styling & Theming

### Color Scheme
The component uses Muhimbili National Hospital brand colors:

- **Primary Blue**: `#1e40af` - Background gradient and text
- **Gold/Amber**: `#f59e0b` - Progress bar and percentage
- **White**: `#ffffff` - Logo background and text
- **Red**: `#ef4444` - First orbiting dot
- **Blue**: `#3b82f6` - Second orbiting dot  
- **Yellow**: `#f59e0b` - Third orbiting dot

### Responsive Breakpoints
- **Desktop**: `> 768px` - Full size logo and animations
- **Tablet**: `≤ 768px` - Medium size with adjusted spacing
- **Mobile**: `≤ 480px` - Compact layout with smaller elements

## Implementation Examples

### 1. Onboarding Integration
```vue
<template>
  <div>
    <LoadingScreen
      v-if="isLoadingOnboarding"
      :show="isLoadingOnboarding"
      :duration="0" 
      :auto-start="false"
      :message="loadingMessage"
      @loading-complete="onOnboardingLoaded"
      ref="onboardingLoader"
    />
    
    <OnboardingDashboard v-else />
  </div>
</template>

<script>
export default {
  data() {
    return {
      isLoadingOnboarding: true,
      loadingMessage: 'Loading your profile...'
    }
  },
  
  async mounted() {
    await this.loadOnboardingData()
  },
  
  methods: {
    async loadOnboardingData() {
      try {
        // Phase 1: User Profile
        this.loadingMessage = 'Loading your profile...'
        this.$refs.onboardingLoader.setProgress(25)
        await this.fetchUserProfile()
        
        // Phase 2: Declaration Status
        this.loadingMessage = 'Checking declaration status...'
        this.$refs.onboardingLoader.setProgress(50)
        await this.fetchDeclarationStatus()
        
        // Phase 3: Phone Verification
        this.loadingMessage = 'Verifying phone number...'
        this.$refs.onboardingLoader.setProgress(75)
        await this.verifyPhone()
        
        // Complete
        this.loadingMessage = 'Welcome!'
        this.$refs.onboardingLoader.setProgress(100)
        
      } catch (error) {
        this.handleLoadingError(error)
      }
    },
    
    onOnboardingLoaded() {
      this.isLoadingOnboarding = false
    }
  }
}
</script>
```

### 2. Declaration Form Integration
```vue
<template>
  <div>
    <LoadingScreen
      :show="isLoadingDeclaration"
      :duration="2500"
      message="Preparing declaration form..."
      @loading-complete="showDeclarationForm"
    />
    
    <DeclarationForm v-if="!isLoadingDeclaration" />
  </div>
</template>

<script>
export default {
  data() {
    return {
      isLoadingDeclaration: true
    }
  },
  
  methods: {
    showDeclarationForm() {
      this.isLoadingDeclaration = false
    }
  }
}
</script>
```

### 3. Data-Driven Loading
```vue
<script>
export default {
  data() {
    return {
      isLoading: true,
      loadingPhases: [
        { at: 20, message: 'Connecting to server...' },
        { at: 40, message: 'Authenticating user...' },
        { at: 60, message: 'Loading dashboard...' },
        { at: 80, message: 'Finalizing setup...' }
      ]
    }
  },
  
  methods: {
    async performDataLoad() {
      for (const phase of this.loadingPhases) {
        this.$refs.loader.setProgress(phase.at)
        this.updateLoadingMessage(phase.message)
        await this.delay(500) // Simulate work
      }
      
      this.$refs.loader.setProgress(100)
    }
  }
}
</script>
```

## Animation Details

### Orbiting Dots
- **3 colored dots** (red, blue, yellow) rotate around the logo
- **80px orbit radius** on desktop, smaller on mobile
- **Staggered animation** with 0.5s delays between dots
- **Pulsing effect** with scale transforms (1.0 → 1.3 → 1.0)

### Loading Phases
The component includes predefined loading phases with contextual messages:

1. **0%**: "Initializing..."
2. **20%**: "Loading your profile..."  
3. **40%**: "Preparing dashboard..."
4. **60%**: "Setting up interface..."
5. **80%**: "Almost ready..."
6. **95%**: "Finalizing..."

### Progress Bar
- **Smooth transitions** with 0.3s easing
- **Gold gradient** with shimmer effect
- **Automatic width animation** based on percentage

## Browser Support

- ✅ **Modern Browsers**: Chrome 70+, Firefox 70+, Safari 12+, Edge 79+
- ✅ **Mobile**: iOS Safari 12+, Chrome Mobile 70+
- ✅ **Accessibility**: Screen reader compatible, keyboard navigation
- ✅ **Performance**: Optimized animations using CSS transforms

## Best Practices

### 1. **Progress Accuracy**
```javascript
// Match loading phases to actual data fetching
async loadUserData() {
  this.setProgress(0)
  
  const profile = await fetchProfile() // 25%
  this.setProgress(25)
  
  const settings = await fetchSettings() // 50%
  this.setProgress(50)
  
  const permissions = await fetchPermissions() // 100%
  this.setProgress(100)
}
```

### 2. **Error Handling**
```javascript
async loadWithErrorHandling() {
  try {
    await this.performLoad()
  } catch (error) {
    // Show error state or retry
    this.loadingMessage = 'Error loading. Retrying...'
    setTimeout(() => this.retry(), 2000)
  }
}
```

### 3. **User Feedback**
```javascript
// Provide meaningful loading messages
const phases = [
  { at: 25, message: 'Fetching your profile data...' },
  { at: 50, message: 'Loading medical records...' },
  { at: 75, message: 'Preparing dashboard...' },
  { at: 100, message: 'Ready!' }
]
```

## Troubleshooting

### Common Issues

1. **Logo not displaying**
   - Check that `/assets/images/logo2.png` exists
   - Verify image path is correct
   - Check console for 404 errors

2. **Animations not smooth**
   - Ensure hardware acceleration is enabled
   - Check for CSS conflicts
   - Verify browser supports CSS transforms

3. **Progress not updating**
   - Use `setProgress()` method correctly
   - Check that component ref is available
   - Verify progress values are 0-100

### Performance Tips

- Use `v-if` instead of `v-show` to completely remove from DOM
- Avoid running heavy computations during animations  
- Test on slower devices to ensure smooth performance

## File Structure

```
src/components/
├── common/
│   ├── LoadingScreen.vue           # Main component
│   └── README-LoadingScreen.md     # This documentation
├── examples/
│   └── LoadingScreenExample.vue    # Usage examples
└── onboarding/
    └── OnboardingWithLoading.vue   # Practical integration
```

This LoadingScreen component provides a polished, professional loading experience that enhances the user interface while data is being fetched in the background.
