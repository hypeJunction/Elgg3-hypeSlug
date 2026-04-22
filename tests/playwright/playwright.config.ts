import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  timeout: 30000,
  use: {
    baseURL: process.env.ELGG_BASE_URL || 'http://elgg',
    ignoreHTTPSErrors: true,
  },
  // Sequential — tests share DB state
  workers: 1,
  projects: [{ name: 'chromium', use: { browserName: 'chromium' } }],
});
