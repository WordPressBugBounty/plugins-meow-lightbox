=== Meow Lightbox ===
Contributors: TigrouMeow
Tags: lightbox, responsive, exif, media, gps, map, photography, photo, gutenberg, image, images, gallery, retina
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 5.2.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beautiful Lightbox designed for photography, displaying EXIF data. Highly optimized for speed and elegance. You’ll love it!

== Description ==

Responsive Lightbox designed for photography which can also displays EXIF data. This lightbox is highly optimized and designed to be very fast and elegant. You will love it! Here are the main features:

- Responsive Layout. Looks great on mobile, tablets and bigger screens.
- Responsive Images. Image resolution itself will adapt to the screen and device.
- Display Image / EXIF information. Shutter speed, aperture, camera, lens.

What about checking a few examples? Have a look at those:
- With a Gallery: [Nara Dreamland](https://haikyo.org/nara-dreamland/)
- Single Photos: [Best Abandoned Places in Japan](https://offbeatjapan.org/best-abandoned-places-2014/)

By default, the selector is set for the classes '.entry-content, .gallery, .mgl-gallery'. If you need the Lightbox to be active for more selectors, you will need to update the settings. The plugin will apply the lightbox for images contained by the selector.

We believe that choice of the gallery system depends on you. We however recommend you to use the [Meow Gallery](https://wordpress.org/plugins/meow-gallery/).

For more information, please check the [official page](https://meowapps.com/meow-lightbox/).

=== Pro Version ===
Getting the Pro version will support us and the development of the plugin, and also add those features:

- Deep-Linking: allow sharing an URL that will open the Lightbox directly on a specific image.
- Slideshow: you can start a slideshow from the Lightbox.
- Social Sharing: share the image on social networks.
- Animation: the Lightbox can be animated when opening and closing.
- Google Maps: if GPS is available in your image, a map will be available.

Languages: English.

== Installation ==

1. Upload `meow-lightbox` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Upgrade Notice ==

Replace all the files. Nothing else to do.

== Frequently Asked Questions ==

Please have a look at [Meow Lightbox](https://meowapps.com/meow-lightbox/).

== Changelog ==

= 5.2.7 (2025/02/17) =
* Add: Added support for videos in the Default Engine for better media compatibility.
* Fix: Prevented the Default Engine from closing when clicking on a map.
* Update: Improved PhotoSwipe by allowing captions to be shown or hidden when zooming in/out.
* Add: Implemented anti-selector filtering in PhotoSwipe data for better customization.
* Update: Refactored social sharing to use dynamic settings for more flexibility.
* ✨ If you have a moment, please write a little [review for the Meow Lightbox](https://wordpress.org/support/plugin/meow-lightbox/reviews/?rate=5#new-post). Thank you! 💕

= 5.2.6 (2025/01/01) =
* Fix: Hide EXIFs data if none should be displayed.
* Fix: PhotoSwipe Map scales the same way as the Default Engine.
* Fix: Hide the captions on map view for PhotoSwipe.
* Update: Reorganized EXIFs to have Lens and Camera together.
* Fix: Remove Camera Name from Lens to avoid seeing it twice.

= 5.2.5 (2024/12/06) =
* Change: Scaling logic moved to the server-side.
* Fix: Compatibity with Envira Galleries (and more, via mwl_image_attributes).
* Fix: Error "Could Not Update Options".
* Add: addToMeowLightboxQueue to dynamically add images with their metadata to the lightbox.

= 5.2.4 (2024/11/04) =
* Add: Google Maps settings for Map Types.
* Fix: PhotoSwipe Location without GPS data.

= 5.2.3 (2024/10/17) =
* Add: Added a RTF Fix option for the Default Engine.
* Update: Enhanced the code architecture.
* Fix: Photoswipe display.
* Fix: Prevent useless events.

= 5.2.2 (2024/09/19) =
* Add: Photoswipe support, with compatibility with all the features of the Meow Lightbox.
* Add: New Lightbox Engine option, which let you choose between the default engine and PhotoSwipe.
* Fix: Zoom to Full Resolution.
* Fix: Minor fixes.

= 5.2.1 (2024/06/28) =
* Update: Optimized the loading and libs loading.
* Fix: Many minor issues.
* Fix: Download link was not using the original image.

= 5.2.0 (2024/06/15) =
* Fix: Avoid loading scripts when not needed.
* Fix: Issue related to image_meta, and a few other minor issues.

= 5.1.9 (2024/06/05) =
* Update: Cleaner UI and removed useless dependencies.
* Fix: Issue with the loading of the scripts.

= 5.1.8 (2024/05/30) =
* Add: "Selector Ahead" functionality.
* Add: Better logs for debugging and performance.
* Fix: Resolved fullscreen issue.
* Fix: Addressed performance impact by avoiding Image ID resolution by default.
* Fix: Corrected array offset warning on image info.
* Fix: Prevented division by zero for shutter speed.
* Fix: Ensured proper loading of pro scripts with cache.
* Fix: Avoid warnings related to geo_coordinates.

= 5.1.7 (2024/05/13) =
* Update: Only load map scripts when images with GPS data are present and map option is enabled.
* Fix: Corrected the selector issue when "Ahead" option is not used.
* Fix: Improved zoom and close animations in the lightbox.

= 5.1.6 (2024/04/27) =
* Update: Pro scripts now load only on front-end, excluding admin pages.
* Optimization: Added asynchronous loading for Leaflet to prevent conflicts when pre-loaded by other resources.
* Update: Ensured pro scripts load even if lightbox initialization is skipped.
* Fix: Confirmed output buffer is HTML in OB Mode to ensure correct rendering.

= 5.1.4 (2024/03/16) =
* Fix: Avoid issues with non-image media.
* Add: Static CDN support (to retrieve EXIF data for offloaded media).

= 5.1.3 (2024/02/02) =
* Add: Introduced "Selector Ahead" in Performance for back-end class checks.
* Update: Replaced "Hide/Show info" with a "Fullscreen" feature for enhanced viewing.
* Fix: Removed debug log for cleaner operation.

= 5.1.2 (2024/01/20) =
* Add: Maintenance features with export, import, and reset options.
* Update: Implemented map/image icon logic.
* Fix: Resolved issue with no metadata on output buffering due to empty anti-selector.
* Update: Introduced separate galleries based on Selector functionality.

= 5.1.1 (2023/12/25) =
* Update: Single click feature for unzooming images.
* Add: Zoom and Pan functionality for mobile devices.
* Add: Pan support when zoomed and double-click to unzoom.
* Update: Implementation to ignore anti-selectors in lightboxify process.
* 🌲💫 Merry Christmas!

= 5.0.9 (2023/11/29) =
* Add: Support for the collections of the Meow Gallery.
* Add: WordPress Big Image Display support.
* Fix: Compatibility with Beaver Builder.
* Fix: Warning in the Meow GPS box.

= 5.0.8 (2023/11/15) =
* Add: New backdrop opacity setting for enhanced visual customization.
* Add: Animation speed control for dynamic gallery transitions.
* Update: Compact carousel design with integrated navigation and captions for an immersive experience.
* Fix: Resolved various carousel issues including caption visibility, zoom opening on larger carousels, and missing closing - comparison.
* Fix: Addressed lightboxify empty DOM in large galleries and fixed missing wp-image class with output buffering.

= 5.0.7 (2023/11/03) =
* Fix: Calculation for the date.
* Add: Toggle Animation. You can see it in action [here](https://meowapps.com/meow-lightbox/tutorial/).

= 5.0.5 (2023/10/23) =
* Fix: Issue related to the zero date.
* Update: If ran into a Meow Gallery, the lightbox will only be applied to the images of the gallery.

= 5.0.4 (2023/10/02) =
* Fix: Compatibility with PHP 8.2 (DiDom).
* Fix: Size of navigation arrows.
* Fix: Use WordPress timezone for EXIF date.
* Add: New option for Date Timezone Compensation.

= 5.0.3 (2023/09/15) =
* Add: Line returns in captions.
* Add: Caption Ellipsis (for long captions) is now an option.
* Add: HTML support for titles.
* Fix: Removed the potential mwl_data error.

= 5.0.1 (2023/09/12) =
* Fix: It was only displaying the first 12 images of a gallery.

= 5.0.0 (2023/09/01) =
* Update: The plugin was completely refactor, many parts were rewritten as well. This will allow us to add new features more easily.
* Info: I wouldn't be surprised if there are some issues in some cases, please let me know if you find any.

= 4.0.6 (2023/05/30) =
* Fix: Issue with Map Layout.

= 4.0.5 (2023/05/01) =
* Fix: Carousel was only working in the right conditions.
* Add: ALT for all images to please those SEO checkers.
* Update: General cleanup to simplify the code structure.
* Add: Keywords in the EXIF section.

= 4.0.4 (2023/04/09) =
* Fix: Description now allows HTML.
* Fix: Hide nav when only one photo.
* Fix: Could not click the Google Maps buttons.
* Fix: Slideshow was stopping by itself.

= 4.0.2 (2023/03/23) =
* Fix: Restore the common dashboard, and lighter bundle.

= 4.0.1 (2023/03/22) =
* Add: New filter mwl_download_link. If you return an empty link, the icon will be removed.
* Fix: Issue with Output Buffering.
* Update: Huge update, new system. Looks should be the same, but from now, it will be easier to maintain and add new features. Please test it well and let me know if you find any issue.

= 3.2.2 (2022/09/29) =
* Fix: Compatibility with WordPress 6.0.1.
* Add: Latest versions of packages.

= 3.2.1 (2022/04/21) =
* Add: jQuery is not required anymore.
* Fix: There were some random issues.

= 3.1.9 (2022/04/14) =
* Add: Option for Output Buffering. That will avoid issues for some users.

= 3.1.8 (2022/04/07) =
* Fix: No more scrolling of the site with the Lightbox is displayed.
* Fix: There was some issue with the JS and CSS only used when actively used.

= 3.1.7 (2022/02/23) =
* Update: Only load the JS and CSS when the Lightbox is actively used.
* Add: Support for 'mwl-img-disabled' class.

= 3.1.6 (2022/02/20) =
* Add: Zoom level for map.
* Fix: Title was overriden by themes too easily.

= 3.1.5 (2021/09/22) =
* Fix: Low-res could be larger than high-res.
* Fix: Issue with download.
* Update: Common lib 3.6.

= 3.1.4 (2021/09/21) =
* Fix: Issue with low res first not sized correctly.
* Add: A metabox that displays the GPS info (useful for debugging it).

= 3.1.3 (2021/09/18) =
* Fix: Catch error when no srcset or sizes.
* Fix: Don't escape HTML in title/caption.

= 3.1.2 (2021/09/11) =
* Fix: Refresh metadata when media updated through ajax.
* Update: New and lighter architecture.

= 3.1.1 (2021/09/01) =
* Fix: Enhanced security.

= 3.0.9 (2021/07/08) =
* Add: Option for social sharing.
* Update: Sanitize the value for selector and anti selector
* Update: Added a few cameras and lenses.

= 3.0.8 (2021/05/22) =
* Update: More natural zoom.
* Fix: Tiny fixes and improvements.

= 3.0.4 =
* Add: Option for magnification.
* Fix: Avoid breaking pagebuilders.
* Update: Replace Leaftlet by Google Maps SDK.
* Fix: Listen to arrow keys when lighbox is open.

= 3.0.3 =
* Fix: Doesn't break page builders and Rank Math SEO.
* Update: Meow Common 3.3.

= 3.0.2 =
* Add: Option to disable cache.
* Fix: Lightbox navigation arrows keeping hover state on mobile.

= 3.0.1 =
* Update: Avoid issues when reading the PHP Error Logs.
* Update: Re-added the timer/delay for the slideshow.

= 3.0.0 (2020/09/06) =
* Update: Brand new UI on the admin side.

= 2.0.7 =
* Fix: Const was used in a non-transpiled environment (and therefore, not working on old browsers).

= 2.0.6 =
* Fix: Divi Builder was not working when Meow Lightbox was enabled.

= 2.0.5 =
* Fix: Issue in the map when lazyloading was used in the gallery.

= 2.0.4 =
* Add: Option to load a low-res first, to make sure the image appears instantly.
* Fix: Exif data format.
* Fix: Bug with certain theme, only the first image was displayed.

= 2.0.3 =
* Fix: Performance issue related to the DOM.
* Update: Optimized the way the images are prepared.
* Fix: Move lightbox controls down when admin bar is displayed.
* Fix: Wraps EXIF when too long.
* Fix: Byebye to the naturalWidth issue.
* Fix: Hide map icon if... it's disabled.
* Add: Display focal length and capture date.

= 1.7.9 =
* Fix: There was a little notice in the Error Logs for old versions of PHP.

= 1.7.8 =
* Fix: Issue with WooCommerce checkout.

= 1.7.6 =
* Update: Exif analysis code restructured.
* Add: Slideshow feature for Pro.

= 1.7.5 =
* Fix: There was an JS error with ImageLoad.
* Update: Improve sizing of low-res image.
* Fix: Compatibility with IE11.

= 1.7.3 =
* Fix: REST requests with the GET method were handled by the plugin (and they shouldn't).
* Update: Loader SVG was moved inline.

= 1.7.1 =
* Fix: Avoid notices when lens it not available in EXIF.
* Fix: Swipe detection.

= 1.6.9 =
* Fix: Simpler and probably better REST detection.
* Fix: Potential fix for WooCommerce.
* Add: Support more camera names.

= 1.6.8 =
* Fix: Remove the notices about the 'Undefined index: geo_coordinates' and the missing path.
* Add: Filters for all the image information.
* Add: Enable right click option.
* Info: If you like this lightbox, please review it. We absolutely need your help in order to add fresh features. You can do it [here]( https://wordpress.org/support/plugin/meow-lightbox/reviews/?rate=5#new-post). Thanks a lot :)

= 1.6.4 =
* Add: Image Size option (Responsive Images or defined Image Size).
* Add: Low-Res First, Deep-Linking.
* Update: Better loader and cleaner JS.

= 1.6.3 =
* Fix: Issue with OB and REST updates in the Post Editor.
* Update: Dashboard and common librairies refreshed.
* Update: Default settings are now set to use OB Mode and HtmlDomParser. If that brings issues for you, please have a look at this: [For broken HTML / other issues](https://wordpress.org/support/topic/for-broken-html-other-issues/), and try the second piece of code.

= 1.6.1 =
* Fix: There was a little mess-up with the Output Buffering.
* Update: Back-end process go through all images instead of being limited by the selector (selector is activated in the front only).
* Info: Sorry, there was a lot of work done on the plugin this week to make it work everywhere, as always, your feedbacks are really valuable. Thank you so much :)

= 1.5.8 =
* Add: New hidden/internal options.
* Updated: Now use OB, coupled with a fast parsing engine.
* Update: Still trying to find the best compromise compatibility/performance (with default behavior that works on 99.8% of the installs). Now the internals of the plugin can work differently depending on internal options, so if you have an issue, please contact us and we will look into it.

= 1.5.7 =
* Update: Fix HTML issues and rendering.
* Update: Additional compatibility for W3C.

= 1.5.6 =
* Fix: Little issue caused by anti-selector.
* Fix: Captions and EXIF weren't showing in images used outside galleries. The Lightbox is now parsing the DOM to actually get all the required information and should work for everything.
* Info: If you like this lightbox, please review it. We absolutely need your help in order to add fresh features. You can do it [here]( https://wordpress.org/support/plugin/meow-lightbox/reviews/?rate=5#new-post). Thanks a lot :)

= 1.5.4 =
* Note: Many things were fixed in the very last commit, but are linked to the very recent changes. Please have a look at the changes below, and it is also recommended to visit your settings for the Lightbox and click on the Reset Cache button (at the top).
* Update: Refactoring of many parts of the lightbox, settings were simplified.
* Update: Do not use slow asynchronous requests anymore = no more delay, the lightbox works right away, with EXIF caching.
* Update: Icons and styles were reviewed.
* Fix: The little images are not blown out anymore.
* Info: If you like this lightbox, please review it. We absolutely need your help in order to add fresh features. You can do it [here](https://wordpress.org/support/plugin/meow-lightbox/reviews/?rate=5#new-post). Thanks a lot :)

= 1.4.6 =
* Fix: Cache was not always reset accordingly.

= 1.4.4 =
* Fix: GPS was not being cached.
* Fix: Ajax calls optimization.
* Update: Caching optimization.

= 1.4.2 =
* Update: Added Anti Selector (CSS selector) to avoid Lightbox to be applied.
* Fix: Better display of arrows on light photos.
* Fix: There was an issue when the same photo was used twice on the same page.
* Fix: Incompatibility had the bad effect to freeze the website.
* Update: Compatibility with WP 5 and Gutenberg.

= 1.2.2 =
* Fix: Remove Updraft link.
* Update: For speed and confidentiality purposes, top using external CDNs. Inline SVGs are used instead.
* Info: If you like this lightbox, please review it. We absolutely need your help in order to add fresh features. You can do it here: https://wordpress.org/support/plugin/meow-lightbox/reviews/?rate=5#new-post. Thanks a lot :)

= 1.1.2 =
* Add: By default, also add the Lightbox to the Meow Gallery.
* Update: Display a message in the admin if the Permalinks are not enabled (they are required by the Lightbox API).

= 1.1.0 =
* Update: Code cleaning.
* Fix: SSL verify for updates.

= 1.0.6 =
* Add: Swipe.
* Fix: Issue with preloading.
* Fix: Issue with vertical photo.

= 1.0.3 =
* Fix: If there are errors in the EXIF, show images anyway.
* Fix: Incompatibility with older versions of PHP.

= 1.0.0 =
* Add: Map for the Photography Layout, if there are GPS coordinates.
* Add: GPS coordinates for images.
* Update: Better performance.
* Update: Disable PrettyPhoto if it is forced by a theme.

= 0.1.3 =
* Add: Option to choose which caption to display in the lightbox (caption or description).

= 0.1.2 =
* Update: Better handing of the API calls.
* Pro: Preloading.

= 0.1.0 =
* Add: Lens information.
* Update: Bigger arrows.
* Update: Camera info, from now this data will be made "nicer" to look at little by little.

= 0.0.7 =
* Add: Theme (Dark or Light)
* Add: Layout (Minimal)
* Update: Improved options.

= 0.0.3 =
* Fix: Was catching too many JS key events.
* Add: Swiping images
* Add: 'Close' button

= 0.0.1 =
* First release.

== Screenshots ==

1. Lightbox displaying EXIF information
