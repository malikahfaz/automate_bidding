const { chromium } = require('playwright');
const fs = require('fs');

async function main() {
    const cookiesPath = 'storage/app/automation/cookies_ivalua.json';
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    if (fs.existsSync(cookiesPath)) {
        await context.addCookies(JSON.parse(fs.readFileSync(cookiesPath)));
    }

    await page.goto('https://t-mobile.ivalua.app/page.aspx/en/auc/auction_browse_extranet', {
        waitUntil: 'domcontentloaded',
        timeout: 60000,
    });
    await page.waitForTimeout(5000);

    const links = await page.locator('a[href*="auction"], a[href*="auc/"]').evaluateAll((els) =>
        els.map((a) => ({ href: a.href, text: a.innerText.trim().slice(0, 80) })).slice(0, 30)
    );
    console.log('Auction links:', JSON.stringify(links, null, 2));

    const title = await page.title();
    console.log('Page title:', title);

    await page.screenshot({ path: 'storage/app/public/automation/screenshots/ivalua_browse_logged_in.png', fullPage: true });
    await browser.close();
}

main().catch(console.error);
