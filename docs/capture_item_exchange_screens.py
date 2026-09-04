"""
Captures the POS Item Exchange screenshots used by
generate_item_exchange_docx.py, into docs/_exchange_assets/.

Requires a demo sale sitting in an open register session (see the guide's
build notes) and the app served at BASE_URL.

    .venv/Scripts/python docs/capture_item_exchange_screens.py <sale_id>
"""

import os
import sys
from playwright.sync_api import sync_playwright

BASE_URL = os.environ.get('TOS_URL', 'http://127.0.0.1:8000')
EMAIL    = os.environ.get('TOS_EMAIL', 'admin@admin.com')
PASSWORD = os.environ.get('TOS_PASSWORD', 'password')

SALE_ID  = sys.argv[1] if len(sys.argv) > 1 else None
OUT      = os.path.join(os.path.dirname(os.path.abspath(__file__)), '_exchange_assets')
os.makedirs(OUT, exist_ok=True)

# The item being exchanged, and what it is being exchanged for.
OUTGOING_ITEM = 'RSB 10MMX6M'
REPLACEMENT_SEARCH = 'RSB 12'
REPLACEMENT_NAME = 'RSB 12MMX6M'
REPLACEMENT_QTY = '2'
REASON = 'Customer wanted the thicker 12mm bar instead'


def goto(pg, url, tries=5):
    """php artisan serve is single-threaded and will abort a request while it is
    busy with another, so navigation gets a few attempts."""
    last = None
    for attempt in range(tries):
        try:
            return pg.goto(url, wait_until='domcontentloaded', timeout=30000)
        except Exception as e:  # noqa: BLE001
            last = e
            print(f'  retry {attempt + 1} for {url}')
            pg.wait_for_timeout(2000)
    raise last


def shot(target, name):
    path = os.path.join(OUT, name)
    target.screenshot(path=path)
    print('  saved', name)


def modal_for(page, heading):
    """The rounded modal card containing the given h3 heading."""
    return page.locator(
        f"xpath=//h3[normalize-space()='{heading}']/ancestor::div[contains(@class,'rounded-2xl')][1]"
    )


with sync_playwright() as p:
    browser = p.chromium.launch()
    ctx = browser.new_context(viewport={'width': 1600, 'height': 1000},
                              device_scale_factor=2)
    page = ctx.new_page()

    # ── Log in ────────────────────────────────────────────────────────────────
    print('logging in...')
    goto(page, f'{BASE_URL}/login')
    page.fill('input[type="email"]', EMAIL)
    page.fill('input[type="password"]', PASSWORD)
    page.press('input[type="password"]', 'Enter')
    page.wait_for_load_state('domcontentloaded')
    page.wait_for_timeout(2500)
    print('  landed on', page.url)

    # ── POS ───────────────────────────────────────────────────────────────────
    print('opening POS...')
    goto(page, f'{BASE_URL}/pos')
    page.wait_for_timeout(3500)
    print('  pos url', page.url, '| alpine roots:', page.locator('[x-data]').count())

    # ── 1. Recent Sales, showing the per-item exchange / void icons ──────────
    print('01 recent sales')
    page.evaluate("""() => {
        const d = Alpine.$data(document.querySelector('[x-data]'));
        d.showReprintModal = true;
        d.fetchRecentSales();
    }""")
    page.wait_for_timeout(2500)

    diag = page.evaluate("""() => {
        const d = Alpine.$data(document.querySelector('[x-data]'));
        return {
            registerSessionId: d.registerSessionId,
            recent: d.recentSales.length,
            filtered: d.filteredRecentSales.length,
            first: (d.filteredRecentSales[0] || {}).id,
            firstSession: (d.filteredRecentSales[0] || {}).cash_register_session_id,
            firstItems: ((d.filteredRecentSales[0] || {}).sale_items || []).map(
                i => (i.product ? i.product.name : i.product_description) + ' x' + i.quantity),
        };
    }""")
    print('  DIAG', diag)
    page.screenshot(path=os.path.join(OUT, 'debug-recent-sales.png'), full_page=False)

    sale_row = page.locator(f"xpath=//span[contains(text(),'{OUTGOING_ITEM}')]"
                            f"/ancestor::div[contains(@class,'rounded-lg')][1]").first
    sale_row.scroll_into_view_if_needed()
    page.wait_for_timeout(400)
    shot(sale_row, '01-recent-sales-item-actions.png')

    # ── 2. Exchange modal, product search ────────────────────────────────────
    print('02 exchange modal (search)')
    exchange_btn = page.locator(
        f"xpath=//span[contains(text(),'{OUTGOING_ITEM}')]"
        f"/following-sibling::div//button[@title='Exchange this item for another product']"
    ).first
    exchange_btn.click()
    page.wait_for_timeout(800)

    modal = modal_for(page, 'Exchange Item')
    page.fill("input[placeholder='Search product...']", REPLACEMENT_SEARCH)
    page.wait_for_timeout(600)
    shot(modal, '02-exchange-search.png')

    # ── 3. Replacement selected — quantity, unit, price difference ───────────
    print('03 replacement selected')
    page.locator(f"xpath=//button[.//span[contains(text(),'{REPLACEMENT_NAME}')]]").first.click()
    page.wait_for_timeout(500)

    qty = page.locator("xpath=//label[normalize-space()='Quantity']/following-sibling::input")
    qty.fill(REPLACEMENT_QTY)
    qty.dispatch_event('input')
    page.wait_for_timeout(500)

    page.fill("input[placeholder='e.g. Customer wanted a different size...']", REASON)
    page.wait_for_timeout(400)
    shot(modal, '03-exchange-price-difference.png')

    # ── 4. Waiting for manager approval ──────────────────────────────────────
    print('04 waiting for approval')
    page.locator("xpath=//button[.//span[contains(text(),'Request Exchange')]]").first.click()
    page.wait_for_timeout(2500)

    waiting = page.locator(
        "xpath=//div[contains(@class,'rounded-2xl')][.//*[contains(text(),'Waiting')] "
        "or .//*[contains(text(),'approval')]]"
    ).first
    try:
        shot(waiting, '04-waiting-for-approval.png')
    except Exception:
        shot(page, '04-waiting-for-approval.png')

    # ── 5. Manager approvals panel, in a second tab ──────────────────────────
    print('05 manager approvals panel')
    mgr = ctx.new_page()
    goto(mgr, f'{BASE_URL}/pos')
    mgr.wait_for_timeout(1500)
    mgr.evaluate("""() => {
        const d = Alpine.$data(document.querySelector('[x-data]'));
        d.openVoidApprovalsPanel();
    }""")
    mgr.wait_for_timeout(2500)

    card = mgr.locator("xpath=//div[contains(@class,'rounded-xl')]"
                       "[.//span[normalize-space()='ITEM EXCHANGE']]").first
    card.scroll_into_view_if_needed()
    mgr.wait_for_timeout(400)
    shot(card, '05-manager-approval-card.png')

    # ── 6. The cashier's confirmation once it is approved ────────────────────
    print('06 approving, then capturing the result')
    approved_msg = {}
    page.on('dialog', lambda d: (approved_msg.setdefault('text', d.message), d.accept()))

    mgr.locator("xpath=//button[normalize-space()='Approve']").first.click()
    mgr.wait_for_timeout(2500)

    # The cashier tab polls every 3s, so give it a beat to notice.
    page.wait_for_timeout(6000)
    print('  cashier saw:', approved_msg.get('text'))

    # ── 7. The sale after the swap ───────────────────────────────────────────
    print('07 sale after exchange')
    mgr.evaluate("""() => {
        const d = Alpine.$data(document.querySelector('[x-data]'));
        d.showVoidApprovalsPanel = false;
        d.showReprintModal = true;
        d.fetchRecentSales();
    }""")
    mgr.wait_for_timeout(3000)

    after = mgr.locator(f"xpath=//span[contains(text(),'{REPLACEMENT_NAME}')]"
                        f"/ancestor::div[contains(@class,'rounded-lg')][1]").first
    after.scroll_into_view_if_needed()
    mgr.wait_for_timeout(400)
    shot(after, '07-sale-after-exchange.png')

    browser.close()

print('\nScreenshots in', OUT)
