<?php
/**
* Plugin Name: CloudFlare Image Resizing
* Plugin URI: https://github.com/Mecanik/cloudflare-image-resizing/
* Description: This plugin will replace Image URL's (including srcset) so you can use the CloudFlare Image Resizing service. As an added bonus it will also add the required width/height for all images that are missing them.
<<<<<<< Updated upstream
* Version: 1.1
=======
* Version: 1.3
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
function cloudflare_single_img( $image )
{
	// Don't alter URL's for Administrator(s)
	if(is_admin()) {
		return $image;
	}
	
    $url = wp_parse_url($image[0]);
	
    $newurl = $url['scheme'] . '://' . $url['host'] . '/cdn-cgi/image/width=' . $image[1] . ',height=' . $image[2] . ',quality=80,format=auto,onerror=redirect,metadata=none' . $url['path'];

    $image[0] = $newurl;

    return $image;
}

function cloudflare_srcset($sources)
{
	// Don't alter URL's for Administrator(s)
	if(is_admin()) {
		return $sources;
	}
	
    foreach ($sources as $key => $value)
	{
        $url = wp_parse_url($value['url']);
        
        $cfparams = '';
		
        if($value['descriptor'] === 'w')
		{
            $cfparams .= 'width=' . $value['value'];
        }
		elseif($value['descriptor'] === 'h')
		{
            $cfparams .= 'height=' . $value['value'];
        }
		
        $cfparams .= ',quality=80,format=auto,onerror=redirect,metadata=none';
		
        $newurl = $url['scheme'] . '://' . $url['host'] . '/cdn-cgi/image/' . $cfparams . $url['path'];
		
        $sources[$key]['url'] = $newurl;
    }
	
    return $sources;
}

function cloudflare_get_attachment_url($url, $post_id) 
{
	// Don't alter URL's for Administrator(s)
	if(is_admin()) {
		return $url;
	}
	
	if(!strstr($url, '/cdn-cgi/image/'))
	{
		// Check if this is a valid image
		// JPEG, PNG, GIF (including animations), and WebP images. SVG is not supported
		if(in_array(strtolower(pathinfo($url, PATHINFO_EXTENSION)), [ 'jpg', 'jpeg', 'gif', 'png', 'webp' ]))
		{
			$old_url = wp_parse_url($url);
			
			$newurl = $old_url['scheme'] . '://' . $old_url['host'] . '/cdn-cgi/image/quality=80,format=auto,onerror=redirect,metadata=none' . $old_url['path'];

			return $newurl;
		}
	}
	
	return $url;
};

// This solves several problems with themes that use crappy methods to include images, for example Divi Theme uses "esc_attr($logo)"
// With the below function we can check if this is the case and alter the URL
function cloudflare_attribute_escape( $safe_text, $text ) 
{ 	
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = _wp_specialchars( $safe_text, ENT_QUOTES );
	
	// Don't alter URL's for Administrator(s)
	if(is_admin()) {
		return $safe_text;
	}
	
	// Check if this is a URL
	if(filter_var($safe_text, FILTER_VALIDATE_URL))
	{
		// Check if URL contains CloudFlare CDN
		if(!strstr($safe_text, '/cdn-cgi/image/'))
		{
			// Check if this is a valid image
			// JPEG, PNG, GIF (including animations), and WebP images. SVG is not supported
			if(in_array(strtolower(pathinfo($safe_text, PATHINFO_EXTENSION)), [ 'jpg', 'jpeg', 'gif', 'png', 'webp' ]))
			{
				$old_url = wp_parse_url($safe_text);
				
				$newurl = $old_url['scheme'] . '://' . $old_url['host'] . '/cdn-cgi/image/quality=80,format=auto,onerror=redirect,metadata=none' . $old_url['path'];
				
				return $newurl;
			}
		}
	}
	
    return $safe_text; 
};

// Same problem as above with Divi and similar themes
function cloudflare_clean_url($url, $protocols, $context) 
{
	// Don't alter URL's for Administrator(s)
	if(is_admin()) {
		return $url;
	}
	
	// Check if this is a valid image
	// JPEG, PNG, GIF (including animations), and WebP images. SVG is not supported
	if(in_array(strtolower(pathinfo($url, PATHINFO_EXTENSION)), [ 'jpg', 'jpeg', 'gif', 'png', 'webp' ]))
	{
		if(!strstr($url, '/cdn-cgi/image/'))
		{
			$old_url = wp_parse_url($url);
			
			$newurl = $old_url['scheme'] . '://' . $old_url['host'] . '/cdn-cgi/image/quality=80,format=auto,onerror=redirect,metadata=none' . $old_url['path'];
			
			return $newurl;
		}
	}
	
	return $url;
};

function cloudflare_add_img_size($content)
{
  $pattern = '/<img [^>]*?src="(https?:\/\/[^"]+?)"[^>]*?>/iu';
  
  preg_match_all($pattern, $content, $imgs);
  
  foreach ($imgs[0] as $i => $img) 
  {
    if (false !== strpos($img, 'width=') && false !== strpos($img, 'height=')) {
      continue;
    }
	
    $img_url = $imgs[1][$i];
	
    $img_size = @getimagesize($img_url);
    
    if (false === $img_size) {
      continue;
    }
	
    $replaced_img = str_replace( '<img ', '<img ' . $img_size[3] . ' ', $imgs[0][$i] );
	
    $content = str_replace($img, $replaced_img, $content);
  }
  
  return $content;
}

add_filter('wp_get_attachment_image_src', 'cloudflare_single_img', PHP_INT_MAX, 4);
add_filter('wp_calculate_image_srcset', 'cloudflare_srcset', PHP_INT_MAX, 5);
add_filter('wp_get_attachment_url', 'cloudflare_get_attachment_url', PHP_INT_MAX, 2);
add_filter('attribute_escape', 'cloudflare_attribute_escape', PHP_INT_MAX, 2); 
add_filter('clean_url', 'cloudflare_clean_url', PHP_INT_MAX, 3);
add_filter('the_content', 'cloudflare_add_img_size', PHP_INT_MAX, 1);

/**
 * Deactivation hook.
 */
function cloudflare_image_resizing_deactivate() 
{
	remove_filter('wp_get_attachment_image_src', 'cloudflare_single_img', PHP_INT_MAX);
	remove_filter('wp_calculate_image_srcset', 'cloudflare_srcset', PHP_INT_MAX);
	remove_filter('wp_get_attachment_url', 'cloudflare_get_attachment_url', PHP_INT_MAX);
	remove_filter('attribute_escape', 'cloudflare_attribute_escape', PHP_INT_MAX);
	remove_filter('clean_url', 'cloudflare_clean_url', PHP_INT_MAX);
	remove_filter('the_content', 'cloudflare_img_size', PHP_INT_MAX);	
=======
require_once('config.php');

define('CF_IMAGE_RESIZING_VERSION', '1.3');

// Utilities class
class Utils
{
    /* 
     * Check if this is a valid image. JPEG, PNG, GIF (including animations), and WebP images. SVG is not supported
     * @return bool
     */
    public static function isValidImage($image)
    {
    	if(@preg_match('/\.(?:jpg|jpeg|gif|png|webp)/', $image, $matches, PREG_OFFSET_CAPTURE, 0)) {
    		return true;
    	}
    
    	return false;
    }
    
    /*
     * Check if this URL is already pointed to Cloudflare CDN
     * @return bool
     */
    public static function isOptimizedImage($image_url)
    {	
        // It seems this is faster and has consistent and better index (100) than strstr().
    	if(@strpos($image_url, '/cdn-cgi/image/') !== false) {
    		return true;
    	}
    
    	return false;
    }
    
    /*
     * Check if this URL contains a whitelisted domain (ignore it)
     * @return bool
     */
    public static function isWhitelisted($image_url)
    {	
        if (CF_IMAGE_RESIZING_WHITELIST === TRUE)
        {
            foreach (CF_IMAGE_RESIZING_WHITELIST_URLS as $site) {
                if(@strpos($image_url, $site) !== false) {
                    return true;
                }
            }
        }
        
    	return false;
    }
    
     /* 
     * Try extract the image path using regex or fallback to wp_parse_url();
     * @return string
     */
    public static function extractPath($url)
    {
        // If PREG_OFFSET_CAPTURE is set then unmatched captures (i.e. ones with '?') will not be present in the result array.
        if(@preg_match('/^(?:.*)(\/wp-content\/.*)$/', $url, $matches, PREG_OFFSET_CAPTURE, 0))
        {
            return $matches[1][0];
        }

        // fallback
        $parsed_url = wp_parse_url($url);
        
        return (isset($parsed_url['path']) && $parsed_url['path'] !== '') ? $parsed_url['path'] : '';
    }
    
     /* 
     * Try to remove size from image filename
     * @return string
     */
    public static function removeSizes($image_url)
    {
        if (CF_IMAGE_RESIZING_STRIP_SIZES === TRUE)
        {
            $pattern = '`((-\d+x\d+)-\d+?)(?2)|-\d+x\d+`';
            $image_url = @preg_replace($pattern, '$1', $image_url);
        }
        
        return $image_url;
    }
    
    /* 
     * Try to extract size from image filename
     * @return array
     */
    public static function extractSizes($image_url)
    {
		$width = 0;
    	$height = 0;
			
    	// Try extract from img url (eg: /wp-content/uploads/2020/07/project-9-1200x848.jpg)
    	@preg_match('/(([0-9]{1,4})x([0-9]{1,4})){1}/', $image_url, $matches, PREG_OFFSET_CAPTURE, 0);
        
		if(isset($matches[2][0]) && isset($matches[3][0])) 
		{
			$width = $matches[2][0];
			$height = $matches[3][0];
		}
		else
		{
			// Try get image size on server because remotely is super slow
			list($width, $height) = @getimagesize(CF_IMAGE_RESIZING_HOME_DIR.$image_url);
		}
			
        return [$width, $height];
    }
    
    /* 
     * Debug print
     * @return banana
     */
    public static function debug($function, $content)
    {
        echo '<pre style="text-align: left;white-space: pre-line;background-color: #444;background-image: -webkit-linear-gradient(#444 50%, #505050 50%);background-image: -moz-linear-gradient(#444 50%, #505050 50%);background-image: -ms-linear-gradient(#444 50%, #505050 50%);background-image: -o-linear-gradient(#444 50%, #505050 50%);background-image: linear-gradient(#444 50%, #505050 50%);background-position: 0 1px;background-repeat: repeat;background-size: 48px 48px; border-radius: 5px;color: #f6f6f6;line-height: 24px;padding: 24px;"><strong style="color:#fff700">'.$function.'</strong>:<br/>';
        var_dump($content);
        echo '</pre>';
    }
}

// Actual plugin core
class CloudflareImageResizingHooks
{
    public static function hook_get_attachment_image_src($image, $attachment_id, $size, $icon)
    {
        //Utils::debug('hook_get_attachment_image_src', [$image]);
        
        // No image, there is nothing to do here.
        if($image === false) 
		{
            return $image;
        }
                
    	// Don't alter URL's for Administrator(s)
    	if(is_admin()) 
		{
    		return $image;
    	}
    	
    	// This check will avoid images that are in whitelist (if enabled)
    	if(Utils::isWhitelisted($image[0]))
    	{
    	    return $image;
    	}
    	
    	// This check will avoid images that are already re-written
    	if(Utils::isOptimizedImage($image[0])) 
		{
    		return $image;
    	}
    	
    	// This check makes sure this is a supported image
    	if(!Utils::isValidImage($image[0])) 
		{
    		return $image;
    	}
			
		// Try get image path from URL
        $image_path = Utils::extractPath($image[0]);
                
        if(!empty($image_path)) 
        {
			 // Try get image size from filename
    		$sizes = Utils::extractSizes($image_path);
   
    		// OK, do we have the sizes or not?
    		if($sizes[0] != 0 && $sizes[1] != 0)
    		{
                $image_path = Utils::removeSizes($image_path);
            
    			$image[0] = CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/width='.$sizes[0].',height='.$sizes[1].',fit='.CF_IMAGE_RESIZING_FIT.',quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
    		}
    		else
    		{
    			// We cannot get the width and height, so we return the original resized image but through Cloudflare.
    			$image[0] =  CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
    		}
        }
    
        return $image;
    }
    
    public static function hook_calculate_image_srcset($size_array, $image_src, $image_meta, $attachment_id)
    {
        //Utils::debug('hook_calculate_image_srcset', $size_array);
        
    	// Don't alter URL's for Administrator(s)
    	if(is_admin()) 
		{
    		return $size_array;
    	}
    	
        foreach ($size_array as $key => $value)
    	{
        	// This check will avoid images that are in whitelist (if enabled)
        	if(Utils::isWhitelisted($value['url']))
        	{
        	    continue;
        	}
        	
        	// This check will avoid images that are already re-written
        	if(Utils::isOptimizedImage($value['url'])) 
    		{
        		continue;
        	}
        	
        	// This check makes sure this is a supported image
        	if(!Utils::isValidImage($value['url'])) 
    		{
        		continue;
        	}
    		
    	    $image_path = Utils::extractPath($value['url']);
    	
			if(!empty($image_path)) 		
			{
				// Try get image size from filename
				$sizes = Utils::extractSizes($image_path);
	   
				// OK, do we have the sizes or not?
				if($sizes[0] != 0 && $sizes[1] != 0)
				{
					$image_path = Utils::removeSizes($image_path);
				
					$size_array[$key]['url'] = CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/width='.$sizes[0].',height='.$sizes[1].',fit='.CF_IMAGE_RESIZING_FIT.',quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
				}
				else
				{
					// We cannot get the width and height, so we return the original resized image but through Cloudflare.
					$size_array[$key]['url'] =  CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
				}
			}
        }
    	
        return $size_array;
    }
    
    public static function hook_get_attachment_url($url, $post_id) 
    {
        //Utils::debug('hook_get_attachment_url', $url);
        
    	// Don't alter URL's for Administrator(s)
    	if(is_admin()) 
		{
    		return $url;
    	}
    	
    	// This check will avoid images that are in whitelist (if enabled)
    	if(Utils::isWhitelisted($url))
    	{
    	    return $url;
    	}
    	
    	// This check will avoid images that are already re-written
    	if(Utils::isOptimizedImage($url)) 
		{
    		return $url;
    	}
    	
    	// This check makes sure this is a supported image
    	if(!Utils::isValidImage($url)) 
		{
    		return $url;
    	}
    	
    	$image_path = Utils::extractPath($url);
    	
		if(!empty($image_path)) 		
		{
			// Try get image size from filename
			$sizes = Utils::extractSizes($image_path);
   
			// OK, do we have the sizes or not?
			if($sizes[0] != 0 && $sizes[1] != 0)
			{
				$image_path = Utils::removeSizes($image_path);
			
				$newurl = CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/width='.$sizes[0].',height='.$sizes[1].',fit='.CF_IMAGE_RESIZING_FIT.',quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
				return $newurl;
			}
			else
			{
				// We cannot get the width and height, so we return the original resized image but through Cloudflare.
				$newurl =  CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
				return $newurl;
			}
		}
    	
    	return $url;
    }
    
    // This solves several problems with themes that use crappy methods to include images, for example Divi Theme uses "esc_attr($logo)"
    // With the below function we can check if this is the case and alter the URL
    public static function hook_attribute_escape($safe_text, $text ) 
    {  	
    	// Don't alter URL's for Administrator(s)
    	if(is_admin()) 
		{
    		return $safe_text;
    	}
    	
    	// Check if this is a URL
    	if(@filter_var($safe_text, FILTER_VALIDATE_URL))
    	{
    	    // This check will avoid images that are in whitelist (if enabled)
        	if(Utils::isWhitelisted($safe_text))
        	{
        	    return $safe_text;
        	}
        	
        	// This check will avoid images that are already re-written
        	if(Utils::isOptimizedImage($safe_text)) 
    		{
        		return $safe_text;
        	}
        	
        	// This check makes sure this is a supported image
        	if(!Utils::isValidImage($safe_text)) 
    		{
        		return $safe_text;
        	}
    	
    	    // Utils::debug('hook_attribute_escape', $text);
    	
    	    $image_path = Utils::extractPath($safe_text);
    	
			if(!empty($image_path)) 		
			{
				// Try get image size from filename
				$sizes = Utils::extractSizes($image_path);
	   
				// OK, do we have the sizes or not?
				if($sizes[0] != 0 && $sizes[1] != 0)
				{
					$image_path = Utils::removeSizes($image_path);
				
					$newurl = CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/width='.$sizes[0].',height='.$sizes[1].',fit='.CF_IMAGE_RESIZING_FIT.',quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
					return $newurl;
				}
				else
				{
					// We cannot get the width and height, so we return the original resized image but through Cloudflare.
					$newurl =  CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
					return $newurl;
				}
			}
    	}
    	
        return $safe_text; 
    }
    
    // Same problem as above with Divi and similar themes
    public static function hook_clean_url($url, $protocols, $context) 
    {
    	// Don't alter URL's for Administrator(s)
    	if(is_admin()) 
		{
    		return $url;
    	}
    	
	    // This check will avoid images that are in whitelist (if enabled)
    	if(Utils::isWhitelisted($url))
    	{
    	    return $url;
    	}
    	
    	// This check will avoid images that are already re-written
    	if(Utils::isOptimizedImage($url)) 
		{
    		return $url;
    	}
    	
    	// This check makes sure this is a supported image
    	if(!Utils::isValidImage($url)) 
		{
    		return $url;
    	}
    	
    	//Utils::debug('hook_clean_url', $url);
    	
    	$image_path = Utils::extractPath($url);
    		
		if(!empty($image_path)) 		
		{
			// Try get image size from filename
			$sizes = Utils::extractSizes($image_path);
   
			// OK, do we have the sizes or not?
			if($sizes[0] != 0 && $sizes[1] != 0)
			{
				$image_path = Utils::removeSizes($image_path);
			
				$newurl = CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/width='.$sizes[0].',height='.$sizes[1].',fit='.CF_IMAGE_RESIZING_FIT.',quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
				return $newurl;
			}
			else
			{
				// We cannot get the width and height, so we return the original resized image but through Cloudflare.
				$newurl =  CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
				return $newurl;
			}
		}
    	
    	return $url;
    }
    
    // LAST STAND FUNCTION
    // REWRITE ALL IMAGES URL OR DIE TRYING
    // OK - It will also add missing width/height...
    public static function hook_content_filter($content)
    {
        // Don't alter anything for Administrator(s)
    	if(is_admin()) 
    	{
    		return $content;
    	}

      @preg_match_all('/<img [^>]*?src="(https?:\/\/[^"]+?)"[^>]*?>/', $content, $image_tags);
          
      $img_tags = \array_replace([], $image_tags[0]);
      $img_urls = \array_replace([], $image_tags[1]);
      
      foreach ($img_urls as $index => $image) 
      {
        // This check will avoid images that are in whitelist (if enabled)
    	if(Utils::isWhitelisted($image))
    	{
    	    continue;
    	}
    	
    	// This check will avoid images that are already re-written
        if(Utils::isOptimizedImage($image)) 
		{
		    if(CF_IMAGE_RESIZING_FIX_VC_COMPOSER === TRUE)
		    {
    		    // This is (the) most interesting "fix" for this plugin...
    		    // Example: <img class="vc_single_image-img" src="https://.../cdn-cgi/image/width=2363,height=2362,fit=crop,quality=80,format=auto,onerror=redirect,metadata=none/wp-content/uploads/2018/12/icons5-15-80x80.png" width="80" height="80">
    		    // Fix: <img class="vc_single_image-img" src="https://.../cdn-cgi/image/width=80,height=80,fit=crop,quality=80,format=auto,onerror=redirect,metadata=none/wp-content/uploads/2018/12/icons5-15.png" width="80" height="80">
    		    $img_tags[$index] = @preg_replace_callback('#\/cdn-cgi\/image\/width=(\d+),height=(\d+)(?:.*)\-(\d+)x(\d+)#', function($matches) {
                
                    // Replace width
                    $matches[0] = @str_replace($matches[1], $matches[3] , $matches[0]);
                    
                    // Replace height
                    $matches[0] = @str_replace($matches[2], $matches[4] , $matches[0]);
                    
                    // Remove image size from filename (since we know it know, no need for regex)
                    $matches[0] = @str_replace('-'.$matches[3].'x'.$matches[4], '', $matches[0]);
                
                    return $matches[0];
                
                }, $img_tags[$index]);
                
                $content = @str_replace($image_tags[0][$index], $img_tags[$index], $content);
            }
            
        	continue;
    	}
    	
    	// This check makes sure this is a supported image
    	if(!Utils::isValidImage($image)) 
		{
    		continue;
    	}
   
        $image_path = Utils::extractPath($image);
   
		if(!empty($image_path)) 		
		{
			// Try get image size from filename
			$sizes = Utils::extractSizes($image_path);
   
			// OK, do we have the sizes or not?
			if($sizes[0] != 0 && $sizes[1] != 0)
			{
				$image_path = Utils::removeSizes($image_path);
			
				$img_urls[$index] = CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/width='.$sizes[0].',height='.$sizes[1].',fit='.CF_IMAGE_RESIZING_FIT.',quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
			}
			else
			{
				// We cannot get the width and height, so we return the original resized image but through Cloudflare.
				$img_urls[$index] = CF_IMAGE_RESIZING_SITE_URL.'/cdn-cgi/image/quality='.CF_IMAGE_RESIZING_QUALITY.',format='.CF_IMAGE_RESIZING_FORMAT.',onerror='.CF_IMAGE_RESIZING_ONERROR.',metadata='.CF_IMAGE_RESIZING_METADATA.CF_IMAGE_RESIZING_SITE_FOLDER.$image_path;
			}
			
			// Do the replacement of src="" for this image tag
			$img_tags[$index] = @preg_replace('/src\s*=\s*"([^"]*)".*?/', 'src='.$img_urls[$index].'', $img_tags[$index]);
			
			// Add missing width and height if desired and necessary
			if(CF_IMAGE_RESIZING_ADD_MISSING_SIZES === TRUE)
			{
				// Do we already have the width/height?
				if (true !== @strpos($image_tags[0][$index], 'width=') && true !== @strpos($image_tags[0][$index], 'height=')) 
				{
				    // OK, do we have the sizes or not?
    				if($sizes[0] != 0 && $sizes[1] != 0)
    				{
    					// Add sizes at the beginning of <img 
    					$img_tags[$index] = str_replace('<img ', '<img width="'.$sizes[0].'" height="'.$sizes[1].'"', $img_tags[$index]);
    				}
				}
			}
			
			$content = @str_replace($image_tags[0][$index], $img_tags[$index], $content);
		}
      }
      
        return $content;
    }
}

// Piece of code taken from the original Cloudflare plugin but adapted to our needs
class CloudflareImageResizing
{
    private static $initiated = false;

    public static function loaded()
    {
        if (!self::$initiated) {
            self::initSettings();
            self::initHooks();
        }
    }

    public static function initHooks()
    {
        self::$initiated = true;

        if (CF_IMAGE_RESIZING_HOOK_1 === TRUE)
            add_filter('wp_get_attachment_image_src', [ 
                CloudflareImageResizingHooks::class, 'hook_get_attachment_image_src'
            ], PHP_INT_MAX, 4);
        
        if (CF_IMAGE_RESIZING_HOOK_2 === TRUE)
            add_filter('wp_calculate_image_srcset', [ 
                CloudflareImageResizingHooks::class, 'hook_calculate_image_srcset'
            ], PHP_INT_MAX, 4);
        
        if (CF_IMAGE_RESIZING_HOOK_3 === TRUE)
            add_filter('wp_get_attachment_url', [ 
                CloudflareImageResizingHooks::class, 'hook_get_attachment_url' 
            ], PHP_INT_MAX, 2);
        
        if (CF_IMAGE_RESIZING_HOOK_4 === TRUE)
            add_filter('attribute_escape', [ 
                CloudflareImageResizingHooks::class, 'hook_attribute_escape'
            ], PHP_INT_MAX, 2); 
        
        if (CF_IMAGE_RESIZING_HOOK_5 === TRUE)
            add_filter('clean_url', [ 
                CloudflareImageResizingHooks::class, 'hook_clean_url'
            ], PHP_INT_MAX, 3);
        
        if (CF_IMAGE_RESIZING_HOOK_6 === TRUE)
            add_filter('the_content', [
                CloudflareImageResizingHooks::class, 'hook_content_filter'
            ], PHP_INT_MAX, 1);
    }
    
    public static function initSettings()
    {
        add_action('admin_init', [ CloudflareImageResizing::class, 'register_settings' ]);
        add_action('rest_api_init', [ CloudflareImageResizing::class, 'register_settings' ]);
        add_filter('rest_pre_dispatch', [ CloudflareImageResizing::class, 'hook_rest_pre_dispatch' ], PHP_INT_MAX, 3);
    }
    
    public static function hook_rest_pre_dispatch($result, $server, $request) 
    {
        // We don't care about anything else
        if ('/wp/v2/settings/' !== $request->get_route() && 'POST' !== $request->get_method()) {
            return $result;
        }
        
        // Core starts with a null value.
        // If it is no longer null, another callback has claimed this request.
        if(null !== $result) 
        {
            return $result;
        }
        
        $siteurl = @isset($request['cf_image_resizing_siteurl']) ? $request['cf_image_resizing_siteurl'] : null;
    
        // We don't care about anything else
        if (null === $siteurl) 
        {
            return $result;
        }
        else
        {
            // Sorry... I don't trust you!
            $siteurl = @rtrim($siteurl, '/');
            $request['cf_image_resizing_siteurl'] = $siteurl;
                
            $config_file = WP_PLUGIN_DIR.'/cloudflare-image-resizing/config.php';
            
            $file_content = file_get_contents($config_file);
    
            if(true !== @strpos($file_content, $siteurl))
            {
                $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_SITE_URL\'\,\s\')(.*)(\'\)\;)/", "$1$siteurl$3", $file_content);
            }
            
            file_put_contents($config_file, $file_content);
        }
    
        return $result;
    }

    public static function register_settings() 
    {
    	register_setting('cf-image-resizing-settings', 'cf_image_resizing_siteurl', [ 'show_in_rest' => true, 'type' => 'string', 'default' => get_option('cf_image_resizing_siteurl', ''), ] );
    }
}

// Admin settings page
class CFImageResizingSettings 
{
	public function __construct() 
	{
		add_action('admin_menu', [ $this, 'cf_image_resizing_settings_add_plugin_page' ] );
		add_action('admin_init', [ $this, 'cf_image_resizing_settings_page_init' ] );
	}

    public function cf_image_resizing_options_assets() 
    {
    	wp_enqueue_script('cf-image-resizing-plugin-script', plugins_url('/', __FILE__) . 'assets/script.js?nocache='.substr(uniqid(),-10), [ 'wp-api', 'wp-i18n', 'wp-components', 'wp-element' ], CF_IMAGE_RESIZING_VERSION, true);
    	wp_enqueue_style('cf-image-resizing-plugin-style', plugins_url('/', __FILE__) . 'assets/style.css?nocache='.substr(uniqid(),-10), [ 'wp-components' ] );
    }
	
	public function cf_image_resizing_settings_add_plugin_page() 
	{
		$page_hook_suffix = add_options_page(
			__('Cloudflare Image Resizing', 'textdomain' ), // page_title
			__('CF Image Resizing', 'textdomain' ), // menu_title
			'manage_options', // capability
			'cf-image-resizing-settings', // menu_slug
			[ $this, 'cf_image_resizing_settings_create_admin_page' ] // function
		);
		
		add_action( "admin_print_scripts-{$page_hook_suffix}", [ $this, 'cf_image_resizing_options_assets' ] );
	}

	public function cf_image_resizing_settings_create_admin_page() 
	{
	   echo '<div id="cf_image_resizing_plugin"></div>';
	}

	public function cf_image_resizing_settings_page_init() 
	{
		add_settings_section(
			'cf_image_resizing_settings_setting_section', // id
			'Settings', // title
			[ $this, 'cf_image_resizing_settings_section_info' ], // callback
			'cf-image-resizing-settings-admin' // page
		);
	}

	public function cf_image_resizing_settings_section_info() 
	{
		echo 'Section info';
	}
}

// Run plugin for non-Administrators
if (!is_admin()) 
{
    CloudflareImageResizing::loaded();
>>>>>>> Stashed changes
}
	
// Only available for Administrators
if (is_admin()) 
{
    // Add Settings page
    new CFImageResizingSettings();

    // Add Shortcut to settings page
    function cloudflare_image_resizing_shortcut($links) 
    {
        $url = get_admin_url() . "options-general.php?page=cf-image-resizing-settings";
        $settings_link = '<a href="' . $url . '">' . __('Settings', 'textdomain') . '</a>';
        $links[] = $settings_link;
        return $links;
    }
    
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cloudflare_image_resizing_shortcut');
  
    function cloudflare_image_resizing_admin_notice()
    {
        echo '<div class="notice notice-info is-dismissible">
              <p>Woop Woop! You are one step close to make your WordPress blazing fast. Don\'t forget to set your <strong>site url</strong> otherwise the plugin will not work.</p>
              <p><strong>You can edit settings and options (CF_IMAGE_RESIZING_SITE_URL) by <a href='.get_admin_url().'options-general.php?page=cf-image-resizing-settings>'. __('clicking here', 'textdomain') .'</a>.</strong></p>
            </div>';
    }

    if(empty(CF_IMAGE_RESIZING_SITE_URL))
        add_action('admin_notices', 'cloudflare_image_resizing_admin_notice');
 
    /**
     * Activation hook.
     */
    function cloudflare_image_resizing_activate() 
    {
        // Piece of code taken from the original Cloudflare plugin
        if (version_compare(PHP_VERSION, '7.0', '<')) 
        {
            // We need to load "plugin.php" manually to call "deactivate_plugins"
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        
            deactivate_plugins(plugin_basename(__FILE__), true);
            wp_die('<p>The Cloudflare Image Resizing plugin requires a PHP version of at least 7.0; you have '. PHP_VERSION .'.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));
        }
        
        return true;
    }
  
    register_activation_hook(__FILE__, 'cloudflare_image_resizing_activate');
    
    /**
     * Deactivation hook.
     */
    function cloudflare_image_resizing_deactivate() 
    {
        // Remove plugin actions/filters
        remove_action('admin_init', [ CloudflareImageResizing::class, 'register_settings' ]);
        remove_action('rest_api_init', [ CloudflareImageResizing::class, 'register_settings' ]);
        remove_filter('rest_pre_dispatch', [ CloudflareImageResizing::class, 'hook_rest_pre_dispatch' ]);
        
        // Remove Settings Page
        remove_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cloudflare_image_resizing_shortcut');	
        
        // Remove plugin filters
        if (CF_IMAGE_RESIZING_HOOK_1 === TRUE)
            remove_filter('wp_get_attachment_image_src', [
                CloudflareImageResizingHooks::class, 'hook_single_img' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_2 === TRUE)
            remove_filter('wp_calculate_image_srcset', [ 
                CloudflareImageResizingHooks::class, 'hook_srcset' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_3 === TRUE)  
            remove_filter('wp_get_attachment_url', [ 
                CloudflareImageResizingHooks::class, 'hook_get_attachment_url' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_4 === TRUE)
            remove_filter('attribute_escape', [ 
                CloudflareImageResizingHooks::class, 'hook_attribute_escape' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_5 === TRUE)   
            remove_filter('clean_url', [ 
                CloudflareImageResizingHooks::class, 'hook_clean_url' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_6 === TRUE)    
            remove_filter('the_content', [ 
                CloudflareImageResizingHooks::class, 
                'hook_img_size' 
            ], PHP_INT_MAX);
    }
    
    register_deactivation_hook(__FILE__, 'cloudflare_image_resizing_deactivate');
}