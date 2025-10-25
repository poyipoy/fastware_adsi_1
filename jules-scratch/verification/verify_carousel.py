from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto("http://localhost:5173/")
        page.wait_for_selector('#username', timeout=60000)
        page.fill('#username', "testuser")
        page.fill('#password', "testpassword")
        page.press('#password', "Enter")
        page.wait_for_url("**/dashboard-tcpd", timeout=60000)
        page.screenshot(path="jules-scratch/verification/verification.png")
        browser.close()

run()
