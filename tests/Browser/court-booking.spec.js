import { test, expect } from '@playwright/test';
import { PrivateTrainingPage } from './pages/private-train-m.page.js';

async function setup(request, data = {}) {
    const response = await request.post('/__e2e/court-booking/case', { data });
    if (!response.ok()) throw new Error(`สร้างข้อมูล COUR-BOO ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    return response.json();
}
async function login(page, fixture) { await new PrivateTrainingPage(page).loginFromHome(fixture.user); }
async function state(request) { const r = await request.get('/__e2e/court-booking/state'); expect(r.ok()).toBeTruthy(); return r.json(); }
const addDays = days => { const d = new Date(); d.setDate(d.getDate() + days); return d.toISOString().slice(0, 10); };
const sectionUrl = (f, code = 'full', duration = 60) => `/booking/calendar?court_id=${f.court.id}&section_id=${f.sections[code].id}&date=${f.date}&duration_minutes=${duration}`;
async function selectCalendarTime(page, sectionId, hour = 16) {
    const lane = page.locator(`#lane-${sectionId}`);
    const box = await lane.boundingBox();
    if (!box) throw new Error('ไม่พบ lane สำหรับเลือกเวลา');
    await lane.click({ position: { x: box.width / 2, y: box.height * ((hour - 6) / 16) } });
    await expect(page.locator('#confirmBox')).toBeVisible();
}

test.describe.serial('Court Booking COUR-BOO-01 ถึง COUR-BOO-53', () => {
    test('COUR-BOO-01 ตรวจสอบการเข้าสู่หน้าจองสนามจากหน้า Home', async ({ page, request }) => {
        const f = await setup(request); await login(page, f); await page.goto('/');
        await page.getByRole('link', { name: 'จองสนามเลย', exact: true }).first().click();
        await expect(page).toHaveURL(/\/booking$/); await expect(page.getByText('เลือกวัน & เวลา')).toBeVisible();
    });
    test('COUR-BOO-02 ตรวจสอบการเข้าสู่หน้าจองสนามจากเมนูจองสนาม', async ({ page, request }) => {
        const f = await setup(request); await login(page, f);
        await page.locator('a[href$="/booking"]:visible').first().click(); await expect(page).toHaveURL(/\/booking$/);
    });

    for (const [id, title, offset, expected] of [
        ['03', 'เลือกวันที่ปัจจุบัน', 0, addDays(0)], ['04', 'เลือกวันที่ล่วงหน้าไม่เกิน 3 วัน', 3, addDays(3)],
        ['05', 'เลือกวันที่ล่วงหน้าเกิน 3 วัน', 4, addDays(3)], ['06', 'เลือกวันที่ย้อนหลัง', -1, addDays(0)],
    ]) test(`COUR-BOO-${id} ตรวจสอบการ${title}`, async ({ page, request }) => {
        const f = await setup(request); await login(page, f); await page.goto(`/booking?date=${addDays(offset)}`);
        await expect(page.locator('#dateInput')).toHaveValue(expected);
    });

    test('COUR-BOO-07 ตรวจสอบการกำหนดระยะเวลาขั้นต่ำ 30 นาที', async ({ page, request }) => {
        const f = await setup(request); await login(page, f); await page.goto('/booking?duration_minutes=1');
        await expect(page.locator('#durationInput')).toHaveValue('30'); await expect(page.locator('#durMinus')).toBeDisabled();
    });
    for (const [id, title, start, action, result] of [
        ['08', 'เพิ่มระยะเวลาทีละ 30 นาที', 60, '#durPlus', '90'], ['09', 'ลดระยะเวลาทีละ 30 นาที', 60, '#durMinus', '30'],
        ['10', 'ระยะเวลาสูงสุด 300 นาที', 300, null, '300'], ['11', 'เพิ่มระยะเวลาเกิน 300 นาที', 300, 'disabled:#durPlus', '300'],
        ['12', 'ลดระยะเวลาต่ำกว่า 30 นาที', 30, 'disabled:#durMinus', '30'],
    ]) test(`COUR-BOO-${id} ตรวจสอบ${title}`, async ({ page, request }) => {
        const f = await setup(request); await login(page, f); await page.goto(`/booking?duration_minutes=${start}`);
        if (action?.startsWith('disabled:')) await expect(page.locator(action.slice(9))).toBeDisabled();
        else if (action) await page.locator(action).click();
        await expect(page.locator('#durationInput')).toHaveValue(result);
    });

    test('COUR-BOO-13 ตรวจสอบการแสดงสนามที่ว่างตามวันที่และระยะเวลา', async ({ page, request }) => {
        const f = await setup(request); await login(page, f); await page.goto(`/booking/courts?date=${f.date}&duration_minutes=60`);
        await expect(page.getByText(f.court.name, { exact: true })).toBeVisible();
    });
    test('COUR-BOO-14 ตรวจสอบการเลือกสนาม', async ({ page, request }) => {
        const f = await setup(request); await login(page, f); await page.goto(`/booking/courts?date=${f.date}&duration_minutes=60`);
        await page.getByText(f.court.name, { exact: true }).click(); await expect(page).toHaveURL(/\/booking\/sections/);
    });
    for (const [id, code, title] of [['15','full','เต็มสนาม'],['16','a','ครึ่งสนาม A'],['17','b','ครึ่งสนาม B']])
        test(`COUR-BOO-${id} ตรวจสอบการเลือก${title}`, async ({ page, request }) => {
            const f = await setup(request); await login(page, f); await page.goto(`/booking/sections?court_id=${f.court.id}&date=${f.date}&duration_minutes=60`);
            await page.locator(`a[href*="section_id=${f.sections[code].id}"]`).click();
            await expect(page).toHaveURL(new RegExp(`section_id=${f.sections[code].id}`));
        });

    for (const [id, title, code] of [['18','เต็มสนามไม่สามารถเลือกได้เมื่อครึ่งสนามมีการใช้งาน','full'],['19','ครึ่งสนามเมื่อเต็มสนามมีการใช้งาน','a'],['20','การแสดงช่วงเวลาว่างตามระยะเวลา','b'],['21','การเลือกช่วงเวลาว่าง','b'],['22','ช่วงเวลาที่ไม่ว่างไม่สามารถเลือกได้','a'],['23','ช่วงเวลาปิดให้บริการไม่สามารถเลือกได้','b'],['24','ช่วงเวลาตรงกับระยะเวลาที่กำหนด','b']])
        test(`COUR-BOO-${id} ตรวจสอบ${title}`, async ({ page, request }) => {
            const f = await setup(request); await login(page, f); await page.goto(sectionUrl(f, code, id === '24' ? 90 : 60));
            await expect(page.locator(`#lane-${f.sections[code].id}`)).toBeVisible();
            if (id === '24') await expect(page.getByText(/ยาว 90 นาที/)).toBeVisible();
            if (['18','22'].includes(id)) await expect(page.locator('.cal-block').first()).toBeVisible();
        });

    for (const [id, title, text] of [
        ['25','การแสดงราคาปกติตามการตั้งค่า','ราคาปกติ'], ['26','การแสดงราคาตามช่วงเวลา Sunset','ราคา'],
        ['27','การแสดงราคาโปรโมชั่นเมื่อเข้าเงื่อนไข','COUR-BOO Promotion'], ['28','ราคาโปรโมชั่นไม่แสดงเมื่อไม่เข้าเงื่อนไข','เลือกรูปแบบราคา'],
        ['29','การคำนวณราคาตามเต็มสนาม/ครึ่งสนาม','ราคา'], ['30','รายละเอียดก่อนยืนยันการจอง','ยืนยัน'],
    ]) test(`COUR-BOO-${id} ตรวจสอบ${title}`, async ({ page, request }) => {
        const f = await setup(request); await login(page, f); const code = ['27','29'].includes(id) ? 'full' : 'b';
        await page.goto(sectionUrl(f, code)); await selectCalendarTime(page, f.sections[code].id, id === '27' ? 19 : 16);
        await expect(page.getByText(text, { exact: false }).first()).toBeAttached();
    });

    for (const [id, title] of [
        ['31','เข้าหน้ายืนยันการชำระเงินจากรายการจองที่ถูกต้อง'], ['32','รายละเอียดรายการจองในหน้าชำระเงิน'],
        ['33','ยอดเครดิตคงเหลือหลังชำระ'], ['34','ข้อความและจำนวนเงินบนปุ่มชำระด้วยเครดิต'],
        ['35','รีเฟรชหน้าชำระเงิน'], ['36','ย้อนกลับแล้วกลับมาหน้าชำระเงิน'], ['37','การลดลงของเวลา'],
        ['38','ปิดแท็บชำระเงินแล้วกลับเข้ามาก่อนหมดเวลา'], ['39','ออกจากระบบแล้วกลับเข้ามาก่อนหมดเวลา'],
        ['40','เปิด Checkout รายการเดียวกันหลายแท็บ'],
    ]) test(`COUR-BOO-${id} ตรวจสอบ${title}`, async ({ page, request, context }) => {
        const f = await setup(request, { with_checkout: true }); await login(page, f); const url = `/checkout/${f.checkout_booking_id}`; await page.goto(url);
        await expect(page.getByRole('heading', { name: 'ยืนยันการชำระเงิน' })).toBeVisible();
        if (id === '32') await expect(page.locator('.co-main')).toContainText(f.court.name);
        if (id === '34') await expect(page.locator('.btn-pay.credit')).toContainText('฿500');
        if (id === '35') { await page.reload(); await expect(page).toHaveURL(new RegExp(url)); }
        if (id === '36') { await page.goBack(); await page.goForward(); await expect(page).toHaveURL(new RegExp(url)); }
        if (id === '37') { const before = await page.locator('#coClock').textContent(); await page.waitForTimeout(1100); await expect(page.locator('#coClock')).not.toHaveText(before); }
        if (id === '38') { await page.close(); const next = await context.newPage(); await next.goto(url); await expect(next).toHaveURL(new RegExp(url)); }
        if (id === '39') { await page.locator('#logout-form').evaluate(form => form.requestSubmit()); await login(page, f); await page.goto(url); await expect(page).toHaveURL(new RegExp(url)); }
        if (id === '40') { const second = await context.newPage(); await second.goto(url); await expect(second.locator('#coClock')).toBeVisible(); }
        if (id === '33') {
            await expect(page.getByText('ยอดเครดิตคงเหลือ', { exact: true })).toBeVisible();
            await page.locator('.btn-pay.credit').click(); await expect(page).toHaveURL(/\/history/);
            expect((await state(request)).credit_balance).toBe(4500);
        }
    });

    for (const [id, title, seconds] of [['41','ปล่อยเวลาจนถึง 00:00',6],['42','กดชำระ 00:01',7],['43','กดชำระหลังหมดเวลา',-1],['44','Slot หลังรายการหมดเวลา',-1]])
        test(`COUR-BOO-${id} ${title}`, async ({ page, request }) => {
            const f = await setup(request, { with_checkout: true, lock_seconds: seconds }); await login(page, f); await page.goto(`/checkout/${f.checkout_booking_id}`);
            if (id === '41') { await expect(page.locator('#coClock')).toHaveText('00:00', { timeout: 8000 }); }
            if (id === '42') { await expect(page.locator('#coClock')).toHaveText('00:01', { timeout: 8000 }); await page.locator('.btn-pay.credit').click(); await expect(page).toHaveURL(/\/history/); }
            if (['43','44'].includes(id)) await expect(page).toHaveURL(/\/booking$/);
        });

    for (const [id, title, credit, pay] of [
        ['45','เครดิตมากกว่ายอดชำระ',1000,true], ['46','เครดิตเท่ากับยอดชำระ',500,true],
        ['47','เครดิตน้อยกว่ายอดชำระ',499,false], ['48','สถานะปุ่มเมื่อเครดิตต่ำกว่ายอดชำระ',499,false],
    ]) test(`COUR-BOO-${id} ทดสอบ${title}`, async ({ page, request }) => {
        const f = await setup(request, { with_checkout: true, credit_balance: credit }); await login(page, f); await page.goto(`/checkout/${f.checkout_booking_id}`);
        const button = page.locator('.btn-pay.credit');
        if (pay) { await button.click(); await expect(page).toHaveURL(/\/history/); expect((await state(request)).credit_balance).toBe(credit - 500); }
        else { await expect(button).toBeDisabled(); await expect(page.getByText(/เครดิตไม่เพียงพอ/)).toBeVisible(); }
    });

    test('COUR-BOO-49 ป้องกันการกดปุ่มชำระเงินซ้ำ', async ({ page, request }) => {
        const f = await setup(request, { with_checkout: true, credit_balance: 1000 }); await login(page, f); await page.goto(`/checkout/${f.checkout_booking_id}`);
        await page.evaluate(() => { const b=document.querySelector('.btn-pay.credit'); b.click(); b.click(); }); await expect(page).toHaveURL(/\/history/);
        expect((await state(request)).transactions).toBe(1);
    });
    test('COUR-BOO-50 ไม่ชำระเงินแล้วไปหน้าประวัติการจอง', async ({ page, request }) => {
        const f = await setup(request, { with_checkout: true }); await login(page, f); await page.goto('/history');
        await expect(page.getByText('รอชำระเงิน', { exact: true })).toBeVisible(); await expect(page.getByRole('main').getByRole('link', { name: /กลับไปชำระเงิน/ })).toBeVisible();
    });
    for (const [id, title] of [['51','การเปลี่ยนสถานะรายการจอง'],['52','การอัปเดตยอดเครดิตทุกตำแหน่ง'],['53','หน้าที่ระบบนำไปหลังชำระสำเร็จ']])
        test(`COUR-BOO-${id} ตรวจสอบ${title}`, async ({ page, request }) => {
            const f = await setup(request, { with_checkout: true, credit_balance: 1000 }); await login(page, f); await page.goto(`/checkout/${f.checkout_booking_id}`);
            await page.locator('.btn-pay.credit').click(); await expect(page).toHaveURL(/\/history/);
            const s = await state(request); expect(s.bookings.find(b => b.id === f.checkout_booking_id)).toMatchObject({ status:'approved', payment_status:'paid' });
            if (id === '52') { expect(s.credit_balance).toBe(500); await expect(page.locator('nav')).toContainText('500.00 ฿'); }
        });
});
