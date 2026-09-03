import { readFile } from 'node:fs/promises';

export default class GoogleSheetReporter {
    constructor() {
        this.webhookUrl = process.env.GOOGLE_SHEET_WEBHOOK_URL;
        this.secret = process.env.GOOGLE_SHEET_WEBHOOK_SECRET;
        this.pendingUpdates = [];
    }

    onTestEnd(test, result) {
        if (!this.webhookUrl || result.status === 'skipped') {
            return;
        }

        this.pendingUpdates.push({ test, result });
    }

    async onEnd() {
        for (const { test, result } of this.pendingUpdates) {
            try {
                await this.reportTest(test, result);
            } catch (error) {
                // One test's webhook call failing (network blip, Apps Script quota,
                // etc.) must not stop every later test in the run from being
                // reported at all — log it and keep going.
                console.warn(`[Google Sheet] Failed to report ${test.title}: ${error.message}`);
            }
        }
    }

    async reportTest(test, result) {

        // Supports both AUTH-01 and multi-part IDs such as GROUP-BAS-01.
        const testId = test.title.match(/\b[A-Z]+(?:-[A-Z]+)*-\d+\b/)?.[0];
        if (!testId) {
            console.warn(`[Google Sheet] No Test Case ID found in: ${test.title}`);
            return;
        }

        const screenshot = result.attachments.find(
            ({ contentType, path }) => contentType === 'image/png' && path,
        );
        const includeScreenshot = process.env.GOOGLE_SHEET_SCREENSHOTS !== 'failed'
            || result.status !== 'passed';

        const body = JSON.stringify({
            secret: this.secret,
            testId,
            status: result.status === 'passed' ? 'Passed' : 'Failed',
            durationMs: result.duration,
            error: result.error?.message ?? '',
            screenshotName: `${testId}-${Date.now()}.png`,
            screenshotBase64: screenshot && includeScreenshot
                ? (await readFile(screenshot.path)).toString('base64')
                : '',
        });

        for (let attempt = 1; attempt <= 3; attempt++) {
            try {
                const response = await fetch(this.webhookUrl, {
                    method: 'POST', headers: { 'Content-Type': 'text/plain;charset=utf-8' }, body,
                });
                if (!response.ok) throw new Error(`Google Sheet webhook returned HTTP ${response.status}`);

                const payload = await response.json();
                if (payload.ok) return;
                throw new Error(`Google Sheet update failed: ${payload.error ?? 'Unknown error'}`);
            } catch (error) {
                // A transient HTTP error (common with Apps Script web apps under
                // back-to-back calls) used to skip the retry loop entirely and
                // throw immediately — now every failure mode gets the same 3 tries.
                if (attempt === 3) throw error;
                await new Promise(resolve => setTimeout(resolve, attempt * 500));
            }
        }
    }
}
