import { chromium } from 'playwright';

const BASE_URL = 'http://localhost/Core%20calorie%20advisor';
const VIEWPORTS = [
  { width: 1920, height: 1080 },
  { width: 1440, height: 900 },
  { width: 768, height: 1024 },
  { width: 390, height: 844 }
];
const ROLES = [
  { portal: 'admin', email: 'admin@titanforge.com', pass: 'titan123', startUrl: '/portals/admin.php' },
  { portal: 'member', email: 'member@titanforge.com', pass: 'titan123', startUrl: '/portals/member.php' },
  { portal: 'patient', email: 'patient@titanforge.com', pass: 'titan123', startUrl: '/portals/patient.php' },
  { portal: 'trainer', email: 'trainer@titanforge.com', pass: 'titan123', startUrl: '/portals/trainer.php' },
  { portal: 'doctor', email: 'doctor@titanforge.com', pass: 'titan123', startUrl: '/portals/doctor.php' }
];

async function capture() {
  const browser = await chromium.launch({ headless: true });
  for (const role of ROLES) {
    for (const vp of VIEWPORTS) {
      const context = await browser.newContext({ viewport: vp });
      const page = await context.newPage();
      
      // Login
      await page.goto(`${BASE_URL}/auth/login.php`);
      await page.fill('input[name="email"]', role.email);
      await page.fill('input[name="password"]', role.pass);
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
      
      // Navigate and screenshot
      await page.goto(`${BASE_URL}${role.startUrl}`);
      await page.waitForTimeout(2000);
      await page.screenshot({ path: `_audit/before/${role.portal}/dashboard-${vp.width}x${vp.height}.png`, fullPage: true });
      
      await context.close();
    }
  }
  
  // 3D Scene sweep
  const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
  const page = await context.newPage();
  await page.goto(`${BASE_URL}/index.php`);
  await page.waitForTimeout(5000); // wait for 3D load
  // (In a real scenario, this would manipulate the Three.js controls via JS evaluation)
  await page.screenshot({ path: `_audit/before/3d/orbit-default.png` });
  await context.close();
  
  await browser.close();
  console.log('Screenshots captured successfully.');
}

capture().catch(console.error);
