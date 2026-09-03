import { expect } from '@playwright/test';

export class CreditPurchasePage {
    constructor(page) {
        this.page = page;
        this.customAmountInput = page.locator('#customAmount');
        this.nextButton = page.getByRole('button', { name: /ถัดไป/ });
        this.slipInput = page.locator('#slipInput');
        this.submitButton = page.locator('#submitBtn');
        this.qrCanvas = page.locator('#qrCanvas');
        this.navCreditButton = page.locator('nav button:visible', { hasText: '฿' });
    }

    async login(account) {
        await this.page.goto('/login');
        await this.page.locator('input[name="email"]').fill(account.email);
        await this.page.locator('input[name="password"]').fill(account.password);
        await this.page.getByRole('button', { name: 'เข้าสู่ระบบ' }).click();
    }

    async logout() {
        const before = this.page.url();
        await Promise.all([
            this.page.waitForURL((url) => url.toString() !== before),
            this.page.locator('#logout-form').evaluate((form) => form.requestSubmit()),
        ]);
    }

    async goto() {
        await this.page.goto('/credits/topup');
        await expect(this.page.getByRole('heading', { name: 'เติมเครดิต', exact: true })).toBeVisible();
    }

    // Other suites' (and possibly real) packages can share the same price
    // point as ours, so price text alone isn't a reliable selector — match
    // the fixture's own package by its radio input's value (the package id).
    packageCard(id) {
        return this.page.locator('.pkg-card').filter({ has: this.page.locator(`input[type="radio"][value="${id}"]`) });
    }

    async selectPackage(id) {
        await this.packageCard(id).locator('input[type="radio"]').check();
    }

    async fillCustomAmount(value) {
        await this.customAmountInput.fill(String(value));
    }

    // The navbar renders both a desktop and a mobile credit-balance button —
    // only one is actually visible at a time depending on viewport, so
    // `.first()` alone can resolve to the hidden one. Scope to :visible.
    creditBalanceLocator(amount) {
        return this.page.locator('nav button:visible', { hasText: baht(amount) });
    }
}

export function baht(amount) {
    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
