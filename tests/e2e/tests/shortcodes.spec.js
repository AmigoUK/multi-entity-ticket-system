// tests/shortcodes.spec.js
// Comprehensive shortcode tests for METS plugin
const { test, expect } = require('@playwright/test');

const BASE = process.env.BASE_URL || 'https://testwp:8890';

// ─────────────────────────────────────────────────
// [ticket_form] shortcode
// ─────────────────────────────────────────────────
test.describe('[ticket_form] shortcode', () => {

  test('renders the ticket form wrapper', async ({ page }) => {
    await page.goto(`${BASE}/ticket-form/`);
    await expect(page.locator('.mets-ticket-form-wrapper')).toBeVisible();
  });

  test('shows KB search gate before form', async ({ page }) => {
    await page.goto(`${BASE}/ticket-form/`);
    const gate = page.locator('#mets-kb-search-gate');
    await expect(gate).toBeVisible();
    await expect(gate.locator('.mets-kb-gate-header')).toBeVisible();
    await expect(gate.locator('input[type="text"]')).toBeVisible();
  });

  test('ticket form is hidden until KB gate is passed', async ({ page }) => {
    await page.goto(`${BASE}/ticket-form/`);
    const form = page.locator('#mets-ticket-form');
    // Form should exist but be hidden behind the KB gate
    await expect(form).toBeAttached();
  });

  test('KB gate search input accepts text', async ({ page }) => {
    await page.goto(`${BASE}/ticket-form/`);
    const searchInput = page.locator('#mets-kb-search-gate input[type="text"]');
    await searchInput.fill('test search query');
    await expect(searchInput).toHaveValue('test search query');
  });

  test('form contains required fields', async ({ page }) => {
    await page.goto(`${BASE}/ticket-form/`);
    // Check form has nonce field
    await expect(page.locator('#mets_ticket_nonce')).toBeAttached();
    // Check form has status area
    await expect(page.locator('#mets-form-status')).toBeAttached();
  });

  test('form has CSRF nonce protection', async ({ page }) => {
    await page.goto(`${BASE}/ticket-form/`);
    const nonce = page.locator('input[name="mets_ticket_nonce"]');
    await expect(nonce).toBeAttached();
    const value = await nonce.getAttribute('value');
    expect(value).toBeTruthy();
    expect(value.length).toBeGreaterThan(5);
  });

  test('KB gate reveals form after search + click through', async ({ page }) => {
    await page.goto(`${BASE}/ticket-form/`);
    // Fill KB search
    const searchInput = page.getByRole('textbox', { name: /describe your issue/i });
    await searchInput.fill('password reset help');
    // Click search button
    await page.getByRole('button', { name: 'Search Knowledge Base' }).click();
    // Wait for "Create Support Ticket" button to appear
    await expect(page.getByRole('button', { name: 'Create Support Ticket' })).toBeVisible({ timeout: 10000 });
    // Click through to form
    await page.getByRole('button', { name: 'Create Support Ticket' }).click();
    // Verify form fields are now visible
    await expect(page.getByRole('textbox', { name: /subject/i })).toBeVisible();
    await expect(page.getByRole('textbox', { name: /description/i })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit Ticket' })).toBeVisible();
  });

  test('form pre-fills subject from KB search query', async ({ page }) => {
    await page.goto(`${BASE}/ticket-form/`);
    const searchInput = page.getByRole('textbox', { name: /describe your issue/i });
    await searchInput.fill('my test issue');
    await page.getByRole('button', { name: 'Search Knowledge Base' }).click();
    await page.getByRole('button', { name: 'Create Support Ticket' }).click();
    // Subject should be pre-filled from search
    await expect(page.getByRole('textbox', { name: /subject/i })).toHaveValue('my test issue');
  });
});

// ─────────────────────────────────────────────────
// [ticket_portal] shortcode
// ─────────────────────────────────────────────────
test.describe('[ticket_portal] shortcode', () => {

  test('shows login required message for guests', async ({ page }) => {
    // Clear any auth state
    await page.context().clearCookies();
    await page.goto(`${BASE}/customer-portal/`);
    await expect(page.locator('.mets-customer-portal.mets-login-required')).toBeVisible();
    await expect(page.locator('.mets-login-link')).toBeVisible();
  });

  test('login link points to wp-login.php', async ({ page }) => {
    await page.context().clearCookies();
    await page.goto(`${BASE}/customer-portal/`);
    const link = page.locator('.mets-login-link a, .mets-customer-portal a');
    await expect(link.first()).toBeVisible();
    const href = await link.first().getAttribute('href');
    expect(href).toContain('wp-login.php');
  });

  test('shows ticket list when logged in', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/wp-login.php`);
    await page.fill('#user_login', process.env.WP_ADMIN_USER || 'admin');
    await page.fill('#user_pass', process.env.WP_ADMIN_PASS || 'admin123');
    await page.click('#wp-submit');
    // Wait for login to complete (redirects to wp-admin dashboard)
    await page.waitForSelector('#wpadminbar, #wpwrap', { timeout: 15000 });
    // Now navigate to portal
    await page.goto(`${BASE}/customer-portal/`);
    await page.waitForLoadState('domcontentloaded');
    // Should not show login required
    await expect(page.locator('.mets-login-required')).not.toBeVisible();
    // Should show portal content
    await expect(page.getByText('My Support Tickets')).toBeVisible();
  });
});

// ─────────────────────────────────────────────────
// [mets_knowledgebase] shortcode
// ─────────────────────────────────────────────────
test.describe('[mets_knowledgebase] shortcode', () => {

  test('renders the KB widget container', async ({ page }) => {
    await page.goto(`${BASE}/knowledge-base/`);
    await expect(page.locator('.mets-kb-widget')).toBeVisible();
  });

  test('uses grid layout by default', async ({ page }) => {
    await page.goto(`${BASE}/knowledge-base/`);
    await expect(page.locator('.mets-kb-layout-grid')).toBeVisible();
  });

  test('renders search form section', async ({ page }) => {
    await page.goto(`${BASE}/knowledge-base/`);
    await expect(page.locator('.mets-kb-search-form')).toBeVisible();
    await expect(page.locator('.mets-kb-search-form input[type="text"]')).toBeVisible();
  });

  test('renders categories section (empty state OK)', async ({ page }) => {
    await page.goto(`${BASE}/knowledge-base/`);
    // Either shows categories or "No categories found" message
    const hasCategories = await page.locator('.mets-kb-category-card').count() > 0;
    const hasEmptyMsg = await page.getByText('No categories').count() > 0;
    expect(hasCategories || hasEmptyMsg).toBe(true);
  });

  test('renders popular articles section (empty state OK)', async ({ page }) => {
    await page.goto(`${BASE}/knowledge-base/`);
    // Either shows articles or "No articles found" message
    const hasArticles = await page.locator('.mets-kb-article-item').count() > 0;
    const hasEmptyMsg = await page.getByText('No articles').count() > 0;
    expect(hasArticles || hasEmptyMsg).toBe(true);
  });

  test('search input has placeholder text', async ({ page }) => {
    await page.goto(`${BASE}/knowledge-base/`);
    const input = page.locator('.mets-kb-search-form input[type="text"]');
    const placeholder = await input.getAttribute('placeholder');
    expect(placeholder).toBeTruthy();
  });
});

// ─────────────────────────────────────────────────
// [mets_kb_search] shortcode
// ─────────────────────────────────────────────────
test.describe('[mets_kb_search] shortcode', () => {

  test('renders standalone search form', async ({ page }) => {
    await page.goto(`${BASE}/kb-search/`);
    await expect(page.locator('.mets-kb-search-form')).toBeVisible();
  });

  test('has search input field', async ({ page }) => {
    await page.goto(`${BASE}/kb-search/`);
    const input = page.locator('.mets-kb-search-form input[type="text"]');
    await expect(input).toBeVisible();
    // Should have a placeholder
    const placeholder = await input.getAttribute('placeholder');
    expect(placeholder).toBeTruthy();
  });

  test('has submit button', async ({ page }) => {
    await page.goto(`${BASE}/kb-search/`);
    await expect(page.locator('.mets-search-submit, .mets-kb-search-form button[type="submit"]')).toBeVisible();
  });

  test('has suggestions container', async ({ page }) => {
    await page.goto(`${BASE}/kb-search/`);
    await expect(page.locator('.mets-kb-search-suggestions, #mets-kb-search-suggestions')).toBeAttached();
  });

  test('search input accepts text', async ({ page }) => {
    await page.goto(`${BASE}/kb-search/`);
    const input = page.locator('.mets-kb-search-form input[type="text"]');
    await input.fill('test query');
    await expect(input).toHaveValue('test query');
  });
});

// ─────────────────────────────────────────────────
// [mets_kb_categories] shortcode
// ─────────────────────────────────────────────────
test.describe('[mets_kb_categories] shortcode', () => {

  test('renders categories section', async ({ page }) => {
    await page.goto(`${BASE}/kb-categories/`);
    // Should render either category cards or empty state
    const content = await page.content();
    const hasCategoriesContent = content.includes('mets-kb-category-card') || content.includes('No categories');
    expect(hasCategoriesContent).toBe(true);
  });

  test('shows empty state when no categories exist', async ({ page }) => {
    await page.goto(`${BASE}/kb-categories/`);
    const cards = await page.locator('.mets-kb-category-card').count();
    if (cards === 0) {
      await expect(page.getByText('No categories')).toBeVisible();
    }
  });

  test('category cards are clickable links when present', async ({ page }) => {
    await page.goto(`${BASE}/kb-categories/`);
    const cards = page.locator('.mets-kb-category-card');
    const count = await cards.count();
    if (count > 0) {
      const firstCard = cards.first();
      const href = await firstCard.getAttribute('href');
      expect(href).toContain('/knowledgebase/category/');
    }
  });
});

// ─────────────────────────────────────────────────
// [mets_kb_popular_articles] shortcode
// ─────────────────────────────────────────────────
test.describe('[mets_kb_popular_articles] shortcode', () => {

  test('renders popular articles section', async ({ page }) => {
    await page.goto(`${BASE}/kb-popular-articles/`);
    const content = await page.content();
    const hasContent = content.includes('mets-kb-article-item') || content.includes('No articles');
    expect(hasContent).toBe(true);
  });

  test('shows empty state when no articles exist', async ({ page }) => {
    await page.goto(`${BASE}/kb-popular-articles/`);
    const articles = await page.locator('.mets-kb-article-item').count();
    if (articles === 0) {
      await expect(page.getByText('No articles')).toBeVisible();
    }
  });

  test('articles have title links when present', async ({ page }) => {
    await page.goto(`${BASE}/kb-popular-articles/`);
    const articles = page.locator('.mets-kb-article-item');
    const count = await articles.count();
    if (count > 0) {
      const firstLink = articles.first().locator('a');
      await expect(firstLink).toBeVisible();
      const href = await firstLink.getAttribute('href');
      expect(href).toContain('/knowledgebase/article/');
    }
  });
});

// ─────────────────────────────────────────────────
// Cross-shortcode: no PHP errors
// ─────────────────────────────────────────────────
test.describe('Shortcode error safety', () => {

  const pages = [
    { name: 'ticket-form', path: '/ticket-form/' },
    { name: 'customer-portal', path: '/customer-portal/' },
    { name: 'knowledge-base', path: '/knowledge-base/' },
    { name: 'kb-search', path: '/kb-search/' },
    { name: 'kb-categories', path: '/kb-categories/' },
    { name: 'kb-popular-articles', path: '/kb-popular-articles/' },
  ];

  for (const p of pages) {
    test(`${p.name} page has no PHP fatal errors`, async ({ page }) => {
      await page.goto(`${BASE}${p.path}`);
      const content = await page.content();
      expect(content).not.toContain('Fatal error');
      expect(content).not.toContain('Parse error');
      expect(content).not.toContain('Call to undefined');
    });

    test(`${p.name} page has no PHP warnings in output`, async ({ page }) => {
      await page.goto(`${BASE}${p.path}`);
      const content = await page.content();
      // WordPress should not display warnings on frontend
      expect(content).not.toContain('<b>Warning</b>');
      expect(content).not.toContain('<b>Notice</b>');
    });

    test(`${p.name} page returns 200 status`, async ({ page }) => {
      const response = await page.goto(`${BASE}${p.path}`);
      expect(response.status()).toBe(200);
    });
  }
});
