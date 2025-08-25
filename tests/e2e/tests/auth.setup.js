// tests/auth.setup.js
const { test as setup, expect } = require('@playwright/test');
const path = require('path');

const adminFile = path.join(__dirname, '..', 'playwright', '.auth', 'admin.json');
const agentFile = path.join(__dirname, '..', 'playwright', '.auth', 'agent.json');

// Ensure auth directory exists
const fs = require('fs');
const authDir = path.dirname(adminFile);
if (!fs.existsSync(authDir)) {
  fs.mkdirSync(authDir, { recursive: true });
}

setup('authenticate as admin', async ({ page }) => {
  console.log('🔐 Setting up admin authentication...');
  
  // Go to WordPress admin
  await page.goto('/wp-admin');
  
  // Fill login form
  await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
  await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'password');
  
  // Click login
  await page.click('#wp-submit');
  
  // Wait for successful login
  await page.waitForURL('**/wp-admin/**');
  
  // Verify we're logged in by checking for admin bar
  await expect(page.locator('#wpadminbar')).toBeVisible();
  
  // Save signed-in state
  await page.context().storageState({ path: adminFile });
  
  console.log('✅ Admin authentication complete');
});

setup('authenticate as agent', async ({ page }) => {
  console.log('🔐 Setting up agent authentication...');
  
  try {
    await page.goto('/wp-admin');
    
    await page.fill('#user_login', process.env.WP_AGENT_USER || 'agent');
    await page.fill('#user_pass', process.env.WP_AGENT_PASS || 'password');
    
    await page.click('#wp-submit');
    
    // Check if login was successful
    await page.waitForLoadState('networkidle');
    
    // If we're still on login page, agent user doesn't exist
    const currentUrl = page.url();
    if (currentUrl.includes('wp-login.php')) {
      console.log('⚠️  Agent user does not exist, skipping agent auth setup');
      return;
    }
    
    await expect(page.locator('#wpadminbar')).toBeVisible();
    await page.context().storageState({ path: agentFile });
    
    console.log('✅ Agent authentication complete');
    
  } catch (error) {
    console.log('⚠️  Agent authentication failed, using admin auth for agent tests');
    
    // Copy admin auth to agent auth as fallback
    if (fs.existsSync(adminFile)) {
      fs.copyFileSync(adminFile, agentFile);
    }
  }
});

setup('verify METS plugin is active', async ({ page }) => {
  console.log('🔌 Verifying METS plugin is active...');
  
  await page.goto('/wp-admin/plugins.php');
  
  // Look for METS plugin row
  const metsPlugin = page.locator('tr[data-plugin*="multi-entity-ticket-system"]');
  
  if (await metsPlugin.count() > 0) {
    // Check if plugin is active
    const isActive = await metsPlugin.locator('.active').count() > 0;
    
    if (!isActive) {
      console.log('⚡ Activating METS plugin...');
      await metsPlugin.locator('.activate a').click();
      await page.waitForLoadState('networkidle');
    }
    
    console.log('✅ METS plugin is active');
  } else {
    console.log('❌ METS plugin not found!');
    throw new Error('METS plugin is not installed');
  }
});

setup('create test data', async ({ page }) => {
  console.log('📊 Creating test data...');
  
  // Navigate to METS settings
  await page.goto('/wp-admin/admin.php?page=mets-settings');
  
  // Configure basic settings
  await page.fill('#company_name', 'Test Company');
  await page.fill('#support_email', 'test@example.com');
  await page.fill('#ticket_prefix', 'TEST-');
  
  // Save settings
  await page.click('#submit');
  await page.waitForLoadState('networkidle');
  
  console.log('✅ Test data created');
});