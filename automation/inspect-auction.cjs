const { chromium } = require('playwright');
const fs = require('fs');

async function main() {
    const url = process.argv[2] || 'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_console/20027';
    const cookiesPath = 'storage/app/automation/cookies_ivalua.json';
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    if (fs.existsSync(cookiesPath)) {
        await context.addCookies(JSON.parse(fs.readFileSync(cookiesPath)));
    }

    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(5000);

    console.log('Title:', await page.title());

    const textBlocks = await page.locator('span, label, td, th, div').evaluateAll((els) =>
        els
            .map((el) => el.innerText?.trim())
            .filter((t) => t && /bid|increment|time|price|amount|\$/i.test(t))
            .slice(0, 40)
    );
    console.log('Bid-related text:', JSON.stringify([...new Set(textBlocks)], null, 2));

    const inputs = await page.locator('input:not([type="hidden"])').evaluateAll((els) =>
        els.map((el) => ({ type: el.type, name: el.name, id: el.id, value: el.value }))
    );
    console.log('Inputs:', JSON.stringify(inputs, null, 2));

    const buttons = await page.locator('button, input[type="submit"]').evaluateAll((els) =>
        els
            .map((el) => ({ id: el.id, name: el.name, text: (el.innerText || el.value || '').trim() }))
            .filter((b) => /bid|place|submit|confirm/i.test(b.text + b.id + b.name))
    );
    console.log('Bid buttons:', JSON.stringify(buttons, null, 2));

    await page.screenshot({ path: 'storage/app/public/automation/screenshots/ivalua_auction_detail.png', fullPage: true });
    await browser.close();
}

main().catch(console.error);
