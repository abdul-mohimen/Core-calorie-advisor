const { chromium } = require('playwright');
const fs = require('fs');

async function capture() {
  if (!fs.existsSync('_audit/intro')) fs.mkdirSync('_audit/intro');

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  // We will load index.php with ?test_intro=1 to force it to show.
  // Actually index.php doesn't include intro.php yet. 
  // Let's just create a dummy page with the intro.
  const html = fs.readFileSync('includes/intro.php', 'utf8');
  await page.setContent(html);

  // Take 10 screenshots over 2.6s
  for (let i = 0; i < 10; i++) {
    await page.waitForTimeout(260); // 260ms per frame
    await page.screenshot({ path: `_audit/intro/frame-${i+1}.png` });
    console.log(`Captured frame ${i+1}`);
  }

  await context.close();
  await browser.close();
}

capture().catch(console.error);
