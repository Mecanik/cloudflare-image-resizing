=== Cloudflare Image Resizing - Optimize & Accelerate Your Images ===
Contributors: Mecanik
Donate link: https://github.com/sponsors/Mecanik
Tags: image, image-optimization, image-resizing, cloudflare images, optimizer, optimize, cloudflare, cloudflare-image-resizing, resize-images, performance, pagespeed, core web vitals, seo, speed, smush, jpg, png, gif, compression, compress, images, pictures, reduce-image-size, image-optimize
Requires at least: 5.0
Tested up to: 6.4.3
Stable tag: 1.5.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Optimize images on-the-fly using Cloudflare's Image Resizing service, improving performance and core web vitals.

== Description ==

Elevate your site's performance with this plugin that dynamically optimizes and resizes images using the [Cloudflare Image Resizing](https://blog.cloudflare.com/announcing-cloudflare-image-resizing-simplifying-optimal-image-delivery/) service.

Experience a significant speed boost by delivering AVIF/WEBP images from Cloudflare's global locations directly to your users' browsers.

The plugin utilizes the fastest available PHP functions, without any database calls, ensuring minimum overhead in performance. Your website's speed will not be compromised, but it will noticeably improve.

### Benefits ###

* Refines your images, delivering higher clarity and enhanced quality, as opposed to standard WordPress cropping which often reduces image quality.
* Supercharges your website speed, offering users an improved, faster experience by loading images from Cloudflare CDN, far quicker than traditional servers.
* Boosts your SEO significantly due to superior website performance and speed.
* Relieves your web hosting of heavy load, as asset loading is a prominent issue for servers, particularly Apache.
* Conserves substantial bandwidth on your web hosting, an especially noticeable benefit for websites featuring numerous images/photos.

### How does this work? ###

The Cloudflare Image Resizing plugin for WordPress enhances your site's speed and performance by automating image resizing using Cloudflare's advanced technology. But how exactly does this work? Let's simplify it.

Usually, when you upload an image to your WordPress site, it creates multiple sizes of the image to fit different screen sizes, which can slow down your site. This plugin eliminates that issue.

The plugin taps into WordPress's image management functions and changes the final URLs of the images in your site's HTML code. Instead of pointing to the images on your server, these URLs now point to Cloudflare's Image Resizing service.

When users visit your site, they aren't just served images. Cloudflare resizes and optimizes these images on-the-fly, ensuring they're perfectly sized for their device, enhancing load times and user experience. All this happens behind the scenes, making your website faster without any extra effort from you!

### Current features ###

* Substitutes all attachment/single image source URLs.
* Replaces all multiple image source set URLs.
* Revises all other image source URLs found within the content.
* Provides the original image to Cloudflare Image Resizing service for efficient cropping.
* Supplements missing image default sizes (width/height), enhancing Google Page Insights scores.

### Compatibility ###

* Latest WordPress and PHP 7+
* All general themes
* MAI Themes and Genesis Framework
* DIVI Themes
* Content Areas (formerly Template Parts)
* Editor plugins like Visual Composer
* Optimization plugins like WP Rocket

### Notes ###

* Before using this plugin please ensure **you have turned ON** the Cloudflare Image Resizing feature for your domain.
* If your WordPress resides in a sub-folder, adjust the plugin settings by specifying the folder name.
* You have the option to tweak quality settings, though it's generally best to stick with the default configuration.
* If images aren't being re-written as expected, consider enabling more "hooks". If the issue persists, feel free to open a support ticket.
* The plugin allows for whitelisting of images sourced from platforms like Facebook, Twitter, Instagram, PayPal, etc.


### Quick Guide and Tips ###

#### Checking if Cloudflare Image Resizing is Working ####

Once you've activated the Cloudflare Image Resizing plugin, you might be wondering how to check if it's doing its job. Here's a quick way to verify:

1. With your site open, activate the developer console in your browser. This is typically done by right-clicking on your webpage and selecting "Inspect" or "Inspect Element".
2. In the console, navigate to the "Network" tab.
3. Reload your webpage. You should now see a list of items that are being loaded on your page. You're interested in the images.
4. Look for any image files in this list (they'll typically end in .jpg, .png, etc.). Click on an image to view more details.
5. Look at the URL or the response headers for that image. If you see reference to the Cloudflare Image Resizing format, it means your plugin is working correctly and your images are being optimized.

#### Addressing Filename Issues ####

The Cloudflare Image Resizing plugin works by identifying the images on your site and applying optimizations. However, it can get confused if the filenames of your images are too complex or contain multiple dimensions.

For example, an image file named "image-100x200-100x300.jpg" might not be processed correctly. If you find that some images aren't being resized, consider renaming these files and re-uploading them to your site. Simpler names, like "image1.jpg" or "product-shot.jpg", are usually best.

Remember, the goal of image resizing is to deliver the most optimal version of an image for every user. So, it's always a good idea to test this functionality on different devices and browsers to ensure all your users are getting the best experience possible.

## Need Expert Support? ##

Feeling stuck? For hands-on help optimizing your plugin settings or improving your WordPress site, I'm here for you.

Check out my [Consulting Services](https://mecanik.dev/en/consulting/) and let's take your website to the next level.

## Disclaimer ##

Please note that this plugin is developed and maintained independently, and is not officially affiliated with or endorsed by Cloudflare Inc. This plugin simply makes use of the image resizing feature offered by Cloudflare's services. All trademarks and copyrights belong to their respective owners. For any issues related to Cloudflare's services themselves, please contact Cloudflare's support directly.

== Installation ==

= If you're installing from your WordPress Dashboard =

1. Navigate to "Plugins" and then click on "Add New".
2. In the search bar, type in "Cloudflare Image Resizing" and hit enter.
3. Once you see our plugin, click on the "Install Now" button.
4. After the installation is done, you can activate the Cloudflare Image Resizing plugin from your Plugins page.

= If you're installing from WordPress.org =

1. Download the Cloudflare Image Resizing plugin.
2. Next, you'll need to upload the "cf-image-resizing" directory. You can do this by using a file transfer protocol (like FTP, SFTP, SCP, etc.) to move it to your "/wp-content/plugins/" directory.
3. Once the upload is complete, go back to your WordPress site and navigate to your Plugins page.
4. Here you'll see Cloudflare Image Resizing listed among your plugins. Just click on the "Activate" button next to it, and you're all set!

== Frequently Asked Questions ==

= Will this plugin slow down my website? =
Not at all. The plugin has been engineered with a focus on speed optimization. It eliminates database calls and utilizes highly efficient PHP functions to ensure optimal performance, ultimately enhancing your site's speed.

= Will this affect my existing SEO? =
No, this plugin is designed to improve your SEO, not hinder it. Faster load times from image optimization contribute positively to your site's SEO rankings. Better page speed, improved performance, and optimized images can boost your site's position on search engine results pages.

= Will this affect my existing URLs? =
The plugin will replace your existing image URLs with URLs from Cloudflare's Image Resizing service in the final HTML output. However, this should not affect the rest of your site's URLs or overall site functionality.

= Does this preserve image metadata? =
The plugin is focused on optimizing and resizing images, and it removes image metadata. You can change this by editing the config.php, please see below.

= Is Cloudflare Image Resizing feature free to use? =
Cloudflare Image Resizing feature is a paid add-on in their Pro, Business, and Enterprise plans, and not available in the free plan. For the most current information, please refer to the Cloudflare website.

= Does this plugin optimize images in real-time? =
Yes, this plugin optimizes and resizes your images on-the-fly. As soon as an image is requested, it is processed and served by Cloudflare's Image Resizing service, providing optimal image delivery with minimal delay.

= Will this plugin help improve my Google Page Insights score? =
Absolutely. By optimizing your images and speeding up your website, this plugin can help to improve your Google Page Insights score. The quicker your site loads, the better your user experience and the higher your potential score.

= How do I configure the Cloudflare Image Resizing options? =
Please visit the settings page. Each option is explained in detail for easy understanding.

= Can I enable extra options for image resizing? =
Please visit the settings page. Each option is explained in detail for easy understanding.

= Can I exclude specific image sources from Cloudflare Image Resizing? =
Yes, you can. However you need to edit manually the config.php as of this writing. If CF_IMAGE_RESIZING_WHITELIST is set to TRUE, the plugin will bypass images coming from the domains listed in CF_IMAGE_RESIZING_WHITELIST_URLS. This is useful for excluding images from sites like Facebook, Twitter, etc.

== Screenshots ==
1. Example resized image savings
2. Example plugin setup

== Changelog ==

##### Version 1.5.5

- Tested up to WordPress 6.4.3
- Support more images

##### Version 1.5.4

- Tested up to WordPress 6.4.1
- Fixed bug in plugin deactivation feedback

##### Version 1.5.3

- Added deactivation feedback
- Fixed bug with shortcut links
- General code improvements

##### Version 1.5.2

- Fixed Spelling

##### Version 1.5.1

- Automatic configuration on new install
- New settings page
- Bugfixes and improvements
- SVG support (sanitize SVGs, but not resize them)

##### Version 1.5

- Applied latest changes from Github

##### Version 1.4

- Tested up to WordPress 6.2.2
- Updated readme

##### Version 1.3

- Rewrite: The plugin was re-written to include fixes for all the bugs reported via github, new features (and flexibility to add more features), configurable settings/options and more.
- Tested up to WordPress 5.9.1
- Tested for several weeeks on several large production websites

##### Version 1.2

- Bugfixes and improvements

##### Version 1.0

- Initial release of the plugin