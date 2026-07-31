import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

(async () => {
  console.log('Starting orbit test...');
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  // Connect to the local XAMPP server
  await page.goto('http://localhost/Core%20calorie%20advisor/trainer-viewer.html');
  
  // Wait for 3D container to appear
  await page.waitForSelector('#stage canvas', { timeout: 15000 });
  // Wait a moment for model and textures to load
  await page.waitForTimeout(4000);
  
  const clips = ['idle', 'squat', 'pushup'];
  const outDir = path.join(__dirname, 'after', '3d');
  
  for (const clip of clips) {
    console.log(`Testing clip: ${clip}`);
    const clipDir = path.join(outDir, clip);
    fs.mkdirSync(clipDir, { recursive: true });
    
    // Switch mode
    await page.evaluate((mode) => {
       if (typeof window.setMode === 'function') {
         window.setMode(mode);
       }
    }, clip);
    
    // Give time to blend animation
    await page.waitForTimeout(1000);
    
    // Orbit camera 360 in 90-degree chunks, taking screenshots
    for (let i = 0; i < 4; i++) {
       const deg = i * 90;
       const rad = deg * (Math.PI / 180);
       
       await page.evaluate((r) => {
         if (window.tfCamera && window.tfControls) {
           const dist = window.tfControls.getDistance();
           window.tfCamera.position.x = window.tfControls.target.x + dist * Math.sin(r);
           window.tfCamera.position.z = window.tfControls.target.z + dist * Math.cos(r);
           window.tfControls.update();
         }
       }, rad);
       
       await page.waitForTimeout(300); // let frame render
       await page.screenshot({ path: path.join(clipDir, `orbit-${deg}deg.png`) });
    }
  }
  
  console.log('Orbit test completed.');
  await browser.close();
})();
