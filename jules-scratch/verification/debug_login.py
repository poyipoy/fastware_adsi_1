from playwright.sync_api import sync_playwright

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        page.goto("http://localhost:5173/")
        page.screenshot(path="jules-scratch/verification/login_page_debug.png")
        browser.close()

run()
