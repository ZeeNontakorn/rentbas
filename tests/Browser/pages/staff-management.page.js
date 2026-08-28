import { expect } from '@playwright/test';

export class StaffManagementPage {
    constructor(page) {
        this.page = page;
        this.heading = page.getByRole('heading', { name: 'จัดการโค้ช / ผู้ช่วยสนาม' });
        this.searchInput = page.locator('input[name="search"]');
        this.calendar = page.locator('#staff-schedule-calendar');
        this.profileModal = page.locator('#staffProfileModal');
        this.profileForm = this.profileModal.locator('form');
        this.nameInput = this.profileForm.locator('input[name="us_name"]');
        this.emailInput = this.profileForm.locator('input[name="email"]');
        this.roleSelect = this.profileForm.locator('select[name="membership_type"]');
        this.phoneInput = this.profileForm.locator('input[name="phone"]');
        this.genderSelect = this.profileForm.locator('select[name="gender"]');
        this.specialtyInput = this.profileForm.locator('input[name="specialty"]');
        this.bioInput = this.profileForm.locator('textarea[name="bio"]');
        this.imageInput = this.profileForm.locator('input[name="profile_image"]');
        this.saveButton = this.profileForm.getByRole('button', { name: 'บันทึกการแก้ไข' });
    }

    async login(account) {
        await this.page.goto('/login');
        await this.page.locator('input[name="email"]').fill(account.email);
        await this.page.locator('input[name="password"]').fill(account.password);
        await this.page.getByRole('button', { name: 'เข้าสู่ระบบ', exact: true }).click();
        await expect(this.page.locator('#logout-form')).toBeAttached();
    }

    async openFromProfileMenu() {
        await this.page.locator('#adminMenuBtn').click();
        await this.page.locator('#adminMenuDropdown').getByRole('link', { name: 'โค้ช และผู้ช่วย' }).click();
        await expect(this.page).toHaveURL(/\/admin\/staffs(?:\?.*)?$/);
        await expect(this.heading).toBeVisible();
    }

    row(name) {
        return this.page.locator('tbody tr').filter({ hasText: name });
    }

    async search(term) {
        await this.searchInput.fill(term);
        await this.page.getByRole('button', { name: 'ค้นหา', exact: true }).click();
    }

    async selectTab(name) {
        await this.page.getByRole('link', { name }).click();
    }

    async openProfile(name) {
        const row = this.row(name);
        await expect(row).toHaveCount(1);
        await row.getByRole('link', { name: 'ดูโปรไฟล์และตารางงาน' }).click();
        await expect(this.page).toHaveURL(/\/admin\/staffs\/\d+$/);
    }

    async openEdit() {
        await this.page.getByRole('button', { name: 'แก้ไขข้อมูลบุคลากร' }).click();
        await expect(this.profileModal).toBeVisible();
    }

    async save() {
        await this.saveButton.click();
    }

    async expectSuccess() {
        await expect(this.page.locator('.swal2-title')).toContainText(/สำเร็จ/);
        await expect(this.page.locator('.swal2-container')).toBeHidden({ timeout: 5_000 });
    }
}
