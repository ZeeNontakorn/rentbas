import { defineConfig, devices } from '@playwright/test';
import { existsSync } from 'node:fs';

if (existsSync('.env')) {
    process.loadEnvFile('.env');
}

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    // Google Sheets/Drive updates insert images and should be serialized.
    workers: 1,
    reporter: [
        ['list'],
        ['html', { open: 'never' }],
        ['./tests/Browser/reporters/google-sheet-reporter.js'],
    ],
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8011',
        screenshot: 'on',
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: process.env.PLAYWRIGHT_BASE_URL
        ? undefined
        : {
              command: 'APP_ENV=local E2E_TESTING=true APP_URL=http://127.0.0.1:8011 MAIL_MAILER=array php artisan serve --host=127.0.0.1 --port=8011',
              url: 'http://127.0.0.1:8011/login',
              reuseExistingServer: false,
              timeout: 120_000,
          },
});
