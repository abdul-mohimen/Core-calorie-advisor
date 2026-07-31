/* Logs in as each demo role and captures that role's dashboard, so the five
   portals can be compared side by side instead of judged one at a time. */
import { chromium } from 'playwright';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = 'http://localhost/Core%20calorie%20advisor';
const OUT = path.join(__dirname, 'after', 'portals');

const ROLES = [
  ['member',  'member@corecalorieadvisor.com',  '/member/dashboard.php'],
  ['trainer', 'trainer@corecalorieadvisor.com', '/trainer/dashboard.php'],
  ['doctor',  'doctor@corecalorieadvisor.com',  '/doctor/dashboard.php'],
  ['patient', 'patient@corecalorieadvisor.com', '/patient/dashboard.php'],
  ['admin',   'admin@corecalorieadvisor.com',   '/admin/dashboard.php'],
];

const theme = process.argv[2] || 'light';

(async () => {
  const { default: fs } = await import('fs');
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch();

  for (const [role, email, url] of ROLES) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    await ctx.addInitScript(t => {
      localStorage.setItem('tf-theme', t);
      sessionStorage.setItem('cca_intro_seen', '1');
    }, theme);
    const p = await ctx.newPage();

    await p.goto(BASE + '/auth/login.php', { waitUntil: 'domcontentloaded' });
    await p.fill('input[name=email]', email);
    await p.fill('input[name=password]', 'cca123');
    await Promise.all([
      p.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
      p.click('button[type=submit]')
    ]);

    const res = await p.goto(BASE + url, { waitUntil: 'domcontentloaded' });
    await p.waitForTimeout(1600);
    await p.evaluate(() => document.getElementById('cca-intro-shell')?.remove());

    const err = await p.evaluate(() => /Fatal error|Warning:|Notice:/.test(document.body.innerText));
    await p.screenshot({ path: `${OUT}/${role}-${theme}.png`, fullPage: false });
    console.log(`  ${res.status()}  ${role.padEnd(8)} phpError=${err}  -> ${role}-${theme}.png`);
    await ctx.close();
  }
  await browser.close();
})();
