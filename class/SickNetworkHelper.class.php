<?php
class SickNetworkHelper
{

    static function write($val)
    {
        die('<sicknetwork>' . base64_encode(serialize($val)) . '</sicknetwork>');
    }

    static function error($error)
    {
        $information['error'] = $error;
        SickNetworkHelper::write($information);
    }

    static function uploadImage($img_url)
    {
        include_once(ABSPATH . 'wp-admin/includes/file.php'); //Contains download_url
        //Download $img_url
        $temporary_file = download_url($img_url);

        if (is_wp_error($temporary_file))
        {
            throw new Exception('Error: ' . $temporary_file->get_error_message());
        }
        else
        {
            $upload_dir = wp_upload_dir();
            $local_img_path = $upload_dir['path'] . DIRECTORY_SEPARATOR . basename($img_url); //Local name
            $local_img_url = $upload_dir['url'] . '/' . basename($img_url);
            $moved = @rename($temporary_file, $local_img_path);
            if ($moved)
            {
                $wp_filetype = wp_check_filetype(basename($img_url), null); //Get the filetype to set the mimetype
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($img_url)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $local_img_path); //Insert the image in the database
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $local_img_path);
                wp_update_attachment_metadata($attach_id, $attach_data); //Update generated metadata

                return array('id' => $attach_id, 'url' => $local_img_url);
            }
        }
        if (file_exists($temporary_file))
        {
            unlink($temporary_file);
        }
        return null;
    }

    static function createPost($new_post, $post_custom, $post_category, $post_featured_image, $sicknetwork_upload_dir)
    {
        global $current_user;

        //Set up a new post (adding addition information)
        $usr = get_user_by('login', $_POST['user']);
        //$new_post['post_author'] = $current_user->ID;
        $new_post['post_author'] = $usr->ID; // to fix missing post author
        $ezine_post = !empty($post_custom['_ezine_post_article_source']) ? true : false;
        $terms = $new_post['_ezin_post_category'];
        unset($new_post['_ezin_post_category']);
        
        $wp_error = null;

        //Search for all the images added to the new post
        //some images have a href tag to click to navigate to the image.. we need to replace this too
        update_option('SICK_DEBUG', print_r($sicknetwork_upload_dir,1));
        
        if (!$ezine_post) {              
            $foundMatches = preg_match_all('/(<a[^>]+href=\"(.*?)\"[^>]*>)?(<img[^>\/]*src=\"((.*?)(png|gif|jpg|jpeg))\")/ix', $new_post['post_content'], $matches, PREG_SET_ORDER);
        }
        else 
        {            
            $foundMatches = 0;            
        }    
                
        if ($foundMatches > 0)
        {
            //We found images, now to download them so we can start balbal
            foreach ($matches as $match)
            {
                $hrefLink = $match[2];
                $imgUrl = $match[4];

                if (!isset($sicknetwork_upload_dir['baseurl']) || (strripos($imgUrl, $sicknetwork_upload_dir['baseurl']) != 0)) continue;

                if (preg_match('/-\d{3}x\d{3}\.[a-zA-Z0-9]{3,4}$/', $imgUrl, $imgMatches)) {
                    $search = $imgMatches[0];
                    $replace = '.'.$match[6];
                    $originalImgUrl = str_replace($search, $replace, $imgUrl);
                } else {
                    $originalImgUrl = $imgUrl;
                }

                $downloadfile = SickNetworkHelper::uploadImage($originalImgUrl);
                $localUrl = $downloadfile['url'];
                $linkToReplaceWith = dirname($localUrl);
                if ($hrefLink != '')
                {
                    $lnkToReplace = dirname($hrefLink);
                    $new_post['post_content'] = str_replace($lnkToReplace, $linkToReplaceWith, $new_post['post_content']);
                }

                $lnkToReplace = dirname($imgUrl);
                $new_post['post_content'] = str_replace($lnkToReplace, $linkToReplaceWith, $new_post['post_content']);
            }
        }

        //Save the post to the wp
        remove_filter('content_save_pre', 'wp_filter_post_kses');  // to fix brake scripts or html
        $new_post_id = wp_insert_post($new_post, $wp_error);

        //Show errors if something went wrong
        if (is_wp_error($wp_error))
        {
            return $wp_error->get_error_message();
        }
        if ($new_post_id == 0)
        {
            return 'Undefined error';
        }
        
        if (!empty($terms)) {                 
                 wp_set_object_terms($new_post_id, array_map( intval, $terms),  'category');
        }         
        
        $permalink = get_permalink( $new_post_id );

        //Set custom fields
        $not_allowed = array('_slug', '_tags', '_edit_lock', '_selected_sites', '_selected_groups', '_selected_by', '_categories', '_edit_last');
        foreach ($post_custom as $meta_key => $meta_values)
        {
            if (!in_array($meta_key, $not_allowed))
            {
                foreach ($meta_values as $meta_value)
                {
                    add_post_meta($new_post_id, $meta_key, $meta_value);
                }
            }
        }

        //If categories exist, create them (second parameter of wp_create_categories adds the categories to the post)
        include_once(ABSPATH . 'wp-admin/includes/taxonomy.php'); //Contains wp_create_categories
        if (isset($post_category) && $post_category != '')
        {
            $categories = explode(',', $post_category);
            if (count($categories) > 0)
            {
                $post_category = wp_create_categories($categories, $new_post_id);
            }
        }

        //If featured image exists - set it
        if ($post_featured_image != null)
        {
            try
            {
                $upload = SickNetworkHelper::uploadImage($post_featured_image); //Upload image to WP

                if ($upload != null)
                {
                    update_post_meta($new_post_id, '_thumbnail_id', $upload['id']); //Add the thumbnail to the post!
                }
            }
            catch (Exception $e)
            {
                
            }
        }
        $ret['success'] = true;
        $ret['link'] = $permalink;
        $ret['added_id'] = $new_post_id;        
        return $ret;
    }

    static function getSickNetworkDir($what = null)
    {
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'sicknetwork' . DIRECTORY_SEPARATOR;
        self::checkDir($dir);
        if (!file_exists($dir . 'index.php'))
        {
            @touch($dir . 'index.php');
        }
        $url = $upload_dir['baseurl'] . '/sicknetwork/';

        if ($what == 'backup')
        {
            $dir .= 'backup' . DIRECTORY_SEPARATOR;
            self::checkDir($dir);
            if (!file_exists($dir . 'index.php'))
            {
                @touch($dir . 'index.php');
            }
            $url .= 'backup/';
        }

        return array($dir, $url);
    }

    static function checkDir($dir)
    {
        SickNetworkHelper::getWPFilesystem();
        global $wp_filesystem;
        if (!file_exists($dir))
        {
            if (empty($wp_filesystem))
            {
                @mkdir($dir, 0777, true);
            }
            else
            {
                $wp_filesystem->mkdir($dir, 0777);
            }

            if (!file_exists($dir))
            {
                self::error('Unable to create directory ' . str_replace(ABSPATH, '', $dir) . '. Is its parent directory writable by the server?');
            }
        }
    }

    static function search($array, $key)
    {
        if (is_object($array)) $array = (array)$array;
        if (is_array($array) || is_object($array))
        {
            if (isset($array[$key])) return $array[$key];

            foreach ($array as $subarray)
            {
                $result = self::search($subarray, $key);
                if ($result != null) return $result;
            }
        }

        return null;
    }

    /**
     * @return WP_Filesystem_Base
     */
    public static function getWPFilesystem()
    {
        global $wp_filesystem;

        if (empty($wp_filesystem))
        {
            ob_start();
            if (file_exists(ABSPATH . '/wp-admin/includes/screen.php')) include_once(ABSPATH . '/wp-admin/includes/screen.php');
            if (file_exists(ABSPATH . '/wp-admin/includes/template.php')) include_once(ABSPATH . '/wp-admin/includes/template.php');
            $creds = request_filesystem_credentials('test');
            ob_end_clean();
            $init = WP_Filesystem($creds);
        }
        else
        {
            $init = true;
        }

        return $init;
    }

    function startsWith($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public static function getNiceURL($pUrl, $showHttp = false)
    {
        $url = $pUrl;

        if (self::startsWith($url, 'http://'))
        {
            if (!$showHttp) $url = substr($url, 7);
        }
        else if (self::startsWith($pUrl, 'https://'))
        {
            if (!$showHttp) $url = substr($url, 8);
        }
        else
        {
            if ($showHttp) $url = 'http://'.$url;
        }

        if (self::endsWith($url, '/'))
        {
            if (!$showHttp) $url = substr($url, 0, strlen($url) - 1);
        }
        else
        {
            $url = $url . '/';
        }
        return $url;
    }

    public static function endSession()
    {
        session_write_close();
        ob_end_flush();
    }

    public static function fetchUrl($url, $postdata)
    {
        $agent= 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        $data = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if (($data === false) && ($http_status == 0)) {
            throw new Exception('Http Error: ' . $err);
        }
        else if (preg_match('/<sicknetwork>(.*)<\/sicknetwork>/', $data, $results) > 0) {
            $result = $results[1];
            $information = unserialize(base64_decode($result));
            return $information;
        }
        else if ($data == '')
        {
            throw new Exception('Something went wrong while contacting the child site. Please check if there is an error on the child site. This error could also be caused by trying to clone or restore a site to large for your server settings.');
        }
        else
        {
            throw new Exception('Child plugin is disabled or the security key is incorrect. Please resync with your main installation.');
        }
    }


    public static function randString($length, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
    {
        $str = '';
        $count = strlen($charset);
        while ($length--)
        {
            $str .= $charset[mt_rand(0, $count - 1)];
        }
        return $str;
    }

    public static function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    public static function human_filesize($bytes, $decimals = 2) {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}

?>
