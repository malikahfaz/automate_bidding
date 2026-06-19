const { chromium } = require('playwright');
const parseArgs = require('minimist');
const path = require('path');
const fs = require('fs');

const BStockAdapter = require('./adapters/BStockAdapter.cjs');
const IvaluaAdapter = require('./adapters/IvaluaAdapter.cjs');

async function main() {
    const argv = parseArgs(process.argv.slice(2));

    const action = argv.action; // login, sync, place-bid
    const platform = argv.platform; // bstock, ivalua
    const email = argv.email;
    const password = argv.password;
    const url = argv.url;
    const amount = argv.amount ? parseFloat(argv.amount) : null;
    const cookiesPath = argv['cookies-path'] || path.join(__dirname, '..', 'storage', 'app', 'automation', `cookies_${platform}.json`);
    const screenshotPath = argv['screenshot-path'] || path.join(__dirname, '..', 'storage', 'app', 'automation', 'screenshots', `${platform}_${Date.now()}.png`);

    if (!action || !platform) {
        console.log(JSON.stringify({
            success: false,
            error: 'Missing required arguments --action and --platform'
        }));
        process.exit(1);
    }

    let browser = null;
    let page = null;
    let adapter = null;

    try {
        // Create screenshot directory if it doesn't exist
        fs.mkdirSync(path.dirname(screenshotPath), { recursive: true });

        // Launch playwright browser
        browser = await chromium.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });

        // Create browser context
        const context = await browser.newContext({
            viewport: { width: 1280, height: 800 },
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        });

        page = await context.newPage();

        // Initialize adapter
        if (platform === 'bstock') {
            adapter = new BStockAdapter(page, cookiesPath);
        } else if (platform === 'ivalua') {
            adapter = new IvaluaAdapter(page, cookiesPath);
        } else {
            throw new Error(`Unsupported platform: ${platform}`);
        }

        let resultData = null;

        // Execute action
        if (action === 'login') {
            if (!email || !password) {
                throw new Error('Email and password required for login action');
            }
            const loginSuccess = await adapter.login(email, password);
            resultData = { success: loginSuccess };
        } else if (action === 'sync') {
            if (!url) {
                throw new Error('URL required for sync action');
            }
            if (email && password) {
                await adapter.ensureSession(email, password);
            }
            resultData = await adapter.readAuctionState(url);
        } else if (action === 'place-bid') {
            if (!url || !amount) {
                throw new Error('URL and amount required for place-bid action');
            }
            if (!email || !password) {
                throw new Error('Email and password required for place-bid action');
            }
            await adapter.ensureSession(email, password);
            resultData = await adapter.placeBid(url, amount);
        } else {
            throw new Error(`Unsupported action: ${action}`);
        }

        // Output success
        console.log(JSON.stringify({
            success: true,
            data: resultData
        }));

    } catch (err) {
        // Take screenshot on failure
        let screenshotSaved = null;
        if (page && !page.isClosed()) {
            try {
                await page.screenshot({ path: screenshotPath });
                screenshotSaved = screenshotPath;
            } catch (screenshotErr) {
                console.error('Failed to take screenshot:', screenshotErr.message);
            }
        }

        // Output error JSON
        console.log(JSON.stringify({
            success: false,
            error: err.message,
            screenshot: screenshotSaved
        }));
    } finally {
        if (browser) {
            await browser.close();
        }
    }
}

main();
