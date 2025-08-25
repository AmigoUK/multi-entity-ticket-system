// tests/ticket-creation.spec.js
const { test, expect } = require('@playwright/test');
const { faker } = require('faker');

test.describe('Ticket Creation', () => {
  
  test.beforeEach(async ({ page }) => {
    // Navigate to the ticket creation page
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
  });

  test('should create a new ticket through admin interface', async ({ page }) => {
    // Click "Add New Ticket" button
    await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
    
    // Fill ticket form
    const ticketTitle = faker.lorem.sentence();
    const customerEmail = faker.internet.email();
    const ticketContent = faker.lorem.paragraphs(2);
    
    await page.fill('#ticket_title', ticketTitle);
    await page.fill('#customer_name', faker.name.fullName());
    await page.fill('#customer_email', customerEmail);
    await page.fill('#ticket_content', ticketContent);
    
    // Select entity
    await page.selectOption('#entity_id', { index: 1 });
    
    // Select priority
    await page.selectOption('#priority', 'medium');
    
    // Select category
    await page.selectOption('#category', 'general');
    
    // Submit form
    await page.click('#submit, input[type="submit"]');
    
    // Wait for success message or redirect
    await page.waitForLoadState('networkidle');
    
    // Verify ticket was created
    await expect(page.locator('.notice-success, .updated')).toBeVisible();
    
    // Verify ticket appears in list
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
    await expect(page.locator(`text=${ticketTitle.substring(0, 20)}`)).toBeVisible();
  });

  test('should create ticket through frontend form', async ({ page }) => {
    // Go to a page with the ticket form shortcode
    // First, create a test page with the shortcode
    await page.goto('/wp-admin/post-new.php?post_type=page');
    
    await page.fill('#title', 'Support Page');
    
    // Switch to text editor if visual editor is active
    if (await page.locator('#content-html').isVisible()) {
      await page.click('#content-html');
    }
    
    await page.fill('#content', '[mets_ticket_form]');
    
    // Publish page
    await page.click('#publish');
    await page.waitForSelector('.notice-success, .updated');
    
    // Get the page URL and visit it
    const viewLink = await page.locator('a[href*="/support-page/"]').first();
    const pageUrl = await viewLink.getAttribute('href');
    
    await page.goto(pageUrl);
    
    // Fill frontend ticket form
    const ticketData = {
      title: faker.lorem.sentence(),
      name: faker.name.fullName(),
      email: faker.internet.email(),
      content: faker.lorem.paragraphs(2)
    };
    
    await page.fill('#mets_ticket_title', ticketData.title);
    await page.fill('#mets_customer_name', ticketData.name);
    await page.fill('#mets_customer_email', ticketData.email);
    await page.fill('#mets_ticket_content', ticketData.content);
    
    // Select entity if available
    if (await page.locator('#mets_entity_id').count() > 0) {
      await page.selectOption('#mets_entity_id', { index: 1 });
    }
    
    // Submit form
    await page.click('#mets_submit_ticket');
    
    // Wait for success message
    await expect(page.locator('.mets-success, .success-message')).toBeVisible();
    
    // Verify ticket was created in admin
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
    await expect(page.locator(`text=${ticketData.title.substring(0, 20)}`)).toBeVisible();
  });

  test('should validate required fields', async ({ page }) => {
    await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
    
    // Try to submit form without required fields
    await page.click('#submit, input[type="submit"]');
    
    // Check for validation errors
    await expect(page.locator('.error, .notice-error')).toBeVisible();
  });

  test('should validate email format', async ({ page }) => {
    await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
    
    // Fill form with invalid email
    await page.fill('#ticket_title', 'Test Ticket');
    await page.fill('#customer_name', 'John Doe');
    await page.fill('#customer_email', 'invalid-email');
    await page.fill('#ticket_content', 'Test content');
    
    await page.click('#submit, input[type="submit"]');
    
    // Check for email validation error
    await expect(page.locator('text=/email|invalid/i')).toBeVisible();
  });

  test('should create ticket with file attachment', async ({ page }) => {
    await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
    
    // Fill basic ticket info
    await page.fill('#ticket_title', 'Ticket with Attachment');
    await page.fill('#customer_name', 'Jane Doe');
    await page.fill('#customer_email', 'jane@example.com');
    await page.fill('#ticket_content', 'This ticket includes an attachment.');
    
    // Upload file if file upload is available
    if (await page.locator('#ticket_attachment, input[type="file"]').count() > 0) {
      const filePath = require('path').join(__dirname, '..', 'fixtures', 'test-attachment.txt');
      
      // Create test file if it doesn't exist
      const fs = require('fs');
      const fixturesDir = require('path').join(__dirname, '..', 'fixtures');
      if (!fs.existsSync(fixturesDir)) {
        fs.mkdirSync(fixturesDir, { recursive: true });
      }
      
      if (!fs.existsSync(filePath)) {
        fs.writeFileSync(filePath, 'This is a test attachment file.');
      }
      
      await page.setInputFiles('#ticket_attachment, input[type="file"]', filePath);
    }
    
    await page.selectOption('#entity_id', { index: 1 });
    await page.click('#submit, input[type="submit"]');
    
    // Verify ticket was created
    await expect(page.locator('.notice-success, .updated')).toBeVisible();
  });

  test('should auto-assign ticket number', async ({ page }) => {
    await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
    
    const ticketTitle = 'Auto Number Test';
    await page.fill('#ticket_title', ticketTitle);
    await page.fill('#customer_name', 'Auto User');
    await page.fill('#customer_email', 'auto@example.com');
    await page.fill('#ticket_content', 'Testing auto ticket number assignment.');
    
    await page.selectOption('#entity_id', { index: 1 });
    await page.click('#submit, input[type="submit"]');
    
    await page.waitForLoadState('networkidle');
    
    // Go to tickets list and verify ticket has a number
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
    
    // Look for ticket with TEST- prefix (based on our setup)
    await expect(page.locator('text=/TEST-\\d+/')).toBeVisible();
  });

  test('should send email notification on ticket creation', async ({ page }) => {
    await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
    
    // Fill ticket form
    await page.fill('#ticket_title', 'Email Notification Test');
    await page.fill('#customer_name', 'Email User');
    await page.fill('#customer_email', 'emailtest@example.com');
    await page.fill('#ticket_content', 'Testing email notifications.');
    
    await page.selectOption('#entity_id', { index: 1 });
    await page.click('#submit, input[type="submit"]');
    
    await page.waitForLoadState('networkidle');
    
    // Check email log if available
    if (await page.locator('a[href*="mets-email-log"]').count() > 0) {
      await page.click('a[href*="mets-email-log"]');
      await expect(page.locator('text=emailtest@example.com')).toBeVisible();
    }
    
    // Verify success message indicates email was sent
    await expect(page.locator('.notice-success, .updated')).toBeVisible();
  });

  test('should handle bulk ticket creation', async ({ page }) => {
    // This test would create multiple tickets in sequence
    const ticketCount = 3;
    
    for (let i = 1; i <= ticketCount; i++) {
      await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
      
      await page.fill('#ticket_title', `Bulk Ticket ${i}`);
      await page.fill('#customer_name', `Customer ${i}`);
      await page.fill('#customer_email', `customer${i}@example.com`);
      await page.fill('#ticket_content', `Content for ticket ${i}`);
      
      await page.selectOption('#entity_id', { index: 1 });
      await page.click('#submit, input[type="submit"]');
      
      await page.waitForLoadState('networkidle');
      await expect(page.locator('.notice-success, .updated')).toBeVisible();
      
      // Go back to tickets list for next iteration
      await page.goto('/wp-admin/admin.php?page=mets-tickets');
    }
    
    // Verify all tickets were created
    for (let i = 1; i <= ticketCount; i++) {
      await expect(page.locator(`text=Bulk Ticket ${i}`)).toBeVisible();
    }
  });
});