export class GroupBasketballPage {
    constructor(page) {
        this.page = page;
        this.section = page.locator('#group-sessions');
    }

    async goto() {
        await this.page.goto('/#group-sessions');
        await this.section.scrollIntoViewIfNeeded();
    }

    card(title) {
        return this.section.locator('.gs-card').filter({ hasText: title });
    }

    async login(email, password) {
        await this.page.goto('/login');
        await this.page.locator('input[name="email"]').fill(email);
        await this.page.locator('input[name="password"]').fill(password);
        await this.page.getByRole('button', { name: 'เข้าสู่ระบบ' }).click();
    }

    async openCheckoutFromHome(round) {
        await this.goto();
        const card = this.card(round.title);
        await card.locator(`a[href$="/group-rounds/${round.id}/checkout"]`).click();
    }

    async openMyBookingsFromHome(round) {
        await this.goto();
        await this.card(round.title).locator('a[href$="/group-rounds/my-bookings"]').click();
    }

    async openMyBookingsFromNavigation() {
        await this.page.locator('a[href$="/group-rounds/my-bookings"]:visible').click();
    }

    nameInputs() {
        return this.page.locator('.gc-name-input');
    }

    async addName(value = '') {
        await this.page.locator('.gc-add-btn').click();
        const inputs = this.nameInputs();
        await inputs.nth((await inputs.count()) - 1).fill(value);
    }

    async fillNames(names) {
        const inputs = this.nameInputs();
        await inputs.first().fill(names[0] ?? '');
        for (const name of names.slice(1)) await this.addName(name);
    }

    payButton() {
        return this.page.locator('.gc-button');
    }
}
