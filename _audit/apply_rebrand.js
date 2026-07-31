const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');

const replacements = [
  { from: /Titan Forge/g, to: 'Core Calorie Advisor' },
  { from: /TITAN FORGE/g, to: 'CORE CALORIE ADVISOR' },
  { from: /titanforge\.com/g, to: 'corecalorieadvisor.com' },
  { from: /titan123/g, to: 'cca123' },
  { from: /Titan/g, to: 'CCA' },
  { from: /TITAN/g, to: 'CCA' }
];

// Avoid replacing in these paths/files or code-specific things
const excludeDirs = ['node_modules', 'vendor', '.git', 'assets', '_audit'];
const excludeExts = ['.png', '.jpg', '.mp4', '.glb', '.fbx', '.css', '.js']; 

function processDir(dir) {
  const files = fs.readdirSync(dir);
  for (const file of files) {
    const fullPath = path.join(dir, file);
    const stat = fs.statSync(fullPath);
    
    if (stat.isDirectory()) {
      if (!excludeDirs.includes(file)) {
        processDir(fullPath);
      }
    } else {
      const ext = path.extname(file);
      if (!excludeExts.includes(ext)) {
        let content = fs.readFileSync(fullPath, 'utf8');
        let changed = false;
        
        // Safety: Do not rename variables or DB schema names if possible
        // We'll replace globally but we excluded CSS and JS to be safe.
        // We still need to replace in SQL (seed data) and PHP.
        for (const r of replacements) {
          if (r.from.test(content)) {
            // Check if it's safe (e.g. not part of a filename like titan3d.js)
            // We'll manually skip replacing 'titan' if it's followed by 3d.js
            content = content.replace(r.from, (match, offset, string) => {
              // Special case for titan3d, titan-rig, titan-
              const nextChars = string.substr(offset, 15);
              if (nextChars.toLowerCase().startsWith('titan3d') || nextChars.toLowerCase().startsWith('titan-rig') || nextChars.toLowerCase().startsWith('titan_')) {
                return match; // don't replace
              }
              return r.to;
            });
            changed = true;
          }
        }
        
        if (changed) {
          fs.writeFileSync(fullPath, content, 'utf8');
          console.log(`Updated: ${fullPath}`);
        }
      }
    }
  }
}

processDir(root);

// Generate the remaining SVGs
const brandDir = path.join(root, 'assets', 'brand');
const favContent = `<svg viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="256" cy="256" r="200" stroke="#FF6B1A" stroke-width="40" stroke-dasharray="900 1200" stroke-linecap="round" transform="rotate(-90 256 256)"/>
  <rect x="180" y="230" width="152" height="52" rx="10" fill="#0F1115"/>
  <rect x="130" y="190" width="60" height="132" rx="12" fill="#0F1115"/>
  <rect x="322" y="190" width="60" height="132" rx="12" fill="#0F1115"/>
</svg>`;

fs.writeFileSync(path.join(brandDir, 'favicon.svg'), favContent);

const monoDark = `<svg viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="256" cy="256" r="200" stroke="#FFFFFF" stroke-width="40" stroke-dasharray="900 1200" stroke-linecap="round" transform="rotate(-90 256 256)"/>
  <rect x="180" y="230" width="152" height="52" rx="10" fill="#FFFFFF"/>
  <rect x="130" y="190" width="60" height="132" rx="12" fill="#FFFFFF"/>
  <rect x="322" y="190" width="60" height="132" rx="12" fill="#FFFFFF"/>
</svg>`;
fs.writeFileSync(path.join(brandDir, 'logo-mono-dark.svg'), monoDark);

const monoLight = `<svg viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="256" cy="256" r="200" stroke="#000000" stroke-width="40" stroke-dasharray="900 1200" stroke-linecap="round" transform="rotate(-90 256 256)"/>
  <rect x="180" y="230" width="152" height="52" rx="10" fill="#000000"/>
  <rect x="130" y="190" width="60" height="132" rx="12" fill="#000000"/>
  <rect x="322" y="190" width="60" height="132" rx="12" fill="#000000"/>
</svg>`;
fs.writeFileSync(path.join(brandDir, 'logo-mono-light.svg'), monoLight);

console.log('Rebrand completed.');
