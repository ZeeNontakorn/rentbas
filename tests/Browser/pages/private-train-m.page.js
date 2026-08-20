import { expect } from '@playwright/test';

export class PrivateTrainingPage {
    constructor(page) {
        this.page = page;
        this.calendar = page.locator('#private-calendar');
        this.bookingModal = page.locator('#bookTrainingModal');
        this.packageSelect = page.locator('#package-purchase-select');
        this.participantCount = page.locator('#participant-count');
        this.assistantRequested = page.locator('#assistant-requested');
        this.assistantSelect = page.locator('#court-assistant-select');
    }

    async loginFromHome(account) {
        await this.page.goto('/');
        await this.page.getByRole('link', { name: 'จองสนามเลย', exact: true }).first().click();
        await expect(this.page).toHaveURL(/\/login$/);
        await this.page.locator('input[name="email"]').fill(account.email);
        await this.page.locator('input[name="password"]').fill(account.password);
        await this.page.getByRole('button', { name: 'เข้าสู่ระบบ', exact: true }).click();
        await expect(this.page.locator('#logout-form')).toBeAttached();
    }

    async openFromNavigation() {
        await this.page.locator('a[href$="/private-training"]:visible').click();
        await expect(this.page).toHaveURL(/\/private-training$/);
        await expect(this.page.getByRole('heading', { name: /Private Training/ })).toBeVisible();
    }

    async loginAndOpen(account) {
        await this.loginFromHome(account);
        await this.openFromNavigation();
    }

    packageCard(packageName) {
        return this.page.locator('.package-card2').filter({ hasText: packageName });
    }

    async openPackagesFromEmptyState() {
        await this.page.locator('#btn-need-package').click();
        await expect(this.page).toHaveURL(/\/#packages$/);
        await expect(this.page.locator('#packages')).toBeVisible();
    }

    async selectPackage(packageName) {
        const card = this.packageCard(packageName);
        await expect(card).toBeVisible();
        await card.getByRole('button', { name: 'เลือกแพ็กเกจนี้', exact: true }).click();
        await expect(this.page).toHaveURL(/\/package-checkout\/purchase\/\d+$/);
    }

    async buyPackage(packageName) {
        await this.openPackagesFromEmptyState();
        await this.selectPackage(packageName);
    }

    checkoutRow(label) {
        const exactLabel = new RegExp(`^${label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}$`);
        return this.page.locator('.co-row').filter({
            has: this.page.locator('.k', { hasText: exactLabel }),
        }).locator('.v');
    }

    async payWithCredit() {
        await this.page.locator('.btn-pay.credit').click();
        await expect(this.page).toHaveURL(/\/private-training$/);
    }

    coachCard(coachName) {
        return this.page.locator('a[href*="/private-training/"]').filter({ hasText: coachName });
    }

    async openCoach(coachName) {
        const card = this.coachCard(coachName);
        await expect(card).toBeVisible();
        await card.click();
        await expect(this.page).toHaveURL(/\/private-training\/\d+$/);
        await expect(this.calendar).toBeVisible();
    }

    async showDate(date) {
        for (let attempt = 0; attempt < 8; attempt += 1) {
            if (await this.calendar.locator(`.fc-timegrid-col[data-date="${date}"]`).count()) return;
            await this.calendar.locator('.fc-next-button').click();
            await this.page.waitForTimeout(150);
        }
        throw new Error(`ไม่พบวันที่ ${date} ในปฏิทินหลังเลื่อนไป 8 สัปดาห์`);
    }

    async dragAvailableTime(date, start = '18:00', end = '19:00') {
        await this.showDate(date);
        const column = this.calendar.locator(`.fc-timegrid-col[data-date="${date}"]`).first();
        const startLane = this.calendar.locator(`.fc-timegrid-slot-lane[data-time="${start}:00"]`).first();
        const endLane = this.calendar.locator(`.fc-timegrid-slot-lane[data-time="${end}:00"]`).first();

        await expect(column).toBeVisible();
        await expect(startLane).toBeVisible();
        await startLane.scrollIntoViewIfNeeded();

        const [columnBox, startBox, endBox] = await Promise.all([
            column.boundingBox(),
            startLane.boundingBox(),
            endLane.boundingBox(),
        ]);
        if (!columnBox || !startBox || !endBox) throw new Error('อ่านตำแหน่งช่องเวลาในปฏิทินไม่ได้');

        const x = columnBox.x + columnBox.width / 2;
        await this.page.mouse.move(x, startBox.y + 3);
        await this.page.mouse.down();
        // ปล่อยเมาส์ก่อนขอบบนของ slot ปลายเล็กน้อย เพราะ FullCalendar นับ
        // slot ที่ pointer อยู่เป็นช่วงที่เลือกด้วย (ปล่อยบน 19:00 จะกลายเป็น 19:30)
        await this.page.mouse.move(x, endBox.y - 2, { steps: 12 });
        await this.page.mouse.up();

        const confirmButton = this.page.locator('#private-calendar-confirm-btn');
        await expect(confirmButton).toBeVisible();
        await confirmButton.click();
        await expect(this.bookingModal).toBeVisible();
        await expect(this.page.locator('#booking-date')).toHaveValue(date);
        await expect(this.page.locator('#booking-start')).toHaveValue(start);
        await expect(this.page.locator('#booking-end')).toHaveValue(end);
    }

    async clickBusyTime(date) {
        await this.showDate(date);
        const event = this.calendar.locator('.fc-event').filter({ hasText: 'โค้ชไม่ว่าง' }).first();
        await expect(event).toBeVisible();
        await event.click();
    }

    async clickToday(date) {
        await this.showDate(date);
        const column = this.calendar.locator(`.fc-timegrid-col[data-date="${date}"]`).first();
        const lane = this.calendar.locator('.fc-timegrid-slot-lane[data-time="18:00:00"]').first();
        await lane.scrollIntoViewIfNeeded();
        const [columnBox, laneBox] = await Promise.all([column.boundingBox(), lane.boundingBox()]);
        if (!columnBox || !laneBox) throw new Error('อ่านตำแหน่งวันนี้ในปฏิทินไม่ได้');
        await this.page.mouse.click(columnBox.x + columnBox.width / 2, laneBox.y + 4);
    }

    packageOption(purchaseId) {
        return this.packageSelect.locator(`option[value="${purchaseId}"]`);
    }

    async choosePackage(purchaseId) {
        await this.packageSelect.selectOption(String(purchaseId));
        await expect(this.packageSelect).toHaveValue(String(purchaseId));
    }

    async requestAssistant() {
        const responsePromise = this.page.waitForResponse((response) =>
            response.url().includes('/private-training/available-assistants') && response.ok(),
        );
        await this.assistantRequested.selectOption('1');
        await responsePromise;
        await expect(this.assistantSelect).toBeEnabled();
    }

    async fillBooking({ purchaseId, participantCount = 1, assistantId, note = '' }) {
        await this.choosePackage(purchaseId);
        await this.participantCount.selectOption(String(participantCount));
        if (assistantId) {
            await this.requestAssistant();
            await this.assistantSelect.selectOption(String(assistantId));
        } else {
            await this.assistantRequested.selectOption('0');
        }
        if (note) await this.bookingModal.locator('textarea[name="note"]').fill(note);
    }

    submitButton() {
        return this.bookingModal.getByRole('button', { name: 'ส่งคำขอ', exact: true });
    }

    async submitBooking() {
        await this.submitButton().click();
        await expect(this.page.locator('.swal2-title')).toContainText('ส่งคำขอจองเทรนเนอร์ส่วนตัวเรียบร้อย');
    }

    async exposeParticipantInput() {
        await this.participantCount.evaluate((select) => {
            const input = document.createElement('input');
            input.id = select.id;
            input.name = select.name;
            input.type = 'text';
            input.className = select.className;
            input.placeholder = 'จำนวนผู้เข้าร่วม (จำลองแก้ Request)';
            select.replaceWith(input);
        });
        this.participantCount = this.page.locator('#participant-count');
        await expect(this.participantCount).toBeVisible();
    }

    async enterInvalidParticipant(value) {
        await this.exposeParticipantInput();
        if (value !== '') await this.participantCount.pressSequentially(value, { delay: 120 });
        await expect(this.participantCount).toHaveValue(value);
    }

    async openAdminPrivateTraining() {
        await this.page.getByRole('button', { name: 'การสอน', exact: true }).click();
        await this.page.getByRole('link', { name: 'จัดการ Private Training', exact: true }).click();
        await expect(this.page).toHaveURL(/\/admin\/private-training(?:\?.*)?$/);
    }

    adminBookingCard(email) {
        return this.page.locator('main .divide-y.divide-gray-100 > .p-5').filter({ hasText: email });
    }

    async openAdminUsers() {
        await this.page.locator('#adminMenuBtn').click();
        await this.page.getByRole('link', { name: 'จัดการผู้ใช้งาน', exact: true }).click();
        await expect(this.page).toHaveURL(/\/admin\/users$/);
    }
}
