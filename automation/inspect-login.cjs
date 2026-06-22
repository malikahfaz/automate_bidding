const { chromium } = require('playwright');

async function main() {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    const urls = [
        'https://t-mobile.ivalua.app/page.aspx/en/usr/login',
        'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_browse_extranet',
    ];

    for (const url of urls) {
        console.log('\n=== URL:', url, '===');
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await page.waitForTimeout(5000);

        const inputs = await page.locator('input').evaluateAll((els) =>
            els.map((el) => ({
                type: el.type,
                name: el.name,
                id: el.id,
                placeholder: el.placeholder,
                className: el.className,
            }))
        );
        console.log('Inputs:', JSON.stringify(inputs, null, 2));

        const buttons = await page.locator('button, input[type="submit"]').evaluateAll((els) =>
            els.map((el) => ({
                tag: el.tagName,
                type: el.type,
                name: el.name,
                id: el.id,
                value: el.value,
                text: el.innerText?.trim?.() || el.value,
            }))
        );
        console.log('Buttons:', JSON.stringify(buttons, null, 2));
    }

    await browser.close();
}

main().catch((e) => {
    console.error(e);
    process.exit(1);
});
