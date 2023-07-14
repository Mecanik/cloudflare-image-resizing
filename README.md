# Cloudflare Image Resizing plugin for WordPress
 Rewrites images on the fly so you can use the [Cloudflare Image Resizing](https://blog.cloudflare.com/announcing-cloudflare-image-resizing-simplifying-optimal-image-delivery/) service.

 You will speed up your website drastically by offering to browsers AVIF/WEBP images delivered from global locations thanks to Cloudflare.

<img src="https://i.postimg.cc/T2rTSWVL/Screenshot-2023-07-13-at-8-22-47-PM.png">

 Internally the plugin is developed to use the fastest functions from PHP available, without any database calls. There is zero to none overhead in performance, your website will not get slower, only faster.

## Benefits
* Enhances your images to become clearer and much nicer (from tests, wordpress cropping alters the image and becomes lower quality)
* Speeds up your website to offer a better experience for users (images loaded from cloudflare cdn is much faster than your server)
* Improves your SEO (since your website performs much faster)
* Removes strain from your web hosting (asset loading is one of the biggest problems for web servers, especially Apache)
* Saves bandwith on your web hosting (there is a very significant save factor, especially on websites with many images/photos)

## Current features
* Replaces all attachment/single image source URL's
* Replaces all multiple image source set URL's
* Replaces all every other image src found inside the content
* Serves CloudFlare Image Resizing the original image and lets Cloudflare crop it
* Adds missing image default sizes (width/height for Google Page Insights scoring)

### Compatibility
* Latest WordPress and PHP 7+
* All general themes
* MAI Themes and Genesis Framework
* DIVI Themes
* Content Areas (formerly Template Parts)
* Editor plugins like Visual Composer
* Optimization plugins like WP Rocket

### Usage
* Upload the plugin manually via WordPress or FTP, and enable it.
* Set your site URL in the settings page.

<img src="https://i.postimg.cc/QxmhNGnj/Screenshot-2023-07-13-at-8-21-03-PM.png">

### Notes
* Before using this plugin please ensure **you have turned ON** the Cloudflare Image Resizing feature for your domain.
* If your WordPress is inside a sub-folder, remember to tell the plugin this by setting the folder name.
* You can configure quality settings (I do not recommend altering the default settings).
* If you notice images not being re-written, try enabling more "hooks". If it still does not work, open an issue.
* You can configure whitelisting of images coming from Facebook, Twitter, Instagram, PayPal, etc.

### Tips
* Open the developer console in your browser after enabling this plugin to test functionality. Go to Network and then select "Images" tab and see if the all the downloaded images have the Cloudflare Image Resizing format.
<img src="https://i.postimg.cc/wMhxQ1VN/console.jpg">
* If you have filenames similar to image-100x200-100x300.jpg the plugin might not re-write it. Rename your file and re-upload it without any extra sizes in the filename.

### Contribution
 Feel free to contribute with your own functions/methods. Just make sure you tested it properly.

### Special Thanks
 These are the people that spent time and effort to test and help improve the plugin, as well as sponsoring development.

* [Urban Change Lab](https://www.urbanchangelab.com)
* [danmaby](https://github.com/danmaby)
* [zerobounce](https://github.com/zerobounce)
* [swissky](https://github.com/swissky)
* [itsOwen](https://github.com/itsOwen)

### Assistance
 In your are in immediate need of commercial help/advice/assistance, I can offer you my assistance for a small fee.
 Please do contact me via my email or if you cannot do so open an issue.
 
### Support me
 Buy me a coffee to give me more energy and write more code :)
