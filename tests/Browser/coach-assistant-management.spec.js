import { expect, test } from '@playwright/test';
import { PrivateTrainingPage } from './pages/private-train-m.page.js';
import { StaffManagementPage } from './pages/staff-management.page.js';

async function setup(request) {
    const response = await request.post('/__e2e/coach-assistant-management/case');
    expect(response.ok(), await response.text()).toBeTruthy();
    return response.json();
}

async function setupPrivateTraining(request) {
    const response = await request.post('/__e2e/private-training/case', { data: { purchases: ['weekday'] } });
    expect(response.ok(), await response.text()).toBeTruthy();
    return response.json();
}

async function openManagement(page, request) {
    const fixture = await setup(request);
    const staff = new StaffManagementPage(page);
    await staff.login(fixture.admin);
    await staff.openFromProfileMenu();
    return { fixture, staff };
}

async function openCoach(page, request) {
    const result = await openManagement(page, request);
    await result.staff.openProfile(result.fixture.coach.name);
    return result;
}

async function state(request, id) {
    const response = await request.get(`/__e2e/coach-assistant-management/${id}/state`);
    expect(response.ok()).toBeTruthy();
    return response.json();
}

test.describe.serial('CO-AS-M การจัดการโค้ชและผู้ช่วยสนาม', () => {
    test('CO-AS-M-01 เข้าสู่หน้าจอการจัดการโค้ชและผู้ช่วยสนาม', async ({ page, request }) => {
        const { fixture, staff } = await openManagement(page, request);
        await expect(staff.heading).toBeVisible();
        await expect(staff.row(fixture.coach.name)).toBeVisible();
        await expect(staff.row(fixture.assistant.name)).toBeVisible();
    });

    test('CO-AS-M-02 ตรวจสอบการค้นหาบุคลากร', async ({ page, request }) => {
        const { fixture, staff } = await openManagement(page, request);
        await staff.search('ชาตรี');
        await expect(staff.row(fixture.coach.name)).toHaveCount(1);
        await expect(staff.row(fixture.assistant.name)).toHaveCount(0);
    });

    test('CO-AS-M-03 ตรวจสอบการแสดงผลบุคลากรทั้งหมด', async ({ page, request }) => {
        const { fixture, staff } = await openManagement(page, request);
        await staff.selectTab('บุคลากรทั้งหมด');
        await expect(staff.row(fixture.coach.name)).toHaveCount(1);
        await expect(staff.row(fixture.assistant.name)).toHaveCount(1);
    });

    test('CO-AS-M-04 ตรวจสอบการแสดงผลเฉพาะโค้ช', async ({ page, request }) => {
        const { fixture, staff } = await openManagement(page, request);
        await staff.selectTab('เฉพาะโค้ช (Coaches)');
        await expect(staff.row(fixture.coach.name)).toHaveCount(1);
        await expect(staff.row(fixture.assistant.name)).toHaveCount(0);
        await expect(page.locator('tbody')).not.toContainText('ผู้ช่วยสนาม (Staff)');
    });

    test('CO-AS-M-05 ตรวจสอบการแสดงผลเฉพาะผู้ช่วยสนาม', async ({ page, request }) => {
        const { fixture, staff } = await openManagement(page, request);
        await staff.selectTab('เฉพาะผู้ช่วยสนาม (Staffs)');
        await expect(staff.row(fixture.assistant.name)).toHaveCount(1);
        await expect(staff.row(fixture.coach.name)).toHaveCount(0);
        await expect(page.locator('tbody')).not.toContainText('ผู้ฝึกสอน (Coach)');
    });

    test('CO-AS-M-06 ตรวจสอบโปรไฟล์และตารางงานของโค้ช', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await expect(page.getByRole('heading', { name: fixture.coach.name })).toBeVisible();
        await expect(page.getByText('พัฒนาทักษะการยิงและการเลี้ยงบอล')).toBeVisible();
        await expect(staff.calendar).toBeVisible();
    });

    test('CO-AS-M-07 ตรวจสอบโปรไฟล์และตารางงานของผู้ช่วยสนาม', async ({ page, request }) => {
        const { fixture, staff } = await openManagement(page, request);
        await staff.openProfile(fixture.assistant.name);
        await expect(page.getByRole('heading', { name: fixture.assistant.name })).toBeVisible();
        await expect(page.getByText('ดูแลสนามและอุปกรณ์')).toBeVisible();
        await expect(staff.calendar).toBeVisible();
    });

    test('CO-AS-M-08 เปิดหน้าต่างแก้ไขและแสดงข้อมูลเดิมถูกต้อง', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.openEdit();
        await expect(staff.nameInput).toHaveValue(fixture.coach.name);
        await expect(staff.emailInput).toHaveValue(fixture.coach.email);
        await expect(staff.roleSelect).toHaveValue('coach');
        await expect(staff.phoneInput).toHaveValue('0898000002');
    });

    test('CO-AS-M-09 แก้ไขข้อมูลทุกช่องด้วยข้อมูลที่ถูกต้อง', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.openEdit();
        await staff.nameInput.fill('โค้ชชาตรี แก้ไขครบ');
        await staff.emailInput.fill('coas-coach-updated@e2e.local');
        await staff.roleSelect.selectOption('coach');
        await staff.phoneInput.fill('0898111111');
        await staff.genderSelect.selectOption('female');
        await staff.specialtyInput.fill('การยิงสามคะแนน');
        await staff.bioInput.fill('ข้อมูลแนะนำตัวที่แก้ไขครบทุกช่อง');
        await staff.imageInput.setInputFiles({ name: 'coach.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', 'base64') });
        await staff.save();
        await staff.expectSuccess();
        await expect(page.getByRole('heading', { name: 'โค้ชชาตรี แก้ไขครบ' })).toBeVisible();

        const snapshot = await state(request, fixture.coach.id);
        expect(snapshot.user).toMatchObject({ name: 'โค้ชชาตรี แก้ไขครบ', email: 'coas-coach-updated@e2e.local', phone: '0898111111', membership_type: 'coach' });
        expect(snapshot.profile).toMatchObject({ gender: 'female', specialty: 'การยิงสามคะแนน', bio: 'ข้อมูลแนะนำตัวที่แก้ไขครบทุกช่อง' });
        expect(snapshot.profile.profile_image).toBeTruthy();
    });

    test('CO-AS-M-10 แก้ไขเพียงบางช่องโดยค่าอื่นไม่สูญหาย', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.openEdit();
        await staff.nameInput.fill('โค้ชชาตรี แก้ไขบางส่วน');
        await staff.phoneInput.fill('0898222222');
        await staff.bioInput.fill('แก้ไขเฉพาะ Bio');
        await staff.save();
        await staff.expectSuccess();

        const snapshot = await state(request, fixture.coach.id);
        expect(snapshot.user).toMatchObject({ name: 'โค้ชชาตรี แก้ไขบางส่วน', email: fixture.coach.email, phone: '0898222222', membership_type: 'coach' });
        expect(snapshot.profile).toMatchObject({ specialty: 'พัฒนาทักษะการยิงและการเลี้ยงบอล', gender: 'male', bio: 'แก้ไขเฉพาะ Bio' });
    });

    test('CO-AS-M-11 Validation ช่องบังคับ', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.openEdit();
        await staff.nameInput.fill('');
        await staff.emailInput.fill('');
        await staff.roleSelect.selectOption('');
        await staff.profileForm.evaluate((form) => form.noValidate = true);
        await staff.save();
        await expect(page.locator('.swal2-title')).toContainText('กรุณากรอกชื่อ-นามสกุล');
        await expect(staff.profileModal).toContainText('กรุณากรอกอีเมล');
        await expect(staff.profileModal).toContainText('กรุณาเลือกตำแหน่ง');
        const snapshot = await state(request, fixture.coach.id);
        expect(snapshot.user).toMatchObject({ name: fixture.coach.name, email: fixture.coach.email, membership_type: 'coach' });
    });

    test('CO-AS-M-12 ช่องเบอร์โทรศัพท์ไม่รับตัวอักษรหรืออักขระ', async ({ page, request }) => {
        const { staff } = await openCoach(page, request);
        await staff.openEdit();
        await staff.phoneInput.fill('abc-!@#');
        await expect(staff.phoneInput).toHaveValue('');
    });

    test('CO-AS-M-13 Validation จำนวนหลักเบอร์โทรศัพท์ไม่ถูกต้อง', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.openEdit();
        await staff.phoneInput.fill('081234567');
        await staff.save();
        await expect(page.locator('.swal2-title')).toContainText('เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก');
        expect((await state(request, fixture.coach.id)).user.phone).toBe('0898000002');
    });

    test('CO-AS-M-14 ล้างข้อมูลช่องไม่บังคับได้', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.openEdit();
        await staff.phoneInput.fill('');
        await staff.specialtyInput.fill('');
        await staff.bioInput.fill('');
        await staff.save();
        await staff.expectSuccess();

        const snapshot = await state(request, fixture.coach.id);
        expect(snapshot.user.phone).toBeNull();
        expect(snapshot.profile.specialty).toBeNull();
        expect(snapshot.profile.bio).toBeNull();
    });

    test('CO-AS-M-15 ยกเลิกการแก้ไขแล้วข้อมูลไม่เปลี่ยน', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.openEdit();
        await staff.nameInput.fill('ชื่อที่ต้องไม่ถูกบันทึก');
        await staff.phoneInput.fill('0898999999');
        await staff.bioInput.fill('Bio ที่ต้องไม่ถูกบันทึก');
        await staff.profileForm.getByRole('button', { name: 'ยกเลิก' }).click();
        await expect(staff.profileModal).toBeHidden();
        expect((await state(request, fixture.coach.id)).user.name).toBe(fixture.coach.name);
    });

    test('CO-AS-M-16 ป้องกันการกดบันทึกข้อมูลซ้ำ', async ({ page, request }) => {
        const { staff } = await openCoach(page, request);
        await staff.openEdit();
        await staff.nameInput.fill('โค้ชป้องกันบันทึกซ้ำ');
        const buttonState = await staff.profileForm.evaluate((form) => {
            form.requestSubmit();
            const button = form.querySelector('button[type="submit"]');
            return { disabled: button.disabled, text: button.textContent.trim() };
        });
        expect(buttonState).toEqual({ disabled: true, text: 'กำลังบันทึก...' });
    });

    test('CO-AS-M-17 ถอดบทบาทโค้ชหรือผู้ช่วยสนาม', async ({ page, request }) => {
        const { fixture, staff } = await openManagement(page, request);
        await staff.row(fixture.removable.name).getByRole('button', { name: 'ถอดบทบาท' }).click();
        await page.locator('.swal2-confirm').click();
        await expect(staff.row(fixture.removable.name)).toHaveCount(0);
        expect((await state(request, fixture.removable.id)).user).toMatchObject({ role: 'user', membership_type: 'customer' });
    });

    test('CO-AS-M-18 แสดงกิจกรรมบนปฏิทินพร้อมชื่อ วันที่ และเวลา', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await expect(staff.calendar.locator('.fc-event').filter({ hasText: fixture.event.title })).toBeVisible();
        await expect(staff.calendar).toContainText('10:00');
    });

    test('CO-AS-M-19 แสดงปฏิทินรายเดือน', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.calendar.getByRole('button', { name: 'เดือน' }).click();
        await expect(staff.calendar.locator('.fc-dayGridMonth-view')).toBeVisible();
        await expect(staff.calendar.locator('.fc-event').filter({ hasText: fixture.event.title })).toBeVisible();
    });

    test('CO-AS-M-20 แสดงปฏิทินรายสัปดาห์', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.calendar.getByRole('button', { name: 'สัปดาห์' }).click();
        await expect(staff.calendar.locator('.fc-timeGridWeek-view')).toBeVisible();
        await expect(staff.calendar.locator('.fc-event').filter({ hasText: fixture.event.title })).toBeVisible();
    });

    test('CO-AS-M-21 แสดงปฏิทินแบบรายการ', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.calendar.getByRole('button', { name: 'รายการ' }).click();
        await expect(staff.calendar.locator('.fc-listWeek-view')).toBeVisible();
        await expect(staff.calendar).toContainText(fixture.event.title);
    });

    test('CO-AS-M-22 กดดูรายละเอียด schedule', async ({ page, request }) => {
        const { fixture, staff } = await openCoach(page, request);
        await staff.calendar.locator('.fc-event').filter({ hasText: fixture.event.title }).click();
        await expect(page.locator('.swal2-title')).toHaveText(fixture.event.title);
        await expect(page.locator('.swal2-html-container')).toContainText('งาน');
    });

    test('CO-AS-M-23 กดปุ่มแก้ไข schedule และแสดงข้อมูลเดิม', async ({ page, request }) => {
        const { fixture } = await openCoach(page, request);
        await page.getByRole('link', { name: 'จัดการ Schedule แบบละเอียด' }).click();
        await expect(page).toHaveURL(/\/admin\/private-schedule\?staff_id=\d+$/);
        const calendar = page.locator('#private-schedule-calendar');
        const event = calendar.locator('.fc-event').filter({ hasText: fixture.event.title });
        await expect(event).toBeVisible();
        await event.click();

        await expect(page.locator('#admin-schedule-modal')).toBeVisible();
        await expect(page.locator('#admin-modal-title')).toHaveText('แก้ไขกำหนดการ');
        await expect(page.locator('#admin-event-title')).toHaveValue(fixture.event.title);
        await expect(page.locator('#admin-event-date')).toHaveValue(fixture.event.date);
        await expect(page.locator('#admin-event-start')).toHaveValue(fixture.event.start);
        await expect(page.locator('#admin-event-end')).toHaveValue(fixture.event.end);
    });

    test('CO-AS-M-24 แสดงโปรไฟล์โค้ชที่หน้า Private Training', async ({ page, request }) => {
        const fixture = await setupPrivateTraining(request);
        const privateTraining = new PrivateTrainingPage(page);
        await privateTraining.loginAndOpen(fixture.user);
        const card = privateTraining.coachCard(fixture.coach.name);
        await expect(card).toBeVisible();
        await expect(card).toContainText('ผู้ฝึกสอน (Coach)');
        await expect(card).toContainText('ความเชี่ยวชาญ');
    });

    test('CO-AS-M-25 แสดงรายละเอียดโปรไฟล์และวันเวลาว่างของโค้ช', async ({ page, request }) => {
        const fixture = await setupPrivateTraining(request);
        const privateTraining = new PrivateTrainingPage(page);
        await privateTraining.loginAndOpen(fixture.user);
        await privateTraining.openCoach(fixture.coach.name);
        await expect(page.getByRole('heading', { name: fixture.coach.name })).toBeVisible();
        await expect(page.getByText('เลือกวันและเวลาฝึก')).toBeVisible();
        await expect(privateTraining.calendar).toBeVisible();
    });
});
