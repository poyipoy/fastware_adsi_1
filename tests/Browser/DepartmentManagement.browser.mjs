// Run DepartmentManagementTest with DEPARTMENT_BROWSER_EXPORT=1 first.
// Uses rendered synthetic fixtures and actual page styles; never submits to the application.
import { spawn } from 'node:child_process';
import { readFile, writeFile, mkdir, mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { resolve, join } from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';

const directory = resolve('storage/framework/department-browser-qa');
const profile = await mkdtemp(join(tmpdir(), 'department-browser-'));
const executable = process.env.DEPARTMENT_CHROME ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const chrome = spawn(executable, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
    '--disable-background-networking', '--disable-extensions', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'],
{ windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
const sockets = [];
const failures = [];
const checks = [];
let launchError;
let browserErrors = '';
chrome.on('error', error => { launchError = error; });
chrome.stderr.on('data', chunk => { browserErrors += chunk.toString(); });

async function connect(url) {
    const socket = new WebSocket(url);
    sockets.push(socket);
    await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
    let sequence = 0;
    const pending = new Map();
    socket.onmessage = event => {
        const data = JSON.parse(event.data);
        if (!data.id) return;
        const operation = pending.get(data.id);
        pending.delete(data.id);
        clearTimeout(operation.timeout);
        if (data.error) operation.reject(new Error(data.error.message));
        else operation.resolve(data.result);
    };
    return (method, params = {}) => new Promise((resolve, reject) => {
        const id = ++sequence;
        const timeout = setTimeout(() => { pending.delete(id); reject(new Error(`CDP timeout: ${method}`)); }, 10000);
        pending.set(id, { resolve, reject, timeout });
        socket.send(JSON.stringify({ id, method, params }));
    });
}

try {
    let port;
    for (let attempt = 0; attempt < 100; attempt++) {
        if (launchError) throw launchError;
        try { port = (await readFile(join(profile, 'DevToolsActivePort'), 'utf8')).split('\n')[0]; break; }
        catch { await delay(100); }
    }
    if (!port) throw new Error(`Headless browser did not start (exit ${chrome.exitCode}): ${browserErrors.slice(-1200)}`);
    const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
    const send = await connect(targets.find(target => target.type === 'page').webSocketDebuggerUrl);
    const css = (await Promise.all([
        'public/assets/vendor/bootstrap/css/bootstrap.min.css',
        'public/assets/css/style.css',
        'public/assets/css/hr/departments.css',
    ].map(path => readFile(resolve(path), 'utf8')))).join('\n');
    await send('Page.enable');
    await send('Network.enable');
    await send('Network.setBlockedURLs', { urls: ['http://*', 'https://*'] });
    await mkdir(directory, { recursive: true });
    for (const page of ['index', 'detail']) {
        const markup = await readFile(join(directory, `${page}.html`), 'utf8');
        for (const width of [1440, 375]) {
            await send('Emulation.setDeviceMetricsOverride', { width, height: 1000, deviceScaleFactor: 1, mobile: false });
            const html = markup.replace('<head>', `<head><meta name="viewport" content="width=device-width, initial-scale=1"><style>${css}</style>`)
                .replace('<body>', '<body><header class="fixed-top" style="height:64px;background:#000072;color:white;padding:20px" aria-hidden="true">HR fixture</header>');
            await send('Page.navigate', { url: `data:text/html;charset=utf-8,${encodeURIComponent(html)}` });
            let result;
            for (let attempt = 0; attempt < 50; attempt++) {
                result = await send('Runtime.evaluate', { expression: 'document.readyState', returnByValue: true });
                if (result.result.value === 'complete') break;
                await delay(50);
            }
            const evaluation = await send('Runtime.evaluate', { expression: `(() => {
                const checks = [];
                const check = (name, condition) => { if (!condition) throw new Error(name); checks.push(name); };
                check('no page overflow', document.documentElement.scrollWidth <= innerWidth + 1);
                check('heading below fixed navigation', document.querySelector('.department-heading').getBoundingClientRect().top >= 80);
                check('card has breathing room', parseFloat(getComputedStyle(document.querySelector('.card-body')).paddingTop) >= (innerWidth < 768 ? 20 : 28));
                check('table rows spaced', [...document.querySelectorAll('tbody tr')].every(row => row.getBoundingClientRect().height >= 60));
                check('escaped department name', !document.querySelector('img[onerror]') && document.body.textContent.includes('<img src=x onerror=alert(1)>'));
                const writes = [...document.forms].filter(form => form.method === 'post');
                check('write forms carry CSRF', writes.length > 0 && writes.every(form => form.querySelector('input[name="_token"]')?.value));
                check('visible action buttons', [...document.querySelectorAll('button')].every(button => button.getBoundingClientRect().height > 20));
                document.addEventListener('submit', event => { event.preventDefault(); window.lastSubmission = [...new FormData(event.target)]; });
                if (${JSON.stringify(page)} === 'detail') {
                    const move = document.querySelector('input[name="structure_token"]').form;
                    const select = move.querySelector('select[name="department_id"]');
                    check('empty target invalid', !select.checkValidity());
                    select.selectedIndex = 1;
                    check('target selectable', select.value && select.checkValidity());
                    move.requestSubmit();
                    check('move preserves stale guard', window.lastSubmission.some(([key, value]) => key === 'structure_token' && value.length === 64)
                        && window.lastSubmission.some(([key]) => key === 'expected_department_id'));
                    check('approval reminder visible', document.body.textContent.includes('Jalur approval tetap'));
                } else {
                    const create = document.querySelector('#new-department-name').form;
                    create.querySelector('input[name="name"]').value = 'Browser fixture department';
                    create.requestSubmit();
                    check('create form submits intended name', window.lastSubmission.some(([key, value]) => key === 'name' && value === 'Browser fixture department'));
                    check('detail links exist', [...document.querySelectorAll('a')].some(link => link.textContent.includes('Detail / Edit')));
                }
                return checks;
            })()`, returnByValue: true });
            if (evaluation.exceptionDetails) throw new Error(evaluation.exceptionDetails.exception?.description ?? 'Browser assertion failed');
            checks.push({ page, width, checks: evaluation.result.value });
            const screenshot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
            await writeFile(join(directory, `${page}-${width}.png`), Buffer.from(screenshot.data, 'base64'));
        }
    }
    await writeFile(join(directory, 'report.json'), JSON.stringify({ checks, failures }, null, 2));
    console.log(JSON.stringify({ scenarios: checks.length, assertions: checks.reduce((count, scenario) => count + scenario.checks.length, 0), artifactDirectory: directory }));
} finally {
    for (const socket of sockets) socket.close();
    chrome.kill();
    await delay(500);
    if (!resolve(profile).startsWith(resolve(tmpdir(), 'department-browser-'))) throw new Error('Unexpected browser profile path');
    await rm(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
}
