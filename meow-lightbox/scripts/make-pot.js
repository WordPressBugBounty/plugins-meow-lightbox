const wpPot = require('wp-pot');

// Every string lives in PHP, including the lightbox labels (Meow_MWL_Core::get_lightbox_i18n).
wpPot({
  destFile: 'languages/meow-lightbox.pot',
  domain: 'meow-lightbox',
  package: 'Meow Lightbox',
  src: ['**/*.php', '!node_modules/**', '!vendor/**'],
});

console.log('Generated languages/meow-lightbox.pot');
