import { test, expect } from '@playwright/test';
import { PrivateTrainingPage } from './pages/private-train-m.page.js';

const image = (name, mimeType = 'image/jpeg') => ({
    name, mimeType,
    buffer: Buffer.from('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxB//9k=', 'base64'),
});

async function setup(request) {
    const response = await request.post('/__e2e/review-rating/case');
    if (!response.ok()) throw new Error(`สร้างข้อมูล REV-RAT ไม่สำเร็จ (${response.status()}): ${await response.text()}`);
    return response.json();
}

async function state(request) {
    const response = await request.get('/__e2e/review-rating/state');
    expect(response.ok()).toBeTruthy();
    return response.json();
}

async function loginAndOpenReview(page, fixture) {
    const app = new PrivateTrainingPage(page);
    await app.loginFromHome(fixture.review_user);
    await page.goto('/#member-reviews');
    await page.getByRole('link', { name: /เขียนรีวิว/ }).click();
    await expect(page).toHaveURL(/\/reviews\/create$/);
    return new ReviewPage(page, fixture);
}

class ReviewPage {
    constructor(page, fixture) {
        this.page = page;
        this.fixture = fixture;
        this.comment = page.locator('#comment');
        this.submit = page.getByRole('button', { name: 'ส่งรีวิว', exact: true });
    }
    overall(score) { return this.page.getByRole('button', { name: `ให้ ${score} ดาว`, exact: true }); }
    facility(name) { return this.page.locator('article').filter({ has: this.page.getByRole('heading', { name, exact: true }) }); }
    facilityStar(name, score) { return this.facility(name).getByRole('button', { name: `ให้ ${name} ${score} ดาว`, exact: true }); }
    facilityInput(slug) { return this.page.locator(`#facility-rating-${this.fixture.facilities[slug].id}`); }
    async fillValid(comment = 'บริการดีมาก สนามสะอาด ประทับใจครับ') {
        await this.overall(5).click();
        await this.facilityStar('คาเฟ่ & เครื่องดื่ม', 4).click();
        await this.comment.fill(comment);
    }
    async submitSuccess() {
        await this.submit.click();
        await expect(this.page).toHaveURL(/\/#member-reviews$/);
        await expect(this.page.locator('.swal2-title')).toContainText('ส่งรีวิวสำเร็จ');
    }
}

test.describe.serial('Review & Rating REV-RAT-01 ถึง REV-RAT-29', () => {
    test('REV-RAT-01 ตรวจสอบการแสดงรีวิว โดยยังไม่เข้าสู่ระบบ', async ({ page, request }) => {
        const fixture = await setup(request);
        await page.goto('/#member-reviews');
        await expect(page.locator('#member-reviews')).toBeVisible();
        await expect(page.locator('.review-card').filter({ hasText: fixture.baseline_comment })).toBeVisible();
        await expect(page.getByRole('link', { name: /เขียนรีวิว/ })).toHaveCount(0);
    });

    test('REV-RAT-02 ตรวจสอบการเข้าสู่ระบบ และดูรีวิว', async ({ page, request }) => {
        const fixture = await setup(request);
        const app = new PrivateTrainingPage(page);
        await app.loginFromHome(fixture.review_user);
        await page.goto('/#member-reviews');
        await expect(page.locator('.review-card').first()).toBeVisible();
        await expect(page.getByRole('link', { name: /เขียนรีวิว/ })).toBeVisible();
    });

    test('REV-RAT-03 ตรวจสอบการเลื่อนแถบรีวิว', async ({ page, request }) => {
        const fixture = await setup(request);
        const app = new PrivateTrainingPage(page);
        await app.loginFromHome(fixture.review_user);
        await page.goto('/#member-reviews');
        const track = page.locator('#review-track');
        const initial = await track.evaluate(element => element.scrollLeft);
        await page.getByRole('button', { name: 'เลื่อนรีวิวไปทางขวา' }).click();
        await expect.poll(() => track.evaluate(element => element.scrollLeft)).toBeGreaterThan(initial);
        await page.getByRole('button', { name: 'เลื่อนรีวิวไปทางซ้าย' }).click();
        await expect.poll(() => track.evaluate(element => element.scrollLeft)).toBeLessThanOrEqual(initial);
    });

    test('REV-RAT-04 ตรวจสอบการเปิดหน้าเขียนรีวิว', async ({ page, request }) => {
        const fixture = await setup(request);
        const review = await loginAndOpenReview(page, fixture);
        await expect(page.getByRole('heading', { name: 'เขียนรีวิว THATA HOMECOURT' })).toBeVisible();
        await expect(page.locator('#overall-rating')).toHaveValue('');
        await expect(page.getByText('ยังไม่ได้ให้คะแนน', { exact: true })).toBeVisible();
        await expect(review.facilityInput('e2e-cafe')).toBeDisabled();
    });

    test('REV-RAT-05 ตรวจสอบข้อมูลผู้รีวิว', async ({ page, request }) => {
        const fixture = await setup(request);
        await loginAndOpenReview(page, fixture);
        await expect(page.locator('#review-form').getByText(fixture.review_user.name, { exact: true })).toBeVisible();
        await expect(page.locator('#review-form').getByText('✓ สมาชิกที่เข้าสู่ระบบแล้ว')).toBeVisible();
        await expect(page.getByRole('button', { name: fixture.review_user.name })).toBeVisible();
    });

    for (const [id, score] of [['REV-RAT-06', 1], ['REV-RAT-07', 5]]) {
        test(`${id} ให้คะแนนรวม ${score} ดาว`, async ({ page, request }) => {
            const review = await loginAndOpenReview(page, await setup(request));
            await review.overall(score).click();
            await expect(page.locator('#overall-rating')).toHaveValue(String(score));
            const active = page.locator('[data-input="overall-rating"] [data-score].text-amber-400');
            await expect(active).toHaveCount(score);
        });
    }

    test('REV-RAT-08 เปลี่ยนคะแนนรวมจาก 5 เป็น 3 ดาว', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await review.overall(5).click();
        await expect(page.locator('[data-input="overall-rating"] [data-score].text-amber-400')).toHaveCount(5);
        await review.overall(3).click();
        await expect(page.locator('#overall-rating')).toHaveValue('3');
        await expect(page.locator('[data-input="overall-rating"] [data-score].text-amber-400')).toHaveCount(3);
    });

    test('REV-RAT-09 ไม่เลือกคะแนนรวม', async ({ page, request }) => {
        const fixture = await setup(request);
        const review = await loginAndOpenReview(page, fixture);
        await review.facilityStar('คาเฟ่ & เครื่องดื่ม', 4).click();
        await review.comment.fill('ข้อความทดสอบยังต้องคงอยู่หลัง validation');
        await review.submit.click();
        await expect(page.locator('#overall-rating-error')).toBeVisible();
        await expect(review.comment).toHaveValue('ข้อความทดสอบยังต้องคงอยู่หลัง validation');
        expect(await state(request)).toHaveLength(0);
    });

    test('REV-RAT-10 ให้คะแนนบริการหนึ่งรายการ', async ({ page, request }) => {
        const fixture = await setup(request);
        const review = await loginAndOpenReview(page, fixture);
        await review.overall(5).click();
        await review.facilityStar('คาเฟ่ & เครื่องดื่ม', 4).click();
        await review.comment.fill('ให้คะแนนเฉพาะคาเฟ่และเครื่องดื่ม');
        await review.submitSuccess();
        expect((await state(request))[0].ratings).toEqual({ 'คาเฟ่ & เครื่องดื่ม': 4 });
    });

    test('REV-RAT-11 ให้คะแนนหลายบริการ', async ({ page, request }) => {
        const fixture = await setup(request);
        const review = await loginAndOpenReview(page, fixture);
        await review.overall(5).click();
        await review.facilityStar('คาเฟ่ & เครื่องดื่ม', 4).click();
        await review.facilityStar('Basketball Shop', 3).click();
        await review.facilityStar('ห้องน้ำ & ห้องอาบน้ำ', 5).click();
        await review.comment.fill('ทดสอบการให้คะแนนหลายบริการพร้อมกัน');
        await review.submitSuccess();
        expect((await state(request))[0].ratings).toEqual({ 'คาเฟ่ & เครื่องดื่ม': 4, 'Basketball Shop': 3, 'ห้องน้ำ & ห้องอาบน้ำ': 5 });
    });

    test('REV-RAT-12 ให้คะแนนก่อน แล้วเลือกไม่ได้ใช้', async ({ page, request }) => {
        const fixture = await setup(request);
        const review = await loginAndOpenReview(page, fixture);
        await review.facilityStar('Basketball Shop', 3).click();
        await expect(review.facilityInput('e2e-shop')).toHaveValue('3');
        await review.facility('Basketball Shop').getByRole('button', { name: 'ไม่ได้ใช้' }).click();
        await expect(review.facilityInput('e2e-shop')).toBeDisabled();
        await expect(review.facilityInput('e2e-shop')).toHaveValue('');
    });

    test('REV-RAT-13 เปลี่ยนจากไม่ได้ใช้เป็นคะแนน', async ({ page, request }) => {
        const fixture = await setup(request);
        const review = await loginAndOpenReview(page, fixture);
        await review.facility('ห้องน้ำ & ห้องอาบน้ำ').getByRole('button', { name: 'ไม่ได้ใช้' }).click();
        await review.facilityStar('ห้องน้ำ & ห้องอาบน้ำ', 4).click();
        await expect(review.facilityInput('e2e-restroom')).toBeEnabled();
        await expect(review.facilityInput('e2e-restroom')).toHaveValue('4');
    });

    test('REV-RAT-14 ไม่ให้คะแนนทุกบริการ', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await review.overall(4).click();
        await review.comment.fill('มีความคิดเห็นแต่ไม่ได้ให้คะแนนบริการ');
        await review.submit.click();
        await expect(page.locator('#facility-rating-error')).toBeVisible();
    });

    test('REV-RAT-15 กรอกความคิดเห็นปกติ', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        const text = 'สนามสะอาด คาเฟ่บริการดีมาก 😊';
        await review.comment.fill(text);
        await expect(review.comment).toHaveValue(text);
        await expect(page.locator('#comment-count')).toHaveText(String(text.length));
    });

    for (const [id, value, message] of [
        ['REV-RAT-16', '', 'กรุณาเขียนความคิดเห็น'],
        ['REV-RAT-17', '   \n  ', 'กรุณาเขียนความคิดเห็น'],
    ]) {
        test(`${id} ตรวจสอบความคิดเห็นที่ไม่ถูกต้อง`, async ({ page, request }) => {
            const review = await loginAndOpenReview(page, await setup(request));
            await review.overall(5).click();
            await review.facilityStar('คาเฟ่ & เครื่องดื่ม', 4).click();
            await review.comment.fill(value);
            await review.submit.click();
            await expect(page.locator('#comment-error')).toHaveText(message);
            await expect(page).toHaveURL(/\/reviews\/create$/);
        });
    }

    test('REV-RAT-18 ความคิดเห็น 1,000 ตัวอักษร', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await review.comment.fill('ก'.repeat(1000));
        await expect(review.comment).toHaveValue('ก'.repeat(1000));
        await expect(page.locator('#comment-count')).toHaveText('1000');
    });

    test('REV-RAT-19 ความคิดเห็นเกิน 1,000 ตัวอักษร', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await review.comment.fill('A'.repeat(1001));
        await expect(review.comment).toHaveValue('A'.repeat(1000));
        await expect(page.locator('#comment-count')).toHaveText('1000');
    });

    test('REV-RAT-20 รองรับอักขระหลายประเภท', async ({ page, request }) => {
        const fixture = await setup(request);
        const review = await loginAndOpenReview(page, fixture);
        const text = 'บริการดี THATA 2026 🏀✨ คะแนน 10/10';
        await review.fillValid(text);
        await review.submitSuccess();
        expect((await state(request))[0].comment).toBe(text);
    });

    test('REV-RAT-21 อัปโหลดไฟล์ PNG WebP JPG', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await page.locator('#review-images').setInputFiles([image('court.png', 'image/png'), image('cafe.webp', 'image/webp'), image('shop.jpg')]);
        await expect(page.locator('#image-previews img')).toHaveCount(3);
        await expect(page.locator('#image-error')).toBeHidden();
    });

    test('REV-RAT-22 เลือกรูปภาพจำนวนสูงสุด 3 รูป', async ({ page, request }) => {
        await loginAndOpenReview(page, await setup(request));
        await page.locator('#review-images').setInputFiles([image('1.jpg'), image('2.jpg'), image('3.jpg')]);
        await expect(page.locator('#image-previews img')).toHaveCount(3);
        expect(await page.locator('#review-images').evaluate(input => input.files.length)).toBe(3);
    });

    test('REV-RAT-23 อัปโหลดเกิน 3 รูป', async ({ page, request }) => {
        await loginAndOpenReview(page, await setup(request));
        await page.locator('#review-images').setInputFiles([image('1.jpg'), image('2.jpg'), image('3.jpg'), image('4.jpg')]);
        await expect(page.locator('#image-error')).toHaveText('เลือกได้สูงสุด 3 รูป');
        await expect(page.locator('#image-previews img')).toHaveCount(0);
        expect(await page.locator('#review-images').evaluate(input => input.files.length)).toBe(0);
    });

    test('REV-RAT-24 ลบรูปที่เลือกและเลือกรูปใหม่', async ({ page, request }) => {
        await loginAndOpenReview(page, await setup(request));
        await page.locator('#review-images').setInputFiles([image('first.jpg'), image('second.jpg')]);
        await page.getByRole('button', { name: 'ลบรูป 1' }).click();
        await expect(page.locator('#image-previews img')).toHaveCount(1);
        await page.locator('#review-images').setInputFiles(image('replacement.jpg'));
        await expect(page.locator('#image-previews img')).toHaveCount(2);
    });

    test('REV-RAT-25 ส่งรีวิวโดยไม่แนบรูป', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await review.fillValid();
        await review.submitSuccess();
        expect((await state(request))[0].images).toEqual([]);
    });

    test('REV-RAT-26 ส่งรีวิวสำเร็จพร้อมรูป', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await review.fillValid('รีวิวพร้อมรูปภาพสองรูปสำหรับทดสอบ');
        await review.facilityStar('ห้องน้ำ & ห้องอาบน้ำ', 5).click();
        await page.locator('#review-images').setInputFiles([image('one.jpg'), image('two.jpg')]);
        await review.submitSuccess();
        expect((await state(request))[0].images).toHaveLength(2);
    });

    test('REV-RAT-27 ป้องกันการกดส่งซ้ำ', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await review.fillValid('ทดสอบป้องกันการส่งรีวิวซ้ำหลายครั้ง');
        let posts = 0;
        page.on('request', req => { if (req.method() === 'POST' && new URL(req.url()).pathname === '/reviews') posts += 1; });
        await page.evaluate(() => {
            const button = document.querySelector('#review-form button[type="submit"]');
            button.click();
            button.click();
        });
        await expect(page).toHaveURL(/\/#member-reviews$/);
        expect(posts).toBe(1);
        expect(await state(request)).toHaveLength(1);
    });

    test('REV-RAT-28 กรอกข้อมูลบางส่วนแล้วกดยกเลิก', async ({ page, request }) => {
        const review = await loginAndOpenReview(page, await setup(request));
        await review.overall(4).click();
        await review.facilityStar('คาเฟ่ & เครื่องดื่ม', 4).click();
        await review.comment.fill('ข้อมูลบางส่วนที่ยังไม่ได้ส่ง');
        await page.getByRole('link', { name: 'ยกเลิก', exact: true }).click();
        await expect(page).toHaveURL(/\/#facility-reviews$/);
        expect(await state(request)).toHaveLength(0);
    });

    test('REV-RAT-29 ตรวจสอบข้อมูลหลังส่ง', async ({ page, request }) => {
        const fixture = await setup(request);
        const review = await loginAndOpenReview(page, fixture);
        const comment = 'รีวิวหลังส่งต้องแสดงข้อมูลครบถ้วน 🏀';
        await review.overall(5).click();
        await review.facilityStar('คาเฟ่ & เครื่องดื่ม', 4).click();
        await review.facilityStar('Basketball Shop', 3).click();
        await review.comment.fill(comment);
        await page.locator('#review-images').setInputFiles(image('evidence.jpg'));
        await review.submitSuccess();
        const saved = (await state(request))[0];
        expect(saved).toMatchObject({ overall_rating: 5, comment, ratings: { 'คาเฟ่ & เครื่องดื่ม': 4, 'Basketball Shop': 3 } });
        expect(saved.images).toHaveLength(1);
        const card = page.locator('.review-card').filter({ hasText: comment });
        await expect(card).toContainText(fixture.review_user.name);
        await expect(card.locator('.review-images img')).toHaveCount(1);
    });
});
