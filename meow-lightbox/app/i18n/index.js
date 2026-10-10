// English fallbacks only. The translated labels come from PHP (Meow_MWL_Core::get_lightbox_i18n,
// localized as mwl_i18n): translate.wordpress.org and Loco cannot see __() calls in the minified bundle.
const translated = window.mwl_i18n || {};

const i18n = {

  ARIA: {
    ZOOM_IMAGE: 'Zoom image',
  },

  TOOLBAR: {
    PREVIOUS: 'Previous',
    NEXT: 'Next',
    ACTIONS: 'Actions',
    PLAY: 'Start Slideshow',
    PAUSE: 'Stop Slideshow',
    SHOW_INFO: 'Show Info',
    HIDE_INFO: 'Hide Info',
    SHOW_IMAGE: 'Show Image',
    SHOW_ON_MAP: 'Show on Map',
    SHOW_METADATA: 'Show Metadata',
    HIDE_METADATA: 'Hide Metadata',
    DOWNLOAD: 'Download',
    CLOSE: 'Close',
    CLOSE_SHARING: 'Close sharing options',
    IMAGE_ERROR: 'The image cannot be loaded',
    IMAGE_CAPTION_AND_METADATA: 'Image caption and metadata',
    SHARE: 'Share',
    FULLSCREEN: 'Fullscreen',
    EXIT_FULLSCREEN: 'Exit Fullscreen',
  },

  EXIF: {
    CAMERA_MODEL: 'Camera model',
    LENS: 'Lens',
    FOCAL_LENGTH: 'Focal length',
    SHUTTER_SPEED: 'Shutter speed',
    APERTURE: 'Aperture',
    ISO: 'ISO',
    DATE: 'Date taken',
    KEYWORDS: 'Keywords',
    COPYRIGHT: 'Copyright',
    AUTHOR: 'Author',
  },

};

Object.keys( i18n ).forEach( group => Object.assign( i18n[group], translated[group] ) );

export default i18n;
