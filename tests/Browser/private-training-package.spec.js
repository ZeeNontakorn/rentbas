import { test, expect } from '@playwright/test';
import { PrivateTrainingPage } from './pages/private-train-m.page.js';

const fixtureImage = {
    name: 'private-training-test.jpg',
    mimeType: 'image/jpeg',
    buffer: Buffer.from('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k=', 'base64'),
};

async function setup(request) {
    const response = await request.post('/__e2e/private-training-package/case');
    if (!response.ok()) throw new Error(`สร้างข้อมูล PTP ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    return response.json();
}

async function state(request) {
    const response = await request.get('/__e2e/private-training-package/state');
    expect(response.ok()).toBeTruthy();
    return response.json();
}

async function loginAdmin(page, fixture) {
    const app = new PrivateTrainingPage(page);
    await app.loginFromHome(fixture.admin);
    await page.getByRole('button', { name: 'การสอน', exact: true }).click();
    await page.getByRole('link', { name: 'จัดการแพ็กเกจเทรนเนอร์', exact: true }).click();
    await expect(page).toHaveURL(/\/admin\/packages(?:\?.*)?$/);
}

const packageRow = (page, name) => page.locator('tbody tr').filter({ hasText: name });

async function openCreate(page) {
    await page.getByRole('link', { name: 'เพิ่มแพ็กเกจ', exact: true }).click();
    await expect(page).toHaveURL(/\/admin\/packages\/create$/);
}

async function fillPackage(page, {
    name = '[E2E PTP] Private Training Test',
    description = 'แพ็กเกจสำหรับจองไพรเวทเทรนนิ่ง',
    price = '6900', uses = '4', days = '120', usableDays = ['mon', 'tue', 'wed', 'thu', 'fri'],
    image = false, active = true,
} = {}) {
    await page.locator('#name').fill(name);
    await page.locator('#description').fill(description);
    await page.locator('#price').fill(price);
    await page.locator('#num_of_use').fill(uses);
    await page.locator('#day').fill(days);
    for (const day of usableDays) await page.locator(`input[name="usable_days[]"][value="${day}"]`).check({ force: true });
    const activeInput = page.locator('input[name="is_active"]');
    if (active) await activeInput.check({ force: true }); else await activeInput.uncheck({ force: true });
    if (image) await page.locator('#image').setInputFiles(fixtureImage);
}

async function submitCreate(page) {
    await page.getByRole('button', { name: 'เพิ่มแพ็กเกจ', exact: true }).click();
    await expect(page).toHaveURL(/\/admin\/packages$/);
    await expect(page.locator('.swal2-title')).toContainText('เพิ่มแพ็กเกจสำเร็จ');
}

test.describe.serial('Private Training Package PTP-01 ถึง PTP-13', () => {
    test('PTP-01 ตรวจสอบการเข้าถึงหน้าจัดการแพ็กเกจไพรเวทเทรนนิ่ง', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await expect(page.getByRole('heading', { name: 'จัดการแพ็กเกจ' })).toBeVisible();
        await expect(page.locator('thead')).toContainText('ชื่อแพ็กเกจ');
        await expect(page.locator('thead')).toContainText('วันที่ใช้ได้');
        await expect(packageRow(page, fixture.ptp_packages.active.name)).toContainText('เปิดใช้งาน');
    });

    test('PTP-02 ตรวจสอบการค้นหาแพ็กเกจ', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await page.locator('input[name="search"]').fill(fixture.ptp_packages.inactive.name);
        await page.getByRole('button', { name: 'ค้นหาแพ็กเกจ' }).click();
        await expect(packageRow(page, fixture.ptp_packages.inactive.name)).toHaveCount(1);
        await expect(packageRow(page, fixture.ptp_packages.active.name)).toHaveCount(0);
    });

    test('PTP-03 ตรวจสอบการเพิ่มแพ็กเกจไพรเวทเทรนนิ่ง', async ({ page, request }) => {
        const fixture = await setup(request);
        const name = '[E2E PTP] Private Training Test';
        await loginAdmin(page, fixture);
        await openCreate(page);
        await fillPackage(page, { name, image: true });
        await submitCreate(page);
        const row = packageRow(page, name);
        await expect(row).toContainText('6,900.00');
        await expect(row).toContainText('4 ครั้ง');
        await expect(row).toContainText('120 วัน');
        await expect(row).toContainText('เปิดใช้งาน');
    });

    test('PTP-04 ตรวจสอบการเพิ่มแพ็กเกจโดยกำหนดวันที่สามารถใช้งานได้', async ({ page, request }) => {
        const fixture = await setup(request);
        const name = '[E2E PTP] วันจันทร์ถึงศุกร์';
        await loginAdmin(page, fixture);
        await openCreate(page);
        await fillPackage(page, { name });
        await submitCreate(page);
        const created = (await state(request)).find(item => item.name === name);
        expect(created.usable_days).toEqual(['mon', 'tue', 'wed', 'thu', 'fri']);
        await expect(packageRow(page, name)).toContainText('จ');
        await expect(packageRow(page, name)).toContainText('ศ');
    });

    test('PTP-05 ตรวจสอบการเพิ่มแพ็กเกจโดยไม่เลือกวันที่สามารถใช้งานได้', async ({ page, request }) => {
        const fixture = await setup(request);
        const name = '[E2E PTP] ใช้งานทุกวัน';
        await loginAdmin(page, fixture);
        await openCreate(page);
        await fillPackage(page, { name, usableDays: [] });
        await submitCreate(page);
        await expect(packageRow(page, name)).toContainText('ทุกวัน');
        expect((await state(request)).find(item => item.name === name).usable_days).toEqual([]);
    });

    test('PTP-06 ตรวจสอบการเพิ่มแพ็กเกจโดยไม่กรอกข้อมูลที่จำเป็น', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await openCreate(page);
        await page.locator('#name').fill('');
        await page.locator('#price').fill('');
        await page.locator('#num_of_use').fill('');
        await page.locator('#type').evaluate(select => { select.value = ''; });
        await page.getByRole('button', { name: 'เพิ่มแพ็กเกจ', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/packages\/create$/);
        for (const selector of ['#name', '#price', '#num_of_use', '#type']) {
            expect(await page.locator(selector).evaluate(element => element.validity.valueMissing)).toBe(true);
        }
    });

    test('PTP-07 ตรวจสอบการอัปโหลดรูปภาพแพ็กเกจ', async ({ page, request }) => {
        const fixture = await setup(request);
        const name = '[E2E PTP] Package With Image';
        await loginAdmin(page, fixture);
        await openCreate(page);
        await fillPackage(page, { name, image: true });
        await expect(page.locator('#imageName')).toHaveText(fixtureImage.name);
        await expect(page.locator('#img-preview')).toHaveAttribute('src', /^blob:/);
        await submitCreate(page);
        await expect(packageRow(page, name).getByRole('img', { name })).toBeVisible();
        expect((await state(request)).find(item => item.name === name).image).toMatch(/^packages\//);
    });

    test('PTP-08 ตรวจสอบการแก้ไขข้อมูลแพ็กเกจ', async ({ page, request }) => {
        const fixture = await setup(request);
        const updatedName = '[E2E PTP] Private Training Update';
        await loginAdmin(page, fixture);
        await packageRow(page, fixture.ptp_packages.active.name).getByRole('link', { name: 'แก้ไข' }).click();
        await page.locator('#name').fill(updatedName);
        await page.locator('#price').fill('7500');
        await page.locator('#num_of_use').fill('5');
        await page.getByRole('button', { name: 'บันทึกการแก้ไข' }).click();
        await expect(page.locator('.swal2-title')).toContainText('แก้ไขแพ็กเกจสำเร็จ');
        await expect(packageRow(page, updatedName)).toContainText('7,500.00');
        await expect(packageRow(page, updatedName)).toContainText('5 ครั้ง');
    });

    test('PTP-09 ตรวจสอบการเปิด/ปิดใช้งานแพ็กเกจ', async ({ page, request, browser }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await packageRow(page, fixture.ptp_packages.active.name).getByRole('link', { name: 'แก้ไข' }).click();
        await page.locator('input[name="is_active"]').uncheck({ force: true });
        await page.getByRole('button', { name: 'บันทึกการแก้ไข' }).click();
        await expect(packageRow(page, fixture.ptp_packages.active.name)).toContainText('ปิดใช้งาน');
        expect((await state(request)).find(item => item.id === fixture.ptp_packages.active.id).is_active).toBe(false);

        const userContext = await browser.newContext();
        const userPage = await userContext.newPage();
        const app = new PrivateTrainingPage(userPage);
        await app.loginAndOpen(fixture.user);
        await app.openPackagesFromEmptyState();
        await expect(app.packageCard(fixture.ptp_packages.active.name)).toHaveCount(0);
        await userContext.close();
    });

    test('PTP-10 ตรวจสอบการลบแพ็กเกจ', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        const row = packageRow(page, fixture.ptp_packages.active.name);
        await row.getByRole('button', { name: 'ลบ' }).click();
        await page.getByRole('button', { name: 'ยืนยันการลบ' }).click();
        await expect(page.locator('.swal2-title')).toContainText('ลบแพ็กเกจสำเร็จ');
        await expect(packageRow(page, fixture.ptp_packages.active.name)).toHaveCount(0);
        expect((await state(request)).some(item => item.id === fixture.ptp_packages.active.id)).toBe(false);
    });

    test('PTP-11 ตรวจสอบการยกเลิกการลบแพ็กเกจ', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAdmin(page, fixture);
        await packageRow(page, fixture.ptp_packages.active.name).getByRole('button', { name: 'ลบ' }).click();
        await page.getByRole('button', { name: 'ยกเลิก' }).click();
        await expect(packageRow(page, fixture.ptp_packages.active.name)).toHaveCount(1);
        expect((await state(request)).some(item => item.id === fixture.ptp_packages.active.id)).toBe(true);
    });

    test('PTP-12 ตรวจสอบการแสดงแพ็กเกจที่เปิดใช้งานบนหน้าเว็บไซต์', async ({ page, request }) => {
        const fixture = await setup(request);
        const app = new PrivateTrainingPage(page);
        await app.loginAndOpen(fixture.user);
        await app.openPackagesFromEmptyState();
        await expect(app.packageCard(fixture.ptp_packages.active.name)).toBeVisible();
        await expect(app.packageCard(fixture.ptp_packages.active.name).getByRole('button', { name: 'เลือกแพ็กเกจนี้' })).toBeEnabled();
    });

    test('PTP-13 ตรวจสอบแพ็กเกจที่ปิดใช้งานไม่แสดงบนหน้าเว็บไซต์', async ({ page, request }) => {
        const fixture = await setup(request);
        const app = new PrivateTrainingPage(page);
        await app.loginAndOpen(fixture.user);
        await app.openPackagesFromEmptyState();
        await expect(app.packageCard(fixture.ptp_packages.inactive.name)).toHaveCount(0);
        await expect(app.packageCard(fixture.ptp_packages.active.name)).toBeVisible();
    });
});
