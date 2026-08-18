import { expect, test } from '@playwright/test';
import { LoginPage } from './pages/login.page.js';
import { RegisterPage } from './pages/register.page.js';

const accounts = [
    ['Super Admin', '66160366@go.buu.ac.th', '123456', /\/admin\/dashboard$/],
    ['Coach 1', 'snotnew1234@gmail.com', '121212', /\/$/],
    ['Coach 2', 'pachara000004@gmail.com', '123456', /\/$/],
    ['Assistant Staff', 'oamnaka03@gmail.com', '121212', /\/$/],
    ['Customer 1', 'enfroman6666@gmail.com', '123456', /\/$/],
    ['Customer 2', 'bubbleteachakaimook1234@gmail.com', '123456', /\/$/],
];

const runId = Date.now();
const newMember = {
    name: `authuser_${runId}`.slice(0, 20),
    email: `member-${runId}@auth-test.local`,
    phone: '0891111111',
    password: '123456',
};
const resetMember = {
    name: `resetuser_${runId}`.slice(0, 20),
    email: `reset-${runId}@auth-test.local`,
    phone: '0897777777',
    password: '123456',
};

async function readOtp(page) {
    const response = await page.request.get('/__e2e/otp');
    expect(response.ok()).toBeTruthy();
    return (await response.json()).otp;
}

async function enterOtp(page, otp, selector = '.otp-box') {
    const boxes = page.locator(selector);
    for (const [index, digit] of [...otp].entries()) {
        await boxes.nth(index).fill(digit);
    }
}

test.describe.serial('authentication test cases from specification', () => {
    test('AUTH-01 ทดสอบเข้าสู่หน้าจอเข้าสู่ระบบ (Login Page)', async ({ page }) => {
        await page.goto('/');
        await page.getByRole('link', { name: 'เข้าสู่ระบบ' }).filter({ visible: true }).first().click();

        const loginPage = new LoginPage(page);
        await expect(page).toHaveURL(/\/login$/);
        await expect(loginPage.heading).toBeVisible();
        await expect(loginPage.emailInput).toBeVisible();
        await expect(loginPage.passwordInput).toBeVisible();
    });

    test('AUTH-02 เข้าสู่ระบบด้วย Email และ Password ที่ถูกต้อง', async ({ browser }) => {
        for (const [label, email, password, destination] of accounts) {
            const context = await browser.newContext();
            const page = await context.newPage();
            const loginPage = new LoginPage(page);

            await loginPage.goto();
            await loginPage.login(email, password);
            await expect(page, `${label} should log in`).toHaveURL(destination);
            await context.close();
        }
    });

    test('AUTH-03 Email ถูกต้อง แต่ Password ไม่ถูกต้อง', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.login(accounts[4][1], 'wrong-password');

        await expect(page).toHaveURL(/\/login$/);
        await expect(loginPage.errorMessage).toContainText('อีเมลหรือรหัสผ่านไม่ถูกต้อง');
    });

    test('AUTH-04 Email ไม่ถูกต้อง แต่ Password ถูกต้อง', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.login('unknown@auth-test.local', accounts[4][2]);

        await expect(page).toHaveURL(/\/login$/);
        await expect(loginPage.errorMessage).toContainText('อีเมลหรือรหัสผ่านไม่ถูกต้อง');
    });

    test('AUTH-05 กรอก Email แต่ไม่กรอก Password', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.emailInput.fill(accounts[4][1]);
        await loginPage.loginButton.click();

        await expect(loginPage.passwordError).toHaveText('กรุณากรอกรหัสผ่าน');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('AUTH-06 ไม่กรอก Email แต่กรอก Password', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.passwordInput.fill(accounts[4][2]);
        await loginPage.loginButton.click();

        await expect(loginPage.emailError).toHaveText('กรุณากรอกอีเมล');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('AUTH-07 ไม่กรอก Email และ Password', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.loginButton.click();

        await expect(loginPage.emailError).toHaveText('กรุณากรอกอีเมล');
        await expect(loginPage.passwordError).toHaveText('กรุณากรอกรหัสผ่าน');
    });

    test('AUTH-08 ตรวจสอบการจดจำผู้ใช้งานหลังเปิด Browser ใหม่', async ({ browser }) => {
        const firstContext = await browser.newContext();
        const firstPage = await firstContext.newPage();
        const loginPage = new LoginPage(firstPage);
        await loginPage.goto();
        await loginPage.login(accounts[4][1], accounts[4][2], { remember: true });
        await expect(firstPage).toHaveURL(/\/$/);

        const persistentCookies = (await firstContext.cookies())
            .filter((cookie) => cookie.name.startsWith('remember_web_'));
        expect(persistentCookies).not.toHaveLength(0);
        await firstContext.close();

        const reopenedContext = await browser.newContext();
        await reopenedContext.addCookies(persistentCookies);
        const reopenedPage = await reopenedContext.newPage();
        await reopenedPage.goto('/profile');
        await expect(reopenedPage).toHaveURL(/\/profile$/);
        await reopenedContext.close();
    });

    test('AUTH-09 ตรวจสอบลืมรหัสผ่าน ยืนยัน OTP และ Login ด้วยรหัสใหม่', async ({ page }) => {
        const registerPage = new RegisterPage(page);
        await registerPage.goto();
        await registerPage.register(resetMember);
        await enterOtp(page, await readOtp(page));
        await page.getByRole('button', { name: 'ยืนยัน OTP' }).click();

        const loginPage = new LoginPage(page);
        await loginPage.logout();
        await loginPage.goto();
        await loginPage.openForgotPassword();
        await page.locator('#email').fill(resetMember.email);
        await page.locator('#submitBtn').click();
        await expect(page).toHaveURL(/\/reset-otp$/);

        await enterOtp(page, await readOtp(page), '.otp-digit');
        await expect(page).toHaveURL(/\/reset-password/);
        await page.locator('#password').fill('654321');
        await page.locator('#password_confirmation').fill('654321');
        await page.getByRole('button', { name: 'บันทึกรหัสผ่านใหม่' }).click();
        await expect(page).toHaveURL(/\/login$/);
        await loginPage.login(resetMember.email, '654321');
        await expect(page).toHaveURL(/\/$/);
    });

    test('AUTH-10 ทดสอบเข้าสู่หน้าจอสมัครสมาชิก (Register)', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.openRegistration();

        const registerPage = new RegisterPage(page);
        await expect(page).toHaveURL(/\/register$/);
        await expect(registerPage.nameInput).toBeVisible();
        await expect(registerPage.registerButton).toBeVisible();
    });

    test('AUTH-12 สมัครสมาชิกด้วยข้อมูลครบและ OTP ถูกต้อง', async ({ page }) => {
        const registerPage = new RegisterPage(page);
        await registerPage.goto();
        await registerPage.register(newMember);
        await expect(page).toHaveURL(/\/verify-otp$/);
        await enterOtp(page, await readOtp(page));
        await page.getByRole('button', { name: 'ยืนยัน OTP' }).click();
        await expect(page).toHaveURL(/\/$/);
    });

    test('AUTH-13 Login ด้วยบัญชีที่เพิ่งสมัคร', async ({ page }) => {
        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.login(newMember.email, newMember.password);
        await expect(page).toHaveURL(/\/$/);
    });

    test('AUTH-14 Validate ช่องสมัครสมาชิกทั้งหมด', async ({ page }) => {
        const registerPage = new RegisterPage(page);
        await registerPage.goto();
        await registerPage.fill({
            name: '1', email: 'invalid-email', phone: '2', password: '123', passwordConfirmation: '456',
        });
        await registerPage.submit({ acceptPolicy: false });

        await expect(registerPage.fieldErrors).toHaveCount(5);
        await expect(page).toHaveURL(/\/register$/);
    });

    test('AUTH-15 บัญชีที่ยังไม่ยืนยัน OTP ไม่สามารถ Login ได้', async ({ browser }) => {
        const data = { name: `unverified_${runId}`.slice(0, 20), email: `unverified-${runId}@auth-test.local`, phone: '0892222222', password: '123456' };
        const registerContext = await browser.newContext();
        const registerPage = new RegisterPage(await registerContext.newPage());
        await registerPage.goto();
        await registerPage.register(data);
        await expect(registerPage.page).toHaveURL(/\/verify-otp$/);
        await registerContext.close();

        const loginContext = await browser.newContext();
        const page = await loginContext.newPage();
        const loginPage = new LoginPage(page);
        await loginPage.goto();
        await loginPage.login(data.email, data.password);
        await expect(page).toHaveURL(/\/verify-otp$/);
        await expect(page.locator('body')).toContainText('กรุณายืนยันรหัส OTP');
        await loginContext.close();
    });

    test('AUTH-16 ไม่อนุญาตให้สมัครด้วย Email ซ้ำ', async ({ page }) => {
        const registerPage = new RegisterPage(page);
        await registerPage.goto();
        await registerPage.register({ name: `duplicate_${runId}`.slice(0, 20), email: accounts[4][1], phone: '0893333333', password: '123456' });

        await expect(page).toHaveURL(/\/register$/);
        await expect(registerPage.errorMessage).toContainText('อีเมลนี้ถูกใช้งานแล้ว');
    });

    test('AUTH-17 ไม่อนุญาตให้สมัครด้วย Username ซ้ำ', async ({ page }) => {
        const registerPage = new RegisterPage(page);
        await registerPage.goto();
        await registerPage.register({ name: newMember.name, email: `duplicate-name-${runId}@auth-test.local`, phone: '0894444444', password: '123456' });

        await expect(page).toHaveURL(/\/register$/);
        await expect(registerPage.errorMessage).toContainText('ชื่อผู้ใช้นี้ถูกใช้งานแล้ว');
    });

    test('AUTH-18 แจ้งเตือนเมื่อกรอก OTP ไม่ถูกต้อง', async ({ page }) => {
        const registerPage = new RegisterPage(page);
        await registerPage.goto();
        await registerPage.register({ name: `wrongotp_${runId}`.slice(0, 20), email: `wrong-otp-${runId}@auth-test.local`, phone: '0895555555', password: '123456' });
        await enterOtp(page, '000000');
        await page.getByRole('button', { name: 'ยืนยัน OTP' }).click();

        await expect(page.locator('.auth-error')).toContainText('รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว');
    });

    test('AUTH-19 ต้องยอมรับข้อกำหนดก่อนสมัครสมาชิก', async ({ page }) => {
        const registerPage = new RegisterPage(page);
        await registerPage.goto();
        await registerPage.fill({ name: `consent_${runId}`.slice(0, 20), email: `consent-${runId}@auth-test.local`, phone: '0896666666', password: '123456' });
        await registerPage.submit({ acceptPolicy: false });

        await expect(registerPage.consentModal).toBeVisible();
        await expect(registerPage.confirmConsentButton).toBeDisabled();
        await registerPage.acceptPolicyCheckbox.check();
        await expect(registerPage.confirmConsentButton).toBeEnabled();
        await registerPage.confirmConsentButton.click();
        await expect(page).toHaveURL(/\/verify-otp$/);
    });
});
