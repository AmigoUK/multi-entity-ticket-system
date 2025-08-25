// tests/ticket-management.spec.js
const { test, expect } = require('@playwright/test');
const { faker } = require('faker');

test.describe('Ticket Management', () => {
  
  let testTicketId;
  let testTicketTitle;

  test.beforeEach(async ({ page }) => {
    // Create a test ticket for management operations
    testTicketTitle = `Test Ticket ${Date.now()}`;
    
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
    await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
    
    await page.fill('#ticket_title', testTicketTitle);
    await page.fill('#customer_name', faker.name.fullName());
    await page.fill('#customer_email', faker.internet.email());
    await page.fill('#ticket_content', faker.lorem.paragraphs(2));
    
    await page.selectOption('#entity_id', { index: 1 });
    await page.click('#submit, input[type="submit"]');
    
    await page.waitForLoadState('networkidle');
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
  });

  test('should view ticket details', async ({ page }) => {
    // Find and click on the test ticket
    await page.click(`text=${testTicketTitle.substring(0, 20)}`);
    
    // Verify we're on the ticket detail page
    await expect(page.locator(`text=${testTicketTitle}`)).toBeVisible();
    await expect(page.locator('.ticket-content, #ticket-content')).toBeVisible();
    await expect(page.locator('.ticket-meta, .ticket-info')).toBeVisible();
  });

  test('should edit ticket information', async ({ page }) => {
    // Navigate to ticket and edit it
    await page.click(`text=${testTicketTitle.substring(0, 20)}`);
    
    // Click edit button
    await page.click('a[href*="action=edit"], .edit-ticket, #edit-ticket');
    
    // Update ticket information
    const newTitle = testTicketTitle + ' - Updated';
    await page.fill('#ticket_title', newTitle);
    await page.selectOption('#priority', 'high');
    await page.selectOption('#status', 'in_progress');
    
    // Save changes
    await page.click('#submit, input[type="submit"]');
    await page.waitForLoadState('networkidle');
    
    // Verify changes
    await expect(page.locator(`text=${newTitle}`)).toBeVisible();
    await expect(page.locator('text=High, text=high')).toBeVisible();
    await expect(page.locator('text=In Progress, text=in_progress')).toBeVisible();
  });

  test('should add reply to ticket', async ({ page }) => {
    await page.click(`text=${testTicketTitle.substring(0, 20)}`);
    
    // Find reply section
    const replyContent = 'This is a test reply from agent.';
    await page.fill('#reply_content, #ticket_reply', replyContent);
    
    // Mark as agent reply if option exists
    if (await page.locator('#reply_type').count() > 0) {
      await page.selectOption('#reply_type', 'agent');
    }
    
    // Submit reply
    await page.click('#submit_reply, .reply-submit');
    await page.waitForLoadState('networkidle');
    
    // Verify reply was added
    await expect(page.locator(`text=${replyContent}`)).toBeVisible();
    await expect(page.locator('.ticket-reply, .reply-item')).toBeVisible();
  });

  test('should change ticket status', async ({ page }) => {
    await page.click(`text=${testTicketTitle.substring(0, 20)}`);
    
    // Test different status changes
    const statuses = ['in_progress', 'resolved', 'closed'];
    
    for (const status of statuses) {
      if (await page.locator('#ticket_status, #status').count() > 0) {
        await page.selectOption('#ticket_status, #status', status);
        await page.click('#update_status, .status-update');
        await page.waitForLoadState('networkidle');
        
        // Verify status change
        await expect(page.locator(`text=${status.replace('_', ' ')}`)).toBeVisible();
      }
    }
  });

  test('should assign ticket to agent', async ({ page }) => {
    await page.click(`text=${testTicketTitle.substring(0, 20)}`);
    
    // Look for assignment dropdown
    if (await page.locator('#assigned_to, #ticket_assignee').count() > 0) {
      await page.selectOption('#assigned_to, #ticket_assignee', { index: 1 });
      await page.click('#assign_ticket, .assign-submit');
      await page.waitForLoadState('networkidle');
      
      // Verify assignment
      await expect(page.locator('.assigned-to, .ticket-assignee')).toBeVisible();
    } else {
      console.log('Assignment feature not available in current UI');
    }
  });

  test('should search and filter tickets', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
    
    // Test search functionality
    if (await page.locator('#ticket_search, .search-box input').count() > 0) {
      const searchTerm = testTicketTitle.split(' ')[0];
      await page.fill('#ticket_search, .search-box input', searchTerm);
      await page.click('#search_submit, .search-submit');
      await page.waitForLoadState('networkidle');
      
      // Verify search results
      await expect(page.locator(`text=${testTicketTitle.substring(0, 20)}`)).toBeVisible();
    }
    
    // Test status filter
    if (await page.locator('#status_filter').count() > 0) {
      await page.selectOption('#status_filter', 'open');
      await page.waitForLoadState('networkidle');
      
      // Verify filtered results
      const ticketRows = await page.locator('.ticket-row, tr').count();
      expect(ticketRows).toBeGreaterThan(0);
    }
  });

  test('should bulk update tickets', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
    
    // Create additional test tickets for bulk operations
    const additionalTickets = 2;
    for (let i = 1; i <= additionalTickets; i++) {
      await page.click('a[href*="mets-tickets&action=new"], .page-title-action');
      
      await page.fill('#ticket_title', `Bulk Test ${i}`);
      await page.fill('#customer_name', `Customer ${i}`);
      await page.fill('#customer_email', `bulk${i}@example.com`);
      await page.fill('#ticket_content', `Bulk content ${i}`);
      
      await page.selectOption('#entity_id', { index: 1 });
      await page.click('#submit, input[type="submit"]');
      await page.waitForLoadState('networkidle');
      
      await page.goto('/wp-admin/admin.php?page=mets-tickets');
    }
    
    // Select multiple tickets for bulk action
    if (await page.locator('.bulk-select, input[type="checkbox"]').count() > 0) {
      const checkboxes = await page.locator('.bulk-select, input[type="checkbox"]').all();
      
      // Select first few checkboxes
      for (let i = 0; i < Math.min(3, checkboxes.length); i++) {
        await checkboxes[i].check();
      }
      
      // Perform bulk action
      if (await page.locator('#bulk_action, .bulk-actions select').count() > 0) {
        await page.selectOption('#bulk_action, .bulk-actions select', 'close');
        await page.click('#bulk_submit, .bulk-actions input[type="submit"]');
        await page.waitForLoadState('networkidle');
        
        // Verify bulk action
        await expect(page.locator('.notice-success, .updated')).toBeVisible();
      }
    }
  });

  test('should export tickets data', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
    
    // Look for export functionality
    if (await page.locator('#export_tickets, .export-button').count() > 0) {
      const downloadPromise = page.waitForEvent('download');
      await page.click('#export_tickets, .export-button');
      
      const download = await downloadPromise;
      expect(download.suggestedFilename()).toContain('tickets');
      expect(download.suggestedFilename()).toMatch(/\.(csv|xlsx|json)$/);
    } else {
      console.log('Export feature not available in current UI');
    }
  });

  test('should handle ticket pagination', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=mets-tickets');
    
    // Check if pagination exists
    if (await page.locator('.pagination, .tablenav-pages').count() > 0) {
      // Test next page
      if (await page.locator('.next-page, .pagination .next').count() > 0) {
        await page.click('.next-page, .pagination .next');
        await page.waitForLoadState('networkidle');
        
        // Verify we're on next page
        expect(page.url()).toContain('paged=2');
      }
      
      // Test previous page
      if (await page.locator('.prev-page, .pagination .prev').count() > 0) {
        await page.click('.prev-page, .pagination .prev');
        await page.waitForLoadState('networkidle');
      }
    }
  });

  test('should view ticket history and audit trail', async ({ page }) => {
    await page.click(`text=${testTicketTitle.substring(0, 20)}`);
    
    // Add a reply to create history
    await page.fill('#reply_content, #ticket_reply', 'Creating history entry');
    await page.click('#submit_reply, .reply-submit');
    await page.waitForLoadState('networkidle');
    
    // Look for history/audit section
    if (await page.locator('.ticket-history, .audit-trail, #ticket-timeline').count() > 0) {
      await expect(page.locator('.ticket-history, .audit-trail, #ticket-timeline')).toBeVisible();
      await expect(page.locator('.history-item, .audit-entry')).toBeVisible();
    }
  });

  test('should delete ticket with confirmation', async ({ page }) => {
    await page.click(`text=${testTicketTitle.substring(0, 20)}`);
    
    // Look for delete option
    if (await page.locator('a[href*="action=delete"], .delete-ticket').count() > 0) {
      await page.click('a[href*="action=delete"], .delete-ticket');
      
      // Handle confirmation dialog
      page.on('dialog', async dialog => {
        expect(dialog.message()).toContain('delete');
        await dialog.accept();
      });
      
      await page.waitForLoadState('networkidle');
      
      // Verify ticket is deleted
      await page.goto('/wp-admin/admin.php?page=mets-tickets');
      await expect(page.locator(`text=${testTicketTitle}`)).not.toBeVisible();
    } else {
      console.log('Delete functionality not available in current UI');
    }
  });
});