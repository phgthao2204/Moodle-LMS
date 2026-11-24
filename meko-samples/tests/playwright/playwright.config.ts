import { defineConfig } from '@playwright/test';
import dotenv from 'dotenv';
import path from 'path';

// Load environment variables from .env file.
dotenv.config({ path: path.resolve(__dirname, '.env') });

const baseURL = (process.env.LMS_BASE_URL || 'http://meko.localhost/lms').replace(/\/+$/, '');
const ignoreHTTPSErrors = process.env.IGNORE_HTTPS_ERRORS !== 'false';

export default defineConfig({
  testDir: './tests',
  fullyParallel: false, // Run API tests predictably sequentially
  workers: 1,
  timeout: 60_000,
  expect: {
    timeout: 10_000,
  },
  retries: 0,
  reporter: [
    ['html', { open: 'never', outputFolder: 'playwright-report' }],
    ['list'],
  ],
  use: {
    baseURL,
    ignoreHTTPSErrors,
    extraHTTPHeaders: {
      'Accept': 'application/json',
      'X-ACCEES-API-Version': process.env.ACCEES_API_VERSION || '1.0',
    },
    trace: 'retain-on-failure',
  },
});

