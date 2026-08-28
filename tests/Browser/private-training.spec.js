import { expect, test } from '@playwright/test';
import { PrivateTrainingPage } from './pages/private-train-m.page.js';

async function setup(request, options = {}) {
    const response = await request.post('/__e2e/private-training/case', { data: options });
    if (!response.ok()) {
        throw new Error(`สร้างข้อมูล PTB ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    }
    return response.json();
}

async function state(request) {
    const response = await request.get('/__e2e/private-training/state');
    expect(response.ok()).toBeTruthy();
    return response.json();
}

function money(value, decimals = 0) {
    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

function purchase(fixture, kind = 'weekday') {
    return fixture.purchases[kind];
}

async function loginPrivate(page, fixture) {
    const privateTraining = new PrivateTrainingPage(page);
    await privateTraining.loginAndOpen(fixture.user);
    return privateTraining;
}

async function openCoach(page, fixture) {
    const privateTraining = await loginPrivate(page, fixture);
    await privateTraining.openCoach(fixture.coach.name);
    return privateTraining;
}

async function openBooking(page, fixture, kind = 'weekday') {
    const privateTraining = await openCoach(page, fixture);
    await privateTraining.dragAvailableTime(fixture.dates[kind]);
    return privateTraining;
}

async function submitStandardBooking(page, fixture, options = {}) {
    const kind = options.kind ?? 'weekday';
    const privateTraining = await openBooking(page, fixture, kind);
    await privateTraining.fillBooking({
        purchaseId: purchase(fixture, kind).id,
        participantCount: options.participantCount ?? 2,
        assistantId: options.assistantId,
        note: options.note ?? '',
    });
    await privateTraining.submitBooking();
    return privateTraining;
}

test.describe.serial('Private Training PTB-01 ถึง PTB-26', () => {
    test('PTB-01 ตรวจสอบการเข้าสู่หน้าจองไพรเวทเทรนนิ่ง (ไม่มีแพ็กเกจ)', async ({ page, request }) => {
        const fixture = await setup(request);
        const privateTraining = await loginPrivate(page, fixture);

        await expect(page.getByText('คุณยังไม่มีแพ็กเกจที่ใช้จองเทรนเนอร์ได้')).toBeVisible();
        await expect(page.locator('#btn-need-package')).toBeVisible();
        await expect(privateTraining.coachCard(fixture.coach.name)).toHaveCount(0);
    });

    test('PTB-02 ตรวจสอบการซื้อแพ็กเกจเมื่อเครดิตเพียงพอ', async ({ page, request }) => {
        const fixture = await setup(request, { credit_balance: 2000 });
        const privateTraining = await loginPrivate(page, fixture);
        const selectedPackage = fixture.packages.weekday;

        await privateTraining.buyPackage(selectedPackage.name);
        await expect(page.locator('.co-main')).toContainText(selectedPackage.name);
        await expect(privateTraining.checkoutRow('ยอดเครดิตปัจจุบัน')).toHaveText(`฿${money(fixture.user.credit_balance)}`);
        await expect(privateTraining.checkoutRow('ยอดชำระ')).toHaveText(`฿-${money(selectedPackage.price)}`);
        await expect(privateTraining.checkoutRow('ยอดเครดิตคงเหลือ')).toHaveText(`฿${money(fixture.user.credit_balance - selectedPackage.price)}`);
        await privateTraining.payWithCredit();

        const snapshot = await state(request);
        const approved = snapshot.purchases.find((item) => item.status === 'approved');
        expect(approved).toMatchObject({ package: selectedPackage.name, remaining_use: 4 });
        expect(snapshot.credit_balance).toBe(fixture.user.credit_balance - selectedPackage.price);
    });

    test('PTB-03 ตรวจสอบการซื้อแพ็กเกจเมื่อเครดิตเท่ากับราคาแพ็กเกจ', async ({ page, request }) => {
        const fixture = await setup(request, { credit_balance: 1000 });
        const privateTraining = await loginPrivate(page, fixture);

        await privateTraining.buyPackage(fixture.packages.weekday.name);
        await expect(privateTraining.checkoutRow('ยอดเครดิตคงเหลือ')).toHaveText('฿0');
        await privateTraining.payWithCredit();

        const snapshot = await state(request);
        expect(snapshot.credit_balance).toBe(0);
        expect(snapshot.purchases.filter((item) => item.status === 'approved')).toHaveLength(1);
    });

    test('PTB-04 ตรวจสอบการซื้อแพ็กเกจเมื่อเครดิตไม่เพียงพอ', async ({ page, request }) => {
        const fixture = await setup(request, { credit_balance: 500 });
        const privateTraining = await loginPrivate(page, fixture);

        await privateTraining.buyPackage(fixture.packages.weekday.name);
        await expect(page.getByText(/เครดิตไม่เพียงพอ/)).toBeVisible();
        await expect(page.locator('.btn-pay.credit')).toBeDisabled();

        const snapshot = await state(request);
        expect(snapshot.credit_balance).toBe(fixture.user.credit_balance);
        expect(snapshot.transactions).toHaveLength(0);
        expect(snapshot.purchases.filter((item) => item.status === 'approved')).toHaveLength(0);
    });

    test('PTB-05 ตรวจสอบจำนวนเครดิตหลังซื้อแพ็กเกจ', async ({ page, request }) => {
        const fixture = await setup(request, { credit_balance: 2000 });
        const privateTraining = await loginPrivate(page, fixture);
        const expectedBalance = fixture.user.credit_balance - fixture.packages.weekday.price;

        await privateTraining.buyPackage(fixture.packages.weekday.name);
        await expect(privateTraining.checkoutRow('ยอดเครดิตปัจจุบัน')).toHaveText(`฿${money(fixture.user.credit_balance)}`);
        await privateTraining.payWithCredit();
        await expect(page.locator('nav')).toContainText(`${money(expectedBalance, 2)} ฿`);
        expect((await state(request)).credit_balance).toBe(expectedBalance);
    });

    test('PTB-06 ตรวจสอบการบันทึกประวัติการหักเครดิตจากการซื้อแพ็กเกจ', async ({ page, request }) => {
        const fixture = await setup(request, { credit_balance: 2000 });
        const privateTraining = await loginPrivate(page, fixture);

        await privateTraining.buyPackage(fixture.packages.weekday.name);
        await privateTraining.payWithCredit();
        await page.locator('#logout-form').evaluate((form) => form.requestSubmit());
        await expect(page).toHaveURL(/\/$/);

        await privateTraining.loginFromHome(fixture.admin);
        await privateTraining.openAdminUsers();
        await page.locator('input[name="search"]').fill(fixture.user.name);
        await page.getByRole('button', { name: 'ค้นหา', exact: true }).click();
        const userRow = page.locator('tbody tr').filter({ hasText: fixture.user.email });
        await expect(userRow).toHaveCount(1);
        await userRow.getByRole('link', { name: 'ดูข้อมูลและประวัติ', exact: true }).click();
        await page.getByRole('link', { name: /จัดการเครดิต/ }).click();

        const txRow = page.locator('tbody tr').filter({ hasText: 'ชำระค่าแพ็กเกจ' });
        await expect(txRow).toHaveCount(1);
        await expect(txRow).toContainText('หักเครดิต');
        await expect(txRow).toContainText(`-฿${money(fixture.packages.weekday.price, 2)}`);
        await expect(txRow).toContainText(`฿${money(fixture.user.credit_balance - fixture.packages.weekday.price, 2)}`);
    });

    test('PTB-07 ตรวจสอบการเข้าสู่หน้าจองไพรเวทเทรนนิ่ง', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await loginPrivate(page, fixture);

        await expect(privateTraining.coachCard(fixture.coach.name)).toBeVisible();
        await expect(privateTraining.coachCard(fixture.coach.name)).toContainText('ดูตารางว่างและจองเวลา');
    });

    test('PTB-08 ตรวจสอบการเลือกโค้ช', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openCoach(page, fixture);

        await expect(page.getByRole('heading', { name: fixture.coach.name, exact: true })).toBeVisible();
        await expect(privateTraining.calendar).toBeVisible();
        await expect(page.getByRole('heading', { name: 'เลือกวันและเวลาฝึก' })).toBeVisible();
    });

    test('PTB-09 ตรวจสอบการเลือกช่วงเวลาที่โค้ชว่าง', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture);

        await expect(privateTraining.bookingModal).toBeVisible();
        await expect(page.locator('#booking-date-label')).not.toBeEmpty();
        await expect(page.locator('#booking-time-label')).toHaveText('18:00–19:00 น.');
    });

    test('PTB-10 ตรวจสอบการเลือกช่วงเวลาที่โค้ชไม่ว่าง', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openCoach(page, fixture);

        await privateTraining.clickBusyTime(fixture.dates.weekday);
        await expect(page.locator('.swal2-title')).toHaveText('ช่วงเวลานี้จองไม่ได้');
        await expect(page.locator('.swal2-html-container')).toContainText('โค้ชมีกำหนดการในช่วงเวลานี้');
        await expect(privateTraining.bookingModal).toBeHidden();
    });

    test('PTB-11 ตรวจสอบแพ็กเกจจันทร์-ศุกร์จองในวันจันทร์-ศุกร์', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture, 'weekday');
        const option = privateTraining.packageOption(purchase(fixture, 'weekday').id);

        await expect(option).toBeEnabled();
        await privateTraining.choosePackage(purchase(fixture, 'weekday').id);
        await expect(page.locator('#package-day-warning')).toBeHidden();
    });

    test('PTB-12 ตรวจสอบแพ็กเกจจันทร์-ศุกร์จองในวันเสาร์-อาทิตย์', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture, 'weekend');

        await expect(privateTraining.packageOption(purchase(fixture, 'weekday').id)).toBeDisabled();
        await expect(page.locator('#package-day-warning')).toBeVisible();
        await expect(privateTraining.packageSelect).toHaveValue('');
    });

    test('PTB-13 ตรวจสอบแพ็กเกจเสาร์-อาทิตย์จองในวันเสาร์-อาทิตย์', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekend'] });
        const privateTraining = await openBooking(page, fixture, 'weekend');

        await expect(privateTraining.packageOption(purchase(fixture, 'weekend').id)).toBeEnabled();
        await privateTraining.choosePackage(purchase(fixture, 'weekend').id);
        await expect(page.locator('#package-day-warning')).toBeHidden();
    });

    test('PTB-14 ตรวจสอบแพ็กเกจเสาร์-อาทิตย์จองในวันจันทร์-ศุกร์', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekend'] });
        const privateTraining = await openBooking(page, fixture, 'weekday');

        await expect(privateTraining.packageOption(purchase(fixture, 'weekend').id)).toBeDisabled();
        await expect(page.locator('#package-day-warning')).toBeVisible();
        await expect(privateTraining.packageSelect).toHaveValue('');
    });

    test('PTB-15 ตรวจสอบการแสดงผู้ช่วยสนามที่ว่างตรงกับเวลาจอง', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture);
        await privateTraining.choosePackage(purchase(fixture).id);
        await privateTraining.requestAssistant();

        await expect(privateTraining.assistantSelect.locator('option').filter({ hasText: fixture.assistants.free.name })).toHaveCount(1);
        await expect(privateTraining.assistantSelect.locator('option').filter({ hasText: fixture.assistants.busy.name })).toHaveCount(0);
        await expect(page.locator('#assistant-status')).toContainText(/มีผู้ช่วยว่าง \d+ คน/);
    });

    test('PTB-16 ตรวจสอบผู้ช่วยสนามที่ไม่ว่าง', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture);
        await privateTraining.choosePackage(purchase(fixture).id);
        await privateTraining.requestAssistant();

        await expect(privateTraining.assistantSelect.locator(`option[value="${fixture.assistants.busy.id}"]`)).toHaveCount(0);
        await expect(privateTraining.assistantSelect.locator(`option[value="${fixture.assistants.free.id}"]`)).toBeEnabled();
    });

    test('PTB-17 ตรวจสอบการกรอกจำนวนผู้เข้าใช้บริการ', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture);
        await privateTraining.fillBooking({ purchaseId: purchase(fixture).id, participantCount: 2 });

        await expect(privateTraining.participantCount).toHaveValue('2');
        await privateTraining.submitBooking();
        expect((await state(request)).bookings[0].participant_count).toBe(2);
    });

    test('PTB-18 ตรวจสอบการไม่กรอกจำนวนผู้เข้าใช้บริการ', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture);
        await privateTraining.choosePackage(purchase(fixture).id);
        await privateTraining.enterInvalidParticipant('');
        await privateTraining.submitButton().click();

        await expect(page.locator('.swal2-title')).toContainText(/participant count|required|จำนวนผู้เข้าร่วม/i);
        const snapshot = await state(request);
        expect(snapshot.bookings).toHaveLength(0);
        expect(snapshot.purchases[0].remaining_use).toBe(4);
    });

    test('PTB-19 ตรวจสอบจำนวนผู้เข้าใช้บริการเป็น 0', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture);
        await privateTraining.choosePackage(purchase(fixture).id);
        await privateTraining.enterInvalidParticipant('0');
        await privateTraining.submitButton().click();

        await expect(page.locator('.swal2-title')).toContainText(/participant count|at least 1|จำนวนผู้เข้าร่วม/i);
        expect((await state(request)).bookings).toHaveLength(0);
    });

    test('PTB-20 ตรวจสอบจำนวนผู้เข้าใช้บริการเป็นค่าติดลบ', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture);
        await privateTraining.choosePackage(purchase(fixture).id);
        await privateTraining.enterInvalidParticipant('-1');
        await privateTraining.submitButton().click();

        await expect(page.locator('.swal2-title')).toContainText(/participant count|at least 1|จำนวนผู้เข้าร่วม/i);
        expect((await state(request)).bookings).toHaveLength(0);
    });

    test('PTB-21 ตรวจสอบจำนวนผู้เข้าใช้บริการเป็นตัวอักษร', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openBooking(page, fixture);
        const values = await privateTraining.participantCount.locator('option').evaluateAll((options) =>
            options.map((option) => option.value),
        );

        expect(values).toEqual(['1', '2', '3', '4', '5', '6']);
        await privateTraining.participantCount.focus();
        await page.keyboard.type('abc');
        await expect(privateTraining.participantCount).toHaveValue('1');
        expect((await state(request)).bookings).toHaveLength(0);
    });

    test('PTB-22 ตรวจสอบการส่งคำขอจองไพรเวทเทรนนิ่ง', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const note = 'ฝึกการยิงสามแต้มและการเคลื่อนที่';
        await submitStandardBooking(page, fixture, {
            assistantId: fixture.assistants.free.id,
            participantCount: 2,
            note,
        });

        await expect(page.getByText('คำขอของคุณกับโค้ชคนนี้')).toBeVisible();
        await expect(page.getByText('รออนุมัติ', { exact: true }).first()).toBeVisible();
        const booking = (await state(request)).bookings[0];
        expect(booking).toMatchObject({
            status: 'pending',
            participant_count: 2,
            assistant_requested: true,
            court_assistant_id: fixture.assistants.free.id,
            note,
        });
    });

    test('PTB-23 ตรวจสอบการหักสิทธิ์แพ็กเกจหลังส่งคำขอ', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'], remaining_use: 4 });
        expect((await state(request)).purchases[0].remaining_use).toBe(4);

        await submitStandardBooking(page, fixture);

        const snapshot = await state(request);
        expect(snapshot.bookings).toHaveLength(1);
        expect(snapshot.purchases[0].remaining_use).toBe(3);
    });

    test('PTB-24 ตรวจสอบสถานะและกำหนดการตารางหลังจาก Admin อนุมัติคำขอ', async ({ page, request, browser }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        await submitStandardBooking(page, fixture);

        const adminContext = await browser.newContext();
        const adminPage = await adminContext.newPage();
        const adminPrivateTraining = new PrivateTrainingPage(adminPage);
        await adminPrivateTraining.loginFromHome(fixture.admin);
        await adminPrivateTraining.openAdminPrivateTraining();
        const bookingCard = adminPrivateTraining.adminBookingCard(fixture.user.email);
        await expect(bookingCard).toHaveCount(1);
        await bookingCard.getByRole('button', { name: 'อนุมัติ', exact: true }).click();
        await expect(adminPage.locator('.swal2-title')).toContainText('รับคำขอเรียบร้อยแล้ว');
        await adminContext.close();

        await page.getByRole('link', { name: /กลับไปหน้ารายชื่อโค้ช/ }).click();
        await expect(page.getByText('รอจัดสนาม', { exact: true })).toBeVisible();
        const snapshot = await state(request);
        expect(snapshot.bookings[0].status).toBe('awaiting_court');
        expect(snapshot.purchases[0].remaining_use).toBe(3);
    });

    test('PTB-25 ตรวจสอบสถานะและกำหนดการตารางหลังจาก Admin ปฏิเสธคำขอ', async ({ page, request, browser }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const rejectReason = 'โค้ชติดภารกิจด่วนในช่วงเวลานี้';
        await submitStandardBooking(page, fixture);

        const adminContext = await browser.newContext();
        const adminPage = await adminContext.newPage();
        const adminPrivateTraining = new PrivateTrainingPage(adminPage);
        await adminPrivateTraining.loginFromHome(fixture.admin);
        await adminPrivateTraining.openAdminPrivateTraining();
        const bookingCard = adminPrivateTraining.adminBookingCard(fixture.user.email);
        await bookingCard.getByRole('button', { name: 'ปฏิเสธ', exact: true }).click();
        await adminPage.locator('#rejectModal textarea[name="reject_reason"]').fill(rejectReason);
        await adminPage.locator('#rejectModal').getByRole('button', { name: 'ยืนยันปฏิเสธ', exact: true }).click();
        await expect(adminPage.locator('.swal2-title')).toContainText('ปฏิเสธคำขอจองเรียบร้อย');
        await adminContext.close();

        await page.getByRole('link', { name: /กลับไปหน้ารายชื่อโค้ช/ }).click();
        await expect(page.getByText('ถูกปฏิเสธ', { exact: true })).toBeVisible();
        await expect(page.getByText(`เหตุผลที่ปฏิเสธ: ${rejectReason}`, { exact: true })).toBeVisible();
        const snapshot = await state(request);
        expect(snapshot.bookings[0]).toMatchObject({ status: 'rejected', reject_reason: rejectReason });
        expect(snapshot.purchases[0].remaining_use).toBe(4);
    });

    test('PTB-26 ตรวจสอบการจองไพรเวทเทรนนิ่งในวันนี้', async ({ page, request }) => {
        const fixture = await setup(request, { purchases: ['weekday'] });
        const privateTraining = await openCoach(page, fixture);

        await privateTraining.clickToday(fixture.dates.today);
        await expect(page.locator('.swal2-title')).toHaveText('จองวันนี้ไม่ได้');
        await expect(page.locator('.swal2-html-container')).toContainText('กรุณาเลือกจองล่วงหน้าอย่างน้อย 1 วัน');
        await expect(privateTraining.bookingModal).toBeHidden();
    });
});
