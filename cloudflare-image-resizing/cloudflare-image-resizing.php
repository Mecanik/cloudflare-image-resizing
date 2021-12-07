<?php
/**
* Plugin Name: CloudFlare Image Resizing
* Plugin URI: https://github.com/Mecanik/cloudflare-image-resizing/
* Description: This plugin will replace Image URL's (including srcset) so you can use the CloudFlare Image Resizing service. As an added bonus it will also add the required width/height for all images that are missing them.
* Version: 1.1
* Author: Mecanik
* Author URI: https://github.com/Mecanik/
**/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

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
		if(in_array(strtolower(pathinfo($safe_text, PATHINFO_EXTENSION)), [ 'jpg', 'jpeg', 'gif', 'png', 'webp' ]))
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
}
register_deactivation_hook(__FILE__, 'cloudflare_image_resizing_deactivate');