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
        this.consentModal = page.locator('#consentModal');
        this.acceptPolicyCheckbox = page.locator('#acceptPolicy');
        this.confirmConsentButton = page.locator('#confirmConsentBtn');
        this.errorMessage = page.locator('.auth-error');
        this.fieldErrors = page.locator('.field-error.show');
        this.loginLink = page
            .locator('.auth-tabs')
            .getByRole('link', { name: 'เข้าสู่ระบบ' });
    }

    async goto() {
        await this.page.goto('/register');
    }

    async fill({ name, email, phone, password, passwordConfirmation = password }) {
        await this.nameInput.fill(name);
        await this.emailInput.fill(email);
        await this.phoneInput.fill(phone);
        await this.passwordInput.fill(password);
        await this.passwordConfirmationInput.fill(passwordConfirmation);
    }

    async submit({ acceptPolicy = true } = {}) {
        await this.registerButton.click();

        if (acceptPolicy && await this.consentModal.isVisible()) {
            await this.acceptPolicyCheckbox.check();
            await this.confirmConsentButton.click();
        }
    }

    async register(data) {
        await this.fill(data);
        await this.submit();
    }

    async openLogin() {
        await this.loginLink.click();
    }
}
