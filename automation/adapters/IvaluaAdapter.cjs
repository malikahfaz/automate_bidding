const fs = require('fs');
const path = require('path');

class IvaluaAdapter {
    constructor(page, cookiesPath) {
        this.page = page;
        this.cookiesPath = cookiesPath;

        // Configurable Selectors (easy to update)
        this.selectors = {
            loginUrl: 'https://t-mobile.ivalua.app/page.aspx/en/usr/login',
            emailInput: 'input[name="login"], input#login-username',
            passwordInput: 'input[name="password"], input#login-password',
            submitButton: 'button[type="submit"], input#login-button',
            profileIndicator: '.user-info, .header-user, #user-menu',
            
            // Auction Page Selectors
            title: '.auc-title, .auction-title, h2',
            currentBid: '.current-bid, .current-price, #lblCurrentBid',
            bidIncrement: '.bid-increment, .increment-price, #lblBidIncrement',
            timeRemaining: '.time-remaining, .countdown, #lblTimeRemaining',
            
            // Bidding selectors
            bidInput: 'input[name="bid_price"], input#txtBidAmount',
            placeBidBtn: 'button.btn-place-bid, input#btnPlaceBid',
            bidSuccessIndicator: '.bid-success-dialog, .message-success'
        };
    }

    async ensureSession(email, password) {
        console.log(`[Ivalua] Checking session using cookies at: ${this.cookiesPath}`);
        
        // Try loading cookies
        if (fs.existsSync(this.cookiesPath)) {
            try {
                const cookies = JSON.parse(fs.readFileSync(this.cookiesPath));
                await this.page.context().addCookies(cookies);
                console.log('[Ivalua] Loaded cookies successfully.');
            } catch (e) {
                console.error('[Ivalua] Failed to parse cookie file:', e.message);
            }
        }

        // Navigate to a dashboard page or login page to test if logged in
        try {
            await this.page.goto('https://t-mobile.ivalua.app', { waitUntil: 'domcontentloaded', timeout: 15000 });
            const loggedIn = await this.page.locator(this.selectors.profileIndicator).count() > 0;
            
            if (loggedIn) {
                console.log('[Ivalua] Session is valid.');
                return true;
            }
        } catch (e) {
            console.log('[Ivalua] Failed to verify session on homepage, attempting login anyway.');
        }

        // Session not active, perform login
        return await this.login(email, password);
    }

    async login(email, password) {
        console.log(`[Ivalua] Logging in with account: ${email}`);
        
        // Check if we are running in a mock/test target
        if (email.includes('example.com') || email.includes('ellectmobility.com')) {
            console.log('[Ivalua] Running in Mock Mode - Simulating login success.');
            // Write a dummy cookie file
            fs.mkdirSync(path.dirname(this.cookiesPath), { recursive: true });
            fs.writeFileSync(this.cookiesPath, JSON.stringify([{ name: 'mock_ivalua_session', value: '1', domain: 't-mobile.ivalua.app', path: '/' }]));
            return true;
        }

        await this.page.goto(this.selectors.loginUrl, { waitUntil: 'networkidle', timeout: 30000 });
        
        await this.page.fill(this.selectors.emailInput, email);
        await this.page.fill(this.selectors.passwordInput, password);
        await this.page.click(this.selectors.submitButton);
        
        // Wait for profile indicator or navigation to verify login
        try {
            await this.page.waitForSelector(this.selectors.profileIndicator, { timeout: 15000 });
            console.log('[Ivalua] Login successful.');
            
            // Save cookies
            const cookies = await this.page.context().cookies();
            fs.mkdirSync(path.dirname(this.cookiesPath), { recursive: true });
            fs.writeFileSync(this.cookiesPath, JSON.stringify(cookies, null, 2));
            return true;
        } catch (e) {
            throw new Error(`Ivalua login failed: Profile indicator not found post-login. ${e.message}`);
        }
    }

    async readAuctionState(url) {
        console.log(`[Ivalua] Reading state for: ${url}`);

        // Mock mode detection for client demo
        if (url.includes('ellectmobility.com') || url.includes('mock') || url.includes('id=101')) {
            console.log('[Ivalua] Mock Mode - Returning simulated auction state.');
            const baseBid = 24500.00;
            const inc = 250.00;
            const randomAdd = Math.floor(Date.now() / 10000) % 6 * inc; // updates every 10s
            
            return {
                title: 'T-Mobile Ivalua Auction: Apple iPhone 15 Pro Max Bulk Lot (50 units)',
                current_bid: baseBid + randomAdd,
                bid_increment: inc,
                time_remaining: '2h ' + (60 - (Math.floor(Date.now() / 1000) % 60)) + 'm',
                ends_at: new Date(Date.now() + 2 * 3600 * 1000).toISOString(),
                status: 'active'
            };
        }

        await this.page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });

        try {
            const title = await this.page.locator(this.selectors.title).first().innerText();
            const currentBidText = await this.page.locator(this.selectors.currentBid).first().innerText();
            const bidIncrementText = await this.page.locator(this.selectors.bidIncrement).first().innerText();
            const timeRemaining = await this.page.locator(this.selectors.timeRemaining).first().innerText();

            // Extract numbers from text (e.g. "$24,500.00" -> 24500.00)
            const current_bid = parseFloat(currentBidText.replace(/[^0-9.]/g, ''));
            const bid_increment = parseFloat(bidIncrementText.replace(/[^0-9.]/g, ''));

            return {
                title: title.trim(),
                current_bid,
                bid_increment,
                time_remaining: timeRemaining.trim(),
                status: 'active'
            };
        } catch (e) {
            throw new Error(`Failed to parse Ivalua page selectors: ${e.message}`);
        }
    }

    async placeBid(url, amount) {
        console.log(`[Ivalua] Placing bid of ${amount} on ${url}`);

        if (url.includes('ellectmobility.com') || url.includes('mock') || url.includes('id=101')) {
            console.log('[Ivalua] Mock Mode - Simulating successful bid placement.');
            return {
                success: true,
                tx_id: 'iv_mock_tx_' + Math.floor(Math.random() * 1000000),
                current_bid: amount,
                message: 'Bid placed successfully in Mock mode.'
            };
        }

        await this.page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });

        try {
            // Fill input and submit
            await this.page.fill(this.selectors.bidInput, amount.toString());
            await this.page.click(this.selectors.placeBidBtn);
            
            // Check for success dialog or alert
            await this.page.waitForSelector(this.selectors.bidSuccessIndicator, { timeout: 15000 });
            
            // Re-read current state to confirm bid went through
            const updatedState = await this.readAuctionState(url);
            
            return {
                success: true,
                current_bid: updatedState.current_bid,
                message: 'Bid confirmed.'
            };
        } catch (e) {
            throw new Error(`Ivalua bid placement failed: ${e.message}`);
        }
    }
}

module.exports = IvaluaAdapter;
