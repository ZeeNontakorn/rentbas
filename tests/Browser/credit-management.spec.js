import { test, expect } from '@playwright/test';
import { PrivateTrainingPage } from './pages/private-train-m.page.js';

async function setup(request) {
    const response = await request.post('/__e2e/credit-management/case');
    if (!response.ok()) throw new Error(`สร้างข้อมูล CREDIT-M ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    return response.json();
}
async function login(page, fixture) { await new PrivateTrainingPage(page).loginFromHome(fixture.admin); }
async function state(request) { const response = await request.get('/__e2e/credit-management/state'); expect(response.ok()).toBeTruthy(); return response.json(); }
const row = (page, id) => page.locator('tbody tr').filter({ has: page.locator(`a[href$="/credit-topups/${id}"]`) });

test.describe.serial('Credit Management CREDIT-M-01 ถึง CREDIT-M-14', () => {
    test('CREDIT-M-01 เข้าสู่หน้าจอคำขอเติมเครดิต', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto('/admin/credit-topups');
        await expect(page.getByRole('heading', { name: 'คำขอเติมเครดิต' })).toBeVisible();
        await expect(row(page, fixture.requests.package)).toContainText('รอตรวจสอบ');
    });

    for (const [id, tabName, status, key] of [
        ['02', 'อนุมัติแล้ว', 'approved', 'approved'], ['03', 'ปฏิเสธแล้ว', 'rejected', 'rejected'],
    ]) test(`CREDIT-M-${id} ตรวจสอบการเปิดแท็บ${tabName}`, async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto('/admin/credit-topups');
        await page.getByRole('link', { name: tabName, exact: true }).click();
        await expect(page).toHaveURL(new RegExp(`status=${status}`));
        await expect(row(page, fixture.requests[key])).toContainText(tabName);
        await expect(row(page, fixture.requests.package)).toHaveCount(0);
    });

    test('CREDIT-M-04 ตรวจสอบการเปิดแท็บทั้งหมด', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto('/admin/credit-topups?status=all');
        const missing = new Set(Object.values(fixture.requests).map(String));
        for (let pageNumber = 1; pageNumber <= 20 && missing.size; pageNumber += 1) {
            await page.goto(`/admin/credit-topups?status=all&page=${pageNumber}`);
            for (const href of await page.locator('tbody a[href*="/credit-topups/"]').evaluateAll(links => links.map(link => link.getAttribute('href')))) {
                missing.delete(href?.split('/').pop());
            }
            if (!await page.locator('a[rel="next"]').count()) break;
        }
        expect([...missing]).toEqual([]);
    });

    test('CREDIT-M-05 ตรวจสอบรายละเอียดคำขอและหลักฐานการชำระเงิน', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/credit-topups/${fixture.requests.package}`);
        await expect(page.locator('main')).toContainText(fixture.customer.name);
        await expect(page.locator('main')).toContainText('฿500.00');
        await expect(page.getByRole('img', { name: 'สลิป' })).toBeVisible();
        await expect(page.getByRole('link').filter({ has: page.getByRole('img', { name: 'สลิป' }) })).toHaveAttribute('target', '_blank');
        await expect(page.getByRole('button', { name: 'อนุมัติและเติมเครดิต' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'ปฏิเสธคำขอ' })).toBeVisible();
    });

    test('CREDIT-M-06 อนุมัติคำขอจากแพ็กเกจที่กำหนดวันหมดอายุ', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/credit-topups/${fixture.requests.package}`);
        await page.getByRole('button', { name: 'อนุมัติและเติมเครดิต' }).click();
        await expect(page).toHaveURL(/\/admin\/credit-topups/); await expect(page.locator('main')).toContainText('อนุมัติคำขอเติมเครดิต');
        const snapshot = await state(request); expect(snapshot.requests.find(item => item.id === fixture.requests.package).status).toBe('approved');
        expect(snapshot.credit_balance).toBe(1550);
    });

    test('CREDIT-M-07 อนุมัติคำขอแบบระบุเครดิตเองพร้อมวันหมดอายุ', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/credit-topups/${fixture.requests.custom}`);
        await page.locator('input[name="expiry_days"]').fill('90'); await page.getByRole('button', { name: 'อนุมัติและเติมเครดิต' }).click();
        await expect(page).toHaveURL(/\/admin\/credit-topups/);
        const snapshot = await state(request); expect(snapshot.requests.find(item => item.id === fixture.requests.custom).status).toBe('approved');
        expect(snapshot.credit_balance).toBe(1700);
    });

    test('CREDIT-M-08 ปฏิเสธคำขอเติมเครดิต', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/credit-topups/${fixture.requests.custom}`);
        await page.getByRole('button', { name: 'ปฏิเสธคำขอ' }).click(); await page.locator('#rejectTopupReason').fill('ยอดเงินไม่ตรงกับสลิป');
        await page.getByRole('button', { name: 'ปฏิเสธและส่งอีเมลแจ้งลูกค้า' }).click();
        await expect(page).toHaveURL(/\/admin\/credit-topups/);
        const snapshot = await state(request); expect(snapshot.requests.find(item => item.id === fixture.requests.custom)).toMatchObject({ status: 'rejected', rejected_reason: 'ยอดเงินไม่ตรงกับสลิป' });
    });

    test('CREDIT-M-09 ไม่กรอกวันหมดอายุสำหรับคำขอระบุเครดิตเอง', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/credit-topups/${fixture.requests.custom}`);
        const expiry = page.locator('input[name="expiry_days"]'); await page.getByRole('button', { name: 'อนุมัติและเติมเครดิต' }).click();
        await expect(expiry).toBeFocused(); expect(await expiry.evaluate(element => element.validity.valueMissing)).toBeTruthy();
        expect((await state(request)).requests.find(item => item.id === fixture.requests.custom).status).toBe('pending');
    });

    test('CREDIT-M-10 เข้าสู่หน้าจัดการเครดิตผ่านข้อมูลผู้ใช้', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/users/${fixture.customer.id}`);
        await page.getByRole('link', { name: /จัดการเครดิต/ }).click(); await expect(page).toHaveURL(new RegExp(`/admin/users/${fixture.customer.id}/credit`));
        await expect(page.getByText('ประวัติธุรกรรมเครดิต')).toBeVisible();
    });

    test('CREDIT-M-11 เติมเครดิตให้ผู้ใช้', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/users/${fixture.customer.id}/credit`);
        const form = page.locator('form[action$="/credit/topup"]'); await form.locator('[name="amount"]').fill('500');
        await form.locator('[name="payment_method"]').selectOption('cash_counter'); await form.locator('[name="expiry_days"]').fill('365');
        await form.locator('[name="note"]').fill('เติมเงินสดหน้าเคาน์เตอร์'); await form.getByRole('button', { name: 'เติมเครดิต' }).click();
        await expect(page.locator('main')).toContainText('จำนวน 500 บาท สำเร็จ'); expect((await state(request)).credit_balance).toBe(1500);
    });

    test('CREDIT-M-12 ไม่กรอกข้อมูลแล้วกดเติมเครดิต', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/users/${fixture.customer.id}/credit`);
        const form = page.locator('form[action$="/credit/topup"]'); await form.getByRole('button', { name: 'เติมเครดิต' }).click();
        expect(await form.locator('[name="amount"]').evaluate(element => element.validity.valueMissing)).toBeTruthy(); expect((await state(request)).credit_balance).toBe(1000);
    });

    test('CREDIT-M-13 หักเครดิตจากผู้ใช้', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/users/${fixture.customer.id}/credit`);
        const form = page.locator('#deductCreditForm'); await form.locator('[name="deduct_amount"]').fill('200'); await form.locator('[name="deduct_note"]').fill('แก้ไขยอด');
        page.once('dialog', dialog => dialog.accept()); await form.getByRole('button', { name: 'หักเครดิต' }).click();
        await expect(page.locator('main')).toContainText('จำนวน 200 บาท สำเร็จ'); expect((await state(request)).credit_balance).toBe(800);
    });

    test('CREDIT-M-14 ไม่กรอกจำนวนเงินแล้วกดหักเครดิต', async ({ page, request }) => {
        const fixture = await setup(request); await login(page, fixture); await page.goto(`/admin/users/${fixture.customer.id}/credit`);
        const form = page.locator('#deductCreditForm'); await form.getByRole('button', { name: 'หักเครดิต' }).click();
        expect(await form.locator('[name="deduct_amount"]').evaluate(element => element.validity.valueMissing)).toBeTruthy(); expect((await state(request)).credit_balance).toBe(1000);
    });
});
