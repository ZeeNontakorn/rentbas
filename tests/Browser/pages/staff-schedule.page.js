import { expect } from '@playwright/test';

export class StaffSchedulePage {
    constructor(page) {
        this.page = page;
        this.calendar = page.locator('#private-schedule-calendar');
        this.staffFilter = page.locator('#staff-filter');
    }

    async login(account) {
        await this.page.goto('/login');
        await this.page.locator('input[name="email"]').fill(account.email);
        await this.page.locator('input[name="password"]').fill(account.password);
        await this.page.getByRole('button', { name: 'เข้าสู่ระบบ' }).click();
    }

    async goto() {
        await this.page.goto('/admin/private-schedule');
        await expect(this.calendar).toBeVisible();
        await this.page.waitForSelector('.fc-view-harness');
    }

    async selectStaff(value) {
        await this.staffFilter.selectOption(String(value));
    }

    viewButton(name) {
        return this.page.getByRole('button', { name, exact: true });
    }
}
