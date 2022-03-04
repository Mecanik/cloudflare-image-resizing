<?php
/**
* Author: Mecanik
* Author URI: https://github.com/Mecanik/
* 
* Copyright (c) 2021 - 2022 Mecanik
* 
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
**/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit; 
}

// You MUST set your website URL here, otherwise the plugin will not WORK.
// NO TRAILING SLASH (/)
define('CF_IMAGE_RESIZING_SITE_URL', '');

// You MUST set your website FOLDER here, for example if it's in a sub-folder.
// If you installed in "/blog" for example, then enter "/blog", otherhwise just
// leave this EMPTY.
define('CF_IMAGE_RESIZING_SITE_FOLDER', '');

// This defines your web hosting HOME path, like: "/home/.../public_html/"
// ABSPATH should work but if you notice any errors set this manually yourself.
define('CF_IMAGE_RESIZING_HOME_DIR', ABSPATH);

/******************************************************************************/
// Enable (TRUE) or Disable (FALSE) hooks on specific functions to alter URLs.

// NOTE: You do not need to enable all of them, only in case you are using Divi
// theme for example or if you see that some images are not being delivered via
// Cloudflare. You should check this in your browser (Inspect -> Network).

// Hook -> wp_get_attachment_image_src
define('CF_IMAGE_RESIZING_HOOK_1', TRUE);

// Hook -> wp_calculate_image_srcset
define('CF_IMAGE_RESIZING_HOOK_2', TRUE);

// Hook -> wp_get_attachment_url
define('CF_IMAGE_RESIZING_HOOK_3', FALSE);

// Hook -> attribute_escape
define('CF_IMAGE_RESIZING_HOOK_4', FALSE);

// Hook -> clean_url
define('CF_IMAGE_RESIZING_HOOK_5', FALSE);

// Hook -> the_content
define('CF_IMAGE_RESIZING_HOOK_6', TRUE);

/******************************************************************************/
// Configure default Cloudflare Image Resizing options
// https://developers.cloudflare.com/images/image-resizing/url-format#options

// Recommended: 'crop'
define('CF_IMAGE_RESIZING_FIT', 'crop');

// Recommended: 80
define('CF_IMAGE_RESIZING_QUALITY', 80);

// Recommended: 'auto'
define('CF_IMAGE_RESIZING_FORMAT', 'auto');

// Recommended: 'redirect'
define('CF_IMAGE_RESIZING_ONERROR', 'redirect');

// Recommended: 'none'
define('CF_IMAGE_RESIZING_METADATA', 'none');

/******************************************************************************/
// Extra options - Enable (TRUE) or Disable (FALSE) any of them.

// If enabled this will remove the size specification from the image URL, which
// in result will give Cloudflare the original image for resizing.
// Example: /wp-content/uploads/2020/07/project-9-1200x848.jpg
// Becomes: /wp-content/uploads/2020/07/project-9.jpg
// NOTE: Only takes effect when the plugin can get the width + height properly.

define('CF_IMAGE_RESIZING_STRIP_SIZES', TRUE);

// If enabled this will check all your <img /> tags and add the missing width
// and height. This helps your score in Google Pagespeed tests.
// NOTE: Requires CF_IMAGE_RESIZING_HOOK_6 to be enabled.

define('CF_IMAGE_RESIZING_ADD_MISSING_SIZES', TRUE);

// If enabled, fixes the wrong width and height in the final cloudflare URL
// due to the bugged and shit way of how "vc_single_image-img" works.
// NOTE: Requires CF_IMAGE_RESIZING_HOOK_6 to be enabled.

define('CF_IMAGE_RESIZING_FIX_VC_COMPOSER', FALSE);

// If enabled, it will bypass images that are coming from Facebook, Twitter...

define('CF_IMAGE_RESIZING_WHITELIST', FALSE);
define('CF_IMAGE_RESIZING_WHITELIST_URLS', [ 'fbcdn.net', 'twimg.com', 'cdninstagram.com', 'paypalobjects.com' ]);