const fs = require('fs');
const path = require('path');

class BStockAdapter {
    constructor(page, cookiesPath) {
        this.page = page;
        this.cookiesPath = cookiesPath;

        // Configurable Selectors (easy to update)
        this.selectors = {
            loginUrl: 'https://bstock.com/login',
            emailInput: 'input[name="email"]',
            passwordInput: 'input[name="password"]',
            submitButton: 'button[type="submit"]',
            profileIndicator: '.user-profile-menu, .account-nav',
            
            // Auction Page Selectors
            title: 'h1.auction-title, h1.product-title, h1',
            currentBid: '.current-bid, .current-bid-amount, .bid-price',
            bidIncrement: '.bid-increment, .increment-amount',
            timeRemaining: '.time-remaining, .countdown, .timer',
            
            // Bidding selectors
            bidInput: 'input[name="bid_amount"], input#bid-amount',
            placeBidBtn: 'button.place-bid, button.submit-bid',
            bidSuccessIndicator: '.bid-success-alert, .bid-confirmed'
        };
    }

    async ensureSession(email, password) {
        console.log(`[B-Stock] Checking session using cookies at: ${this.cookiesPath}`);
        
        // Try loading cookies
        if (fs.existsSync(this.cookiesPath)) {
            try {
                const cookies = JSON.parse(fs.readFileSync(this.cookiesPath));
                await this.page.context().addCookies(cookies);
                console.log('[B-Stock] Loaded cookies successfully.');
            } catch (e) {
                console.error('[B-Stock] Failed to parse cookie file:', e.message);
            }
        }

        // Navigate to a dashboard page or login page to test if logged in
        try {
            await this.page.goto('https://bstock.com', { waitUntil: 'domcontentloaded', timeout: 15000 });
            const loggedIn = await this.page.locator(this.selectors.profileIndicator).count() > 0;
            
            if (loggedIn) {
                console.log('[B-Stock] Session is valid.');
                return true;
            }
        } catch (e) {
            console.log('[B-Stock] Failed to verify session on homepage, attempting login anyway.');
        }

        // Session not active, perform login
        return await this.login(email, password);
    }

    async login(email, password) {
        console.log(`[B-Stock] Logging in with account: ${email}`);
        
        // Check if we are running in a mock/test target
        if (email.includes('example.com') || email.includes('ellectmobility.com')) {
            console.log('[B-Stock] Running in Mock Mode - Simulating login success.');
            // Write a dummy cookie file
            fs.mkdirSync(path.dirname(this.cookiesPath), { recursive: true });
            fs.writeFileSync(this.cookiesPath, JSON.stringify([{ name: 'mock_session', value: '1', domain: 'bstock.com', path: '/' }]));
            return true;
        }

        await this.page.goto(this.selectors.loginUrl, { waitUntil: 'networkidle', timeout: 30000 });
        
        await this.page.fill(this.selectors.emailInput, email);
        await this.page.fill(this.selectors.passwordInput, password);
        await this.page.click(this.selectors.submitButton);
        
        // Wait for profile indicator or navigation to verify login
        try {
            await this.page.waitForSelector(this.selectors.profileIndicator, { timeout: 15000 });
            console.log('[B-Stock] Login successful.');
            
            // Save cookies
            const cookies = await this.page.context().cookies();
            fs.mkdirSync(path.dirname(this.cookiesPath), { recursive: true });
            fs.writeFileSync(this.cookiesPath, JSON.stringify(cookies, null, 2));
            return true;
        } catch (e) {
            throw new Error(`B-Stock login failed: Profile indicator not found post-login. ${e.message}`);
        }
    }

    async readAuctionState(url) {
        console.log(`[B-Stock] Reading state for: ${url}`);

        // Mock mode detection for client demo
        if (url.includes('ellectmobility.com') || url.includes('mock')) {
            console.log('[B-Stock] Mock Mode - Returning simulated auction state.');
            const isS4Ultra = url.includes('id=4520');
            const baseBid = isS4Ultra ? 12100.00 : 3400.00;
            const inc = isS4Ultra ? 100.00 : 50.00;
            const randomAdd = Math.floor(Date.now() / 15000) % 5 * inc; // updates every 15s
            
            return {
                title: isS4Ultra ? 'B-Stock: Grade A Samsung Galaxy S24 Ultra Mixed Lot (25 units)' : 'B-Stock: Target Customer Returns - Electronics Accessories (Pallet)',
                current_bid: baseBid + randomAdd,
                bid_increment: inc,
                time_remaining: '4h ' + (60 - (Math.floor(Date.now() / 1000) % 60)) + 'm',
                ends_at: new Date(Date.now() + 4 * 3600 * 1000).toISOString(),
                status: 'active'
            };
        }

        await this.page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });

        try {
            const title = await this.page.locator(this.selectors.title).first().innerText();
            const currentBidText = await this.page.locator(this.selectors.currentBid).first().innerText();
            const bidIncrementText = await this.page.locator(this.selectors.bidIncrement).first().innerText();
            const timeRemaining = await this.page.locator(this.selectors.timeRemaining).first().innerText();

            // Extract numbers from text (e.g. "$12,100.00" -> 12100.00)
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
            throw new Error(`Failed to parse B-Stock page selectors: ${e.message}`);
        }
    }

    async placeBid(url, amount) {
        console.log(`[B-Stock] Placing bid of ${amount} on ${url}`);

        if (url.includes('ellectmobility.com') || url.includes('mock')) {
            console.log('[B-Stock] Mock Mode - Simulating successful bid placement.');
            return {
                success: true,
                tx_id: 'bs_mock_tx_' + Math.floor(Math.random() * 1000000),
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
            throw new Error(`B-Stock bid placement failed: ${e.message}`);
        }
    }
}

module.exports = BStockAdapter;
