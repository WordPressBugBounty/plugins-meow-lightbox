const wpPot = require('wp-pot');
const fs = require('fs');
const path = require('path');

// Generate POT for PHP files
const potContents = wpPot({
  destFile: 'languages/meow-lightbox.pot',
  domain: 'meow-lightbox',
  package: 'Meow Lightbox',
  src: ['**/*.php', '!node_modules/**', '!vendor/**'],
  writeFile: false
});

// Read i18n.js to extract JS strings
const i18nPath = path.join(__dirname, '../app/admin/i18n.js');
const i18nContent = fs.readFileSync(i18nPath, 'utf8');

// Extract strings from __( 'string', 'meow-lightbox' ) pattern
const jsStrings = [];
const regex = /__\(\s*['"]([^'"]+)['"]\s*,\s*['"]meow-lightbox['"]\s*\)/g;
let match;
while ((match = regex.exec(i18nContent)) !== null) {
  jsStrings.push(match[1]);
}

// Build JS strings POT entries
let jsEntries = '\n#. JavaScript strings from app/admin/i18n.js\n';
jsStrings.forEach(str => {
  jsEntries += `\n#: app/admin/i18n.js\nmsgid "${str}"\nmsgstr ""\n`;
});

// Combine PHP POT with JS strings
const finalPot = potContents + jsEntries;

// Write the combined POT file
fs.writeFileSync('languages/meow-lightbox.pot', finalPot);

console.log(`Generated languages/meow-lightbox.pot with ${jsStrings.length} JS strings`);
