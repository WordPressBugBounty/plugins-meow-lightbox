=== Meow Lightbox ===
Contributors: TigrouMeow
Tags: lightbox, responsive, exif, photoswipe, photography
Donate link: https://www.patreon.com/meowapps
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 5.4.9
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The elegant lightbox built for photographers. Fast, responsive, and displays your photos beautifully with EXIF data and maps. You'll love it! 💕

== Description ==

**Meow Lightbox is the photographer's choice for WordPress**. Built from scratch for speed and elegance, it showcases your images beautifully while displaying EXIF data, GPS maps, and metadata—all in a clean, responsive interface.

Stop settling for bloated lightboxes that slow down your site! 😘

Meow Lightbox focuses on what photographers actually need: **Beautiful Presentation** and **Technical Details**. Choose between our custom engine or PhotoSwipe, both optimized for performance. Check out [our official site](https://meowapps.com/meow-lightbox/) to see it in action.

== Core Features ==

📸 **Responsive Design**
Looks stunning on mobile, tablets, and desktop. Images adapt to screen size for optimal viewing on any device.

🎨 **Dual Lightbox Engines**
Switch between our custom Default Engine or PhotoSwipe. Both are optimized for speed and elegance, with full EXIF support.

📊 **EXIF Data Display**
Show camera settings, lens info, shutter speed, aperture, ISO, and capture date—perfect for photography portfolios.

🗺️ **GPS & Maps**
Display shooting location with Google Maps or Leaflet integration when GPS data is available in your images.

== 📸 Photography Features ==

Your photos deserve to shine. Meow Lightbox displays them beautifully with all the technical details photographers love.

**Image Information:**

* Camera and Lens
* Shutter Speed & Aperture
* ISO and Focal Length
* Capture Date & Time
* Keywords and Metadata
* GPS Coordinates

**Display Options:**

* Responsive layout for all devices
* Fullscreen mode
* Zoom and pan on images
* Keyboard navigation
* Touch gestures on mobile

**Gallery Support:**

* Works with [Meow Gallery](https://wordpress.org/plugins/meow-gallery/)
* Compatible with WordPress core galleries
* Supports WooCommerce product images
* Separate galleries mode

== 🎯 Lightbox Engines ==

Choose the engine that fits your needs best. Both are fast, elegant, and fully featured.

**Default Engine:**

* Custom-built for photographers
* Smooth zoom animations
* Clean, minimal interface
* Optimized for EXIF display
* Glass morphism design

**PhotoSwipe:**

* Industry-standard lightbox
* Touch-optimized gestures
* Smooth transitions
* Full EXIF integration
* Modern UI with caption support

== Pro Features ==

* Deep-Linking: Share URLs that open specific images
* Slideshow: Auto-play through your gallery
* Social Sharing: Let visitors share your photos
* Animation: Beautiful open/close transitions
* Google Maps: Display shooting locations
* Priority Support

== Why Meow Lightbox? ==

**Performance First**
No bloat, no unnecessary features. Just clean, fast code that showcases your photos beautifully.

**Built for Photographers**
EXIF data, GPS maps, and metadata display—everything photographers need in one elegant package.

**Flexible**
Works with any gallery plugin or WordPress core galleries. Choose your preferred lightbox engine.

**Developer Friendly**
Clean APIs, WordPress hooks, and filters. Extend it your way.

**Constantly Evolving**
Regular updates based on real photographer feedback. We listen, we improve.

== Installation ==

1. Upload `meow-lightbox` to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu
3. Visit Meow Lightbox in your admin menu
4. Configure your preferences
5. Start showcasing your photos! 🚀

The lightbox activates for `.entry-content`, `.gallery`, and `.mgl-gallery` by default. Customize the selectors in settings if needed.

== Frequently Asked Questions ==

= Does this work with my gallery plugin? =

Yes! Meow Lightbox works with WordPress core galleries, Meow Gallery, and most other gallery plugins. We recommend [Meow Gallery](https://wordpress.org/plugins/meow-gallery/) for the best experience.

= Can I choose which EXIF data to display? =

Absolutely! You control exactly which EXIF fields appear: camera, lens, shutter speed, aperture, ISO, focal length, date, and keywords.

= Which lightbox engine should I use? =

Both are excellent! The Default Engine offers a custom-designed experience optimized for EXIF display. PhotoSwipe is industry-standard with touch-optimized gestures. Try both and see which you prefer!

= Does this work with WooCommerce? =

Yes! Meow Lightbox works great with WooCommerce product images.

= Can I display GPS locations on a map? =

Yes! If your images contain GPS data, you can display shooting locations using Google Maps or Leaflet (Pro feature).

= Is this suitable for large photography websites? =

Absolutely! Meow Lightbox is optimized for performance and works beautifully with sites of any size.

= Does this support keyboard navigation? =

Yes! Use arrow keys to navigate, ESC to close, and keyboard shortcuts for all controls.

= What about multilingual sites? =

Meow Lightbox works great with translation plugins and multilingual setups.

== Changelog ==

= 5.4.9 (2026/01/27) =
* Fix: Improve shutter speed handling by converting string values to floats.
* Fix: Speed up thumbnail loading by cleaning URLs before checking for related media attachments.
* Fix: Ensure deep linking only opens after the page has fully rendered to prevent broken or partial views.
* Update: Improve mobile and tablet detection by using the browser’s user agent.
* Update: Allow Orphans to support custom width and height attributes.
* 🎵 Discuss with others about Meow Lightbox on [the Discord](https://discord.gg/bHDGh38).
* 🌴 Keep us motivated with [a little review here](https://wordpress.org/support/plugin/meow-lightbox/reviews/). Thank you!
* 🥰 If you want to help us, check our [Patreon](https://www.patreon.com/meowapps). Thank you!

= 5.4.8 (2026/01/05) =
* Update: Improve image matching and lazy loading by using data-src instead of src when available.  
* Fix: Improve display and interaction on small screens, including forcing arrow visibility on mobile devices.  
* Add: Support responsive images by handling srcset data for better image quality and performance.  
* Add: Introduce a "Skip dynamic" option to fetch images as orphans for setups not using wp-json.  
* Add: Enable the Metadata Toggle feature across all themes.  
* Add: Support infinite loading with Meow Gallery.  
* Fix: Improve lightbox behavior to properly handle rerendering while it remains open.  
* Add: Introduce a new "Flat Light" visual theme for the lightbox.

= 5.4.7 (2025/12/14) =
* Fix: Hotfix to resolve issues with duplicated cache entries.

= 5.4.6 (2025/12/07) =
* Fix: Correct handling of anti-selectors on parent classes so elements are excluded from the lightbox as expected.
* Update: Clear the plugin cache automatically when a post is saved to ensure changes are reflected immediately.
* Fix: Resolve an issue where the disable_cache setting was always treated as false in REST API requests.
* Add: Introduce an option to enable JavaScript logs for easier debugging and support.
* Add: New 'include_orphans' option to include media not in the WordPress Media Library in the lightbox.

= 5.4.5 (2025/12/04) =
* Update: Refactored how styles are loaded.  
* Update: Improved the way anti-selectors are handled.  
* Add: Added caching for dynamic content to reduce REST API calls.  
* Fix: Corrected the regenerate MWL data process.

= 5.4.4 (2025/12/03) =
* Fix: Prevent long image captions from running off the screen.  
* Fix: Correct EXIF data display in the Flat theme.

= 5.4.3 (2025/12/03) =
* Fix: Resolve a PHP warning related to image metadata handling for smoother media processing.
* Fix: Prevent an error when combining keyword values to ensure keyword handling works reliably.

= 5.4.2 (2025/12/01) =
* Fix: Adjusted Flat theme captions so they no longer propagate clicks or move when toggling animations.  
* Add: Introduced orientation-based caption classes and a new Flat theme orientation setting.  
* Update: Simplified the Flat theme by forcing the "below" display.  
* Add: Added a rendering delay option and improved handling of static and dynamic content, including a renderMeowLightboxWithSelector function and more reliable media queuing.  
* Fix: Improved EXIF time handling by using EXIF OffsetTime (0x9010).  
* Fix: Made the Flat theme use only lightboxified elements, optimized JavaScript selectors, and ensured captions no longer get stuck hidden.  
* Fix: Updated data transformation and regeneration functions to better handle null or undefined inputs and reliably find attachments by ID.  
* Update: Refreshed documentation to cover the new Flat theme options and rendering behavior.

= 5.4.1 (2025/11/25) =
* Add: New Preview Lightbox in the Settings.
* Add: Introduced a new "Metadata Toggle".
* Update: Refreshed the Dark Glass, Light Glass and Plain Dark themes.
* Update: Simplifid the Settings UI.
* Fix: Correct separate galleries skipping the first gallery.
* Fix: Improved caption focus and related events.
* Fix: Prevent magnification from zooming when disabled.
* Fix: Hide the social sharing icon unless deep linking is enabled.
* 🎵 Discuss with others about Meow Lightbox on [the Discord](https://discord.gg/bHDGh38).
* 🌴 Keep us motivated with [a little review here](https://wordpress.org/support/plugin/meow-lightbox/reviews/). Thank you!

= 5.3.8 (2025/11/19) =
* Fix: Hotfix to prevent errors by ensuring selectors are not empty.

= 5.3.7 (2025/11/18) =
- Add: New Cosmic Theme and Glass features.
- Fix: Slideshow intervals for more consistent display.  
- Fix: Cursor issues when hovering over thumbnails.    
- Fix: Styling inconsistencies.  
- Update: Updated MeowKit.

= 5.3.6 (2025/11/12) =
* Update: Added option to toggle fullscreen mode from settings.
* Update: Disabled fullscreen mode when map is open.
* Update: Preserved fullscreen state across different media.
* Update: Improved accessibility with labels and focus navigation.
* Fix: Hidden elements with display none when zoomed in.
* Fix: Hotfix for charset handling in HtmlDomParser.
* Fix: Corrected mobile meta size to prevent images from jumping during load.
* Update: Added "Hide Arrows On Mobile" option for cleaner mobile view.

= 5.3.5 (2025/09/29) =
* Add: GPS feature with direct link to Google Maps.
* Fix: Optimized PhotoSwipe for better performance.
* Fix: Removed unnecessary logs.
* Update: Moved ALT attribute handling.

= 5.3.4 (2025/08/27) =
* Fix: Resolved overlapping checkboxes.
* Fix: Fixed PhotoSwipe AntiSelector logic to correctly handle images with same ID.

= 5.3.3 (2025/08/16) =
* Add: Support for "Separate Galleries" feature when using PhotoSwipe.
* Update: Enhanced PhotoSwipe UI with sleek, liquid glass finish.
* Fix: Corrected missing or incorrect ALT text for images.
* Fix: Reversed PhotoSwipe data order to ensure thumbnails display correctly.
* Fix: Resolved issue where PhotoSwipe captions weren't visible when animations enabled.

= 5.3.2 (2025/07/23) =
* Add: Leaflet images that were missing to improve map visuals.

= 5.3.1 (2025/07/01) =
* Update: Improved zoom functionality for better mobile experience.
* Fix: Refined initialization process to prevent unwanted Meow Common messages.

= 5.3.0 (2025/05/04) =
* Add: Support for keyboard navigation.

= 5.2.9 (2025/05/01) =
* Fix: Ensured end_ob function only flushes if output buffering is active.
* Add: Added internationalization support for translations.
* Fix: Removed "auto" value from file sizes in image attachment handling.
* Add: Introduced deep linking slug option to customize URL structure.
* Update: Dynamically imported PhotoSwipe CSS with namespace to avoid conflicts.

= 5.2.8 (2025/03/12) =
* Update: Improved MIME type detection for better media compatibility.
* Fix: Resolved warning related to MIME type handling.
* Add: Added logging for media ID resolution and DOM element detection.
* Update: Refactored PhotoSwipe captions to render properly, matching Default behavior.
* Update: Enhanced captions with scrolling, improved layout, and better visibility.

= 5.2.7 (2025/02/17) =
* Add: Support for videos in Default Engine.
* Fix: Prevented Default Engine from closing when clicking on map.
* Update: Improved PhotoSwipe by allowing captions to show/hide when zooming.
* Add: Implemented anti-selector filtering in PhotoSwipe data.
* Update: Refactored social sharing to use dynamic settings.
* ✨ If you have a moment, please write a little [review for Meow Lightbox](https://wordpress.org/support/plugin/meow-lightbox/reviews/?rate=5#new-post). Thank you! 💕

= 5.2.6 (2025/01/01) =
* Fix: Hide EXIF data if none should be displayed.
* Fix: PhotoSwipe Map scales same way as Default Engine.
* Fix: Hide captions on map view for PhotoSwipe.
* Update: Reorganized EXIF to have Lens and Camera together.
* Fix: Remove Camera Name from Lens to avoid seeing it twice.

= 5.2.5 (2024/12/06) =
* Change: Scaling logic moved to server-side.
* Fix: Compatibility with Envira Galleries (and more, via mwl_image_attributes).
* Fix: Error "Could Not Update Options".
* Add: addToMeowLightboxQueue to dynamically add images with metadata.

= 5.2.4 (2024/11/04) =
* Add: Google Maps settings for Map Types.
* Fix: PhotoSwipe Location without GPS data.

= 5.2.3 (2024/10/17) =
* Add: RTF Fix option for Default Engine.
* Update: Enhanced code architecture.
* Fix: PhotoSwipe display issues.

= 5.2.2 (2024/09/19) =
* Add: PhotoSwipe support with compatibility for all Meow Lightbox features.
* Add: New Lightbox Engine option (choose between Default Engine and PhotoSwipe).
* Fix: Zoom to Full Resolution.

= 5.2.1 (2024/06/28) =
* Update: Optimized loading and library loading.
* Fix: Many minor issues.
* Fix: Download link was not using original image.

= 5.2.0 (2024/06/15) =
* Fix: Avoid loading scripts when not needed.
* Fix: Issue related to image_meta and other minor issues.

= 5.1.9 (2024/06/05) =
* Update: Cleaner UI and removed useless dependencies.
* Fix: Issue with script loading.

= 5.1.8 (2024/05/30) =
* Add: "Selector Ahead" functionality.
* Add: Better logs for debugging and performance.
* Fix: Resolved fullscreen issue.
* Fix: Addressed performance impact by avoiding Image ID resolution by default.

= 5.1.7 (2024/05/13) =
* Update: Only load map scripts when images with GPS data are present.
* Fix: Corrected selector issue when "Ahead" option not used.
* Fix: Improved zoom and close animations.

= 5.1.6 (2024/04/27) =
* Update: Pro scripts now load only on front-end, excluding admin pages.
* Optimization: Added asynchronous loading for Leaflet.

= 5.1.4 (2024/03/16) =
* Fix: Avoid issues with non-image media.
* Add: Static CDN support (to retrieve EXIF data for offloaded media).

= 5.1.3 (2024/02/02) =
* Add: Introduced "Selector Ahead" in Performance settings.
* Update: Replaced "Hide/Show info" with "Fullscreen" feature.

= 5.1.2 (2024/01/20) =
* Add: Maintenance features with export, import, and reset options.
* Update: Implemented map/image icon logic.
* Update: Introduced separate galleries based on Selector functionality.

= 5.1.1 (2023/12/25) =
* Update: Single click feature for unzooming images.
* Add: Zoom and Pan functionality for mobile devices.
* 🌲💫 Merry Christmas!

= 5.0.9 (2023/11/29) =
* Add: Support for Meow Gallery collections.
* Add: WordPress Big Image Display support.
* Fix: Compatibility with Beaver Builder.

= 5.0.8 (2023/11/15) =
* Add: New backdrop opacity setting for enhanced visual customization.
* Add: Animation speed control for dynamic gallery transitions.
* Update: Compact carousel design with integrated navigation.

= 5.0.7 (2023/11/03) =
* Fix: Calculation for date.
* Add: Toggle Animation. See it in action [here](https://meowapps.com/meow-lightbox/tutorial/).

= 5.0.5 (2023/10/23) =
* Fix: Issue related to zero date.
* Update: If ran into Meow Gallery, lightbox only applies to gallery images.

= 5.0.4 (2023/10/02) =
* Fix: Compatibility with PHP 8.2 (DiDom).
* Fix: Size of navigation arrows.
* Fix: Use WordPress timezone for EXIF date.
* Add: New option for Date Timezone Compensation.

= 5.0.3 (2023/09/15) =
* Add: Line returns in captions.
* Add: Caption Ellipsis (for long captions) is now an option.
* Add: HTML support for titles.

= 5.0.1 (2023/09/12) =
* Fix: It was only displaying the first 12 images of a gallery.

= 5.0.0 (2023/09/01) =
* Update: Complete plugin refactor. Many parts rewritten for better maintainability.

== Screenshots ==

1. Lightbox displaying EXIF information
