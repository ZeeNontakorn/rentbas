import { test, expect } from '@playwright/test';
import { CreditPackagesPage } from './pages/credit-packages.page.js';

async function setup(request) {
    const response = await request.post('/__e2e/credit-packages/case');
    if (!response.ok()) throw new Error(`สร้างข้อมูล CREDIT-PACK ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    return response.json();
}

async function state(request) {
    const response = await request.get('/__e2e/credit-packages/state');
    expect(response.ok()).toBeTruthy();
    return response.json();
}

async function openAdmin(page, request) {
    const fixture = await setup(request);
    const admin = new CreditPackagesPage(page);
    await admin.login(fixture.admin);
    await admin.goto();
    return { admin, fixture };
}

test.describe.serial('จัดการแพ็กเกจเครดิต / PromptPay / LINE CREDIT-PACK-01 ถึง 25', () => {
    test('CREDIT-PACK-01 เข้าสู่หน้าจอการตั้งค่าแพ็กเกจเครดิต', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await expect(admin.promptpayForm).toBeVisible();
        await expect(admin.lineUrlForm).toBeVisible();
        await expect(admin.addPackageForm).toBeVisible();
        await expect(page.locator('table')).toBeVisible();
    });

    test('CREDIT-PACK-02 บันทึกเบอร์ PromptPay ที่ถูกต้อง', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.promptpayForm.locator('input[name="promptpay_number"]').fill('0898765432');
        await admin.promptpayForm.locator('input[name="promptpay_name"]').fill('ทดสอบ บันทึก');
        await admin.promptpayForm.getByRole('button', { name: 'บันทึกข้อมูล PromptPay' }).click();

        await expect(page.getByText('บันทึกเบอร์ PromptPay เรียบร้อยแล้ว').first()).toBeVisible();
        await page.reload();
        await expect(admin.promptpayForm.locator('input[name="promptpay_number"]')).toHaveValue('0898765432');
        await expect(admin.promptpayForm.locator('input[name="promptpay_name"]')).toHaveValue('ทดสอบ บันทึก');

        const after = await state(request);
        expect(after.promptpay_number).toBe('0898765432');
        expect(after.promptpay_name).toBe('ทดสอบ บันทึก');
    });

    test('CREDIT-PACK-03 กรอกเบอร์ PromptPay ผิดรูปแบบ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.promptpayForm.locator('input[name="promptpay_number"]').fill('081234');
        await admin.promptpayForm.locator('input[name="promptpay_name"]').fill('ทดสอบ ผิดรูปแบบ');
        await admin.promptpayForm.getByRole('button', { name: 'บันทึกข้อมูล PromptPay' }).click();

        await expect(admin.promptpayForm.getByText('กรุณากรอกเบอร์มือถือให้ถูกต้อง (ขึ้นต้นด้วย 0 ตามด้วยตัวเลข 9 หลัก เช่น 0812345678)')).toBeVisible();
        expect((await state(request)).promptpay_number).toBe('0812345678');
    });

    test('CREDIT-PACK-04 เว้นช่องเบอร์ PromptPay ว่างแล้วบันทึก', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.promptpayForm.locator('input[name="promptpay_number"]').fill('');
        await admin.promptpayForm.locator('input[name="promptpay_name"]').fill('ทดสอบ เว้นเบอร์');
        await admin.promptpayForm.getByRole('button', { name: 'บันทึกข้อมูล PromptPay' }).click();

        await expect(admin.promptpayForm.getByText('กรุณากรอกเบอร์มือถือ PromptPay')).toBeVisible();
        expect((await state(request)).promptpay_number).toBe('0812345678');
    });

    test('CREDIT-PACK-05 เว้นช่องชื่อบัญชี', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.promptpayForm.locator('input[name="promptpay_number"]').fill('0898765432');
        await admin.promptpayForm.locator('input[name="promptpay_name"]').fill('');
        await admin.promptpayForm.getByRole('button', { name: 'บันทึกข้อมูล PromptPay' }).click();

        await expect(admin.promptpayForm.getByText('กรุณากรอกชื่อบัญชี PromptPay')).toBeVisible();
        expect((await state(request)).promptpay_name).toBe('ทดสอบ ระบบ');
    });

    test('CREDIT-PACK-06 กรอกชื่อบัญชีเป็นข้อความยาวเกินที่ระบบรองรับ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const tooLong = 'ก'.repeat(101);
        await admin.promptpayForm.locator('input[name="promptpay_number"]').fill('0898765432');
        await admin.promptpayForm.locator('input[name="promptpay_name"]').fill(tooLong);
        await admin.promptpayForm.getByRole('button', { name: 'บันทึกข้อมูล PromptPay' }).click();

        // No custom message is defined for the 100-char limit — just assert the
        // field's own error slot renders something and the value wasn't saved.
        await expect(page.locator('input[name="promptpay_name"] ~ p.text-red-600')).toBeVisible();
        expect((await state(request)).promptpay_name).toBe('ทดสอบ ระบบ');
    });

    test('CREDIT-PACK-07 แก้ไขเบอร์และ/หรือชื่อบัญชีเดิมเป็นค่าใหม่', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.promptpayForm.locator('input[name="promptpay_number"]').fill('0899999999');
        await admin.promptpayForm.locator('input[name="promptpay_name"]').fill('บัญชีใหม่ ทดสอบ');
        await admin.promptpayForm.getByRole('button', { name: 'บันทึกข้อมูล PromptPay' }).click();
        await expect(page.getByText('บันทึกเบอร์ PromptPay เรียบร้อยแล้ว').first()).toBeVisible();

        await admin.logout();
        await admin.login(fixture.customer);
        await page.goto('/credits/topup');
        await page.getByRole('button', { name: /ถัดไป/ }).click();

        await expect(page).toHaveURL(/\/credits\/topup\/checkout/);
        await expect(page.getByText('พร้อมเพย์:')).toContainText('0899999999');
        await expect(page.getByText('ชื่อบัญชี:')).toContainText('บัญชีใหม่ ทดสอบ');
    });

    test('CREDIT-PACK-08 บันทึกลิงก์ LINE ID ที่ถูกต้อง', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        await admin.lineUrlForm.locator('input[name="line_topup_url"]').fill('https://line.me/R/ti/p/@e2etest');
        await admin.lineUrlForm.getByRole('button', { name: 'บันทึกลิงก์' }).click();
        await expect(page.getByText('บันทึกลิงก์ LINE เรียบร้อยแล้ว').first()).toBeVisible();

        await admin.logout();
        await admin.login(fixture.customer);
        await page.goto('/credits/topup');

        const lineButton = page.getByRole('link', { name: /เติมผ่านไลน์/ });
        await expect(lineButton).toBeVisible();
        await expect(lineButton).toHaveAttribute('href', 'https://line.me/R/ti/p/@e2etest');
    });

    test('CREDIT-PACK-09 กรอกลิงก์ที่ไม่ใช่รูปแบบ URL', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.bypassHtml5Validation(admin.lineUrlForm);
        await admin.lineUrlForm.locator('input[name="line_topup_url"]').fill('ไม่ใช่ลิงก์');
        await admin.lineUrlForm.getByRole('button', { name: 'บันทึกลิงก์' }).click();

        await expect(admin.lineUrlForm.getByText('กรุณากรอกลิงก์ให้ถูกต้อง (ต้องขึ้นต้นด้วย http:// หรือ https://)')).toBeVisible();
        expect((await state(request)).line_topup_url).toBeNull();
    });

    test('CREDIT-PACK-10 เว้นช่องลิงก์ LINE ว่างแล้วบันทึก', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        // Seed a non-empty link first so clearing it is a real change, not a no-op.
        await admin.lineUrlForm.locator('input[name="line_topup_url"]').fill('https://line.me/R/ti/p/@before');
        await admin.lineUrlForm.getByRole('button', { name: 'บันทึกลิงก์' }).click();
        await expect(page.getByText('บันทึกลิงก์ LINE เรียบร้อยแล้ว').first()).toBeVisible();

        await admin.lineUrlForm.locator('input[name="line_topup_url"]').fill('');
        await admin.lineUrlForm.getByRole('button', { name: 'บันทึกลิงก์' }).click();
        await expect(page.getByText('บันทึกลิงก์ LINE เรียบร้อยแล้ว').first()).toBeVisible();

        await admin.logout();
        await admin.login(fixture.customer);
        await page.goto('/credits/topup');
        await expect(page.getByRole('link', { name: /เติมผ่านไลน์/ })).toHaveCount(0);
    });

    test('CREDIT-PACK-11 เพิ่มแพ็กเกจโดยกรอกข้อมูลครบถ้วนถูกต้อง', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.addPackageForm.locator('input[name="label"]').fill('[E2E CP] แพ็กเกจครบถ้วน');
        await admin.addPackageForm.locator('input[name="price"]').fill('10000');
        await admin.addPackageForm.locator('input[name="credit"]').fill('12000');
        await admin.addPackageForm.locator('input[name="expiry_days"]').fill('365');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(page.getByText('เพิ่มแพ็กเกจเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();
        const created = (await state(request)).packages.find((p) => p.label === '[E2E CP] แพ็กเกจครบถ้วน');
        expect(created).toBeTruthy();
        expect(created.price_satang).toBe(1000000);
        expect(created.credit_satang).toBe(1200000);
        expect(created.expiry_days).toBe(365);
    });

    test('CREDIT-PACK-12 เพิ่มแพ็กเกจโดยไม่กรอกป้ายชื่อ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.addPackageForm.locator('input[name="price"]').fill('300');
        await admin.addPackageForm.locator('input[name="credit"]').fill('300');
        await admin.addPackageForm.locator('input[name="expiry_days"]').fill('10');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(admin.addPackageForm.getByText('กรุณากรอกชื่อแพ็กเกจ')).toBeVisible();
    });

    test('CREDIT-PACK-13 เพิ่มแพ็กเกจโดยไม่กรอกราคา', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.addPackageForm.locator('input[name="label"]').fill('[E2E CP] ทดสอบ');
        await admin.addPackageForm.locator('input[name="credit"]').fill('500');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(admin.addPackageForm.getByText('กรุณากรอกราคาแพ็กเกจ')).toBeVisible();
    });

    test('CREDIT-PACK-14 เพิ่มแพ็กเกจโดยไม่กรอกเครดิตที่ได้', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.addPackageForm.locator('input[name="label"]').fill('[E2E CP] ทดสอบ');
        await admin.addPackageForm.locator('input[name="price"]').fill('600');
        await admin.addPackageForm.locator('input[name="expiry_days"]').fill('2');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(admin.addPackageForm.getByText('กรุณากรอกจำนวนเครดิตที่ได้')).toBeVisible();
    });

    test('CREDIT-PACK-15 เพิ่มแพ็กเกจโดยไม่กรอกวันหมดอายุ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.addPackageForm.locator('input[name="label"]').fill('[E2E CP] ไม่หมดอายุ');
        await admin.addPackageForm.locator('input[name="price"]').fill('1000');
        await admin.addPackageForm.locator('input[name="credit"]').fill('1000');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(page.getByText('เพิ่มแพ็กเกจเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();
        const created = (await state(request)).packages.find((p) => p.label === '[E2E CP] ไม่หมดอายุ');
        expect(created.expiry_days).toBeNull();
    });

    test('CREDIT-PACK-16 เพิ่มแพ็กเกจที่ราคา/เครดิตเป็นค่าติดลบหรือ 0', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.bypassHtml5Validation(admin.addPackageForm);
        await admin.addPackageForm.locator('input[name="label"]').fill('[E2E CP] ค่าติดลบ');
        await admin.addPackageForm.locator('input[name="price"]').fill('0');
        await admin.addPackageForm.locator('input[name="credit"]').fill('-1');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(admin.addPackageForm.getByText('ราคาแพ็กเกจต้องไม่ต่ำกว่า 1 บาท')).toBeVisible();
        await expect(admin.addPackageForm.getByText('จำนวนเครดิตที่ได้ต้องไม่ต่ำกว่า 1 เครดิต')).toBeVisible();
        expect((await state(request)).packages.find((p) => p.label === '[E2E CP] ค่าติดลบ')).toBeUndefined();
    });

    test('CREDIT-PACK-17 เพิ่มแพ็กเกจที่เครดิตเท่ากับราคาพอดี (ไม่มีโบนัส)', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.addPackageForm.locator('input[name="label"]').fill('[E2E CP] ไม่มีโบนัส');
        await admin.addPackageForm.locator('input[name="price"]').fill('400');
        await admin.addPackageForm.locator('input[name="credit"]').fill('400');
        await admin.addPackageForm.locator('input[name="expiry_days"]').fill('30');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(page.getByText('เพิ่มแพ็กเกจเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();
        const created = (await state(request)).packages.find((p) => p.label === '[E2E CP] ไม่มีโบนัส');
        const row = admin.row(created.id);
        await expect(row.locator('.bonus-cell')).toHaveText('—');
    });

    test('CREDIT-PACK-18 เพิ่มแพ็กเกจ เครดิตที่ได้มากกว่าราคาที่ซื้อ (มีโบนัส)', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.addPackageForm.locator('input[name="label"]').fill('[E2E CP] มีโบนัส');
        await admin.addPackageForm.locator('input[name="price"]').fill('400');
        await admin.addPackageForm.locator('input[name="credit"]').fill('500');
        await admin.addPackageForm.locator('input[name="expiry_days"]').fill('30');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(page.getByText('เพิ่มแพ็กเกจเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();
        const created = (await state(request)).packages.find((p) => p.label === '[E2E CP] มีโบนัส');
        const row = admin.row(created.id);
        await expect(row.locator('.bonus-cell')).toContainText('+฿100');
    });

    test('CREDIT-PACK-19 เพิ่มแพ็กเกจ เครดิตที่ได้น้อยกว่าราคาที่ซื้อ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.addPackageForm.locator('input[name="label"]').fill('[E2E CP] เครดิตน้อยกว่า');
        await admin.addPackageForm.locator('input[name="price"]').fill('250');
        await admin.addPackageForm.locator('input[name="credit"]').fill('200');
        await admin.addPackageForm.locator('input[name="expiry_days"]').fill('15');
        await admin.addPackageForm.getByRole('button', { name: 'เพิ่ม', exact: true }).click();

        await expect(page.getByText('เพิ่มแพ็กเกจเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();
        const created = (await state(request)).packages.find((p) => p.label === '[E2E CP] เครดิตน้อยกว่า');
        expect(created).toBeTruthy();
        const row = admin.row(created.id);
        await expect(row.locator('.bonus-cell')).toHaveText('—');
    });

    test('CREDIT-PACK-20 แก้ไขค่าราคา/เครดิต/หมดอายุในตาราง แล้วบันทึก', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const target = fixture.packages[0];
        const row = admin.row(target.id);

        await row.locator('input[name="price"]').fill('150');
        await row.locator('input[name="credit"]').fill('180');
        await row.locator('input[name="expiry_days"]').fill('60');
        await admin.saveAllButton.click();

        await expect(page.getByText('บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว').first()).toBeVisible();
        const saved = (await state(request)).packages.find((p) => p.id === target.id);
        expect(saved.price_satang).toBe(15000);
        expect(saved.credit_satang).toBe(18000);
        expect(saved.expiry_days).toBe(60);
    });

    test('CREDIT-PACK-21 แก้ไขแล้วไม่กดบันทึก แล้วรีเฟรชหน้า', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const target = fixture.packages[0];
        const row = admin.row(target.id);

        await row.locator('input[name="price"]').fill('999');
        await expect(admin.saveAllButton).toBeEnabled();

        await page.reload();
        const reloadedRow = admin.row(target.id);
        await expect(reloadedRow.locator('input[name="price"]')).toHaveValue(String(target.price_satang / 100));
        const unchanged = (await state(request)).packages.find((p) => p.id === target.id);
        expect(unchanged.price_satang).toBe(target.price_satang);
    });

    test('CREDIT-PACK-23 ปิดการแสดงผลแพ็กเกจ (toggle "แสดงผล")', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const target = fixture.packages[0];
        const row = admin.row(target.id);

        // The real checkbox is visually hidden (Tailwind `sr-only`) behind a
        // styled <span> that plays the switch — check/uncheck can't click it
        // without `force`, since the span intercepts pointer events.
        await row.locator('input[type="checkbox"][name="is_active"]').uncheck({ force: true });
        await admin.saveAllButton.click();
        await expect(page.getByText('บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว').first()).toBeVisible();

        await admin.logout();
        await admin.login(fixture.customer);
        await page.goto('/credits/topup');
        await expect(admin.packageCardByPrice(target.price_satang / 100)).toHaveCount(0);

        // Must log out of the customer session first — logging in again while
        // still authenticated just bounces off /login (guest-only route).
        await admin.logout();
        await admin.login(fixture.admin);
        await admin.goto();
        await expect(admin.row(target.id)).toBeVisible();
    });

    test('CREDIT-PACK-24 เปิดการแสดงผลแพ็กเกจกลับคืน', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const target = fixture.packages[0];

        // Establish the "hidden" state first so re-enabling it is a real change.
        await admin.row(target.id).locator('input[type="checkbox"][name="is_active"]').uncheck({ force: true });
        await admin.saveAllButton.click();
        await expect(page.getByText('บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว').first()).toBeVisible();

        await admin.row(target.id).locator('input[type="checkbox"][name="is_active"]').check({ force: true });
        await admin.saveAllButton.click();
        await expect(page.getByText('บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว').first()).toBeVisible();

        await admin.logout();
        await admin.login(fixture.customer);
        await page.goto('/credits/topup');
        await expect(admin.packageCardByPrice(target.price_satang / 100)).toBeVisible();
    });

    test('CREDIT-PACK-25 ลบแพ็กเกจออกจากตาราง', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const target = fixture.packages[fixture.packages.length - 1];

        await admin.deletePackage(target.id);

        await expect(page.getByText('ลบแพ็กเกจเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(admin.row(target.id)).toHaveCount(0);
        expect((await state(request)).packages.find((p) => p.id === target.id)).toBeUndefined();
    });
});
