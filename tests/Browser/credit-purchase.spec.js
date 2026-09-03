import { test, expect } from '@playwright/test';
import { CreditPurchasePage, baht } from './pages/credit-purchase.page.js';

const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
const slipImage = (name = 'slip.png') => ({ name, mimeType: 'image/png', buffer: Buffer.from(PNG_BASE64, 'base64') });
const pdfFile = () => ({ name: 'slip.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4\n%%EOF') });

async function setup(request) {
    const response = await request.post('/__e2e/credit-purchase/case');
    if (!response.ok()) throw new Error(`สร้างข้อมูล CREDIT-PUR ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    return response.json();
}

async function state(request) {
    const response = await request.get('/__e2e/credit-purchase/state');
    expect(response.ok()).toBeTruthy();
    return response.json();
}

async function openCustomer(page, request) {
    const fixture = await setup(request);
    const app = new CreditPurchasePage(page);
    await app.login(fixture.customer);
    await app.goto();
    return { app, fixture };
}

// fixture.packages is always created in this order: ฿250, ฿500, ฿800, ฿1600.
const pkg = (fixture, baht_) => fixture.packages.find((p) => p.price_satang === baht_ * 100);

test.describe.serial('การซื้อ/เติมเครดิต CREDIT-PUR-01 ถึง 30', () => {
    test('CREDIT-PUR-01 เข้าหน้าจอการซื้อเครดิต', async ({ page, request }) => {
        const fixture = await setup(request);
        const app = new CreditPurchasePage(page);
        await app.login(fixture.customer);
        await page.goto('/');
        await app.navCreditButton.click();

        await expect(page).toHaveURL(/\/credits\/topup$/);
        await expect(page.getByRole('heading', { name: 'เติมเครดิต', exact: true })).toBeVisible();
        await expect(page.getByText('ยอดคงเหลือ')).toBeVisible();
        await expect(app.packageCard(pkg(fixture, 250).id)).toBeVisible();
        await expect(app.customAmountInput).toBeVisible();
        await expect(app.nextButton).toBeVisible();
    });

    test('CREDIT-PUR-02 ตรวจสอบยอดเครดิตปัจจุบัน', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await expect(page.getByText(`฿${baht(1000)}`)).toBeVisible();
        await expect(app.creditBalanceLocator(1000)).toBeVisible();
    });

    for (const amount of [250, 500, 800, 1600]) {
        const id = { 250: '03', 500: '04', 800: '05', 1600: '06' }[amount];
        test(`CREDIT-PUR-${id} เติมเครดิตโดยเลือกแพ็กเกจ ฿${amount}`, async ({ page, request }) => {
            const { app, fixture } = await openCustomer(page, request);
            await app.selectPackage(pkg(fixture, amount).id);
            await app.nextButton.click();

            await expect(page).toHaveURL(/\/credits\/topup\/checkout/);
            await expect(page.getByText('ยอดชำระ')).toContainText(`฿${baht(amount)}`);
            await expect(page.locator('#qrCanvas')).toBeVisible();
        });
    }

    test('CREDIT-PUR-07 ระบุจำนวนเงินเอง', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(350);
        await app.nextButton.click();

        await expect(page).toHaveURL(/\/credits\/topup\/checkout/);
        await expect(page.getByText('ยอดชำระ')).toContainText(`฿${baht(350)}`);
    });

    test('CREDIT-PUR-08 เติมเครดิตโดยระบุจำนวนเงินขั้นต่ำ', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(20);
        await app.nextButton.click();

        await expect(page).toHaveURL(/\/credits\/topup\/checkout/);
        await expect(page.getByText('ยอดชำระ')).toContainText(`฿${baht(20)}`);
    });

    test('CREDIT-PUR-09 เติมเครดิตมากกว่าขั้นต่ำ', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(500);
        await app.nextButton.click();

        await expect(page).toHaveURL(/\/credits\/topup\/checkout/);
        await expect(page.getByText('ยอดชำระ')).toContainText(`฿${baht(500)}`);
    });

    test('CREDIT-PUR-10 เติมเครดิตน้อยกว่าขั้นต่ำ', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(10);
        await app.nextButton.click();

        // min="20" on the input blocks native submission before it ever reaches the server.
        await expect(app.customAmountInput).toHaveJSProperty('validity.valid', false);
        await expect(page).toHaveURL(/\/credits\/topup$/);
    });

    test('CREDIT-PUR-11 เติมเครดิตจำนวนมากสุด (เกิน 100,000)', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(1000000);
        await app.nextButton.click();

        // max="100000" on the input blocks native submission before it ever reaches the server.
        await expect(app.customAmountInput).toHaveJSProperty('validity.valid', false);
        await expect(page).toHaveURL(/\/credits\/topup$/);
    });

    test('CREDIT-PUR-12 กรอกจำนวนเงินเป็นทศนิยม', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount('20.5');
        await app.nextButton.click();

        // step="1" rejects a fractional value before it ever reaches the server.
        await expect(app.customAmountInput).toHaveJSProperty('validity.valid', false);
        await expect(page).toHaveURL(/\/credits\/topup$/);
    });

    test('CREDIT-PUR-13 กรอกจำนวนเงินเป็นเลขศูนย์', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(0);
        await app.nextButton.click();

        await expect(app.customAmountInput).toHaveJSProperty('validity.valid', false);
        await expect(page).toHaveURL(/\/credits\/topup$/);
    });

    test('CREDIT-PUR-14 กรอกจำนวนเงินติดลบ', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(-50);
        await app.nextButton.click();

        await expect(app.customAmountInput).toHaveJSProperty('validity.valid', false);
        await expect(page).toHaveURL(/\/credits\/topup$/);
    });

    test('CREDIT-PUR-15 กรอกจำนวนเงินเป็นตัวอักษร/อักขระพิเศษ', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        // type=number strips non-numeric input entirely; focusing the field also
        // unchecks every package radio (onfocus handler), so the field stays empty
        // AND no package is selected — submitting hits the server's own
        // "choose a package or an amount" guard.
        await app.customAmountInput.click();
        await app.customAmountInput.pressSequentially('abc!@#');
        await expect(app.customAmountInput).toHaveValue('');
        await app.nextButton.click();

        await expect(page).toHaveURL(/\/credits\/topup$/);
        // The layout also fires a SweetAlert2 toast with the same text as the
        // inline error box, so two elements legitimately match — .first() is
        // enough to confirm the message is showing.
        await expect(page.getByText('กรุณาเลือกแพ็กเกจ หรือระบุจำนวนเงินที่ต้องการเติม').first()).toBeVisible();
    });

    test('CREDIT-PUR-16 เว้นว่างจำนวนเงิน', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        // Focusing (without typing) the amount field also unchecks the default
        // package radio, so submitting with it left blank has neither a package
        // nor an amount selected.
        await app.customAmountInput.click();
        await app.customAmountInput.blur();
        await app.nextButton.click();

        await expect(page).toHaveURL(/\/credits\/topup$/);
        // The layout also fires a SweetAlert2 toast with the same text as the
        // inline error box, so two elements legitimately match — .first() is
        // enough to confirm the message is showing.
        await expect(page.getByText('กรุณาเลือกแพ็กเกจ หรือระบุจำนวนเงินที่ต้องการเติม').first()).toBeVisible();
    });

    test('CREDIT-PUR-17 กดปุ่มส่งคำขอเติมเครดิตหลายครั้งติดกัน', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.selectPackage(pkg(fixture, 250).id);
        await app.nextButton.click();
        await expect(page).toHaveURL(/\/credits\/topup\/checkout/);
        await app.slipInput.setInputFiles(slipImage());

        let posts = 0;
        page.on('request', (req) => { if (req.method() === 'POST' && new URL(req.url()).pathname === '/credits/topup') posts += 1; });
        await page.evaluate(() => {
            const button = document.getElementById('submitBtn');
            button.click();
            button.click();
        });
        await expect(page).toHaveURL(/\/credits\/topup$/);

        expect(posts).toBe(1);
        expect((await state(request)).requests.filter((r) => r.price_satang === 25000)).toHaveLength(1);
    });

    test('CREDIT-PUR-18 รีเฟรชหน้าหลังเลือกแพ็กเกจ', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        const chosen = pkg(fixture, 500);
        await app.selectPackage(chosen.id);
        await expect(app.packageCard(chosen.id).locator('input[type="radio"]')).toBeChecked();

        await page.reload();
        // The selection is only client-side state — a reload reverts to whatever
        // the server renders as the default (the first package in the list),
        // which is not necessarily this one.
        await expect(app.packageCard(chosen.id).locator('input[type="radio"]')).not.toBeChecked();
    });

    test('CREDIT-PUR-19 รีเฟรชหน้าหลังกรอกจำนวนเงิน', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(777);
        await page.reload();

        await expect(app.customAmountInput).toHaveValue('');
        expect((await state(request)).requests).toHaveLength(1); // only the fixture's pre-seeded pending request
        expect((await state(request)).credit_balance).toBe(1000);
    });

    test('CREDIT-PUR-20 ใช้ปุ่ม Back ของ Browser หลังไปหน้าชำระเงิน', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await app.fillCustomAmount(300);
        await app.nextButton.click();
        await expect(page).toHaveURL(/\/credits\/topup\/checkout/);

        await page.goBack();
        await expect(page).toHaveURL(/\/credits\/topup$/);
        expect((await state(request)).requests).toHaveLength(1);
    });

    test('CREDIT-PUR-21 ตรวจสอบการแสดง QR PromptPay', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.fillCustomAmount(300);
        await app.nextButton.click();

        await expect(page.locator('#qrCanvas')).toBeVisible();
        await expect(page.getByText('พร้อมเพย์:')).toContainText(fixture.promptpay_number);
        await expect(page.getByText('ชื่อบัญชี:')).toContainText(fixture.promptpay_name);
    });

    test('CREDIT-PUR-22 ตรวจสอบยอดหน้าเว็บเทียบกับยอดที่จะได้รับ', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.selectPackage(pkg(fixture, 800).id);
        await app.nextButton.click();

        await expect(page.locator('main')).toContainText(`ยอดชำระ ฿${baht(800)}`);
        await expect(page.locator('main')).toContainText(`รับเครดิต ฿${baht(800)}`);
    });

    test('CREDIT-PUR-23 ไม่อัปโหลดสลิป', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.selectPackage(pkg(fixture, 250).id);
        await app.nextButton.click();
        await app.submitButton.click();

        await expect(page.getByText('กรุณาแนบสลิปการโอนเงินก่อนส่งคำขอ').first()).toBeVisible();
        expect((await state(request)).requests).toHaveLength(1);
    });

    test('CREDIT-PUR-24 อัปโหลด PDF แทนรูปสลิป', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.selectPackage(pkg(fixture, 250).id);
        await app.nextButton.click();
        await app.slipInput.setInputFiles(pdfFile());
        await app.submitButton.click();

        await expect(page.getByText('ไฟล์สลิปต้องเป็นรูปภาพเท่านั้น').first()).toBeVisible();
        expect((await state(request)).requests).toHaveLength(1);
    });

    test('CREDIT-PUR-25 กดส่งคำขอเติมเครดิต สลิปถูกต้อง', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.selectPackage(pkg(fixture, 500).id);
        await app.nextButton.click();
        await app.slipInput.setInputFiles(slipImage());
        await app.submitButton.click();

        await expect(page).toHaveURL(/\/credits\/topup$/);
        await expect(page.getByText('ส่งคำขอเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();
        await expect(page.getByText('รอตรวจสอบ').first()).toBeVisible();

        const after = await state(request);
        expect(after.requests.filter((r) => r.price_satang === 50000)).toHaveLength(1);
        expect(after.credit_balance).toBe(1000); // credit is only granted once an admin approves
    });

    test('CREDIT-PUR-26 รีเฟรชหลังส่งคำขอ', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.selectPackage(pkg(fixture, 500).id);
        await app.nextButton.click();
        await app.slipInput.setInputFiles(slipImage());
        await app.submitButton.click();
        await expect(page.getByText('ส่งคำขอเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();

        await page.reload();
        await expect(page.getByText('รอตรวจสอบ').first()).toBeVisible();
        expect((await state(request)).requests.filter((r) => r.price_satang === 50000)).toHaveLength(1);
    });

    test('CREDIT-PUR-27 ตรวจสอบยอดเครดิตคงเหลือก่อนเติม', async ({ page, request }) => {
        const { app } = await openCustomer(page, request);
        await expect(app.creditBalanceLocator(1000)).toBeVisible();
        expect((await state(request)).credit_balance).toBe(1000);
    });

    test('CREDIT-PUR-28 ตรวจสอบยอดเครดิตหลังส่งคำขอ (ยังรอตรวจสอบ)', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.selectPackage(pkg(fixture, 500).id);
        await app.nextButton.click();
        await app.slipInput.setInputFiles(slipImage());
        await app.submitButton.click();
        await expect(page.getByText('ส่งคำขอเติมเครดิตเรียบร้อยแล้ว').first()).toBeVisible();

        await expect(app.creditBalanceLocator(1000)).toBeVisible();
        expect((await state(request)).credit_balance).toBe(1000);
    });

    test('CREDIT-PUR-29 ตรวจสอบยอดเครดิตหลังแอดมินอนุมัติ', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.logout();
        await app.login(fixture.admin);
        await page.goto(`/admin/credit-topups/${fixture.pendingRequest.id}`);
        await page.getByRole('button', { name: 'อนุมัติและเติมเครดิต' }).click();
        await expect(page).toHaveURL(/\/admin\/credit-topups/);

        await app.logout();
        await app.login(fixture.customer);
        await app.goto();

        // The fixture's pending request is worth ฿800 → 1000 + 800 = 1800.
        await expect(app.creditBalanceLocator(1800)).toBeVisible();
        expect((await state(request)).credit_balance).toBe(1800);
    });

    test('CREDIT-PUR-30 ตรวจสอบยอดเครดิตเมื่อแอดมินปฏิเสธ', async ({ page, request }) => {
        const { app, fixture } = await openCustomer(page, request);
        await app.logout();
        await app.login(fixture.admin);
        await page.goto(`/admin/credit-topups/${fixture.pendingRequest.id}`);
        await page.getByRole('button', { name: 'ปฏิเสธคำขอ' }).click();
        await page.locator('#rejectTopupReason').fill('สลิปไม่ชัดเจน (E2E)');
        await page.getByRole('button', { name: 'ปฏิเสธและส่งอีเมลแจ้งลูกค้า' }).click();
        await expect(page).toHaveURL(/\/admin\/credit-topups/);

        await app.logout();
        await app.login(fixture.customer);
        await app.goto();

        await expect(app.creditBalanceLocator(1000)).toBeVisible();
        const after = await state(request);
        expect(after.credit_balance).toBe(1000);
        expect(after.requests.find((r) => r.id === fixture.pendingRequest.id)).toMatchObject({ status: 'rejected', rejected_reason: 'สลิปไม่ชัดเจน (E2E)' });
    });
});
