<?php

/*  Copyright 2014 Matti Lattu

    This file is part of Digabi Skaba.
 
    Digabi Skaba is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// log function - from http://fuelyourcoding.com/simple-debugging-with-wordpress/

if(!function_exists('_log')){
  function _log( $message ) {
   if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log("debug log message: ".print_r( $message, true ), 4 );
      } else {
        error_log("debug log message: ".$message, 4 );
      }
    }
  }
}

/**
 * Writes a message to httpd log.
 * @param string $log_message
 */
function digabi_skaba_warning ($log_message) {
	error_log("Warning from WP plugin digabi_skaba: ".$log_message, 4);
}

/**
 * Clean a given $str to be used as a public URL.
 * @param string $str URL string to clean.
 * @return string Cleaned URL string.
 */
function digabi_skaba_html_cleanup_url ($str) {
    $str = preg_replace('/</', '&lt;', $str);
    $str = preg_replace('/>/', '&gt;', $str);
    $str = preg_replace('/"/', '', $str);
    $str = preg_replace('/\'/', '', $str);
    
    return $str;
}

/**
 * Clean a given text to be used as a public text.
 * @param string $str String to clean.
 * @return string Cleaned string.
 */
function digabi_skaba_html_cleanup_text ($str) {
    $str = preg_replace('/\n/', '', $str);
    
    $str = htmlentities($str, ENT_QUOTES | ENT_HTML401);
    
    return $str;
}

/**
 * Returns unique hash for a Digabi Skaba post.
 * @param string $header Post header (title)
 * @param string $url Post URL
 * @param string $content Post content
 * @return string Hash (SHA1) for the given parameters.
 */
function digabi_skaba_hash ($header, $url, $content) {
    return sha1($header.$url.$content);
}

/**
 * Check whether a post with a given Digabi Skaba hash exists.
 * @param string $hash Hash to look for
 * @return boolean TRUE if a post was found, otherwise FALSE.
 */
function digabi_skaba_hash_exists ($hash) {
    $search_array = Array(
        'post_type' => 'post',
    );
    
    $this_query = new WP_Query($search_array);

    if ($this_query->have_posts()) {
        // We have matching posts
        
        foreach ($this_query->posts as $this_post) {
            // Get necessary fields
            $this_hash = get_post_meta($this_post->ID, 'digabi_skaba_hash', TRUE);
            
            if ($this_hash == $hash) {
                // We found the hash which we were looking for
                
                return TRUE;
            }
        }
    }
    
    // We didn't find the post
    return FALSE;
}


/**
 * Return a web page thumbnail from bluga.net web service using Easythumb API
 * (http://webthumb.bluga.net/api-easythumb). The function fetches the JPEG
 * file from the bluga service. 
 * @global $DIGABI_SKABA_BLUGA_USERID
 * @global $DIGABI_SKABA_BLUGA_CACHE
 * @global $DIGABI_SKABA_BLUGA_APIKEY
 * @param type $url Web page (URL) to store to a thumbnail
 * @param type $filename File to create.
 * @return boolean TRUE on success.
 */
function digabi_skaba_get_bluga_thumbnail ($url, $filename) {
    global $DIGABI_SKABA_BLUGA_USERID;
    global $DIGABI_SKABA_BLUGA_APIKEY;
    global $DIGABI_SKABA_BLUGA_CACHE;
    
    // Make sure that the URL can be retrieved
    if (!digabi_skaba_url_exists($url)) {
        _log("Given URL $url does not exist");
        return FALSE;
    }
    
    // API URL
    $bluga_easythumb_url = "http://webthumb.bluga.net/easythumb.php?";
    $thumbnail_size = 'large'; // Size of the thumbnail to return small, medium, medium2, large
    
    // Generate security hash
    $sec_hash = md5(gmdate('Ymd').$url.$DIGABI_SKABA_BLUGA_APIKEY);
    
    $user = $DIGABI_SKABA_BLUGA_USERID;
    $url_encoded = urlencode($url);
    $cache = $DIGABI_SKABA_BLUGA_CACHE;
    
    $url = $bluga_easythumb_url.
           "user=".$user."&".
            "url=".$url_encoded."&".
            "size=".$thumbnail_size."&".
            "cache=".$cache."&".
            "hash=".$sec_hash;
    
    // FIXME: Debug
    /*
    _log("Bluga url: ".$url);
    copy('/tmp/sample_image.jpeg', $filename);
    return TRUE;
    */
    // End of debug
    
    $thumbnail_data = file_get_contents($url);
    
    if ($thumbnail_data) {
        // We got thumnbnail from Bluga service
        if (!file_put_contents($filename, $thumbnail_data)) {
            // Failed to save thumnbnail
            _log("Failed to write retrieved Bluga thumbnail to file: ".$filename);
            return FALSE;
        }
    }
    else {
        // Retrieving a thumnbnail failed
        _log("Failed to retrieve a thumbnail from Bluga URL: ".$url);
        return FALSE;
    }
    
    // Success!
    return TRUE;
}

/**
 * Check wether a given URL exists.
 * @param string $url URL to check
 * @return boolean FALSE if URL returns HTTP error code 404, otherwise TRUE
 */
function digabi_skaba_url_exists ($url) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // $retcode >= 400 -> not found, $retcode = 200, found.
    curl_close($ch);
    
    if (($retcode == 0) or ($retcode >= 400)) {
        return FALSE;
    }
    
    return TRUE;
}

?>