# Cloudflare Image Resizing plugin for WordPress
The current Cloudflare plugin for WordPress does not replace URL's automatically for you to use the Image Resizing feature. This plugin does.
You will speed up your website drastically by offering to browsers AVIF/WEBP images.

## Current features
* Replaces all single image source URL's (src)
* Replaces all multiple image source URL's (srcset)
* Replaces all attachement image source URL's
* Replaces all esc_attr source URL's (solves several problems with themes that use crappy methods to include images, for example Divi Theme uses "esc_attr($logo)")
* Replaces all clean_url source URL's (solves aame problem as above with Divi and similar themes)
* **BONUS**: Adds missing image default sizes (width/height for Google Page Insights scoring)

## Usage
* Upload the plugin manually via WordPress or FTP, and enable it. That simple.

## Notes
* Before using this plugin please ensure **you have turned ON** the Cloudflare Image Resizing feature for your domain.
* At the moment there are no configurable options via WordPress, I did not have time for this. The default settings are: [quality=80,format=auto,onerror=redirect,metadata=none](https://developers.cloudflare.com/images/image-resizing/url-format#options)

## Tips
* Open the developer console in your browser after enabling this plugin to test functionality. Go to "Images" tab and see if the all the downloaded images have the Cloudflare Image Resizing format.

## Contribution
 Feel free to contribute with your own functions/methods. Just make sure you tested it properly.

## Assistance
 In your are in immediate need of commercial help/advice/assistance, I can offer you my assistance for a small fee.
 Please do contact me via my email or if you cannot do so open an issue.
 
## Support me
 Buy me a coffee to give me more energy and write more code :)
