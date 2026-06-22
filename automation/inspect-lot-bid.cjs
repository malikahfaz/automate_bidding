const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function main() {
    const lotId = process.argv[2] || 'MIXEA992945';
    const consoleUrl = process.argv[3] || 'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_console/20053';
    const cookiesPath = path.join(__dirname, '..', 'storage', 'app', 'automation', 'cookies_ivalua.json');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1400, height: 900 } });
    const page = await context.newPage();

    if (fs.existsSync(cookiesPath)) {
        await context.addCookies(JSON.parse(fs.readFileSync(cookiesPath, 'utf8')));
    }

    await page.goto(consoleUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(4000);

    const row = page.locator('tr').filter({ hasText: lotId }).first();
    await row.waitFor({ timeout: 15000 });
    await row.scrollIntoViewIfNeeded();

    const bidCandidates = await row.evaluate((tr) => {
        const out = [];
        for (const el of tr.querySelectorAll('a, button, span, input, div')) {
            const text = (el.innerText || el.value || '').trim();
            if (/^bid$/i.test(text) || (el.id && /bid/i.test(el.id)) || (el.className && /btn/i.test(el.className) && /bid/i.test(text))) {
                out.push({
                    tag: el.tagName,
                    id: el.id,
                    name: el.name,
                    className: el.className,
                    text: text.substring(0, 40),
                    href: el.getAttribute('href'),
                    onclick: el.getAttribute('onclick') ? 'yes' : null,
                    visible: el.offsetParent !== null,
                });
            }
        }
        return out;
    });
    console.log('BID_CANDIDATES', JSON.stringify(bidCandidates, null, 2));

    const bidEl = row.locator('a').filter({ hasText: /^\s*Bid\s*$/i }).first();
    console.log('CLICK_COUNT', await bidEl.count());

    if (await bidEl.count() > 0) {
        await bidEl.click({ timeout: 10000 });
        await page.waitForTimeout(3000);
    }

    const modalVisible = await page.locator('#newOffer, #body_x_biddingdiv').first().isVisible().catch(() => false);
    console.log('MODAL_VISIBLE', modalVisible);

    const after = await page.evaluate(() => {
        const inputs = [...document.querySelectorAll('input:not([type="hidden"])')].map((el) => ({
            type: el.type,
            id: el.id,
            name: el.name,
            value: el.value,
            visible: el.offsetParent !== null,
        }));
        const dialogs = [...document.querySelectorAll('[role="dialog"], .modal, .popup, [class*="dialog"], [class*="modal"]')].map((el) => ({
            id: el.id,
            className: el.className,
            text: el.innerText.substring(0, 200),
            visible: el.offsetParent !== null,
        }));
        const buttons = [...document.querySelectorAll('span, button, input[type="submit"], a')].filter((el) => {
            const t = (el.innerText || el.value || '').trim();
            return /bid|confirm|submit|place/i.test(t + el.id);
        }).map((el) => ({
            tag: el.tagName,
            id: el.id,
            text: (el.innerText || el.value || '').trim().substring(0, 40),
            visible: el.offsetParent !== null,
        }));
        return { url: location.href, inputs, dialogs, buttons };
    });

    console.log('AFTER_CLICK', JSON.stringify(after, null, 2));

    const shotDir = path.join(__dirname, '..', 'storage', 'app', 'public', 'automation', 'screenshots');
    fs.mkdirSync(shotDir, { recursive: true });
    await page.screenshot({ path: path.join(shotDir, 'inspect_lot_bid_after.png'), fullPage: true });

    await browser.close();
}

main().catch((e) => {
    console.error(e);
    process.exit(1);
});
