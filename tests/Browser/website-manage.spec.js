import { test, expect } from '@playwright/test';
import { WebsiteManagePage } from './pages/website-manage.page.js';

const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
const JPG_BASE64 = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k=';
const WEBP_BASE64 = 'UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==';

const pngImage = (name = 'facility.png') => ({ name, mimeType: 'image/png', buffer: Buffer.from(PNG_BASE64, 'base64') });
const jpgImage = (name = 'facility.jpg') => ({ name, mimeType: 'image/jpeg', buffer: Buffer.from(JPG_BASE64, 'base64') });
const webpImage = (name = 'facility.webp') => ({ name, mimeType: 'image/webp', buffer: Buffer.from(WEBP_BASE64, 'base64') });
const oversizedImage = () => {
    const base = Buffer.from(PNG_BASE64, 'base64');
    return { name: 'oversized.png', mimeType: 'image/png', buffer: Buffer.concat([base, Buffer.alloc(5_300_000 - base.length)]) };
};
const pdfFile = () => ({ name: 'document.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4\n%%EOF') });

async function setup(request) {
    const response = await request.post('/__e2e/website-manage/case');
    if (!response.ok()) throw new Error(`สร้างข้อมูล WEB-M ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    return response.json();
}

async function state(request, facilityName) {
    const url = facilityName
        ? `/__e2e/website-manage/state?facility_name=${encodeURIComponent(facilityName)}`
        : '/__e2e/website-manage/state';
    const response = await request.get(url);
    expect(response.ok()).toBeTruthy();
    return response.json();
}

async function openAdmin(page, request) {
    const fixture = await setup(request);
    const admin = new WebsiteManagePage(page);
    await admin.login(fixture.admin);
    await admin.goto();
    return { admin, fixture };
}

test.describe.serial('จัดการเว็บไซต์ - สิ่งอำนวยความสะดวก และรีวิว WEB-M-14 ถึง 36', () => {
    test('WEB-M-14 ตรวจสอบการเพิ่มการ์ดใหม่โดยกรอกข้อมูลครบทุกช่องและเลือกรูปถูกต้อง', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] เพิ่มการ์ดครบถ้วน';
        await admin.fillCreateForm({ name, description: 'รายละเอียดครบถ้วนสำหรับทดสอบ', sortOrder: 10, image: pngImage() });
        await admin.submitCreateForm();

        await expect(page).toHaveURL(/\/admin\/edit-text#facility-management$/);
        await expect(page.getByText('เพิ่มสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(admin.facilityNameInput(name)).toBeVisible();
        expect((await state(request, name)).facilities).toHaveLength(1);
    });

    test('WEB-M-15 เพิ่มการ์ดโดยกรอกเฉพาะข้อมูลที่จำเป็น', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] ร้านกาแฟ';
        await admin.fillCreateForm({ name, image: pngImage() });
        await admin.submitCreateForm();

        await expect(page.getByText('เพิ่มสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(admin.facilityNameInput(name)).toBeVisible();
        expect((await state(request, name)).facilities).toHaveLength(1);
    });

    test('WEB-M-16 เพิ่มหลายรายการต่อเนื่องไม่มีข้อมูลหาย', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const names = ['[E2E WM] ที่จอดรถ', '[E2E WM] ห้องน้ำ', '[E2E WM] ร้านอุปกรณ์กีฬา'];

        for (const name of names) {
            await admin.fillCreateForm({ name, description: 'รองรับที่จอดรถมากกว่า 50 คัน', sortOrder: 3, image: pngImage() });
            await admin.submitCreateForm();
            await expect(page.getByText('เพิ่มสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();

            for (const previousName of names) {
                if (previousName === name) break;
                await expect(admin.facilityNameInput(previousName), `${previousName} ต้องยังอยู่หลังเพิ่ม ${name}`).toBeVisible();
            }
        }
    });

    test('WEB-M-17 กดปุ่มเพิ่มการ์ดหลายครั้งติดกันระบบไม่สร้างข้อมูลซ้ำ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] กันข้อมูลซ้ำ';
        await admin.fillCreateForm({ name, description: 'ทดสอบกดซ้ำ', sortOrder: 4, image: pngImage() });

        let posts = 0;
        page.on('request', (req) => {
            if (req.method() === 'POST' && new URL(req.url()).pathname === '/admin/website/facilities') posts += 1;
        });
        await admin.createForm.evaluate((form) => {
            const button = form.querySelector('button[type="submit"]');
            button.click();
            button.click();
        });
        await expect(page).toHaveURL(/\/admin\/edit-text#facility-management$/);

        expect((await state(request, name)).facilities).toHaveLength(1);
        expect(posts).toBeLessThanOrEqual(2);
    });

    test('WEB-M-18 รีเฟรชหน้าหลังเพิ่มสำเร็จข้อมูลยังอยู่ในระบบ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] ทดสอบรีเฟรช';
        await admin.fillCreateForm({ name, description: 'ทดสอบข้อมูลหลังรีเฟรช', sortOrder: 5, image: pngImage() });
        await admin.submitCreateForm();
        await expect(admin.facilityNameInput(name)).toBeVisible();

        await page.reload();
        await expect(admin.facilityNameInput(name)).toBeVisible();
    });

    test('WEB-M-19 ตรวจสอบการไม่กรอกชื่อหัวข้อ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        await admin.bypassHtml5Validation(admin.createForm);
        await admin.fillCreateForm({ description: 'ทดสอบการไม่กรอกชื่อหัวข้อ', sortOrder: 5, image: pngImage() });
        await admin.submitCreateForm();

        await expect(admin.createForm.getByText('กรุณากรอกชื่อหัวข้อ')).toBeVisible();
        expect((await state(request)).facilities.filter((f) => f.name === '')).toHaveLength(0);
    });

    test('WEB-M-20 ตรวจสอบการไม่กรอกลำดับระบบเพิ่มลำดับต่อท้ายอัตโนมัติ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const before = await state(request);
        const name = '[E2E WM] ไม่กรอกลำดับ';

        await admin.fillCreateForm({ name, description: 'ทดสอบไม่กรอกลำดับ', image: pngImage() });
        await admin.createForm.locator('input[name="sort_order"]').fill('');
        await admin.submitCreateForm();

        await expect(page.getByText('เพิ่มสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
        const after = await state(request, name);
        expect(after.facilities).toHaveLength(1);
        expect(after.facilities[0].sort_order).toBe(before.max_sort_order + 1);
    });

    test('WEB-M-21 ตรวจสอบการกรอกลำดับเป็น 0', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] ลำดับศูนย์';
        await admin.fillCreateForm({ name, description: 'ทดสอบลำดับ 0', sortOrder: 0, image: pngImage() });
        await admin.submitCreateForm();

        await expect(page.getByText('เพิ่มสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
        const after = await state(request, name);
        expect(after.facilities).toHaveLength(1);
        expect(after.facilities[0].sort_order).toBe(0);
    });

    test('WEB-M-22 กรอกลำดับเป็นค่าติดลบหรือตัวอักษรไม่สามารถบันทึกได้', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] ลำดับติดลบ';
        await admin.fillCreateForm({ name, description: 'ทดสอบลำดับติดลบ', image: pngImage() });

        const sortOrderInput = admin.createForm.locator('input[name="sort_order"]');
        // type=number structurally rejects non-numeric characters — clear the
        // field then simulate real typing (rather than .fill, which errors on
        // invalid input for this type) and confirm no characters get accepted.
        await sortOrderInput.fill('');
        await sortOrderInput.pressSequentially('abc');
        await expect(sortOrderInput).toHaveValue('');

        await sortOrderInput.fill('-10');
        await admin.submitCreateForm();
        await expect(sortOrderInput).toHaveJSProperty('validity.valid', false);
        expect((await state(request, name)).facilities).toHaveLength(0);
    });

    test('WEB-M-23 ไม่เลือกรูปภาพระบบไม่บันทึกข้อมูล', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] ไม่เลือกรูป';
        await admin.bypassHtml5Validation(admin.createForm);
        await admin.fillCreateForm({ name, description: 'ทดสอบไม่เลือกรูปภาพ', sortOrder: 6 });
        await admin.submitCreateForm();

        await expect(admin.createForm.getByText('กรุณาเลือกรูปภาพ')).toBeVisible();
        expect((await state(request, name)).facilities).toHaveLength(0);
    });

    test('WEB-M-24 อัปโหลดไฟล์ PDF ระบบไม่อนุญาตให้อัปโหลด', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] อัปโหลด PDF';
        await admin.bypassHtml5Validation(admin.createForm);
        await admin.fillCreateForm({ name, description: 'ทดสอบอัปโหลด PDF', sortOrder: 7, image: pdfFile() });
        await admin.submitCreateForm();

        await expect(admin.createForm.getByText(/ต้องเป็นรูปภาพ|รองรับเฉพาะไฟล์ JPG/)).toBeVisible();
        expect((await state(request, name)).facilities).toHaveLength(0);
    });

    test('WEB-M-25 อัปโหลดไฟล์เกิน 5 MB ระบบไม่อนุญาตให้อัปโหลด', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const name = '[E2E WM] อัปโหลดไฟล์ใหญ่';
        await admin.fillCreateForm({ name, description: 'ทดสอบไฟล์เกิน 5MB', sortOrder: 8, image: oversizedImage() });
        await admin.submitCreateForm();

        // PHP's own upload_max_filesize (2M on this server) is below Laravel's
        // 5MB business rule, so a >5MB file is rejected by PHP itself first —
        // it never reaches the app's custom "รูปภาพต้องมีขนาดไม่เกิน 5 MB" message.
        await expect(admin.createForm.getByText(/รูปภาพต้องมีขนาดไม่เกิน 5 MB|failed to upload/i)).toBeVisible();
        expect((await state(request, name)).facilities).toHaveLength(0);
    });

    test('WEB-M-26 อัปโหลดรูป JPG, PNG และ WebP สามารถอัปโหลดได้สำเร็จ', async ({ page, request }) => {
        const { admin } = await openAdmin(page, request);
        const cases = [
            { name: '[E2E WM] รูป JPG', image: jpgImage() },
            { name: '[E2E WM] รูป PNG', image: pngImage() },
            { name: '[E2E WM] รูป WebP', image: webpImage() },
        ];

        for (const { name, image } of cases) {
            await admin.fillCreateForm({ name, description: 'ทดสอบชนิดไฟล์รูปภาพ', sortOrder: 9, image });
            await admin.submitCreateForm();
            await expect(page.getByText('เพิ่มสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
            expect((await state(request, name)).facilities, `${name} ควรถูกบันทึก`).toHaveLength(1);
        }
    });

    test('WEB-M-27 ทดสอบการแก้ไขการ์ด', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const facility = fixture.facilities[0];
        const card = admin.facilityCard(facility.id);

        await card.locator('input[name="name"]').fill('[E2E WM] แก้ไขแล้ว');
        await card.locator('textarea[name="description"]').fill('รายละเอียดหลังแก้ไข');
        await card.locator('input[name="sort_order"]').fill('1');
        await page.locator(`#facility_img_${facility.id}_file`).setInputFiles(jpgImage());
        await card.getByRole('button', { name: 'บันทึกการ์ด', exact: true }).click();

        await expect(page.getByText('อัปเดตสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(admin.facilityNameInput('[E2E WM] แก้ไขแล้ว')).toBeVisible();

        await page.goto('/');
        await expect(page.locator('.facility-card-title', { hasText: '[E2E WM] แก้ไขแล้ว' })).toBeVisible();
    });

    test('WEB-M-28 แก้ไขการเรียงลำดับการ์ด', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const [first, second, third] = fixture.facilities;
        const swaps = [[first, 3], [second, 1], [third, 2]];

        for (const [facility, newOrder] of swaps) {
            const card = admin.facilityCard(facility.id);
            await card.locator('input[name="sort_order"]').fill(String(newOrder));
            await card.getByRole('button', { name: 'บันทึกการ์ด', exact: true }).click();
            await expect(page.getByText('อัปเดตสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
        }

        const after = await state(request);
        const bySortOrder = after.facilities.sort((a, b) => a.sort_order - b.sort_order).map((f) => f.id);
        expect(bySortOrder).toEqual([second.id, third.id, first.id]);
    });

    test('WEB-M-29 ทดสอบการซ่อนและแสดงการ์ดอีกครั้ง', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const facility = fixture.facilities[0];
        const card = admin.facilityCard(facility.id);

        await card.locator('input[name="is_active"]').uncheck();
        await card.getByRole('button', { name: 'บันทึกการ์ด', exact: true }).click();
        await expect(page.getByText('อัปเดตสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(admin.facilityCard(facility.id)).toBeVisible();

        await page.goto('/');
        await expect(page.locator('.facility-card-title', { hasText: facility.name })).toHaveCount(0);

        await admin.goto();
        await admin.facilityCard(facility.id).locator('input[name="is_active"]').check();
        await admin.facilityCard(facility.id).getByRole('button', { name: 'บันทึกการ์ด', exact: true }).click();
        await expect(page.getByText('อัปเดตสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();

        await page.goto('/');
        await expect(page.locator('.facility-card-title', { hasText: facility.name })).toBeVisible();
    });

    test('WEB-M-30 ทดสอบการลบการ์ด', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const facility = fixture.facilities[fixture.facilities.length - 1];

        await admin.deleteViaConfirm(admin.facilityCard(facility.id).getByRole('button', { name: 'ลบการ์ด', exact: true }), { confirmText: 'ลบการ์ด' });

        await expect(page.getByText('ลบสิ่งอำนวยความสะดวกเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(admin.facilityCard(facility.id)).toHaveCount(0);

        await page.goto('/');
        await expect(page.locator('.facility-card-title', { hasText: facility.name })).toHaveCount(0);
    });

    test('WEB-M-31 ตรวจสอบการแสดงรายการรีวิวในหน้าจัดการเว็บไซต์ของผู้ดูแลระบบ', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const published = fixture.reviews.find((r) => r.status === 'published');
        const card = admin.reviewCard(published.comment);

        await expect(card).toContainText(fixture.reviewer.name);
        await expect(card).toContainText(fixture.reviewer.email);
        await expect(card).toContainText(published.comment);
        await expect(card).toContainText('เผยแพร่แล้ว');
        await expect(card.locator('.text-amber-400')).toBeVisible();
    });

    test('WEB-M-32 ซ่อนรีวิวที่เผยแพร่แล้ว', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const published = fixture.reviews.find((r) => r.status === 'published');
        const card = admin.reviewCard(published.comment);

        await card.getByRole('button', { name: 'ซ่อนรีวิว', exact: true }).click();
        await expect(page.getByText('ซ่อนรีวิวจากหน้าเว็บไซต์แล้ว').first()).toBeVisible();
        await expect(admin.reviewCard(published.comment)).toContainText('ซ่อนอยู่');

        await page.goto('/');
        await expect(page.locator('.review-comment', { hasText: published.comment })).toHaveCount(0);
    });

    test('WEB-M-33 เผยแพร่รีวิวที่ถูกซ่อนอีกครั้ง', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const hidden = fixture.reviews.find((r) => r.status === 'hidden');
        const card = admin.reviewCard(hidden.comment);

        await card.getByRole('button', { name: 'เผยแพร่', exact: true }).click();
        await expect(page.getByText('เผยแพร่รีวิวบนหน้าเว็บไซต์แล้ว').first()).toBeVisible();
        await expect(admin.reviewCard(hidden.comment)).toContainText('เผยแพร่แล้ว');

        await page.goto('/');
        await expect(page.locator('.review-comment', { hasText: hidden.comment })).toBeVisible();
    });

    test('WEB-M-34 ลบรีวิว', async ({ page, request }) => {
        const { admin, fixture } = await openAdmin(page, request);
        const pending = fixture.reviews.find((r) => r.status === 'pending');

        await admin.deleteViaConfirm(admin.reviewCard(pending.comment).getByRole('button', { name: 'ลบรีวิว', exact: true }), { confirmText: 'ลบรีวิว' });

        await expect(page.getByText('ลบรีวิวเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(admin.reviewCard(pending.comment)).toHaveCount(0);

        await page.goto('/');
        await expect(page.locator('.review-comment', { hasText: pending.comment })).toHaveCount(0);
    });

    test('WEB-M-35 ตรวจสอบข้อมูลรีวิวที่แสดงบนหน้าเว็บไซต์', async ({ page, request }) => {
        const fixture = await setup(request);
        const published = fixture.reviews.find((r) => r.status === 'published');

        await page.goto('/');
        const card = page.locator('.review-card').filter({ hasText: published.comment });
        await expect(card).toContainText(fixture.reviewer.name);
        await expect(card).toContainText(published.comment);
        await expect(card).toContainText(fixture.facilities[0].name);
        await expect(card.locator('.review-stars')).toBeVisible();
    });

    test('WEB-M-36 ตรวจสอบผลคะแนนรีวิวของสิ่งอำนวยความสะดวก', async ({ page, request }) => {
        const fixture = await setup(request);
        const targetFacility = fixture.facilities[0];

        await page.goto('/');
        const facilityCard = page.locator('.facility-card').filter({ has: page.locator('.facility-card-title', { hasText: targetFacility.name }) });
        // Only the published review (rating 5) counts toward the average — pending/hidden reviews are excluded.
        await expect(facilityCard.locator('.facility-score strong')).toHaveText('5.0');
        await expect(facilityCard.locator('.facility-score small')).toHaveText('1 รีวิว');
    });
});
