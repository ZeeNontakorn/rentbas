import { test, expect } from '@playwright/test';
import { StaffSchedulePage } from './pages/staff-schedule.page.js';

async function setup(request) {
    const response = await request.post('/__e2e/staff-schedule/case');
    if (!response.ok()) throw new Error(`สร้างข้อมูล STAFF-SCH ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    return response.json();
}

async function openAdmin(page, request) {
    const fixture = await setup(request);
    const app = new StaffSchedulePage(page);
    await app.login(fixture.admin);
    await app.goto();
    return { app, fixture };
}

test.describe.serial('ตารางงานบุคลากร STAFF-SCH-01 ถึง 08', () => {
    test('STAFF-SCH-01 ตรวจสอบการเข้าถึงหน้าจอ', async ({ page, request }) => {
        const fixture = await setup(request);
        const app = new StaffSchedulePage(page);
        await app.login(fixture.admin);
        await app.goto();

        await expect(page.getByRole('heading', { name: 'ตารางงานโค้ช และผู้ช่วยสนาม' })).toBeVisible();
        await expect(app.calendar).toBeVisible();
        await expect(app.staffFilter).toBeVisible();
    });

    test('STAFF-SCH-02 ตรวจสอบการเปลี่ยนรูปแบบการแสดงผลกำหนดการแบบรายการ', async ({ page, request }) => {
        const { app } = await openAdmin(page, request);
        await app.viewButton('สัปดาห์').click();
        await app.viewButton('รายการ').click();

        await expect(page.locator('.fc-listWeek-view')).toBeVisible();
    });

    test('STAFF-SCH-03 ตรวจสอบการเปลี่ยนรูปแบบการแสดงผลกำหนดการแบบรายวัน', async ({ page, request }) => {
        const { app } = await openAdmin(page, request);
        await app.viewButton('วัน').click();

        await expect(page.locator('.fc-timeGridDay-view')).toBeVisible();
    });

    test('STAFF-SCH-04 ตรวจสอบการเปลี่ยนรูปแบบการแสดงผลกำหนดการแบบรายสัปดาห์', async ({ page, request }) => {
        const { app } = await openAdmin(page, request);
        await app.viewButton('สัปดาห์').click();

        await expect(page.locator('.fc-timeGridWeek-view')).toBeVisible();
    });

    test('STAFF-SCH-05 ตรวจสอบการเปลี่ยนรูปแบบการแสดงผลกำหนดการแบบรายเดือน', async ({ page, request }) => {
        const { app } = await openAdmin(page, request);
        await app.viewButton('เดือน').click();

        await expect(page.locator('.fc-dayGridMonth-view')).toBeVisible();
    });

    test('STAFF-SCH-06 เลือกดูกำหนดการของบุคลากรทั้งหมด', async ({ page, request }) => {
        const { app, fixture } = await openAdmin(page, request);
        // Land on a specific staff's schedule first, then switch to "ดูทุกคน"
        // so picking "all" is a real change, not the page's own default.
        await app.selectStaff(fixture.coach.id);
        await expect(page.getByText(fixture.coachEvent.title)).toBeVisible();

        await app.selectStaff('all');
        await expect(page.getByText(fixture.coachEvent.title)).toBeVisible();
        await expect(page.getByText(fixture.assistantEvent.title)).toBeVisible();
    });

    test('STAFF-SCH-07 เลือกดูกำหนดการรายบุคคล (เลือกดูของโค้ช)', async ({ page, request }) => {
        const { app, fixture } = await openAdmin(page, request);
        await app.selectStaff(fixture.coach.id);

        await expect(page.getByText(fixture.coachEvent.title)).toBeVisible();
        await expect(page.getByText(fixture.assistantEvent.title)).toHaveCount(0);
    });

    test('STAFF-SCH-08 เลือกดูกำหนดการรายบุคคล (เลือกดูของผู้ช่วยสนาม)', async ({ page, request }) => {
        const { app, fixture } = await openAdmin(page, request);
        await app.selectStaff(fixture.assistant.id);

        await expect(page.getByText(fixture.assistantEvent.title)).toBeVisible();
        await expect(page.getByText(fixture.coachEvent.title)).toHaveCount(0);
    });
});
