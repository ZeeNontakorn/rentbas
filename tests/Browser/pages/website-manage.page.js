import { expect } from '@playwright/test';

export class WebsiteManagePage {
    constructor(page) {
        this.page = page;
        this.createForm = page.locator('form[action*="/admin/website/facilities"]').filter({
            has: page.getByRole('button', { name: 'เพิ่มการ์ด', exact: true }),
        });
    }

    async login(account) {
        await this.page.goto('/login');
        await this.page.locator('input[name="email"]').fill(account.email);
        await this.page.locator('input[name="password"]').fill(account.password);
        await this.page.getByRole('button', { name: 'เข้าสู่ระบบ' }).click();
    }

    async goto() {
        await this.page.goto('/admin/edit-text#facility-management');
        await expect(this.page.getByRole('heading', { name: '5. สิ่งอำนวยความสะดวก' })).toBeVisible();
    }

    // Setting `noValidate` bypasses the browser's own HTML5 constraint
    // validation (required/min/max) so the request actually reaches the
    // server, letting us assert on the server-side validation messages.
    async bypassHtml5Validation(form) {
        await form.evaluate((element) => { element.noValidate = true; });
    }

    async fillCreateForm({ name, description, sortOrder, image } = {}) {
        if (name !== undefined) await this.createForm.locator('input[name="name"]').fill(name);
        if (description !== undefined) await this.createForm.locator('textarea[name="description"]').fill(description);
        if (sortOrder !== undefined) await this.createForm.locator('input[name="sort_order"]').fill(String(sortOrder));
        if (image !== undefined) await this.createForm.locator('input[name="image"]').setInputFiles(image);
    }

    async submitCreateForm() {
        await this.createForm.getByRole('button', { name: 'เพิ่มการ์ด', exact: true }).click();
    }

    facilityCard(id) {
        return this.page.locator(`#facility-${id}`);
    }

    // The facility name lives in an <input value="..."> rather than as
    // visible text, so match on the value attribute via a CSS attribute
    // selector instead of a text-based locator.
    facilityNameInput(name) {
        return this.page.locator(`input[name="name"][value="${name}"]`);
    }

    reviewCard(comment) {
        return this.page.locator('#review-moderation article').filter({ hasText: comment });
    }

    async deleteViaConfirm(triggerButton, { confirmText = 'ลบ' } = {}) {
        await triggerButton.click();
        const modal = this.page.locator('.swal2-popup');
        await expect(modal).toBeVisible();
        await modal.getByRole('button', { name: confirmText, exact: true }).click();
    }
}
