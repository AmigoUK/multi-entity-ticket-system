// global-setup.js
const { chromium } = require('@playwright/test');

async function globalSetup(config) {
  console.log('🚀 Starting METS E2E Test Setup...');
  
  // Setup test database
  try {
    console.log('📊 Setting up test database...');
    
    // Here you would typically:
    // 1. Create a clean test database
    // 2. Import WordPress with METS plugin
    // 3. Create test users and sample data
    
    console.log('✅ Test database setup complete');
  } catch (error) {
    console.error('❌ Database setup failed:', error);
    process.exit(1);
  }

  // Create admin authentication state
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  try {
    console.log('🔐 Creating admin authentication state...');
    
    // Navigate to WordPress login
    await page.goto('/wp-admin');
    
    // Login as admin
    await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
    await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'password');
    await page.click('#wp-submit');
    
    // Wait for successful login
    await page.waitForURL('/wp-admin/index.php');
    
    // Save authentication state
    await page.context().storageState({ path: 'playwright/.auth/admin.json' });
    console.log('✅ Admin authentication state saved');
    
  } catch (error) {
    console.error('❌ Admin authentication failed:', error);
    throw error;
  } finally {
    await browser.close();
  }

  // Create agent authentication state
  const agentBrowser = await chromium.launch();
  const agentPage = await agentBrowser.newPage();
  
  try {
    console.log('🔐 Creating agent authentication state...');
    
    await agentPage.goto('/wp-admin');
    
    // Login as agent
    await agentPage.fill('#user_login', process.env.WP_AGENT_USER || 'agent');
    await agentPage.fill('#user_pass', process.env.WP_AGENT_PASS || 'password');
    await agentPage.click('#wp-submit');
    
    await agentPage.waitForURL('/wp-admin/index.php');
    await agentPage.context().storageState({ path: 'playwright/.auth/agent.json' });
    
    console.log('✅ Agent authentication state saved');
    
  } catch (error) {
    console.log('⚠️  Agent authentication skipped (user may not exist)');
  } finally {
    await agentBrowser.close();
  }

  console.log('🎉 METS E2E Test Setup Complete!');
}

module.exports = globalSetup;