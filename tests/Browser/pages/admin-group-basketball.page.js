import { expect } from '@playwright/test';

export class AdminGroupBasketballPage {
    constructor(page) {
        this.page = page;
    }

    async login(account) {
        await this.page.goto('/login');
        await this.page.locator('input[name="email"]').fill(account.email);
        await this.page.locator('input[name="password"]').fill(account.password);
        await this.page.getByRole('button', { name: 'เข้าสู่ระบบ' }).click();
    }

    async goto() {
        await this.page.goto('/admin/group-sessions');
        await expect(this.page.getByRole('heading', { name: 'กลุ่มเล่นบาส', exact: true })).toBeVisible();
        await this.page.waitForFunction(() => window.Alpine !== undefined);
    }

    templateRow(name) {
        return this.page.locator('table tbody tr').filter({ hasText: name });
    }

    upcomingRow(title) {
        return this.page.locator('table tbody tr').filter({ hasText: title });
    }

    async openTemplateForm() {
        await this.page.getByRole('button', { name: /สร้างรอบประจำใหม่/ }).click();
        return this.page.locator('form').filter({
            has: this.page.getByRole('button', { name: 'บันทึก', exact: true }),
        });
    }

    async openCustomRoundForm() {
        await this.page.getByRole('button', { name: /เปิดรอบแบบกำหนดเอง/ }).click();
        return this.roundForm();
    }

    roundForm() {
        return this.page.locator('form').filter({ has: this.page.locator('input[name="play_date"]') });
    }

    async fillTemplate(form, fixture, overrides = {}) {
        const data = {
            name: '[E2E ADMIN GROUP] CREATED', day_of_week: '3', start_time: '17:00',
            end_time: '19:00', court_id: String(fixture.court.id), max_players: '16',
            credit_cost: '120', ...overrides,
        };
        await this.setFormValues(form, data);
    }

    async fillRound(form, fixture, overrides = {}) {
        const data = {
            title: '[E2E ADMIN GROUP] CUSTOM', play_date: fixture.play_date,
            start_time: '17:00', end_time: '19:00', court_id: String(fixture.court.id),
            max_players: '10', credit_cost: '80', cancel_deadline: '', ...overrides,
        };
        await this.setFormValues(form, data);
    }

    async setFormValues(form, data) {
        await form.evaluate((element, values) => {
            for (const [name, value] of Object.entries(values)) {
                const field = element.elements.namedItem(name);
                if (!field) continue;
                field.value = value;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }, data);
    }
}
