const fs = require('fs');
const path = require('path');

class IvaluaAdapter {
    constructor(page, cookiesPath, options = {}) {
        this.page = page;
        this.cookiesPath = cookiesPath;
        this.mockMode = options.mockMode === true;
        this.verboseLog = options.verboseLog === true;
        this.lotIdPattern = /\b([A-Z]{3,5}\d{3,})\b/;

        this.bidLog = [];

        this._sessionEmail = null;
        this._sessionPassword = null;

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

    logBid(step, meta = {}) {
        if (!this.verboseLog) {
            return;
        }
        const entry = { step, ...meta, at: new Date().toISOString() };
        this.bidLog.push(entry);
        const detail = Object.keys(meta).length ? ` ${JSON.stringify(meta)}` : '';
        console.error(`[Ivalua-BID] ${step}${detail}`);
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

    isAuthFailureUrl(url = '') {
        return /\/usr\/(login|logout)/i.test(url) || /invalid_cookie|reason=/i.test(url);
    }

    async pageShowsAuthFailure() {
        const url = this.page.url();
        if (this.isAuthFailureUrl(url)) {
            return true;
        }
        try {
            const body = await this.page.locator('body').innerText({ timeout: 5000 });
            return /session expired|logged out|please log in again|you are logged out/i.test(body);
        } catch (_) {
            return false;
        }
    }

    async reloginIfNeeded() {
        if (!this._sessionEmail || !this._sessionPassword) {
            throw new Error('Ivalua session expired. Master credentials required to re-login.');
        }
        console.log('[Ivalua] Session expired on page — re-logging in.');
        await this.login(this._sessionEmail, this._sessionPassword);
    }

    async gotoAuthenticated(url, options = {}) {
        const waitUntil = options.waitUntil || 'domcontentloaded';
        const timeout = options.timeout || 60000;
        const settleMs = options.settleMs ?? 3000;

        await this.page.goto(url, { waitUntil, timeout });
        await this.page.waitForTimeout(settleMs);

        if (await this.pageShowsAuthFailure()) {
            await this.reloginIfNeeded();
            await this.page.goto(url, { waitUntil, timeout });
            await this.page.waitForTimeout(settleMs);
        }

        if (await this.pageShowsAuthFailure()) {
            throw new Error('Ivalua session could not be restored after re-login.');
        }
    }

    async isLoggedIn() {
        try {
            await this.page.goto(this.selectors.browseUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
            await this.page.waitForTimeout(2000);

            if (await this.pageShowsAuthFailure()) {
                return false;
            }

            const loginField = this.page.locator(this.selectors.emailInput);
            const loginVisible = await loginField.count() > 0 && await loginField.first().isVisible();
            return !loginVisible;
        } catch (_) {
            return false;
        }
    }

    async ensureSession(email, password) {
        console.log(`[Ivalua] Checking session using cookies at: ${this.cookiesPath}`);

        this._sessionEmail = email;
        this._sessionPassword = password;

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
     * Ivalua v5 puts many lots in one <tr> — parse each lot block by Lot ID cell position.
     */
    async scrapeConsoleLotsFromPage(filterLotIds = null) {
        return await this.page.locator('tr').evaluateAll((rows, filterIds) => {
            const filter = filterIds && filterIds.length ? new Set(filterIds) : null;
            const lotIdPattern = /^[A-Z]{3,5}\d{3,}$/;
            const results = [];
            const seen = new Set();

            const parseBlock = (cells, lotId, startIdx) => {
                const name = cells[startIdx + 1] || lotId;
                const grade = cells[startIdx + 2] || '';
                const qty = cells[startIdx + 3] || '';
                const location = cells[startIdx + 4] || '';
                const carrier = cells[startIdx + 5] || '';
                const oem = cells[startIdx + 6] || '';
                const condition = cells[startIdx + 7] || '';
                const category = cells[startIdx + 10] || '';
                const timeRem = cells[startIdx + 11] || '';

                const blockSlice = cells.slice(startIdx + 13, startIdx + 22);
                const blockText = blockSlice.join(' ');

                let startingPrice = 0;
                let increment = 0;
                for (const part of blockSlice) {
                    const p = part.match(/^([\d,]+\.\d{2})$/);
                    if (!p) {
                        continue;
                    }
                    const val = parseFloat(p[1].replace(/,/g, ''));
                    if (startingPrice === 0) {
                        startingPrice = val;
                    } else if (increment === 0) {
                        increment = val;
                        break;
                    }
                }

                const extMatch = blockText.match(/([\d,]+\.\d{2})\(Unit/i);
                const current_bid = extMatch
                    ? parseFloat(extMatch[1].replace(/,/g, ''))
                    : startingPrice;

                const endMatches = blockText.match(
                    /(\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}:\d{2}\s+[AP]M)/gi
                );
                const endDate = endMatches ? endMatches[endMatches.length - 1] : '';

                const descriptionParts = [];
                if (grade) descriptionParts.push(`Grade ${grade}`);
                if (qty) descriptionParts.push(`Qty ${qty}`);
                if (location) descriptionParts.push(location);
                if (carrier) descriptionParts.push(carrier);
                if (oem) descriptionParts.push(oem);
                if (condition) descriptionParts.push(condition);
                if (category) descriptionParts.push(category);

                return {
                    lot_id: lotId,
                    title: name,
                    description: descriptionParts.join(' · '),
                    quantity: parseInt(qty, 10) || null,
                    cosmetic_grade: grade || null,
                    current_bid,
                    bid_increment: increment > 0 ? increment : 1,
                    time_remaining: endDate || timeRem,
                    ends_at: endDate ? new Date(endDate).toISOString() : null,
                    status: 'active',
                };
            };

            for (const row of rows) {
                const cells = [...row.querySelectorAll('td')].map((td) =>
                    td.innerText.trim().replace(/\s+/g, ' ')
                );

                for (let i = 0; i < cells.length; i++) {
                    const cell = cells[i];
                    if (!cell || !lotIdPattern.test(cell)) {
                        continue;
                    }
                    const lotId = cell;
                    if (filter && !filter.has(lotId)) {
                        continue;
                    }
                    if (seen.has(lotId)) {
                        continue;
                    }
                    seen.add(lotId);
                    results.push(parseBlock(cells, lotId, i));
                }
            }

            return results;
        }, filterLotIds || []);
    }

    /**
     * Parse all lot rows from the current auction_console page in one DOM pass.
     */
    async parseAllLotsFromPage(filterLotIds = null) {
        return await this.scrapeConsoleLotsFromPage(filterLotIds);
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
            await this.gotoAuthenticated(url, { settleMs: 2000 });

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

        await this.gotoAuthenticated(url, { settleMs: 3000 });

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
        const lots = await this.scrapeConsoleLotsFromPage([lotId]);
        const lot = lots.find((entry) => entry.lot_id === lotId);

        if (!lot) {
            throw new Error(`Lot ${lotId} not found on console page ${this.page.url()}`);
        }

        return {
            title: lot.title,
            description: lot.description,
            quantity: lot.quantity,
            cosmetic_grade: lot.cosmetic_grade,
            current_bid: lot.current_bid,
            bid_increment: lot.bid_increment,
            time_remaining: lot.time_remaining,
            ends_at: lot.ends_at,
            status: lot.status,
            lot_id: lotId,
        };
    }

    /** Enable mass-bid inputs on auction_console (often hidden until toggled). */
    async enableConsoleMassBidMode() {
        const toggles = [
            'input[id*="MassBid" i][type="checkbox"]',
            'input[name*="MassBid" i][type="checkbox"]',
            '#chkMassBidUp',
            'input[id*="mass_bid" i][type="checkbox"]',
            'label:has-text("Mass Bid")',
            'a:has-text("Mass Bid")',
            'button:has-text("Mass Bid")',
        ];

        for (const sel of toggles) {
            try {
                const el = this.page.locator(sel).first();
                if (await el.count() === 0) {
                    continue;
                }

                const tag = await el.evaluate((node) => node.tagName.toLowerCase());
                if (tag === 'input') {
                    const checked = await el.isChecked().catch(() => false);
                    if (!checked) {
                        await el.check({ force: true }).catch(async () => {
                            await el.evaluate((node) => {
                                node.checked = true;
                                node.dispatchEvent(new Event('change', { bubbles: true }));
                                node.dispatchEvent(new Event('click', { bubbles: true }));
                            });
                        });
                    }
                } else if (await el.isVisible().catch(() => false)) {
                    await el.click({ timeout: 3000 });
                }

                await this.page.waitForTimeout(800);
                return true;
            } catch (_) {
                // try next toggle
            }
        }

        return false;
    }

    /** Find the Bid link in the same cell block as the target lot (multi-lot rows). */
    async findLotBidLinkInRow(row, lotId) {
        const cells = row.locator('td');
        const count = await cells.count();

        for (let i = 0; i < count; i++) {
            const text = (await cells.nth(i).innerText()).trim();
            if (text !== lotId) {
                continue;
            }

            for (let j = i + 1; j < Math.min(i + 24, count); j++) {
                const cell = cells.nth(j);
                const cellText = (await cell.innerText()).trim();
                if (!/^\s*Bid\s*$/i.test(cellText)) {
                    continue;
                }

                const link = cell.locator('a').filter({ hasText: /^\s*Bid\s*$/i }).first();
                if (await link.count() > 0) {
                    return link;
                }
            }

            throw new Error(`Bid link not found in cell block for lot ${lotId}`);
        }

        throw new Error(`Lot cell ${lotId} not found in console row`);
    }

    /** Click row "Bid" link and wait for Ivalua manual bidding modal (#newOffer). */
    async openConsoleLotBidModal(row, lotId) {
        const bidLink = await this.findLotBidLinkInRow(row, lotId);
        if (await bidLink.count() === 0) {
            throw new Error(`Bid link not found in row for lot ${lotId}`);
        }

        await bidLink.scrollIntoViewIfNeeded();
        await bidLink.click({ timeout: 10000 });
        await this.page.waitForTimeout(1500);

        const modal = this.page.locator('#body_x_biddingdiv, #newOffer').first();
        await modal.waitFor({ state: 'visible', timeout: 15000 });

        const newOffer = this.page.locator('#newOffer');
        await newOffer.waitFor({ state: 'visible', timeout: 10000 });

        const preset = await newOffer.inputValue().catch(() => '');
        this.logBid('bid_modal_open', { lotId, preset });

        return newOffer;
    }

    /** Fill #newOffer and submit via #btnBid in the manual bidding modal. */
    async submitConsoleLotBidModal(page, amount, password, lotId) {
        const newOffer = page.locator('#newOffer');
        await newOffer.waitFor({ state: 'visible', timeout: 10000 });

        await newOffer.click({ timeout: 5000 }).catch(() => {});

        await newOffer.evaluate((el, val) => {
            el.removeAttribute('readonly');
            el.readOnly = false;
            el.value = String(val);
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
            el.dispatchEvent(new Event('blur', { bubbles: true }));
        }, amount);

        const filledRaw = await newOffer.inputValue().catch(() => '');
        const filledNum = parseFloat(String(filledRaw).replace(/,/g, ''));
        if (!filledNum || filledNum < amount - 0.01) {
            await newOffer.evaluate((el, val) => {
                el.removeAttribute('readonly');
                el.readOnly = false;
                const formatted = Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                el.value = formatted;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }, amount);
        }

        this.logBid('newOffer_filled', { amount, value: await newOffer.inputValue().catch(() => '') });

        const btnBid = page.locator('input#btnBid, button#btnBid').first();
        await btnBid.waitFor({ state: 'visible', timeout: 10000 });
        await btnBid.click({ timeout: 10000 });
        await page.waitForTimeout(1500);

        await this.completeBidConfirmation(page, password);

        const closeBtn = page.locator('#body_x_biddingdiv_ico');
        if (await closeBtn.isVisible().catch(() => false)) {
            await closeBtn.click({ timeout: 3000 }).catch(() => {});
            await page.waitForTimeout(500);
        }

        this.logBid('bid_modal_submitted', { lotId });
    }

    /** Place bid on auction_console via row Bid modal (Ivalua v5 manual bidding). */
    async placeBidOnConsoleLot(url, amount, lotId, password) {
        await this.gotoAuthenticated(url, { settleMs: 3000 });

        const row = await this.findConsoleLotRow(lotId);
        await row.scrollIntoViewIfNeeded();
        await this.page.waitForTimeout(500);
        this.logBid('row_found', { lotId });

        await this.openConsoleLotBidModal(row, lotId);
        await this.submitConsoleLotBidModal(this.page, amount, password, lotId);

        await this.page.waitForTimeout(3000);

        const pageError = this.page.locator('.error, .alert-danger, [class*="error"], [class*="Error"], #noBid').first();
        if (await pageError.count() > 0 && await pageError.isVisible()) {
            const errText = (await pageError.innerText()).trim();
            if (errText && !/can't bid/i.test(errText)) {
                throw new Error(`Ivalua rejected bid: ${errText.substring(0, 200)}`);
            }
        }

        const updatedState = await this.readConsoleLotState(lotId);
        this.logBid('verify', { expected: amount, saw: updatedState.current_bid });

        if (updatedState.current_bid < amount - 0.01) {
            this.logBid('verify_failed', { expected: amount, saw: updatedState.current_bid });
            throw new Error(
                `Bid may not have registered on Ivalua. Expected >= ${amount}, saw ${updatedState.current_bid}. Log: ${JSON.stringify(this.bidLog.slice(-10))}`
            );
        }

        this.logBid('success', { current_bid: updatedState.current_bid });
        return {
            success: true,
            current_bid: updatedState.current_bid,
            lot_id: lotId,
            message: 'Bid confirmed on Ivalua lot.',
            bid_log: this.bidLog,
        };
    }

    async fillPasswordField(page, password) {
        if (!password) {
            return;
        }

        const passSelectors = [
            '#password',
            '#passwordParticipate',
            'input[name="password"][type="password"]',
            'input[name="passwordParticipate"][type="password"]',
            'input[id*="password" i][type="password"]',
            'input[id*="Password" i][type="password"]',
        ];

        for (const sel of passSelectors) {
            const passField = page.locator(sel).first();
            if (await passField.count() === 0) {
                continue;
            }
            try {
                await passField.waitFor({ state: 'visible', timeout: 5000 });
                await passField.fill(password);
                return;
            } catch (_) {
                await passField.fill(password, { force: true }).catch(() => {});
                return;
            }
        }
    }

    /** Extract internal lot key from mass_bid fields only (not generic [123] fields). */
    async extractRowLotKey(row) {
        return await row.evaluate((tr) => {
            for (const inp of tr.querySelectorAll('input')) {
                const name = inp.name || '';
                const id = inp.id || '';
                const fromName = name.match(/mass_bid(?:_up)?\[(\d+)\]/i);
                if (fromName) {
                    return fromName[1];
                }
                const fromId = id.match(/mass_bid(?:_up)?_(\d+)/i);
                if (fromId) {
                    return fromId[1];
                }
            }
            return null;
        });
    }

    /** Find mass_bid input aligned with this lot row (by row index / same row / lot id). */
    async resolveMassBidInput(row, lotId) {
        const meta = await row.evaluate((tr, targetLotId) => {
            const lotPattern = /[A-Z]{3,5}\d{3,}/;

            const inRow = tr.querySelector('input[name^="mass_bid_up"], input[id^="mass_bid_up"], input[name*="mass_bid"]');
            if (inRow) {
                return { id: inRow.id, name: inRow.name, strategy: 'in_row' };
            }

            for (const cell of tr.querySelectorAll('td')) {
                const cellInput = cell.querySelector('input[name^="mass_bid_up"], input[id^="mass_bid_up"]');
                if (cellInput) {
                    return { id: cellInput.id, name: cellInput.name, strategy: 'cell' };
                }
            }

            const table = tr.closest('table');
            if (!table) {
                return null;
            }

            const lotRows = [...table.querySelectorAll('tr')].filter(
                (r) => lotPattern.test(r.innerText) && r.innerText.includes(targetLotId)
            );
            const targetRow = lotRows[0] || tr;
            const allLotRows = [...table.querySelectorAll('tr')].filter((r) => lotPattern.test(r.innerText));
            const rowIdx = allLotRows.indexOf(targetRow);

            const form = tr.closest('form') || document;
            const massInputs = [...form.querySelectorAll('input[name^="mass_bid_up"], input[id^="mass_bid_up"]')];

            if (rowIdx >= 0 && massInputs[rowIdx]) {
                const inp = massInputs[rowIdx];
                return { id: inp.id, name: inp.name, strategy: 'index', rowIdx, totalMass: massInputs.length };
            }

            return null;
        }, lotId);

        if (!meta) {
            return null;
        }

        this.logBid('mass_input_resolve', { ...meta, lotId });

        if (meta.id) {
            const escapedId = meta.id.replace(/([!"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
            return this.page.locator(`#${escapedId}`).first();
        }
        if (meta.name) {
            return this.page.locator(`input[name="${meta.name}"]`).first();
        }

        return null;
    }

    /** Select lot checkbox so mass bid submit includes this row only. */
    async selectLotForMassBid(row) {
        const checkbox = row.locator('input[type="checkbox"]').first();
        if (await checkbox.count() === 0) {
            return false;
        }
        try {
            const checked = await checkbox.isChecked().catch(() => false);
            if (!checked) {
                await checkbox.check({ force: true });
            }
            this.logBid('mass_row_checked', { checked: true });
            return true;
        } catch (err) {
            this.logBid('mass_row_check_fail', { error: err.message });
            return false;
        }
    }

    /** Clear other mass bid fields so submit only applies to target lot. */
    async clearOtherMassBidInputs(targetInput) {
        const targetMeta = await targetInput.evaluate((el) => ({ id: el.id, name: el.name }));
        await this.page.evaluate(({ id, name }) => {
            document.querySelectorAll('input[name^="mass_bid_up"], input[id^="mass_bid_up"]').forEach((inp) => {
                if (inp.id !== id && inp.name !== name) {
                    inp.value = '';
                    inp.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }, targetMeta);
        this.logBid('mass_other_cleared', targetMeta);
    }

    async waitForPasswordDialog(page, timeoutMs = 8000) {
        const pass = page.locator(
            '#password, input[name="password"][type="password"], input[id*="password" i][type="password"]'
        ).first();
        try {
            await pass.waitFor({ state: 'visible', timeout: timeoutMs });
            return true;
        } catch (_) {
            return false;
        }
    }

    /** Password + confirm after bid submit click (Ivalua shows dialog after submit). */
    async completeBidConfirmation(page, password) {
        await page.waitForTimeout(1500);

        const hasPassword = await this.waitForPasswordDialog(page, 8000);
        if (hasPassword && password) {
            this.logBid('password_dialog', { visible: true });
            await this.fillPasswordField(page, password);
            await page.waitForTimeout(500);
        } else {
            this.logBid('password_dialog', { visible: false });
        }

        const btnBid = page.locator('input#btnBid, button#btnBid').first();
        if (await btnBid.isVisible().catch(() => false)) {
            await btnBid.click({ timeout: 5000 }).catch(() => {});
            await page.waitForTimeout(1500);
        }

        await this.confirmBidDialogsOnPage(page);
        await page.waitForTimeout(2000);
        await this.confirmBidDialogsOnPage(page);
        await page.waitForTimeout(1500);
    }

    /** Open lot bid UI: navigate to Bid link URL, popup, or inline form. Returns page to use. */
    async openLotBidContext(row, lotId) {
        let bidLink = row.locator('a').filter({ hasText: /^Bid$/i }).first();
        if (await bidLink.count() === 0) {
            bidLink = row.locator('a, button').filter({ hasText: /Bid/i }).first();
        }

        if (await bidLink.count() === 0) {
            return { page: this.page, mode: 'console' };
        }

        const href = await bidLink.getAttribute('href');

        if (href && !href.startsWith('javascript') && !href.startsWith('#') && href !== '') {
            const targetUrl = new URL(href, this.page.url()).href;
            console.log(`[Ivalua] Navigating to lot bid page: ${targetUrl}`);
            await this.page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
            await this.page.waitForTimeout(2500);
            await this.dismissPassiveNotifications();
            return { page: this.page, mode: 'detail' };
        }

        const popupPromise = this.page.context().waitForEvent('page', { timeout: 8000 }).catch(() => null);
        await bidLink.click({ timeout: 10000 });
        const popup = await popupPromise;

        if (popup) {
            await popup.waitForLoadState('domcontentloaded', { timeout: 30000 }).catch(() => {});
            await popup.waitForTimeout(2000);
            console.log(`[Ivalua] Lot bid opened in popup: ${popup.url()}`);
            return { page: popup, mode: 'popup' };
        }

        await this.page.waitForTimeout(3000);

        if (this.page.url().includes('auction_lot') || this.page.url().includes('lot_bid')) {
            return { page: this.page, mode: 'detail' };
        }

        return { page: this.page, mode: 'inline' };
    }

    /** Click Ivalua bid submit — btnBidOuter is a styled span, not a normal button. */
    async clickIvaluaBidSubmit(page) {
        const attempts = [
            { sel: '#btnBidOuter', note: 'green bid wrapper span' },
            { sel: 'span#btnBidOuter.btn_color_green', note: 'bid outer span' },
            { sel: '#btnBidOuter .btn', note: 'inner btn label' },
            { sel: 'input#btnBid', note: 'hidden submit input' },
            { sel: 'button#btnBid', note: 'bid button element' },
            { sel: 'input[value*="Bid" i][type="submit"]', note: 'submit input' },
            { sel: 'button:has-text("Place Bid")', note: 'Place Bid text button' },
            { sel: 'span.btn_color_green:has-text("Bid")', note: 'green Bid span' },
        ];

        const failures = [];

        for (const { sel, note } of attempts) {
            const btn = page.locator(sel).first();
            if (await btn.count() === 0) {
                this.logBid('submit_skip', { selector: sel, reason: 'not_found' });
                continue;
            }

            try {
                const visible = await btn.isVisible().catch(() => false);
                this.logBid('submit_try', { selector: sel, note, visible });

                if (visible) {
                    await btn.scrollIntoViewIfNeeded().catch(() => {});
                    await btn.click({ timeout: 10000 });
                } else {
                    await btn.evaluate((el) => {
                        el.click();
                        el.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
                    });
                }

                this.logBid('submit_ok', { selector: sel });
                return sel;
            } catch (err) {
                failures.push({ selector: sel, error: err.message });
                this.logBid('submit_fail', { selector: sel, error: err.message });
            }
        }

        try {
            const viaJs = await page.evaluate(() => {
                const outer = document.getElementById('btnBidOuter');
                if (outer) {
                    outer.click();
                    return 'btnBidOuter-js';
                }
                const input = document.getElementById('btnBid');
                if (input) {
                    input.click();
                    if (typeof input.form?.requestSubmit === 'function') {
                        input.form.requestSubmit(input);
                    }
                    return 'btnBid-js';
                }
                const green = document.querySelector('span.btn_color_green[id*="Bid"], .btn_color_green[id="btnBidOuter"]');
                if (green) {
                    green.click();
                    return 'green-span-js';
                }
                return null;
            });

            if (viaJs) {
                this.logBid('submit_ok', { method: viaJs });
                return viaJs;
            }
        } catch (err) {
            failures.push({ selector: 'evaluate', error: err.message });
            this.logBid('submit_fail', { method: 'evaluate', error: err.message });
        }

        this.logBid('submit_exhausted', { failures });
        throw new Error(`Could not click Ivalua bid submit button. Steps: ${JSON.stringify(failures)}`);
    }

    /** Submit bid on lot detail page (NOT generic console page inputs). */
    async submitBidOnPage(page, amount, password, options = {}) {
        const { scopeRow = null, lotId = null } = options;
        const pageUrl = page.url();
        this.logBid('submit_page_start', { url: pageUrl, amount, lotId, scoped: !!scopeRow });

        if (pageUrl.includes('auction_console') && !scopeRow) {
            this.logBid('submit_page_abort', { reason: 'refuse_generic_console_input' });
            return false;
        }

        const root = scopeRow || page;
        const inputSelectors = [
            'input[name="bid_price"]',
            'input#txtBidAmount',
            'input[id*="BidAmount" i]',
            'input[id*="BidValue" i]',
            'input[id*="txtBid" i]',
            'input[name^="mass_bid_up"]',
            'input[id^="mass_bid_up"]',
            'input[name*="mass_bid"]',
            ...this.selectors.bidInput.split(', '),
        ];

        let filled = false;
        let usedInputSelector = null;
        for (const sel of inputSelectors) {
            const input = root.locator(sel).first();
            if (await input.count() === 0) {
                continue;
            }
            try {
                await input.waitFor({ state: 'visible', timeout: 4000 });
                await input.fill(String(amount));
                filled = true;
                usedInputSelector = sel;
                this.logBid('input_filled', { selector: sel, mode: 'visible' });
                break;
            } catch (_) {
                try {
                    await input.waitFor({ state: 'attached', timeout: 2000 });
                    await input.evaluate((el, val) => {
                        el.value = String(val);
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }, String(amount));
                    filled = true;
                    usedInputSelector = sel;
                    this.logBid('input_filled', { selector: sel, mode: 'hidden' });
                    break;
                } catch (innerErr) {
                    this.logBid('input_skip', { selector: sel, error: innerErr.message });
                }
            }
        }

        if (!filled) {
            this.logBid('submit_page_abort', { reason: 'no_bid_input_found' });
            return false;
        }

        await this.clickIvaluaBidSubmit(page);
        await this.completeBidConfirmation(page, password);

        this.logBid('submit_page_done', { input: usedInputSelector });
        return true;
    }

    async confirmBidDialogsOnPage(page) {
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
                    const btn = page.locator(sel).first();
                    if (await btn.count() > 0 && await btn.isVisible()) {
                        await btn.click({ timeout: 3000 });
                        clicked = true;
                        await page.waitForTimeout(1500);
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

    /** Fill bid amount on console table (mass bid input may be outside the row). */
    async fillConsoleRowBidInput(row, amount, lotId) {
        const bidInput = await this.resolveMassBidInput(row, lotId);
        if (!bidInput) {
            throw new Error(`No mass bid input found for lot ${lotId} on Ivalua console.`);
        }

        await bidInput.waitFor({ state: 'attached', timeout: 15000 });
        await this.clearOtherMassBidInputs(bidInput);

        await bidInput.evaluate((el, val) => {
            el.removeAttribute('readonly');
            el.removeAttribute('disabled');
            el.style.display = '';
            el.style.visibility = 'visible';
            el.value = String(val);
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }, String(amount));

        return bidInput;
    }

    /** After opening lot bid page/popup, submit and verify. */
    async tryPlaceBidOnLotDetailPage(page, amount, password, lotId, consoleUrl) {
        const isConsole = page.url().includes('auction_console');
        const submitted = await this.submitBidOnPage(page, amount, password, {
            scopeRow: isConsole ? null : undefined,
            lotId,
        });

        if (!submitted) {
            if (page !== this.page) {
                await page.close().catch(() => {});
            }
            if (this.page.url() !== consoleUrl) {
                await this.page.goto(consoleUrl, { waitUntil: 'domcontentloaded', timeout: 60000 }).catch(() => {});
                await this.page.waitForTimeout(2000);
            }
            return null;
        }

        if (page !== this.page) {
            await page.close().catch(() => {});
            await this.page.goto(consoleUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
            await this.page.waitForTimeout(3000);
        }

        const updatedState = await this.readConsoleLotState(lotId);

        if (updatedState.current_bid < amount - 0.01) {
            this.logBid('detail_unverified', { expected: amount, saw: updatedState.current_bid });
            return null;
        }

        return {
            success: true,
            current_bid: updatedState.current_bid,
            lot_id: lotId,
            message: 'Bid confirmed on Ivalua lot detail page.',
        };
    }

    async placeBid(url, amount, lotId = null, password = null) {
        this.bidLog = [];
        this.logBid('start', { url, amount, lotId });

        if (this.mockMode) {
            console.log('[Ivalua] Mock Mode - Simulating successful bid placement.');
            return {
                success: true,
                tx_id: 'iv_mock_tx_' + Math.floor(Math.random() * 1000000),
                current_bid: amount,
                message: 'Bid placed successfully in Mock mode.',
            };
        }

        if (url.includes('auction_console') && lotId) {
            return await this.placeBidOnConsoleLot(url, amount, lotId, password);
        }

        await this.gotoAuthenticated(url, { settleMs: 2500 });
        await this.dismissPassiveNotifications();

        try {
            await this.page.fill(this.selectors.bidInput, amount.toString());
            await this.clickIvaluaBidSubmit(this.page);
            await this.completeBidConfirmation(this.page, password);
            await this.page.waitForSelector(this.selectors.bidSuccessIndicator, { timeout: 15000 });

            const updatedState = await this.readAuctionState(url);

            return {
                success: true,
                current_bid: updatedState.current_bid,
                message: 'Bid confirmed.',
                bid_log: this.bidLog,
            };
        } catch (e) {
            this.logBid('fatal', { error: e.message });
            throw new Error(`Ivalua bid placement failed: ${e.message} | bid_log: ${JSON.stringify(this.bidLog.slice(-12))}`);
        }
    }

    /** Find lot row on console — exact lot ID match, searches paginated tables. */
    async rowMatchesLotId(row, lotId) {
        return await row.evaluate((tr, id) => {
            const pattern = /\b[A-Z]{3,5}\d{3,}\b/g;
            for (const td of tr.querySelectorAll('td')) {
                const text = td.innerText.trim();
                if (text === id) {
                    return true;
                }
                const ids = text.match(pattern) || [];
                if (ids.includes(id)) {
                    return true;
                }
            }
            return false;
        }, lotId);
    }

    async findConsoleLotRow(lotId) {
        for (let pageNum = 1; pageNum <= 30; pageNum++) {
            if (pageNum > 1) {
                const moved = await this.clickBrowsePagerLink(pageNum);
                if (!moved) {
                    break;
                }
            }

            const rows = this.page.locator('tr');
            const count = await rows.count();
            for (let i = 0; i < count; i++) {
                const row = rows.nth(i);
                if (!(await this.rowMatchesLotId(row, lotId))) {
                    continue;
                }
                await row.waitFor({ state: 'visible', timeout: 8000 });
                this.logBid('row_match', { lotId, pageNum, rowIndex: i });
                return row;
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
