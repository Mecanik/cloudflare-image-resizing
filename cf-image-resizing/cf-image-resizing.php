<?php
/**
* Plugin Name: Cloudflare Image Resizing
* Plugin URI: https://wordpress.org/plugins/cf-image-resizing/
* Description: Optimize images on-the-fly using Cloudflare's Image Resizing service, improving performance and core web vitals.
* Version: 1.5.5
* Author: Mecanik
* Author URI: https://mecanik.dev/en/?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=wp-dash
* License: GPLv3 or later
* Text Domain: cf-image-resizing
* Domain Path: /languages
* Requires at least: 5.0
* Requires PHP: 7.0
**/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once('config.php');

define('CF_IMAGE_RESIZING_VERSION', '1.5.5');

// Utilities class
class Utils
{
    /* 
     * Check if this is a valid image. JPEG, PNG, GIF (including animations), and WebP images. 
	 * SVG support: You can use Image Resizing to sanitize SVGs, but not to resize them.
     * @return bool
     */
    public static function isValidImage($image)
    {
    	if(@preg_match('/\.(?:jpe?g|gif|png|webp|svg)/', $image, $matches, PREG_OFFSET_CAPTURE, 0)) {
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
        
		// Check if image host is external. If so, then don't strip the root url from the path	
        $host = rtrim(str_replace(['http://', 'https://', ], '', CF_IMAGE_RESIZING_SITE_URL), '/');	
        if (isset($parsed_url['host']) && $parsed_url['host'] != $host) {	
            $parsed_url['path'] = '/'.$parsed_url['scheme'].'://'.$parsed_url['host'].$parsed_url['path'];	
        }
		
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
	
	public static function get_region($account, $token)
    {
        $api_url = "https://api.mecanik.dev/v1/developer/$account/geo-info";
    
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'method' => 'GET',
        ]);
    
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
		
        return $response_data['result'];
    }
    
    public static function get_wp_version() {
        global $wp_version;
        return $wp_version;
    }

    public static function get_php_version() {
        return PHP_VERSION;
    }
    
    public static function get_db_type() {
        global $wpdb;
    
        // Query the information_schema database to retrieve the database type
        $query = "SELECT TABLE_SCHEMA FROM information_schema.TABLES WHERE TABLE_NAME = 'wp_options'";
        $result = $wpdb->get_row($query);
    
        // Check if the query was successful and if a result was obtained
        if ($result && isset($result->TABLE_SCHEMA)) {
            $table_schema = $result->TABLE_SCHEMA;
    
            // Determine the database type based on the table schema
            if (strpos($table_schema, 'mysql') !== false) {
                return 'MySQL';
            } elseif (strpos($table_schema, 'pgsql') !== false) {
                return 'PostgreSQL';
            } elseif (strpos($table_schema, 'sqlite') !== false) {
                return 'SQLite';
            }
        }
    
        return 'Unknown';
    }
    
    public static function get_db_version() {
        global $wpdb;
        return $wpdb->db_version();
    }

    public static function get_server_type() {
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            return explode(' ', $_SERVER['SERVER_SOFTWARE'])[0];
        }
        return 'Unknown';
    }

    public static function get_server_version() {
        $uname = php_uname('s');

        $serverTypes = [
            'Apache' => 'Apache',
            'Nginx' => 'Nginx',
            'LiteSpeed' => 'LiteSpeed'
        ];
    
        if (isset($serverTypes[$uname])) {
            return $serverTypes[$uname];
        }

        return '0.0.0';
    }
}

// Actual plugin core
class CFImageResizingHooks
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
        //var_dump($text);
    	//$safe_text = wp_check_invalid_utf8($text);
    	//$safe_text = _wp_specialchars($safe_text, ENT_QUOTES );
    	
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

      @preg_match_all('/<img [^>]*?(src|data-src|data-src-.*)="(https?:\/\/[^"]+?)"[^>]*?>/', $content, $image_tags);
          
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
    		    // Fix: <img class="vc_single_image-img" src="https://..k/cdn-cgi/image/width=80,height=80,fit=crop,quality=80,format=auto,onerror=redirect,metadata=none/wp-content/uploads/2018/12/icons5-15.png" width="80" height="80">
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
			$img_tags[$index] = @preg_replace('/(src|data-src|data-src-.*)\s*=\s*"([^"]*)".*?/', 'src='.$img_urls[$index].'', $img_tags[$index]);
			
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
class CFImageResizing
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
                CFImageResizingHooks::class, 'hook_get_attachment_image_src'
            ], PHP_INT_MAX, 4);
        
        if (CF_IMAGE_RESIZING_HOOK_2 === TRUE)
            add_filter('wp_calculate_image_srcset', [ 
                CFImageResizingHooks::class, 'hook_calculate_image_srcset'
            ], PHP_INT_MAX, 4);
        
        if (CF_IMAGE_RESIZING_HOOK_3 === TRUE)
            add_filter('wp_get_attachment_url', [ 
                CFImageResizingHooks::class, 'hook_get_attachment_url' 
            ], PHP_INT_MAX, 2);
        
        if (CF_IMAGE_RESIZING_HOOK_4 === TRUE)
            add_filter('attribute_escape', [ 
                CFImageResizingHooks::class, 'hook_attribute_escape'
            ], PHP_INT_MAX, 2); 
        
        if (CF_IMAGE_RESIZING_HOOK_5 === TRUE)
            add_filter('clean_url', [ 
                CFImageResizingHooks::class, 'hook_clean_url'
            ], PHP_INT_MAX, 3);
        
        if (CF_IMAGE_RESIZING_HOOK_6 === TRUE)
            add_filter('the_content', [
                CFImageResizingHooks::class, 'hook_content_filter'
            ], PHP_INT_MAX, 1);
    }
    
    public static function initSettings()
    {
        add_action('admin_init', [ CFImageResizing::class, 'register_settings' ]);
        add_action('rest_api_init', [ CFImageResizing::class, 'register_settings' ]);
        add_filter('rest_pre_dispatch', [ CFImageResizing::class, 'hook_rest_pre_dispatch' ], PHP_INT_MAX, 3);
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
        
        $config_file = WP_PLUGIN_DIR.'/cf-image-resizing/config.php';
        
        $siteurl = @isset($request['cf_image_resizing_siteurl']) ? $request['cf_image_resizing_siteurl'] : null;
    
        // We don't care about anything else
        if (null !== $siteurl)
        {
            // Sorry... I don't trust you!
            $siteurl = @rtrim($siteurl, '/');
            $request['cf_image_resizing_siteurl'] = $siteurl;
            
            $file_content = file_get_contents($config_file);
    
            if(true !== @strpos($file_content, $siteurl))
            {
                $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_SITE_URL\'\,\s\')(.*)(\'\)\;)/", "$1$siteurl$3", $file_content);
            }
            
            file_put_contents($config_file, $file_content);
        }
    
        $hook_1 = @isset($request['cf_image_resizing_hook_1']) ? $request['cf_image_resizing_hook_1'] : null;
          
        if (null !== $hook_1)
        {
            $enabled = $hook_1 ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_HOOK_1\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
        
		$hook_2 = @isset($request['cf_image_resizing_hook_2']) ? $request['cf_image_resizing_hook_2'] : null;
          
        if (null !== $hook_2)
        {
            $enabled = $hook_2 ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_HOOK_2\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$hook_3 = @isset($request['cf_image_resizing_hook_3']) ? $request['cf_image_resizing_hook_3'] : null;
          
        if (null !== $hook_3)
        {
            $enabled = $hook_3 ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_HOOK_3\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$hook_4 = @isset($request['cf_image_resizing_hook_4']) ? $request['cf_image_resizing_hook_4'] : null;
          
        if (null !== $hook_4)
        {
            $enabled = $hook_4 ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_HOOK_4\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$hook_5 = @isset($request['cf_image_resizing_hook_5']) ? $request['cf_image_resizing_hook_5'] : null;
          
        if (null !== $hook_5)
        {
            $enabled = $hook_5 ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_HOOK_5\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$hook_6 = @isset($request['cf_image_resizing_hook_6']) ? $request['cf_image_resizing_hook_6'] : null;
          
        if (null !== $hook_6)
        {
            $enabled = $hook_6 ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_HOOK_6\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$fit = @isset($request['cf_image_resizing_fit']) ? $request['cf_image_resizing_fit'] : null;
          
        if (null !== $fit)
        {
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_FIT\'\,\s\')(.*)(\'\)\;)/", "$1$fit$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$quality = @isset($request['cf_image_resizing_quality']) ? $request['cf_image_resizing_quality'] : null;

		if (null !== $quality)
		{
			$file_content = file_get_contents($config_file);

			$file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_QUALITY\'\,\s)(\d+)(\)\;)/", '${1}' . (string)$quality . '${3}', $file_content);

			file_put_contents($config_file, $file_content);
		}
		
		$format = @isset($request['cf_image_resizing_format']) ? $request['cf_image_resizing_format'] : null;
          
        if (null !== $format)
        {
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_FORMAT\'\,\s\')(.*)(\'\)\;)/", "$1$format$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$metadata = @isset($request['cf_image_resizing_metadata']) ? $request['cf_image_resizing_metadata'] : null;
          
        if (null !== $metadata)
        {
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_METADATA\'\,\s\')(.*)(\'\)\;)/", "$1$metadata$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$onerror = @isset($request['cf_image_resizing_onerror']) ? $request['cf_image_resizing_onerror'] : null;
          
        if (null !== $onerror)
        {
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_ONERROR\'\,\s\')(.*)(\'\)\;)/", "$1$onerror$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$strip_img_sizes = @isset($request['cf_image_resizing_strip_img_sizes']) ? $request['cf_image_resizing_strip_img_sizes'] : null;
          
        if (null !== $strip_img_sizes)
        {
            $enabled = $strip_img_sizes ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_STRIP_SIZES\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$add_img_sizes = @isset($request['cf_image_resizing_add_img_sizes']) ? $request['cf_image_resizing_add_img_sizes'] : null;
          
        if (null !== $add_img_sizes)
        {
            $enabled = $add_img_sizes ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_ADD_MISSING_SIZES\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
		$fix_vc_composer = @isset($request['cf_image_resizing_fix_vc_composer']) ? $request['cf_image_resizing_fix_vc_composer'] : null;
          
        if (null !== $fix_vc_composer)
        {
            $enabled = $fix_vc_composer ? 'TRUE' : 'FALSE';
            
            $file_content = file_get_contents($config_file);
    
            $file_content = preg_replace("/(define\(\'CF_IMAGE_RESIZING_FIX_VC_COMPOSER\'\,\s)(.*)(\)\;)/", "$1$enabled$3", $file_content);
        
            file_put_contents($config_file, $file_content);
        }
		
        return $result;
    }

    public static function register_settings() 
    {
    	register_setting('cf-image-resizing-settings', 'cf_image_resizing_siteurl', [ 'show_in_rest' => true, 'type' => 'string', 'default' => get_option('cf_image_resizing_siteurl', rtrim(home_url(), '/')), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_sitefolder', [ 'show_in_rest' => true, 'type' => 'string', 'default' => get_option('cf_image_resizing_sitefolder', ''), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_homedir', [ 'show_in_rest' => true, 'type' => 'string', 'default' => get_option('cf_image_resizing_homedir', ABSPATH), ] );
    	register_setting('cf-image-resizing-settings', 'cf_image_resizing_hook_1', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_hook_1', true), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_hook_2', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_hook_2', true), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_hook_3', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_hook_3', false), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_hook_4', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_hook_4', false), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_hook_5', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_hook_5', false), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_hook_6', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_hook_6', true), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_fit', [ 'show_in_rest' => true, 'type' => 'string', 'default' => get_option('cf_image_resizing_fit', 'crop'), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_quality', [ 'show_in_rest' => true, 'type' => 'number', 'default' => get_option('cf_image_resizing_quality', 80), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_format', [ 'show_in_rest' => true, 'type' => 'string', 'default' => get_option('cf_image_resizing_format', 'auto'), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_metadata', [ 'show_in_rest' => true, 'type' => 'string', 'default' => get_option('cf_image_resizing_metadata', 'none'), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_onerror', [ 'show_in_rest' => true, 'type' => 'string', 'default' => get_option('cf_image_resizing_onerror', 'redirect'), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_strip_img_sizes', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_strip_img_sizes', true), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_add_img_sizes', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_add_img_sizes', true), ] );
		register_setting('cf-image-resizing-settings', 'cf_image_resizing_fix_vc_composer', [ 'show_in_rest' => true, 'type' => 'boolean', 'default' => get_option('cf_image_resizing_fix_vc_composer', false), ] );
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
			__('Cloudflare Image Resizing', 'cf-image-resizing' ), // page_title
			__('CF Image Resizing', 'cf-image-resizing' ), // menu_title
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
    CFImageResizing::loaded();
}
	
// Only available for Administrators
if (is_admin()) 
{
    // Add Settings page
    new CFImageResizingSettings();

    // Add Shortcut to settings page
    function cf_image_resizing_shortcut($links) 
    {
        $url = get_admin_url() . "options-general.php?page=cf-image-resizing-settings";
        $settings_link = '<a href="' . $url . '">' . __('Settings', 'cf-image-resizing') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cf_image_resizing_shortcut');

	// Add Review notice
	function cf_image_resizing_admin_notice()
	{
		$screen = get_current_screen();

        if (null === $screen) {
            return;
        }
        
        if ($screen->id === "settings_page_cf-image-resizing-settings") {
            return;
        }
		
		if (get_option('cf_image_resizing_admin_notice_dismissed', 'no') == 'no') 
		{
			$message = __("I'm glad you're using my plugin! If you find it helpful, could you please take a moment to leave a review? I'd really appreciate it. Thank you! - Mecanik", 'cf-image-resizing');
			$leaveReviewLink = 'https://wordpress.org/support/plugin/cf-image-resizing/reviews/#new-post';

			$html = <<<HTML
				<div class="notice notice-success is-dismissible cf-image-resizing-review-notice" style="padding: 15px;">
					<h2 style="margin-top: 0px !important; margin-bottom: 10px !important;">Thanks for using Cloudflare Image Resizing</h2>
					<p>$message</p>
					<a href="$leaveReviewLink" class="button button-primary" target="_blank"><span class="dashicons dashicons-star-filled" style="margin-top: 3px;"></span> Leave a review</a>
					<button type="button" class="notice-dismiss" id="cf-image-resizing-notice-dismiss"><span class="screen-reader-text">Already left a review</span></button>
				</div>
				<script type='text/javascript'>
					document.addEventListener('DOMContentLoaded', function() {
						document.querySelector('#cf-image-resizing-notice-dismiss').addEventListener('click', function() {
							var xhr = new XMLHttpRequest();
							xhr.open('POST', ajaxurl, true);
							xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
							xhr.onload = function() {
								if (this.status >= 200 && this.status < 400) {
									// Success!
									console.log(this.response);
									document.querySelector('.cf-image-resizing-review-notice').style.display = 'none';
								} else {
									// Error :(
									console.error('XHR error');
								}
							};
							xhr.onerror = function() {
								// Connection error
								console.error('XHR connection error');
							};
							xhr.send('action=dismiss_cf_image_resizing_admin_notice');
						});
					});
				</script>
			HTML;

			echo $html;
		}
	}

    add_action('admin_notices', 'cf_image_resizing_admin_notice');
 
	function dismiss_cf_image_resizing_admin_notice() 
	{
		update_option('cf_image_resizing_admin_notice_dismissed', 'yes');
		wp_die();
	}

	add_action('wp_ajax_dismiss_cf_image_resizing_admin_notice', 'dismiss_cf_image_resizing_admin_notice');

	// Add Feedback system
	function cf_image_resizing_admin_feedback()
	{
		$screen = get_current_screen();

        if (null === $screen) {
            return;
        }
        
        if ($screen->id !== "plugins") {
            return;
        }

		$html = <<<HTML
			<div id="cf-image-resizing-feedback-modal" class="cf-image-resizing-modal">
				<div class="cf-image-resizing-modal-content">
					<div class="cf-image-resizing-modal-header">
						<h2>CF Image Resizing Deactivation Feedback</h2>
						<button class="cf-image-resizing-modal-close" title="Close feedback form">&times;</button>
					</div>
					<div class="cf-image-resizing-modal-body">
						<p>I'd appreciate it if you could share your reasons for deactivating this plugin. Your feedback helps me make improvements for everyone's benefit. Thank you!</p>
						<p>
							<label for="reason"><strong>Reason for Deactivation:</strong></label>
							<select id="reason" name="reason" class="regular-text">
								<option value="not-working">It's not working</option>
								<option value="better-plugin">Found a better plugin</option>
								<option value="temporary-deactivation">Temporary deactivation</option>
								<option value="feature-missing">Needed feature is missing</option>
								<option value="hard-to-use">It's too hard to use</option>
								<option value="not-compatible">Not compatible with another plugin/theme</option>
								<option value="performance-issues">Causing performance issues</option>
								<option value="site-crash">Caused my site to crash</option>
								<option value="not-updated">Plugin isn't updated regularly</option>
								<option value="switched-theme">Switched to a theme that includes similar functionality</option>
								<option value="support-issues">Not getting adequate support</option>
								<option value="other">Other</option>
							</select>
						</p>
						<p>
							<label for="comments"><strong>Additional Comments:</strong></label>
							<textarea id="comments" name="comments" rows="4" class="large-text"></textarea>
						</p>
						
						<small class="cf-image-resizing-modal-text-muted">
							By providing feedback, you're sharing specifics like the plugin name, version, and some of your environment details (WordPress info, PHP, database, and server). This data aids in diagnosing and enhancing the plugin. Rest assured, no personal information is gathered or disseminated to third parties.
						</small>
						
						<div id="feedback-error" class="cf-image-resizing-feedback-error" style="display: none;"></div>
						
					</div>
					<div class="cf-image-resizing-modal-footer">
						<button class="cf-image-resizing-modal-submit button button-primary">Submit and Deactivate</button>
						<button class="cf-image-resizing-modal-skip button">Skip and Deactivate</button>
					</div>
				</div>
			</div>
		HTML;

		echo $html;
	}
	
	add_action('admin_footer', 'cf_image_resizing_admin_feedback');
	
	function send_cf_image_resizing_admin_feedback() 
	{
		if (!wp_verify_nonce($_POST['nonce'], 'cf_image_resizing_admin_feedback_nonce')) {
            wp_send_json_error('Request is invalid. Please refresh the page and try again.', 400, 0);
            exit();
        }
        
        $account = "af87c346-67e8-4c94-a4d2-c5a5db65b4c7";
        $token = "lu-3VkkHTqTOnQ8JDRuadMAJErJQvBSQXaZL04igSTs";
        $api_url = "https://api.mecanik.dev/v1/developer/$account/wp-feedback";
    
        $region = Utils::get_region($account, $token);
	
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body' => json_encode([
                'reason'             => sanitize_text_field($_POST['reason']),
                'comments'           => sanitize_textarea_field($_POST['comments']),
                'wp_plugin_name'     => 'cf-image-resizing',
                'wp_plugin_version'  => CF_IMAGE_RESIZING_VERSION,
                'wp_site_url'        => get_bloginfo('url'),
                'wp_version'         => Utils::get_wp_version(),
                'wp_locale'          => get_locale(),
                'wp_multisite'       => is_multisite(),
                'php_version'        => Utils::get_php_version(),
                'db_type'            => Utils::get_db_type(),
                'db_version'         => Utils::get_db_version(),
                'server_type'        => Utils::get_server_type(),
                'server_version'     => Utils::get_server_version(),
                'date_created'       => current_time('mysql'),
                'region'             => isset($region['recommended-storage-region']) ? $region['recommended-storage-region'] : "weur",
            ]),
            'method' => 'PUT',
            'data_format' => 'body'
        ]);
    
        // Check for errors in the response body
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body);
    
        if ($response_data && isset($response_data->success) && $response_data->success === false) {
            $error_message = isset($response_data->errors[0]->message) ? esc_html($response_data->errors[0]->message) : 'Unknown error';
            wp_send_json_error($error_message, 400);
            exit();
        }
        else if ($response_data && isset($response_data->success) && $response_data->success === true && isset($response_data->error)) {
            wp_send_json_error($response_data->error, 400);
            exit();
        }
        
        wp_send_json_success($response_data, 200, 0);
	}
	
	add_action('wp_ajax_send_cf_image_resizing_admin_feedback',  'send_cf_image_resizing_admin_feedback');
	
	function enqueue_feedback_scripts($hook_suffix)
    {
        if (!isset($hook_suffix)) {
            return;
        }
        
        if ('plugins.php' !== $hook_suffix) {
            return;
        }
    
        wp_enqueue_style('cf-image-resizing-feedback-style', plugins_url('/', __FILE__) . 'assets/feedback.css?nocache='.substr(uniqid(),-10), [], CF_IMAGE_RESIZING_VERSION, 'all');
        wp_enqueue_script('cf-image-resizing-feedback-script', plugins_url('/', __FILE__) . 'assets/feedback.js?nocache='.substr(uniqid(),-10), [], CF_IMAGE_RESIZING_VERSION, false);

        wp_localize_script('cf-image-resizing-feedback-script', 'params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajax_send_cf_image_resizing_admin_feedback_nonce' => wp_create_nonce('cf_image_resizing_admin_feedback_nonce'),
        ]);
    }
	
	add_action('admin_enqueue_scripts', 'enqueue_feedback_scripts');
	
    /**
     * Activation hook.
     */
    function cf_image_resizing_activate() 
    {
        // Piece of code taken from the original Cloudflare plugin
        if (version_compare(PHP_VERSION, '7.0', '<')) 
        {
            // We need to load "plugin.php" manually to call "deactivate_plugins"
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        
            deactivate_plugins(plugin_basename(__FILE__), true);
            wp_die('<p>The CF Image Resizing plugin requires a PHP version of at least 7.0; you have '. PHP_VERSION .'.</p>', 'Plugin Activation Error', array('response' => 200, 'back_link' => true));
        }
		
		// Set default options
		update_option('cf_image_resizing_siteurl', rtrim(home_url(), '/'));
		update_option('cf_image_resizing_sitefolder', '');
		update_option('cf_image_resizing_homedir', ABSPATH);
		update_option('cf_image_resizing_hook_1', true);
		update_option('cf_image_resizing_hook_2', true);
		update_option('cf_image_resizing_hook_3', false);
		update_option('cf_image_resizing_hook_4', false);
		update_option('cf_image_resizing_hook_5', false);
		update_option('cf_image_resizing_hook_6', true);
		update_option('cf_image_resizing_fit', 'crop');
		update_option('cf_image_resizing_quality', 80);
		update_option('cf_image_resizing_format', 'auto');
		update_option('cf_image_resizing_metadata', 'none');
		update_option('cf_image_resizing_onerror', 'redirect');
		update_option('cf_image_resizing_strip_img_sizes', true);
		update_option('cf_image_resizing_add_img_sizes', true);
		update_option('cf_image_resizing_fix_vc_composer', false);
		
        return true;
    }
  
    register_activation_hook(__FILE__, 'cf_image_resizing_activate');
    
    /**
     * Deactivation hook.
     */
    function cf_image_resizing_deactivate() 
    {
        // Remove plugin actions/filters
        remove_action('admin_init', [ CFImageResizing::class, 'register_settings' ]);
        remove_action('rest_api_init', [ CFImageResizing::class, 'register_settings' ]);
        remove_filter('rest_pre_dispatch', [ CFImageResizing::class, 'hook_rest_pre_dispatch' ]);
        
        // Remove Settings Page
        remove_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cf_image_resizing_shortcut');	
        
        // Remove plugin filters
        if (CF_IMAGE_RESIZING_HOOK_1 === TRUE)
            remove_filter('wp_get_attachment_image_src', [
                CFImageResizingHooks::class, 'hook_single_img' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_2 === TRUE)
            remove_filter('wp_calculate_image_srcset', [ 
                CFImageResizingHooks::class, 'hook_srcset' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_3 === TRUE)  
            remove_filter('wp_get_attachment_url', [ 
                CFImageResizingHooks::class, 'hook_get_attachment_url' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_4 === TRUE)
            remove_filter('attribute_escape', [ 
                CFImageResizingHooks::class, 'hook_attribute_escape' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_5 === TRUE)   
            remove_filter('clean_url', [ 
                CFImageResizingHooks::class, 'hook_clean_url' 
            ], PHP_INT_MAX);
        
        if (CF_IMAGE_RESIZING_HOOK_6 === TRUE)    
            remove_filter('the_content', [ 
                CFImageResizingHooks::class, 'hook_content_filter' 
            ], PHP_INT_MAX);
    }
    
    register_deactivation_hook(__FILE__, 'cf_image_resizing_deactivate');
}