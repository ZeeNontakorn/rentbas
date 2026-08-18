import { expect, test } from '@playwright/test';
import { GroupBasketballPage } from './pages/group-basketball.page.js';

let fixture;

test.describe.serial('User จอยกลุ่มบาส GROUP-BAS-01 ถึง GROUP-BAS-09', () => {
    test.beforeAll(async ({ request }) => {
        const response = await request.post('/__e2e/group-rounds/seed');
        expect(response.ok()).toBeTruthy();
        fixture = await response.json();
    });

    async function openAsMember(page) {
        const groupPage = new GroupBasketballPage(page);
        await groupPage.login(fixture.user.email, fixture.user.password);
        await expect(page).toHaveURL(/\/$/);
        await groupPage.goto();
        return groupPage;
    }

    test('GROUP-BAS-01 ตรวจสอบการแสดงรอบที่เปิดรับสมัคร', async ({ page }) => {
        const groupPage = await openAsMember(page);
        const card = groupPage.card(fixture.rounds.OPEN.title);

        await expect(card).toBeVisible();
        await expect(card).toContainText(fixture.rounds.OPEN.play_date);
        await expect(card).toContainText('18:00–20:00 น.');
        await expect(card).toContainText('สนาม E2E กลุ่มบาส');
        await expect(card).toContainText(`เครดิต ${fixture.rounds.OPEN.credit_cost} / คน`);
        await expect(card).toContainText(`ลงชื่อแล้ว 0/${fixture.rounds.OPEN.max_players} คน`);
        await expect(card).toContainText(`เหลือ ${fixture.rounds.OPEN.max_players} ที่`);
        await expect(card.getByRole('link', { name: 'ลงชื่อจอง' })).toBeEnabled();
    });

    test('GROUP-BAS-02 ผู้ใช้ที่ยังไม่ได้เข้าสู่ระบบกดลงชื่อจอง', async ({ page }) => {
        const before = await (await page.request.get('/__e2e/group-rounds/state')).json();
        const groupPage = new GroupBasketballPage(page);
        await groupPage.goto();
        await groupPage.card(fixture.rounds.OPEN.title).getByRole('link', { name: 'ลงชื่อจอง' }).click();

        await expect(page).toHaveURL(/\/login$/);
        const after = await (await page.request.get('/__e2e/group-rounds/state')).json();
        expect(after).toEqual(before);
    });

    test('GROUP-BAS-03 ตรวจสอบรอบที่ปิดรับสมัคร', async ({ page }) => {
        const groupPage = await openAsMember(page);
        await expect(groupPage.card(fixture.rounds.CLOSED.title)).toHaveCount(0);
    });

    test('GROUP-BAS-04 ตรวจสอบรอบที่ถูกยกเลิก', async ({ page }) => {
        const groupPage = await openAsMember(page);
        await expect(groupPage.card(fixture.rounds.CANCELLED.title)).toHaveCount(0);
    });

    test('GROUP-BAS-05 ตรวจสอบรอบที่วันเล่นผ่านมาแล้ว', async ({ page }) => {
        const groupPage = await openAsMember(page);
        await expect(groupPage.card(fixture.rounds.PAST.title)).toHaveCount(0);
    });

    test('GROUP-BAS-06 ตรวจสอบข้อมูลรอบและ Deadline', async ({ page }) => {
        const groupPage = await openAsMember(page);
        const card = groupPage.card(fixture.rounds.OPEN.title);

        await expect(card).toContainText(fixture.rounds.OPEN.play_date);
        await expect(card).toContainText('18:00–20:00 น.');
        await expect(card).toContainText('สนาม E2E กลุ่มบาส');
        await expect(card).toContainText(`เครดิต ${fixture.rounds.OPEN.credit_cost} / คน`);
        await expect(card).toContainText(`ลงชื่อแล้ว 0/${fixture.rounds.OPEN.max_players} คน`);
        await expect(card).toContainText(`ยกเลิกจองได้ถึง ${fixture.deadline} น.`);
    });

    test('GROUP-BAS-07 ตรวจสอบรอบที่ไม่มี Deadline', async ({ page }) => {
        const groupPage = await openAsMember(page);
        await expect(groupPage.card(fixture.rounds['NO-DEADLINE'].title))
            .toContainText('ยกเลิกจองได้ตลอดเวลา');
    });

    test('GROUP-BAS-08 ตรวจสอบจำนวนผู้สมัครและจำนวนที่เหลือ', async ({ page }) => {
        const groupPage = await openAsMember(page);
        const card = groupPage.card(fixture.rounds['FOUR-OF-SIX'].title);

        await expect(card).toContainText('ลงชื่อแล้ว 4/6 คน');
        await expect(card).toContainText('เหลือ 2 ที่');
    });

    test('GROUP-BAS-09 ตรวจสอบการแสดงผลเมื่อรอบเต็ม', async ({ page }) => {
        const groupPage = await openAsMember(page);
        const card = groupPage.card(fixture.rounds.FULL.title);

        await expect(card).toContainText('ลงชื่อแล้ว 6/6 คน');
        await expect(card).not.toContainText('เหลือ');
        await expect(card.getByRole('link', { name: 'ลงชื่อสำรอง' })).toBeVisible();
        await card.getByRole('link', { name: 'ลงชื่อสำรอง' }).click();
        await expect(page.locator('body')).toContainText('ตอนนี้ตัวจริงเต็มแล้ว');
        await expect(page.locator('body')).toContainText('คิวสำรอง');
    });
});

async function setup(request, options = {}) {
    const response = await request.post('/__e2e/group-rounds/case', { data: options });
    expect(response.ok()).toBeTruthy();
    return response.json();
}

async function state(request, fixture) {
    return (await request.get(`/__e2e/group-rounds/${fixture.round.id}/case-state`)).json();
}

async function loginCheckout(page, fixture, userIndex = 0) {
    const group = new GroupBasketballPage(page);
    await group.login(fixture.users[userIndex].email, fixture.users[userIndex].password);
    await group.openCheckoutFromHome(fixture.round);
    return group;
}

async function loginHome(page, fixture, userIndex = 0) {
    const group = new GroupBasketballPage(page);
    await group.login(fixture.users[userIndex].email, fixture.users[userIndex].password);
    await group.goto();
    return group;
}

async function directSignup(page, fixture, names) {
    const token = await page.locator('main form input[name="_token"]').inputValue();
    const form = { _token: token };
    names.forEach((name, index) => { form[`names[${index}]`] = name; });
    return page.request.post(`/group-rounds/${fixture.round.id}/signup`, { form });
}

function paymentRow(page, label) {
    return page.locator('.gc-pay .gc-row').filter({ has: page.locator('.gc-label', { hasText: label }) }).locator('.gc-value');
}

async function expectPaymentSummary(page, fixture, count) {
    const balance = fixture.users[0].credit_balance;
    const total = fixture.round.credit_cost * count;
    await expect(paymentRow(page, 'จำนวนคน')).toHaveText(String(count));
    await expect(paymentRow(page, 'ยอดเครดิตปัจจุบัน')).toHaveText(`฿${balance.toLocaleString('en-US', { minimumFractionDigits: 2 })}`);
    await expect(paymentRow(page, 'ยอดชำระ')).toHaveText(`฿-${total.toLocaleString('en-US')}`);
    await expect(paymentRow(page, 'ยอดเครดิตคงเหลือ')).toHaveText(`฿${Math.max(0, balance - total).toLocaleString('en-US')}`);
}

function userCredit(snapshot, fixture, userIndex = 0) {
    return snapshot.credits[fixture.users[userIndex].email];
}

test.describe.serial('User จอยกลุ่มบาสลงชื่อรวมถึงชำระเครดิต TC-10-46', () => {
    test('GROUP-BAS-10 ตรวจสอบเปิดหน้าชำระเครดิต', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 1000 });
        const group = await loginCheckout(page, f);
        await expect(page.getByRole('heading', { name: 'ยืนยันการชำระเงิน' })).toBeVisible();
        await expect(page.locator('.group-checkout')).toContainText(f.round.title);
        await expect(group.nameInputs()).toHaveCount(1);
        await expectPaymentSummary(page, f, 1);
    });

    test('GROUP-BAS-11 ตรวจสอบลงชื่อผู้เล่น 1 คน', async ({ page, request }) => {
        const f = await setup(request); const group = await loginCheckout(page, f);
        await group.fillNames(['สมชาย']);
        await expectPaymentSummary(page, f, 1);
    });

    test('GROUP-BAS-12 ตรวจสอบจองแทนเพื่อนหลายคน', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 1000 }); const group = await loginCheckout(page, f);
        await group.fillNames(['สมชาย', 'สมศรี']);
        await expectPaymentSummary(page, f, 2);
    });

    test('GROUP-BAS-13 ตรวจสอบเพิ่มช่องชื่อ', async ({ page, request }) => {
        const f = await setup(request); const group = await loginCheckout(page, f);
        for (let i = 0; i < 4; i++) await group.addName();
        await expect(group.nameInputs()).toHaveCount(5);
        await expect(page.locator('.gc-add-btn')).toBeHidden();
    });

    test('GROUP-BAS-14 ตรวจสอบลบช่องชื่อ', async ({ page, request }) => {
        const f = await setup(request); const group = await loginCheckout(page, f);
        await group.fillNames(['A', 'B', 'C']);
        await page.locator('.gc-remove-btn').nth(1).click();
        await expect(group.nameInputs()).toHaveCount(2);
        await expect(group.nameInputs().nth(0)).toHaveValue('A');
        await expect(group.nameInputs().nth(1)).toHaveValue('C');
        await expectPaymentSummary(page, f, 2);
    });

    test('GROUP-BAS-15 ตรวจสอบไม่กรอกชื่อผู้เล่น', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 500 }); const group = await loginCheckout(page, f);
        await group.payButton().click();
        await expect(page.locator('body')).toContainText('กรุณากรอกชื่อผู้เล่นอย่างน้อย 1 คน');
        const s = await state(request, f); expect(s.signups).toHaveLength(0); expect(userCredit(s, f)).toBe(f.users[0].credit_balance);
    });

    test('GROUP-BAS-16 ตรวจสอบช่องชื่อบางรายการว่าง', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 500 }); const group = await loginCheckout(page, f);
        await group.fillNames(['หนึ่ง', '', 'สาม']); await group.payButton().click();
        const s = await state(request, f); expect(s.signups.map(x => x.name)).toEqual(['หนึ่ง', 'สาม']); expect(userCredit(s, f)).toBe(f.users[0].credit_balance - (2 * f.round.credit_cost));
    });

    test('GROUP-BAS-17 ตรวจสอบตัดช่องว่างหน้าหลังชื่อ', async ({ page, request }) => {
        const f = await setup(request); const group = await loginCheckout(page, f);
        await group.fillNames(['  สมชาย  ']); await group.payButton().click();
        const s = await state(request, f); expect(s.signups[0].name).toBe('สมชาย');
    });

    test('GROUP-BAS-18 ตรวจสอบชื่อยาวเกินกำหนด', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 500 }); const group = await loginCheckout(page, f);
        const overlongName = 'ก'.repeat(256);
        await group.nameInputs().evaluate(input => input.removeAttribute('maxlength'));
        await group.nameInputs().click();
        await group.nameInputs().pressSequentially(overlongName, { delay: 5 });
        await expect(group.nameInputs()).toHaveValue(overlongName);
        await group.payButton().click();
        await expect(page.locator('body')).toContainText('The names.0 field must not be greater than 255 characters.');
        const s = await state(request, f); expect(s.signups).toHaveLength(0); expect(userCredit(s, f)).toBe(f.users[0].credit_balance);
    });

    test('GROUP-BAS-19 ตรวจสอบจองครบสูงสุด 5 ที่', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 1000, max_players: 10 }); const group = await loginCheckout(page, f);
        await group.fillNames(['A', 'B', 'C', 'D', 'E']); await group.payButton().click();
        await expect(page.locator('body')).toContainText('คุณจอง 5/5 ที่');
        const s = await state(request, f); expect(s.signups).toHaveLength(5); expect(userCredit(s, f)).toBe(f.users[0].credit_balance - (5 * f.round.credit_cost));
    });

    test('GROUP-BAS-20 ตรวจสอบส่งคำขอจองเกิน 5 ที่', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 1000 }); await loginCheckout(page, f);
        const response = await directSignup(page, f, ['A','B','C','D','E','F']); expect(response.ok()).toBeTruthy();
        const s = await state(request, f); expect(s.signups).toHaveLength(0); expect(userCredit(s, f)).toBe(f.users[0].credit_balance);
    });

    test('GROUP-BAS-21 ตรวจสอบจองเพิ่มตามโควตาคงเหลือ', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 1000, max_players: 10, booked_names: ['เดิม1', 'เดิม2'] }); const group = await loginCheckout(page, f);
        await expect(page.locator('body')).toContainText('จองเพิ่มได้อีก 3 คน');
        await group.fillNames(['ใหม่1','ใหม่2','ใหม่3']); await group.payButton().click();
        const s = await state(request, f); expect(s.signups).toHaveLength(5);
    });

    test('GROUP-BAS-22 ตรวจสอบจองเพิ่มเกินโควตาคงเหลือ', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 1000, max_players: 10, booked_names: ['เดิม1','เดิม2','เดิม3'] });
        const group = await loginCheckout(page, f);
        await expect(page.locator('body')).toContainText('จองเพิ่มได้อีก 2 คน');
        await group.fillNames(['ใหม่1', 'ใหม่2']);

        // หน้าเว็บอนุญาตเพียง 2 ช่อง จึงเพิ่มช่องที่ 3 เพื่อจำลองผู้ใช้แก้ request
        // และยืนยันว่า backend ยังป้องกันการจองเกินโควตาได้
        await page.locator('form:has(.gc-pay)').evaluate((form) => {
            form.id = 'group-signup-form';
            const row = document.createElement('div');
            row.className = 'flex gap-2';
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'names[2]';
            input.setAttribute('form', form.id);
            input.className = 'gc-name-input';
            input.placeholder = 'ชื่อผู้เล่น (จำลองแก้ Request)';
            row.appendChild(input);
            document.querySelector('.gc-card .space-y-2').appendChild(row);
        });
        await group.nameInputs().nth(2).pressSequentially('ใหม่3', { delay: 80 });
        await expect(group.nameInputs()).toHaveCount(3);
        await group.payButton().click();
        await expect(page.locator('body')).toContainText('จองได้อีกแค่ 2 ที่');
        const s = await state(request, f); expect(s.signups).toHaveLength(3); expect(userCredit(s, f)).toBe(f.users[0].credit_balance);
    });

    test('GROUP-BAS-23 ตรวจสอบเครดิตเพียงพอพอดี', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 100 }); const group = await loginCheckout(page, f);
        await group.fillNames(['พอดี']); await group.payButton().click();
        const s = await state(request, f); expect(s.signups).toHaveLength(1); expect(userCredit(s, f)).toBe(f.users[0].credit_balance - f.round.credit_cost);
    });

    test('GROUP-BAS-24 ตรวจสอบเครดิตคงเหลือหลังชำระ', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 288 }); const group = await loginCheckout(page, f);
        await group.fillNames(['A','B']); await expectPaymentSummary(page, f, 2); await group.payButton().click();
        expect(userCredit(await state(request, f), f)).toBe(f.users[0].credit_balance - (2 * f.round.credit_cost));
    });

    test('GROUP-BAS-25 ตรวจสอบเครดิตไม่เพียงพอ', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 99 }); const group = await loginCheckout(page, f);
        await group.fillNames(['ไม่พอ']); await expect(page.locator('body')).toContainText('เครดิตไม่เพียงพอ'); await expect(group.payButton()).toBeDisabled();
        const s = await state(request, f); expect(s.signups).toHaveLength(0); expect(userCredit(s, f)).toBe(f.users[0].credit_balance);
    });

    test('GROUP-BAS-26 ตรวจสอบรอบที่ไม่ใช้เครดิต', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 288, credit_cost: 0 }); const group = await loginCheckout(page, f);
        await group.fillNames(['ฟรี']); await group.payButton().click(); const s = await state(request, f);
        expect(s.signups).toHaveLength(1); expect(userCredit(s, f)).toBe(f.users[0].credit_balance);
    });

    test('GROUP-BAS-27 ตรวจสอบกดยืนยันซ้ำ', async ({ page, request }) => {
        test.fail(true, 'Backend ยังไม่มี idempotency ป้องกันคำขอชำระซ้ำ');
        const f = await setup(request, { credit_balance: 500 }); await loginCheckout(page, f);
        await Promise.all([directSignup(page, f, ['คลิกครั้งเดียว']), directSignup(page, f, ['คลิกครั้งเดียว'])]);
        const s = await state(request, f); expect(s.signups).toHaveLength(1); expect(userCredit(s, f)).toBe(f.users[0].credit_balance - f.round.credit_cost);
    });

    test('GROUP-BAS-28 ตรวจสอบ Refresh หลังชำระสำเร็จ', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 500 }); const group = await loginCheckout(page, f);
        await group.fillNames(['Refresh']); await group.payButton().click(); await page.reload();
        const s = await state(request, f); expect(s.signups).toHaveLength(1); expect(userCredit(s, f)).toBe(f.users[0].credit_balance - f.round.credit_cost);
    });

    test('GROUP-BAS-29 ตรวจสอบรอบถูกปิดระหว่างกรอกข้อมูล', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 500 }); const group = await loginCheckout(page, f); await group.fillNames(['ช้า']);
        await request.post(`/__e2e/group-rounds/${f.round.id}/mutate`, { data: { status: 'closed' } }); await group.payButton().click();
        const s = await state(request, f); expect(s.signups).toHaveLength(0); expect(userCredit(s, f)).toBe(f.users[0].credit_balance);
    });

    test('GROUP-BAS-30 ตรวจสอบเครดิตถูกใช้จากอีกหน้าต่าง', async ({ page, request }) => {
        const f = await setup(request, { credit_balance: 100 }); const group = await loginCheckout(page, f); await group.fillNames(['ล่าสุด']);
        await request.post(`/__e2e/group-rounds/${f.round.id}/mutate`, { data: { credit_balance: 0 } }); await group.payButton().click();
        const s = await state(request, f); expect(s.signups).toHaveLength(0); expect(userCredit(s, f)).toBe(0);
    });

    test('GROUP-BAS-31 ตรวจสอบลงชื่อขณะที่มีที่ว่าง', async ({ page, request }) => {
        const f = await setup(request, { other_main: 4 }); const group = await loginCheckout(page, f); await group.fillNames(['ลำดับห้า']); await group.payButton().click();
        const signup = (await state(request, f)).signups.find(x => x.name === 'ลำดับห้า'); expect(signup).toMatchObject({ order: 5, reserve: false });
    });

    test('GROUP-BAS-32 ตรวจสอบคำสั่งเดียวมีทั้งตัวจริงและสำรอง', async ({ page, request }) => {
        const f = await setup(request, { other_main: 5 }); const group = await loginCheckout(page, f); await group.fillNames(['ตัวจริง','สำรอง']); await group.payButton().click();
        const s = await state(request, f); expect(s.signups.find(x=>x.name==='ตัวจริง')).toMatchObject({order:6,reserve:false}); expect(s.signups.find(x=>x.name==='สำรอง')).toMatchObject({order:7,reserve:true});
    });

    test('GROUP-BAS-33 ตรวจสอบลงชื่อเมื่อรอบเต็ม', async ({ page, request }) => {
        const f = await setup(request, { other_main: 6 }); const group = await loginCheckout(page, f); await expect(page.locator('body')).toContainText('ตอนนี้ตัวจริงเต็มแล้ว');
        await group.fillNames(['สำรองใหม่']); await group.payButton().click(); expect((await state(request, f)).signups.find(x=>x.name==='สำรองใหม่').reserve).toBe(true);
    });

    test('GROUP-BAS-34 ตรวจสอบลำดับคิวสำรองเมื่อจองพร้อมกัน', async ({ browser, request }) => {
        const f = await setup(request, { other_main: 5 }); const contexts = await Promise.all([browser.newContext(), browser.newContext()]);
        const pages = await Promise.all(contexts.map(c=>c.newPage())); await Promise.all(pages.map((p,i)=>loginCheckout(p,f,i)));
        const responses = await Promise.all(pages.map((p,i)=>directSignup(p,f,[`พร้อมกัน${i}`]))); responses.forEach(r=>expect(r.ok()).toBeTruthy());
        const added=(await state(request,f)).signups.filter(x=>x.name.startsWith('พร้อมกัน')); expect(new Set(added.map(x=>x.order)).size).toBe(2); expect(added.filter(x=>!x.reserve)).toHaveLength(1); expect(added.filter(x=>x.reserve)).toHaveLength(1);
        await Promise.all(contexts.map(c=>c.close()));
    });

    test('GROUP-BAS-35 ตรวจสอบเลื่อนสำรองเมื่อตัวจริงยกเลิก', async ({ page, request }) => {
        const f = await setup(request, { max_players: 1, booked_names: ['ตัวจริง'], reserve_names: ['สำรองแรก'] }); const group = await loginHome(page, f);
        await group.openMyBookingsFromHome(f.round); page.on('dialog', d=>d.accept()); await page.getByRole('button',{name:'ยกเลิก'}).click();
        const s=await state(request,f); expect(s.signups.find(x=>x.name==='สำรองแรก').reserve).toBe(false); expect(s.notifications[f.users[1].email]).toBe(1);
    });

    test('GROUP-BAS-36 ตรวจสอบสำรองหมดสิทธิ์หลัง Deadline', async ({ page, request }) => {
        const f = await setup(request, { max_players: 1, other_main: 1, reserve_names: ['หมดเวลา'], deadline: 'past' }); await loginCheckout(page,f,1);
        const s=await state(request,f); expect(s.signups.find(x=>x.name==='หมดเวลา').status).toBe('cancelled'); expect(userCredit(s, f, 1)).toBe(f.users[1].credit_balance + f.round.credit_cost); expect(s.notifications[f.users[1].email]).toBe(1);
    });

    test('GROUP-BAS-37 ตรวจสอบประมวลผล Deadline ซ้ำ', async ({ page, request }) => {
        const f = await setup(request, { max_players: 1, other_main: 1, reserve_names: ['ครั้งเดียว'], deadline: 'past' }); await loginCheckout(page,f,1);
        await page.reload(); await page.reload(); const s=await state(request,f);
        expect(userCredit(s, f, 1)).toBe(f.users[1].credit_balance + f.round.credit_cost); expect(s.notifications[f.users[1].email]).toBe(1);
    });

    test('GROUP-BAS-38 ตรวจสอบหน้ากลุ่มเล่นบาสที่คุณจอง', async ({ page, request }) => {
        const f=await setup(request,{max_players:1,booked_names:['ของฉัน1','ของฉัน2']}); const group = await loginHome(page,f); await group.openMyBookingsFromHome(f.round);
        await expect(page.locator('.my-booking-card')).toContainText('คุณจอง 2/5 ที่'); await expect(page.locator('.my-booking-card')).toContainText('สำรอง');
    });

    test('GROUP-BAS-39 ตรวจสอบแสดงหลายรอบ', async ({ page, request }) => {
        const first=await setup(request,{title:'รอบหนึ่ง',booked_names:['A']}); await setup(request,{preserve:true,title:'รอบสอง',booked_names:['B']}); const group = await loginHome(page,first); await group.openMyBookingsFromNavigation();
        await expect(page.locator('.my-booking-card')).toHaveCount(2); await expect(page.locator('body')).toContainText('รอบหนึ่ง'); await expect(page.locator('body')).toContainText('รอบสอง');
    });

    test('GROUP-BAS-40 ตรวจสอบข้อมูลส่วนบุคคลของผู้เล่นอื่น', async ({ page, request }) => {
        const f=await setup(request,{other_main:1,booked_names:['ของฉัน']}); const group = await loginHome(page,f); await group.openMyBookingsFromNavigation(); const body=page.locator('.my-booking-card');
        await expect(body).toContainText('บุคคลอื่น 1'); await expect(body).not.toContainText('@e2e.local'); await expect(body).not.toContainText('0890000002');
    });

    test('GROUP-BAS-41 ตรวจสอบยกเลิกตัวจริงก่อน Deadline', async ({ page, request }) => {
        const f=await setup(request,{max_players:1,credit_balance:500,booked_names:['ตัวจริง'],reserve_names:['สำรอง']}); const group = await loginHome(page,f); await group.openMyBookingsFromHome(f.round); page.on('dialog',d=>d.accept()); await page.getByRole('button',{name:'ยกเลิก'}).click();
        const s=await state(request,f); expect(userCredit(s, f)).toBe(f.users[0].credit_balance + f.round.credit_cost); expect(s.signups.find(x=>x.name==='สำรอง').reserve).toBe(false);
    });

    test('GROUP-BAS-42 ตรวจสอบยกเลิกที่จองแทนเพื่อน', async ({ page, request }) => {
        const f=await setup(request,{credit_balance:500,booked_names:['ฉัน','เพื่อน']}); const group = await loginHome(page,f); await group.openMyBookingsFromNavigation(); page.on('dialog',d=>d.accept());
        await page.locator('.my-seat-row').filter({hasText:'เพื่อน'}).getByRole('button',{name:'ยกเลิก'}).click(); const s=await state(request,f); expect(s.signups.find(x=>x.name==='ฉัน').status).toBe('confirmed'); expect(s.signups.find(x=>x.name==='เพื่อน').status).toBe('cancelled'); expect(userCredit(s, f)).toBe(f.users[0].credit_balance + f.round.credit_cost);
    });

    test('GROUP-BAS-43 ตรวจสอบยกเลิกเพียงหนึ่งจากหลายที่', async ({ page, request }) => {
        const f=await setup(request,{booked_names:['นัท','อาร์ท']}); const group = await loginHome(page,f); await group.openMyBookingsFromNavigation(); page.on('dialog',d=>d.accept()); await page.locator('.my-seat-row').filter({hasText:'นัท'}).getByRole('button',{name:'ยกเลิก'}).click();
        const s=await state(request,f); expect(s.signups.find(x=>x.name==='นัท').status).toBe('cancelled'); expect(s.signups.find(x=>x.name==='อาร์ท').status).toBe('confirmed');
    });

    test('GROUP-BAS-44 ตรวจสอบยกเลิกหลัง Deadline', async ({ page, request }) => {
        const f=await setup(request,{credit_balance:500,booked_names:['สายเกินไป'],deadline:'past'}); await loginCheckout(page,f); const signup=(await state(request,f)).signups[0];
        const token=await page.locator('main form input[name="_token"]').inputValue(); await page.request.post(`/group-rounds/${f.round.id}/signups/${signup.id}/cancel`,{form:{_token:token}}); const s=await state(request,f); expect(s.signups[0].status).toBe('confirmed'); expect(userCredit(s, f)).toBe(f.users[0].credit_balance);
    });

    test('GROUP-BAS-45 ตรวจสอบยกเลิกรายการซ้ำ', async ({ page, request }) => {
        const f=await setup(request,{credit_balance:500,booked_names:['ครั้งเดียว']}); await loginCheckout(page,f); const signup=(await state(request,f)).signups[0]; const token=await page.locator('main form input[name="_token"]').inputValue();
        await page.request.post(`/group-rounds/${f.round.id}/signups/${signup.id}/cancel`,{form:{_token:token}}); await page.request.post(`/group-rounds/${f.round.id}/signups/${signup.id}/cancel`,{form:{_token:token}}); const s=await state(request,f); expect(userCredit(s, f)).toBe(f.users[0].credit_balance + f.round.credit_cost); expect(s.signups[0].status).toBe('cancelled');
    });

    test('GROUP-BAS-46 ตรวจสอบแอดมินยกเลิกรอบที่ผู้ใช้จอง', async ({ page, request }) => {
        const f=await setup(request,{credit_balance:500,booked_names:['A','B']}); const group = await loginHome(page,f); await request.post(`/__e2e/group-rounds/${f.round.id}/mutate`,{data:{cancel_round:true}}); await group.openMyBookingsFromNavigation(); const s=await state(request,f);
        expect(s.round_status).toBe('cancelled'); expect(s.signups.every(x=>x.status==='cancelled')).toBe(true); expect(userCredit(s, f)).toBe(f.users[0].credit_balance + (2 * f.round.credit_cost)); await expect(page.locator('body')).toContainText('ยังไม่มีรายการจองกลุ่มเล่นบาส');
    });
});
