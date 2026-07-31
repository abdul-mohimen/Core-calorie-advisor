import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const viewports = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'tablet', width: 810, height: 1080 },
  { name: 'mobile-landscape', width: 844, height: 390 },
  { name: 'mobile-portrait', width: 390, height: 844 }
];

(async () => {
  console.log('Starting trainer portal visual audit...');
  
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  
  console.log('Logging in as trainer via session injection...');
  await page.goto('http://127.0.0.1/Core%20calorie%20advisor/_audit/set_session.php');
  await page.waitForLoadState('networkidle');
  console.log('Session injected.');

  const outDir = path.join(__dirname, 'compare', 'trainer');
  if (!fs.existsSync(outDir)) {
    fs.mkdirSync(outDir, { recursive: true });
  }

  // Go explicitly to trainer portal
  await page.goto('http://127.0.0.1/Core%20calorie%20advisor/portals/trainer.php');
  
  // Wait for the main container to load
  await page.waitForSelector('.cca-page-container', { timeout: 10000 });
  await page.waitForTimeout(2000); // Give fonts/styles a moment to settle

  for (const vp of viewports) {
    console.log(`Capturing ${vp.name}...`);
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.waitForTimeout(1000); // Wait for resize re-layout
    
    await page.screenshot({ 
      path: path.join(outDir, `after-${vp.name}.png`), 
      fullPage: true 
    });
  }

  await browser.close();
  console.log('Trainer portal audit completed.');
})();
