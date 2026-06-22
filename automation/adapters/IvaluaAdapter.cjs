const fs = require('fs');
const path = require('path');

class IvaluaAdapter {
    constructor(page, cookiesPath, options = {}) {
        this.page = page;
        this.cookiesPath = cookiesPath;
        this.mockMode = options.mockMode === true;
        this.lotIdPattern = /\b([A-Z]{3,5}\d{3,})\b/;

        this.selectors = {
            loginUrl: 'https://t-mobile.ivalua.app/page.aspx/en/usr/login',
            browseUrl: 'https://t-mobile.ivalua.app/page.aspx/en/auc/auction_browse_extranet',
            emailInput: '#body_x_txtLogin, input[name="body:x:txtLogin"]',
            passwordInput: '#body_x_txtPass, input[name="body:x:txtPass"]',
            submitButton: '#body_x_btnLogin, button[name="body:x:btnLogin"]',
            loggedInIndicator: '#body_x_txtLogin',
            dismissNotificationBtn: '#btnNoPassiveMessage, button[name="btnNoPassiveMessage"]',

            title: '.auc-title, .auction-title, h2, #body_x_prxAuctionTitle',
            currentBid: '.current-bid, .current-price, #lblCurrentBid, [id*="CurrentBid"], [id*="currentBid"]',
            bidIncrement: '.bid-increment, .increment-price, #lblBidIncrement, [id*="Increment"]',
            timeRemaining: '.time-remaining, .countdown, #lblTimeRemaining, [id*="TimeRemaining"], [id*="timeRemaining"]',

            bidInput: 'input[name="bid_price"], input#txtBidAmount, input[id*="BidAmount"], input[id*="bidAmount"]',
            placeBidBtn: 'button.btn-place-bid, input#btnPlaceBid, button[id*="PlaceBid"], button[id*="btnBid"]',
            bidSuccessIndicator: '.bid-success-dialog, .message-success, [class*="success"]',
        };
    }

    async dismissPassiveNotifications() {
        try {
            const btn = this.page.locator(this.selectors.dismissNotificationBtn);
            if (await btn.count() > 0 && await btn.first().isVisible()) {
                await btn.first().click({ timeout: 3000 });
            }
        } catch (_) {
            // optional popup
        }
    }

    async isLoggedIn() {
        try {
            await this.page.goto(this.selectors.browseUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
            await this.page.waitForTimeout(2000);
            const loginField = this.page.locator(this.selectors.emailInput);
            const loginVisible = await loginField.count() > 0 && await loginField.first().isVisible();
            return !loginVisible;
        } catch (_) {
            return false;
        }
    }

    async ensureSession(email, password) {
        console.log(`[Ivalua] Checking session using cookies at: ${this.cookiesPath}`);

        if (fs.existsSync(this.cookiesPath)) {
            try {
                const cookies = JSON.parse(fs.readFileSync(this.cookiesPath));
                await this.page.context().addCookies(cookies);
                console.log('[Ivalua] Loaded cookies successfully.');
            } catch (e) {
                console.error('[Ivalua] Failed to parse cookie file:', e.message);
            }
        }

        if (await this.isLoggedIn()) {
            console.log('[Ivalua] Session is valid.');
            return true;
        }

        return await this.login(email, password);
    }

    async login(email, password) {
        console.log(`[Ivalua] Logging in with account: ${email}`);

        if (this.mockMode) {
            console.log('[Ivalua] Mock Mode - Simulating login success.');
            fs.mkdirSync(path.dirname(this.cookiesPath), { recursive: true });
            fs.writeFileSync(this.cookiesPath, JSON.stringify([{ name: 'mock_ivalua_session', value: '1', domain: 't-mobile.ivalua.app', path: '/' }]));
            return true;
        }

        await this.page.goto(this.selectors.loginUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await this.page.waitForSelector(this.selectors.emailInput, { timeout: 30000 });

        await this.page.fill(this.selectors.emailInput, email);
        await this.page.fill(this.selectors.passwordInput, password);
        await this.page.click(this.selectors.submitButton);

        try {
            await this.page.waitForFunction(
                () => {
                    const login = document.querySelector('#body_x_txtLogin');
                    return !login || login.offsetParent === null;
                },
                { timeout: 30000 }
            );
        } catch (e) {
            throw new Error(`Ivalua login failed: still on login page after submit. ${e.message}`);
        }

        await this.dismissPassiveNotifications();

        const cookies = await this.page.context().cookies();
        fs.mkdirSync(path.dirname(this.cookiesPath), { recursive: true });
        fs.writeFileSync(this.cookiesPath, JSON.stringify(cookies, null, 2));
        console.log('[Ivalua] Login successful.');
        return true;
    }

    /** Parse "23 Record(s)" footer on browse grid. */
    async getBrowseTotalRecords() {
        try {
            const text = await this.page.locator('text=/\\d+\\s*Record\\(s\\)/i').first().textContent({ timeout: 5000 });
            const match = text.match(/(\d+)\s*Record/i);
            return match ? parseInt(match[1], 10) : null;
        } catch (_) {
            return null;
        }
    }

    /** Parse one browse grid page — cell-based so AENB/AENC groups are not missed. */
    async parseBrowseTablePage() {
        return await this.page.locator('tr').evaluateAll((rows) => {
            const isLikelyGroup = (cell) => {
                if (!cell || cell.length < 3 || cell.length > 6) return false;
                if (!/^[A-Z][A-Z0-9]+$/.test(cell)) return false;
                if (/^\d+$/.test(cell)) return false;
                if (/^(Open|Closed|Ended|Paused)$/i.test(cell)) return false;
                if (/\d{1,2}\/\d{1,2}\/\d{4}/.test(cell)) return false;
                if (/t-mobile/i.test(cell)) return false;
                return true;
            };

            const results = [];

            for (const row of rows) {
                const cells = [...row.querySelectorAll('td')].map((td) =>
                    td.innerText.trim().replace(/\s+/g, ' ')
                );
                if (cells.length < 6) continue;

                const eventId = cells.find((c) => /^20\d{3}$/.test(c));
                if (!eventId) continue;

                const eventIdIdx = cells.indexOf(eventId);
                const group = cells.slice(0, eventIdIdx).find((c) => isLikelyGroup(c)) || null;
                const groupIdx = group ? cells.indexOf(group) : -1;

                let lots_count = null;
                if (groupIdx >= 0 && /^\d+$/.test(cells[groupIdx + 2] || '')) {
                    lots_count = parseInt(cells[groupIdx + 2], 10);
                }

                const datePattern = /\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}:\d{2}\s+[AP]M/i;
                const dates = cells.filter((c) => datePattern.test(c));

                results.push({
                    id: eventId,
                    group,
                    url: `https://t-mobile.ivalua.app/page.aspx/en/auc/auction_console/${eventId}`,
                    lots_count,
                    title: groupIdx >= 0 ? cells[groupIdx + 1] : null,
                    status: cells.find((c) => /^(Open|Closed|Ended|Paused)$/i.test(c)) || 'Open',
                    starts_at: dates[0] || null,
                    ends_at: dates[1] || null,
                });
            }

            return results;
        });
    }

    /** Wait until browse grid has at least one auction event row. */
    async waitForBrowseGrid() {
        try {
            await this.page.waitForFunction(
                () => {
                    for (const row of document.querySelectorAll('tr')) {
                        if (/\b20\d{3}\b/.test(row.innerText || '')) {
                            return true;
                        }
                    }
                    return false;
                },
                { timeout: 45000 }
            );
        } catch (_) {
            // continue — parse may still find rows
        }
        await this.page.waitForTimeout(2000);
    }

    /** Always land on browse page 1 before collecting (session may open on page 2). */
    async ensureBrowsePageOne() {
        const onPageOne = await this.page.evaluate(() => {
            const active = document.querySelector(
                '[class*="pager"] .active, [class*="Pager"] .active, [class*="pager"] .selected, [class*="Pager"] .selected'
            );
            if (active && (active.textContent || '').trim() === '1') {
                return true;
            }
            return false;
        });

        if (!onPageOne) {
            const clicked = await this.clickBrowsePagerLink(1);
            if (!clicked) {
                console.log('[Ivalua] Pager "1" not found — reloading browse URL for page 1...');
                await this.page.goto(this.selectors.browseUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
                await this.page.waitForTimeout(2500);
                await this.dismissPassiveNotifications();
                await this.clickBrowsePagerLink(1);
            }
        }

        await this.waitForBrowseGrid();
    }

    /** Click a page number inside the grid pager footer only (not table cells). */
    async clickBrowsePagerLink(pageNumber) {
        const clicked = await this.page.evaluate((pageNum) => {
            const target = String(pageNum);
            const pagerSelectors = [
                '[class*="pager"]',
                '[class*="Pager"]',
                '[class*="pagination"]',
                '[id*="pager"]',
                '[id*="Pager"]',
                'tfoot',
            ];

            const roots = [];
            for (const sel of pagerSelectors) {
                document.querySelectorAll(sel).forEach((el) => roots.push(el));
            }

            if (!roots.length) {
                const recordLabel = [...document.querySelectorAll('*')].find((el) =>
                    /\d+\s*Record\(s\)/i.test(el.textContent || '')
                );
                if (recordLabel && recordLabel.parentElement) {
                    roots.push(recordLabel.parentElement);
                    if (recordLabel.parentElement.parentElement) {
                        roots.push(recordLabel.parentElement.parentElement);
                    }
                }
            }

            const seen = new Set();
            for (const root of roots) {
                for (const el of root.querySelectorAll('a, button, span, li')) {
                    if (seen.has(el)) continue;
                    seen.add(el);

                    const text = (el.textContent || '').trim();
                    if (text !== target) continue;
                    if (el.offsetParent === null) continue;

                    const tag = el.tagName.toLowerCase();
                    if (tag === 'a' || tag === 'button' || el.getAttribute('onclick')) {
                        el.click();
                        return true;
                    }
                }
            }

            return false;
        }, pageNumber);

        if (clicked) {
            await this.page.waitForTimeout(3500);
            await this.waitForBrowseGrid();
            return true;
        }

        return false;
    }

    /** Read all numeric pager tabs (1, 2, 3 …) near the browse grid footer. */
    async getBrowsePagerPageNumbers() {
        const pages = await this.page.evaluate(() => {
            const nums = new Set();
            const roots = [
                ...document.querySelectorAll('[class*="pager"], [class*="Pager"], [class*="pagination"], tfoot'),
            ];

            if (!roots.length) {
                const recordLabel = [...document.querySelectorAll('*')].find((el) =>
                    /\d+\s*Record\(s\)/i.test(el.textContent || '')
                );
                if (recordLabel?.parentElement) {
                    roots.push(recordLabel.parentElement);
                }
            }

            for (const root of roots) {
                for (const el of root.querySelectorAll('a, button, span, li')) {
                    const text = (el.textContent || '').trim();
                    if (/^\d+$/.test(text)) {
                        nums.add(parseInt(text, 10));
                    }
                }
            }

            return [...nums].sort((a, b) => a - b);
        });

        return pages.length ? pages : [1];
    }

    async goToBrowsePageNumber(pageNumber) {
        return this.clickBrowsePagerLink(pageNumber);
    }

    async goToNextBrowsePage() {
        const clicked = await this.page.evaluate(() => {
            const nextLabels = ['>', '›', '>>', 'Next', 'next'];
            for (const el of document.querySelectorAll('a, button')) {
                const text = (el.textContent || '').trim();
                if (!nextLabels.includes(text)) continue;
                if (el.offsetParent === null) continue;
                el.click();
                return true;
            }
            return false;
        });

        if (clicked) {
            await this.page.waitForTimeout(3000);
            return true;
        }
        return false;
    }

    /** Walk all browse pagination pages and collect every auction event. */
    async collectBrowseConsoles() {
        const byId = new Map();

        await this.waitForBrowseGrid();
        await this.ensureBrowsePageOne();

        let totalExpected = await this.getBrowseTotalRecords();
        if (totalExpected) {
            console.log(`[Ivalua] Browse grid reports ${totalExpected} total record(s).`);
        }

        let pageNumbers = await this.getBrowsePagerPageNumbers();
        console.log(`[Ivalua] Pager pages detected: ${pageNumbers.join(', ')}`);

        // Always include page 1 first even if pager detection missed it
        if (!pageNumbers.includes(1)) {
            pageNumbers = [1, ...pageNumbers];
        }

        for (const pageNum of pageNumbers) {
            if (pageNum > 1) {
                const navigated = await this.clickBrowsePagerLink(pageNum);
                if (!navigated) {
                    console.log(`[Ivalua] Could not open browse page ${pageNum}, trying next arrow...`);
                    if (!(await this.goToNextBrowsePage())) {
                        break;
                    }
                }
            } else {
                await this.ensureBrowsePageOne();
            }

            let rows = await this.parseBrowseTablePage();

            // Retry page 1 once if grid was still loading
            if (rows.length === 0 && pageNum === 1) {
                console.log('[Ivalua] Page 1 empty on first parse — waiting and retrying...');
                await this.page.waitForTimeout(4000);
                rows = await this.parseBrowseTablePage();
            }

            let newCount = 0;
            for (const row of rows) {
                if (!byId.has(row.id)) {
                    newCount++;
                }
                byId.set(row.id, row);
            }

            console.log(
                `[Ivalua] Browse page ${pageNum}: ${rows.length} row(s), ${newCount} new — total ${byId.size}` +
                    (totalExpected ? ` / ${totalExpected}` : '')
            );

            if (totalExpected && byId.size >= totalExpected) {
                break;
            }
        }

        // Fallback: walk forward with ">" until no new rows (handles 3+ pages)
        if (totalExpected && byId.size < totalExpected) {
            console.log(`[Ivalua] Still missing records (${byId.size}/${totalExpected}) — walking with next arrow...`);
            let safety = 0;
            while (safety < 20 && byId.size < totalExpected) {
                const before = byId.size;
                if (!(await this.goToNextBrowsePage())) {
                    break;
                }
                const rows = await this.parseBrowseTablePage();
                for (const row of rows) {
                    byId.set(row.id, row);
                }
                if (byId.size === before) {
                    break;
                }
                safety++;
            }
        }

        const ids = [...byId.keys()].sort();
        const idRange = ids.length ? `${ids[0]}–${ids[ids.length - 1]}` : 'none';
        console.log(`[Ivalua] Browse collect done: ${byId.size} events (IDs ${idRange}).`);

        return [...byId.values()];
    }

    /** Fast browse-only scan (pagination, no console lot scraping). */
    async listBrowseEventsOnly() {
        await this.page.goto(this.selectors.browseUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await this.page.waitForTimeout(3000);
        await this.dismissPassiveNotifications();

        const consoles = await this.collectBrowseConsoles();
        const browseTotal = await this.getBrowseTotalRecords();
        const browseUrl = this.selectors.browseUrl;

        const events = consoles.map((meta) => ({
            external_event_id: meta.id,
            auction_group: meta.group ?? null,
            external_url: meta.url,
            browse_url: browseUrl,
            title: meta.title || (meta.group ? `Auction ${meta.group} (${meta.id})` : `Auction Event ${meta.id}`),
            lots_count: meta.lots_count ?? 0,
            starts_at: meta.starts_at ?? null,
            ends_at: meta.ends_at ?? null,
            status: (meta.status || 'Open').toLowerCase() === 'open' ? 'active' : 'paused',
        }));

        console.log(`[Ivalua] Browse watch: ${events.length} event(s) on grid${browseTotal ? ` (${browseTotal} reported total)` : ''}.`);

        return { events, browse_total: browseTotal ?? events.length };
    }

    async listCatalog(options = {}) {
        const limitConsoles = options.limitConsoles ?? 0; // 0 = all consoles on browse page
        const lots = [];

        await this.page.goto(this.selectors.browseUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await this.page.waitForTimeout(3000);
        await this.dismissPassiveNotifications();

        let consolesFromBrowse = await this.collectBrowseConsoles();

        let consoleLinks = consolesFromBrowse.map((c) => c.url);

        // Fallback: scrape console links from page if table parse found nothing
        if (consoleLinks.length === 0) {
            consoleLinks = await this.page.locator('a[href*="auction_console/"]').evaluateAll((els) => {
                const hrefs = els
                    .map((el) => el.href)
                    .filter((href) => /auction_console\/\d+/i.test(href));
                return [...new Set(hrefs)];
            });
            consolesFromBrowse = consoleLinks.map((url) => {
                const idMatch = url.match(/auction_console\/(\d+)/i);
                return {
                    id: idMatch ? idMatch[1] : null,
                    group: null,
                    url,
                    lots_count: null,
                };
            });
        }

        console.log(`[Ivalua] Found ${consoleLinks.length} auction console pages across browse pagination.`);

        const slice = limitConsoles > 0 ? consoleLinks.slice(0, limitConsoles) : consoleLinks;
        const browseUrl = this.selectors.browseUrl;

        const browseByUrl = Object.fromEntries(consolesFromBrowse.map((c) => [c.url, c]));

        const events = slice.map((url) => {
            const idMatch = url.match(/auction_console\/(\d+)/i);
            const meta = browseByUrl[url] ?? {};
            const eventId = meta.id ?? (idMatch ? idMatch[1] : null);
            const group = meta.group ?? null;
            const browseTitle = meta.title || null;

            return {
                external_event_id: eventId,
                auction_group: group,
                external_url: url,
                browse_url: browseUrl,
                title: browseTitle || (group ? `Auction ${group} (${eventId})` : `Auction Event ${eventId}`),
                lots_count: meta.lots_count ?? 0,
                starts_at: meta.starts_at ?? null,
                ends_at: meta.ends_at ?? null,
                status: (meta.status || 'Open').toLowerCase() === 'open' ? 'active' : 'paused',
            };
        });

        const eventsByUrl = Object.fromEntries(events.map((e) => [e.external_url, e]));

        for (const consoleUrl of slice) {
            console.log(`[Ivalua] Scanning console: ${consoleUrl}`);
            await this.page.goto(consoleUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
            await this.page.waitForTimeout(3000);

            const eventTitle = (await this.page.title()).replace(/: Buyer.*/i, '').trim();

            const lotIds = await this.page.locator('tr').evaluateAll((rows) => {
                const ids = [];
                const pattern = /\b([A-Z]{3,5}\d{3,})\b/;
                for (const row of rows) {
                    const match = row.innerText.match(pattern);
                    if (match && !ids.includes(match[1])) {
                        ids.push(match[1]);
                    }
                }
                return ids;
            });

            console.log(`[Ivalua] Found ${lotIds.length} lots in ${consoleUrl}`);

            const pageLots = await this.parseAllLotsFromPage();
            for (const lot of pageLots) {
                lots.push({
                    external_url: consoleUrl,
                    external_lot_id: lot.lot_id,
                    title: lot.title,
                    current_bid: lot.current_bid,
                    bid_increment: lot.bid_increment,
                    time_remaining: lot.time_remaining,
                    ends_at: lot.ends_at,
                    status: lot.status,
                    auction_event: eventTitle,
                });
            }

            const eventRow = eventsByUrl[consoleUrl];
            if (eventRow && !eventRow.auction_group && pageLots.length > 0) {
                const lotGroup = pageLots[0].lot_id.match(/^([A-Z]{4})\d/);
                if (lotGroup) {
                    eventRow.auction_group = lotGroup[1];
                    if (!eventRow.title || eventRow.title.startsWith('Auction Event')) {
                        eventRow.title = `Auction ${lotGroup[1]} (${eventRow.external_event_id})`;
                    }
                }
            }
        }

        return { events: Object.values(eventsByUrl), lots, consoles_found: consoleLinks.length, browse_total: consolesFromBrowse.length };
    }

    /**
     * Parse all lot rows from the current auction_console page in one DOM pass.
     */
    async parseAllLotsFromPage(filterLotIds = null) {
        return await this.page.locator('tr').evaluateAll((rows, filterIds) => {
            const filter = filterIds && filterIds.length ? new Set(filterIds) : null;
            const results = [];
            const pattern = /\b([A-Z]{3,5}\d{3,})\b/;

            for (const row of rows) {
                const lotMatch = row.innerText.match(pattern);
                if (!lotMatch) continue;

                const lotId = lotMatch[1];
                if (filter && !filter.has(lotId)) continue;

                const cells = [...row.querySelectorAll('td')].map((td) =>
                    td.innerText.trim().replace(/\s+/g, ' ')
                );
                const rowText = cells.join(' ');

                let title = lotId;
                const lotIdIndex = cells.findIndex((c) => c === lotId || c.includes(lotId));
                if (lotIdIndex >= 0 && cells[lotIdIndex + 1]) {
                    title = cells[lotIdIndex + 1].substring(0, 250);
                }

                const priceMatches = rowText.match(/([\d,]+\.\d{2})/g) || [];
                const prices = priceMatches.map((p) => parseFloat(p.replace(/,/g, '')));
                const startingPrice = prices[0] ?? 0;
                const referencePrice = prices[1] ?? 0;

                const extendedBidMatch = rowText.match(/([\d,]+\.\d{2})\(Unit/i);
                const current_bid = extendedBidMatch
                    ? parseFloat(extendedBidMatch[1].replace(/,/g, ''))
                    : startingPrice;

                const endDateMatch = rowText.match(
                    /(\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}:\d{2}\s+[AP]M)/i
                );

                results.push({
                    lot_id: lotId,
                    title,
                    current_bid,
                    bid_increment: referencePrice > 0 ? referencePrice : 1,
                    time_remaining: endDateMatch ? endDateMatch[1] : '',
                    ends_at: endDateMatch ? new Date(endDateMatch[1]).toISOString() : null,
                    status: 'active',
                });
            }

            return results;
        }, filterLotIds || []);
    }

    /**
     * Bulk sync: one login, visit each console URL once, scrape all lots per page.
     */
    async bulkSyncConsoles(consoles) {
        const allLots = [];

        for (const entry of consoles) {
            const url = entry.url;
            const lotIds = entry.lot_ids || [];

            console.log(`[Ivalua] Bulk sync console: ${url} (${lotIds.length} tracked lots)`);
            await this.page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
            await this.page.waitForTimeout(2000);

            const lots = await this.parseAllLotsFromPage(lotIds.length ? lotIds : null);
            for (const lot of lots) {
                allLots.push({
                    ...lot,
                    external_url: url,
                    external_lot_id: lot.lot_id,
                });
            }

            console.log(`[Ivalua] Scraped ${lots.length} lots from ${url}`);
        }

        return { lots: allLots, consoles_synced: consoles.length };
    }

    async readAuctionState(url, lotId = null) {
        console.log(`[Ivalua] Reading state for: ${url}${lotId ? ` (lot: ${lotId})` : ''}`);

        if (this.mockMode) {
            console.log('[Ivalua] Mock Mode - Returning simulated auction state.');
            const baseBid = 24500.00;
            const inc = 250.00;
            const randomAdd = Math.floor(Date.now() / 10000) % 6 * inc;
            return {
                title: 'T-Mobile Ivalua Auction: Apple iPhone 15 Pro Max Bulk Lot (50 units)',
                current_bid: baseBid + randomAdd,
                bid_increment: inc,
                time_remaining: '2h ' + (60 - (Math.floor(Date.now() / 1000) % 60)) + 'm',
                ends_at: new Date(Date.now() + 2 * 3600 * 1000).toISOString(),
                status: 'active',
            };
        }

        await this.page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await this.page.waitForTimeout(3000);

        if (url.includes('auction_console') && lotId) {
            return await this.readConsoleLotState(lotId);
        }

        try {
            const title = await this.page.locator(this.selectors.title).first().innerText({ timeout: 10000 });
            const currentBidText = await this.page.locator(this.selectors.currentBid).first().innerText({ timeout: 10000 });
            const bidIncrementText = await this.page.locator(this.selectors.bidIncrement).first().innerText({ timeout: 10000 });
            const timeRemaining = await this.page.locator(this.selectors.timeRemaining).first().innerText({ timeout: 10000 });

            const current_bid = parseFloat(currentBidText.replace(/[^0-9.]/g, ''));
            const bid_increment = parseFloat(bidIncrementText.replace(/[^0-9.]/g, ''));

            return {
                title: title.trim(),
                current_bid,
                bid_increment,
                time_remaining: timeRemaining.trim(),
                status: 'active',
            };
        } catch (e) {
            throw new Error(`Failed to parse Ivalua page selectors: ${e.message}`);
        }
    }

    async readConsoleLotState(lotId) {
        const row = this.page.locator('tr').filter({ hasText: lotId }).first();
        await row.waitFor({ timeout: 15000 });

        const cells = (await row.locator('td').allInnerTexts()).map((c) => c.trim().replace(/\s+/g, ' '));
        const rowText = cells.join(' ');

        let title = lotId;
        const lotIdIndex = cells.findIndex((c) => c === lotId || c.includes(lotId));
        if (lotIdIndex >= 0 && cells[lotIdIndex + 1]) {
            title = cells[lotIdIndex + 1].substring(0, 250);
        }

        const priceMatches = rowText.match(/([\d,]+\.\d{2})/g) || [];
        const prices = priceMatches.map((p) => parseFloat(p.replace(/,/g, '')));
        const startingPrice = prices[0] ?? 0;
        const referencePrice = prices[1] ?? 0;

        const extendedBidMatch = rowText.match(/([\d,]+\.\d{2})\(Unit/i);
        const current_bid = extendedBidMatch
            ? parseFloat(extendedBidMatch[1].replace(/,/g, ''))
            : startingPrice;

        const endDateMatch = rowText.match(/(\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}:\d{2}\s+[AP]M)/i);

        return {
            title,
            current_bid,
            bid_increment: referencePrice > 0 ? referencePrice : 1,
            time_remaining: endDateMatch ? endDateMatch[1] : '',
            ends_at: endDateMatch ? new Date(endDateMatch[1]).toISOString() : null,
            status: 'active',
            lot_id: lotId,
        };
    }

    async placeBid(url, amount, lotId = null, password = null) {
        console.log(`[Ivalua] Placing bid of ${amount} on ${url}${lotId ? ` (lot: ${lotId})` : ''}`);

        if (this.mockMode) {
            console.log('[Ivalua] Mock Mode - Simulating successful bid placement.');
            return {
                success: true,
                tx_id: 'iv_mock_tx_' + Math.floor(Math.random() * 1000000),
                current_bid: amount,
                message: 'Bid placed successfully in Mock mode.',
            };
        }

        await this.page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await this.page.waitForTimeout(2500);
        await this.dismissPassiveNotifications();

        if (url.includes('auction_console') && lotId) {
            const row = await this.findConsoleLotRow(lotId);
            await row.scrollIntoViewIfNeeded();
            await this.page.waitForTimeout(500);

            const bidLink = row.getByRole('link', { name: /^Bid$/i }).or(row.locator('a').filter({ hasText: /^Bid$/i }));
            if (await bidLink.count() > 0) {
                await bidLink.first().click({ timeout: 10000 });
            } else {
                await row.locator('a, button').filter({ hasText: /Bid/i }).first().click({ timeout: 10000 });
            }
            await this.page.waitForTimeout(2000);

            const bidInput = this.page.locator(
                `input[id^="mass_bid_"][id*="${lotId}"], input[name^="mass_bid_up"], input[id*="mass_bid"]`
            ).first();
            await bidInput.waitFor({ timeout: 15000 });
            await bidInput.click();
            await bidInput.fill('');
            await bidInput.fill(String(amount));

            if (password) {
                const passSelectors = [
                    '#password',
                    'input[name="password"][type="password"]',
                    'input[id*="password" i][type="password"]',
                    'input[id*="Password" i][type="password"]',
                ];
                for (const sel of passSelectors) {
                    const passField = this.page.locator(sel).first();
                    if (await passField.count() > 0 && await passField.isVisible()) {
                        await passField.fill(password);
                        break;
                    }
                }
            }

            const submitBtn = this.page.locator('#btnBid, input#btnBid, button#btnBid, [id*="btnBid"]').first();
            await submitBtn.waitFor({ state: 'visible', timeout: 10000 });
            await submitBtn.click({ timeout: 10000 });
            await this.page.waitForTimeout(2000);

            await this.confirmBidDialogs();

            await this.page.waitForTimeout(2500);

            const pageError = await this.page.locator('.error, .alert-danger, [class*="error"], [class*="Error"]').first();
            if (await pageError.count() > 0 && await pageError.isVisible()) {
                const errText = (await pageError.innerText()).trim();
                if (errText) {
                    throw new Error(`Ivalua rejected bid: ${errText.substring(0, 200)}`);
                }
            }

            const updatedState = await this.readConsoleLotState(lotId);

            if (updatedState.current_bid < amount - 0.01) {
                throw new Error(
                    `Bid may not have registered on Ivalua. Expected >= ${amount}, saw ${updatedState.current_bid}`
                );
            }

            return {
                success: true,
                current_bid: updatedState.current_bid,
                lot_id: lotId,
                message: 'Bid confirmed on Ivalua lot.',
            };
        }

        try {
            await this.page.fill(this.selectors.bidInput, amount.toString());
            await this.page.click(this.selectors.placeBidBtn);
            await this.confirmBidDialogs();
            await this.page.waitForSelector(this.selectors.bidSuccessIndicator, { timeout: 15000 });

            const updatedState = await this.readAuctionState(url);

            return {
                success: true,
                current_bid: updatedState.current_bid,
                message: 'Bid confirmed.',
            };
        } catch (e) {
            throw new Error(`Ivalua bid placement failed: ${e.message}`);
        }
    }

    /** Find lot row on console — searches paginated lot tables. */
    async findConsoleLotRow(lotId) {
        for (let pageNum = 1; pageNum <= 30; pageNum++) {
            if (pageNum > 1) {
                const moved = await this.clickBrowsePagerLink(pageNum);
                if (!moved) {
                    break;
                }
            }

            const row = this.page.locator('tr').filter({ hasText: lotId }).first();
            if (await row.count() > 0) {
                try {
                    await row.waitFor({ state: 'visible', timeout: 8000 });
                    return row;
                } catch (_) {
                    // try next console page
                }
            }
        }

        throw new Error(`Lot ${lotId} not found on console page ${this.page.url()}`);
    }

    async confirmBidDialogs() {
        const confirmSelectors = [
            '#btnConfirm',
            'button[name="btnConfirm"]',
            'input[value="Confirm"]',
            'button:has-text("Confirm")',
            'a:has-text("Confirm")',
        ];

        for (let attempt = 0; attempt < 3; attempt++) {
            let clicked = false;
            for (const sel of confirmSelectors) {
                try {
                    const btn = this.page.locator(sel).first();
                    if (await btn.count() > 0 && await btn.isVisible()) {
                        await btn.click({ timeout: 3000 });
                        clicked = true;
                        await this.page.waitForTimeout(1500);
                        break;
                    }
                } catch (_) {
                    // optional dialog
                }
            }
            if (!clicked) {
                break;
            }
        }
    }
}

module.exports = IvaluaAdapter;
