export class RegisterPage {
    constructor(page) {
        this.page = page;

        this.heading = page.getByRole('heading', { name: 'ยินดีต้อนรับ' });
        this.nameInput = page.locator('input[name="name"]');
        this.emailInput = page.locator('input[name="email"]');
        this.phoneInput = page.locator('input[name="phone"]');
        this.passwordInput = page.locator('input[name="password"]');
        this.passwordConfirmationInput = page.locator(
            'input[name="password_confirmation"]',
        );
        this.registerButton = page.getByRole('button', { name: 'สมัครสมาชิก' });
        this.loginLink = page
            .locator('.auth-tabs')
            .getByRole('link', { name: 'เข้าสู่ระบบ' });
    }

    async goto() {
        await this.page.goto('/register');
    }

    async register({ name, email, phone, password }) {
        await this.nameInput.fill(name);
        await this.emailInput.fill(email);
        await this.phoneInput.fill(phone);
        await this.passwordInput.fill(password);
        await this.passwordConfirmationInput.fill(password);
        await this.registerButton.click();
    }

    async openLogin() {
        await this.loginLink.click();
    }
}
