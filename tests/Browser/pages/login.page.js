export class LoginPage {
    constructor(page) {
        this.page = page;

        this.heading = page.getByRole('heading', { name: 'ยินดีต้อนรับ' });
        this.emailInput = page.locator('input[name="email"]');
        this.passwordInput = page.locator('input[name="password"]');
        this.rememberCheckbox = page.locator('input[name="remember"]');
        this.loginButton = page.getByRole('button', { name: 'เข้าสู่ระบบ' });
        this.registerLink = page
            .locator('.auth-tabs')
            .getByRole('link', { name: 'สมัครสมาชิก' });
        this.forgotPasswordLink = page.getByRole('link', {
            name: 'ลืมรหัสผ่าน / รีเซ็ตรหัสผ่าน',
        });
        this.errorMessage = page.locator('.auth-error');
    }

    async goto() {
        await this.page.goto('/login');
    }

    async login(email, password, { remember = false } = {}) {
        await this.emailInput.fill(email);
        await this.passwordInput.fill(password);

        if (remember) {
            await this.rememberCheckbox.check();
        }

        await this.loginButton.click();
    }

    async openRegistration() {
        await this.registerLink.click();
    }

    async openForgotPassword() {
        await this.forgotPasswordLink.click();
    }
}
