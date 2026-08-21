import { expect, test } from '@playwright/test';
import { AdminGroupBasketballPage } from './pages/admin-group-basketball.page.js';

async function setup(request, options = {}) {
    const response = await request.post('/__e2e/group-session-admin/case', { data: options });
    expect(response.ok()).toBeTruthy();
    return response.json();
}

async function openAdmin(page, request, options = {}) {
    const fixture = await setup(request, options);
    const admin = new AdminGroupBasketballPage(page);
    await admin.login(fixture.admin);
    await admin.goto();
    return { admin, fixture };
}

async function state(request, fixture) {
    return (await request.get(`/__e2e/group-session-admin/${fixture.round.id}/state`)).json();
}

test.describe.serial('Admin ตั้งค่ากลุ่มบาส GROUP-BAS-SET-01 ถึง 25', () => {
    test('GROUP-BAS-SET-01 เข้าสู่หน้าจอการตั้งกลุ่มเล่นบาส', async ({ page, request }) => {
        await openAdmin(page, request);
        await expect(page).toHaveURL(/\/admin\/group-sessions$/);
        await expect(page.getByText('รอบประจำ (เทมเพลต)')).toBeVisible();
        await expect(page.getByText('รอบที่กำลังจะถึง')).toBeVisible();
    });

    test('GROUP-BAS-SET-02 เข้าถึงหน้าประวัติกลุ่มบาส', async ({ page, request }) => {
        const { fixture } = await openAdmin(page, request);
        await page.getByRole('link', { name: 'ประวัติกลุ่มเล่นบาส' }).click();
        await expect(page.getByRole('heading', { name: 'ประวัติกลุ่มเล่นบาส' })).toBeVisible();
        await expect(page.getByText(fixture.past.title)).toBeVisible();
    });

    test('GROUP-BAS-SET-03 ค้นหาชื่อกลุ่ม', async ({ page, request }) => {
        const { fixture } = await openAdmin(page, request);
        await page.getByRole('link', { name: 'ประวัติกลุ่มเล่นบาส' }).click();
        await page.locator('input[name="search"]').fill('HISTORY SEARCH');
        await page.getByRole('button', { name: 'ค้นหา' }).click();
        await expect(page.getByText(fixture.past.title)).toBeVisible();
        await expect(page.getByText(fixture.round.title)).toHaveCount(0);
    });

    test('GROUP-BAS-SET-04 เพิ่มเทมเพลตรอบด้วยข้อมูลครบถ้วน', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const form = await admin.openTemplateForm();
        await admin.fillTemplate(form, fixture);
        await expect(form.locator('input[name="name"]')).toHaveValue('[E2E ADMIN GROUP] CREATED');
        await expect(form.locator('input[name="start_time"]')).toHaveValue('17:00');
        await expect(form.locator('input[name="end_time"]')).toHaveValue('19:00');
        await form.getByRole('button', { name: 'บันทึก', exact: true }).click();
        await expect(page.getByText('สร้างเทมเพลตรอบประจำเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(admin.templateRow('[E2E ADMIN GROUP] CREATED')).toContainText(fixture.court.name);
    });

    test('GROUP-BAS-SET-05 ไม่บันทึกเทมเพลตเมื่อไม่กรอกข้อมูล', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const form = await admin.openTemplateForm();
        await form.locator('input[name="name"]').fill('');
        await form.getByRole('button', { name: 'บันทึก', exact: true }).click();
        await expect(form.locator('input[name="name"]:invalid')).toBeVisible();
    });

    test('GROUP-BAS-SET-06 ไม่บันทึกเมื่อเว้นชื่อรอบและเวลา', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const form = await admin.openTemplateForm();
        await form.getByRole('button', { name: 'บันทึก', exact: true }).click();
        await expect(form.locator('input[name="name"]:invalid')).toBeVisible();
        // หน้าเว็บกำหนดค่าเริ่มต้นของช่องเวลาเป็น 00:00 จึงไม่ถือว่าเป็นช่องว่างในระดับ HTML
        // และ browser จะหยุด submit ที่ required field แรก (ชื่อรอบ) ก่อนถึง validation ฝั่ง server
        await expect(form.locator('input[name="start_time"]')).toHaveValue('00:00');
        await expect(form.locator('input[name="end_time"]')).toHaveValue('00:00');
        await expect(admin.templateRow('[E2E ADMIN GROUP] CREATED')).toHaveCount(0);
    });

    test('GROUP-BAS-SET-07 ปฏิเสธเวลาสิ้นสุดที่ไม่ถูกต้อง', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const form = await admin.openTemplateForm();
        await admin.fillTemplate(form, fixture, { start_time: '19:00', end_time: '18:00' });
        await form.getByRole('button', { name: 'บันทึก', exact: true }).click();
        await expect(page.getByText('เวลาเลิกต้องอยู่หลังเวลาเริ่ม').first()).toBeVisible();
    });

    test('GROUP-BAS-SET-08 ปฏิเสธจำนวนคนและเครดิตที่ไม่ถูกต้อง', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const form = await admin.openTemplateForm();
        await admin.fillTemplate(form, fixture, { max_players: '-1', credit_cost: '-10' });
        await form.getByRole('button', { name: 'บันทึก', exact: true }).click();
        await expect(form.locator('input[name="max_players"]:invalid')).toBeVisible();
        await expect(form.locator('input[name="credit_cost"]:invalid')).toBeVisible();
    });

    test('GROUP-BAS-SET-09 แก้ไขข้อมูลรอบประจำ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.templateRow(fixture.session.name).getByRole('button', { name: 'แก้ไข' }).click();
        const form = page.locator('form').filter({ has: page.getByRole('button', { name: 'บันทึกการแก้ไข' }) });
        await form.locator('input[name="name"]').fill('[E2E ADMIN GROUP] EDITED');
        await form.getByRole('button', { name: 'บันทึกการแก้ไข' }).click();
        await expect(admin.templateRow('[E2E ADMIN GROUP] EDITED')).toBeVisible();
    });

    test('GROUP-BAS-SET-10 ลบรอบประจำ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.templateRow(fixture.session.name).getByRole('button', { name: 'ลบ' }).click();
        await expect(page.getByRole('heading', { name: 'ยืนยันการทำรายการ' })).toBeVisible();
        await page.getByRole('button', { name: 'ลบเทมเพลต', exact: true }).click();
        await expect(page.locator('.swal2-popup').filter({ hasText: 'ลบเทมเพลตรอบประจำเรียบร้อยแล้ว' })).toBeVisible();
        await expect(admin.templateRow(fixture.session.name)).toHaveCount(0);
    });

    test('GROUP-BAS-SET-11 เปิดหน้าต่างเปิดรับสมัครและดึงข้อมูลจากเทมเพลต', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.templateRow(fixture.session.name).getByRole('button', { name: 'เปิดรับสมัครรอบใหม่' }).click();
        const form = admin.roundForm();
        await expect(page.getByRole('heading', { name: 'เปิดรอบ', exact: true })).toBeVisible();
        await expect(form.locator('input[name="title"]')).toHaveValue(fixture.session.name);
        await expect(form.locator('input[name="play_date"]')).toHaveValue(fixture.session_play_date);
        await expect(form.locator('input[name="start_time"]')).toHaveValue('18:00');
        await expect(form.locator('input[name="end_time"]')).toHaveValue('20:00');
        await expect(form.locator('select[name="court_id"]')).toHaveValue(String(fixture.court.id));
        await expect(form.locator('input[name="max_players"]')).toHaveValue('12');
        await expect(form.locator('input[name="credit_cost"]')).toHaveValue('100');
    });

    test('GROUP-BAS-SET-12 เปิดรอบประจำสำเร็จ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.templateRow(fixture.session.name).getByRole('button', { name: 'เปิดรับสมัครรอบใหม่' }).click();
        const form = admin.roundForm();
        await form.locator('select[name="court_id"]').selectOption(String(fixture.court.id));
        await form.getByRole('button', { name: 'เปิดรอบ' }).click();
        await expect(page.getByText('เปิดรอบเรียบร้อยแล้ว').first()).toBeVisible();
    });

    test('GROUP-BAS-SET-13 เปิดรอบประจำพร้อมวันยกเลิก', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.templateRow(fixture.session.name).getByRole('button', { name: 'เปิดรับสมัครรอบใหม่' }).click();
        const form = admin.roundForm();
        await form.locator('select[name="court_id"]').selectOption(String(fixture.court.id));
        await form.locator('input[name="cancel_deadline"]').fill(`${fixture.play_date}T16:00`);
        await form.getByRole('button', { name: 'เปิดรอบ' }).click();
        await expect(page.getByText('เปิดรอบเรียบร้อยแล้ว').first()).toBeVisible();
    });

    test('GROUP-BAS-SET-14 แก้ข้อมูลเฉพาะรอบก่อนเปิดรับสมัคร', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.templateRow(fixture.session.name).getByRole('button', { name: 'เปิดรับสมัครรอบใหม่' }).click();
        const form = admin.roundForm();
        await form.locator('input[name="title"]').fill('[E2E ADMIN GROUP] OVERRIDE');
        await form.locator('input[name="max_players"]').fill('20');
        await form.locator('select[name="court_id"]').selectOption(String(fixture.court.id));
        await form.getByRole('button', { name: 'เปิดรอบ' }).click();
        await expect(page.locator('body')).toContainText('[E2E ADMIN GROUP] OVERRIDE');
        await expect(page.locator('body')).toContainText('/ 20 คน');
    });

    test('GROUP-BAS-SET-15 ปฏิเสธวันยกเลิกหลังเวลาเล่น', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const form = await admin.openCustomRoundForm();
        await admin.fillRound(form, fixture, { cancel_deadline: `${fixture.play_date}T23:00` });
        await form.getByRole('button', { name: 'เปิดรอบ' }).click();
        await expect(page.locator('body')).toContainText('วันหรือเวลายกเลิกจองไม่ถูกต้อง');
    });

    test('GROUP-BAS-SET-16 เพิ่มรอบแบบกำหนดเองครบถ้วน', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const form = await admin.openCustomRoundForm();
        await admin.fillRound(form, fixture);
        await form.getByRole('button', { name: 'เปิดรอบ' }).click();
        await expect(page.locator('body')).toContainText('[E2E ADMIN GROUP] CUSTOM');
    });

    test('GROUP-BAS-SET-17 ไม่เปิดรอบกำหนดเองเมื่อข้อมูลไม่ครบ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const form = await admin.openCustomRoundForm();
        await admin.fillRound(form, fixture, { title: '' });
        await form.getByRole('button', { name: 'เปิดรอบ' }).click();
        await expect(form.locator('input[name="title"]:invalid')).toBeVisible();
    });

    test('GROUP-BAS-SET-18 ปฏิเสธเวลารอบกำหนดเองที่ไม่ถูกต้อง', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const form = await admin.openCustomRoundForm();
        await admin.fillRound(form, fixture, { start_time: '20:00', end_time: '19:00' });
        await form.getByRole('button', { name: 'เปิดรอบ' }).click();
        await expect(page.getByText('เวลาเลิกต้องอยู่หลังเวลาเริ่ม').first()).toBeVisible();
    });

    test('GROUP-BAS-SET-19 ปฏิเสธวันยกเลิกรอบกำหนดเองที่ไม่ถูกต้อง', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const form = await admin.openCustomRoundForm();
        await admin.fillRound(form, fixture, { cancel_deadline: `${fixture.play_date}T23:30` });
        await form.getByRole('button', { name: 'เปิดรอบ' }).click();
        await expect(page.locator('body')).toContainText('วันหรือเวลายกเลิกจองไม่ถูกต้อง');
    });

    test('GROUP-BAS-SET-20 ดูรายละเอียดและรายชื่อผู้ลงชื่อ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request, { with_signup: true });
        await admin.upcomingRow(fixture.round.title).getByRole('link', { name: 'ดูรายชื่อ' }).click();
        await expect(page.locator('table')).toContainText(fixture.member.name);
        for (const heading of ['ลำดับ', 'ชื่อ', 'เวลาลงชื่อ', 'เครดิตที่ใช้', 'เพิ่มโดย']) {
            await expect(page.getByRole('columnheader', { name: heading, exact: true })).toBeVisible();
        }
    });

    test('GROUP-BAS-SET-21 เพิ่มสมาชิกเข้าสู่รอบและตัดเครดิต', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.upcomingRow(fixture.round.title).getByRole('link', { name: 'ดูรายชื่อ' }).click();
        const memberSearch = page.getByPlaceholder('พิมพ์ชื่อ, อีเมล หรือเบอร์โทร เพื่อค้นหาสมาชิก');
        await memberSearch.fill(fixture.member.email);
        await page.locator('button').filter({ hasText: fixture.member.email }).click();
        await expect(page.locator('input[name="user_id"]')).toHaveValue(String(fixture.member.id));
        await page.getByRole('button', { name: '+ เพิ่มผู้จอง' }).click();
        await expect(page.locator('table')).toContainText(fixture.member.name);
        const snapshot = await state(request, fixture);
        expect(snapshot.signups).toHaveLength(1);
        expect(snapshot.member_credit).toBe(40000);
    });

    test('GROUP-BAS-SET-22 เพิ่มผู้จองภายนอกโดยไม่ตัดเครดิต', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.upcomingRow(fixture.round.title).getByRole('link', { name: 'ดูรายชื่อ' }).click();
        await page.locator('input[name="guest_name"]').fill('บุคคลภายนอก E2E');
        await page.getByRole('button', { name: '+ เพิ่มผู้จอง' }).click();
        const snapshot = await state(request, fixture);
        expect(snapshot.signups[0].name).toBe('บุคคลภายนอก E2E');
        expect(snapshot.member_credit).toBe(50000);
    });

    test('GROUP-BAS-SET-23 นำผู้จองออกจากรอบ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request, { with_signup: true });
        await admin.upcomingRow(fixture.round.title).getByRole('link', { name: 'ดูรายชื่อ' }).click();
        await page.getByRole('button', { name: 'นำออก' }).click();
        const confirmation = page.locator('.swal2-popup').filter({ hasText: 'ยืนยันการทำรายการ' });
        await expect(confirmation).toBeVisible();
        await confirmation.getByRole('button', { name: 'นำออก', exact: true }).click();
        await expect(page.locator('.swal2-popup').filter({ hasText: 'นำ สมาชิกกลุ่มบาส E2E ออกจากรอบแล้ว' })).toBeVisible();
        await expect(page.locator('table')).not.toContainText(fixture.member.name);
        const snapshot = await state(request, fixture);
        expect(snapshot.signups[0].status).toBe('cancelled');
        expect(snapshot.member_credit).toBe(50000);
    });

    test('GROUP-BAS-SET-24 ปิดรับสมัครรอบ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.upcomingRow(fixture.round.title).getByRole('link', { name: 'ดูรายชื่อ' }).click();
        page.once('dialog', dialog => dialog.accept());
        await page.getByRole('button', { name: 'ปิดรับสมัคร' }).click();
        expect((await state(request, fixture)).round_status).toBe('closed');
        await expect(page.getByRole('button', { name: '+ เพิ่มผู้จอง' })).toHaveCount(0);
    });

    test('GROUP-BAS-SET-25 ยกเลิกรอบ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request, { with_signup: true });
        await admin.upcomingRow(fixture.round.title).getByRole('link', { name: 'ดูรายชื่อ' }).click();
        await page.getByRole('button', { name: 'ยกเลิกรอบ' }).click();
        await expect(page.getByRole('heading', { name: 'ยืนยันการทำรายการ' })).toBeVisible();
        await page.getByRole('button', { name: 'ยืนยันยกเลิก', exact: true }).click();
        await expect(page.locator('.swal2-popup').filter({ hasText: 'ยกเลิกรอบและคืนเครดิตให้ทุกคนแล้ว' })).toBeVisible();
        const snapshot = await state(request, fixture);
        expect(snapshot.round_status).toBe('cancelled');
        expect(snapshot.signups[0].status).toBe('cancelled');
    });
});
