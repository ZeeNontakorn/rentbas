import { test, expect } from '@playwright/test';
import { PrivateTrainingPage } from './pages/private-train-m.page.js';

const setup = async request => {
    const response = await request.post('/__e2e/private-training-management/case');
    expect(response.ok()).toBeTruthy();
    return response.json();
};

const loginAdmin = async (page, fixture) => {
    const app = new PrivateTrainingPage(page);
    await app.loginFromHome(fixture.admin);
    await app.openAdminPrivateTraining();
    return app;
};

const card = (page, note) => page.locator('main .divide-y.divide-gray-100 > .p-5').filter({ hasText: note });
const tab = (page, name) => page.getByRole('link', { name, exact: true });

async function state(page) {
    const response = await page.request.get('/__e2e/private-training/state');
    expect(response.ok()).toBeTruthy();
    return response.json();
}

async function openSchedule(page, staffId) {
    await page.goto(`/admin/private-schedule?staff_id=${staffId}`);
    await expect(page.getByRole('heading', { name: 'ตารางงานโค้ช และผู้ช่วยสนาม' })).toBeVisible();
    await expect(page.locator('#private-schedule-calendar')).toBeVisible();
}

test.describe.serial('Private Training Management PTM-01 ถึง PTM-15', () => {
    test('PTM-01 ตรวจสอบการเข้าถึงหน้าจัดการแพ็กเกจไพรเวทเทรนนิ่ง', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await expect(page.getByRole('heading', { name: 'จัดการเทรนเนอร์ส่วนตัว' })).toBeVisible();
        await expect(card(page, '[E2E PTM] รออนุมัติ')).toContainText('รออนุมัติ');
    });

    for (const [id, tabName, note, statusText] of [
        ['PTM-02', 'รออนุมัติ', '[E2E PTM] รออนุมัติ', 'รออนุมัติ'],
        ['PTM-03', 'รอจัดสนาม', '[E2E PTM] รอจัดสนาม', 'รอจัดสนาม'],
        ['PTM-04', 'ยืนยันแล้ว', '[E2E PTM] ยืนยันแล้ว', 'ยืนยันแล้ว'],
        ['PTM-05', 'เลยกำหนด', '[E2E PTM] เลยกำหนด', 'เลยกำหนด'],
        ['PTM-06', 'ปฏิเสธแล้ว', '[E2E PTM] ปฏิเสธแล้ว', 'ปฏิเสธแล้ว'],
    ]) {
        test(`${id} ตรวจสอบการแสดงผลแถบ${tabName}`, async ({ page, request }) => {
            const fixture = await setup(request);
            await loginAdmin(page, fixture);
            await tab(page, tabName).click();
            await expect(page).toHaveURL(new RegExp(`status=${id === 'PTM-02' ? 'pending' : id === 'PTM-03' ? 'awaiting_court' : id === 'PTM-04' ? 'confirmed' : id === 'PTM-05' ? 'expired' : 'rejected'}`));
            await expect(card(page, note)).toContainText(statusText);
            await expect(card(page, '[E2E PTM] รออนุมัติ')).toHaveCount(id === 'PTM-02' ? 1 : 0);
        });
    }

    test('PTM-07 ตรวจสอบการแสดงผลแถบทั้งหมด', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await tab(page, 'ทั้งหมด').click();
        for (const note of ['รออนุมัติ', 'รอจัดสนาม', 'ยืนยันแล้ว', 'เลยกำหนด', 'ปฏิเสธแล้ว']) {
            await expect(card(page, `[E2E PTM] ${note}`)).toHaveCount(1);
        }
    });

    test('PTM-08 ตรวจสอบการอนุมัติการจอง', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await card(page, '[E2E PTM] รออนุมัติ').getByRole('button', { name: 'อนุมัติ', exact: true }).click();
        await expect(page.locator('.swal2-title')).toContainText('รับคำขอเรียบร้อยแล้ว');
        await expect(page).toHaveURL(/status=awaiting_court/);
        const snapshot = await state(page);
        expect(snapshot.bookings.find(item => item.id === fixture.bookings.pending).status).toBe('awaiting_court');
    });

    test('PTM-09 ตรวจสอบการอนุมัติการปฏิเสธการจอง', async ({ page, request }) => {
        const fixture = await setup(request);
        const reason = 'ไม่สามารถให้บริการในช่วงเวลาที่เลือก';
        await loginAdmin(page, fixture);
        await card(page, '[E2E PTM] รออนุมัติ').getByRole('button', { name: 'ปฏิเสธ', exact: true }).click();
        await page.locator('#rejectModal textarea').fill(reason);
        await page.locator('#rejectModal').getByRole('button', { name: 'ยืนยันปฏิเสธ' }).dispatchEvent('click');
        await expect(page.locator('.swal2-title')).toContainText('ปฏิเสธคำขอจองเรียบร้อย');
        const snapshot = await state(page);
        expect(snapshot.bookings.find(item => item.id === fixture.bookings.pending)).toMatchObject({ status: 'rejected', reject_reason: reason });
    });

    test('PTM-10 ตรวจสอบการจัดสนามหลังอนุมัติ', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await tab(page, 'รอจัดสนาม').click();
        await card(page, '[E2E PTM] รอจัดสนาม').getByRole('button', { name: 'จัดสนาม' }).click();
        const select = page.locator('#court-section-select');
        await expect(select).toBeEnabled();
        await expect(select.locator('option')).toContainText([fixture.courts.free.name]);
        await select.selectOption(String(fixture.courts.free.section_id));
        await page.locator('#confirm-court-button').dispatchEvent('click');
        await expect(page.locator('.swal2-title')).toContainText('จัดสนามและยืนยัน Private Training เรียบร้อยแล้ว');
        const snapshot = await state(page);
        expect(snapshot.bookings.find(item => item.id === fixture.bookings.awaiting_court)).toMatchObject({ status: 'confirmed', court_section_id: fixture.courts.free.section_id });
    });

    test('PTM-11 ตรวจสอบการจัดสนามที่มีการใช้งานแล้ว', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await tab(page, 'รอจัดสนาม').click();
        await card(page, '[E2E PTM] รอจัดสนาม').getByRole('button', { name: 'จัดสนาม' }).click();
        const options = page.locator('#court-section-select option');
        await expect(options).toContainText([fixture.courts.free.name]);
        await expect(options.filter({ hasText: fixture.courts.busy.name })).toHaveCount(0);
    });

    for (const [id, role, staffKey] of [
        ['PTM-12', 'โค้ช', 'coach'],
        ['PTM-13', 'ผู้ช่วยสนาม', 'assistant'],
    ]) {
        test(`${id} ตรวจสอบ Schedule ของ${role}`, async ({ page, request }) => {
            const fixture = await setup(request);
            await loginAdmin(page, fixture);
            const staffId = staffKey === 'coach' ? fixture.coach.id : fixture.assistants.free.id;
            await openSchedule(page, staffId);
            const rangeEnd = new Date(`${fixture.schedule_date}T12:00:00`);
            rangeEnd.setDate(rangeEnd.getDate() + 1);
            const end = rangeEnd.toISOString().slice(0, 10);
            const response = await page.request.get(`/admin/private-schedule/events?staff_id=${staffId}&start=${fixture.schedule_date}&end=${end}`);
            expect(response.ok()).toBeTruthy();
            const events = await response.json();
            const event = events.find(item => item.extendedProps.bookingId === fixture.bookings.confirmed);
            expect(event).toBeTruthy();
            expect(event.extendedProps).toMatchObject({ statusKey: 'confirmed', customerEmail: fixture.user.email });
            expect(event.start).toContain(`${fixture.schedule_date}T13:00`);
        });
    }

    for (const [id, action] of [['PTM-14', 'แก้ไข'], ['PTM-15', 'ลบ']]) {
        test(`${id} ตรวจสอบการ${action} Schedule ไพรเวทเทรนนิ่งที่ระบบสร้าง`, async ({ page, request }) => {
            const fixture = await setup(request);
            await loginAdmin(page, fixture);
            await openSchedule(page, fixture.coach.id);
            await page.locator('.fc-next-button').click();
            const event = page.locator('.fc-event').filter({ hasText: `Private: ${fixture.user.name}` }).first();
            await expect(event).toBeVisible();
            await event.click();
            await expect(page.locator('.swal2-popup')).toContainText('รายละเอียดการจอง');
            await expect(page.locator('#admin-schedule-modal')).toBeHidden();
            await expect(page.locator('#submit-admin-event')).toBeHidden();
            await expect(page.locator('#delete-admin-event')).toBeHidden();
        });
    }
});
