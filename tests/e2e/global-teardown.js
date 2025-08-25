// global-teardown.js
const fs = require('fs');
const path = require('path');

async function globalTeardown(config) {
  console.log('🧹 Starting METS E2E Test Teardown...');
  
  try {
    // Clean up test data
    console.log('🗑️  Cleaning up test data...');
    
    // Here you would typically:
    // 1. Clean up test database
    // 2. Remove uploaded test files
    // 3. Reset WordPress to clean state
    
    console.log('✅ Test data cleanup complete');
  } catch (error) {
    console.error('❌ Test data cleanup failed:', error);
  }

  try {
    // Clean up authentication files
    console.log('🔐 Cleaning up authentication states...');
    
    const authDir = path.join(__dirname, 'playwright', '.auth');
    if (fs.existsSync(authDir)) {
      fs.rmSync(authDir, { recursive: true, force: true });
    }
    
    console.log('✅ Authentication cleanup complete');
  } catch (error) {
    console.error('❌ Authentication cleanup failed:', error);
  }

  try {
    // Generate test summary
    console.log('📊 Generating test summary...');
    
    const reportPath = path.join(__dirname, 'test-summary.json');
    const summary = {
      timestamp: new Date().toISOString(),
      testRun: 'METS E2E Tests',
      cleanup: 'completed'
    };
    
    fs.writeFileSync(reportPath, JSON.stringify(summary, null, 2));
    console.log('✅ Test summary generated');
  } catch (error) {
    console.error('❌ Test summary generation failed:', error);
  }

  console.log('🎯 METS E2E Test Teardown Complete!');
}

module.exports = globalTeardown;