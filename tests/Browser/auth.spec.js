import { expect, test } from '@playwright/test';
import { LoginPage } from './pages/login.page.js';
import { RegisterPage } from './pages/register.page.js';

test.describe('authentication pages', () => {
    test('AUTH-01 guest can open the login page', async ({ page }) => {
        const loginPage = new LoginPage(page);

        await loginPage.goto();

        await expect(page).toHaveTitle(/เข้าสู่ระบบ/);
        await expect(loginPage.heading).toBeVisible();
        await expect(loginPage.emailInput).toBeVisible();
        await expect(loginPage.passwordInput).toBeVisible();
        await expect(loginPage.loginButton).toBeVisible();
    });

    test('AUTH-10 guest can navigate from login to registration', async ({ page }) => {
        const loginPage = new LoginPage(page);
        const registerPage = new RegisterPage(page);

        await loginPage.goto();
        await loginPage.openRegistration();

        await expect(page).toHaveURL(/\/register$/);
        await expect(page).toHaveTitle(/สมัครสมาชิก/);
        await expect(registerPage.nameInput).toBeVisible();
        await expect(registerPage.registerButton).toBeVisible();
    });
});
