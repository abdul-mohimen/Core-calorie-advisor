const { chromium } = require('playwright');
const path = require('path');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  const fileUrl = `file:///${path.resolve(__dirname, 'styleguide.html').replace(/\\/g, '/')}`;
  
  // Desktop
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(fileUrl);
  await page.waitForTimeout(500); // wait for fonts
  await page.screenshot({ path: path.join(__dirname, 'styleguide-desktop-dark.png'), fullPage: true });

  await page.click('.theme-toggle');
  await page.waitForTimeout(300);
  await page.screenshot({ path: path.join(__dirname, 'styleguide-desktop-light.png'), fullPage: true });

  // Mobile
  await page.setViewportSize({ width: 390, height: 844 });
  await page.click('.theme-toggle'); // back to dark
  await page.waitForTimeout(300);
  await page.screenshot({ path: path.join(__dirname, 'styleguide-mobile-dark.png'), fullPage: true });
  
  await page.click('.theme-toggle'); // light
  await page.waitForTimeout(300);
  await page.screenshot({ path: path.join(__dirname, 'styleguide-mobile-light.png'), fullPage: true });

  await browser.close();
  console.log('Screenshots captured');
})();
