// Minimal config for shortcode tests only
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  testMatch: 'shortcodes.spec.js',
  fullyParallel: true,
  retries: 0,
  workers: 1,
  reporter: [['line']],
  use: {
    baseURL: process.env.BASE_URL || 'https://testwp:8890',
    ignoreHTTPSErrors: true,
    actionTimeout: 10000,
    navigationTimeout: 30000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  timeout: 30 * 1000,
  expect: { timeout: 10 * 1000 },
  outputDir: 'test-results/',
});
