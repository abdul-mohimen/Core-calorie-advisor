import { chromium } from 'playwright';

async function capture() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  
  await page.goto('http://localhost/Core%20calorie%20advisor/_audit/brand-options/index.html');
  await page.waitForTimeout(1000);
  await page.screenshot({ path: '_audit/brand-options/preview.png', fullPage: true });
  
  await context.close();
  await browser.close();
  console.log('Logo preview captured.');
}

capture().catch(console.error);
