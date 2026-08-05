import { readFile } from 'node:fs/promises';

export default class GoogleSheetReporter {
    constructor() {
        this.webhookUrl = process.env.GOOGLE_SHEET_WEBHOOK_URL;
        this.secret = process.env.GOOGLE_SHEET_WEBHOOK_SECRET;
        this.pendingUpdates = [];
    }

    onTestEnd(test, result) {
        if (!this.webhookUrl) {
            return;
        }

        this.pendingUpdates.push(this.reportTest(test, result));
    }

    async onEnd() {
        await Promise.all(this.pendingUpdates);
    }

    async reportTest(test, result) {

        const testId = test.title.match(/\b[A-Z]+-\d+\b/)?.[0];
        if (!testId) {
            console.warn(`[Google Sheet] No Test Case ID found in: ${test.title}`);
            return;
        }

        const screenshot = result.attachments.find(
            ({ contentType, path }) => contentType === 'image/png' && path,
        );

        const response = await fetch(this.webhookUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'text/plain;charset=utf-8' },
            body: JSON.stringify({
                secret: this.secret,
                testId,
                status: result.status === 'passed' ? 'Passed' : 'Failed',
                durationMs: result.duration,
                error: result.error?.message ?? '',
                screenshotName: `${testId}-${Date.now()}.png`,
                screenshotBase64: screenshot
                    ? (await readFile(screenshot.path)).toString('base64')
                    : '',
            }),
        });

        if (!response.ok) {
            throw new Error(`Google Sheet webhook returned HTTP ${response.status}`);
        }
    }
}
