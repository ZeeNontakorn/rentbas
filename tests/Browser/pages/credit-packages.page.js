import { expect } from '@playwright/test';

export class CreditPackagesPage {
    constructor(page) {
        this.page = page;
        this.promptpayForm = page.locator('form[action*="/admin/credit-topup-packages/promptpay"]');
        this.lineUrlForm = page.locator('form[action*="/admin/credit-topup-packages/line-url"]');
        this.addPackageForm = page.locator('form').filter({
            has: page.getByRole('button', { name: 'เพิ่ม', exact: true }),
        });
        this.saveAllButton = page.locator('#saveAllBtn');
        this.pageToast = page.locator('#pageToast');
    }

    async login(account) {
        await this.page.goto('/login');
        await this.page.locator('input[name="email"]').fill(account.email);
        await this.page.locator('input[name="password"]').fill(account.password);
        await this.page.getByRole('button', { name: 'เข้าสู่ระบบ' }).click();
    }

    async logout() {
        // waitForLoadState('load') resolves immediately if the page is already
        // idle (it does not wait for a *future* navigation), so the very next
        // goto() can still win the race and cancel the logout POST before it
        // reaches the server. Wait for the URL to actually change instead.
        const before = this.page.url();
        await Promise.all([
            this.page.waitForURL((url) => url.toString() !== before),
            this.page.locator('#logout-form').evaluate((form) => form.requestSubmit()),
        ]);
    }

    async goto() {
        await this.page.goto('/admin/credit-topup-packages');
        await expect(this.page.getByRole('heading', { name: 'แพ็กเกจเครดิต & โปรโมชั่น' })).toBeVisible();
    }

    // Some of these forms/inputs have no `required`/`min` HTML attribute, but a few
    // do (e.g. price/credit `min="1"`, the LINE url field's `type="url"`) — bypassing
    // native constraint validation lets us assert on the server's own messages.
    async bypassHtml5Validation(form) {
        await form.evaluate((element) => { element.noValidate = true; });
    }

    row(id) {
        return this.page.locator(`tr[data-id="${id}"]`);
    }

    editForm(id) {
        return this.page.locator(`#editPkg${id}`);
    }

    async deletePackage(id) {
        await this.row(id).getByRole('button', { name: 'ลบ', exact: true }).click();
        const modal = this.page.locator('.swal2-popup');
        await expect(modal).toBeVisible();
        await modal.getByRole('button', { name: 'ยืนยันการลบ', exact: true }).click();
    }

    packageCardByPrice(baht) {
        return this.page.locator('.pkg-card').filter({ has: this.page.getByText(`฿${baht}`, { exact: true }) });
    }
}
