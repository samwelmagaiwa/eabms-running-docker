const fs = require('fs')
const path = require('path')

// Simple image optimization script
// For production, consider using imagemin or similar tools

const imageDir = path.join(__dirname, '../public/assets/images')

function analyzeImages() {
  console.log('🖼️  Analyzing images in:', imageDir)

  if (!fs.existsSync(imageDir)) {
    console.log('❌ Images directory not found')
    return
  }

  const files = fs.readdirSync(imageDir)
  const imageFiles = files.filter((file) =>
    /\.(jpg|jpeg|png|gif|svg|webp)$/i.test(file)
  )

  console.log(`📊 Found ${imageFiles.length} image files:`)

  let totalSize = 0
  const recommendations = []

  imageFiles.forEach((file) => {
    const filePath = path.join(imageDir, file)
    const stats = fs.statSync(filePath)
    const sizeKB = Math.round(stats.size / 1024)
    totalSize += stats.size

    console.log(`   ${file}: ${sizeKB}KB`)

    // Provide optimization recommendations
    if (sizeKB > 500) {
      recommendations.push(
        `⚠️  ${file} is large (${sizeKB}KB) - consider optimizing`
      )
    }

    if (file.endsWith('.jpg') || file.endsWith('.jpeg')) {
      recommendations.push(`💡 Consider converting ${file} to WebP format`)
    }

    if (file.endsWith('.png') && sizeKB > 100) {
      recommendations.push(
        `💡 Consider optimizing ${file} with tools like TinyPNG`
      )
    }
  })

  console.log(`\n📈 Total images size: ${Math.round(totalSize / 1024)}KB`)

  if (recommendations.length > 0) {
    console.log('\n🔧 Optimization Recommendations:')
    recommendations.forEach((rec) => console.log(`   ${rec}`))

    console.log('\n📝 Optimization Tips:')
    console.log('   • Use WebP format for better compression')
    console.log(
      '   • Optimize images with tools like TinyPNG, ImageOptim, or Squoosh'
    )
    console.log('   • Consider lazy loading for non-critical images')
    console.log(
      '   • Use appropriate image sizes for different screen densities'
    )
    console.log('   • Add loading="lazy" attribute to img tags')
  } else {
    console.log('\n✅ All images appear to be reasonably optimized!')
  }

  // Generate WebP conversion suggestions
  console.log('\n🚀 Quick optimization commands:')
  console.log('   # Install imagemin-cli globally:')
  console.log(
    '   npm install -g imagemin-cli imagemin-webp imagemin-mozjpeg imagemin-pngquant'
  )
  console.log('')
  console.log('   # Convert to WebP:')
  console.log(
    `   imagemin ${imageDir}/*.{jpg,png} --out-dir=${imageDir}/webp --plugin=webp`
  )
  console.log('')
  console.log('   # Optimize JPEGs:')
  console.log(
    `   imagemin ${imageDir}/*.jpg --out-dir=${imageDir}/optimized --plugin=mozjpeg`
  )
  console.log('')
  console.log('   # Optimize PNGs:')
  console.log(
    `   imagemin ${imageDir}/*.png --out-dir=${imageDir}/optimized --plugin=pngquant`
  )
}

function generateImageManifest() {
  console.log('\n📋 Generating image manifest...')

  const files = fs.readdirSync(imageDir)
  const imageFiles = files.filter((file) =>
    /\.(jpg|jpeg|png|gif|svg|webp)$/i.test(file)
  )

  const manifest = {
    generated: new Date().toISOString(),
    images: imageFiles.map((file) => {
      const filePath = path.join(imageDir, file)
      const stats = fs.statSync(filePath)
      return {
        name: file,
        size: stats.size,
        sizeKB: Math.round(stats.size / 1024),
        path: `/assets/images/${file}`,
        lastModified: stats.mtime.toISOString()
      }
    })
  }

  const manifestPath = path.join(
    __dirname,
    '../public/assets/image-manifest.json'
  )
  fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2))

  console.log(`✅ Image manifest generated: ${manifestPath}`)
}

// Run the analysis
console.log('🎯 Image Optimization Analysis')
console.log('================================')
analyzeImages()
generateImageManifest()

console.log('\n🎉 Analysis complete!')
console.log('\n💡 Pro tip: Run this script regularly to monitor image sizes')
console.log(
  '💡 Consider setting up automated image optimization in your CI/CD pipeline'
)
